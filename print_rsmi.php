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

// Filters - MATCH THE SAME FILTERS AS rsmi.php
$where_date = $report_date ? " AND DATE(r.date) = '" . $db->escape($report_date) . "'" : '';
$where_cluster = ($selected_cluster && $selected_cluster !== 'all') ? " AND i.fund_cluster = '" . $db->escape($selected_cluster) . "'" : '';

// Fetch request items with the SAME FILTERS as rsmi.php
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
    WHERE r.status NOT IN ('Pending', 'Approved')  -- SAME STATUS FILTER AS rsmi.php
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

// Use provided serial number or default
$final_serial_number = $serial_number ?: $serial_no_prefix . '0000';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $page_title ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #28a745;
    --primary-dark: #1e7e34;
    --primary-light: #34ce57;
    --secondary: #6c757d;
    --success: #28a745;
    --info: #17a2b8;
    --warning: #ffc107;
    --danger: #dc3545;
    --light: #f8f9fa;
    --dark: #343a40;
    --border-radius: 12px;
    --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

body { 
    font-family: 'Times New Roman', serif; 
    background: #f8f9fa;
    margin: 0;
    padding: 20px;
}
#print-area { 
    width: 1000px; 
    max-width: 100%; 
    margin: 20px auto; 
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.print-header {
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 0;
}
.print-header h4 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
}
table { 
    width: 100%; 
    border-collapse: collapse; 
    font-size: 11px; /* Slightly smaller font */
    margin-bottom: 0;
}
.header-table td { 
    border: none; 
    text-align: left; 
    padding: 6px 0; /* Reduced padding */
    font-size: 12px; /* Slightly smaller */
}
th, td { 
    border: 1px solid #000; 
    padding: 3px 5px; /* Reduced padding - smaller height */
    text-align: center; 
    line-height: 1.2; /* Reduced line height */
    height: 20px; /* Fixed smaller height */
}
th { 
    background: #f2f2f2; 
    font-weight: 600;
}

/* Print Controls */
.print-controls {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    z-index: 1000;
    border: 2px solid var(--primary);
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 180px;
}

.print-controls .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    gap: 8px;
}

.print-controls .btn-success {
    background: linear-gradient(135deg, var(--success), #1e7e34);
    color: white;
}

.print-controls .btn-success:hover {
    background: linear-gradient(135deg, #1e7e34, #155724);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.print-controls .btn-secondary {
    background: var(--secondary);
    color: white;
}

.print-controls .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.print-controls .btn i {
    font-size: 16px;
}

/* Content area */
.content-area {
    padding: 1.5rem; /* Reduced padding */
}

/* Signature sections */
.signature-section {
    padding: 12px; /* Reduced padding */
    background: #f8f9fa;
    border-top: 2px solid #dee2e6;
}

.signature-line {
    border-bottom: 1px solid #000;
    margin-bottom: 4px; /* Reduced margin */
    height: 18px; /* Smaller height */
}

.signature-label {
    font-size: 10px; /* Smaller font */
    text-align: center;
    color: #666;
}

/* Additional table styling for compact rows */
tbody tr {
    height: 22px; /* Fixed smaller row height */
}

/* Make empty rows smaller */
td:empty {
    padding: 2px 5px;
    height: 18px;
}

/* Reduce padding in specific cells */
.text-left, .text-right, .text-center {
    padding: 3px 5px !important;
}

@media print {
    .print-controls { 
        display: none !important; 
    }
    body { 
        background: white; 
        padding: 0;
        margin: 0;
    }
    #print-area { 
        box-shadow: none; 
        margin: 0;
        border-radius: 0;
    }
    .print-header {
        border-radius: 0;
        padding: 1rem; /* Reduced for print */
    }
    @page { 
        size: portrait; 
        margin: 10mm; /* Reduced margin for more space */
    }
    table {
        font-size: 10px; /* Even smaller for print */
    }
    th, td {
        padding: 2px 4px; /* Even smaller padding for print */
        height: 18px; /* Smaller for print */
    }
    .content-area {
        padding: 1rem; /* Reduced for print */
    }
}

/* Responsive design */
@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    .print-controls {
        position: static;
        margin-bottom: 20px;
        width: 100%;
        box-sizing: border-box;
    }
    #print-area {
        width: 100%;
        margin: 0;
    }
    .content-area {
        padding: 1rem;
    }
    table {
        font-size: 10px; /* Smaller for mobile */
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease;
}
</style>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls no-print">
    <button onclick="handlePrint()" class="btn btn-success">
        <i class="fas fa-print"></i> Print Report
    </button>
    <button onclick="handleClose()" class="btn btn-secondary">
        <i class="fas fa-times"></i> Close Window
    </button>
</div>

<div id="print-area" class="fade-in">
    <!-- Header -->
    <div class="print-header">
        <h4>REPORT OF SUPPLIES AND MATERIALS ISSUED</h4>
    </div>

    <div class="content-area">
        <!-- Header info table -->
        <table class="header-table" style="width:100%; margin-bottom:25px;">
            <tr>
                <td style="width:50%;">
                    <strong>Entity Name:</strong> 
                    <span style="display:inline-block; border-bottom:1px solid #000; width:60%; margin-left:10px; text-align:center;">
                        <?= 'BSU - BOKOD CAMPUS'; ?>
                    </span>
                </td>
                <td style="width:50%; text-align:right;">
                    <strong>Serial No:</strong> 
                    <span style="display:inline-block; border-bottom:1px solid #000; width:60%; margin-left:10px; text-align:center;">
                        <?= htmlspecialchars($final_serial_number); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Fund Cluster:</strong> 
                    <span style="display:inline-block; border-bottom:1px solid #000; width:60%; margin-left:10px; text-align:center;">
                        <?= $selected_cluster ?? 'All'; ?>
                    </span>
                </td>
                <td style="text-align:right;">
                    <strong>Date:</strong> 
                    <span style="display:inline-block; border-bottom:1px solid #000; width:60%; margin-left:10px; text-align:center;">
                        <?= $display_date; ?>
                    </span>
                </td>
            </tr>
        </table>

        <!-- Items table -->
        <table>
            <colgroup>
                <col style="width: 15%;">
                <col style="width: 8%;">
                <col style="width: 10%;">
                <col style="width: 25%;">
                <col style="width: 7%;">
                <col style="width: 10%;">
                <col style="width: 10%;">
                <col style="width: 15%;">
            </colgroup>
            <thead>
                <tr>
                    <td colspan="6" class="text-center" style="background:#f2f2f2; font-style: italic;">
                        To be filled up by the Supply and/or Property Division/Unit
                    </td>
                    <td colspan="2" class="text-center" style="background:#f2f2f2; font-style: italic;">
                        To be filled up by the Accounting Division/Unit
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
                <?php
                $max_rows = 10;
                $num_items = count($issued_items);
                $empty_rows = max(0, $max_rows - $num_items);
                $recap = [];

                if(!empty($issued_items)):
                    foreach($issued_items as $item): 
                        // Determine which unit to display (same logic as rsmi.php)
                        $unit_display = 'N/A';
                        $unit_type = 'unknown';
                        
                        // Priority: Check if unit_id is set (custom unit)
                        if (!empty($item['unit_id']) && !empty($item['unit_symbol'])) {
                            $unit_display = $item['unit_symbol'];
                            $unit_type = 'custom_unit';
                        }
                        // Fallback: Check if base_unit_id is set (base unit)
                        elseif (!empty($item['base_unit_id']) && !empty($item['base_unit_symbol'])) {
                            $unit_display = $item['base_unit_symbol'];
                            $unit_type = 'base_unit';
                        }
                        ?>
                        <tr>
                            <td><?= "RIS-" . $item['ris_no']; ?></td>
                            <td></td>
                            <td><?= $item['stock_card']; ?></td>
                            <td class="text-left"><?= $item['item_name']; ?></td>
                            <td><?= $unit_display; ?></td>
                            <td><?= $item['qty_issued']; ?></td>
                            <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                            <td class="text-right">₱<?= number_format($item['amount'], 2); ?></td>
                        </tr>
                        <?php
                        // Recap
                        $stock = $item['stock_card'];
                        if (!isset($recap[$stock])) {
                            $recap[$stock] = [
                                'qty' => 0,
                                'unit_cost' => $item['unit_cost'],
                                'total_cost' => 0,
                                'unit' => $unit_display,
                                'unit_type' => $unit_type
                            ];
                        }
                        $recap[$stock]['qty'] += $item['qty_issued'];
                        $recap[$stock]['total_cost'] += $item['amount'];
                    endforeach;
                else: ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px;">
                            No items found for the selected filters.
                        </td>
                    </tr>
                <?php endif;

                // Empty filler rows
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

                <!-- Recapitulation Rows -->
                <?php if(!empty($recap)): ?>
                    <tr>
                        <td colspan="1" class="text-center" style="font-weight:bold; background:#f2f2f2;"></td>
                        <td colspan="2" class="text-center" style="font-weight:bold; background:#f2f2f2;">
                            Recapitulation
                        </td>
                        <td colspan="1" class="text-center" style="font-weight:bold; background:#f2f2f2;"></td>
                        <td colspan="1" class="text-center" style="font-weight:bold; background:#f2f2f2;"></td>
                        <td colspan="3" class="text-center" style="font-weight:bold; background:#f2f2f2;">
                            Recapitulation
                        </td>
                    </tr>

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

                <!-- Additional empty rows after recap -->
                <?php 
                $recap_rows = !empty($recap) ? count($recap) + 2 : 0;
                $total_rows_used = $num_items + $recap_rows;
                $remaining_empty_rows = max(0, 10 - $total_rows_used);
                ?>

                <?php for($i = 0; $i < $remaining_empty_rows; $i++): ?>
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

                <!-- Signatories -->
                <tr>
                    <td colspan="4" style="padding:20px; vertical-align: top;">
                        <div style="margin-bottom: 10px; font-size: 12px; text-align: left;">
                            I hereby certify the correctness of the above information.
                        </div>
                        <div style="border-bottom: 1px solid #000; width: 220px; margin: 0 auto 5px auto; padding-bottom: 3px; text-align: center;">
                            <?= $current_user['name']; ?>
                        </div>
                        <div style="font-size: 11px; text-align: center; color: #666;">
                            <?= $current_user['position'] ?? 'Position'; ?>
                        </div>
                    </td>
                    <td colspan="4" style="padding:20px; vertical-align: top;">
                        <div style="margin-bottom: 10px; font-size: 12px; text-align: left;">
                            Posted by:
                        </div>
                        <div style="border-bottom: 1px solid #000; width: 220px; margin: 0 auto 5px auto; padding-bottom: 3px; text-align: center;">
                            <?= htmlspecialchars($signatory_name); ?>
                        </div>
                        <div style="font-size: 11px; text-align: center; color: #666;">
                            <?= htmlspecialchars($signatory_position); ?>
                        </div>
                        <div style="font-size: 11px; text-align: center; color: #666; margin-top: 5px;">
                            Date: ________________
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

// Auto focus on print button for accessibility
document.addEventListener('DOMContentLoaded', function() {
    const printBtn = document.querySelector('.btn-success');
    if (printBtn) {
        printBtn.focus();
    }
});
</script>

</body>
</html>