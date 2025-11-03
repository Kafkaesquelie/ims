<?php
// export_registry_excel.php
require_once('includes/load.php');

// Make sure you have PhpSpreadsheet installed via Composer
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Start output buffering at the very beginning
ob_start();

try {
    // Get the posted data
    $export_data = json_decode($_POST['export_data'] ?? '{}', true);
    
    // Validate data
    if (empty($export_data)) {
        throw new Exception("No data received for export");
    }
    
    // Extract data from export_data
    $fund_cluster = $export_data['fund_cluster'] ?? '';
    $search = $export_data['search'] ?? '';
    $tableData = $export_data['tableData'] ?? [];
    
    $current_date = date('Y-m-d');
    $generation_date = date('F j, Y');
    
    // Define template path
    $templatePath = 'REGISTRY_Template.xlsx';
    $alternativePaths = [
        'REGISTRY_Template.xlsx',
        './REGISTRY_Template.xlsx',
        'templates/REGISTRY_Template.xlsx',
        '../REGISTRY_Template.xlsx'
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
        error_log("Registry Template FOUND at: " . $actualTemplatePath);
        
        try {
            // Load the template
            $spreadsheet = IOFactory::load($actualTemplatePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // DEBUG: Check if template loaded successfully
            error_log("Registry Template loaded successfully. Worksheet title: " . $worksheet->getTitle());
            
            // Map header information to template - FIXED: Include fund cluster at N4
            $mappingSuccess = false;
            
            // Try multiple possible cell mappings for header data
            $mappingAttempts = [
                // Attempt 1: Standard mapping
                [
                    'N4' => $fund_cluster ?: '__________', // FIXED: Added fund cluster at N4
                ],
                // Attempt 2: Alternative mapping
                [
                    'N4' => $fund_cluster ?: '__________', // FIXED: Added fund cluster at N4
                ]
            ];
            
            foreach ($mappingAttempts as $attempt => $mapping) {
                try {
                    foreach ($mapping as $cell => $value) {
                        $worksheet->setCellValue($cell, $value);
                    }
                    $mappingSuccess = true;
                    error_log("Header data mapping successful with attempt: " . ($attempt + 1));
                    break;
                } catch (Exception $e) {
                    error_log("Header mapping attempt " . ($attempt + 1) . " failed: " . $e->getMessage());
                    continue;
                }
            }
            
            if (!$mappingSuccess) {
                throw new Exception("All header mapping attempts failed");
            }
            
            // Populate table data - Try different starting rows
            $possibleStartRows = [8, 9, 10, 11, 12, 6, 7];
            $startRowUsed = null;
            
            foreach ($possibleStartRows as $startRow) {
                try {
                    $rowIndex = $startRow;
                    $testCell = 'A' . $rowIndex;
                    $currentValue = $worksheet->getCell($testCell)->getValue();
                    
                    // If cell is empty or contains placeholder text, use this row
                    if (empty($currentValue) || strpos($currentValue, 'DATE') !== false || strpos($currentValue, 'Date') !== false) {
                        $startRowUsed = $startRow;
                        break;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            if ($startRowUsed === null) {
                $startRowUsed = 8; // Default fallback
            }
            
            error_log("Using start row for registry data: " . $startRowUsed);
            
            $rowIndex = $startRowUsed;
            $recordsProcessed = 0;
            
            foreach ($tableData as $row) {
                try {
                    // Map row data to columns based on registry structure
                    $columnMapping = [
                        'A' => 0,  // DATE
                        'B' => 1,  // ICS/RRSP No.
                        'C' => 2,  // Semi-expandable Property No.
                        'D' => 3,  // ITEM DESCRIPTION
                        'E' => 4,  // Estimated Useful Life
                        'F' => 5,  // ISSUED QTY
                        'G' => 6,  // ISSUED Officer
                        'H' => 7,  // RETURNED QTY
                        'I' => 8,  // RETURNED Officer
                        'J' => 9,  // RE-ISSUED QTY
                        'K' => 10, // RE-ISSUED Officer
                        'L' => 11, // Disposed
                        'M' => 12, // Balance
                        'N' => 13, // Amount
                        'O' => 14  // Remarks
                    ];
                    
                    foreach ($columnMapping as $column => $dataIndex) {
                        if (isset($row[$dataIndex])) {
                            $cellValue = $row[$dataIndex];
                            
                            // FIXED: Format amount column (column N) to have .00
                            if ($column === 'N' && is_numeric($cellValue)) {
                                // Convert to number and format with 2 decimal places
                                $worksheet->setCellValue($column . $rowIndex, (float)$cellValue);
                                $worksheet->getStyle($column . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                            } else {
                                $worksheet->setCellValue($column . $rowIndex, $cellValue);
                            }
                        }
                    }
                    
                    $rowIndex++;
                    $recordsProcessed++;
                    
                } catch (Exception $e) {
                    error_log("Error processing registry row " . $rowIndex . ": " . $e->getMessage());
                    continue;
                }
            }
            
            // FIXED: Apply amount formatting to all amount cells in column N
            if ($recordsProcessed > 0) {
                $amountRange = 'N' . $startRowUsed . ':N' . ($rowIndex - 1);
                $worksheet->getStyle($amountRange)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            
            error_log("Total registry records processed in template: " . $recordsProcessed);
            
        } catch (Exception $e) {
            error_log("Error using registry template: " . $e->getMessage());
            $templateFound = false; // Fall back to creating from scratch
        }
    }
    
    // If template not found or failed to load, create from scratch
    if (!$templateFound || $spreadsheet === null) {
        error_log("Creating Registry Excel from scratch. Template found: " . ($templateFound ? 'YES' : 'NO'));
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("School Inventory Management System")
            ->setTitle("Registry of Semi-Expendable Property Issued")
            ->setSubject("Registry Export");
        
        // Create header
        $worksheet->setCellValue('A1', 'REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED');
        $worksheet->mergeCells('A1:O1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
   
        $worksheet->setCellValue('G3', $fund_cluster ?: '__________');
        $worksheet->setCellValue('N4', $fund_cluster ?: '__________'); // FIXED: Set fund cluster at N4
      
        
        // Create complex table headers for registry
        // First header row
        $worksheet->setCellValue('A6', 'DATE');
        $worksheet->mergeCells('A6:A8');
        $worksheet->setCellValue('B6', 'REFERENCE');
        $worksheet->mergeCells('B6:C6');
        $worksheet->setCellValue('D6', 'ITEM DESCRIPTION');
        $worksheet->mergeCells('D6:D8');
        $worksheet->setCellValue('E6', 'Estimated Useful Life');
        $worksheet->mergeCells('E6:E8');
        $worksheet->setCellValue('F6', 'ISSUED');
        $worksheet->mergeCells('F6:G6');
        $worksheet->setCellValue('H6', 'RETURNED');
        $worksheet->mergeCells('H6:I6');
        $worksheet->setCellValue('J6', 'RE-ISSUED');
        $worksheet->mergeCells('J6:K6');
        $worksheet->setCellValue('L6', 'Disposed');
        $worksheet->mergeCells('L6:L8');
        $worksheet->setCellValue('M6', 'Balance');
        $worksheet->mergeCells('M6:M8');
        $worksheet->setCellValue('N6', 'Amount');
        $worksheet->mergeCells('N6:N8');
        $worksheet->setCellValue('O6', 'Remarks');
        $worksheet->mergeCells('O6:O8');
        
        // Second header row
        $worksheet->setCellValue('B7', 'ICS/RRSP No.');
        $worksheet->mergeCells('B7:B8');
        $worksheet->setCellValue('C7', 'Semi-expandable Property No.');
        $worksheet->mergeCells('C7:C8');
        $worksheet->setCellValue('F7', 'QTY');
        $worksheet->mergeCells('F7:F8');
        $worksheet->setCellValue('G7', 'Officer');
        $worksheet->mergeCells('G7:G8');
        $worksheet->setCellValue('H7', 'QTY');
        $worksheet->mergeCells('H7:H8');
        $worksheet->setCellValue('I7', 'Officer');
        $worksheet->mergeCells('I7:I8');
        $worksheet->setCellValue('J7', 'QTY');
        $worksheet->mergeCells('J7:J8');
        $worksheet->setCellValue('K7', 'Officer');
        $worksheet->mergeCells('K7:K8');
        
        // Style header rows
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
        
        $worksheet->getStyle('A6:O8')->applyFromArray($headerStyle);
        
        // Populate registry data
        $rowIndex = 9;
        foreach ($tableData as $row) {
            $columnMapping = [
                'A' => 0,  // DATE
                'B' => 1,  // ICS/RRSP No.
                'C' => 2,  // Semi-expandable Property No.
                'D' => 3,  // ITEM DESCRIPTION
                'E' => 4,  // Estimated Useful Life
                'F' => 5,  // ISSUED QTY
                'G' => 6,  // ISSUED Officer
                'H' => 7,  // RETURNED QTY
                'I' => 8,  // RETURNED Officer
                'J' => 9,  // RE-ISSUED QTY
                'K' => 10, // RE-ISSUED Officer
                'L' => 11, // Disposed
                'M' => 12, // Balance
                'N' => 13, // Amount
                'O' => 14  // Remarks
            ];
            
            foreach ($columnMapping as $column => $dataIndex) {
                if (isset($row[$dataIndex])) {
                    $cellValue = $row[$dataIndex];
                    
                    // FIXED: Format amount column (column N) to have .00
                    if ($column === 'N' && is_numeric($cellValue)) {
                        // Convert to number and format with 2 decimal places
                        $worksheet->setCellValue($column . $rowIndex, (float)$cellValue);
                    } else {
                        $worksheet->setCellValue($column . $rowIndex, $cellValue);
                    }
                }
            }
            
            $rowIndex++;
        }
        
        // FIXED: Apply amount formatting to all amount cells in column N
        if (!empty($tableData)) {
            $amountRange = 'N9:N' . ($rowIndex - 1);
            $worksheet->getStyle($amountRange)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        
        // Fill empty rows to reach 25 (like in the HTML view)
        $currentRows = count($tableData);
        $emptyRows = max(0, 25 - $currentRows);
        
        for ($i = 0; $i < $emptyRows; $i++) {
            for ($col = 'A'; $col <= 'O'; $col++) {
                $worksheet->setCellValue($col . $rowIndex, '');
            }
            $rowIndex++;
        }
        
        // Apply styling to data rows
        if (!empty($tableData) || $emptyRows > 0) {
            $lastRow = $rowIndex - 1;
            $dataRange = 'A9:O' . $lastRow;
            
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
        }
        
        // Auto-size columns
        foreach (range('A', 'O') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
    
    // Generate filename
    $filename = "Registry_Semi_Expendable_" . ($fund_cluster ?: 'All') . "_" . date('Y-m-d') . ".xlsx";
    
    // Clean output buffer completely before headers
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
    error_log("Registry Excel Export Error: " . $e->getMessage());
    
    // Return simple error message
    echo "Error exporting Registry to Excel: " . $e->getMessage();
    exit;
}