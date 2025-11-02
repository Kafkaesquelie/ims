<?php
require_once 'includes/load.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Increase memory and execution time for large files
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Start output buffering at the very beginning
ob_start();

// Get the transaction_id or ICS number from the query
$transaction_id = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : null;
$ics_no = isset($_GET['ics_no']) ? trim($db->escape($_GET['ics_no'])) : null;

// Fetch return item(s) data
if ($transaction_id) {
    $sql = "
        SELECT 
            t.*,
            ri.id as return_item_id,
            ri.return_date,
            ri.qty as returned_qty,
            ri.conditions,
            ri.remarks,
            p.item,
            p.item_description,
            p.unit,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name
        FROM return_items ri
        JOIN transactions t ON ri.transaction_id = t.id
        JOIN semi_exp_prop p ON t.item_id = p.id
        JOIN employees e ON t.employee_id = e.id
        JOIN offices o ON e.office = o.id
        WHERE ri.transaction_id = {$transaction_id}
        ORDER BY ri.return_date DESC 
        LIMIT 1
    ";
    $return_items = find_by_sql($sql);
    
    if (!$return_items) {
        // Clean buffer before redirect
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $session->msg("d", "No return record found for transaction ID: {$transaction_id}");
        redirect('logs.php');
    }
    $return_item = $return_items[0];
    $ics_no = $return_item['ICS_No'];
    $rrsp_number = date('Y-m') . '-' . sprintf('%04d', $return_item['return_item_id']);
    
} elseif ($ics_no) {
    $sql = "
        SELECT 
            t.*,
            ri.id as return_item_id,
            ri.return_date,
            ri.qty as returned_qty,
            ri.conditions,
            ri.remarks,
            p.item,
            p.item_description,
            p.unit,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name
        FROM return_items ri
        JOIN transactions t ON ri.transaction_id = t.id
        JOIN semi_exp_prop p ON t.item_id = p.id
        JOIN employees e ON t.employee_id = e.id
        JOIN offices o ON e.office = o.id
        WHERE t.ICS_No = '{$ics_no}'
        ORDER BY ri.return_date DESC, p.item ASC
    ";
    $return_items = find_by_sql($sql);
    
    if (!$return_items) {
        // Clean buffer before redirect
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $session->msg("d", "No returned items found for ICS: {$ics_no}");
        redirect('logs.php');
    }
    $rrsp_number = date('Y-m') . '-' . sprintf('%04d', rand(1000, 9999));
} else {
    // Clean buffer before redirect
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    $session->msg("d", "No transaction or ICS number provided.");
    redirect('logs.php');
}

// Get current user for received by information
$current_user = current_user();
$current_date = date('m/d/Y');

try {
    // Check if template exists
    $templatePath = 'templates/RRSP_Template.xlsx';
    if (!file_exists($templatePath)) {
        throw new Exception("Template file not found: " . $templatePath);
    }

    // Load the template
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // === FIXED: Check if cells already have labels before overwriting ===
    
    // Get current values to see if labels exist
    $currentE5 = $sheet->getCell('E5')->getCalculatedValue();
    $currentE6 = $sheet->getCell('E6')->getCalculatedValue();
    
    // Fill in the header information - ONLY if cells don't have proper labels
    if (strpos($currentE5, 'RRSP') === false) {
        // If E5 doesn't contain "RRSP", add the full text with label
        $sheet->setCellValue('E5', 'RRSP No: ' . $rrsp_number);
    } else {
        // If E5 already has "RRSP" label, just add the number
        $sheet->setCellValue('E5', $currentE5 . $rrsp_number);
    }
    
    if (strpos($currentE6, 'Date') === false) {
        // If E6 doesn't contain "Date", add the full text with label
        $sheet->setCellValue('E6', 'Date: ' . $current_date);
    } else {
        // If E6 already has "Date" label, just add the date
        $sheet->setCellValue('E6', $currentE6 . $current_date);
    }

    // Fill in the items
    $row = 9;
    foreach ($return_items as $item) {
        $sheet->setCellValue('A' . $row, $item['item']);
        $sheet->setCellValue('B' . $row, $item['returned_qty']);
        $sheet->setCellValue('C' . $row, $item['ICS_No']);
        $sheet->setCellValue('D' . $row, $item['employee_name']);
        $sheet->setCellValue('E' . $row, $item['conditions']);
        $row++;
    }

    // === FIXED: Better signatory handling with null checks ===
    
    // Check if template already has signatory placeholders
    $currentB39 = $sheet->getCell('B39')->getCalculatedValue();
    $currentE39 = $sheet->getCell('E39')->getCalculatedValue();
    $currentB41 = $sheet->getCell('B41')->getCalculatedValue();
    $currentE41 = $sheet->getCell('E41')->getCalculatedValue();
    
    // Fill signatories - preserve any existing labels with null-safe checks
    if (empty($currentB39) || (is_string($currentB39) && strpos($currentB39, '[END_USER]') !== false)) {
        // If B39 is empty or has placeholder, set employee name
        $sheet->setCellValue('B39', $return_items[0]['employee_name']);
    } else {
        // If B39 has content, append employee name
        $sheet->setCellValue('B39', $currentB39 . "\n" . $return_items[0]['employee_name']);
    }
    
    if (empty($currentE39) || (is_string($currentE39) && strpos($currentE39, '[HEAD]') !== false)) {
        // If E39 is empty or has placeholder, set current user name
        $sheet->setCellValue('E39', $current_user['name']);
    } else {
        // If E39 has content, append current user name
        $sheet->setCellValue('E39', $currentE39 . "\n" . $current_user['name']);
    }

    // === ADD CURRENT DATE TO B41 AND E41 with null-safe checks ===
    
    // Fill date in B41 - preserve any existing labels
    if (empty($currentB41) || (is_string($currentB41) && strpos($currentB41, '[DATE]') !== false)) {
        // If B41 is empty or has placeholder, set current date
        $sheet->setCellValue('B41', $current_date);
    } else {
        // If B41 has content, append current date
        $sheet->setCellValue('B41', $currentB41 . "\n" . $current_date);
    }
    
    // Fill date in E41 - preserve any existing labels
    if (empty($currentE41) || (is_string($currentE41) && strpos($currentE41, '[DATE]') !== false)) {
        // If E41 is empty or has placeholder, set current date
        $sheet->setCellValue('E41', $current_date);
    } else {
        // If E41 has content, append current date
        $sheet->setCellValue('E41', $currentE41 . "\n" . $current_date);
    }

    // Clear output buffer completely before headers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Set appropriate headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="RRSP_' . $rrsp_number . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Save to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Excel Export Error: " . $e->getMessage());
    
    // Clear output buffer completely
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Fallback to creating a new spreadsheet from scratch
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set basic headers WITH LABELS
        $sheet->setCellValue('A1', 'RECEIPT OF RETURNED SEMI-EXPENDABLE PROPERTY (RRSP)');
        $sheet->setCellValue('A2', 'Entity Name: BENGUET STATE UNIVERSITY - BOKOD CAMPUS');
        $sheet->setCellValue('E5', 'RRSP No: ' . $rrsp_number); // WITH LABEL
        $sheet->setCellValue('E6', 'Date: ' . $current_date);   // WITH LABEL
        
        // Set table headers
        $sheet->setCellValue('A8', 'ITEM DESCRIPTION');
        $sheet->setCellValue('B8', 'QTY');
        $sheet->setCellValue('C8', 'ICS NO.');
        $sheet->setCellValue('D8', 'END-USER');
        $sheet->setCellValue('E8', 'REMARKS');
        
        // Fill data
        $row = 9;
        foreach ($return_items as $item) {
            $sheet->setCellValue('A' . $row, $item['item']);
            $sheet->setCellValue('B' . $row, $item['returned_qty']);
            $sheet->setCellValue('C' . $row, $item['ICS_No']);
            $sheet->setCellValue('D' . $row, $item['employee_name']);
            $sheet->setCellValue('E' . $row, $item['conditions']);
            $row++;
        }
        
        // === ADD SIGNATORIES WITH LABELS FOR FALLBACK ===
        $sheet->setCellValue('A38', 'SIGNATORIES:');
        $sheet->setCellValue('A39', 'End User:');
        $sheet->setCellValue('B39', $return_items[0]['employee_name']); // Employee Name
        $sheet->setCellValue('D39', 'Head/Receiver:');
        $sheet->setCellValue('E39', $current_user['name']); // Current User
        
        // === ADD CURRENT DATE TO B41 AND E41 FOR FALLBACK ===
        $sheet->setCellValue('A40', 'Date Signed:');
        $sheet->setCellValue('A41', 'End User Date:');
        $sheet->setCellValue('B41', $current_date); // Current date for B41
        $sheet->setCellValue('D41', 'Head Date:');
        $sheet->setCellValue('E41', $current_date); // Current date for E41
 
        
        // Auto-size columns for better readability
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="RRSP_' . $rrsp_number . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        
    } catch (Exception $fallbackError) {
        // Ultimate fallback to CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="RRSP_' . $rrsp_number . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['RRSP Number', 'Date', 'Item Description', 'Quantity', 'ICS Number', 'End User', 'Conditions', 'Received By', 'End User Date', 'Head Date']);
        
        foreach ($return_items as $item) {
            fputcsv($output, [
                $rrsp_number,
                $current_date,
                $item['item'],
                $item['returned_qty'],
                $item['ICS_No'],
                $item['employee_name'],
                $item['conditions'],
                $current_user['name'], // Add current user to CSV
                $current_date, // End User Date
                $current_date  // Head Date
            ]);
        }
        
        fclose($output);
        exit();
    }
}
?>