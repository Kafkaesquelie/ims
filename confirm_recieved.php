<?php
require_once('includes/load.php');
page_require_level(3);

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user = current_user();

    $req = find_by_id('requests', $id);
    if ($req && $req['requested_by'] == $user['id'] && strtolower($req['status']) == 'completed') {
        $sql = "UPDATE requests SET status = 'Received', date_received = NOW() WHERE id = '{$id}'";
        if ($db->query($sql)) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request or unauthorized.']);
exit;
?>
