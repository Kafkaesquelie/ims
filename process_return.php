<?php
require_once('includes/load.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = (int)$_POST['transaction_id'];
    $return_status = remove_junk($db->escape($_POST['return_status']));
    $remarks = remove_junk($db->escape($_POST['remarks']));

    if (!$transaction_id || !$return_status) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $statusToSet = $return_status == 'Damaged' ? 'Damaged' : 'Returned';

    $query = "
        UPDATE transactions 
        SET status = '{$statusToSet}', 
            return_date = NOW(), 
            remarks = '{$remarks}' 
        WHERE id = '{$transaction_id}'
    ";

    if ($db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'The item has been marked as returned.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
}
?>
