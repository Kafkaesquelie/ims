<?php
require_once('includes/load.php');

if (isset($_GET['id'])) {
    $archive_id = (int)$_GET['id'];

    if (restore_from_archive($archive_id)) {
        $session->msg("s", "Record restored successfully!");
    } else {
        $session->msg("d", "Failed to restore record.");
    }
    redirect($_SERVER['HTTP_REFERER'], false);
}
?>
