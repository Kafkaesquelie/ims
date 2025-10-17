<?php
require_once('includes/load.php');
page_require_level(1);

if (isset($_GET['id'])) {
    // Sanitize the request ID
    $request_id = (int)$_GET['id'];

    // Update the request status to 'Approved' and fill date_approved with the current timestamp
    $sql = "UPDATE requests 
            SET status = 'Approved', date_completed = NOW() 
            WHERE id = '{$request_id}'";

    if ($db->query($sql)) {
        $session->msg("s", "✅ Request approved successfully!");
    } else {
        $session->msg("d", "❌ Failed to approve the request: " . $db->error);
    }

    // Redirect back to requests page
    redirect('requests.php', false);
} else {
    $session->msg("d", "⚠️ Can't submit request.");
    redirect('requests.php', false);
}
