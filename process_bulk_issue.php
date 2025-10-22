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

// Identify table and document type
if ($item_type === 'ppe') {
    $item_table = 'properties';
    $doc_field  = 'PAR_No';
    $doc_type   = 'par';
    $qty_field  = 'qty';
} elseif ($item_type === 'semi') {
    $item_table = 'semi_exp_prop';
    $doc_field  = 'ICS_No';
    $doc_type   = 'ics';
    $qty_field  = 'qty_left';
} else {
    $response['message'] = 'Invalid item type.';
    echo json_encode($response);
    exit;
}

// Generate document number
$yearMonth = date('Y-m');
$fullDocNo = "{$yearMonth}-" . str_pad($doc_number, 4, '0', STR_PAD_LEFT);

// Calculate return due date
$return_due_date = '';
if ($item_type === 'ppe') {
    $return_due_date = null;
} else {
    $return_due_date = date('Y-m-d', strtotime($issue_date . ' +3 years'));
}

// Start transaction
$db->query("START TRANSACTION");

try {
    $issued_count = 0;
    
    foreach ($items as $item) {
        $item_id = $item['id'];
        $issue_qty = isset($quantities[$item_id]) ? (int)$quantities[$item_id] : 1;
        
        if ($issue_qty <= 0) {
            $response['errors'][] = "Invalid quantity for item: {$item['name']}";
            continue;
        }

        // Fetch item
        $item_data = find_by_id($item_table, $item_id);
        if (!$item_data) {
            $response['errors'][] = "Item not found: {$item['name']}";
            continue;
        }

        // Stock validation
        $available_qty = isset($item_data[$qty_field]) ? (int)$item_data[$qty_field] : 0;
        if ($issue_qty > $available_qty) {
            $response['errors'][] = "Insufficient stock for {$item['name']} (Available: {$available_qty}, Requested: {$issue_qty})";
            continue;
        }

        // Insert transaction
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

        if (!$db->query($insert_sql)) {
            throw new Exception('Failed to record transaction for item: ' . $item['name'] . ' - ' . $db->error());
        }

        // Update stock
        $new_qty = $available_qty - $issue_qty;
        $update_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item_id}'";

        if (!$db->query($update_sql)) {
            throw new Exception('Failed to update stock for item: ' . $item['name'] . ' - ' . $db->error());
        }

        $issued_count++;
    }

    if ($issued_count === 0) {
        throw new Exception('No items were successfully issued. Check errors above.');
    }

    // Commit
    $db->query("COMMIT");

    $response['success'] = true;
    $response['message'] = "Bulk {$doc_type} issuance completed successfully.";
    $response['document_number'] = $fullDocNo;
    $response['items_issued'] = $issued_count;
    $response['return_due_date'] = $return_due_date;

} catch (Exception $e) {
    $db->query("ROLLBACK");
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>