<?php
require_once('includes/load.php');
header('Content-Type: application/json');

// Default response
$response = [
    'exists' => false,
    'message' => ''
];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Retrieve POST data
$doc_type   = $_POST['doc_type'] ?? '';
$doc_number = trim($_POST['doc_number'] ?? '');

if (empty($doc_type) || empty($doc_number)) {
    $response['message'] = 'Missing document type or number.';
    echo json_encode($response);
    exit;
}

// Determine which column to check
$column = '';
if ($doc_type === 'ics') {
    $column = 'ICS_No';
} elseif ($doc_type === 'par') {
    $column = 'PAR_No';
} else {
    $response['message'] = 'Invalid document type.';
    echo json_encode($response);
    exit;
}

// Sanitize and query database
$escaped_doc_no = $db->escape($doc_number);
$query = "SELECT COUNT(*) AS count FROM transactions WHERE {$column} = '{$escaped_doc_no}' LIMIT 1";
$result = $db->query($query);
if ($result && $db->num_rows($result) > 0) {
    $row = $db->fetch_assoc($result);
    if ((int)$row['count'] > 0) {
        $response['exists'] = true;
        $response['message'] = 'Document number already exists.';
    } else {
        $response['exists'] = false;
        $response['message'] = 'Document number is available.';
    }
} else {
    $response['message'] = 'Query failed or returned no result.';
}

// Return JSON
echo json_encode($response);
exit;
?>
