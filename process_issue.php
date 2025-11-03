<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'document_number' => '',
    'debug' => []
];

// Custom escape function that handles NULL values properly
function safe_escape($db, $value) {
    if ($value === null) {
        return 'NULL';
    }
    return "'" . $db->escape($value) . "'";
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Only POST requests are allowed.');
    }

    // Retrieve and validate POST data
    $doc_type = $_POST['doc_type'] ?? '';
    $item_id = (int)($_POST['item_id'] ?? 0);
    $requestor_id = (int)($_POST['requestor_id'] ?? 0);
    $issue_qty = (int)($_POST['issue_qty'] ?? 0);
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $doc_number = trim($_POST['doc_number'] ?? '');
    $remarks = $_POST['remarks'] ?? '';
    $item_type = $_POST['item_type'] ?? ''; // Added item_type for better handling

    $response['debug']['received_data'] = [
        'doc_type' => $doc_type,
        'item_id' => $item_id,
        'requestor_id' => $requestor_id,
        'issue_qty' => $issue_qty,
        'issue_date' => $issue_date,
        'doc_number' => $doc_number,
        'item_type' => $item_type
    ];

    // Comprehensive validation
    if (empty($doc_type) || !in_array($doc_type, ['par', 'ics'])) {
        throw new Exception('Invalid document type. Must be "par" or "ics".');
    }

    if ($item_id <= 0) {
        throw new Exception('Invalid item selected.');
    }

    if ($requestor_id <= 0) {
        throw new Exception('Invalid requestor selected.');
    }

    if ($issue_qty <= 0) {
        throw new Exception('Issue quantity must be greater than zero.');
    }

    if (empty($doc_number)) {
        throw new Exception('Document number is required.');
    }

    if (!preg_match('/^\d{1,4}$/', $doc_number)) {
        throw new Exception('Document number must be 1-4 digits.');
    }

    // Validate issue date
    if (!strtotime($issue_date)) {
        throw new Exception('Invalid issue date format.');
    }

    // Determine item type if not provided
    if (empty($item_type)) {
        $item_type = ($doc_type === 'par') ? 'ppe' : 'semi';
    }

    $response['debug']['determined_item_type'] = $item_type;

    // Start transaction
    $db->query("START TRANSACTION");

    try {
        // Identify table and document configuration
        if ($doc_type === 'par') {
            $item_table = 'properties';
            $doc_field = 'PAR_No';
            $doc_display = 'PAR';
            $qty_field = 'qty';
            $use_property_id = true;
        } else {
            $item_table = 'semi_exp_prop';
            $doc_field = 'ICS_No';
            $doc_display = 'ICS';
            $qty_field = 'qty_left';
            $use_property_id = false;
        }

        // Fetch and validate item
        $item = find_by_id($item_table, $item_id);
        if (!$item) {
            throw new Exception("Item not found in {$item_table} with ID: {$item_id}");
        }

        $response['debug']['item_data'] = [
            'id' => $item['id'],
            'name' => $item['article'] ?? $item['item'] ?? 'Unknown',
            'available_qty' => $item[$qty_field] ?? 0
        ];

        // Stock validation
        $available_qty = isset($item[$qty_field]) ? (int)$item[$qty_field] : 0;
        
        if ($available_qty <= 0) {
            throw new Exception('Item is out of stock.');
        }

        if ($issue_qty > $available_qty) {
            throw new Exception("Issued quantity exceeds available stock. Available: {$available_qty}, Requested: {$issue_qty}");
        }

        // Generate full document number
        $yearMonth = date('Y-m');
        $fullDocNo = "{$yearMonth}-" . str_pad($doc_number, 4, '0', STR_PAD_LEFT);
        $response['document_number'] = $fullDocNo;

        // Check for duplicate document number
        $check_sql = "SELECT id FROM transactions WHERE {$doc_field} = " . safe_escape($db, $fullDocNo);
        $check_result = $db->query($check_sql);
        
        if ($db->num_rows($check_result) > 0) {
            throw new Exception("{$doc_display} number {$fullDocNo} already exists. Please use a different document number.");
        }

        // Validate requestor exists
        $requestor_check = find_by_id('employees', $requestor_id);
        if (!$requestor_check) {
            throw new Exception('Selected requestor not found in the system.');
        }

        // Calculate return due date (3 years for semi-expendable items)
        $return_due_date = ($doc_type === 'par') ? null : date('Y-m-d', strtotime($issue_date . ' +3 years'));

        // Check table structure for property_id column
        $property_id_column_exists = false;
        $check_column = $db->query("SHOW COLUMNS FROM transactions LIKE 'property_id'");
        if ($db->num_rows($check_column) > 0) {
            $property_id_column_exists = true;
        }

        $response['debug']['table_structure'] = [
            'property_id_column_exists' => $property_id_column_exists,
            'use_property_id' => $use_property_id
        ];

        // Build transaction insert query with proper NULL handling
        if ($use_property_id && $property_id_column_exists) {
            // For PPE items when property_id column exists
            $insert_sql = sprintf(
                "INSERT INTO transactions 
                (employee_id, property_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_due_date)
                VALUES (%d, %d, %d, %s, 'issue', %s, 'Issued', %s, %s)",
                $doc_field,
                $requestor_id,
                $item_id,
                $issue_qty,
                safe_escape($db, $fullDocNo),
                safe_escape($db, $issue_date),
                safe_escape($db, $remarks),
                safe_escape($db, $return_due_date)
            );
        } else {
            // For semi-expendable items OR if property_id column doesn't exist
            $insert_sql = sprintf(
                "INSERT INTO transactions 
                (employee_id, item_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_due_date)
                VALUES (%d, %d, %d, %s, 'issue', %s, 'Issued', %s, %s)",
                $doc_field,
                $requestor_id,
                $item_id,
                $issue_qty,
                safe_escape($db, $fullDocNo),
                safe_escape($db, $issue_date),
                safe_escape($db, $remarks),
                safe_escape($db, $return_due_date)
            );
        }

        $response['debug']['insert_sql'] = $insert_sql;

        // Insert transaction record
        if (!$db->query($insert_sql)) {
            $error_msg = "Failed to record transaction: " . $db->get_last_error();
            $response['debug']['insert_error'] = $error_msg;
            throw new Exception($error_msg);
        }

        $transaction_id = $db->insert_id();
        $response['debug']['transaction_id'] = $transaction_id;

        // Update stock quantity
        $new_qty = $available_qty - $issue_qty;
        $update_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item_id}'";
        
        $response['debug']['update_sql'] = $update_sql;

        if (!$db->query($update_sql)) {
            $error_msg = "Failed to update stock quantity: " . $db->get_last_error();
            $response['debug']['update_error'] = $error_msg;
            throw new Exception($error_msg);
        }

        // Commit transaction
        $db->query("COMMIT");

        // Build success response
        $item_name = $item['article'] ?? $item['item'] ?? 'Item';
        $success_message = "{$doc_display} issuance successful! ";
        $success_message .= "Issued {$issue_qty} of '{$item_name}' to ";
        $success_message .= $requestor_check['first_name'] . ' ' . $requestor_check['last_name'];
        $success_message .= " with {$doc_display} No: {$fullDocNo}";

        if ($return_due_date) {
            $success_message .= " (Due: " . date('M d, Y', strtotime($return_due_date)) . ")";
        }

        $response['success'] = true;
        $response['message'] = $success_message;
        $response['return_due_date'] = $return_due_date;
        $response['issued_quantity'] = $issue_qty;
        $response['item_name'] = $item_name;
        $response['requestor_name'] = $requestor_check['first_name'] . ' ' . $requestor_check['last_name'];

    } catch (Exception $e) {
        // Rollback transaction on any error
        $db->query("ROLLBACK");
        throw new Exception("Transaction failed: " . $e->getMessage());
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['success'] = false;
    
    // Log the error for debugging
    error_log("Single Issue Error: " . $e->getMessage());
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