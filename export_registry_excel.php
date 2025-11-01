<?php
require_once 'includes/load.php';
// Make sure you have PHPExcel or PhpSpreadsheet installed
require_once 'vendor/autoload.php'; // If using Composer

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $fund_cluster = $_POST['fund_cluster'] ?? '';
    $tableData = json_decode($_POST['tableData'], true);
    
    // Export to Excel function
    exportRegistryToExcel($tableData, $fund_cluster);
}

function exportRegistryToExcel($tableData, $fund_cluster) {
    try {
        // Define the template path
        $templatePath = 'REGISTRY_Template.xlsx';
        
        // Check if template exists
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: " . $templatePath);
        }
        
        // Load the template
        $spreadsheet = IOFactory::load($templatePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Set basic information in the template
        $exportDate = date('F d, Y');
        $worksheet->setCellValue('A1', 'REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED');
        $worksheet->setCellValue('A2', 'Entity Name: Benguet State University - Bokod Campus');
        $worksheet->setCellValue('A3', 'Fund Cluster: ' . $fund_cluster);
        $worksheet->setCellValue('A4', 'Export Date: ' . $exportDate);
        
        // Define starting row for data (adjust based on your template structure)
        $startRow = 6; // Typically templates have headers at row 5 or 6
        
        // Populate data into the template
        $rowIndex = $startRow;
        
        foreach ($tableData as $row) {
            $colIndex = 0;
            
            foreach ($row as $cellValue) {
                $columnLetter = getColumnLetter($colIndex);
                $worksheet->setCellValue($columnLetter . $rowIndex, $cellValue);
                $colIndex++;
            }
            
            $rowIndex++;
        }
        
        // Set headers for file download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Registry_Semi_Expendable_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Save the file
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        // Log error and return message
        error_log("Excel Export Error: " . $e->getMessage());
        
        // Return error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error exporting to Excel: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Alternative function if template doesn't exist - create from scratch
function exportRegistryToExcelWithoutTemplate($tableData, $fund_cluster) {
    try {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Benguet State University - Bokod Campus")
            ->setTitle("Registry of Semi-Expendable Property Issued")
            ->setSubject("Semi-Expendable Property Registry");
        
        // Set headers
        $headers = [
            'DATE', 
            'ICS/RRSP No.', 
            'Semi-expendable Property No.', 
            'ITEM DESCRIPTION', 
            'Estimated Useful Life',
            'ISSUED QTY', 
            'ISSUED Officer',
            'RETURNED QTY', 
            'RETURNED Officer',
            'RE-ISSUED QTY', 
            'RE-ISSUED Officer',
            'Disposed', 
            'Balance', 
            'Amount', 
            'Remarks'
        ];
        
        // Set title and metadata
        $worksheet->setCellValue('A1', 'REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED');
        $worksheet->mergeCells('A1:O1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $worksheet->setCellValue('A2', 'Entity Name: Benguet State University - Bokod Campus');
        $worksheet->setCellValue('A3', 'Fund Cluster: ' . $fund_cluster);
        $worksheet->setCellValue('A4', 'Export Date: ' . date('F d, Y'));
        
        // Set column headers
        $worksheet->fromArray($headers, NULL, 'A6');
        
        // Apply styles to headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8E8E8']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        
        $worksheet->getStyle('A6:O6')->applyFromArray($headerStyle);
        
        // Populate data
        $rowIndex = 7;
        foreach ($tableData as $row) {
            $colIndex = 0;
            foreach ($row as $cellValue) {
                $columnLetter = getColumnLetter($colIndex);
                $worksheet->setCellValue($columnLetter . $rowIndex, $cellValue);
                $colIndex++;
            }
            $rowIndex++;
        }
        
        // Apply borders to data
        $dataRange = 'A7:O' . ($rowIndex - 1);
        $worksheet->getStyle($dataRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Auto-size columns
        foreach (range('A', 'O') as $columnID) {
            $worksheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Registry_Semi_Expendable_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        error_log("Excel Export Error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error exporting to Excel: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Helper function to convert column index to letter
function getColumnLetter($index) {
    $letters = '';
    while ($index >= 0) {
        $letters = chr(65 + ($index % 26)) . $letters;
        $index = floor($index / 26) - 1;
    }
    return $letters ?: 'A';
}

// If you need a simple CSV export as fallback
function exportToCSV($tableData, $fund_cluster) {
    $filename = "Registry_Semi_Expendable_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    $headers = [
        'DATE', 'ICS/RRSP No.', 'Property No.', 'Item Description', 
        'Useful Life', 'Issued Qty', 'Issued Officer', 'Returned Qty', 
        'Returned Officer', 'Re-issued Qty', 'Re-issued Officer', 
        'Disposed', 'Balance', 'Amount', 'Remarks'
    ];
    
    fputcsv($output, $headers);
    
    // Add data
    foreach ($tableData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>