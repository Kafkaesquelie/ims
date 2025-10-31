<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'document_number' => '',
    'items_issued' => 0,
    'errors' => []
];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Retrieve POST data
$selected_items = $_POST['selected_items'] ?? '';
$item_type      = $_POST['item_type'] ?? '';
$requestor_id   = (int)($_POST['requestor_id'] ?? 0);
$issue_date     = $_POST['issue_date'] ?? date('Y-m-d');
$doc_number     = trim($_POST['doc_number'] ?? '');
$remarks        = $_POST['remarks'] ?? '';
$quantities     = json_decode($_POST['quantities'] ?? '{}', true);

if (!$selected_items || !$item_type || !$requestor_id || !$doc_number) {
    $response['message'] = 'Please fill in all required fields.';
    echo json_encode($response);
    exit;
}

$items = json_decode($selected_items, true);
if (!is_array($items) || empty($items)) {
    $response['message'] = 'No items selected.';
    echo json_encode($response);
    exit;
}

// Start transaction with foreign key checks disabled
$db->query("SET FOREIGN_KEY_CHECKS=0");
$db->query("START TRANSACTION");

try {
    // Identify table and document type
    if ($item_type === 'ppe') {
        $item_table = 'properties';
        $doc_field  = 'PAR_No';
        $doc_type   = 'par';
        $qty_field  = 'qty';
        $id_field   = 'properties_id'; // Use properties_id for PPE items
    } elseif ($item_type === 'semi') {
        $item_table = 'semi_exp_prop';
        $doc_field  = 'ICS_No';
        $doc_type   = 'ics';
        $qty_field  = 'qty_left';
        $id_field   = 'item_id'; // Use item_id for semi-expendable
    } else {
        throw new Exception('Invalid item type.');
    }

    // Generate document number
    $yearMonth = date('Y-m');
    $fullDocNo = "{$yearMonth}-" . str_pad($doc_number, 4, '0', STR_PAD_LEFT);

    // Check for duplicate document number
    $check_doc = $db->query("SELECT id FROM transactions WHERE {$doc_field} = '{$fullDocNo}' AND status = 'Issued'");
    if ($db->num_rows($check_doc) > 0) {
        throw new Exception('Document number already exists: ' . $fullDocNo);
    }

    // Calculate return due date
    $return_due_date = ($item_type === 'ppe') ? null : date('Y-m-d', strtotime($issue_date . ' +3 years'));

    $issued_count = 0;
    $errors = [];
    
    foreach ($items as $item) {
        $item_id = $item['id'];
        $issue_qty = isset($quantities[$item_id]) ? (int)$quantities[$item_id] : 1;
        
        if ($issue_qty <= 0) {
            $errors[] = "Invalid quantity for item: {$item['name']}";
            continue;
        }

        // Fetch item
        $item_data = find_by_id($item_table, $item_id);
        if (!$item_data) {
            $errors[] = "Item not found: {$item['name']}";
            continue;
        }

        // Stock validation
        $available_qty = isset($item_data[$qty_field]) ? (int)$item_data[$qty_field] : 0;
        if ($issue_qty > $available_qty) {
            $errors[] = "Insufficient stock for {$item['name']} (Available: {$available_qty}, Requested: {$issue_qty})";
            continue;
        }

        // Insert transaction - use different ID fields based on item type
        if ($item_type === 'ppe') {
            // For PPE items, use properties_id field
            $insert_sql = sprintf("
                INSERT INTO transactions 
                (employee_id, properties_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_due_date)
                VALUES ('%d', '%d', '%d', '%s', 'issue', '%s', 'Issued', '%s', '%s')
            ",
                $doc_field,
                $requestor_id,
                $item_id, // Use actual properties ID
                $issue_qty,
                $db->escape($fullDocNo),
                $db->escape($issue_date),
                $db->escape($remarks),
                $db->escape($return_due_date)
            );
        } else {
            // For semi-expendable items, use item_id field
            $insert_sql = sprintf("
                INSERT INTO transactions 
                (employee_id, item_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_due_date)
                VALUES ('%d', '%d', '%d', '%s', 'issue', '%s', 'Issued', '%s', '%s')
            ",
                $doc_field,
                $requestor_id,
                $item_id, // Use actual item ID
                $issue_qty,
                $db->escape($fullDocNo),
                $db->escape($issue_date),
                $db->escape($remarks),
                $db->escape($return_due_date)
            );
        }

        if (!$db->query($insert_sql)) {
            $errors[] = "Failed to record transaction for item: {$item['name']} - " . $db->get_last_error();
            continue;
        }

        // Update stock
        $new_qty = $available_qty - $issue_qty;
        $update_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item_id}'";

        if (!$db->query($update_sql)) {
            $errors[] = "Failed to update stock for item: {$item['name']}";
            continue;
        }

        $issued_count++;
    }

    if ($issued_count === 0) {
        throw new Exception('No items were successfully issued. ' . implode(', ', $errors));
    }

    // Commit
    $db->query("COMMIT");
    $db->query("SET FOREIGN_KEY_CHECKS=1"); // Re-enable foreign key checks

    $response['success'] = true;
    $response['message'] = "Successfully issued {$issued_count} item(s) with {$doc_type} number: {$fullDocNo}";
    $response['document_number'] = $fullDocNo;
    $response['items_issued'] = $issued_count;
    $response['return_due_date'] = $return_due_date;
    
    if (!empty($errors)) {
        $response['message'] .= " Some items had issues: " . implode(', ', $errors);
    }

} catch (Exception $e) {
    $db->query("ROLLBACK");
    $db->query("SET FOREIGN_KEY_CHECKS=1"); // Re-enable foreign key checks even on error
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['errors'] = $errors ?? [];
}

echo json_encode($response);
exit;
?>