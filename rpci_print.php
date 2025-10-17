<?php
$page_title = 'Printable RPCI Report';
require_once('includes/load.php');
page_require_level(1);

// Get current user information
$current_user = current_user();
$current_user_name = $current_user['name'];
$current_user_position = $current_user['position'];



// Get filter values from POST
$category_filter = isset($_POST['categorie_id']) && $_POST['categorie_id'] != '' ? $_POST['categorie_id'] : null;
$date_filter = isset($_POST['date_added']) && $_POST['date_added'] != '' ? $_POST['date_added'] : null;
$fund_cluster_filter = isset($_POST['fund_cluster']) && $_POST['fund_cluster'] != '' ? $_POST['fund_cluster'] : null;
$assumption_date = isset($_POST['assumption_date']) && $_POST['assumption_date'] != '' ? $_POST['assumption_date'] : '';

// Get signatory values
$certified_correct_by = isset($_POST['certified_correct_by']) ? $_POST['certified_correct_by'] : '';
$approved_by = isset($_POST['approved_by']) ? $_POST['approved_by'] : '';
$verified_by = isset($_POST['verified_by']) ? $_POST['verified_by'] : '';

// Query with filters
$sql = "
  SELECT i.*, u.symbol AS unit_name
  FROM items i
  LEFT JOIN units u ON i.unit_id = u.id
  WHERE 1=1"; // ✅ Start WHERE clause

if ($category_filter) {
    $sql .= " AND i.categorie_id = '" . $db->escape($category_filter) . "'";
}
if ($date_filter) {
    $sql .= " AND DATE(i.date_added) <= '" . $db->escape($date_filter) . "'";
}
if ($fund_cluster_filter) {
    $sql .= " AND i.fund_cluster = '" . $db->escape($fund_cluster_filter) . "'";
}


$items = find_by_sql($sql);

// Get signatory names
$certified_correct_name = '';
if ($certified_correct_by) {
    $signatory = find_by_id('signatories', (int)$certified_correct_by);
    $certified_correct_name = $signatory ? $signatory['name'] : '';
}

$approved_by_name = '';
if ($approved_by) {
    $signatory = find_by_id('signatories', (int)$approved_by);
    $approved_by_name = $signatory ? $signatory['name'] : '';
}

$verified_by_name = '';
if ($verified_by) {
    $signatory = find_by_id('signatories', (int)$verified_by);
    $verified_by_name = $signatory ? $signatory['name'] : '';
}

// Get category name
$category_name = '';
if ($category_filter) {
    $category = find_by_id('categories', (int)$category_filter);
    $category_name = $category ? $category['name'] : '';
}

// Get fund cluster name
$fund_cluster_name = $fund_cluster_filter;
?>
<!DOCTYPE html>
<html>

<head>
    <title>REPORT ON THE PHYSICAL COUNT OF INVENTORIES</title>
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
            font-size: 14px;
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
            font-size: 15px;
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

        tfoot td {
            padding: 6px;
            font-size: 10px;
            vertical-align: top;
            height: 100px;

        }

        .tfoot-label {
            font-weight: bold;
            text-align: left;
            padding-left: 5px;
            font-size: 9px;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
            table-layout: fixed;
        }

        .inventory-table th,
        .inventory-table td {
            border: 1px solid #000;
            padding: px;
            text-align: center;
            word-wrap: break-word;
            height: 15px;
        }

        .inventory-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        /* Specific column widths for landscape */
        .col-article {
            width: 8%;
        }

        .col-description {
            width: 20%;
        }

        .col-stock {
            width: 10%;
        }

        .col-uom {
            width: 8%;
        }

        .col-value {
            width: 8%;
        }

        .col-balance {
            width: 10%;
        }

        .col-onhand {
            width: 10%;
        }

        .col-shortage {
            width: 10%;
        }

        .col-remarks {
            width: 15%;
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
            margin-top: 1px;
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

            .inventory-table {
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
        <div class="main-title">REPORT ON THE PHYSICAL COUNT OF INVENTORIES</div>
        <div class="sub-title">
            <span class="underline"><?php echo $category_name; ?></span><br>
            (Type of Inventory Item)
        </div>
        <div class="sub-title">
            As at <span class="underline"><?php echo date('F j, Y', strtotime($date_filter)); ?></span>
        </div>
    </div>

    <div style="margin-bottom: 10px; line-height: 1.4; font-size: 12px;">
        <strong>Fund Cluster:</strong>
        <span class="underline" style="min-width: 120px; margin-left: 5px;margin-bottom:2px;"><?php echo $fund_cluster_name; ?></span><br>
        <strong>For which</strong>
        <span class="underline" style="min-width: 130px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
        <span class="underline" style="min-width: 120px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
        BSU-BOKOD CAMPUS is accountable, having assumed such accountability on
        <span class="underline" style="min-width: 100px; margin-left: 5px;"><?php echo date('F j, Y', strtotime($assumption_date)); ?></span>
    </div>

    <table class="inventory-table">
        <thead>
            <tr>
                <th class="col-article">Article</th>
                <th class="col-description">Description</th>
                <th class="col-stock">Stock Number</th>
                <th class="col-uom">Unit of Measure</th>
                <th class="col-value">Unit Value</th>
                <th class="col-balance">Balance Per Card (Quantity)</th>
                <th class="col-onhand">On Hand Per Count (Quantity)</th>
                <th colspan="2" class="col-shortage">Shortage/Overage Quantity</th>
                <th class="col-remarks">Remarks</th>
            </tr>
            <tr>
                <th colspan="7"></th>
                <th>Quantity</th>
                <th>Value</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['name']; ?></td>
                    <td><?php echo $item['stock_card']; ?></td>
                    <td><?php echo $item['unit_name']; ?></td>
                    <td><?php echo $item['unit_cost']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>

            <!-- Add empty rows -->
            <?php for ($i = 0; $i < 10; $i++): ?>
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
        </tbody>
        <tfoot>
            <tr>
                <!-- First signatory -->
                <td colspan="4" class="tfoot-label" style="border-right:none;">
                    Certified Correct by:<br><br>
                    <div style="text-align: center;">
                        <strong><?php echo $certified_correct_name; ?></strong>
                        <div class="signature-line" style="margin: 5px auto;"></div>
                        <div class="signature-caption" style="text-align: center;margin-bottom:5px">
                            Signature over Printed Name of Inventory Committee Chair and Members
                        </div>
                    </div>
                </td>

                <!-- Second signatory -->
                <td colspan="3" class="tfoot-label" style="border-right:none;">
                    Approved by:<br><br>
                    <div style="text-align: center;">
                        <strong><?php echo $approved_by_name; ?></strong>
                        <div class="signature-line" style="margin: 5px auto;"></div>
                        <div class="signature-caption" style="text-align: center; margin-bottom:5px">
                            Signature over Printed Name of Head of Agency/Entity or Authorized Representative
                        </div>
                    </div>
                </td>

                <!-- Third signatory -->
                <td colspan="4" class="tfoot-label">
                    Verified by:<br><br>
                    <div style="text-align: center;">
                        <strong><?php echo $verified_by_name; ?></strong>
                        <div class="signature-line" style="margin: 5px auto;"></div>
                        <div class="signature-caption" style="text-align: center;margin-bottom:5px">
                            Signature over Printed Name of COA Representative
                        </div>
                    </div>
                </td>
            </tr>
        </tfoot>


    </table>



    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>