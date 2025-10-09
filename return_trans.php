<?php
require_once('includes/load.php');
page_require_level(1);

$id = (int)$_GET['id'];
if(!$id) {
    $session->msg("d","Invalid transaction ID.");
    redirect('logs.php');
}

// Fetch the original transaction
$transaction = find_by_id('transactions', $id);
if(!$transaction) {
    $session->msg("d","Transaction not found.");
    redirect('transactions.php');
}

$item_id    = $transaction['item_id'];
$request_id = $transaction['request_id'];
$user_id    = $transaction['user_id'];
$qty        = $transaction['quantity'];

// ✅ Generate new RRSP No (format: RRSP-YYYY-uniqueid)
$rrsp_no = "RRSP-" . date("Y") . "-" . uniqid();

// ✅ Insert new RETURN transaction
$query  = "INSERT INTO transactions (employee_id, user_id, request_id, item_id, quantity, PAR_No, ICS_No, RRSP_No, transaction_type, transaction_date,remarks) 
           VALUES (NULL, '{$user_id}', '{$request_id}', '{$item_id}', '{$qty}', 0, 0, '{$rrsp_no}', 'return', NOW(), 'Returned from request ID: {$request_id}')";

$insert = $db->query($query);

// ✅ Update semi_exp_prop (mark as returned)
$update = $db->query("UPDATE semi_exp_prop 
                      SET status='returned' 
                      WHERE request_id='{$request_id}' 
                      AND item_id='{$item_id}'");

if($insert && $update){
    $session->msg("s","Return processed. RRSP No: {$rrsp_no}");
} else {
    $session->msg("d","Failed to process return.");
}

redirect('logs.php');
?>
