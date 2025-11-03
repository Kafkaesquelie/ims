<?php
require_once('includes/load.php');
page_require_level(1);

// =====================
// Get parameters for filtering
// =====================
$item_id = $_GET['item_id'] ?? null;
$fund_cluster_filter = $_GET['fund_cluster'] ?? '';
$value_filter = $_GET['value_filter'] ?? '';

// =====================
// Fetch specific item details
// =====================
$item = null;
if (!empty($item_id)) {
    $item_sql = "
        SELECT 
            s.id,
            s.item,
            s.item_description,
            s.inv_item_no,
            s.unit_cost,
            s.qty_left AS balance_qty,
            s.fund_cluster,
            s.semicategory_id AS unit_measurement
        FROM semi_exp_prop s
        WHERE s.id = '{$db->escape($item_id)}'
    ";
    $items = find_by_sql($item_sql);
    $item = !empty($items) ? $items[0] : null;
}

if (!$item) {
    die("Item not found.");
}

// =====================
// Fetch transactions for this specific item
// =====================
$smpi_transactions = [];
if (!empty($item)) {
    $transactions_sql = "
        SELECT 
            t.id AS transaction_id,
            t.transaction_type,
            t.transaction_date,
            t.quantity AS issued_qty,
            t.remarks,
            s.unit_cost,
            (t.quantity * s.unit_cost) AS total_cost,
            t.PAR_No,
            t.ICS_No,
            ri.RRSP_No,
            CONCAT(e.first_name, ' ', e.last_name) AS officer,
            e.position,
            e.office AS department,
            s.item AS item_name,
            s.item_description AS item_description,
            s.inv_item_no,
            s.fund_cluster,
            s.unit_cost AS item_unit_cost,
            s.qty_left AS current_balance,
            ri.return_date
        FROM transactions t
        LEFT JOIN semi_exp_prop s ON t.item_id = s.id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = e.id
        LEFT JOIN return_items ri ON t.id = ri.transaction_id
        WHERE t.item_id = '{$db->escape($item_id)}'
        ORDER BY t.transaction_date ASC
    ";

    $transactions = find_by_sql($transactions_sql);

    if (!empty($transactions)) {
        foreach ($transactions as $tx) {
            $smpi_transactions[] = $tx;
        }
    }
}

// =====================
// Excel Export Using Template
// =====================

// Start output buffering
ob_start();

try {
    // Check if PhpSpreadsheet is available
    $phpspreadsheetAvailable = false;
    
    // Try multiple ways to load PhpSpreadsheet
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        $phpspreadsheetAvailable = true;
    } else {
        // Try to include manually
        $possiblePaths = [
            __DIR__ . '/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    $phpspreadsheetAvailable = true;
                    break;
                }
            }
        }
    }
    
    if (!$phpspreadsheetAvailable) {
        throw new Exception("PhpSpreadsheet library not found. Please install via: composer require phpoffice/phpspreadsheet");
    }

    // Now use the classes
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    
    // Define template paths
    $templatePaths = [
        __DIR__ . '/templates/Property_Card_Template.xlsx',
        __DIR__ . '/Property_Card_Template.xlsx',
        'templates/Property_Card_Template.xlsx',
        'Property_Card_Template.xlsx'
    ];
    
    $templateFound = false;
    $actualTemplatePath = '';
    
    foreach ($templatePaths as $path) {
        if (file_exists($path)) {
            $templateFound = true;
            $actualTemplatePath = $path;
            break;
        }
    }

    if ($templateFound) {
        // Load Template
        $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($actualTemplatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Insert Item Information into defined placeholders
        $sheet->setCellValue('K3', $item['fund_cluster'] ?? 'N/A');
        $sheet->setCellValue('C4', strtoupper($item['item'] ?? 'N/A'));
        // $sheet->setCellValue('C7', $item['item_description'] ?? 'N/A');
        // $sheet->setCellValue('C8', $item['inv_item_no'] ?? 'N/A');
        // $sheet->setCellValue('I8', number_format($item['unit_cost'], 2));

        // Starting row number where table entries begin in the template
        $startRow = 8;
        $currentRow = $startRow;

        $running_balance = $item['balance_qty'];
        $running_amount = $running_balance * $item['unit_cost'];

        foreach ($smpi_transactions as $tx) {
            if ($tx['transaction_type'] === 'Issue' || $tx['transaction_type'] === 'Transfer') {
                $running_balance -= $tx['issued_qty'];
            } elseif ($tx['transaction_type'] === 'Receipt') {
                $running_balance += $tx['issued_qty'];
            }

            $running_amount = $running_balance * $item['unit_cost'];

            $sheet->setCellValue("B$currentRow", !empty($tx['transaction_date']) ? date('m/d/Y', strtotime($tx['transaction_date'])) : '');
            $sheet->setCellValue("C$currentRow", $tx['ICS_No'] ?? $tx['PAR_No'] ?? $tx['RRSP_No'] ?? '');
            $sheet->setCellValue("D$currentRow", $tx['transaction_type'] === 'Receipt' ? $tx['issued_qty'] : '');
            $sheet->setCellValue("E$currentRow", $tx['transaction_type'] === 'Receipt' ? number_format($tx['unit_cost'], 2) : '');
            $sheet->setCellValue("F$currentRow", $tx['transaction_type'] === 'Receipt' ? number_format($tx['total_cost'], 2) : '');
            $sheet->setCellValue("G$currentRow", $item['inv_item_no'] ?? '');
            $sheet->setCellValue("H$currentRow", ($tx['transaction_type'] === 'Issue' || $tx['transaction_type'] === 'Transfer') ? $tx['issued_qty'] : '');
            $sheet->setCellValue("I$currentRow", $tx['officer'] ?? ($tx['department'] ?? ''));
            $sheet->setCellValue("J$currentRow", $running_balance);
            $sheet->setCellValue("K$currentRow", number_format($running_amount, 2));
            $sheet->setCellValue("L$currentRow", $tx['remarks']);

            $currentRow++;
        }

        // Apply number formatting to amount columns
        if ($currentRow > $startRow) {
            $sheet->getStyle("D$startRow:D" . ($currentRow - 1))
                 ->getNumberFormat()
                 ->setFormatCode('#,##0.00');
            $sheet->getStyle("E$startRow:E" . ($currentRow - 1))
                 ->getNumberFormat()
                 ->setFormatCode('#,##0.00');
            $sheet->getStyle("J$startRow:J" . ($currentRow - 1))
                 ->getNumberFormat()
                 ->setFormatCode('#,##0.00');
        }

    } else {
        // Fallback: Create spreadsheet from scratch if template not found
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("School Inventory Management System")
            ->setTitle("Property Card for Semi-Expendable Property")
            ->setSubject("SMPI Card Export");
        
        // Create header
        $sheet->setCellValue('A1', 'PROPERTY CARD FOR SEMI-EXPENDABLE PROPERTY');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Item Details
        $sheet->setCellValue('A3', 'Entity Name:');
        $sheet->setCellValue('B3', 'BENGUET STATE UNIVERSITY - BOKOD CAMPUS');
        $sheet->setCellValue('H3', 'Fund Cluster:');
        $sheet->setCellValue('I3', $item['fund_cluster'] ?? 'N/A');
        
        $sheet->setCellValue('A4', 'Item Description:');
        $sheet->setCellValue('B4', strtoupper($item['item'] ?? 'N/A'));
        $sheet->mergeCells('B4:K4');
        
        $sheet->setCellValue('A5', 'Description:');
        $sheet->setCellValue('B5', $item['item_description'] ?? 'N/A');
        $sheet->mergeCells('B5:K5');
        
        $sheet->setCellValue('A6', 'Inventory Item No:');
        $sheet->setCellValue('B6', $item['inv_item_no'] ?? 'N/A');
        $sheet->setCellValue('H6', 'Unit Cost:');
        $sheet->setCellValue('I6', '₱' . number_format($item['unit_cost'], 2));
        
        // Table Headers
        $headers = ['Date', 'Reference', 'Qty', 'Unit Cost', 'Total Cost', 'Item No.', 'Qty.', 'Office/officer', 'Balance', 'Amount', 'Remarks'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '8', $header);
            $col++;
        }
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8E8E8']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
            ],
            'alignment' => [
                'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ];
        $sheet->getStyle('A8:K8')->applyFromArray($headerStyle);
        
        // Populate data
        $currentRow = 9;
        $running_balance = $item['balance_qty'];
        $running_amount = $item['balance_qty'] * $item['unit_cost'];
        
        if (!empty($smpi_transactions)) {
            foreach ($smpi_transactions as $tx) {
                if ($tx['transaction_type'] === 'Issue' || $tx['transaction_type'] === 'Transfer') {
                    $running_balance -= $tx['issued_qty'];
                } elseif ($tx['transaction_type'] === 'Receipt') {
                    $running_balance += $tx['issued_qty'];
                }
                
                $running_amount = $running_balance * $item['unit_cost'];
                
                $sheet->setCellValue('A' . $currentRow, !empty($tx['transaction_date']) ? date('m/d/Y', strtotime($tx['transaction_date'])) : '');
                $sheet->setCellValue('B' . $currentRow, $tx['ICS_No'] ?? $tx['PAR_No'] ?? $tx['RRSP_No'] ?? $tx['transaction_type'] ?? '');
                $sheet->setCellValue('C' . $currentRow, $tx['transaction_type'] === 'Receipt' ? $tx['issued_qty'] : '');
                $sheet->setCellValue('D' . $currentRow, $tx['transaction_type'] === 'Receipt' ? $tx['unit_cost'] : '');
                $sheet->setCellValue('E' . $currentRow, $tx['transaction_type'] === 'Receipt' ? $tx['total_cost'] : '');
                $sheet->setCellValue('F' . $currentRow, $item['inv_item_no'] ?? '');
                $sheet->setCellValue('G' . $currentRow, ($tx['transaction_type'] === 'Issue' || $tx['transaction_type'] === 'Transfer') ? $tx['issued_qty'] : '');
                $sheet->setCellValue('H' . $currentRow, $tx['officer'] ?? ($tx['department'] ?? ''));
                $sheet->setCellValue('I' . $currentRow, $running_balance);
                $sheet->setCellValue('J' . $currentRow, $running_amount);
                $sheet->setCellValue('K' . $currentRow, $tx['transaction_type'] ?? '');
                
                $currentRow++;
            }
        }
        
        // Apply number formatting
        if ($currentRow > 9) {
            $sheet->getStyle('D9:D' . ($currentRow - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E9:E' . ($currentRow - 1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J9:J' . ($currentRow - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        
        // Auto-size columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    // Export the filled template
    $fileName = 'SMPI_Card_' . ($item['inv_item_no'] ?? 'Property') . '_' . date('Y-m-d') . '.xlsx';

    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save("php://output");
    exit;
    
} catch (Exception $e) {
    // Clean any output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Fallback to HTML export
    exportToHTMLFallback($item, $smpi_transactions);
}

// HTML Export Fallback Function
function exportToHTMLFallback($item, $smpi_transactions) {
    $fileName = 'SMPI_Card_' . ($item['inv_item_no'] ?? 'Property') . '_' . date('Y-m-d') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    $excel_content = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>";
    $excel_content .= "<head><meta charset='UTF-8'></head><body>";
    
    $excel_content .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    
    // Header
    $excel_content .= "<tr><td colspan='11' style='text-align: center; font-size: 16px; font-weight: bold; background-color: #f2f2f2;'>PROPERTY CARD FOR SEMI-EXPENDABLE PROPERTY</td></tr>";
    
    // Item Details
    $excel_content .= "<tr><td colspan='11' style='background-color: #e6e6e6; font-weight: bold;'>ITEM DETAILS</td></tr>";
    $excel_content .= "<tr><td colspan='2'><strong>Entity Name:</strong></td><td colspan='4'>BENGUET STATE UNIVERSITY - BOKOD CAMPUS</td><td colspan='2'><strong>Fund Cluster:</strong></td><td colspan='3'>" . ($item['fund_cluster'] ?? 'N/A') . "</td></tr>";
    $excel_content .= "<tr><td colspan='2'><strong>Item Description:</strong></td><td colspan='9'>" . strtoupper($item['item'] ?? 'N/A') . "</td></tr>";
    $excel_content .= "<tr><td colspan='2'><strong>Description:</strong></td><td colspan='9'>" . ($item['item_description'] ?? 'N/A') . "</td></tr>";
    $excel_content .= "<tr><td colspan='2'><strong>Inventory Item No:</strong></td><td colspan='4'>" . ($item['inv_item_no'] ?? 'N/A') . "</td><td colspan='2'><strong>Unit Cost:</strong></td><td colspan='3'>₱" . number_format($item['unit_cost'], 2) . "</td></tr>";
    
    // Empty row
    $excel_content .= "<tr><td colspan='11'></td></tr>";
    
    // Table Headers
    $excel_content .= "<tr style='background-color: #d9d9d9; font-weight: bold; text-align: center;'>";
    $excel_content .= "<td rowspan='2'>Date</td>";
    $excel_content .= "<td rowspan='2'>Reference</td>";
    $excel_content .= "<td colspan='3'>RECEIPT</td>";
    $excel_content .= "<td colspan='3'>ISSUE/TRANSFER/DISPOSAL</td>";
    $excel_content .= "<td rowspan='2'>Balance</td>";
    $excel_content .= "<td rowspan='2'>Amount</td>";
    $excel_content .= "<td rowspan='2'>Remarks</td>";
    $excel_content .= "</tr>";
    $excel_content .= "<tr style='background-color: #d9d9d9; font-weight: bold; text-align: center;'>";
    $excel_content .= "<td>Qty</td>";
    $excel_content .= "<td>Unit Cost</td>";
    $excel_content .= "<td>Total Cost</td>";
    $excel_content .= "<td>Item No.</td>";
    $excel_content .= "<td>Qty.</td>";
    $excel_content .= "<td>Office/officer</td>";
    $excel_content .= "</tr>";
    
    // Transactions
    if (!empty($smpi_transactions)) {
        $running_balance = $item['balance_qty'];
        $running_amount = $item['balance_qty'] * $item['unit_cost'];
        
        foreach ($smpi_transactions as $row) {
            // Calculate running balance and amount
            if ($row['transaction_type'] === 'Issue' || $row['transaction_type'] === 'Transfer') {
                $running_balance -= $row['issued_qty'];
                $running_amount = $running_balance * $row['unit_cost'];
            } elseif ($row['transaction_type'] === 'Receipt') {
                $running_balance += $row['issued_qty'];
                $running_amount = $running_balance * $row['unit_cost'];
            }
            
            $excel_content .= "<tr>";
            $excel_content .= "<td>" . (!empty($row['transaction_date']) ? date('m/d/Y', strtotime($row['transaction_date'])) : '-') . "</td>";
            $excel_content .= "<td>" . ($row['ICS_No'] ?? $row['PAR_No'] ?? $row['RRSP_No'] ?? $row['transaction_type'] ?? '-') . "</td>";
            $excel_content .= "<td style='text-align: center;'>" . ($row['transaction_type'] === 'Receipt' ? $row['issued_qty'] : '') . "</td>";
            $excel_content .= "<td style='text-align: right;'>" . ($row['transaction_type'] === 'Receipt' ? number_format($row['unit_cost'], 2) : '') . "</td>";
            $excel_content .= "<td style='text-align: right;'>" . ($row['transaction_type'] === 'Receipt' ? number_format($row['total_cost'], 2) : '') . "</td>";
            $excel_content .= "<td>" . ($item['inv_item_no'] ?? 'N/A') . "</td>";
            $excel_content .= "<td style='text-align: center;'>" . (($row['transaction_type'] === 'Issue' || $row['transaction_type'] === 'Transfer') ? $row['issued_qty'] : '') . "</td>";
            $excel_content .= "<td>" . ($row['officer'] ?? ($row['department'] ?? '-')) . "</td>";
            $excel_content .= "<td style='text-align: center;'>" . number_format($running_balance, 0) . "</td>";
            $excel_content .= "<td style='text-align: right;'>" . number_format($running_amount, 2) . "</td>";
            $excel_content .= "<td>" . ($row['transaction_type'] ?? '') . (!empty($row['return_date']) ? ' (Returned: ' . date('m/d/Y', strtotime($row['return_date'])) . ')' : '') . "</td>";
            $excel_content .= "</tr>";
        }
    } else {
        $excel_content .= "<tr><td colspan='11' style='text-align: center;'>No transaction data found.</td></tr>";
    }
    
    $excel_content .= "</table>";
    $excel_content .= "</body></html>";
    
    echo $excel_content;
    exit;
}