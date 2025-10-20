<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$transaction_id = (int)($_POST['transaction_id'] ?? 0);
$reissue_qty    = (int)($_POST['reissue_qty'] ?? 0);
$reissue_date   = $db->escape($_POST['reissue_date'] ?? date('Y-m-d'));
$remarks        = $db->escape($_POST['remarks'] ?? '');

if (!$transaction_id || !$reissue_qty) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// 🟢 Fetch transaction
$transaction = find_by_id('transactions', $transaction_id);
if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
    exit;
}

$total_returned = (int)$transaction['qty_returned'];
$total_reissued = (int)$transaction['qty_re_issued'];
$remaining_to_reissue = $total_returned - $total_reissued;

// 🟢 Validate available quantity
if ($remaining_to_reissue <= 0) {
    echo json_encode(['success' => false, 'message' => 'No items available to re-issue.']);
    exit;
}
if ($reissue_qty > $remaining_to_reissue) {
    echo json_encode(['success' => false, 'message' => "Only {$remaining_to_reissue} item(s) available for reissue."]);
    exit;
}

// 🟢 Calculate new reissued total and status
$new_reissued_total = $total_reissued + $reissue_qty;
$status = ($new_reissued_total == $total_returned)
    ? 'Re-Issued'
    : 'Partially Re-Issued';

$db->query("START TRANSACTION");

try {
    // ✅ Update the transaction
    $update_sql = "
        UPDATE transactions
        SET 
            qty_re_issued = '{$new_reissued_total}',
            re_issue_date = '{$reissue_date}',
            remarks = '{$remarks}',
            status = '{$status}'
        WHERE id = '{$transaction_id}'
    ";
    if (!$db->query($update_sql)) {
        throw new Exception('Failed to update transaction.');
    }

    // ✅ Deduct reissued items from stock again
    $item_table = !empty($transaction['ICS_No']) ? 'semi_exp_prop' : 'properties';
    $item = find_by_id($item_table, $transaction['item_id']);
    if ($item) {
        $qty_field = !empty($transaction['ICS_No']) ? 'qty_left' : 'qty';
        $new_qty = $item[$qty_field] - $reissue_qty;
        $db->query("UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item['id']}'");
    }

    $db->query("COMMIT");

    echo json_encode([
        'success' => true,
        'message' => "Successfully re-issued {$reissue_qty} item(s)."
    ]);
} catch (Exception $e) {
    $db->query("ROLLBACK");
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
