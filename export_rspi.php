<?php
require_once('includes/load.php');

// Include PhpSpreadsheet library
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Validate
if (!isset($_POST['excel_data'])) {
    $session->msg('d', 'No data to export.');
    redirect('print_rspi.php', false);
}

$excelData = json_decode($_POST['excel_data'], true);

// Input variables from POST
$entity_name = $_POST['entity_name'] ?? '';
$serial_no = $_POST['serial_no'] ?? '';
$fund_cluster = $_POST['fund_cluster'] ?? '';
$report_date = $_POST['report_date'] ?? '';
$property_custodian = $_POST['property_custodian'] ?? '';
$accounting_staff = $_POST['accounting_staff'] ?? '';

// Path to template
$templatePath = 'templates/RSPI_Template.xlsx';

if (!file_exists($templatePath)) {
    die("Template file not found: " . $templatePath);
}

try {
    // Load template
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // Fill Header Data
    $sheet->setCellValue('G5', $serial_no);
    $sheet->setCellValue('B6', $fund_cluster);
    $sheet->setCellValue('G6', $report_date);

    // Starting row for items
    $row = 9;

    // Insert item data into rows
    foreach ($excelData as $item) {
        // Get ICS number from the item data
        $ics_no = $item['ics_no'] ?? '';
        
        // FIXED: Set the values as numbers but apply number formatting
        $sheet->setCellValue("A{$row}", $ics_no);           // ICS No.
        $sheet->setCellValue("B{$row}", '');                // Responsibility Center Code (empty for now)
        $sheet->setCellValue("C{$row}", $item['inv_item_no']);      // Property No.
        $sheet->setCellValue("D{$row}", $item['item_description']); // Item Description
        $sheet->setCellValue("E{$row}", $item['unit']);             // Unit
        $sheet->setCellValue("F{$row}", $item['qty_issued']);       // Qty Issued
        
        // FIXED: Set numeric values and apply number formatting
        $sheet->setCellValue("G{$row}", $item['unit_cost']);        // Unit Cost (as number)
        $sheet->setCellValue("H{$row}", $item['amount']);           // Amount (as number)
        
        // FIXED: Apply number formatting to show 2 decimal places
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        
        $row++;
    }

    // Footer Signatories
    $sheet->setCellValue('C31', $property_custodian);
    $sheet->setCellValue('F31', $accounting_staff);

    // Auto-generate filename
    $fileName = 'RSPI_Report_' . date('Y-m-d_His') . '.xlsx';

    // Clear any previous output
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Write output to browser
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    // Log error and provide fallback
    error_log("Excel export error: " . $e->getMessage());
    
    // Fallback to CSV
    exportAsCSV($excelData, [
        'entity_name' => $entity_name,
        'serial_no' => $serial_no,
        'fund_cluster' => $fund_cluster,
        'report_date' => $report_date,
        'property_custodian' => $property_custodian,
        'accounting_staff' => $accounting_staff
    ]);
}

function exportAsCSV($data, $metadata) {
    // Clear output
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="RSPI_Report_' . date('Y-m-d_His') . '.csv"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Write headers
    fputcsv($output, ['REPORT OF SEMI-EXPENDABLE PROPERTY ISSUED (RSPI)']);
    fputcsv($output, []);
    fputcsv($output, ['Entity Name:', $metadata['entity_name'], '', 'Serial No.:', $metadata['serial_no']]);
    fputcsv($output, ['Fund Cluster:', $metadata['fund_cluster'], '', 'Date:', $metadata['report_date']]);
    fputcsv($output, []);
    
    // Write table headers - Updated to match the 8 columns
    fputcsv($output, ['ICS No.', 'Resp Center Code', 'Property No.', 'Item Description', 'Unit', 'Qty Issued', 'Unit Cost', 'Amount']);
    
    // Write data with formatted numbers
    foreach ($data as $item) {
        // Get ICS number and format numbers for CSV
        $ics_no = $item['ics_no'] ?? '';
        
        fputcsv($output, [
            $ics_no,                    // ICS No.
            '',                         // Responsibility Center Code (empty)
            $item['inv_item_no'],       // Property No.
            $item['item_description'],  // Item Description
            $item['unit'],              // Unit
            $item['qty_issued'],        // Qty Issued
            number_format($item['unit_cost'], 2, '.', ''),  // Unit Cost (formatted)
            number_format($item['amount'], 2, '.', '')      // Amount (formatted)
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['', 'I hereby certify to the correctness of the above information.', '', '', '', 'Posted by:']);
    fputcsv($output, ['', $metadata['property_custodian'], '', '', '', $metadata['accounting_staff']]);
    
    fclose($output);
    exit;
}
?>