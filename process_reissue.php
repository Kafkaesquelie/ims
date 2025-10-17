<?php
require_once('includes/load.php');
page_require_level(1); // Staff or Admin only

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$transaction_id = (int)$_POST['transaction_id'];
$reissue_qty    = (int)$_POST['reissue_qty'];
$reissue_date   = $db->escape($_POST['reissue_date']);
$remarks        = $db->escape($_POST['remarks'] ?? '');

// 🟢 Validate transaction
$transaction = find_by_id('transactions', $transaction_id);
if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
    exit;
}

$total_returned = (int)$transaction['qty_returned'];
$total_reissued = (int)$transaction['qty_re_issued'];

// 🟢 Check remaining reissuable quantity
$remaining_to_reissue = $total_returned - $total_reissued;
if ($remaining_to_reissue <= 0) {
    echo json_encode(['success' => false, 'message' => 'No items available to re-issue.']);
    exit;
}

if ($reissue_qty > $remaining_to_reissue) {
    echo json_encode(['success' => false, 'message' => "You can only re-issue up to {$remaining_to_reissue} item(s)."]);
    exit;
}

// 🟢 Calculate new reissued count
$new_reissued_total = $total_reissued + $reissue_qty;

// 🟢 Determine updated status
$status = ($new_reissued_total == $total_returned)
    ? 'Re-Issued'
    : 'Partially Re-Issued';

// 🟢 Update the transaction record
$sql = "
    UPDATE transactions
    SET 
        qty_re_issued = '{$new_reissued_total}',
        re_issue_date = '{$reissue_date}',
        remarks =  {$remarks}'),
        status = '{$status}'
    WHERE id = '{$transaction_id}'
";

if ($db->query($sql)) {
    echo json_encode([
        'success' => true,
        'message' => "Successfully re-issued {$reissue_qty} item(s)."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update transaction. Please try again.'
    ]);
}
?>
