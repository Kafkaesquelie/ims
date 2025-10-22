<?php
require_once('includes/load.php');
page_require_level(1);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$transaction_id = (int)($_POST['transaction_id'] ?? 0);

// If this is just a check for functional quantity (no reissue_qty provided)
if (!isset($_POST['reissue_qty'])) {
    if (!$transaction_id) {
        echo json_encode(['success' => false, 'message' => 'Transaction ID required.']);
        exit;
    }

    try {
        // Get total functional quantity from return_items
        $functional_sql = "
            SELECT SUM(qty) as total_functional 
            FROM return_items 
            WHERE transaction_id = '{$transaction_id}' 
            AND conditions = 'Functional'
        ";
        
        $result = $db->query($functional_sql);
        $data = $result->fetch_assoc();
        $total_functional = (int)($data['total_functional'] ?? 0);
        
        echo json_encode([
            'success' => true,
            'functional_qty' => $total_functional,
            'message' => 'Functional quantity retrieved successfully.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// If this is an actual re-issue request
$reissue_qty    = (int)($_POST['reissue_qty'] ?? 0);
$reissue_date   = $db->escape($_POST['reissue_date'] ?? date('Y-m-d'));
$remarks        = $db->escape($_POST['remarks'] ?? '');

if (!$transaction_id || !$reissue_qty) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// 🟢 Fetch transaction
$transaction = find_by_id('transactions', $transaction_id);
if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
    exit;
}

$total_returned = (int)$transaction['qty_returned'];
$total_reissued = (int)$transaction['qty_re_issued'];

// 🟢 Check if there are enough functional items to re-issue
$functional_check_sql = "
    SELECT SUM(qty) as total_functional 
    FROM return_items 
    WHERE transaction_id = '{$transaction_id}' 
    AND conditions = 'Functional'
";
$functional_result = $db->query($functional_check_sql);
$functional_data = $functional_result->fetch_assoc();
$total_functional = (int)$functional_data['total_functional'];

// Adjust for already re-issued functional items
$available_functional = $total_functional - $total_reissued;

if ($available_functional <= 0) {
    echo json_encode(['success' => false, 'message' => 'No functional items available to re-issue.']);
    exit;
}

if ($reissue_qty > $available_functional) {
    echo json_encode(['success' => false, 'message' => "Only {$available_functional} functional item(s) available for reissue."]);
    exit;
}

// 🟢 Calculate new reissued total and status
$new_reissued_total = $total_reissued + $reissue_qty;
$status = ($new_reissued_total == $total_returned)
    ? 'Re-Issued'
    : 'Partially Re-Issued';

$db->query("START TRANSACTION");

try {
    // ✅ Update the transaction - including transaction_type
    $update_sql = "
        UPDATE transactions
        SET 
            qty_re_issued = '{$new_reissued_total}',
            re_issue_date = '{$reissue_date}',
            remarks = CONCAT(IFNULL(remarks, ''), ' Re-issued {$reissue_qty} on {$reissue_date}: {$remarks}. '),
            status = '{$status}',
            transaction_type = 're-issue'
        WHERE id = '{$transaction_id}'
    ";
    if (!$db->query($update_sql)) {
        throw new Exception('Failed to update transaction.');
    }

    // ✅ Deduct reissued items from stock again
    $item_table = !empty($transaction['ICS_No']) ? 'semi_exp_prop' : 'properties';
    $item = find_by_id($item_table, $transaction['item_id']);
    if ($item) {
        $qty_field = !empty($transaction['ICS_No']) ? 'qty_left' : 'qty';
        $new_qty = $item[$qty_field] - $reissue_qty;
        
        $update_stock_sql = "UPDATE {$item_table} SET {$qty_field} = '{$new_qty}' WHERE id = '{$item['id']}'";
        if (!$db->query($update_stock_sql)) {
            throw new Exception('Failed to update stock quantity.');
        }
    }

    $db->query("COMMIT");

    echo json_encode([
        'success' => true,
        'message' => "Successfully re-issued {$reissue_qty} functional item(s).",
        'status' => $status
    ]);
} catch (Exception $e) {
    $db->query("ROLLBACK");
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>