<?php
require_once 'includes/load.php';  // Ensure you have the necessary includes
require_once 'vendor/autoload.php';  // Make sure PhpSpreadsheet is installed via Composer

// Get the RIS number from URL parameter
$ris_no = isset($_GET['ris_no']) ? trim($db->escape($_GET['ris_no'])) : null;

if (!$ris_no) {
    $session->msg("d", "No RIS number provided.");
    redirect('logs.php');
}

// 🟩 Fetch request info from the 'requests' table
$request = find_by_id('requests', $ris_no);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('logs.php');
}

// 🟩 Fetch request items from 'request_items' table related to the given RIS number
$sql = "
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
";
$items = find_by_sql($sql);

if (empty($items)) {
    $session->msg("d", "No items found for RIS: {$ris_no}");
    redirect('logs.php');
}

// 🟩 Load the Excel template file
$templatePath = 'templates/RIS_Template.xlsx';  // Path to your template
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
$sheet = $spreadsheet->getActiveSheet();

// 🟩 Fill in the header data
$sheet->setCellValue('F9',  $request['ris_no']); // RIS Number
$sheet->setCellValue('C9',  $request['fund_cluster']); // Fund Cluster

// 🟩 Fill in the item details
$row = 12;  // Start filling from row 11
foreach ($items as $item) {
    $sheet->setCellValue('A' . $row, $item['qty']); // Qty
    $sheet->setCellValue('B' . $row, $item['unit']); // Unit
    $sheet->setCellValue('C' . $row, $item['item_name']); // Item Description
    $sheet->setCellValue('D' . $row, $item['stock_card']); // Stock Card
    $sheet->setCellValue('E' . $row, !empty($item['remarks']) ? $item['remarks'] : 'N/A'); // Remarks
    $row++;
}

// 🟩 Add Signatories (Use logged-in user for "Issued by")
$current_user = current_user();
$sheet->setCellValue('A30', strtoupper($request['requested_by'])); // Requested by (Name)
$sheet->setCellValue('A31', 'Signature over Printed Name');
$sheet->setCellValue('A32', 'Position');
$sheet->setCellValue('A33', date('M d, Y', strtotime($request['transaction_date']))); // Date

$sheet->setCellValue('E30', strtoupper($current_user['name'])); // Issued by (Current User)
$sheet->setCellValue('E31', 'Signature over Printed Name');
$sheet->setCellValue('E32', $current_user['position']); // Position
$sheet->setCellValue('E33', date('M d, Y', strtotime($request['transaction_date']))); // Date

// 🟩 Export the spreadsheet to Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="RIS_Export_' . $request['ris_no'] . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit();
?>
