<?php
// export_stock_excel.php
require_once('includes/load.php');

// Make sure you have PhpSpreadsheet installed via Composer
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Start output buffering at the very beginning
if (ob_get_level() === 0) {
    ob_start();
}

try {
    // Get the posted data
    $export_data = json_decode($_POST['export_data'] ?? '{}', true);
    
    // Validate data
    if (empty($export_data)) {
        throw new Exception("No data received for export");
    }
    
    // Extract data from export_data
    $stock_number = $export_data['stock_number'] ?? '';
    $item_name = $export_data['item_name'] ?? '';
    $description = $export_data['description'] ?? '';
    $unit_of_measurement = $export_data['unit_of_measurement'] ?? '';
    $fund_cluster = $export_data['fund_cluster'] ?? '';
    $reorder_point = $export_data['reorder_point'] ?? '';
    $transactions = $export_data['transactions'] ?? [];
    
    $current_date = date('Y-m-d');
    $generation_date = date('F j, Y');
    
    // Define template path - Check multiple possible locations
    $templatePath = 'STOCK_Template.xlsx';
    $alternativePaths = [
        'STOCK_Template.xlsx',
        './STOCK_Template.xlsx',
        'templates/STOCK_Template.xlsx',
        '../STOCK_Template.xlsx'
    ];
    
    $templateFound = false;
    $actualTemplatePath = '';
    
    foreach ($alternativePaths as $path) {
        if (file_exists($path)) {
            $templateFound = true;
            $actualTemplatePath = $path;
            break;
        }
    }
    
    $spreadsheet = null;
    
    if ($templateFound) {
        // DEBUG: Log template found
        error_log("Template FOUND at: " . $actualTemplatePath);
        error_log("File size: " . filesize($actualTemplatePath) . " bytes");
        
        try {
            // Load the template
            $spreadsheet = IOFactory::load($actualTemplatePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // DEBUG: Check if template loaded successfully
            error_log("Template loaded successfully. Worksheet title: " . $worksheet->getTitle());
            
            // Map data to template - Try different cell references
            $mappingSuccess = false;
            
            // Try multiple possible cell mappings
            $mappingAttempts = [
                // Attempt 1: Standard mapping
                [
                    'C7' => $fund_cluster,
                    'C8' => $stock_number, 
                    'C9' => $item_name,
                    'C10' => $description,
                    'C11' => $unit_of_measurement,
                    'I8' => $reorder_point,
                ],
                // Attempt 2: Alternative mapping
                [
                    'C2' => $fund_cluster,
                    'C3' => $stock_number,
                    'C4' => $item_name,
                    'C5' => $description,
                    'C6' => $unit_of_measurement,
                    'G3' => $reorder_point,
                    'G2' => $generation_date
                ]
            ];
            
            foreach ($mappingAttempts as $attempt => $mapping) {
                try {
                    foreach ($mapping as $cell => $value) {
                        $worksheet->setCellValue($cell, $value);
                    }
                    $mappingSuccess = true;
                    error_log("Data mapping successful with attempt: " . ($attempt + 1));
                    break;
                } catch (Exception $e) {
                    error_log("Mapping attempt " . ($attempt + 1) . " failed: " . $e->getMessage());
                    continue;
                }
            }
            
            if (!$mappingSuccess) {
                throw new Exception("All mapping attempts failed");
            }
            
            // FIXED: Start at row 16 for populating transaction data
            $startRowUsed = 16;
            error_log("Using fixed start row: " . $startRowUsed);
            
            $rowIndex = $startRowUsed;
            $transactionsProcessed = 0;
            
            foreach ($transactions as $transaction) {
                try {
                    if (!empty($transaction['date'])) {
                        $worksheet->setCellValue('A' . $rowIndex, date("m/d/Y", strtotime($transaction['date'])));
                    }
                    
                    $worksheet->setCellValue('B' . $rowIndex, $transaction['reference'] ?? '');
                    
                    // Receipt columns
                    if ($transaction['change_type'] === 'stock_in' || $transaction['change_type'] === 'carry_forward') {
                        $worksheet->setCellValue('C' . $rowIndex, $transaction['quantity'] ?? '');
                        $worksheet->setCellValue('D' . $rowIndex, $transaction['unit_cost'] ?? '');
                    }
                    
                    // Issuance columns
                    if ($transaction['change_type'] === 'issuance') {
                        $worksheet->setCellValue('E' . $rowIndex, $transaction['quantity'] ?? '');
                        $worksheet->setCellValue('F' . $rowIndex, $transaction['unit_cost'] ?? '');
                        $worksheet->setCellValue('G' . $rowIndex, $transaction['total_cost'] ?? '');
                    }
                    
                    $worksheet->setCellValue('H' . $rowIndex, $transaction['office_name'] ?? '');
                    $worksheet->setCellValue('I' . $rowIndex, $transaction['running_balance'] ?? '');
                    $worksheet->setCellValue('J' . $rowIndex, $transaction['running_total_cost'] ?? '');
                    
                    $remarks = $transaction['change_type'] === 'carry_forward' ? 'Carried Forward' : ($transaction['remarks'] ?? '');
                    $worksheet->setCellValue('L' . $rowIndex, $remarks);
                    
                    $rowIndex++;
                    $transactionsProcessed++;
                    
                } catch (Exception $e) {
                    error_log("Error processing transaction row " . $rowIndex . ": " . $e->getMessage());
                    continue;
                }
            }
            
            error_log("Total transactions processed in template: " . $transactionsProcessed);
            
        } catch (Exception $e) {
            error_log("Error using template: " . $e->getMessage());
            $templateFound = false; // Fall back to creating from scratch
        }
    }
    
    // If template not found or failed to load, create from scratch
    if (!$templateFound || $spreadsheet === null) {
        error_log("Creating Excel from scratch. Template found: " . ($templateFound ? 'YES' : 'NO'));
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("School Inventory Management System")
            ->setTitle("Stock Card - " . ($stock_number ?: 'Unknown'))
            ->setSubject("Stock Card Export");
        
        // Create header
        $worksheet->setCellValue('A1', 'STOCK CARD');
        $worksheet->mergeCells('A1:L1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Item information
        $worksheet->setCellValue('A3', 'Fund Cluster:');
        $worksheet->setCellValue('B3', $fund_cluster);
        $worksheet->setCellValue('A4', 'Stock Number:');
        $worksheet->setCellValue('B4', $stock_number);
        $worksheet->setCellValue('A5', 'Item:');
        $worksheet->setCellValue('B5', $item_name);
        $worksheet->setCellValue('A6', 'Description:');
        $worksheet->setCellValue('B6', $description);
        $worksheet->setCellValue('A7', 'Unit of Measurement:');
        $worksheet->setCellValue('B7', $unit_of_measurement);
        $worksheet->setCellValue('F4', 'Re-order Point:');
        $worksheet->setCellValue('G4', $reorder_point);
        $worksheet->setCellValue('F3', 'Generated:');
        $worksheet->setCellValue('G3', $generation_date);
        
        // Table headers
        $headers = [
            'A15' => 'Date Received / Issued', // Changed to row 15 to match template structure
            'B15' => 'Reference',
            'C15' => 'Receipt Qty',
            'D15' => 'Unit Cost',
            'E15' => 'Issuance Qty',
            'F15' => 'Unit Cost',
            'G15' => 'Total Cost',
            'H15' => 'Office',
            'I15' => 'Balance Qty',
            'J15' => 'Total Cost',
            'K15' => 'No. of Days to Consume',
            'L15' => 'Remarks'
        ];
        
        foreach ($headers as $cell => $header) {
            $worksheet->setCellValue($cell, $header);
        }
        
        // Style header row
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8E8E8']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        
        $worksheet->getStyle('A15:L15')->applyFromArray($headerStyle);
        
        // FIXED: Start at row 16 for populating transaction data
        $rowIndex = 16;
        foreach ($transactions as $transaction) {
            if (!empty($transaction['date'])) {
                $worksheet->setCellValue('A' . $rowIndex, date("m/d/Y", strtotime($transaction['date'])));
            }
            
            $worksheet->setCellValue('B' . $rowIndex, $transaction['reference'] ?? '');
            
            if ($transaction['change_type'] === 'stock_in' || $transaction['change_type'] === 'carry_forward') {
                $worksheet->setCellValue('C' . $rowIndex, $transaction['quantity'] ?? '');
                $worksheet->setCellValue('D' . $rowIndex, $transaction['unit_cost'] ?? '');
            }
            
            if ($transaction['change_type'] === 'issuance') {
                $worksheet->setCellValue('E' . $rowIndex, $transaction['quantity'] ?? '');
                $worksheet->setCellValue('F' . $rowIndex, $transaction['unit_cost'] ?? '');
                $worksheet->setCellValue('G' . $rowIndex, $transaction['total_cost'] ?? '');
            }
            
            $worksheet->setCellValue('H' . $rowIndex, $transaction['office_name'] ?? '');
            $worksheet->setCellValue('I' . $rowIndex, $transaction['running_balance'] ?? '');
            $worksheet->setCellValue('J' . $rowIndex, $transaction['running_total_cost'] ?? '');
            
            $remarks = $transaction['change_type'] === 'carry_forward' ? 'Carried Forward' : ($transaction['remarks'] ?? '');
            $worksheet->setCellValue('L' . $rowIndex, $remarks);
            
            $rowIndex++;
        }
        
        // Apply styling to data rows
        if (!empty($transactions)) {
            $lastRow = $rowIndex - 1;
            $dataRange = 'A16:L' . $lastRow;
            
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];
            
            $worksheet->getStyle($dataRange)->applyFromArray($dataStyle);
            
            // Format numbers
            $worksheet->getStyle('D16:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('F16:F' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('G16:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('J16:J' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle('C16:C' . $lastRow)->getNumberFormat()->setFormatCode('0');
            $worksheet->getStyle('E16:E' . $lastRow)->getNumberFormat()->setFormatCode('0');
            $worksheet->getStyle('I16:I' . $lastRow)->getNumberFormat()->setFormatCode('0');
        }
        
        // Auto-size columns
        foreach (range('A', 'L') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
    
    // Generate filename
    $filename = "Stock_Card_" . ($stock_number ?: 'Unknown') . "_" . date('Y-m-d') . ".xlsx";
    
    // Clean output buffer and set headers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    
    // Save the file
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    // Clean any output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Log error
    error_log("Excel Export Error: " . $e->getMessage());
    
    // Return simple error message
    echo "Error exporting to Excel: " . $e->getMessage();
    exit;
}