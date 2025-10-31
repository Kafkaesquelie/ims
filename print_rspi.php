<?php
$page_title = 'Print RSPI Report';
require_once('includes/load.php');
page_require_level(1);

// Get parameters
$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;
$value_type = $_GET['value_type'] ?? null;
$property_custodian = $_GET['property_custodian'] ?? null;
$accounting_staff = $_GET['accounting_staff'] ?? null;

// Build WHERE conditions
$where_conditions = [];
$where_conditions[] = "t.transaction_type = 'issue'";
$where_conditions[] = "t.quantity > 0";

if ($report_date) {
    $where_conditions[] = "DATE(t.transaction_date) = '" . $db->escape($report_date) . "'";
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

// Fetch issued semi-expendable items from transactions table with details from semi_exp_prop
$semi_expendable_items = find_by_sql("
    SELECT 
        p.inv_item_no,
        p.item,
        p.item_description,
        p.unit,
        p.unit_cost,
        p.fund_cluster,
        t.ICS_No,
        t.quantity as qty_issued,
        (t.quantity * p.unit_cost) AS amount,
        t.transaction_date,
        sc.semicategory_name as category
    FROM transactions t
    INNER JOIN semi_exp_prop p ON t.item_id = p.id
    LEFT JOIN semicategories sc ON p.semicategory_id = sc.id
    WHERE $where_clause
    ORDER BY t.transaction_date ASC, p.inv_item_no ASC
");

$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : date("F d, Y");
$rspi_serial_prefix = date("Y-m-");

// Prepare data for Excel export
$excel_data = [];
if (!empty($semi_expendable_items)) {
    foreach ($semi_expendable_items as $item) {
        $excel_data[] = [
            'ics_no' => $item['ICS_No'],
            'inv_item_no' => $item['inv_item_no'],
            'item_description' => $item['item'] . ' - ' . $item['item_description'],
            'unit' => $item['unit'],
            'qty_issued' => $item['qty_issued'],
            'unit_cost' => $item['unit_cost'],
            'amount' => $item['amount']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSPI Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Print Controls Styling - VERTICAL LAYOUT */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            border: 2px solid #28a745;
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 140px;
        }
        
        .print-controls .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 15px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
        }
        
        .print-controls .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .print-controls .btn-success:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }
        
        .print-controls .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .print-controls .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
        }
        
        .print-controls .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .print-controls .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .print-controls .btn i {
            margin-right: 8px;
        }
        
        /* Column widths */
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
        <button onclick="exportToExcel()" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
      
        <button onclick="handleClose()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <div class="container">
        <!-- RSPI Header -->
        <div class="rspi-header">
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
                        <th>ICS No.</th>
                        <th>Responsibility Center Code</th>
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
                        <?php 
                        $total_amount = 0;
                        foreach($semi_expendable_items as $item): 
                            $total_amount += $item['amount'];
                        ?>
                            <tr>
                                <td><?= $item['ICS_No']; ?></td>
                                <td></td>
                                <td><?= $item['inv_item_no']; ?></td>
                                <td class="text-left"><?= $item['item'] . ' - ' . $item['item_description']; ?></td>
                                <td><?= $item['unit']; ?></td>
                                <td><?= $item['qty_issued']; ?></td>
                                <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                                <td class="text-right">₱<?= number_format($item['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
       
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No semi-expendable properties issued found with the selected filters.</td>
                        </tr>
                    <?php endif; ?>

                    <!-- Empty rows for spacing -->
                    <?php 
                    $item_count = count($semi_expendable_items);
                    $empty_rows = max(0, 10 - $item_count);
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

                    <!-- Signatories Row -->
                    <tr>
                        <td colspan="5" style="padding-top: 20px; vertical-align: top;">
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
        </div>
    </div>

    <script>
        // Enhanced print function
        function handlePrint() {
            // Show print dialog
            window.print();
        }
        
        // Enhanced close function
        function handleClose() {
            // Check if this is a popup window or main window
            if (window.opener && !window.opener.closed) {
                // This is a popup window - close it
                window.close();
            } else {
                // This might be a main window - go back or show message
                if (history.length > 1) {
                    history.back();
                } else {
                    // If no history, just close the window/tab
                    window.close();
                }
            }
        }
        
        // Export to Excel function
        function exportToExcel() {
            // Create a form to submit the data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_rspi.php';
            
            // Add the data as hidden fields
            const data = <?= json_encode($excel_data) ?>;
            const dataInput = document.createElement('input');
            dataInput.type = 'hidden';
            dataInput.name = 'excel_data';
            dataInput.value = JSON.stringify(data);
            form.appendChild(dataInput);
            
            // Add metadata
            const entityInput = document.createElement('input');
            entityInput.type = 'hidden';
            entityInput.name = 'entity_name';
            entityInput.value = 'Benguet State University - BOKOD CAMPUS';
            form.appendChild(entityInput);
            
            const serialInput = document.createElement('input');
            serialInput.type = 'hidden';
            serialInput.name = 'serial_no';
            serialInput.value = '<?= $rspi_serial_prefix ?>0000';
            form.appendChild(serialInput);
            
            const fundInput = document.createElement('input');
            fundInput.type = 'hidden';
            fundInput.name = 'fund_cluster';
            fundInput.value = '<?= $selected_cluster ?: 'GAA' ?>';
            form.appendChild(fundInput);
            
            const dateInput = document.createElement('input');
            dateInput.type = 'hidden';
            dateInput.name = 'report_date';
            dateInput.value = '<?= $display_date ?>';
            form.appendChild(dateInput);
            
            const custodianInput = document.createElement('input');
            custodianInput.type = 'hidden';
            custodianInput.name = 'property_custodian';
            custodianInput.value = '<?= htmlspecialchars($property_custodian) ?>';
            form.appendChild(custodianInput);
            
            const accountingInput = document.createElement('input');
            accountingInput.type = 'hidden';
            accountingInput.name = 'accounting_staff';
            accountingInput.value = '<?= htmlspecialchars($accounting_staff) ?>';
            form.appendChild(accountingInput);
            
            // Submit the form
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure everything is rendered
            setTimeout(function() {
                // Auto-print can be enabled by uncommenting the line below
                // window.print();
            }, 500);
        };

        // Add event listener for after print
        window.onafterprint = function() {
            // Optional: Add any post-print actions here
            console.log('Print completed or cancelled');
        };
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P or Cmd+P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                handlePrint();
            }
            // Escape key to close
            if (e.key === 'Escape') {
                handleClose();
            }
        });
    </script>
</body>
</html>