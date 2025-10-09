<?php
$page_title = 'View PAR';
require_once('includes/load.php');
page_require_level(1);


$current_user = current_user();
$par_id = (int)$_GET['id'];

// 🟩 Fetch transaction details
$sql = "
    SELECT 
        t.id,
        t.par_no,
        t.item_id,
        p.property_no,
        p.article AS item_name,
        p.description,
        p.unit_cost,
        p.date_acquired,
        p.unit,
        p.fund_cluster,
        t.quantity,
        t.transaction_date,
        t.status,
        t.remarks,
        CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
        e.position,
        e.office,
        e.image
    FROM transactions t
    LEFT JOIN properties p ON t.item_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.id = '{$par_id}'
    LIMIT 1
";

$par = find_by_sql($sql);
$par = !empty($par) ? $par[0] : null;

if (!$par) {
    $session->msg("d", "PAR record not found.");
    redirect('transactions.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Acknowledgment Receipt - <?php echo $par['par_no']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .par-form {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            line-height: 1.2;
        }
        .header h1, .header h2, .header h3, .header h4, .header h5, .header h6 {
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        .header h6 { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .header h5 { font-size: 13px; font-weight: bold; margin-bottom: 2px; }
        .header h4 { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
        .receipt-title {
            text-align: center;
            margin-bottom: 20px;
            font-family: "Times New Roman", Times, serif;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 30px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .quantity-col { width: 8%; }
        .unit-col { width: 12%; }
        .description-col { width: 30%; }
        .property-col { width: 15%; }
        .date-col { width: 15%; }
        .amount-col { width: 15%; }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-box {
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid black;
            margin-top: 60px;
            padding-top: 5px;
            text-align: center;
            font-weight: bold;
        }
        .signature-label {
            font-size: 11px;
            margin-top: 5px;
            text-align: center;
        }
        .position-office {
            font-size: 11px;
            margin-top: 5px;
            text-align: center;
        }
        .date-line {
            margin-top: 10px;
            font-size: 11px;
            text-align: center;
        }
        .empty-row {
            height: 25px;
        }
        .fund-cluster {
            margin-bottom: 20px;
        }
        .fund-cluster span {
            margin-right: 20px;
        }
        .print-section {
            text-align: center;
            margin-top: 20px;
        }
        .btn-print {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back {
            margin-left: 10px;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="par-form">
        <!-- Header Section -->
        <div class="header">
            <h6>Republic of the Philippines</h6>
            <h5>Benguet State University</h5>
            <h4>BOKOD CAMPUS</h4>
            <h4>SUPPLY AND PROPERTY MANAGEMENT OFFICE</h4>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">
            PROPERTY ACKNOWLEDGMENT RECEIPT
        </div>

        <!-- Fund Cluster and PAR No -->
        <div class="fund-cluster">
            <span>Fund Cluster: <strong><?php echo $par['fund_cluster']; ?></strong></span>
            <span style="float:right;">PAR No.: <strong><?php echo $par['par_no']; ?></strong></span>
        </div>

        <!-- Main Table -->
        <table>
            <thead>
                <tr>
                    <th class="quantity-col">Quantity</th>
                    <th class="unit-col">Unit</th>
                    <th class="description-col">Description</th>
                    <th class="property-col">Property Number</th>
                    <th class="date-col">Date Acquired</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- First row with data -->
                <tr>
                    <td style="text-align: center;"><?php echo $par['quantity']; ?></td>
                    <td style="text-align: center;"><?php echo $par['unit']; ?></td>
                    <td><?php echo $par['item_name'] . ' - ' . $par['description']; ?></td>
                    <td style="text-align: center;"><?php echo $par['property_no']; ?></td>
                    <td style="text-align: center;"><?php echo date('M d, Y', strtotime($par['date_acquired'])); ?></td>
                    <td style="text-align: right;">₱<?php echo number_format($par['unit_cost'] * $par['quantity'], 2); ?></td>
                </tr>
                <!-- Second row with "nothing follows" -->
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <!-- Empty rows -->
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            </tbody>
          <tr>
    <td colspan="3" style="padding:15px;">
        <div class="mb-2 text-left">Recieved by:</div>
        <div style="border-bottom:1px solid #000; width:200px; margin:auto; text-align:center;">
            <?php echo strtoupper($par['employee_name']); ?>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
            <span style="flex: 1; text-align: center;">Signature over Printed Name of End User</span>
        </div>

        <div style="display: flex; justify-content: center; margin-top: 2px; font-size: 12px;">
            <span style="border-bottom:1px solid #000; width:150px; text-align: center; padding: 0 5px;">
                <?php echo $par['position']; ?>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
            <span style="flex: 1; text-align: center;">Position/Office</span>
        </div>
        <div style="display: flex; justify-content: center; margin-top: 10px; font-size: 12px;">
            <span style="border-bottom:1px solid #000; width:120px; text-align: center; padding: 0 5px;">
                <?php echo date('M d, Y', strtotime($par['transaction_date'])); ?>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
             <span style="flex: 1; text-align: center;">Date</span>
        </div>  
    </td>
    <td colspan="3" style="padding:15px;">
        <div class="mb-2 text-left">Issued by:</div>
        <div style="border-bottom:1px solid #000; width:200px; margin:auto; text-align:center;">
            <?= $current_user['name']; ?>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
            <span style="flex: 1; text-align: center;">Signature over Printed Name of Supply and/or Property Custodian</span>
        </div>
        <div style="display: flex; justify-content: center; margin-top: 2px; font-size: 12px;">
            <span style="border-bottom:1px solid #000; width:150px; text-align: center; padding: 0 5px;">
                <?= $current_user['position'] ?? 'Position'; ?>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
            <span style="flex: 1; text-align: center;">Position/Office</span>
        </div>
        <div style="display: flex; justify-content: center; margin-top: 10px; font-size: 12px;">
            <span style="border-bottom:1px solid #000; width:120px; text-align: center; padding: 0 5px;">
                <?php echo date('M d, Y', strtotime($par['transaction_date'])); ?>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 2px; font-size: 12px;">
             <span style="flex: 1; text-align: center;">Date</span>
        </div>  
    </td>
</tr>
        </table>

   

    <!-- Print and Back Buttons -->
    <div class="print-section">
        <button onclick="window.print()" class="btn-print">Print PAR</button>
        <a href="logs.php" class="btn-back">Back to Transactions</a>
    </div>
</body>
</html>