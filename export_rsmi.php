<?php
require_once 'vendor/autoload.php'; // Load PHPWord via Composer (or include PHPWord manually)
use PhpOffice\PhpWord\TemplateProcessor;

$page_title = 'Export Report of Supplies and Materials Issued';
require_once('includes/load.php');
page_require_level(1); // Only admins

// Get the required data (this can be fetched as per your query)
$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;

// Construct the SQL query to fetch required data
$where_date = '';
if ($report_date) {
    $where_date = " AND DATE(r.date) = '" . $db->escape($report_date) . "'";
}

$where_cluster = '';
if ($selected_cluster && $selected_cluster !== 'all') {
    $where_cluster = " AND i.fund_cluster = '" . $db->escape($selected_cluster) . "'";
}

$issued_items = find_by_sql("
    SELECT 
        r.ris_no,
        i.stock_card,
        i.name AS item_name,
        u.symbol AS unit_symbol,
        i.unit_id,
        ri.qty AS qty_issued,
        i.unit_cost,
        (ri.qty * i.unit_cost) AS amount
    FROM request_items ri
    JOIN requests r ON ri.req_id = r.id
    JOIN items i ON ri.item_id = i.id
    LEFT JOIN units u ON i.unit_id = u.id
    WHERE r.status NOT IN ('Pending', 'Approved')
      AND i.stock_card IS NOT NULL
      AND i.stock_card != ''
      $where_date
      $where_cluster
    ORDER BY r.date ASC
");

// Prepare data for placeholders
$data = [
    'entity_name' => 'BSU - BOKOD CAMPUS',
    'serial_no' => '2023-05-0001', // You can generate this dynamically
    'fund_cluster' => $selected_cluster ?: 'GAA / IGI',
    'report_date' => date("F d, Y", strtotime($report_date)),
    'items' => []
];

// Fill items data
foreach ($issued_items as $item) {
    $data['items'][] = [
        'ris_no' => $item['ris_no'],
        'stock_card' => $item['stock_card'],
        'item_name' => $item['item_name'],
        'qty_issued' => $item['qty_issued'],
        'unit' => $item['unit_symbol'],
        'unit_cost' => number_format($item['unit_cost'], 2),
        'amount' => number_format($item['amount'], 2)
    ];
}

// Path to the Word template
$templatePath = 'templates/RSMI_Template.docx';

// Create a TemplateProcessor instance to load the template
$templateProcessor = new TemplateProcessor($templatePath);

// Replace placeholders in the template with actual data
$templateProcessor->setValue('entity_name', $data['entity_name']);
$templateProcessor->setValue('serial_no', $data['serial_no']);
$templateProcessor->setValue('fund_cluster', $data['fund_cluster']);
$templateProcessor->setValue('report_date', $data['report_date']);

// Loop through items and add to the Word document
$counter = 1;
foreach ($data['items'] as $item) {
    $templateProcessor->setValue('ris_no#' . $counter, $item['ris_no']);
    $templateProcessor->setValue('stock_card#' . $counter, $item['stock_card']);
    $templateProcessor->setValue('item_name#' . $counter, $item['item_name']);
    $templateProcessor->setValue('qty_issued#' . $counter, $item['qty_issued']);
    $templateProcessor->setValue('unit#' . $counter, $item['unit']);
    $templateProcessor->setValue('unit_cost#' . $counter, $item['unit_cost']);
    $templateProcessor->setValue('amount#' . $counter, $item['amount']);
    $counter++;
}

// Save the generated document to a file
$outputFile = 'exports/rsmi_report_' . date('YmdHis') . '.docx';
$templateProcessor->saveAs($outputFile);

// Trigger file download
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . basename($outputFile));
header("Content-Length: " . filesize($outputFile));
readfile($outputFile);
exit;
?>
