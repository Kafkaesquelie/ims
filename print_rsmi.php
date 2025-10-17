<?php
$page_title = 'Printable Report of Supplies and Materials Issued (RSMI)';
require_once('includes/load.php');
page_require_level(1);

$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;
$signatory_name = $_GET['signatory_name'] ?? '';
$signatory_position = $_GET['signatory_position'] ?? '';
$signatory_agency = $_GET['signatory_agency'] ?? '';
$serial_number = $_GET['serial_number'] ?? '';

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
$current_user = current_user();

// Filters
$where_date = $report_date ? " AND DATE(r.date) = '" . $db->escape($report_date) . "'" : '';
$where_cluster = ($selected_cluster && $selected_cluster !== 'all') ? " AND i.fund_cluster = '" . $db->escape($selected_cluster) . "'" : '';

// Fetch approved request items
$issued_items = find_by_sql("
    SELECT 
        r.id,
        r.ris_no,
        i.stock_card,
        i.name AS item_name,
        un.symbol AS unit,
        ri.qty AS qty_issued,
        i.unit_cost,
        (ri.qty * i.unit_cost) AS amount,
        i.fund_cluster
    FROM request_items ri
    JOIN requests r ON ri.req_id = r.id
    JOIN units un ON i.unit_id = un.id
    JOIN items i ON ri.item_id = i.id
    WHERE r.status = 'Approved'
      AND i.stock_card IS NOT NULL
      AND i.stock_card != ''
      $where_date
      $where_cluster
    ORDER BY r.date ASC
");

$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : '';
$year = $report_date ? date("Y", strtotime($report_date)) : date("Y");
$month = $report_date ? date("m", strtotime($report_date)) : date("m");
$serial_no_prefix = $year . '-' . $month . '-';
$serial_no = $_GET['serial_no'] ?? ($serial_no_prefix . '0000'); // fallback if not passed

// Use provided serial number or default
$final_serial_number = $serial_number ?: $serial_no_prefix . '0000';


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $page_title ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { font-family: 'Times New Roman', serif; }
#print-area { width: 1000px; max-width: 100%; margin: 20px auto; }
table { width: 100%; border-collapse: collapse; font-size: 12px; }
.header-table td { border:none; text-align:left; padding:2px 0; }
th, td { border: 1px solid #000; padding: 4px; text-align: center; }
th { background: #f2f2f2; }
.print-btn { margin-bottom: 15px; background: #190361ff; color: white; border: none; padding: 8px 15px; cursor: pointer; font-size: 16px; float:right; }
.print-btn:hover { background: #220184ff; }
@media print {
    .print-btn { display: none; }
    @page { size: portrait; margin: 15mm; }
}
</style>
</head>
<body>


<div id="print-area">
    <button class="print-btn" onclick="window.print()">Print</button>

    <!-- Header and info table -->
    <div class="text-center mb-3">
        <h4><strong>REPORT OF SUPPLIES AND MATERIALS ISSUED</strong></h4>
    </div>

    <table class="header-table" style="width:100%; margin-bottom:20px; border-collapse:collapse;">
    <tr>
        <td style="width:50%;"><strong>Entity Name:</strong> <span class="text-center" style="display:inline-block; border-bottom:1px solid #000; width:50%;"><?= 'BSU - BOKOD CAMPUS'; ?></span></td>
        <td style="width:50%; text-align:right;"><strong>Serial No:</strong> <span class="text-center" style="display:inline-block; border-bottom:1px solid #000; width:50%;"  > <?= htmlspecialchars($final_serial_number); ?></span></td>
    </tr>
    <tr>
        <td><strong>Fund Cluster:</strong> <span class="text-center" style="display:inline-block; border-bottom:1px solid #000; width:50%;"><?= $selected_cluster ?? 'All'; ?></span></td>
        <td style="text-align:right;"><strong>Date:</strong> <span class="text-center" style="display:inline-block; border-bottom:1px solid #000; width:50%;"><?= $display_date; ?></span></td>
    </tr>
</table>


    <!-- Items table -->
    <table>
        <colgroup>
        <col style="width: 15%;">
        <col style="width: 8%;">  <!-- Responsibility Center Code narrower -->
        <col style="width: 10%;">
        <col style="width: 25%;">
        <col style="width: 7%;">
        <col style="width: 10%;">
        <col style="width: 10%;">
        <col style="width: 15%;">
    </colgroup>
        <thead>
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
            <?php
            $max_rows = 10;
            $num_items = count($issued_items);
            $empty_rows = max(0, $max_rows - $num_items);

            $recap = [];

            if(!empty($issued_items)):
                foreach($issued_items as $item): ?>
                    <tr>
                        <td><?=$item['ris_no']; ?></td>
                        <td></td>
                        <td><?= $item['stock_card']; ?></td>
                        <td><?= $item['item_name']; ?></td>
                        <td><?= $item['unit']; ?></td>
                        <td><?= $item['qty_issued']; ?></td>
                        <td>₱<?= number_format($item['unit_cost'],2); ?></td>
                        <td>₱<?= number_format($item['amount'],2); ?></td>
                    </tr>
                    <?php
                    // Recap
                    $stock = $item['stock_card'];
                    if (!isset($recap[$stock])) {
                        $recap[$stock] = ['qty'=>0,'unit_cost'=>$item['unit_cost'],'total_cost'=>0];
                    }
                    $recap[$stock]['qty'] += $item['qty_issued'];
                    $recap[$stock]['total_cost'] += $item['amount'];
                endforeach;
            endif;

 for($i = 0; $i < $empty_rows; $i++): ?>
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
<?php endfor; ?>

           <!-- 🔹 Recapitulation Rows (ONLY if recap has data) -->
<?php if(!empty($recap)): ?>
    <tr >
        <td colspan="1" class="text-center" style="font-weight:bold; background:#f2f2f2;">
        </td>
        <td colspan="2" class="text-center" style="font-weight:bold; background:#f2f2f2;">
            Recapitulation
        </td>
        <td colspan="1" class="text-center" style="font-weight:bold; background:#f2f2f2;"></td>
        <td colspan="1" class="text-center" style="font-weight:bold; background:#f2f2f2;"></td>
        <td colspan="3" class="text-center" style="font-weight:bold; background:#f2f2f2;">
            Recapitulation
        </td>
    </tr>

    <!-- First recap block -->
    <tr>
        <th colspan="1" class="text-center"></th>
        <th colspan="1" class="text-center">Stock No.</th>
        <th colspan="1" class="text-center">Quantity</th>
        <th colspan="1" class="text-center"></th>
        <th colspan="1" class="text-center"></th>
        <th colspan="1" class="text-center">Unit Cost</th>
        <th colspan="1" class="text-center">Total Cost</th>
        <th colspan="1" class="text-center">UACS Object Code</th>
    </tr>
    <?php foreach($recap as $stock_no => $data): ?>
        <tr>
            <td colspan="1" class="text-center"></td>
            <td colspan="1" class="text-center">0<?= $stock_no; ?></td>
            <td colspan="1" class="text-center"><?= $data['qty']; ?></td> 
            <td colspan="1" class="text-center"></td>
            <td colspan="1" class="text-center"></td>
            <td colspan="1" class="text-center">₱<?= number_format($data['unit_cost'], 2); ?></td> 
            <td colspan="1" class="text-center">₱<?= number_format($data['total_cost'], 2); ?></td> 
            <td colspan="1" class="text-center"></td> 
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
<?php for($i = 0; $i < $empty_rows; $i++): ?>
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
<?php endfor; ?>

<!-- 🔹 Signatories inside table -->
            <tr>
                <td colspan="4" style="padding:15px;">
                    <div class="mb-2 text-left">I hereby certify the correctness of the above information.</div>
                    <div style="border-bottom:1px solid #000; width:200px; margin:auto; text-align:center;">
                        <?= $current_user['name']; ?>
                    </div>
                    <div><?= $current_user['position'] ?? 'Position'; ?></div>
                </td>
                <td colspan="4" style="padding:15px;">
                    
                    <div  class="mb-2 text-left">Posted by: </div>
                            <div style="display: flex; justify-content:center">
                            <span class="text-center" style="display:inline-block; width:180px; border-bottom:1px solid #000;">
                                <?= htmlspecialchars($signatory_name); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
                            <span style="flex: 1; text-align: center;"><?= htmlspecialchars($signatory_position); ?></span>
                        </div>
                    <div>Date</div>
                </td>
            </tr>
        </tbody>
    </table>

 

</body>
</html>
