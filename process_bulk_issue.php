<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'document_number' => '',
    'items_issued' => 0,
    'errors' => [],
    'debug' => []
];

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Only POST requests are allowed.');
    }

    // Retrieve and validate POST data
    $selected_items = $_POST['selected_items'] ?? '';
    $item_type = $_POST['item_type'] ?? '';
    $requestor_id = (int)($_POST['requestor_id'] ?? 0);
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $doc_number = trim($_POST['doc_number'] ?? '');
    $remarks = $_POST['remarks'] ?? '';
    $quantities = $_POST['quantities'] ?? '{}';

    $response['debug']['received_data'] = [
        'item_type' => $item_type,
        'requestor_id' => $requestor_id,
        'issue_date' => $issue_date,
        'doc_number' => $doc_number,
        'selected_items_length' => strlen($selected_items),
        'quantities_length' => strlen($quantities)
    ];

    // Basic validation
    if (empty($selected_items)) {
        throw new Exception('No items selected for issuance.');
    }

    if (empty($item_type) || !in_array($item_type, ['ppe', 'semi'])) {
        throw new Exception('Invalid item type. Must be "ppe" or "semi".');
    }

    if ($requestor_id <= 0) {
        throw new Exception('Invalid requestor selected.');
    }

    if (empty($doc_number)) {
        throw new Exception('Document number is required.');
    }

    if (!preg_match('/^\d{1,4}$/', $doc_number)) {
        throw new Exception('Document number must be 1-4 digits.');
    }

    // Validate issue date
    if (!strtotime($issue_date)) {
        throw new Exception('Invalid issue date.');
    }

    // Decode JSON data
    $items = json_decode($selected_items, true);
    $quantities_array = json_decode($quantities, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid data format: ' . json_last_error_msg());
    }

    if (!is_array($items) || empty($items)) {
        throw new Exception('No valid items selected.');
    }

    if (!is_array($quantities_array)) {
        throw new Exception('Invalid quantities data.');
    }

    $response['debug']['decoded_data'] = [
        'items_count' => count($items),
        'quantities_count' => count($quantities_array)
    ];

    // Start transaction
    $db->query("START TRANSACTION");

    try {
        // Identify table and document type
        if ($item_type === 'ppe') {
            $item_table = 'properties';
            $doc_field = 'PAR_No';
            $doc_type = 'PAR';
            $qty_field = 'qty';
            $id_field = 'properties_id';
        } else {
            $item_table = 'semi_exp_prop';
            $doc_field = 'ICS_No';
            $doc_type = 'ICS';
            $qty_field = 'qty_left';
            $id_field = 'item_id';
        }

        // Generate full document number
        $yearMonth = date('Y-m');
        $fullDocNo = "{$yearMonth}-" . str_pad($doc_number, 4, '0', STR_PAD_LEFT);
        $response['document_number'] = $fullDocNo;

        // Check for duplicate document number
        $check_sql = "SELECT id FROM transactions WHERE {$doc_field} = '{$db->escape($fullDocNo)}'";
        $check_result = $db->query($check_sql);
        
        if ($db->num_rows($check_result) > 0) {
            throw new Exception("{$doc_type} number {$fullDocNo} already exists. Please use a different document number.");
        }

        // Validate requestor exists
        $requestor_check = find_by_id('employees', $requestor_id);
        if (!$requestor_check) {
            throw new Exception('Selected requestor not found in the system.');
        }

        // Calculate return due date for semi-expendable items
        $return_due_date = ($item_type === 'ppe') ? null : date('Y-m-d', strtotime($issue_date . ' +3 years'));

        $issued_count = 0;
        $errors = [];
        $successful_items = [];

        foreach ($items as $index => $item) {
            $item_id = (int)$item['id'];
            $issue_qty = isset($quantities_array[$item_id]) ? (int)$quantities_array[$item_id] : 1;
            
            $response['debug']['processing_item'] = [
                'index' => $index,
                'item_id' => $item_id,
                'item_name' => $item['name'] ?? 'Unknown',
                'issue_qty' => $issue_qty
            ];

            // Validate quantity
            if ($issue_qty <= 0) {
                $errors[] = "Invalid quantity ({$issue_qty}) for item: {$item['name']}";
                continue;
            }

            // Fetch and validate item
            $item_data = find_by_id($item_table, $item_id);
            if (!$item_data) {
                $errors[] = "Item not found: {$item['name']} (ID: {$item_id})";
                continue;
            }

            // Stock validation
            $available_qty = isset($item_data[$qty_field]) ? (int)$item_data[$qty_field] : 0;
            if ($available_qty <= 0) {
                $errors[] = "Item out of stock: {$item['name']}";
                continue;
            }

            if ($issue_qty > $available_qty) {
                $errors[] = "Insufficient stock for {$item['name']} (Available: {$available_qty}, Requested: {$issue_qty})";
                continue;
            }

            // Prepare transaction data
            $transaction_data = [
                'employee_id' => $requestor_id,
                'quantity' => $issue_qty,
                $doc_field => $fullDocNo,
                'transaction_type' => 'issue',
                'transaction_date' => $issue_date,
                'status' => 'Issued',
                'remarks' => $remarks,
                'return_due_date' => $return_due_date
            ];

            // Set the appropriate ID field based on item type
            $transaction_data[$id_field] = $item_id;

            // Build insert query
            $columns = implode(', ', array_keys($transaction_data));
            $values = "'" . implode("', '", array_map([$db, 'escape'], array_values($transaction_data))) . "'";
            
            $insert_sql = "INSERT INTO transactions ({$columns}) VALUES ({$values})";
            
            $response['debug']['insert_sql'] = $insert_sql;

            if (!$db->query($insert_sql)) {
                $error_msg = "Database error for {$item['name']}: " . $db->get_last_error();
                $errors[] = $error_msg;
                $response['debug']['insert_error'] = $error_msg;
                continue;
            }

            // Update stock
            $new_qty = $available_qty - $issue_qty;
            $update_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item_id}'";
            
            if (!$db->query($update_sql)) {
                $error_msg = "Failed to update stock for {$item['name']}: " . $db->get_last_error();
                $errors[] = $error_msg;
                $response['debug']['update_error'] = $error_msg;
                // Rollback the transaction insert for this item
                continue;
            }

            $issued_count++;
            $successful_items[] = [
                'name' => $item['name'],
                'quantity' => $issue_qty,
                'stock_no' => $item['stock'] ?? 'N/A'
            ];
        }

        $response['debug']['processing_results'] = [
            'issued_count' => $issued_count,
            'error_count' => count($errors),
            'successful_items' => $successful_items
        ];

        // Check if any items were successfully issued
        if ($issued_count === 0) {
            if (!empty($errors)) {
                throw new Exception("No items were issued. Errors: " . implode('; ', array_slice($errors, 0, 5)));
            } else {
                throw new Exception('No items were processed. Please check your selection.');
            }
        }

        // Commit transaction
        $db->query("COMMIT");

        // Build success message
        $success_message = "Successfully issued {$issued_count} item(s) with {$doc_type} number: {$fullDocNo}";
        
        if (!empty($successful_items)) {
            $item_names = array_column($successful_items, 'name');
            $success_message .= " - " . implode(', ', array_slice($item_names, 0, 3));
            if (count($successful_items) > 3) {
                $success_message .= " and " . (count($successful_items) - 3) . " more";
            }
        }

        // Add warnings if there were errors with some items
        if (!empty($errors)) {
            $error_count = count($errors);
            $success_message .= ". {$error_count} item(s) had issues: " . implode('; ', array_slice($errors, 0, 3));
            if ($error_count > 3) {
                $success_message .= " and " . ($error_count - 3) . " more errors";
            }
        }

        $response['success'] = true;
        $response['message'] = $success_message;
        $response['items_issued'] = $issued_count;
        $response['successful_items'] = $successful_items;
        $response['errors'] = $errors;
        $response['return_due_date'] = $return_due_date;

    } catch (Exception $e) {
        // Rollback transaction on any error
        $db->query("ROLLBACK");
        throw new Exception("Transaction failed: " . $e->getMessage());
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['success'] = false;
    
    // Log the error for debugging
    error_log("Bulk Issue Error: " . $e->getMessage());
    error_log("POST Data: " . print_r($_POST, true));
}

// For production, remove debug information
if (isset($response['debug'])) {
    // Uncomment the line below to include debug info in response
    // $response['debug'] = $response['debug'];
    // Or comment out the line below to remove debug info
    unset($response['debug']);
}

echo json_encode($response);
exit;
?>