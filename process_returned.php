<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$transaction_id = (int)($_POST['transaction_id'] ?? 0);
$return_status  = trim($_POST['return_status'] ?? '');
$remarks        = trim($_POST['remarks'] ?? '');
$return_qty     = (int)($_POST['return_qty'] ?? 0);
$return_date    = $_POST['return_date'] ?? date('Y-m-d');

if (!$transaction_id || !$return_status || !$return_qty) {
    $response['message'] = 'Please fill in all required fields.';
    echo json_encode($response);
    exit;
}

// ✅ Fetch transaction
$transaction = find_by_id('transactions', $transaction_id);
if (!$transaction) {
    $response['message'] = 'Transaction not found.';
    echo json_encode($response);
    exit;
}

// ✅ Validate return quantity
$issued_qty = (int)$transaction['quantity'];
$qty_returned = (int)$transaction['qty_returned'];
$total_returned = $qty_returned + $return_qty;

if ($total_returned > $issued_qty) {
    $response['message'] = 'Return quantity exceeds issued quantity.';
    echo json_encode($response);
    exit;
}

// ✅ Generate RRSP number
$yearMonth = date('Y-m');
$monthStart = $yearMonth . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

$count_sql = "
    SELECT COUNT(*) AS rrsp_count 
    FROM transactions 
    WHERE RRSP_No IS NOT NULL 
      AND RRSP_No != ''
      AND transaction_date BETWEEN '{$monthStart}' AND '{$monthEnd}'
";
$result = $db->query($count_sql);
$count_data = $result ? $result->fetch_assoc() : ['rrsp_count' => 0];
$newCount = (int)$count_data['rrsp_count'] + 1;
$rrsp_no = "{$yearMonth}-" . str_pad($newCount, 4, '0', STR_PAD_LEFT);

$new_status = ($total_returned == $issued_qty) ? 'Returned' : 'Partially Returned';

$db->query("START TRANSACTION");

try {
    // ✅ Update transaction
    $update_sql = "
        UPDATE transactions 
        SET 
            qty_returned = '{$total_returned}',
            status = '{$db->escape($new_status)}',
            remarks = '{$db->escape($remarks)}',
            RRSP_No = '{$db->escape($rrsp_no)}',
            return_date = '{$db->escape($return_date)}',
            transaction_type = 'return'
        WHERE id = '{$transaction_id}'
    ";
    if (!$db->query($update_sql)) {
        throw new Exception('Failed to update transaction.');
    }

    // ✅ Return items to stock
    $item_table = !empty($transaction['ICS_No']) ? 'semi_exp_prop' : 'properties';
    $item = find_by_id($item_table, $transaction['item_id']);
    if ($item) {
        $qty_field = !empty($transaction['ICS_No']) ? 'qty_left' : 'qty';
        $new_qty = $item[$qty_field] + $return_qty;
        $db->query("UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item['id']}'");
    }

    $db->query("COMMIT");
    $response['success'] = true;
    $response['message'] = "Item successfully marked as {$new_status}. RRSP No: {$rrsp_no}";
} catch (Exception $e) {
    $db->query("ROLLBACK");
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
