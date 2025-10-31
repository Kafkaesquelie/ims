<?php
// export_rsmi_excel.php
require_once('includes/load.php');
page_require_level(1);

// Composer autoload
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Get parameters from POST or GET
$report_date = $_POST['report_date'] ?? $_GET['report_date'] ?? null;
$selected_cluster = $_POST['fund_cluster'] ?? $_GET['fund_cluster'] ?? null;
$signatory_name = $_POST['signatory_name'] ?? $_GET['signatory_name'] ?? '';
$signatory_position = $_POST['signatory_position'] ?? $_GET['signatory_position'] ?? '';
$signatory_agency = $_POST['signatory_agency'] ?? $_GET['signatory_agency'] ?? '';
$serial_number = $_POST['serial_number'] ?? $_GET['serial_number'] ?? '';

// Build WHERE filters
$where_date = $report_date ? " AND DATE(r.date) = '" . $db->escape($report_date) . "'" : '';
$where_cluster = ($selected_cluster && $selected_cluster !== 'all') ? " AND i.fund_cluster = '" . $db->escape($selected_cluster) . "'" : '';

// Fetch data
$issued_items = find_by_sql("
    SELECT 
        r.id,
        r.ris_no,
        i.stock_card,
        i.name AS item_name,
        u.symbol AS unit_symbol,
        bu.symbol AS base_unit_symbol,
        i.unit_id,
        i.base_unit_id,
        ri.qty AS qty_issued,
        us.position,
        i.unit_cost,
        (ri.qty * i.unit_cost) AS amount,
        i.fund_cluster
    FROM request_items ri
    JOIN requests r ON ri.req_id = r.id
    JOIN items i ON ri.item_id = i.id
    JOIN users us ON r.requested_by = us.id
    LEFT JOIN units u ON i.unit_id = u.id
    LEFT JOIN base_units bu ON i.base_unit_id = bu.id
    WHERE r.status NOT IN ('Pending', 'Approved')
      AND i.stock_card IS NOT NULL
      AND i.stock_card != ''
      $where_date
      $where_cluster
    ORDER BY r.date ASC
");

// Prepare header values
$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : date("F d, Y");
$year = $report_date ? date("Y", strtotime($report_date)) : date("Y");
$month = $report_date ? date("m", strtotime($report_date)) : date("m");
$serial_no_prefix = $year . '-' . $month . '-';
$final_serial_number = $serial_number ?: $serial_no_prefix . '0000';
$current_user = current_user();

// FIX: If current user position is not available, try to fetch it from database
if (empty($current_user['position'])) {
    $user_id = $current_user['id'] ?? 0;
    if ($user_id) {
        $user_data = find_by_id('users', $user_id);
        if ($user_data && !empty($user_data['position'])) {
            $current_user['position'] = $user_data['position'];
        }
    }
}

// FIX: If still no position, set a default
if (empty($current_user['position'])) {
    $current_user['position'] = 'Administrative Staff';
}

// Template path
$templatePath = __DIR__ . '/templates/RSMI_Template.xls';

// Check if template exists and try to use it, otherwise create from scratch
if (file_exists($templatePath)) {
    try {
        // Try to load the template
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $usingTemplate = true;
        
        // Replace placeholders in template
        replacePlaceholders($sheet, [
            '{{SERIAL_NO}}' => $final_serial_number,
            '{{FUND_CLUSTER}}' => $selected_cluster ?: 'All',
            '{{DATE}}' => $display_date,
            '{{PREPARED_BY}}' => $current_user['name'] ?? '',
            '{{PREPARED_BY_POSITION}}' => $current_user['position'] ?? '',
            '{{APPROVED_BY}}' => $signatory_name,
            '{{APPROVED_BY_POSITION}}' => $signatory_position,
            '{{AGENCY}}' => $signatory_agency,
        ]);
        
        // Insert data into template
        insertDataIntoSheet($sheet, $issued_items);
        
    } catch (Exception $e) {
        // If template fails, create from scratch
        $spreadsheet = createRSMISpreadsheetFromScratch();
        $sheet = $spreadsheet->getActiveSheet();
        $usingTemplate = false;
    }
} else {
    // Create from scratch if no template
    $spreadsheet = createRSMISpreadsheetFromScratch();
    $sheet = $spreadsheet->getActiveSheet();
    $usingTemplate = false;
}

// Function to replace placeholders
function replacePlaceholders($sheet, $placeholders) {
    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $cellValue = $cell->getValue();
            if (is_string($cellValue)) {
                foreach ($placeholders as $placeholder => $value) {
                    if (strpos($cellValue, $placeholder) !== false) {
                        $cell->setValue(str_replace($placeholder, $value, $cellValue));
                    }
                }
            }
        }
    }
}

// Function to insert data into sheet
function insertDataIntoSheet($sheet, $issued_items) {
    // Find items start row
    $itemsStartRow = findMarkerRow($sheet, '{{ITEMS_START}}');
    if (!$itemsStartRow) $itemsStartRow = 12;
    
    $currentRow = $itemsStartRow;
    $totalAmount = 0;
    $recap = [];

    foreach ($issued_items as $item) {
        $unit_display = getUnitDisplay($item);
        
        $sheet->setCellValue('A' . $currentRow, "RIS-" . $item['ris_no']);
        $sheet->setCellValue('B' . $currentRow, '');
        $sheet->setCellValue('C' . $currentRow, $item['stock_card']);
        $sheet->setCellValue('D' . $currentRow, $item['item_name']);
        $sheet->setCellValue('E' . $currentRow, $unit_display);
        $sheet->setCellValue('F' . $currentRow, (float)$item['qty_issued']);
        
        // FIXED: Set numeric values and apply number formatting
        $sheet->setCellValue('G' . $currentRow, (float)$item['unit_cost']);
        $sheet->setCellValue('H' . $currentRow, (float)$item['amount']);
        
        // FIXED: Apply number formatting to show 2 decimal places
        $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $totalAmount += (float)$item['amount'];
        
        // Build recap data
        $stock = $item['stock_card'];
        if (!isset($recap[$stock])) {
            $recap[$stock] = [
                'qty' => 0,
                'unit_cost' => $item['unit_cost'],
                'total_cost' => 0
            ];
        }
        $recap[$stock]['qty'] += $item['qty_issued'];
        $recap[$stock]['total_cost'] += $item['amount'];
        
        $currentRow++;
    }

    // Add empty rows to reach 10 total items
    $num_items = count($issued_items);
    $empty_rows = max(0, 10 - $num_items);
    for($i = 0; $i < $empty_rows; $i++) {
        $sheet->setCellValue('A' . $currentRow, '');
        $sheet->setCellValue('B' . $currentRow, '');
        $sheet->setCellValue('C' . $currentRow, '');
        $sheet->setCellValue('D' . $currentRow, '');
        $sheet->setCellValue('E' . $currentRow, '');
        $sheet->setCellValue('F' . $currentRow, '');
        $sheet->setCellValue('G' . $currentRow, '');
        $sheet->setCellValue('H' . $currentRow, '');
        $currentRow++;
    }

    // Add recapitulation section starting at row 36
    $recapStartRow = 36;
    
    $recapStartRow++;
    
    // Add recapitulation data
    foreach ($recap as $stock_no => $data) {
        $sheet->setCellValue('B' . $recapStartRow, '0' . $stock_no);
        $sheet->setCellValue('C' . $recapStartRow, (float)$data['qty']);
        
        // FIXED: Set numeric values and apply number formatting for recap
        $sheet->setCellValue('F' . $recapStartRow, (float)$data['unit_cost']);
        $sheet->setCellValue('G' . $recapStartRow, (float)$data['total_cost']);
        
        // FIXED: Apply number formatting to recap cells
        $sheet->getStyle('F' . $recapStartRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G' . $recapStartRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $sheet->setCellValue('H' . $recapStartRow, '');
        $recapStartRow++;
    }

    // Add empty rows after recap if needed
    $recap_rows_used = count($recap) + 2; // +2 for header rows
    $remaining_empty_rows = max(0, 10 - $recap_rows_used);
    for($i = 0; $i < $remaining_empty_rows; $i++) {
        $sheet->setCellValue('B' . $recapStartRow, '');
        $sheet->setCellValue('C' . $recapStartRow, '');
        $sheet->setCellValue('F' . $recapStartRow, '');
        $sheet->setCellValue('G' . $recapStartRow, '');
        $sheet->setCellValue('H' . $recapStartRow, '');
        $recapStartRow++;
    }

    // Update total amount placeholder
    replacePlaceholders($sheet, ['{{TOTAL_AMOUNT}}' => number_format($totalAmount, 2)]);
    
    // Add signatories
    addSignatories($sheet);
}

// Function to create spreadsheet from scratch
function createRSMISpreadsheetFromScratch() {
    global $final_serial_number, $selected_cluster, $display_date, $current_user, $signatory_name, $signatory_position, $issued_items;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("School Inventory Management System")
        ->setLastModifiedBy("WBIMS")
        ->setTitle("RSMI Report")
        ->setSubject("Report of Supplies and Materials Issued")
        ->setDescription("RSMI report generated from WBIMS");

    // Add header information
    $sheet->setCellValue('A1', 'REPORT OF SUPPLIES AND MATERIALS ISSUED');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Entity and serial info
    $sheet->setCellValue('A3', 'Entity Name:');
    $sheet->setCellValue('B3', 'BSU - BOKOD CAMPUS');
    $sheet->setCellValue('G3', 'Serial No:');
    $sheet->setCellValue('H3', $final_serial_number);

    // Fund cluster and date
    $sheet->setCellValue('A4', 'Fund Cluster:');
    $sheet->setCellValue('B4', $selected_cluster ?: 'All');
    $sheet->setCellValue('G4', 'Date:');
    $sheet->setCellValue('H4', $display_date);

    // Section headers
    $sheet->setCellValue('A6', 'To be filled up by the Supply and/or Property Division/Unit');
    $sheet->mergeCells('A6:F6');
    $sheet->setCellValue('G6', 'To be filled up by the Accounting Division/Unit');
    $sheet->mergeCells('G6:H6');
    $sheet->getStyle('A6:H6')->getFont()->setItalic(true);
    $sheet->getStyle('A6:H6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Headers
    $headers = ['RIS No.', 'Resp Center Code', 'Stock No.', 'Item Description', 'Unit', 'Qty Issued', 'Unit Cost', 'Amount'];
    $col = 'A';
    $row = 7;
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    
    // Apply header style
    $sheet->getStyle('A7:H7')->getFont()->setBold(true);
    $sheet->getStyle('A7:H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Data rows
    $row = 8;
    $totalAmount = 0;
    $recap = [];
    
    foreach ($issued_items as $item) {
        $unit_display = getUnitDisplay($item);
        
        $sheet->setCellValue('A' . $row, $item['ris_no']);
        $sheet->setCellValue('B' . $row, '');
        $sheet->setCellValue('C' . $row, $item['stock_card']);
        $sheet->setCellValue('D' . $row, $item['item_name']);
        $sheet->setCellValue('E' . $row, $unit_display);
        $sheet->setCellValue('F' . $row, (float)$item['qty_issued']);
        
        // FIXED: Set numeric values and apply number formatting
        $sheet->setCellValue('G' . $row, (float)$item['unit_cost']);
        $sheet->setCellValue('H' . $row, (float)$item['amount']);
        
        // FIXED: Apply number formatting to show 2 decimal places
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $totalAmount += (float)$item['amount'];
        
        // Build recap data
        $stock = $item['stock_card'];
        if (!isset($recap[$stock])) {
            $recap[$stock] = [
                'qty' => 0,
                'unit_cost' => $item['unit_cost'],
                'total_cost' => 0
            ];
        }
        $recap[$stock]['qty'] += $item['qty_issued'];
        $recap[$stock]['total_cost'] += $item['amount'];
        
        $row++;
    }
    
    // Add empty rows to reach 10 total items
    $num_items = count($issued_items);
    $empty_rows = max(0, 10 - $num_items);
    for($i = 0; $i < $empty_rows; $i++) {
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, '');
        $sheet->setCellValue('C' . $row, '');
        $sheet->setCellValue('D' . $row, '');
        $sheet->setCellValue('E' . $row, '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, '');
        $sheet->setCellValue('H' . $row, '');
        $row++;
    }
    
    // Add borders to data
    $sheet->getStyle('A7:H' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Add recapitulation section starting at row 36
    $recapStartRow = 36;

    $recapStartRow++;
    
    // Add recapitulation data
    foreach ($recap as $stock_no => $data) {
        $sheet->setCellValue('B' . $recapStartRow, $stock_no);
        $sheet->setCellValue('C' . $recapStartRow, (float)$data['qty']);
        
        // FIXED: Set numeric values and apply number formatting for recap
        $sheet->setCellValue('F' . $recapStartRow, (float)$data['unit_cost']);
        $sheet->setCellValue('G' . $recapStartRow, (float)$data['total_cost']);
        
        // FIXED: Apply number formatting to recap cells
        $sheet->getStyle('F' . $recapStartRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G' . $recapStartRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $sheet->setCellValue('H' . $recapStartRow, '');
        $recapStartRow++;
    }
    
    // Add empty rows after recap if needed
    $recap_rows_used = count($recap) + 2; // +2 for header rows
    $remaining_empty_rows = max(0, 10 - $recap_rows_used);
    for($i = 0; $i < $remaining_empty_rows; $i++) {
        $sheet->setCellValue('B' . $recapStartRow, '');
        $sheet->setCellValue('C' . $recapStartRow, '');
        $sheet->setCellValue('F' . $recapStartRow, '');
        $sheet->setCellValue('G' . $recapStartRow, '');
        $sheet->setCellValue('H' . $recapStartRow, '');
        $recapStartRow++;
    }
    
    // Add borders to recapitulation
    $sheet->getStyle('B36:H' . ($recapStartRow-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Add signatories
    addSignatories($sheet);
    
    // Auto-size columns
    foreach (range('A', 'H') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    return $spreadsheet;
}

// Function to add signatories
function addSignatories($sheet) {
    global $current_user, $signatory_name, $signatory_position, $display_date;
   
    // Add signatories at row 52
    $signatoryRow = 52;
    
    // Prepared By (left side - Supply/Property Division)
    $sheet->setCellValue('C' . $signatoryRow, $current_user['name'] ?? '');
    $sheet->getStyle('C' . $signatoryRow)->getFont()->setBold(true);
    $sheet->getStyle('C' . $signatoryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Posted By (right side - Accounting Division)
    $sheet->setCellValue('F' . $signatoryRow, $signatory_name);
    $sheet->getStyle('F' . $signatoryRow)->getFont()->setBold(true);
    $sheet->getStyle('F' . $signatoryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    
    // Add positions below signatures (row 53)
    $positionRow = 53;
    $sheet->setCellValue('C' . $positionRow, $current_user['position'] ?? '');
    $sheet->setCellValue('F' . $positionRow, $signatory_position);
    
    // Style positions
    $sheet->getStyle('C' . $positionRow . ':F' . $positionRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C' . $positionRow . ':F' . $positionRow)->getFont()->setSize(10);
}

// Helper function to find marker row
function findMarkerRow($sheet, $marker) {
    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        foreach ($cellIterator as $cell) {
            $cellValue = $cell->getValue();
            if (is_string($cellValue) && strpos($cellValue, $marker) !== false) {
                $cell->setValue('');
                return $cell->getRow();
            }
        }
    }
    return null;
}

// Helper function to get unit display
function getUnitDisplay($item) {
    if (!empty($item['unit_id']) && !empty($item['unit_symbol'])) {
        return $item['unit_symbol'];
    } elseif (!empty($item['base_unit_id']) && !empty($item['base_unit_symbol'])) {
        return $item['base_unit_symbol'];
    }
    return 'N/A';
}

// Set output headers and send file
$filename = 'RSMI_Report_' . date('Y-m-d_His') . '.xlsx';

// Clear any previous output
if (ob_get_length()) ob_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    die('Error generating Excel file: ' . $e->getMessage());
}
?>