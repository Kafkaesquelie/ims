<?php
$page_title = 'Receipt of Returned Semi-Expendable Property (RRSP)';
require_once('includes/load.php');
page_require_level(1);

// Get parameters
$transaction_id = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : null;
$ics_no = isset($_GET['ics_no']) ? trim($db->escape($_GET['ics_no'])) : null;

// Fetch data based on parameters
if ($transaction_id) {
    // Single transaction RRSP - FIXED: Use transaction_id to find return_items
    $sql = "
        SELECT 
            t.*,
            ri.id as return_item_id,
            ri.return_date,
            ri.qty as returned_qty,
            ri.conditions,
            ri.remarks,
            p.item,
            p.item_description,
            p.unit,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name
        FROM return_items ri
        JOIN transactions t ON ri.transaction_id = t.id
        JOIN semi_exp_prop p ON t.item_id = p.id
        JOIN employees e ON t.employee_id = e.id
        JOIN offices o ON e.office = o.id
        WHERE ri.transaction_id = {$transaction_id}
        ORDER BY ri.return_date DESC 
        LIMIT 1
    ";
    $return_items = find_by_sql($sql);
    
    if (!$return_items) {
        $session->msg("d", "No return record found for transaction ID: {$transaction_id}");
        redirect('logs.php');
    }
    
    $return_item = $return_items[0];
    $ics_no = $return_item['ICS_No'];
    
} elseif ($ics_no) {
    // All returned items for an ICS document
    $sql = "
        SELECT 
            t.*,
            ri.id as return_item_id,
            ri.return_date,
            ri.qty as returned_qty,
            ri.conditions,
            ri.remarks,
            p.item,
            p.item_description,
            p.unit,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name
        FROM return_items ri
        JOIN transactions t ON ri.transaction_id = t.id
        JOIN semi_exp_prop p ON t.item_id = p.id
        JOIN employees e ON t.employee_id = e.id
        JOIN offices o ON e.office = o.id
        WHERE t.ICS_No = '{$ics_no}'
        ORDER BY ri.return_date DESC, p.item ASC
    ";
    $return_items = find_by_sql($sql);
    
    if (!$return_items) {
        $session->msg("d", "No returned items found for ICS: {$ics_no}");
        redirect('logs.php');
    }
    
} else {
    $session->msg("d", "No transaction or ICS number provided.");
    redirect('logs.php');
}

// Get current user for received by information
$current_user = current_user();

// Generate RRSP number - FIXED: Use return_item_id if available
if ($transaction_id && isset($return_item['return_item_id'])) {
    $rrsp_number = date('Y-m') . '-' . sprintf('%04d', $return_item['return_item_id']);
} else {
    $rrsp_number = date('Y-m') . '-' . sprintf('%04d', rand(1000, 9999));
}

// If multiple items, use the first return date
if ($ics_no && !empty($return_items)) {
    $return_date = $return_items[0]['return_date'];
} else {
    $return_date = $return_item['return_date'];
}

// Get current date for the form
$current_date = date('m/d/Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RRSP Receipt - <?= $rrsp_number ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 13px;
            background: white;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100vh;
        }
        .container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 15px;
            background: white;
            position: relative;
        }
        h2 {
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header-table, .items-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            font-size: 12px;
            padding: 3px 5px;
            vertical-align: top;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            text-align: center;
            padding: 8px 4px;
            font-size: 11px;
        }
        .items-table th {
            background: #f2f2f2;
            font-weight: bold;
        }
        .footer-table td {
            text-align: center;
            padding: 40px 5px 5px 5px;
            font-size: 12px;
            vertical-align: top;
            width: 50%;
        }
        .signature {
            border-top: 1px solid #000;
            display: inline-block;
            padding-top: 3px;
            font-size: 12px;
            min-width: 200px;
            margin-bottom: 8px;
        }
        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            padding-bottom: 2px;
            min-width: 200px;
        }
        .small-text {
            font-size: 10px;
            color: #666;
        }
        .item-description {
            text-align: left;
            padding-left: 8px;
        }
        .text-left {
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .remarks-section {
            border: 1px solid #000;
            padding: 15px;
            margin-top: 10px;
            min-height: 120px;
        }
        .date-line {
            text-align: right;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            border: 2px solid #28a745;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-print, .btn-excel, .btn-close {
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            min-width: 180px;
        }
        .btn-print {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .btn-print:hover {
            background: linear-gradient(135deg, #218838, #1aa179);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .btn-excel {
            background: linear-gradient(135deg, #217346, #1e7e34);
            color: white;
        }
        .btn-excel:hover {
            background: linear-gradient(135deg, #1e663e, #186429);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 115, 70, 0.4);
        }
        .btn-close {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        .btn-close:hover {
            background: linear-gradient(135deg, #5a6268, #343a40);
            transform: translateY(-2px);
        }
        .no-print {
            display: block;
        }
        @media print {
            .print-controls {
                display: none !important;
            }
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
                background: white;
            }
            .container {
                border: none;
                padding: 10px;
                margin: 0;
                width: 100%;
                box-shadow: none;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            h2 {
                page-break-after: avoid;
            }
            .items-table {
                page-break-inside: avoid;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
        @media screen {
            body {
                padding: 20px;
                background: #f8f9fa;
            }
            .container {
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="btn-print" onclick="printRRSP()">
            <i class="fas fa-print"></i> Print RRSP
        </button><br>
        <button class="btn-excel" onclick="exportToExcel()">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button><br>
        <button class="btn-close" onclick="window.close()">
            <i class="fas fa-times"></i> Close Window
        </button>
    </div>

    <div class="container">
        <h2>RECEIPT OF RETURNED SEMI-EXPENDABLE PROPERTY (RRSP)</h2>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th rowspan="2" colspan="3" class="text-left" style="border: 1px solid #000; padding: 8px;">
                        <strong>Entity Name:</strong> BENGUET STATE UNIVERSITY - BOKOD CAMPUS
                    </th>
                    <th colspan="2" class="text-right" style="border: 1px solid #000; padding: 8px;">
                        <strong>RRSP No:</strong> <?= $rrsp_number; ?>
                    </th>
                </tr>
                <tr>
                    <th colspan="2" class="text-right" style="border: 1px solid #000; padding: 8px;">
                        <strong>Date:</strong> <?= $current_date; ?>
                    </th>
                </tr>
                <tr>
                    <th style="width: 40%;">ITEM DESCRIPTION</th>
                    <th style="width: 10%;">QTY</th>
                    <th style="width: 15%;">ICS NO.</th>
                    <th style="width: 15%;">END-USER</th>
                    <th style="width: 10%;">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transaction_id): ?>
                    <!-- Single item return -->
                    <tr>
                        <td class="item-description">
                            <strong><?= $return_item['item']; ?></strong>
                            <?php if (!empty($return_item['item_description'])): ?>
                                <br><small class="small-text"><?= $return_item['item_description']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $return_item['returned_qty']; ?></td>
                        <td><?= $return_item['ICS_No']; ?></td>
                        <td><?= $return_item['employee_name']; ?></td>
                        <td><?= $return_item['conditions']; ?></td>
                    </tr>
                <?php else: ?>
                    <!-- Multiple items return -->
                    <?php foreach ($return_items as $item): ?>
                        <tr>
                            <td class="item-description">
                                <strong><?= $item['item']; ?></strong>
                                <?php if (!empty($item['item_description'])): ?>
                                    <br><small class="small-text"><?= $item['item_description']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['returned_qty']; ?></td>
                            <td><?= $item['unit']; ?></td>
                            <td><?= $item['ICS_No']; ?></td>
                            <td><?= $item['employee_name']; ?></td>
                            <td><?= $item['conditions']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Blank rows for remaining space -->
                <?php 
                $filled_rows = $transaction_id ? 1 : count($return_items);
                $blank_rows = max(0, 15 - $filled_rows);
                ?>
                <?php for ($i = 0; $i < $blank_rows; $i++): ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
               
                <!-- Signatories -->
                <tr>
                    <td colspan="3" style="border: 1px solid #000; padding: 40px 5px 5px 5px; text-align: center; vertical-align: top;">
                        <div class="underline">
                            <?= $return_items[0]['employee_name']; ?>
                        </div>
                        <div class="small-text">
                            End User<br>
                            Date: <span class="underline" style="min-width: 100px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        </div>
                    </td>
                    <td colspan="2" style=" padding: 40px 5px 5px 5px; text-align: center; vertical-align: top;">
                        <div class="underline">
                            <?= $current_user['name']; ?>
                        </div>
                        <div class="small-text">
                            Head, Property and/or Supply Division / Unit<br>
                            Date: <span class="underline" style="min-width: 100px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        </div>
                    </td>
                </tr>
                
                <?php if (!$transaction_id): ?>
                    <!-- Summary for multiple items -->
                    <tr>
                        <td colspan="5" style="border: 1px solid #000; padding: 10px; text-align: center; font-size: 11px;">
                            <strong>Summary:</strong> 
                            Total <?= count($return_items); ?> item(s) returned under ICS No: <?= $ics_no; ?>
                            on <?= date('F d, Y', strtotime($return_date)); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>
    </div>

    <script>
    // Print function
    function printRRSP() {
        window.print();
    }

    // Export to Excel function
    function exportToExcel() {
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const transaction_id = urlParams.get('transaction_id');
        const ics_no = urlParams.get('ics_no');
        
        // Build export URL
        let exportUrl = 'export_rrsp.php?';
        if (transaction_id) {
            exportUrl += 'transaction_id=' + transaction_id;
        } else if (ics_no) {
            exportUrl += 'ics_no=' + encodeURIComponent(ics_no);
        }
        
        // Redirect to export script
        window.location.href = exportUrl;
    }

    // Close window function
    function closeWindow() {
        window.close();
    }
    </script>
</body>
</html>