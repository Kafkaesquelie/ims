<?php
require_once('includes/load.php');

// Get the admin-set middle part from settings or database
// For now, return a default value - you can modify this to fetch from your settings table
$middle_part = '0000'; // Default value

// You can modify this to fetch from your database:
// $setting = find_by_sql("SELECT value FROM settings WHERE name = 'ris_middle_part' LIMIT 1");
// $middle_part = $setting ? $setting[0]['value'] : '0000';

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'middlePart' => $middle_part
]);
?>