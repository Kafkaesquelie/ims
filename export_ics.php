<?php
// export_to_template.php
require_once('includes/load.php');
page_require_level(1);

// Check if PhpWord is available
try {
    require 'vendor/autoload.php';
} catch (Exception $e) {
    die("Error loading PhpWord: " . $e->getMessage());
}

use PhpOffice\PhpWord\TemplateProcessor;

// Get ICS number from URL
$ics_no = isset($_GET['ics_no']) ? trim($db->escape($_GET['ics_no'])) : null;

if (!$ics_no) {
    die("Invalid ICS number.");
}

// Fetch ALL transactions with the same ICS number
$sql = "
    SELECT 
        t.ics_no,
        t.item_id,
        t.quantity,
        t.transaction_date,
        p.inv_item_no,
        p.item AS item_name,
        p.item_description AS description,
        p.unit_cost,
        p.unit,
        p.estimated_use,
        p.fund_cluster,
        CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
        e.position,
        e.office
    FROM transactions t
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.ICS_No = '{$ics_no}'
      AND t.transaction_type = 'issue'
    ORDER BY p.item ASC
";

$transactions = find_by_sql($sql);

if (empty($transactions)) {
    die("No transactions found for ICS: {$ics_no}");
}

$first_transaction = $transactions[0];
$current_user = current_user();

// Path to the ICS template file
$templatePath = __DIR__ . '/templates/ICS_Template.docx';

if (!file_exists($templatePath)) {
    die("Template not found at: $templatePath");
}

try {
    // Load the template file
    $template = new TemplateProcessor($templatePath);

    // Set the ICS No and Fund Cluster (common values)
    $template->setValue('ics_no', $ics_no);
    $template->setValue('fund_cluster', !empty($first_transaction['fund_cluster']) ? $first_transaction['fund_cluster'] : 'General Fund');
    
    // Add item details to the template
    $template->cloneRow('item_name', count($transactions));  // Clone rows for each item
    
    $total_cost_all = 0;
    foreach ($transactions as $index => $transaction) {
        $total_cost = ($transaction['unit_cost'] ?? 0) * ($transaction['quantity'] ?? 0);
        $total_cost_all += $total_cost;

        // Fill in each cloned row with the transaction data
        $template->setValue("item_name#".($index+1), $transaction['item_name'] ?? '');
        $template->setValue("quantity#".($index+1), $transaction['quantity'] ?? '');
        $template->setValue("unit#".($index+1), $transaction['unit'] ?? '');
        $template->setValue("unit_cost#".($index+1), '₱' . number_format($transaction['unit_cost'] ?? 0, 2));
        $template->setValue("total_cost#".($index+1), '₱' . number_format($total_cost, 2));
        $template->setValue("description#".($index+1), $transaction['description'] ?? '');
        $template->setValue("inv_item_no#".($index+1), $transaction['inv_item_no'] ?? '');
        $template->setValue("estimated_use#".($index+1), $transaction['estimated_use'] ?? '');
    }
    
    // Set Issuer Information
    $template->setValue('issuer_name', strtoupper($current_user['name'] ?? 'Supply Officer'));
    $template->setValue('issuer_position', $current_user['position'] ?? 'Supply and Property Officer');
    $template->setValue('date_issued', date('F d, Y', strtotime($first_transaction['transaction_date'] ?? 'now')));
    
    // ✅ ADDED: Set Receiver Information (Employee details)
    $template->setValue('employee_name', strtoupper($first_transaction['employee_name'] ?? ''));
    $template->setValue('position', $first_transaction['position'] ?? '');
    $template->setValue('receiver_office', $first_transaction['office'] ?? '');

    // Save the generated document to a temporary file
    $tmpDir = sys_get_temp_dir();
    $tmpFile = $tmpDir . '/ics_' . $ics_no . '_' . time() . '.docx';

    // Save the file
    $template->saveAs($tmpFile);

    // Check if the file was created successfully
    if (!file_exists($tmpFile)) {
        throw new Exception("Failed to create temporary file.");
    }

    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Send the file for download
    header("Content-Description: File Transfer");
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Disposition: attachment; filename=\"ICS_{$ics_no}.docx\"");
    header("Content-Length: " . filesize($tmpFile));
    header("Cache-Control: must-revalidate");
    header("Pragma: public");
    header("Expires: 0");

    // Output the file content
    readfile($tmpFile);

    // Delete the temporary file after sending it
    unlink($tmpFile);
    exit;

} catch (Exception $e) {
    // Clear any output before showing error
    if (ob_get_level()) {
        ob_end_clean();
    }
    die("Error generating document: " . $e->getMessage());
}