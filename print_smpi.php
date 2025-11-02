<?php
$page_title = 'Print SMPI Card';
require_once('includes/load.php');
page_require_level(1);

// =====================
// Get parameters - now using item_id for specific item
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
    } else {
        // No transaction record
        $smpi_transactions[] = [
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
            'position' => null,
            'department' => null,
            'item_name' => $item['item'],
            'item_description' => $item['item_description'],
            'inv_item_no' => $item['inv_item_no'],
            'fund_cluster' => $item['fund_cluster'],
            'item_unit_cost' => $item['unit_cost'],
            'current_balance' => $item['balance_qty'],
            'return_date' => null
        ];
    }
}

// =====================
// Excel Export Functionality
// =====================
if (isset($_POST['export_excel']) && $item) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="SMPI_Card_' . $item['inv_item_no'] . '_' . date('Y-m-d') . '.xls"');
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
    
    // Add empty rows for printing
    $count = !empty($smpi_transactions) ? count($smpi_transactions) : 0;
    $empty_rows = 15 - $count;
    if ($empty_rows > 0) {
        for ($i = 0; $i < $empty_rows; $i++) {
            $excel_content .= "<tr>";
            for ($j = 0; $j < 11; $j++) {
                $excel_content .= "<td>&nbsp;</td>";
            }
            $excel_content .= "</tr>";
        }
    }
    
    $excel_content .= "</table>";
    $excel_content .= "</body></html>";
    
    echo $excel_content;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background: white;
            width: 100%;
        }
        
        @page {
            size: legal landscape;
            margin: 0.5cm;
        }
        
        .smpi-card {
            page-break-after: avoid;
            page-break-inside: avoid;
            width: 100%;
            padding: 0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        .table-bordered th,
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
            margin-bottom: 10px;
        }
        
        .property-details {
            margin-bottom: 8px;
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
        
        .remarks-col {
            width: 12% !important;
        }
        
        /* Ensure proper scaling for legal paper */
        .legal-content {
            max-width: 13.5in;
            margin: 0 auto;
        }
        
        .no-print {
            display: none;
        }
        
        /* Print specific styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }

        /* Improved alignment for item details */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 8px;
        }
        
        .detail-table td {
            padding: 3px 5px;
            vertical-align: top;
        }
        
        .label-cell {
            width: 20%;
            white-space: nowrap;
            font-weight: bold;
        }
        
        .value-cell {
            width: 30%;
            border-bottom: 1px solid #000;
        }
        
        .value-cell-full {
            width: 80%;
            border-bottom: 1px solid #000;
        }

        /* Button Styles */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
            flex-direction: column;
        }

        .action-btn {
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            min-width: 140px;
            justify-content: center;
        }

        .print-btn {
            background: #28a745;
            color: white;
        }

        .print-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .excel-btn {
            background: #1d6f42;
            color: white;
        }

        .excel-btn:hover {
            background: #155c34;
            transform: translateY(-2px);
        }

        .close-btn {
            background: #6c757d;
            color: white;
        }

        .close-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-icon {
            font-size: 16px;
        }
    </style>
</head>
<body onload="window.print();">
    <div class="legal-content">
        <?php if ($item): ?>
            <div class="smpi-card">
                <!-- Property Card Header -->
                <div class="header-section text-center">
                    <h6 style="margin:5px 0; font-size:14px; font-weight: bold;">
                        PROPERTY CARD FOR SEMI-EXPENDABLE PROPERTY
                    </h6>
                </div>

                <!-- Item Details - Improved Alignment -->
                <div class="property-details">
                    <table class="detail-table">
                        <tr>
                            <td class="label-cell">Entity Name:</td>
                            <td class="value-cell">BENGUET STATE UNIVERSITY - BOKOD CAMPUS</td>
                            <td class="label-cell" style="text-align: right;">Fund Cluster:</td>
                            <td class="value-cell"><?= $item['fund_cluster'] ?? 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Item Description:</td>
                            <td class="value-cell-full" colspan="3"><?= strtoupper($item['item'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Description:</td>
                            <td class="value-cell-full" colspan="3"><?= $item['item_description'] ?? 'N/A'; ?></td>
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
                            <th class="remarks-col" rowspan="2" style="vertical-align: middle;">Remarks</th>
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
                        <?php if (!empty($smpi_transactions)): ?>
                            <?php 
                            $running_balance = $item['balance_qty'];
                            $running_amount = $item['balance_qty'] * $item['unit_cost'];
                            ?>
                            <?php foreach ($smpi_transactions as $row): ?>
                                <?php
                                // Calculate running balance and amount
                                if ($row['transaction_type'] === 'Issue' || $row['transaction_type'] === 'Transfer') {
                                    $running_balance -= $row['issued_qty'];
                                    $running_amount = $running_balance * $row['unit_cost'];
                                } elseif ($row['transaction_type'] === 'Receipt') {
                                    $running_balance += $row['issued_qty'];
                                    $running_amount = $running_balance * $row['unit_cost'];
                                }
                                ?>
                                <tr>
                                    <td style="border: 1px solid #000;">
                                        <?= !empty($row['transaction_date']) ? date('m/d/Y', strtotime($row['transaction_date'])) : '-' ?>
                                    </td>
                                    <td style="border: 1px solid #000;">
                                        <?= $row['ICS_No'] ?? $row['PAR_No'] ?? $row['RRSP_No'] ?? $row['transaction_type'] ?? '-' ?>
                                    </td>
                                    <td style="border: 1px solid #000; text-align: center;">
                                        <?= $row['transaction_type'] === 'Receipt' ? $row['issued_qty'] : '' ?>
                                    </td>
                                    <td style="border: 1px solid #000; text-align: right;">
                                        <?= $row['transaction_type'] === 'Receipt' ? number_format($row['unit_cost'], 2) : '' ?>
                                    </td>
                                    <td style="border: 1px solid #000; text-align: right;">
                                        <?= $row['transaction_type'] === 'Receipt' ? number_format($row['total_cost'], 2) : '' ?>
                                    </td>
                                    <td style="border: 1px solid #000;"><?= $item['inv_item_no'] ?? 'N/A' ?></td>
                                    <td style="border: 1px solid #000; text-align: center;">
                                        <?= ($row['transaction_type'] === 'Issue' || $row['transaction_type'] === 'Transfer') ? $row['issued_qty'] : '' ?>
                                    </td>
                                    <td style="border: 1px solid #000;">
                                        <?= $row['officer'] ?? ($row['department'] ?? '-') ?>
                                    </td>
                                    <td style="border: 1px solid #000; text-align: center;">
                                        <?= number_format($running_balance, 0) ?>
                                    </td>
                                    <td style="border: 1px solid #000; text-align: right;">
                                        <?= number_format($running_amount, 2) ?>
                                    </td>
                                    <td style="border: 1px solid #000;">
                                        <?= $row['transaction_type'] ?? '' ?>
                                        <?= !empty($row['return_date']) ? '(Returned: ' . date('m/d/Y', strtotime($row['return_date'])) . ')' : '' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align:center; border: 1px solid #000;">No transaction data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <!-- Empty rows -->
                    <tbody>
                        <?php
                        $count = !empty($smpi_transactions) ? count($smpi_transactions) : 0;
                        $empty_rows = 15 - $count;
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
                <h3>Item not found.</h3>
                <p>The requested semi-expendable property item could not be found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <?php if ($item): ?>
            <!-- Excel Export Form -->
            <form method="post" style="display: inline;">
                <button type="submit" name="export_excel" class="action-btn excel-btn">
                    <i class="fas fa-file-excel btn-icon"></i>
                    Export to Excel
                </button>
            </form>
        <?php endif; ?>
        
        <!-- <button onclick="window.print()" class="action-btn print-btn">
            <i class="fas fa-print btn-icon"></i>
            Print
        </button> -->
        
        <button onclick="window.close()" class="action-btn close-btn">
            <i class="fas fa-times btn-icon"></i>
            Close
        </button>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Optional: Add a small delay before auto-printing
            setTimeout(function() {
                window.print();
            }, 1000);
        };

        // Handle Excel export confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const excelForm = document.querySelector('form[method="post"]');
            if (excelForm) {
                excelForm.addEventListener('submit', function(e) {
                    const button = this.querySelector('button[name="export_excel"]');
                    button.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i> Exporting...';
                    button.disabled = true;
                    
                    // Re-enable button after 3 seconds in case of failure
                    setTimeout(() => {
                        button.innerHTML = '<i class="fas fa-file-excel btn-icon"></i> Export to Excel';
                        button.disabled = false;
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>