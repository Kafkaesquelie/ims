<?php
require_once('includes/load.php');
page_require_level(1);

$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;
$signatory_name = $_GET['signatory_name'] ?? '';
$signatory_position = $_GET['signatory_position'] ?? '';
$signatory_agency = $_GET['signatory_agency'] ?? '';

$current_user = current_user();

// Filters
$where_date = $report_date ? " AND DATE(r.date) = '" . $db->escape($report_date) . "'" : '';
$where_cluster = ($selected_cluster && $selected_cluster !== 'all') ? " AND i.fund_cluster = '" . $db->escape($selected_cluster) . "'" : '';

// Fetch request items
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
        i.unit_cost,
        (ri.qty * i.unit_cost) AS amount,
        i.fund_cluster
    FROM request_items ri
    JOIN requests r ON ri.req_id = r.id
    JOIN items i ON ri.item_id = i.id
    LEFT JOIN units u ON i.unit_id = u.id
    LEFT JOIN base_units bu ON i.base_unit_id = bu.id
    WHERE r.status NOT IN ('Pending', 'Approved')
      AND i.stock_card IS NOT NULL
      AND i.stock_card != ''
      $where_date
      $where_cluster
    ORDER BY r.date ASC
");

// Define the missing variables
$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : date("F d, Y");
$year = $report_date ? date("Y", strtotime($report_date)) : date("Y");
$month = $report_date ? date("m", strtotime($report_date)) : date("m");
$serial_no_prefix = $year . '-' . $month . '-';
$final_serial_number = $serial_no_prefix . '0000';

// Set headers for Word document
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=RSMI_Report_" . date('Y-m-d') . ".doc");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="utf-8">
<title>Report of Supplies and Materials Issued</title>
<!--[if gte mso 9]>
<xml>
<w:WordDocument>
<w:View>Print</w:View>
<w:Zoom>100</w:Zoom>
<w:DoNotOptimizeForBrowser/>
</w:WordDocument>
</xml>
<![endif]-->
<style>
/* Word-compatible CSS */
@page {
    size: 8.5in 11in;
    margin: 1in;
}
body {
    font-family: "Times New Roman", serif;
    font-size: 12pt;
    line-height: 1.2;
    margin: 0;
    padding: 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    mso-border-alt: solid windowtext .5pt;
}
td, th {
    border: 1pt solid windowtext;
    padding: 5pt;
    mso-border-alt: solid windowtext .5pt;
}
th {
    background: #CCCCCC;
    font-weight: bold;
}
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-left { text-align: left; }
.header-table td {
    border: none;
    mso-border-alt: none;
}
.signature-line {
    border-bottom: 1pt solid windowtext;
    margin: 10pt 0 5pt 0;
}
.no-items {
    text-align: center;
    font-style: italic;
    color: #666;
}
</style>
</head>
<body>

<div style="text-align: center; margin-bottom: 20pt;">
    <h2 style="margin: 0; font-weight: bold;">REPORT OF SUPPLIES AND MATERIALS ISSUED</h2>
</div>

<table class="header-table" style="border: none; margin-bottom: 20pt;">
    <tr>
        <td style="width: 50%; border: none;">
            <strong>Entity Name:</strong> 
            <span style="border-bottom: 1pt solid windowtext; display: inline-block; width: 60%; text-align: center; margin-left: 10pt;">
                BSU - BOKOD CAMPUS
            </span>
        </td>
        <td style="width: 50%; border: none; text-align: right;">
            <strong>Serial No:</strong> 
            <span style="border-bottom: 1pt solid windowtext; display: inline-block; width: 60%; text-align: center; margin-left: 10pt;">
                <?= htmlspecialchars($final_serial_number); ?>
            </span>
        </td>
    </tr>
    <tr>
        <td style="border: none;">
            <strong>Fund Cluster:</strong> 
            <span style="border-bottom: 1pt solid windowtext; display: inline-block; width: 60%; text-align: center; margin-left: 10pt;">
                <?= $selected_cluster ?? 'All'; ?>
            </span>
        </td>
        <td style="border: none; text-align: right;">
            <strong>Date:</strong> 
            <span style="border-bottom: 1pt solid windowtext; display: inline-block; width: 60%; text-align: center; margin-left: 10pt;">
                <?= $display_date; ?>
            </span>
        </td>
    </tr>
</table>

<!-- Items Table -->
<table>
    <thead>
        <tr>
            <td colspan="8" class="text-center" style="background: #F2F2F2; font-style: italic;">
                To be filled up by the Supply and/or Property Division/Unit
            </td>
        </tr>
        <tr>
            <th>RIS No.</th>
            <th>Responsibility Center Code</th>
            <th>Stock No.</th>
            <th>Item</th>
            <th>Unit</th>
            <th>Qty Issued</th>
            <th>Unit Cost</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($issued_items)): ?>
            <?php foreach($issued_items as $item): 
                $unit_display = 'N/A';
                if (!empty($item['unit_id']) && !empty($item['unit_symbol'])) {
                    $unit_display = $item['unit_symbol'];
                } elseif (!empty($item['base_unit_id']) && !empty($item['base_unit_symbol'])) {
                    $unit_display = $item['base_unit_symbol'];
                }
                ?>
                <tr>
                    <td class="text-center"><?= "RIS-" . $item['ris_no']; ?></td>
                    <td class="text-center"></td>
                    <td class="text-center"><?= $item['stock_card']; ?></td>
                    <td class="text-left"><?= $item['item_name']; ?></td>
                    <td class="text-center"><?= $unit_display; ?></td>
                    <td class="text-center"><?= $item['qty_issued']; ?></td>
                    <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                    <td class="text-right">₱<?= number_format($item['amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center no-items">
                    No items found for the selected filters.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Signature Section -->
<table style="border: none; margin-top: 30pt;">
    <tr>
        <td style="border: none; width: 50%; vertical-align: top;">
            <div style="margin-bottom: 10pt;">
                I hereby certify to the correctness of the above information.
            </div>
            <div class="signature-line" style="width: 200pt;"></div>
            <div class="text-center" style="font-size: 10pt;">
                <?= $current_user['name'] ?? 'Name'; ?><br>
                <?= $current_user['position'] ?? 'Position'; ?>
            </div>
        </td>
        <td style="border: none; width: 50%; vertical-align: top;">
            <div style="margin-bottom: 10pt;">
                Posted by:
            </div>
            <div class="signature-line" style="width: 200pt;"></div>
            <div class="text-center" style="font-size: 10pt;">
                <?= htmlspecialchars($signatory_name); ?><br>
                <?= htmlspecialchars($signatory_position); ?>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
<?php exit; ?>