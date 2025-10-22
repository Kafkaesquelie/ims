<?php
// export_stock_card.php
require_once('includes/load.php');

// Get the posted data
$template = $_POST['template'] ?? 'STOCKCARD_Template';
$stock_number = $_POST['stock_number'] ?? '';
$item_name = $_POST['item_name'] ?? '';
$description = $_POST['description'] ?? '';
$unit_of_measurement = $_POST['unit_of_measurement'] ?? '';
$fund_cluster = $_POST['fund_cluster'] ?? '';
$reorder_point = $_POST['reorder_point'] ?? '';
$current_date = $_POST['current_date'] ?? date('Y-m-d');
$generation_date = $_POST['generation_date'] ?? date('F j, Y');

// Process transactions and calculate balances
$transactions_data = [];
$balance_qty = 0;
$balance_total = 0;

if (isset($_POST['transactions']) && is_array($_POST['transactions'])) {
    foreach ($_POST['transactions'] as $index => $transaction) {
        $qty = floatval($transaction['issue_qty'] ?? 0);
        $unit_cost = floatval($transaction['unit_cost'] ?? 0);
        $total_cost = floatval($transaction['issue_total_cost'] ?? 0);
        
        $balance_qty += $qty;
        $balance_total += $total_cost;
        
        $transactions_data[] = [
            'date' => $transaction['request_date'] ?? '',
            'reference' => 'REQ-' . ($transaction['req_id'] ?? ''),
            'receipt_qty' => $qty,
            'unit_cost' => $unit_cost,
            'issue_qty' => $qty,
            'issue_unit_cost' => $unit_cost,
            'issue_total_cost' => $total_cost,
            'department' => $transaction['department'] ?? '',
            'balance_qty' => $balance_qty,
            'balance_total' => $balance_total,
            'days_to_consume' => '',
            'remarks' => ''
        ];
    }
}

// Define all placeholders for the Word template
$placeholders = [
    // Header Information
    '{{STOCK_NUMBER}}' => htmlspecialchars($stock_number),
    '{{ITEM_NAME}}' => htmlspecialchars($item_name),
    '{{DESCRIPTION}}' => htmlspecialchars($description),
    '{{UNIT_OF_MEASUREMENT}}' => htmlspecialchars($unit_of_measurement),
    '{{FUND_CLUSTER}}' => htmlspecialchars($fund_cluster),
    '{{REORDER_POINT}}' => htmlspecialchars($reorder_point),
    
    // Dates
    '{{CURRENT_DATE}}' => $current_date,
    '{{GENERATION_DATE}}' => $generation_date,
    '{{YEAR}}' => date('Y'),
    
    // Document Info
    '{{DOCUMENT_ID}}' => 'STK-' . $stock_number . '-' . date('Y'),
    '{{TOTAL_TRANSACTIONS}}' => count($transactions_data),
    
    // Summary Information
    '{{TOTAL_QUANTITY}}' => $balance_qty,
    '{{TOTAL_VALUE}}' => number_format($balance_total, 2),
    
    // Transaction placeholders (will be used in loop)
    '{{TRANSACTION_DATE}}' => '',
    '{{REFERENCE_NUMBER}}' => '',
    '{{RECEIPT_QTY}}' => '',
    '{{RECEIPT_UNIT_COST}}' => '',
    '{{ISSUE_QTY}}' => '',
    '{{ISSUE_UNIT_COST}}' => '',
    '{{ISSUE_TOTAL_COST}}' => '',
    '{{OFFICE}}' => '',
    '{{BALANCE_QTY}}' => '',
    '{{BALANCE_TOTAL_COST}}' => '',
    '{{DAYS_TO_CONSUME}}' => '',
    '{{REMARKS}}' => '',
    
    // Messages
    '{{NO_TRANSACTIONS_MESSAGE}}' => empty($transactions_data) ? 'No transactions recorded for this item' : ''
];

// If you have a physical Word template file, you would load it here
// For now, we'll create a simple Word document with the data

header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"stock_card_{$stock_number}_{$current_date}.doc\"");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Stock Card - <?= $item_name ?></title>
<style>
    /* Basic styles for Word document */
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>
</head>
<body>

<h2>Stock Card Data Export</h2>
<p><strong>Template Used:</strong> <?= $template ?></p>

<!-- Display all data that would be mapped to placeholders -->
<div>
    <h3>Item Information</h3>
    <table>
        <tr><td><strong>Stock Number:</strong></td><td><?= $placeholders['{{STOCK_NUMBER}}'] ?></td></tr>
        <tr><td><strong>Item Name:</strong></td><td><?= $placeholders['{{ITEM_NAME}}'] ?></td></tr>
        <tr><td><strong>Description:</strong></td><td><?= $placeholders['{{DESCRIPTION}}'] ?></td></tr>
        <tr><td><strong>Unit of Measurement:</strong></td><td><?= $placeholders['{{UNIT_OF_MEASUREMENT}}'] ?></td></tr>
        <tr><td><strong>Fund Cluster:</strong></td><td><?= $placeholders['{{FUND_CLUSTER}}'] ?></td></tr>
        <tr><td><strong>Re-order Point:</strong></td><td><?= $placeholders['{{REORDER_POINT}}'] ?></td></tr>
    </table>
</div>

<?php if (!empty($transactions_data)): ?>
<div>
    <h3>Transaction Data</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Receipt Qty</th>
                <th>Unit Cost</th>
                <th>Issue Qty</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
                <th>Office</th>
                <th>Balance Qty</th>
                <th>Balance Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions_data as $transaction): ?>
            <tr>
                <td><?= date("m/d/Y", strtotime($transaction['date'])) ?></td>
                <td><?= $transaction['reference'] ?></td>
                <td><?= $transaction['receipt_qty'] ?></td>
                <td>₱<?= number_format($transaction['unit_cost'], 2) ?></td>
                <td><?= $transaction['issue_qty'] ?></td>
                <td>₱<?= number_format($transaction['issue_unit_cost'], 2) ?></td>
                <td>₱<?= number_format($transaction['issue_total_cost'], 2) ?></td>
                <td><?= $transaction['department'] ?></td>
                <td><?= $transaction['balance_qty'] ?></td>
                <td>₱<?= number_format($transaction['balance_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div>
    <h3>Summary</h3>
    <p><strong>Total Quantity:</strong> <?= $placeholders['{{TOTAL_QUANTITY}}'] ?></p>
    <p><strong>Total Value:</strong> ₱<?= $placeholders['{{TOTAL_VALUE}}'] ?></p>
    <p><strong>Generated on:</strong> <?= $placeholders['{{GENERATION_DATE}}'] ?></p>
</div>

<p><em>This data is ready for import into your STOCKCARD_Template.docx file.</em></p>

</body>
</html>