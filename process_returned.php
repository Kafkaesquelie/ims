<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'alert_type' => 'error'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    $response['alert_type'] = 'error';
    echo json_encode($response);
    exit;
}

// Get POST data with validation
$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
$return_qty = isset($_POST['return_qty']) ? (int)$_POST['return_qty'] : 0;
$condition = isset($_POST['conditions']) ? $db->escape($_POST['conditions']) : '';
$return_date = isset($_POST['return_date']) ? $db->escape($_POST['return_date']) : date('Y-m-d');
$remarks = isset($_POST['remarks']) ? $db->escape($_POST['remarks']) : '';
$doc_type = isset($_POST['doc_type']) ? $db->escape($_POST['doc_type']) : '';
$doc_no = isset($_POST['doc_no']) ? $db->escape($_POST['doc_no']) : '';

// Validate required fields
if ($transaction_id <= 0 || $return_qty <= 0 || empty($condition)) {
    $response['message'] = 'Please fill in all required fields.';
    $response['alert_type'] = 'warning';
    echo json_encode($response);
    exit;
}

// Fetch original transaction with detailed information
$transaction_sql = "SELECT t.*, 
                   t.item_id, 
                   t.properties_id,
                   t.ICS_No,
                   t.PAR_No
                   FROM transactions t 
                   WHERE t.id = '{$transaction_id}'";
$transaction_result = $db->query($transaction_sql);

if (!$transaction_result) {
    $response['message'] = 'Database error: ' . $db->get_last_error();
    $response['alert_type'] = 'error';
    echo json_encode($response);
    exit;
}

$transaction = $transaction_result->fetch_assoc();

if (!$transaction) {
    $response['message'] = 'Transaction not found with ID: ' . $transaction_id;
    $response['alert_type'] = 'error';
    echo json_encode($response);
    exit;
}

// Determine item type based on which ID is present
if (!empty($transaction['item_id']) && $transaction['item_id'] > 0) {
    $item_type = 'ics';
    $item_identifier = $transaction['item_id'];
} elseif (!empty($transaction['properties_id']) && $transaction['properties_id'] > 0) {
    $item_type = 'par';
    $item_identifier = $transaction['properties_id'];
} else {
    $response['message'] = 'Cannot determine item type. Both item_id and properties_id are empty or invalid.';
    $response['alert_type'] = 'error';
    echo json_encode($response);
    exit;
}

// Validate quantity
$issued_qty = (int)$transaction['quantity'];
$current_qty_returned = (int)$transaction['qty_returned'];
$new_qty_returned = $current_qty_returned + $return_qty;

if ($new_qty_returned > $issued_qty) {
    $response['message'] = 'Return quantity exceeds issued quantity. Maximum you can return: ' . ($issued_qty - $current_qty_returned);
    $response['alert_type'] = 'warning';
    echo json_encode($response);
    exit;
}

// Determine the new status
if ($new_qty_returned >= $issued_qty) {
    $new_status = 'Returned';
} else {
    $new_status = 'Partially Returned';
}

// Generate RRSP number with format YYYY-MM-XXXX
$currentYear = date('Y');
$currentMonth = date('m');
$yearMonth = $currentYear . '-' . $currentMonth;

// Get the count of return items for this month
$count_sql = "SELECT COUNT(*) AS rrsp_count FROM return_items 
              WHERE YEAR(return_date) = '{$currentYear}' 
              AND MONTH(return_date) = '{$currentMonth}'";
$result = $db->query($count_sql);

if (!$result) {
    $response['message'] = 'Error counting RRSP records: ' . $db->get_last_error();
    $response['alert_type'] = 'error';
    echo json_encode($response);
    exit;
}

$count_data = $result->fetch_assoc();
$newCount = (int)$count_data['rrsp_count'] + 1;

// Format RRSP number as YYYY-MM-XXXX (4-digit sequential number)
$rrsp_no = sprintf('%s-%04d', $yearMonth, $newCount);

// Start transaction
$db->query("START TRANSACTION");

try {
    // 1. Insert into return_items table with proper document numbers
    if ($item_type === 'ics') {
        // ICS item - use ICS_No and set PAR_No to NULL
        $ics_no = !empty($transaction['ICS_No']) ? $transaction['ICS_No'] : '';
        $return_sql = "INSERT INTO return_items (transaction_id, RRSP_No, ics_no, par_no, qty, return_date, conditions, remarks) 
                       VALUES ('{$transaction_id}', '{$rrsp_no}', '{$db->escape($ics_no)}', NULL, 
                               '{$return_qty}', '{$return_date}', '{$condition}', '{$remarks}')";
    } else {
        // PAR item - use PAR_No and set ics_no to NULL
        $par_no = !empty($transaction['PAR_No']) ? $transaction['PAR_No'] : '';
        $return_sql = "INSERT INTO return_items (transaction_id, RRSP_No, ics_no, par_no, qty, return_date, conditions, remarks) 
                       VALUES ('{$transaction_id}', '{$rrsp_no}', NULL, '{$db->escape($par_no)}', 
                               '{$return_qty}', '{$return_date}', '{$condition}', '{$remarks}')";
    }
    
    if (!$db->query($return_sql)) {
        throw new Exception('Failed to create return record: ' . $db->get_last_error());
    }
    
    $return_id = $db->insert_id();
    
    // 2. Update transactions table
    $update_sql = "UPDATE transactions 
                   SET qty_returned = '{$new_qty_returned}',
                       status = '{$db->escape($new_status)}'
                   WHERE id = '{$transaction_id}'";

    if (!$db->query($update_sql)) {
        throw new Exception('Failed to update transaction: ' . $db->get_last_error());
    }

    // 3. Update stock quantity based on item type
    if ($item_type === 'ics') {
        // ICS item - update semi_exp_prop table
        $item_id = (int)$transaction['item_id'];
        $item = find_by_id('semi_exp_prop', $item_id);
        
        if ($item) {
            $current_stock = (int)$item['qty_left'];
            $new_qty = $current_stock + $return_qty;
            
            $update_stock_sql = "UPDATE semi_exp_prop SET qty_left = '{$new_qty}' WHERE id = '{$item_id}'";
            if (!$db->query($update_stock_sql)) {
                throw new Exception('Failed to update semi-expendable stock quantity: ' . $db->get_last_error());
            }
        } else {
            throw new Exception('Semi-expendable item not found with ID: ' . $item_id);
        }
    } elseif ($item_type === 'par') {
        // PAR item - update properties table
        $properties_id = (int)$transaction['properties_id'];
        $item = find_by_id('properties', $properties_id);
        
        if ($item) {
            $current_stock = (int)$item['qty'];
            $new_qty = $current_stock + $return_qty;
            
            $update_stock_sql = "UPDATE properties SET qty = '{$new_qty}' WHERE id = '{$properties_id}'";
            if (!$db->query($update_stock_sql)) {
                throw new Exception('Failed to update property stock quantity: ' . $db->get_last_error());
            }
        } else {
            throw new Exception('Property item not found with ID: ' . $properties_id);
        }
    }

    // Commit transaction
    $db->query("COMMIT");
    
    $response['success'] = true;
    $response['message'] = "Item successfully returned. Status: {$new_status}. RRSP No: {$rrsp_no}";
    $response['alert_type'] = 'success';
    $response['return_id'] = $return_id;
    $response['rrsp_no'] = $rrsp_no;
    $response['item_type'] = $item_type;
    
} catch (Exception $e) {
    $db->query("ROLLBACK");
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['alert_type'] = 'error';
    error_log("Return Item Error: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>