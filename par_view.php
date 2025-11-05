<?php
$page_title = 'View PAR';
require_once('includes/load.php');
if (!$session->isUserLoggedIn()) {
  header("Location: admin.php");
  exit();
}
page_require_level(1);

$current_user = current_user();

// Get the PAR number from URL parameter
$par_no = isset($_GET['par_no']) ? trim($db->escape($_GET['par_no'])) : null;

if (!$par_no) {
    $session->msg("d", "No PAR number provided.");
    redirect('logs.php');
}

// 🟩 Fetch ALL transaction details using PAR number (REMOVED LIMIT 1)
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
    LEFT JOIN properties p ON t.properties_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.par_no = '{$par_no}'
";

$par_items = find_by_sql($sql);

if (!$par_items) {
    $session->msg("d", "PAR record not found for PAR No: {$par_no}");
    redirect('logs.php');
}

// Get first item for header info
$first_item = $par_items[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Property Acknowledgment Receipt - <?php echo $first_item['par_no']; ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* A4 Size with 1-inch margins */
@page {
    size: A4;
    margin: 1in;
}

body {
    font-family: 'Times New Roman', Times, serif;
    margin: 0;
    padding: 0;
    background-color: #f8f9fa;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 25px;
}

.par-form {
    width: 8.5in; /* A4 width minus 2 inches for margins */
    min-height: 11in; /* A4 height minus 2 inches for margins */
    background: white;
    padding: 1in; /* 1 inch margin */
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
    box-sizing: border-box;
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

.header h6 {
    font-size: 12px;
    margin-bottom: 2px;
}

.header h5 {
    font-size: 13px;
    margin-bottom: 2px;
}

.header h4 {
    font-size: 14px;
    margin-bottom: 2px;
}

.receipt-title {
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    text-transform: uppercase;
    background-color: #8bde99ff;
    color: #000;
    padding: 8px;
    margin: 20px 0;
    border: 1px solid #000;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-bottom: 20px;
    table-layout: fixed;
}
th, td {
    border: 1px solid black;
    padding: 6px;
    vertical-align: top;
    word-wrap: break-word;
}
th {
    background-color: #f0f0f0;
    text-align: center;
    font-weight: bold;
}

/* Fixed column widths for better layout */
th:nth-child(1), td:nth-child(1) { width: 8%; }  /* Qty */
th:nth-child(2), td:nth-child(2) { width: 8%; }  /* Unit */
th:nth-child(3), td:nth-child(3) { width: 35%; } /* Description */
th:nth-child(4), td:nth-child(4) { width: 15%; } /* Property No */
th:nth-child(5), td:nth-child(5) { width: 14%; } /* Date Acquired */
th:nth-child(6), td:nth-child(6) { width: 20%; } /* Amount */

.empty-row { 
    height: 20px; 
}

.empty-row td {
    border: 1px solid black;
}

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
.btn-excel { background-color: #217346; }
.btn-back  { background-color: #6c757d; }

.btn-circle:hover { transform: scale(1.1); }

/* Print Styles */
@media print {
    @page {
        size: A4;
        margin: 1in;
    }
    
    body {
        background: white;
        margin: 0;
        padding: 0;
        display: block;
    }
    
    .button-panel { 
        display: none; 
    }
    
    .par-form {
        width: auto;
        min-height: auto;
        margin: 0;
        padding: 1in;
        box-shadow: none;
        border: none;
        page-break-after: always;
    }
    
    .receipt-title {
        background-color: #8bde99ff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    th {
        background-color: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Ensure proper spacing for signatures */
.signature-section {
    padding: 5px;
}

.signature-line {
    border-bottom: 1px solid #000;
    width: 180px;
    margin: 3px auto;
    padding-top: 15px;
    text-align: center;
}

.signature-label {
    font-size: 10px;
    margin-top: 2px;
    text-align: center;
}

/* Fund cluster and PAR number styling */
.doc-info {
    margin-bottom: 15px;
    font-size: 12px;
}

.doc-info strong {
    font-size: 12px;
}
</style>
</head>

<body>

<!-- 🟩 Vertical Button Panel (Left of Form) -->
<div class="button-panel">
    <button onclick="window.print()" class="btn-circle btn-print" title="Print">
        <i class="fa-solid fa-print"></i>
    </button>

    <a href="export_par.php?par_no=<?php echo urlencode($first_item['par_no']); ?>" class="btn-circle btn-word" title="Export using Template">
        <i class="fa-solid fa-file-word"></i>
    </a>

    <a href="export_par_excel.php?par_no=<?php echo urlencode($first_item['par_no']); ?>" class="btn-circle btn-excel" title="Export to Excel">
        <i class="fa-solid fa-file-excel"></i>
    </a>

    <a href="logs.php" class="btn-circle btn-back" title="Back">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
</div>

<!-- 🟩 Form Content - A4 Size with 1-inch margins -->
<div class="par-form" id="par-content">
    <div class="header">
        <h6>Republic of the Philippines</h6>
        <h5 style="color:#0bbc29ff;">Benguet State University</h5>
        <h4 style="color:#0bbc29ff;">BOKOD CAMPUS</h4>
        <h4>SUPPLY AND PROPERTY MANAGEMENT OFFICE</h4>
        <h5>Ambangeg, Daklan, Bokod, Benguet</h5>
    </div>

    <div class="receipt-title">Property Acknowledgment Receipt</div>

    <div class="doc-info">
        <strong>Fund Cluster:</strong> <?php echo $first_item['fund_cluster']; ?>
        <span style="float:right;"><strong>PAR No.:</strong> <?php echo $first_item['par_no']; ?></span>
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
            <?php foreach ($par_items as $item): ?>
            <tr>
                <td align="center"><?php echo $item['quantity']; ?></td>
                <td align="center"><?php echo $item['unit']; ?></td>
                <td>
                    <strong><?php echo $item['item_name']; ?></strong>
                    <?php if (!empty($item['description'])): ?>
                        <br><small><?php echo $item['description']; ?></small>
                    <?php endif; ?>
                </td>
                <td align="center"><?php echo $item['property_no']; ?></td>
                <td align="center"><?php echo !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : 'N/A'; ?></td>
                <td align="right">₱<?php echo number_format($item['unit_cost'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i=0; $i<12; $i++): ?>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>

        <tr>
            <td colspan="3" class="signature-section">
                <strong>Received by:</strong><br><br>
                <div class="signature-line"><?php echo strtoupper($first_item['employee_name']); ?></div>
                <div class="signature-label">Signature over Printed Name</div>
                <div class="signature-line"><?php echo $first_item['position']; ?></div>
                <div class="signature-label">Position/Office</div>
                <div class="signature-line"><?php echo date('M d, Y', strtotime($first_item['transaction_date'])); ?></div>
                <div class="signature-label">Date</div>
            </td>
            <td colspan="3" class="signature-section">
                <strong>Issued by:</strong><br><br>
                <div class="signature-line"><?php echo strtoupper($current_user['name']); ?></div>
                <div class="signature-label">Signature over Printed Name</div>
                <div class="signature-line"><?php echo $current_user['position'] ?? 'Position'; ?></div>
                <div class="signature-label">Position/Office</div>
                <div class="signature-line"><?php echo date('M d, Y', strtotime($first_item['transaction_date'])); ?></div>
                <div class="signature-label">Date</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>