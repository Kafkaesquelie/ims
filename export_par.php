<?php
// export_par.php
require_once('includes/load.php');
page_require_level(1);

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}

try {
    require 'vendor/autoload.php';
} catch (Exception $e) {
    die("Error loading PhpWord: " . $e->getMessage());
}

use PhpOffice\PhpWord\TemplateProcessor;

// Get the PAR number from URL
$par_no = isset($_GET['par_no']) ? trim($db->escape($_GET['par_no'])) : null;

if (!$par_no) {
    die("Invalid PAR number.");
}

// 🟩 FIXED: Use the CORRECT SQL query with proper table joins
$sql = "
    SELECT 
        t.id,
        t.par_no,
        t.item_id,
        p.property_no,
        p.article AS item_name,
        p.description,
        p.unit_cost,
        p.date_acquired,
        p.unit,
        p.fund_cluster,
        t.quantity,
        t.transaction_date,
        t.status,
        t.remarks,
        CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
        e.position,
        e.office,
        e.image
    FROM transactions t
    LEFT JOIN properties p ON t.properties_id = p.id  -- 🟩 FIXED: Changed from item_id to properties_id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.par_no = '{$par_no}'
    ORDER BY p.article ASC
";

$transactions = find_by_sql($sql);

if (empty($transactions)) {
    die("No PAR record found for PAR No: {$par_no}");
}

$first_transaction = $transactions[0];
$current_user = current_user();

// Path to the template
$templatePath = __DIR__ . '/templates/PAR_Template.docx';

if (!file_exists($templatePath)) {
    die("Template not found at: $templatePath");
}

try {
    // Load the template file
    $template = new TemplateProcessor($templatePath);

    // Set the general PAR info
    $template->setValue('par_no', $par_no);
    $template->setValue('fund_cluster', !empty($first_transaction['fund_cluster']) ? $first_transaction['fund_cluster'] : 'General Fund');
    
    // 🟩 FIXED: Set Receiver (Employee) Info with proper field names
    $template->setValue('employee_name', strtoupper($first_transaction['employee_name'] ?? ''));
    $template->setValue('employee_position', $first_transaction['position'] ?? ''); // 🟩 FIXED: Changed from employee_position to position
    $template->setValue('employee_office', $first_transaction['office'] ?? ''); // 🟩 FIXED: Changed from employee_office to office
    
    // Clone rows for each item in the transaction
    $template->cloneRow('item_name', count($transactions));

    // Add item details to each cloned row
    foreach ($transactions as $index => $transaction) {
        $total_cost = ($transaction['unit_cost'] ?? 0) * ($transaction['quantity'] ?? 0);

        $template->setValue("item_name#".($index+1), $transaction['item_name'] ?? '');
        $template->setValue("quantity#".($index+1), $transaction['quantity'] ?? '');
        $template->setValue("unit#".($index+1), $transaction['unit'] ?? '');
        $template->setValue("unit_cost#".($index+1), '₱' . number_format($transaction['unit_cost'] ?? 0, 2));
        $template->setValue("total_cost#".($index+1), '₱' . number_format($total_cost, 2));
        $template->setValue("description#".($index+1), $transaction['description'] ?? '');
        $template->setValue("property_no#".($index+1), $transaction['property_no'] ?? '');
        $template->setValue("date_acquired#".($index+1), !empty($transaction['date_acquired']) ? date('M d, Y', strtotime($transaction['date_acquired'])) : 'N/A');
    }

    // 🟩 FIXED: Set Issuer Information with proper position
    $template->setValue('issuer_name', strtoupper($current_user['name'] ?? 'Supply Officer'));
    $template->setValue('issuer_position', $current_user['position'] ?? 'Supply and Property Officer');
    $template->setValue('date_issued', date('F d, Y', strtotime($first_transaction['transaction_date'] ?? 'now')));

    // Save the generated document to a temporary file
    $tmpDir = sys_get_temp_dir();
    $tmpFile = $tmpDir . '/par_' . $par_no . '_' . time() . '.docx';

    $template->saveAs($tmpFile);

    // Check if the file was created successfully
    if (!file_exists($tmpFile)) {
        throw new Exception("Failed to create temporary file.");
    }

    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send the file for download
    header("Content-Description: File Transfer");
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Disposition: attachment; filename=\"PAR_{$par_no}.docx\"");
    header("Content-Length: " . filesize($tmpFile));
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Output the file content
    readfile($tmpFile);

    // Delete the temporary file after sending it
    unlink($tmpFile);
    exit;

} catch (Exception $e) {
    // Clear output before showing error
    while (ob_get_level()) {
        ob_end_clean();
    }
    die("Error generating document: " . $e->getMessage());
}
?>