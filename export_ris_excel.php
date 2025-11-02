<?php
require_once 'includes/load.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Get the RIS number from URL parameter
$ris_no = isset($_GET['ris_no']) ? trim($db->escape($_GET['ris_no'])) : null;

if (!$ris_no) {
    $session->msg("d", "No RIS number provided.");
    redirect('logs.php');
}

// Fetch request info
$request = find_by_id('requests', $ris_no);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('logs.php');
}

// Fetch requestor info
$requestor_name = 'Unknown';
$requestor_position = '';
$requestor_division = '________________';
$requestor_office = '________________';

if (isset($request['requested_by'])) {
    $user = find_by_id('users', (int)$request['requested_by']);
    if ($user) {
        $requestor_name = $user['name'] ?? 'Unknown';
        $requestor_position = $user['position'] ?? '';
        
        $employee = find_by_sql("SELECT * FROM employees WHERE user_id = '{$request['requested_by']}' LIMIT 1");
        if (!empty($employee)) {
            $employee = $employee[0];
            
            // 🟩 DEBUG: Check what data we're getting
            error_log("Employee data: " . print_r($employee, true));
            
            // 🟩 FIXED: Get division and office data properly
            $requestor_division = $employee['division'] ?? '________________';
            $requestor_office = $employee['office'] ?? '________________';
            $requestor_position = $employee['position'] ?? $requestor_position;
            
            // 🟩 If values are empty, use defaults
            if (empty($requestor_division) || $requestor_division == '________________') {
                $requestor_division = '________________';
            } else {
                // 🟩 If it's numeric, try to get the name from divisions table
                if (is_numeric($requestor_division)) {
                    $division_data = find_by_id('divisions', $requestor_division);
                    $requestor_division = $division_data ? ($division_data['name'] ?? $division_data['division_name'] ?? '________________') : '________________';
                }
            }
            
            if (empty($requestor_office) || $requestor_office == '________________') {
                $requestor_office = '________________';
            } else {
                // 🟩 If it's numeric, try to get the name from offices table
                if (is_numeric($requestor_office)) {
                    $office_data = find_by_id('offices', $requestor_office);
                    $requestor_office = $office_data ? ($office_data['name'] ?? $office_data['office_name'] ?? '________________') : '________________';
                }
            }
        }
    }
}

// 🟩 DEBUG: Check final values
error_log("Final Division: " . $requestor_division);
error_log("Final Office: " . $requestor_office);

// Fetch requested items
$items = find_by_sql("
    SELECT 
        ri.item_id,
        ri.qty,
        ri.unit,
        ri.remarks,
        i.name as item_name,
        i.stock_card,
        i.quantity as current_stock
    FROM request_items ri
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE ri.req_id = '{$ris_no}'
");

if (empty($items)) {
    $session->msg("d", "No items found for RIS: {$ris_no}");
    redirect('logs.php');
}

// Get remarks
$request_remarks = '';
if (!empty($items[0]['remarks'])) {
    $request_remarks = $items[0]['remarks'];
} elseif (!empty($request['remarks'])) {
    $request_remarks = $request['remarks'];
}

// Current user info
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";
$current_user_position = $current_user['position'] ?? "";

// Request status
$request_status = $request['status'] ?? 'Pending';
$all_items_available = ($request_status == 'Completed');

try {
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set paper size to 8.5" x 13" (Legal)
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LEGAL);
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);

    // Set margins
    $sheet->getPageMargins()->setTop(0.5);
    $sheet->getPageMargins()->setRight(0.5);
    $sheet->getPageMargins()->setLeft(0.5);
    $sheet->getPageMargins()->setBottom(0.5);

    // Define styles
    $headerStyle = [
        'font' => [
            'bold' => true,
            'size' => 16,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ]
    ];

    $entityInfoStyle = [
        'font' => [
            'size' => 9,
        ]
    ];

    $tableHeaderStyle = [
        'font' => [
            'bold' => true,
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F0F0F0']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    $tableCellStyle = [
        'font' => [
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    $purposeStyle = [
        'font' => [
            'bold' => true,
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    $signatoryHeaderStyle = [
        'font' => [
            'bold' => true,
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    $signatoryStyle = [
        'font' => [
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    $leftAlignStyle = [
        'font' => [
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    $infoCellStyle = [
        'font' => [
            'size' => 10,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ]
        ]
    ];

    // Calculate total rows per copy (header + info + table + purpose + signatories)
    $rowsPerCopy = 2 + 4 + 2 + (max(count($items), 4)) + 2 + 5; // ~25-30 rows
    
    // 🟩 FIXED: Create three copies with EXACTLY 2 rows gap between them
    for ($copy = 1; $copy <= 3; $copy++) {
        // Calculate start row: (copy-1) * (rowsPerCopy + 2 gap rows)
        $startRow = ($copy - 1) * ($rowsPerCopy + 2) + 1;
        
        // Header
        $sheet->setCellValue('A' . $startRow, 'REQUISITION AND ISSUE SLIP');
        $sheet->mergeCells('A' . $startRow . ':H' . $startRow);
        $sheet->getStyle('A' . $startRow)->applyFromArray($headerStyle);

        // Entity Info
        $sheet->setCellValue('A' . ($startRow + 2), 'Entity Name: BENGUET STATE UNIVERSITY - BOKOD CAMPUS');
        $sheet->setCellValue('F' . ($startRow + 2), 'Fund Cluster: __________');
        $sheet->getStyle('A' . ($startRow + 2))->applyFromArray($entityInfoStyle);
        $sheet->getStyle('F' . ($startRow + 2))->applyFromArray($entityInfoStyle);

        // 🟩 FIXED: Division and Office with actual data
        $sheet->setCellValue('A' . ($startRow + 4), 'Division: ' . $requestor_division);
        $sheet->setCellValue('A' . ($startRow + 5), 'Office: ' . $requestor_office);
        $sheet->mergeCells('A' . ($startRow + 4) . ':E' . ($startRow + 4));
        $sheet->mergeCells('A' . ($startRow + 5) . ':E' . ($startRow + 5));
        $sheet->getStyle('A' . ($startRow + 4) . ':E' . ($startRow + 5))->applyFromArray($infoCellStyle);

        // RIS Info with borders
        $sheet->setCellValue('F' . ($startRow + 4), 'Responsibility Center Code: __________');
        $sheet->setCellValue('F' . ($startRow + 5), 'RIS No: ' . ($request['ris_no'] ?? '__________'));
        $sheet->mergeCells('F' . ($startRow + 4) . ':H' . ($startRow + 4));
        $sheet->mergeCells('F' . ($startRow + 5) . ':H' . ($startRow + 5));
        $sheet->getStyle('F' . ($startRow + 4) . ':H' . ($startRow + 5))->applyFromArray($infoCellStyle);

        // Main table header - NO SPACE from previous section
        $sheet->setCellValue('A' . ($startRow + 6), 'Requisition');
        $sheet->mergeCells('A' . ($startRow + 6) . ':D' . ($startRow + 6));
        $sheet->setCellValue('E' . ($startRow + 6), 'Stock Available?');
        $sheet->mergeCells('E' . ($startRow + 6) . ':F' . ($startRow + 6));
        $sheet->setCellValue('G' . ($startRow + 6), 'Issue');
        $sheet->mergeCells('G' . ($startRow + 6) . ':H' . ($startRow + 6));
        $sheet->getStyle('A' . ($startRow + 6) . ':H' . ($startRow + 6))->applyFromArray($tableHeaderStyle);

        // Column headers
        $columns = ['Stock No.', 'Unit', 'Description', 'Quantity', 'Yes', 'No', 'Quantity', 'Remarks'];
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col . ($startRow + 7), $column);
            $col++;
        }
        $sheet->getStyle('A' . ($startRow + 7) . ':H' . ($startRow + 7))->applyFromArray($tableHeaderStyle);

        // Items data
        $currentRow = $startRow + 8;
        foreach ($items as $index => $item) {
            $sheet->setCellValue('A' . $currentRow, $item['stock_card'] ?? '');
            $sheet->setCellValue('B' . $currentRow, $item['unit'] ?? '');
            $sheet->setCellValue('C' . $currentRow, $item['item_name'] ?? '');
            $sheet->setCellValue('D' . $currentRow, $item['qty'] ?? 0);
            $sheet->setCellValue('E' . $currentRow, $all_items_available ? '✔' : '');
            $sheet->setCellValue('F' . $currentRow, !$all_items_available ? '✔' : '');
            $sheet->setCellValue('G' . $currentRow, $all_items_available ? $item['qty'] : 0);
            
            if ($index === 0 && !empty($request_remarks)) {
                $sheet->setCellValue('H' . $currentRow, $request_remarks);
            }
            
            $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray($tableCellStyle);
            $currentRow++;
        }

        // Fill empty rows
        $item_count = count($items);
        $empty_rows = max(0, 4 - $item_count);
        for ($i = 0; $i < $empty_rows; $i++) {
            $sheet->setCellValue('A' . $currentRow, '');
            $sheet->setCellValue('B' . $currentRow, '');
            $sheet->setCellValue('C' . $currentRow, '');
            $sheet->setCellValue('D' . $currentRow, '');
            $sheet->setCellValue('E' . $currentRow, '');
            $sheet->setCellValue('F' . $currentRow, '');
            $sheet->setCellValue('G' . $currentRow, '');
            $sheet->setCellValue('H' . $currentRow, '');
            $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray($tableCellStyle);
            $currentRow++;
        }

        // Purpose with border - NO SPACE from items table
        $sheet->setCellValue('A' . $currentRow, 'Purpose: ' . ($request_remarks ?: ''));
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray($purposeStyle);
        $currentRow++;

        // Empty row for purpose with border
        $sheet->setCellValue('A' . $currentRow, '');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray($tableCellStyle);
        $currentRow++;

        // Signatories header with borders - NO SPACE from purpose
        $sheet->setCellValue('C' . $currentRow, 'Requested By');
        $sheet->setCellValue('D' . $currentRow, 'Approved By');
        $sheet->setCellValue('F' . $currentRow, 'Issued By');
        $sheet->setCellValue('H' . $currentRow, 'Received By');
        $sheet->mergeCells('C' . $currentRow . ':C' . $currentRow);
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->mergeCells('H' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray($signatoryHeaderStyle);
        $currentRow++;

        // Signature labels with borders
        $sheet->setCellValue('A' . $currentRow, 'Signature:');
        $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
        $sheet->setCellValue('C' . $currentRow, '');
        $sheet->setCellValue('D' . $currentRow, '');
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->setCellValue('F' . $currentRow, '');
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->setCellValue('H' . $currentRow, '');
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray($leftAlignStyle);
        $sheet->getStyle('C' . $currentRow . ':H' . $currentRow)->applyFromArray($signatoryStyle);
        $currentRow++;

        // Printed names with borders
        $sheet->setCellValue('A' . $currentRow, 'Printed Name:');
        $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
        $sheet->setCellValue('C' . $currentRow, $requestor_name);
        $sheet->setCellValue('D' . $currentRow, $current_user_name);
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->setCellValue('F' . $currentRow, $current_user_name);
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->setCellValue('H' . $currentRow, $requestor_name);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($leftAlignStyle);
        $sheet->getStyle('C' . $currentRow . ':H' . $currentRow)->applyFromArray($signatoryStyle);
        $currentRow++;

        // Designation with borders
        $sheet->setCellValue('A' . $currentRow, 'Designation:');
        $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
        $sheet->setCellValue('C' . $currentRow, $requestor_position);
        $sheet->setCellValue('D' . $currentRow, $current_user_position);
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->setCellValue('F' . $currentRow, $current_user_position);
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->setCellValue('H' . $currentRow, $requestor_position);
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($leftAlignStyle);
        $sheet->getStyle('C' . $currentRow . ':H' . $currentRow)->applyFromArray($signatoryStyle);
        $currentRow++;

        // Date with borders
        $sheet->setCellValue('A' . $currentRow, 'Date:');
        $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
        $sheet->setCellValue('C' . $currentRow, '');
        $sheet->setCellValue('D' . $currentRow, '');
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->setCellValue('F' . $currentRow, '');
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->setCellValue('H' . $currentRow, '');
        $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->applyFromArray($leftAlignStyle);
        $sheet->getStyle('C' . $currentRow . ':H' . $currentRow)->applyFromArray($signatoryStyle);

        // 🟩 FIXED: Add EXACTLY 2 empty rows gap between copies (except after last copy)
        if ($copy < 3) {
            $gapStartRow = $currentRow + 1;
            // Add exactly 2 empty rows as gap
            for ($i = 0; $i < 2; $i++) {
                $gapRow = $gapStartRow + $i;
                $sheet->setCellValue('A' . $gapRow, '');
                $sheet->mergeCells('A' . $gapRow . ':H' . $gapRow);
            }
        }
    }

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(8);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(10);
    $sheet->getColumnDimension('E')->setWidth(6);
    $sheet->getColumnDimension('F')->setWidth(6);
    $sheet->getColumnDimension('G')->setWidth(10);
    $sheet->getColumnDimension('H')->setWidth(15);

    // Set row heights
    for ($i = 1; $i <= 150; $i++) {
        $sheet->getRowDimension($i)->setRowHeight(15);
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="RIS_' . $request['ris_no'] . '_FORMATTED.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Save file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    error_log("RIS Excel Export Error: " . $e->getMessage());
    $session->msg("d", "Error generating Excel file: " . $e->getMessage());
    redirect('logs.php');
}