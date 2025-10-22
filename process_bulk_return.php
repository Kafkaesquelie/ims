<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    require_once('includes/load.php');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $ics_no = $_POST['ics_no'] ?? '';
    $transaction_ids = $_POST['transaction_ids'] ?? [];
    $return_qtys = $_POST['return_qty'] ?? [];
    $conditions = $_POST['conditions'] ?? [];
    $return_date = $_POST['return_date'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    // Validate inputs
    if (empty($ics_no) || empty($return_date) || empty($transaction_ids)) {
        throw new Exception('Please fill all required fields.');
    }

    // Generate RRSP number: RRSP-YYYY-MM-XXXX (auto-incrementing)
    $current_year_month = date('Y-m');
    
    // Get the last RRSP number for this year-month
    $sql = "SELECT RRSP_No FROM return_items WHERE RRSP_No LIKE 'RRSP-{$current_year_month}-%' ORDER BY id DESC LIMIT 1";
    $last_rrsp = $db->query($sql);
    
    if ($last_rrsp && $db->num_rows($last_rrsp) > 0) {
        $last_rrsp_data = $db->fetch_assoc($last_rrsp);
        $last_rrsp_no = $last_rrsp_data['RRSP_No'];
        
        // Extract the number part and increment
        $parts = explode('-', $last_rrsp_no);
        $last_number = (int)end($parts);
        $next_number = $last_number + 1;
    } else {
        // First RRSP for this year-month
        $next_number = 1;
    }
    
    // Format: RRSP-YYYY-MM-XXXX (4-digit number with leading zeros)
    $rrsp_no = "RRSP-" . $current_year_month . "-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);

    $success_count = 0;
    $error_messages = [];

    foreach ($transaction_ids as $index => $transaction_id) {
        $return_qty = (int)($return_qtys[$index] ?? 0);
        $condition = $conditions[$index] ?? 'Functional';

        if ($return_qty <= 0) {
            continue;
        }

        // Get transaction details
        $transaction = find_by_id('transactions', (int)$transaction_id);
        if (!$transaction) {
            $error_messages[] = "Transaction ID {$transaction_id} not found.";
            continue;
        }

        // Check if the transaction has the expected structure
        if (!isset($transaction['item_description'])) {
            // Try alternative field names that might exist
            $item_name = $transaction['item'] ?? $transaction['item_name'] ?? 'Unknown Item';
        } else {
            $item_name = $transaction['item'];
        }

        // Check if return quantity is valid
        $remaining_qty = $transaction['quantity'] - ($transaction['qty_returned'] ?? 0);
        if ($return_qty > $remaining_qty) {
            $error_messages[] = "Return quantity for {$item_name} exceeds available quantity.";
            continue;
        }

        // Insert into return_items table using raw SQL
        $return_data = [
            'transaction_id' => $transaction_id,
            'RRSP_No' => $rrsp_no,  // Same RRSP number for all items in bulk return
            'ics_no' => $ics_no,
            'qty' => $return_qty,
            'return_date' => $return_date,
            'conditions' => $condition,
            'remarks' => $remarks
        ];

        // Use raw SQL insert
        $columns = implode(', ', array_keys($return_data));
        $values = "'" . implode("', '", array_values($return_data)) . "'";
        $sql = "INSERT INTO return_items ($columns) VALUES ($values)";
        
        if ($db->query($sql)) {
            // Update transaction with returned quantity using raw SQL
            $new_returned_qty = ($transaction['qty_returned'] ?? 0) + $return_qty;
            $new_status = ($new_returned_qty >= $transaction['quantity']) ? 'Returned' : 'Partially Returned';
            
            $update_sql = "UPDATE transactions SET 
                          qty_returned = $new_returned_qty, 
                          status = '$new_status' 
                          WHERE id = $transaction_id";
            
            if ($db->query($update_sql)) {
                $success_count++;
            } else {
                $error_messages[] = "Failed to update transaction for {$item_name}.";
            }
        } else {
            $error_messages[] = "Failed to process return for {$item_name}.";
        }
    }

    if ($success_count > 0) {
        $message = "Successfully processed {$success_count} item(s) with RRSP No: {$rrsp_no}.";
        if (!empty($error_messages)) {
            $message .= " Issues: " . implode(' ', $error_messages);
        }
        echo json_encode(['success' => true, 'message' => $message, 'rrsp_no' => $rrsp_no]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No items were processed. ' . implode(' ', $error_messages)]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>