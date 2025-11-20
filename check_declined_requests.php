<?php
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user();
$user_id = $current_user['id'];

// Get the last check timestamp from the request or use current time
$lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 minute'));

// Check for new declined requests since last check
$newDeclined = find_by_sql("SELECT r.*, COUNT(ri.id) as item_count 
                           FROM requests r 
                           LEFT JOIN request_items ri ON r.id = ri.req_id 
                           WHERE r.requested_by = '{$user_id}' 
                           AND r.status = 'Declined' 
                           AND r.date_completed > '{$lastCheck}'
                           GROUP BY r.id 
                           ORDER BY r.date_completed DESC");

header('Content-Type: application/json');
echo json_encode([
    'newDeclined' => $newDeclined,
    'lastCheck' => date('Y-m-d H:i:s')
]);
?>