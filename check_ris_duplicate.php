<!-- <?php
require_once('includes/load.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ris_no = $_POST['ris_no'] ?? '';
    
    if (empty($ris_no)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    global $db;
    $ris_no = $db->escape($ris_no);
    $result = $db->query("SELECT id FROM requests WHERE ris_no = '{$ris_no}' LIMIT 1");
    $exists = $db->num_rows($result) > 0;
    
    echo json_encode(['exists' => $exists]);
    exit;
}

echo json_encode(['exists' => false]);
?> -->


<?php
require_once('includes/load.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ris_no = $_POST['ris_no'] ?? '';
    
    // Check if RIS number already exists
    $result = find_by_sql("SELECT id FROM requests WHERE ris_no = '{$db->escape($ris_no)}' LIMIT 1");
    $exists = !empty($result);
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
    exit;
}
?>