<?php
require_once('includes/load.php');
page_require_level(2);
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

// Identify table and quantity column
if ($doc_type === 'ics') {
    $item_table = 'semi_exp_prop';
    $doc_field  = 'ICS_No';
    $qty_field  = 'qty_left'; // Actual remaining quantity column in semi_exp_prop
} elseif ($doc_type === 'par') {
    $item_table = 'properties';
    $doc_field  = 'PAR_No';
    $qty_field  = 'qty_left'; // Actual remaining quantity column in properties
} else {
    $response['message'] = 'Invalid document type.';
    echo json_encode($response);
    exit;
}

// Fetch item
$item = find_by_id($item_table, $item_id);
if (!$item) {
    $response['message'] = 'Item not found.';
    echo json_encode($response);
    exit;
}

// Stock validation
$available_qty = isset($item[$qty_field]) ? (int)$item[$qty_field] : 0;
if ($issue_qty > $available_qty) {
    $response['message'] = 'Issued quantity exceeds available stock.';
    echo json_encode($response);
    exit;
}

// Generate document number
$yearMonth = date('Y-m');
$fullDocNo = "{$yearMonth}-" . str_pad($doc_number, 4, '0', STR_PAD_LEFT);
$return_due = date('Y-m-d', strtotime($issue_date . ' +3 years'));

// Start transaction
$db->query("START TRANSACTION");

try {
    // Insert transaction
    $insert_sql = sprintf("
        INSERT INTO transactions 
        (employee_id, item_id, quantity, %s, transaction_type, transaction_date, status, remarks, return_date)
        VALUES ('%d', '%d', '%d', '%s', 'issue', '%s', 'Issued', '%s', '%s')
    ",
        $doc_field,
        $requestor_id,
        $item_id,
        $issue_qty,
        $db->escape($fullDocNo),
        $db->escape($issue_date),
        $db->escape($remarks),
        $db->escape($return_due)
    );

    if (!$db->query($insert_sql)) {
        throw new Exception('Failed to record transaction.');
    }

    // Update remaining quantity properly
    $new_qty = $available_qty - $issue_qty;
    $update_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item_id}'";

    if (!$db->query($update_sql)) {
        throw new Exception('Failed to update stock quantity.');
    }

    // Commit
    $db->query("COMMIT");

    $response['success'] = true;
    $response['message'] = strtoupper($doc_type) . " issuance recorded successfully.";
    $response['document_number'] = $fullDocNo;

} catch (Exception $e) {
    $db->query("ROLLBACK");
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
