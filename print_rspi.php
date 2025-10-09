<?php
$page_title = 'Print RSPI Report';
require_once('includes/load.php');
page_require_level(1);

// Get parameters
$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;
$value_type = $_GET['value_type'] ?? null;
$property_custodian = $_GET['property_custodian'] ?? 'BRIGIDA A. BENSOSAN';
$accounting_staff = $_GET['accounting_staff'] ?? 'FREDALYN JOY Y. FINMARA';

// Build WHERE conditions (same as main page)
$where_conditions = [];
$where_conditions[] = "p.inv_item_no IS NOT NULL AND p.inv_item_no != ''";

if ($report_date) {
    $where_conditions[] = "DATE(p.date_added) = '" . $db->escape($report_date) . "'";
}

if ($selected_cluster && $selected_cluster !== 'all') {
    $where_conditions[] = "p.fund_cluster = '" . $db->escape($selected_cluster) . "'";
}

if ($value_type === 'low') {
    $where_conditions[] = "p.unit_cost < 5000";
} elseif ($value_type === 'high') {
    $where_conditions[] = "p.unit_cost >= 5000 AND p.unit_cost <= 50000";
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch semi-expendable items
$semi_expendable_items = find_by_sql("
    SELECT 
        p.inv_item_no,
        p.item,
        p.item_description,
        p.unit,
        p.qty_issued,
        p.unit_cost,
        (p.qty_issued * p.unit_cost) AS amount,
        p.fund_cluster,
        p.date_added,
        sc.semicategory_name as category
    FROM semi_exp_prop p
    LEFT JOIN semicategories sc ON p.semicategory_id = sc.id
    WHERE $where_clause
    ORDER BY p.date_added ASC
");

$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : date("F d, Y");
$rspi_serial_prefix = date("Y-m-");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSPI Report</title>
    <style>
        body { 
            font-family: 'Times New Roman', serif; 
            margin: 0;
            padding: 15px;
            font-size: 12px;
            background: white;
        }
        .container { 
            width: 100%; 
            max-width: none;
            margin: 0 auto;
        }
        .rspi-header { 
            text-align: center; 
            margin-bottom: 15px;
            page-break-after: avoid;
        }
        .rspi-title { 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 8px; 
            text-transform: uppercase; 
        }
        .rspi-subtitle { 
            font-size: 12px; 
            margin-bottom: 4px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 4px; 
            text-align: center;
            font-size: 11px;
        }
        th { 
            font-weight: bold; 
            background-color: #f8f9fa;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .rspi-info-table { 
            width: 100%; 
            border: none; 
            margin-bottom: 15px;
        }
        .rspi-info-table td { 
            border: none; 
            padding: 2px 4px; 
            vertical-align: top;
            font-size: 11px;
        }
        .rspi-signature-section { 
            margin-top: 20px;
            page-break-before: avoid;
        }
        .rspi-signature-box { 
            display: inline-block; 
            width: 48%; 
            vertical-align: top;
            margin-right: 2%;
        }
        .rspi-signature-line { 
            border-bottom: 1px solid #000; 
            margin-bottom: 4px; 
            height: 18px;
        }
        .rspi-signature-label { 
            font-size: 10px; 
            text-align: center;
        }
        
        /* Column widths for landscape */
       /* Adjust column widths more evenly */
.rspi-property-table th:nth-child(1),
.rspi-property-table td:nth-child(1) { width: 20%; }

.rspi-property-table th:nth-child(2),
.rspi-property-table td:nth-child(2) { width: 30%; }

.rspi-property-table th:nth-child(3),
.rspi-property-table td:nth-child(3) { width: 10%; }

.rspi-property-table th:nth-child(4),
.rspi-property-table td:nth-child(4) { width: 10%; }

.rspi-property-table th:nth-child(5),
.rspi-property-table td:nth-child(5) { width: 15%; }

.rspi-property-table th:nth-child(6),
.rspi-property-table td:nth-child(6) { width: 15%; }

        
        /* Landscape print styles */
        @media print {
            @page {
                size: landscape;
                 margin: 1in; 
            }
            body { 
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .container {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            table {
                page-break-inside: avoid;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            .rspi-header {
                page-break-after: avoid;
            }
            .rspi-signature-section {
                page-break-before: avoid;
            }
        }

        /* Screen preview styles */
        @media screen {
            body {
                background: #f5f5f5;
                padding: 50px;
            }
            .container {
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                padding: 1in; 
                max-width: 11in;
                min-height: 8.5in;
            }
            .print-controls {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 10px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
            }
        }

        /* Ensure proper spacing in landscape */
        .section-spacing {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Print Controls (visible only on screen) -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-success btn-sm">
            <i class="fas fa-print"></i> Print Now
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <div class="container" >
        <!-- RSPI Header -->
        <div class="rspi-header" >
            <div class="rspi-title">REPORT OF SEMI-EXPENDABLE PROPERTY ISSUED (RSPI)</div>

        </div>

        <!-- ICS and Property Info -->
       <table class="rspi-info-table">
    <tr>
        <td style="width: 50%;">
            <strong>Entity Name:</strong>
            <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px; margin-left: 8px; text-align: center;">
                Benguet State University - BOKOD CAMPUS
            </span>
        </td>
        <td style="width: 50%;">
            <strong>Serial No.:</strong>
            <span style="border-bottom: 1px solid #000; display: inline-block; width: 180px; margin-left: 8px; text-align: center;">
                <?= $rspi_serial_prefix ?>0000
            </span>
        </td>
    </tr>
    <tr>
        <td>
            <strong>Fund Cluster:</strong>
            <span style="border-bottom: 1px solid #000; display: inline-block; width: 250px; margin-left: 8px; text-align: center;">
                <?= $selected_cluster ?: 'GAA' ?>
            </span>
        </td>
        <td>
            <strong>Date:</strong>
            <span style="border-bottom: 1px solid #000; display: inline-block; width: 180px; margin-left: 8px; text-align: center;">
                <?= $display_date ?>
            </span>
        </td>
    </tr>
</table>

        <!-- Property Items Table -->
        <div class="section-spacing">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; margin-bottom: 10px;">
                    <i>To be filled out by the Property and/or Supply Division/Unit</i>
                    <i style="text-align: right;">To be filled out by the Accounting (Division/Unit)</i>
                    </div>

           <table class="rspi-property-table">
            
    <thead>
        <tr>
            <th>Semi-expendable Property No.</th>
            <th>Item Description</th>
            <th>Unit</th>
            <th>Qty. Issued</th>
            <th>Unit Cost</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($semi_expendable_items)): ?>
            <?php foreach($semi_expendable_items as $item): ?>
                <tr>
                    <td><?= $item['inv_item_no']; ?></td>
                    <td class="text-left"><?= $item['item'] . ' ' . $item['item_description']; ?></td>
                    <td><?= $item['unit']; ?></td>
                    <td><?= $item['qty_issued']; ?></td>
                    <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                    <td class="text-right">₱<?= number_format($item['amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No semi-expendable properties found with the selected filters.</td>
            </tr>
        <?php endif; ?>

        <!-- Empty rows for spacing -->
        <?php 
        $item_count = count($semi_expendable_items);
        $empty_rows = max(0, 12 - $item_count);
        for($i = 0; $i < $empty_rows; $i++): ?>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        <?php endfor; ?>

        <!-- Signatories Row -->
      <tr>
    <td colspan="3" style="padding-top: 20px; vertical-align: top;">
        <div style="margin-bottom: 8px; font-size: 11px; text-align: left;">
            I hereby certify to the correctness of the above information.
        </div>
        <div style="border-bottom: 1px solid #000; width: 220px; margin: 0 auto 4px auto; padding-bottom: 2px; text-align: center;">
            <?= htmlspecialchars($property_custodian) ?>
        </div>
        <div style="font-size: 10px; text-align: center;">
            Signature over Printed Name of Property and/or Supply Custodian
        </div>
    </td>

    <td colspan="3" style="padding-top: 20px; vertical-align: top;">
        <div style="margin-bottom: 8px; font-size: 11px; text-align: left;">
            Posted by:
        </div>
        <div style="border-bottom: 1px solid #000; width: 220px; margin: 0 auto 4px auto; padding-bottom: 2px; text-align: center;">
            <?= htmlspecialchars($accounting_staff) ?>
        </div>
        <div style="font-size: 10px; text-align: center;">
            Signature over Printed Name of Designated Accounting Staff
        </div>
    </td>
</tr>

    </tbody>
</table>


    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure everything is rendered
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Add event listener for after print
        window.onafterprint = function() {
            // Optionally close the window after printing
            // window.close();
        };
    </script>
</body>
</html>