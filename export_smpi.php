<?php
require_once 'vendor/autoload.php';  // Ensure PHPWord is included

function export_to_word($smpi_item) {
    global $db;

    // Fetch the details of the item for the ICS
    $item_id = (int)$smpi_item['id'];
    $sql = "
        SELECT 
            s.id,
            s.item,
            s.item_description,
            s.inv_item_no,
            s.unit_cost,
            s.qty_left AS balance_qty,
            s.fund_cluster
        FROM semi_exp_prop s
        WHERE s.id = '{$db->escape($item_id)}'
        LIMIT 1
    ";
    $item = find_by_sql($sql);
    if (empty($item)) {
        return false; // Item not found
    }
    $item = $item[0]; // Get the first result

    // Fetch the transactions linked to this item
    $transactions_sql = "
        SELECT 
            t.transaction_date,
            t.PAR_No,
            t.ICS_No,
            t.RRSP_No,
            t.quantity AS issued_qty,
            t.transaction_type,
            CONCAT(e.first_name, ' ', e.last_name) AS officer,
            s.unit_cost AS item_unit_cost,
            s.qty_left AS current_balance
        FROM transactions t
        LEFT JOIN semi_exp_prop s ON t.item_id = s.id
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.item_id = '{$db->escape($item_id)}'
        ORDER BY t.transaction_date ASC
    ";
    $transactions = find_by_sql($transactions_sql);

    // Load PHPWord library
    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    // Load the template document
    $template = $phpWord->loadTemplate('Property_Template.docx');

    // Replace placeholders with actual data
    $template->setValue('entity_name', 'Benguet State University - BOKOD CAMPUS');
    $template->setValue('fund_cluster', $item['fund_cluster']);
    $template->setValue('item_name', strtoupper($item['item']));
    $template->setValue('item_description', $item['item_description']);
    $template->setValue('quantity', $item['balance_qty']);
    $template->setValue('unit', $item['unit']); // Assuming `unit` is part of the data
    $template->setValue('unit_cost', number_format($item['unit_cost'], 2));
    $template->setValue('total_cost', number_format($item['unit_cost'] * $item['balance_qty'], 2));
    $template->setValue('employee_name', $transactions[0]['officer'] ?? 'N/A');
    $template->setValue('transaction_date', date('M d, Y', strtotime($transactions[0]['transaction_date'])));

    // If there are multiple transactions, append them in the table dynamically
    $transactionRows = '';
    foreach ($transactions as $transaction) {
        $transactionRows .= "
            <w:tr>
                <w:tc><w:p><w:r><w:t>{$transaction['transaction_date']}</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>{$transaction['ICS_No']}</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>{$transaction['issued_qty']}</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>" . number_format($transaction['item_unit_cost'], 2) . "</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>" . number_format($transaction['issued_qty'] * $transaction['item_unit_cost'], 2) . "</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>{$transaction['officer']}</w:t></w:r></w:p></w:tc>
            </w:tr>
        ";
    }

    // Add the transaction rows to the table in the template
    $template->setValue('transaction_rows', $transactionRows);

    // Save the file to a temporary location
    $file_path = 'temp_smpi_' . $item['inv_item_no'] . '.docx';
    $template->saveAs($file_path);

    // Force download of the generated file
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="SMPI_' . $item['inv_item_no'] . '.docx"');
    readfile($file_path);

    // Delete the temporary file
    unlink($file_path);
}

// Example usage
export_to_word($smpi_items[0]); // Pass the first SMPI item for export
?>
