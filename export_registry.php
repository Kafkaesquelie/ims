<?php
require 'vendor/autoload.php'; // PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function exportToExcel($tableData, $fundCluster, $searchTerm) {
    // Load the template Excel file
    $templateFile = 'templates/SMPI_Template.xlsx';  // Path to your template file
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templateFile);
    $sheet = $spreadsheet->getActiveSheet();

    // Replace placeholders with dynamic data
    $sheet->setCellValue('B1', 'Benguet State University - Bokod Campus');  // Entity Name
    $sheet->setCellValue('B2', $fundCluster ?: '__________');  // Fund Cluster
    $sheet->setCellValue('B3', '________________');  // Sheet No. Placeholder

    // Add dynamic data (Table Data)
    $rowIndex = 5;  // Starting row for data insertion
    foreach ($tableData as $row) {
        $sheet->setCellValue('A' . $rowIndex, date('m/d/Y', strtotime($row['date'])));  // Date
        $sheet->setCellValue('B' . $rowIndex, $row['ICS_No'] ?: $row['PAR_No']);  // ICS/RRSP No
        $sheet->setCellValue('C' . $rowIndex, $row['inv_item_no']);  // Semi-exp. Prop. No
        $sheet->setCellValue('D' . $rowIndex, $row['item_description']);  // Item Description
        $sheet->setCellValue('E' . $rowIndex, $row['estimated_use']);  // Estimated Useful Life
        $sheet->setCellValue('F' . $rowIndex, $row['qty_issued']);  // Qty Issued
        $sheet->setCellValue('G' . $rowIndex, $row['officer']);  // Officer
        $sheet->setCellValue('H' . $rowIndex, $row['qty_returned']);  // Qty Returned
        $sheet->setCellValue('I' . $rowIndex, $row['officer_returned']);  // Officer Returned
        $sheet->setCellValue('J' . $rowIndex, $row['qty_re_issued']);  // Qty Re-issued
        $sheet->setCellValue('K' . $rowIndex, $row['officer_re_issued']);  // Officer Re-issued
        $sheet->setCellValue('L' . $rowIndex, '');  // Disposed (empty for now)
        $sheet->setCellValue('M' . $rowIndex, $row['balance_qty']);  // Balance
        $sheet->setCellValue('N' . $rowIndex, $row['unit_cost']);  // Amount (Unit Cost)
        $sheet->setCellValue('O' . $rowIndex, $row['remarks']);  // Remarks

        $rowIndex++;  // Move to the next row
    }

    // Save the file and output for download
    $writer = new Xlsx($spreadsheet);
    $fileName = "SMPI_Card_" . date('YmdHis') . ".xlsx";

    // Output to browser (download prompt)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');  // Output the Excel file directly to the browser
    exit();
}

// Trigger the export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $tableData = json_decode($_POST['tableData'], true);
    $fundCluster = $_POST['fund_cluster'];
    $searchTerm = $_POST['search'];  // You can use this for additional filtering if needed

    exportToExcel($tableData, $fundCluster, $searchTerm);
}
?>
