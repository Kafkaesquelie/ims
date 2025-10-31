<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'document_number' => ''
];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Retrieve POST data
$doc_type     = $_POST['doc_type'] ?? '';
$item_id      = (int)($_POST['item_id'] ?? 0);
$requestor_id = (int)($_POST['requestor_id'] ?? 0);
$issue_qty    = (int)($_POST['issue_qty'] ?? 0);
$issue_date   = $_POST['issue_date'] ?? date('Y-m-d');
$doc_number   = trim($_POST['doc_number'] ?? '');
$remarks      = $_POST['remarks'] ?? '';

if (!$doc_type || !$item_id || !$requestor_id || !$issue_qty || !$doc_number) {
    $response['message'] = 'Please fill in all required fields.';
    echo json_encode($response);
    exit;
}

// Start transaction with foreign key checks disabled
$db->query("SET FOREIGN_KEY_CHECKS=0");
$db->query("START TRANSACTION");

try {
    // Identify table and quantity column
    if ($doc_type === 'ics') {
        $item_table = 'semi_exp_prop';
        $doc_field  = 'ICS_No';
        $qty_field  = 'qty_left';
        $use_property_id = false; // Use item_id for semi-expendable
    } elseif ($doc_type === 'par') {
        $item_table = 'properties';
        $doc_field  = 'PAR_No';
        $qty_field  = 'qty';
        $use_property_id = true; // Use property_id for PPE
    } else {
        throw new Exception('Invalid document type.');
    }

    // Fetch item
    $item = find_by_id($item_table, $item_id);
    if (!$item) {
        throw new Exception('Item not found in ' . $item_table . ' with ID: ' . $item_id);
    }

    // Stock validation
    $available_qty = isset($item[$qty_field]) ? (int)$item[$qty_field] : 0;
    if ($issue_qty > $available_qty) {
        throw new Exception('Issued quantity exceeds available stock. Available: ' . $available_qty);
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
    $return_due_date = ($doc_type === 'par') ? null : date('Y-m-d', strtotime($issue_date . ' +3 years'));

    // Check if property_id column exists in transactions table
    $property_id_column_exists = false;
    $check_column = $db->query("SHOW COLUMNS FROM transactions LIKE 'property_id'");
    if ($db->num_rows($check_column) > 0) {
        $property_id_column_exists = true;
    }

    // Insert transaction - use different approaches based on item type
    if ($use_property_id && $property_id_column_exists) {
        // For PPE, use property_id field (if it exists)
        $insert_sql = sprintf("
            INSERT INTO transactions 
            (employee_id, item_id, property_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_due_date)
            VALUES ('%d', 0, '%d', '%d', '%s', 'issue', '%s', 'Issued', '%s', '%s')
        ",
            $doc_field,
            $requestor_id,
            $item_id,
            $issue_qty,
            $db->escape($fullDocNo),
            $db->escape($issue_date),
            $db->escape($remarks),
            $db->escape($return_due_date)
        );
    } else {
        // For semi-expendable OR if property_id column doesn't exist, use item_id field normally
        $insert_sql = sprintf("
            INSERT INTO transactions 
            (employee_id, item_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_due_date)
            VALUES ('%d', '%d', '%d', '%s', 'issue', '%s', 'Issued', '%s', '%s')
        ",
            $doc_field,
            $requestor_id,
            $item_id,
            $issue_qty,
            $db->escape($fullDocNo),
            $db->escape($issue_date),
            $db->escape($remarks),
            $db->escape($return_due_date)
        );
    }

    if (!$db->query($insert_sql)) {
        throw new Exception('Failed to record transaction: ' . $db->get_last_error());
    }

    // Update stock
    $new_qty = $available_qty - $issue_qty;
    $update_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item_id}'";

    if (!$db->query($update_sql)) {
        throw new Exception('Failed to update stock quantity: ' . $db->get_last_error());
    }

    // Commit
    $db->query("COMMIT");
    $db->query("SET FOREIGN_KEY_CHECKS=1"); // Re-enable foreign key checks

    $response['success'] = true;
    $response['message'] = strtoupper($doc_type) . " issuance recorded successfully.";
    $response['document_number'] = $fullDocNo;
    $response['return_due_date'] = $return_due_date;

} catch (Exception $e) {
    $db->query("ROLLBACK");
    $db->query("SET FOREIGN_KEY_CHECKS=1"); // Re-enable foreign key checks even on error
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>