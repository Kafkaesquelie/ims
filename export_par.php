<?php
// Start output buffering at the VERY beginning
ob_start();

require_once('includes/load.php');

// Check if PhpWord is available
try {
    require 'vendor/autoload.php';
} catch (Exception $e) {
    die("Error loading PhpWord: " . $e->getMessage());
}

use PhpOffice\PhpWord\TemplateProcessor;

// Get PAR ID from URL
$par_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($par_id <= 0) {
    die("Invalid PAR ID.");
}

// 🟩 Fetch PAR data with error handling - include current user data
$sql = "
    SELECT 
        t.par_no,
        t.quantity,
        t.transaction_date,
        p.property_no,
        p.article AS item_name,
        p.description,
        p.unit_cost,
        p.unit,
        p.fund_cluster,
        p.date_acquired,
        e.first_name,
        e.middle_name,
        e.last_name,
        e.position,
        e.office
    FROM transactions t
    LEFT JOIN properties p ON t.item_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.id = '{$par_id}'
    LIMIT 1
";

$result = find_by_sql($sql);

if (empty($result)) {
    die("PAR record not found.");
}

$par = $result[0];

// Get current user (issuer) information
$current_user = current_user();

$templatePath = __DIR__ . '/templates/PAR_Template.docx';

if (!file_exists($templatePath)) {
    die("Template not found at: $templatePath");
}

try {
    // 🟩 Load and fill template
    $template = new TemplateProcessor($templatePath);
    
    // Calculate total amount
    $total_amount = ($par['unit_cost'] ?? 0) * ($par['quantity'] ?? 0);
    
    // Set values with fallbacks
    $template->setValue('fund_cluster', $par['fund_cluster'] ?? '');
    $template->setValue('par_no', $par['par_no'] ?? '');
    
    // Employee (receiver) information
    $employee_name = strtoupper(trim(($par['first_name'] ?? '') . ' ' . ($par['middle_name'] ?? '') . ' ' . ($par['last_name'] ?? '')));
    $template->setValue('employee_name', $employee_name);
    $template->setValue('position', $par['position'] ?? '');
    $template->setValue('office', $par['office'] ?? '');
    
    // Item information
    $template->setValue('item_name', $par['item_name'] ?? '');
    $template->setValue('description', $par['description'] ?? '');
    $template->setValue('quantity', $par['quantity'] ?? '');
    $template->setValue('unit', $par['unit'] ?? '');
    $template->setValue('property_no', $par['property_no'] ?? '');
    $template->setValue('unit_cost', number_format($par['unit_cost'] ?? 0, 2));
    $template->setValue('total', number_format($total_amount, 2));
    $template->setValue('date_acquired', date('F d, Y', strtotime($par['date_acquired'] ?? 'now')));
    
    // 🟩 FIXED: Add the missing placeholders
    $template->setValue('issuer_name', strtoupper($current_user['name'] ?? 'Supply Officer'));
    $template->setValue('issuer_position', $current_user['position'] ?? 'Supply and/or Property Officer');
    $template->setValue('date_issued', date('F d, Y', strtotime($par['transaction_date'] ?? 'now')));
    
    // 🟩 FIXED AMOUNT PLACEHOLDER - Try different variations
    // Try these common placeholder names for amount:
    $template->setValue('amount', number_format($total_amount, 2));
    $template->setValue('Amount', number_format($total_amount, 2)); // with capital A
    $template->setValue('AMOUNT', number_format($total_amount, 2)); // all caps
    $template->setValue('total_amount', number_format($total_amount, 2));
    $template->setValue('Total', number_format($total_amount, 2)); // if it's "Total" instead of "Amount"
    
    // 🟩 Debug: List all placeholders found in template (optional - remove in production)
    $allPlaceholders = $template->getVariables();
    error_log("Found placeholders in template: " . implode(', ', $allPlaceholders));

    // 🟩 Save temporary file
    $tmpDir = sys_get_temp_dir();
    $tmpFile = $tmpDir . '/par_' . $par_id . '_' . time() . '.docx';
    
    $template->saveAs($tmpFile);

    // Check if file was created
    if (!file_exists($tmpFile)) {
        throw new Exception("Failed to create temporary file.");
    }

    // 🟩 Clear any previous output
    ob_clean();
    
    // 🟩 Send file for download
    header("Content-Description: File Transfer");
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Disposition: attachment; filename=\"PAR_{$par['par_no']}.docx\"");
    header("Content-Length: " . filesize($tmpFile));
    header("Cache-Control: must-revalidate");
    header("Pragma: public");
    header("Expires: 0");
    
    // Read and output file
    readfile($tmpFile);
    
    // Delete temporary file
    unlink($tmpFile);
    
    exit;
    
} catch (Exception $e) {
    // Clear output buffer and show error
    ob_clean();
    die("Error generating document: " . $e->getMessage());
}
?>