<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get POST data with validation
$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
$return_qty = isset($_POST['return_qty']) ? (int)$_POST['return_qty'] : 0;
$condition = isset($_POST['conditions']) ? $db->escape($_POST['conditions']) : '';
$return_date = isset($_POST['return_date']) ? $db->escape($_POST['return_date']) : date('Y-m-d');
$remarks = isset($_POST['remarks']) ? $db->escape($_POST['remarks']) : '';

// Validate required fields
if ($transaction_id <= 0 || $return_qty <= 0 || empty($condition)) {
    $response['message'] = 'Please fill in all required fields. Transaction ID: ' . $transaction_id . ', Qty: ' . $return_qty . ', Condition: ' . $condition;
    echo json_encode($response);
    exit;
}

// Fetch original transaction
$transaction = find_by_id('transactions', $transaction_id);
if (!$transaction) {
    $response['message'] = 'Transaction not found with ID: ' . $transaction_id;
    echo json_encode($response);
    exit;
}

// Validate quantity
$issued_qty = (int)$transaction['quantity'];
$current_qty_returned = (int)$transaction['qty_returned'];
$new_qty_returned = $current_qty_returned + $return_qty;

if ($new_qty_returned > $issued_qty) {
    $response['message'] = 'Return quantity exceeds issued quantity. Maximum you can return: ' . ($issued_qty - $current_qty_returned);
    echo json_encode($response);
    exit;
}

// Determine the new status
if ($condition === 'Damaged') {
   if ($new_qty_returned >= $issued_qty) {
        $new_status = 'Returned';
    } else {
        $new_status = 'Partially Returned';
    }
} else {
    if ($new_qty_returned >= $issued_qty) {
        $new_status = 'Returned';
    } else {
        $new_status = 'Partially Returned';
    }
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
    // 1. Insert into return_items table
    $return_sql = "INSERT INTO return_items (transaction_id, RRSP_No, ics_no, qty, return_date, conditions, remarks) 
                   VALUES ('{$transaction_id}', '{$rrsp_no}', '{$db->escape($transaction['ICS_No'])}', 
                           '{$return_qty}', '{$return_date}', '{$condition}', '{$remarks}')";
    
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

    // 3. Update stock quantity
    $item_id = $transaction['item_id'];
    $ics_no = $transaction['ICS_No'];
    
    if (!empty($ics_no)) {
        // It's a semi-expendable property
        $item = find_by_id('semi_exp_prop', $item_id);
        if ($item) {
            $new_qty = $item['qty_left'] + $return_qty;
            $update_stock_sql = "UPDATE semi_exp_prop SET qty_left = '{$new_qty}' WHERE id = '{$item_id}'";
            if (!$db->query($update_stock_sql)) {
                throw new Exception('Failed to update semi-expendable stock quantity: ' . $db->get_last_error());
            }
        } else {
            throw new Exception('Semi-expendable item not found with ID: ' . $item_id);
        }
    } else {
        // It's a regular property
        $item = find_by_id('properties', $item_id);
        if ($item) {
            $new_qty = $item['qty'] + $return_qty;
            $update_stock_sql = "UPDATE properties SET qty = '{$new_qty}' WHERE id = '{$item_id}'";
            if (!$db->query($update_stock_sql)) {
                throw new Exception('Failed to update property stock quantity: ' . $db->get_last_error());
            }
        } else {
            throw new Exception('Property item not found with ID: ' . $item_id);
        }
    }

    // Commit transaction
    $db->query("COMMIT");
    
    $response['success'] = true;
    $response['message'] = "Item successfully returned. Status: {$new_status}. RRSP No: {$rrsp_no}";
    $response['return_id'] = $return_id;
    $response['rrsp_no'] = $rrsp_no;
    
} catch (Exception $e) {
    $db->query("ROLLBACK");
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Return Item Error: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>