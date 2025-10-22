<?php
$page_title = 'Print SMPI Cards';
require_once('includes/load.php');
page_require_level(1);

// =====================
// Get filter parameters (same as main page)
// =====================
$stock_card_input = $_GET['stock_card'] ?? null;
$fund_cluster_filter = $_GET['fund_cluster'] ?? '';
$value_filter = $_GET['value_filter'] ?? '';

// Initialize conditions array
$where_conditions = ["1=1"];

// Apply filters
if (!empty($fund_cluster_filter)) {
  $where_conditions[] = "s.fund_cluster = '{$db->escape($fund_cluster_filter)}'";
}

if ($value_filter === 'high') {
  $where_conditions[] = "s.unit_cost >= 5000";
} elseif ($value_filter === 'low') {
  $where_conditions[] = "s.unit_cost < 5000";
}

// =====================
// Fetch all semi-expendable items (filtered)
// =====================
$where_sql = implode(' AND ', $where_conditions);
$sql_items = "
  SELECT 
      s.id,
      s.item,
      s.item_description,
      s.inv_item_no,
      s.unit_cost,
      s.qty_left AS balance_qty,
      s.fund_cluster
  FROM semi_exp_prop s
  WHERE {$where_sql}
";
$smpi_items = find_by_sql($sql_items);

// =====================
// Fetch transactions linked to those items
// =====================
$smpi_transactions = [];
if (!empty($smpi_items)) {
  foreach ($smpi_items as $item) {
    $item_id = (int)$item['id'];

    $transactions_sql = "
      SELECT 
          t.id AS transaction_id,
          t.transaction_type,
          t.transaction_date,
          t.quantity AS issued_qty,
          s.unit_cost,
          (t.quantity * s.unit_cost) AS total_cost,
          t.PAR_No,
          t.ICS_No,
          t.RRSP_No,
          CONCAT(e.first_name, ' ', e.last_name) AS officer,
          s.item AS item_name,
          s.item_description AS item_description,
          s.inv_item_no,
          s.fund_cluster,
          s.unit_cost AS item_unit_cost,
          s.qty_left AS current_balance
      FROM transactions t
      LEFT JOIN semi_exp_prop s ON t.item_id = s.id
      LEFT JOIN employees e ON t.employee_id = e.id
      WHERE t.item_id = '{$db->escape($item_id)}'
      ORDER BY t.transaction_date ASC
    ";

    $transactions = find_by_sql($transactions_sql);

    if (!empty($transactions)) {
      foreach ($transactions as $tx) {
        $smpi_transactions[$item_id][] = $tx;
      }
    } else {
      // No transaction record
      $smpi_transactions[$item_id][] = [
        'transaction_id' => null,
        'transaction_type' => 'None',
        'transaction_date' => null,
        'issued_qty' => 0,
        'unit_cost' => $item['unit_cost'],
        'total_cost' => 0,
        'PAR_No' => null,
        'ICS_No' => null,
        'RRSP_No' => null,
        'officer' => null,
        'item_name' => $item['item'],
        'item_description' => $item['item_description'],
        'inv_item_no' => $item['inv_item_no'],
        'fund_cluster' => $item['fund_cluster'],
        'item_unit_cost' => $item['unit_cost'],
        'current_balance' => $item['balance_qty']
      ];
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            background: white;
            width: 100%;
        }
        
        @page {
            size: legal landscape;
            margin: 0.5cm;
        }
        
        .smpi-card {
            page-break-after: always;
            page-break-inside: avoid;
            margin-bottom: 1.5cm;
            border: 1px solid #ccc;
            padding: 12px;
            width: 100%;
        }
        
        .smpi-card:last-child {
            page-break-after: auto;
        }
        
        .text-center {
            text-align: center;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        .table-bordered th,
        .table-bordered tr,
        .table-bordered td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 11px;
        }
        
        .table thead th {
            vertical-align: middle;
            background-color: #f8f9fa;
        }
        
        .header-section {
            margin-bottom: 15px;
        }
        
        .property-details {
            margin-bottom: 12px;
        }
        
        /* Print header with filter info */
        .print-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }
        
        .filter-info {
            font-size: 12px;
            margin-bottom: 8px;
            text-align: left;
        }
        
        .total-items {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
            text-align: center;
        }
        
        /* Adjust table column widths for legal landscape */
        .date-col {
            width: 8% !important;
        }
        
        .reference-col {
            width: 10% !important;
        }
        
        .receipt-col {
            width: 7% !important;
        }
        
        .issue-col {
            width: 8% !important;
        }
        
        .balance-col {
            width: 5% !important;
        }
        
        .amount-col {
            width: 10% !important;
        }
        
        /* Ensure proper scaling for legal paper */
        .legal-content {
            max-width: 13.5in;
            margin: 0 auto;
        }
    </style>
</head>
<body onload="window.print();">
    <div class="legal-content">
        <?php if (!empty($smpi_items)): ?>
         
    

                <div class="smpi-card">
                    <!-- Property Card Header -->
                    <div class="header-section text-center">
                                <h6 style="margin-top:3px; font-size:14px;"><strong>SEMI-EXPENDABLE PROPERTY CARD</strong></h6>
                            </div>
                           

                    <!-- Item Details -->
                    <div class="property-details">
                        <table style="width: 100%; border-collapse: collapse; font-size:11px;">
                            <tr>
                                <td style="padding: 3px 0;">
                                    <strong>Entity Name: </strong>
                                    <span style="margin-left:8px; display:inline-block; border-bottom:1px solid #000; min-width:250px;text-align:center;">
                                        Benguet State University - BOKOD CAMPUS
                                    </span>
                                </td>
                                <td style="padding: 3px 0; text-align: right;">
                                    <strong>Fund Cluster: </strong>
                                    <span style="margin-left:8px; display:inline-block; border-bottom:1px solid #000; min-width:120px;text-align:center;">
                                        <?= $item['fund_cluster'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 3px 0;">
                                    <strong>Semi-expendable Property: </strong>
                                    <span style="margin-left:8px; display:inline-block; border-bottom:1px solid #000; min-width:350px;text-align:center;">
                                        <?= strtoupper($item['item'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 3px 0;">
                                    <strong>Description: </strong>
                                    <span style="margin-left:8px; display:inline-block; border-bottom:1px solid #000; min-width:350px;text-align:center;">
                                        <?= $item['item_description'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                            </tr>
                        </table>
                    </div>

                    <!-- SMPI Table -->
                    <table class="table table-bordered text-center" style="border: 2px solid #000;">
                        <thead>
                            <tr>
                                <th class="date-col" rowspan="2" style="vertical-align: middle;">Date</th>
                                <th class="reference-col" rowspan="2" style="vertical-align: middle;">Reference</th>
                                <th colspan="3">RECEIPT</th>
                                <th colspan="3">ISSUE/TRANSFER/DISPOSAL</th>
                                <th class="balance-col" rowspan="2" style="vertical-align: middle;">Balance</th>
                                <th class="amount-col" rowspan="2" style="vertical-align: middle;">Amount</th>
                                <th class="amount-col" rowspan="2" style="vertical-align: middle;">Remarks</th>
                            </tr>
                            <tr>
                                <th class="receipt-col">Qty</th>
                                <th class="receipt-col">Unit Cost</th>
                                <th class="receipt-col">Total Cost</th>
                                <th class="issue-col">Item No.</th>
                                <th class="issue-col">Qty.</th>
                                <th class="issue-col">Office/officer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $item_transactions = $smpi_transactions[$item['id']] ?? [];
                            if (!empty($item_transactions)): 
                            ?>
                                <?php foreach ($item_transactions as $row): ?>
                                    <tr>
                                        <td style="border: 1px solid #000;">
                                            <?= !empty($row['transaction_date']) ? date('m/d/Y', strtotime($row['transaction_date'])) : '-' ?>
                                        </td>
                                        <td style="border: 1px solid #000;">
                                            <?= $row['ICS_No'] ?? $row['ICS_No'] ?? $row['RRSP_No'] ?? $row['transaction_type'] ?? '-' ?>
                                        </td>
                                        <td style="border: 1px solid #000; text-align: center;">
                                            <?=  $row['issued_qty']?>
                                        </td>
                                        <td style="border: 1px solid #000; text-align: right;">
                                            <?= number_format($row['unit_cost'], 2) ?>
                                        </td>
                                        <td style="border: 1px solid #000; text-align: right;">
                                            <?=  number_format($row['total_cost'], 2) ?>
                                        </td>
                                        <td style="border: 1px solid #000;"><?= $item['inv_item_no'] ?? 'N/A' ?></td>
                                        <td style="border: 1px solid #000; text-align: center;">
                                            <?= ($row['transaction_type'] !== 'Receipt' && $row['transaction_type'] !== 'None') ? $row['issued_qty'] : '' ?>
                                        </td>
                                        <td style="border: 1px solid #000;">
                                            <?= $row['officer'] ?? '-' ?>
                                        </td>
                                        <td style="border: 1px solid #000; text-align: center;">
                                            <?= number_format($row['current_balance'] ?? 0, 0) ?>
                                        </td>
                                        <td style="border: 1px solid #000; text-align: right;">
                                            <?= number_format(($row['current_balance'] ?? 0) * ($row['unit_cost'] ?? 0), 2) ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align:center; border: 1px solid #000;">No transaction data found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        
                        <!-- Empty rows -->
                        <tbody>
                            <?php
                            $count = !empty($item_transactions) ? count($item_transactions) : 0;
                            $empty_rows = 20 - $count;
                            if ($empty_rows > 0):
                                for ($i = 0; $i < $empty_rows; $i++): ?>
                                    <tr>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                        <td style="border: 1px solid #000;">&nbsp;</td>
                                    </tr>
                            <?php endfor;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <h3>No semi-expendable items found matching your criteria.</h3>
                <?php if ($fund_cluster_filter || $value_filter || $stock_card_input): ?>
                    <p>Filters applied:</p>
                    <ul style="list-style: none; padding: 0;">
                        <?php if ($fund_cluster_filter): ?>
                            <li>Fund Cluster: <?= $fund_cluster_filter ?></li>
                        <?php endif; ?>
                        <?php if ($value_filter): ?>
                            <li>Value Range: <?= $value_filter === 'high' ? 'High Value (₱5,000 - ₱50,000)' : 'Low Value (Below ₱5,000)' ?></li>
                        <?php endif; ?>
                        <?php if ($stock_card_input): ?>
                            <li>Search: <?= $stock_card_input ?></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>