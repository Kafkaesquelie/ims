<?php
$page_title = 'View ICS';
require_once('includes/load.php');
page_require_level(1);

$current_user = current_user();
$ics_id = (int)$_GET['id'];

// 🟩 Fetch specific ICS transaction
$sql = "
    SELECT 
        t.id,
        t.ics_no,
        t.item_id,
        p.inv_item_no,
        p.item AS item_name,
        p.item_description AS description,
        p.unit_cost,
        p.unit,
        p.estimated_use,
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
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.id = '{$ics_id}'
    LIMIT 1
";

$ics = find_by_sql($sql);
$ics = !empty($ics) ? $ics[0] : null;

if (!$ics) {
    $session->msg("d", "ICS record not found.");
    redirect('logs.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<title>Inventory Custodian Slip - <?php echo $ics['ics_no']; ?></title>
<style>
    body {
        font-family: 'Times New Roman', Times, serif;
        margin: 0;
        padding: 20px;
        background-color: #f8f9fa;
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }

    .ics-form {
        max-width: 800px;
        background: white;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        border: 1px solid #ddd;
    }

    .header {
        text-align: center;
        margin-bottom: 15px;
        padding-bottom: 5px;
        line-height: 1.1;
    }

    .header h5, .header h6 {
        margin: 2px 0;
        font-size: 14px;
    }

    .receipt-title {
        text-align: center;
        margin: 20px 0;
        font-family: "Times New Roman", Times, serif;
        font-size: 18px;
        font-weight: bold;
        text-transform: uppercase;
        color: black;
        background-color: #a4a4a4ff;
        padding: 3px;
    }

    .form-info {
        margin-bottom: 15px;
        font-size: 12px;
        line-height: 1.5;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        margin-bottom: 20px;
        table-layout: fixed;
    }

    table, th, td {
        border: 1px solid black;
    }

    th, td {
        padding: 6px;
        text-align: left;
        vertical-align: top;
    }

    th {
        background-color: #f0f0f0;
        text-align: center;
        font-weight: bold;
    }

    /* Fixed column widths */
    .col-qty { width: 8%; }
    .col-unit { width: 8%; }
    .col-unit-cost { width: 15%; }
    .col-total-cost { width: 15%; }
    .col-description { width: 25%; }
    .col-inv-no { width: 15%; }
    .col-useful-life { width: 15%; }

    .empty-row { height: 20px; }

    .signature-section {
        padding: 10px;
        text-align: center;
    }

    .signature-line {
        border-bottom: 1px solid #000;
        width: 180px;
        margin: 3px auto;
        padding-top: 15px;
    }

    .signature-label {
        font-size: 11px;
        margin-top: 2px;
    }

    /* 🖨 Print styles */
    @media print {
        .button-panel {
            display: none;
        }
        body {
            padding: 0;
            background: white;
        }
        .ics-form {
            box-shadow: none;
            border: none;
            padding: 10px;
        }
        .receipt-title {
            background-color: #a4a4a4ff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    /* 🟩 Circular Buttons */
.button-panel {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-right: 20px;
    position: sticky;
    top: 30px;
    height: fit-content;
}

.btn-print, .btn-back, .btn-word {
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
}

/* Colors */
.btn-print { background-color: #007bff; }
.btn-word  { background-color: #28a745; }
.btn-back  { background-color: #6c757d; }

/* Hover effect */
.btn-print:hover, .btn-word:hover, .btn-back:hover {
    transform: scale(1.1);
}

/* Hide when printing */
@media print {
    .button-panel {
        display: none;
    }
}

</style>
</head>
<body>

<!-- 🟦 Vertical Buttons -->
<div class="button-panel">
    <button onclick="window.print()" class="btn-circle btn-print" title="Print">
        <i class="fa-solid fa-print"></i>
    </button>

    <button class="btn-circle btn-word" onclick="saveAsWord()" title="Save as Word">
        <i class="fa-solid fa-file-word"></i>
    </button>

    <a href="logs.php" class="btn-circle btn-back d-flex align-items-center justify-content-center" title="Back">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
</div>

<div class="ics-form" id="ics-content">
    <!-- Header -->
    <div class="header">
        <h6>Republic of the Philippines</h6>
        <h5 style="color:#0bbc29ff">Benguet State University</h5>
        <h5 style="color:#0bbc29ff">BOKOD CAMPUS</h5>
        <h5>SUPPLY AND PROPERTY MANAGEMENT OFFICE</h5>
        <h6>Ambangeg, Daklan, Bokod, Benguet</h6>
    </div>

    <!-- Title -->
    <div class="receipt-title">INVENTORY CUSTODIAN SLIP</div>

    <!-- Fund Cluster and ICS No -->
    <div class="form-info">
        <div><strong>Fund Cluster:</strong> <?php echo $ics['fund_cluster']; ?></div>
        <div style="float:right; margin-top: -20px;"><strong>ICS No.:</strong> <?php echo $ics['ics_no']; ?></div>
        <div style="clear:both;"></div>
    </div>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th class="col-qty" rowspan="2">Qty</th>
                <th class="col-unit" rowspan="2">Unit</th>
                <th colspan="2">Amount</th>
                <th class="col-description" rowspan="2">Description</th>
                <th class="col-inv-no" rowspan="2">Inventory Item No</th>
                <th class="col-useful-life" rowspan="2">Estimated Useful Life</th>
            </tr>
            <tr>
                <th class="col-unit-cost">Unit Cost</th>
                <th class="col-total-cost">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-qty" style="text-align: center;"><?php echo $ics['quantity']; ?></td>
                <td class="col-unit" style="text-align: center;"><?php echo $ics['unit']; ?></td>
                <td class="col-unit-cost" style="text-align: right;">₱<?php echo number_format($ics['unit_cost'], 2); ?></td>
                <td class="col-total-cost" style="text-align: right;">₱<?php echo number_format($ics['unit_cost'] * $ics['quantity'], 2); ?></td>
                <td class="col-description">
                    <strong><?php echo $ics['item_name']; ?></strong>
                    <?php if (!empty($ics['description'])): ?>
                        <br><small><?php echo $ics['description']; ?></small>
                    <?php endif; ?>
                </td>
                <td class="col-inv-no" style="text-align: center;"><?php echo $ics['inv_item_no']; ?></td>
                <td class="col-useful-life" style="text-align: center;"><?php echo $ics['estimated_use']; ?></td>
            </tr>

            <!-- Empty Rows -->
            <?php for ($i = 0; $i < 12; $i++): ?>
                <tr class="empty-row">
                    <td class="col-qty"></td>
                    <td class="col-unit"></td>
                    <td class="col-unit-cost"></td>
                    <td class="col-total-cost"></td>
                    <td class="col-description"></td>
                    <td class="col-inv-no"></td>
                    <td class="col-useful-life"></td>
                </tr>
            <?php endfor; ?>
        </tbody>

        <!-- Signatures -->
        <tr>
            <td colspan="4" class="signature-section">
                <div style="text-align: left; margin-bottom: 8px;"><strong>Issued by:</strong></div>
                <div class="signature-line">
                    <?= strtoupper($current_user['name']); ?>
                </div>
                <div class="signature-label">Signature over Printed Name</div>

                <div class="signature-line">
                    <?= $current_user['position'] ?? 'Position'; ?>
                </div>
                <div class="signature-label">Position/Office</div>

                <div class="signature-line">
                    <?php echo date('M d, Y', strtotime($ics['transaction_date'])); ?>
                </div>
                <div class="signature-label">Date</div>
            </td>
            <td colspan="3" class="signature-section">
                <div style="text-align: left; margin-bottom: 8px;"><strong>Received by:</strong></div>
                <div class="signature-line">
                    <strong><?php echo strtoupper($ics['employee_name']); ?></strong> 
                </div>
                <div class="signature-label">Signature over Printed Name</div>

                <div class="signature-line">
                    <?php echo $ics['position']; ?>
                </div>
                <div class="signature-label">Position/Office</div>

                <div class="signature-line">
                    <?php echo date('M d, Y', strtotime($ics['transaction_date'])); ?>
                </div>
                <div class="signature-label">Date</div>
            </td>
        </tr>
    </table>
</div>

<script>
function saveAsWord() {
    const content = document.getElementById('ics-content').innerHTML;
    
    // Create Word document with proper styling
    const wordContent = `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' 
              xmlns:w='urn:schemas-microsoft-com:office:word' 
              xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset="utf-8">
            <title>ICS_<?php echo $ics['ics_no']; ?></title>
            <style>
                body { 
                    font-family: 'Times New Roman', Times, serif; 
                    margin: 15px; 
                }
                .receipt-title { 
                    background-color: #a4a4a4ff !important; 
                    color: black;
                    text-align: center;
                    font-size: 18px;
                    font-weight: bold;
                    text-transform: uppercase;
                    padding: 3px;
                    margin: 10px 0;
                }
                table { 
                    border-collapse: collapse; 
                    width: 100%; 
                    font-size: 12px;
                    table-layout: fixed;
                }
                th, td {  
                    border: 1px solid black; 
                    padding: 6px;
                }
                th { 
                    background-color: #f0f0f0 !important; 
                    text-align: center;
                    font-weight: bold;
                }
                .col-qty { width: 8%; }
                .col-unit { width: 8%; }
                .col-unit-cost { width: 12%; }
                .col-total-cost { width: 12%; }
                .col-description { width: 30%; }
                .col-inv-no { width: 15%; }
                .col-useful-life { width: 15%; }
                .signature-line { 
                    border-bottom: 1px solid black; 
                    width: 180px; 
                    margin: 3px auto;
                    padding-top: 15px;
                }
                .signature-label { 
                    font-size: 11px; 
                    margin-top: 2px;
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `;
    
    const blob = new Blob(['\ufeff', wordContent], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'ICS_<?php echo $ics['ics_no']; ?>.doc';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

</body>
</html>