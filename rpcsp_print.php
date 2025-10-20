<?php
$page_title = 'Printable RPCSP Report';
require_once('includes/load.php');
page_require_level(1);

// Get current user information
$current_user = current_user();
$current_user_name = $current_user['name'];
$current_user_position = $current_user['position'];

// Get filter values from POST
$semicategory_filter = isset($_POST['semicategory_id']) ? $_POST['semicategory_id'] : '';
$smpdate_filter = isset($_POST['smpdate_added']) ? $_POST['smpdate_added'] : '';
$smpfund_cluster_filter = isset($_POST['smpfund_cluster']) ? $_POST['smpfund_cluster'] : '';
$value_type_filter = isset($_POST['value_type']) ? $_POST['value_type'] : '';
$assumption_date_semi = isset($_POST['assumption_date_semi']) ? $_POST['assumption_date_semi'] : '';

// Query for Semi-Expendable Property tab
$sql_semi = "SELECT * FROM `semi_exp_prop` WHERE 1=1";
if ($semicategory_filter) {
    $sql_semi .= " AND semicategory_id = '" . $db->escape($semicategory_filter) . "'";
}
if ($smpdate_filter) {
    $sql_semi .= " AND DATE(date_added) <= '" . $db->escape($smpdate_filter) . "'";
}
if ($smpfund_cluster_filter) {
    $sql_semi .= " AND fund_cluster = '" . $db->escape($smpfund_cluster_filter) . "'";
}
if ($value_type_filter) {
    if ($value_type_filter == 'low') {
        $sql_semi .= " AND unit_cost < 5000";
    } elseif ($value_type_filter == 'high') {
        $sql_semi .= " AND unit_cost >= 5000 AND unit_cost < 50000";
    }
}
$sql_semi .= " ORDER BY inv_item_no ASC";

$semi_items = find_by_sql($sql_semi);

// Get signatory values
$certified_correct_by_semi = isset($_POST['certified_correct_by_semi']) ? $_POST['certified_correct_by_semi'] : '';
$approved_by_semi = isset($_POST['approved_by_semi']) ? $_POST['approved_by_semi'] : '';
$witnessed_by_semi = isset($_POST['witnessed_by_semi']) ? $_POST['witnessed_by_semi'] : '';

// Get signatory names
$certified_correct_name_semi = '';
if ($certified_correct_by_semi) {
    $signatory = find_by_id('signatories', (int)$certified_correct_by_semi);
    $certified_correct_name_semi = $signatory ? $signatory['name'] : '';
}

$approved_by_name_semi = '';
if ($approved_by_semi) {
    $signatory = find_by_id('signatories', (int)$approved_by_semi);
    $approved_by_name_semi = $signatory ? $signatory['name'] : '';
}

$witnessed_by_name_semi = '';
if ($witnessed_by_semi) {
    $signatory = find_by_id('signatories', (int)$witnessed_by_semi);
    $witnessed_by_name_semi = $signatory ? $signatory['name'] : '';
}

// Get semicategory name
$semicategory_name = '';
if ($semicategory_filter) {
    $semicat = find_by_id('semicategories', (int)$semicategory_filter);
    $semicategory_name = $semicat ? $semicat['semicategory_name'] : '';
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>REPORT ON THE PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY (RPCSP)</title>
    <style>
        @page {
            size: landscape;
            margin: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 12px;
            width: 297mm;
            min-height: 210mm;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-top: 10px;
        }

        .main-title {
            font-family: 'Times New Roman', serif;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .sub-title {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            text-align: center;
            height: 16px;
            vertical-align: bottom;
        }

        .semi-expendable-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
            table-layout: fixed;
        }

        .semi-expendable-table th,
        .semi-expendable-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            word-wrap: break-word;
            height: 30px;
        }

        .semi-expendable-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        /* Specific column widths for landscape */
        .col-article {
            width: 8%;
        }

        .col-description {
            width: 18%;
        }

        .col-property-no {
            width: 12%;
        }

        .col-uom {
            width: 8%;
        }

        .col-value {
            width: 8%;
        }

        .col-balance {
            width: 8%;
        }

        .col-onhand {
            width: 8%;
        }

        .col-shortage {
            width: 10%;
        }

        .col-remarks {
            width: 12%;
        }

        .certifications-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .certifications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .certifications-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
            text-align: center;
            width: 33.33%;
            height: 80px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-caption {
            font-size: 8px;
            margin-top: 3px;
            line-height: 1.1;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
                width: 297mm;
                height: 210mm;
            }

            .semi-expendable-table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .certifications-section {
                page-break-inside: avoid;
            }
        }

        @media screen {
            body {
                border: 1px solid #ccc;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                margin: 20px auto;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">


            <!-- Header Text -->
            <div style="flex: 1; text-align: center;">

                <div class="main-title">REPORT ON PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY (RPCSP)</div>
                <div class="sub-title">
                    <span class="underline"><?php echo $semicategory_name; ?></span><br>
                    (Type of Semi-expendable Property)
                </div>
                <div class="sub-title">As of <?php echo date('F j, Y', strtotime($smpdate_filter)); ?></div>
            </div>


        </div>
    </div>


    <div style="margin-bottom: 10px; line-height: 1.4; font-size: 10px;">
        <strong>Fund Cluster:</strong>
        <span class="underline" style="min-width: 120px; margin-left: 5px;"><?php echo $smpfund_cluster_filter; ?></span><br>
        <strong>For which</strong>
        <span class="underline" style="min-width: 140px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
        <span class="underline" style="min-width: 120px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
        BSU-BOKOD CAMPUS is accountable, having assumed such accountability on
        <span class="underline" style="min-width: 100px; margin-left: 5px;"><?php echo date('F j, Y', strtotime($assumption_date_semi)); ?></span>
    </div>

    <table class="semi-expendable-table">
        <thead>
            <tr>
                <th class="col-article" rowspan="2">ARTICLE</th>
                <th class="col-description" rowspan="2">Description</th>
                <th class="col-property-no" rowspan="2">Semi-expendable Property No.</th>
                <th class="col-uom" rowspan="2">Unit of Measure</th>
                <th class="col-value" rowspan="2">Unit Value</th>
                <th colspan="1">Balance per Card</th>
                <th colspan="1">On Hand Per Count</th>
                <th colspan="2">Shortage/Overage</th>
                <th class="col-remarks" rowspan="2">Remarks</th>
            </tr>
            <tr>
                <th>Qty</th>
                <th>Qty</th>
                <th>Qty</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($semi_items as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['item_description']; ?></td>
                    <td><?php echo $item['inv_item_no']; ?></td>
                    <td><?php echo $item['unit']; ?></td>
                    <td><?php echo $item['total_qty']; ?></td>
                    <td><?php echo $item['qty_left']; ?></td>
                    <td></td>
                    <td>0</td>
                    <td>₱0.00</td>
                    <td></td>
                </tr>
            <?php endforeach; ?>

            <!-- Add empty rows -->
            <?php for ($i = 0; $i < 5; $i++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
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
            <!-- ✅ Signatories directly inside tbody -->
            <tr>
                <!-- Certified Correct -->
                <td colspan="3" style="text-align:center;">
                    <div style="text-align:left;">Certified Correct by:</div>
                    <strong><?php echo $certified_correct_name_semi; ?></strong><br>
                    <div style="border-top:1px solid #000; width:80%; margin:auto;"></div>
                    <p>Signature over Printed Name of Inventory Committee Chair and Members</p>
                </td>

                <!-- Approved by -->
                <td colspan="4" style="text-align:center;">
                    <div style="text-align:left;">Approved by:</div>
                    <strong><?php echo $approved_by_name_semi; ?></strong><br>
                    <div style="border-top:1px solid #000; width:80%; margin:auto;"></div>
                    <p>Signature over Printed Name of Head of Agency/Entity or Authorized Representative</p>
                </td>

                <!-- Witnessed by -->
                <td colspan="3" style="text-align:center;">
                    <div style="text-align:left;">Witnessed by:</div>
                    <strong><?php echo $witnessed_by_name_semi; ?></strong><br>
                    <div style="border-top:1px solid #000; width:80%; margin:auto;"></div>
                    <p>Signature over Printed Name of COA Representative</p>
                </td>
            </tr>

        </tbody>
    </table>



    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>