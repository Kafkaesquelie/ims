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
    redirect('logs.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Property Acknowledgment Receipt - <?php echo $par['par_no']; ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Your existing CSS styles here */
body {
    font-family: 'Times New Roman', Times, serif;
    margin: 0;
    padding: 20px;
    background-color: #f8f9fa;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 25px;
}

.par-form {
    max-width: 800px;
    background: white;
    padding: 30px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
}

.header {
    text-align: center;
    margin-bottom: 20px;
    line-height: 1.2;
}
.header h6, .header h5, .header h4 {
    margin: 0;
    padding: 0;
}

.receipt-title {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    text-transform: uppercase;
    background-color: #8bde99ff;
    color: #000;
    padding: 5px;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-bottom: 25px;
}
th, td {
    border: 1px solid black;
    padding: 6px;
    vertical-align: top;
}
th {
    background-color: #f0f0f0;
    text-align: center;
    font-weight: bold;
}

.empty-row { height: 25px; }

.button-panel {
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: sticky;
    top: 30px;
    height: fit-content;
}

.btn-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: transform 0.2s, background-color 0.2s;
    text-decoration: none;
}

.btn-print { background-color: #007bff; }
.btn-word  { background-color: #28a745; }
.btn-back  { background-color: #6c757d; }

.btn-circle:hover { transform: scale(1.1); }

@media print {
    .button-panel { display: none; }
    body { background: white; }
    .par-form { box-shadow: none; border: none; }
}
</style>
</head>

<body>

<!-- 🟩 Vertical Button Panel (Left of Form) -->
<div class="button-panel">
    <button onclick="window.print()" class="btn-circle btn-print" title="Print">
        <i class="fa-solid fa-print"></i>
    </button>

    <a href="export_par.php?id=<?php echo $par['id']; ?>" class="btn-circle btn-word" title="Export using Template">
        <i class="fa-solid fa-file-word"></i>
    </a>

    <a href="logs.php" class="btn-circle btn-back" title="Back">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
</div>

<!-- 🟩 Form Content -->
<div class="par-form" id="par-content">
    <div class="header">
        <h6>Republic of the Philippines</h6>
        <h5 style="color:#0bbc29ff;">Benguet State University</h5>
        <h4 style="color:#0bbc29ff;">BOKOD CAMPUS</h4>
        <h4>SUPPLY AND PROPERTY MANAGEMENT OFFICE</h4>
        <h5>Ambangeg, Daklan, Bokod, Benguet</h5>
    </div>

    <div class="receipt-title">Property Acknowledgment Receipt</div>

    <div style="margin-bottom: 10px;">
        <strong>Fund Cluster:</strong> <?php echo $par['fund_cluster']; ?>
        <span style="float:right;"><strong>PAR No.:</strong> <?php echo $par['par_no']; ?></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Qty</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Property No.</th>
                <th>Date Acquired</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center"><?php echo $par['quantity']; ?></td>
                <td align="center"><?php echo $par['unit']; ?></td>
                <td><?php echo $par['item_name'] . ' - ' . $par['description']; ?></td>
                <td align="center"><?php echo $par['property_no']; ?></td>
                <td align="center"><?php echo date('M d, Y', strtotime($par['date_acquired'])); ?></td>
                <td align="right">₱<?php echo number_format($par['unit_cost'] * $par['quantity'], 2); ?></td>
            </tr>
            <?php for ($i=0; $i<12; $i++): ?>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>

        <tr>
            <td colspan="3" style="padding:10px;">
                <strong>Received by:</strong><br><br>
                <div style="border-bottom:1px solid #000; width:200px; margin:auto;text-align:center"><?php echo strtoupper($par['employee_name']); ?></div>
                <div style="text-align:center; font-size:11px;">Signature over Printed Name</div>
                <div style="border-bottom:1px solid #000; width:150px; margin:auto;text-align:center"><?php echo $par['position']; ?></div>
                <div style="text-align:center; font-size:11px;">Position/Office</div>
                <div style="border-bottom:1px solid #000; width:120px; margin:8px auto;text-align:center"><?php echo date('M d, Y', strtotime($par['transaction_date'])); ?></div>
                <div style="text-align:center; font-size:11px;">Date</div>
            </td>
            <td colspan="3" style="padding:10px;">
                <strong>Issued by:</strong><br><br>
                <div style="border-bottom:1px solid #000; width:200px; margin:auto;text-align:center"><?php echo strtoupper($current_user['name']); ?></div>
                <div style="text-align:center; font-size:11px;">Signature over Printed Name</div>
                <div style="border-bottom:1px solid #000; width:150px; margin:auto;text-align:center"><?php echo $current_user['position'] ?? 'Position'; ?></div>
                <div style="text-align:center; font-size:11px;">Position/Office</div>
                <div style="border-bottom:1px solid #000; width:120px; margin:8px auto;text-align:center"><?php echo date('M d, Y', strtotime($par['transaction_date'])); ?></div>
                <div style="text-align:center; font-size:11px;">Date</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>