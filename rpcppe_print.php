<?php
$page_title = 'Printable RPCSPPE Report';
require_once('includes/load.php');
page_require_level(1);

// Get current user information
$current_user = current_user();
$current_user_name = $current_user['name'];
$current_user_position = $current_user['position'];

// Get filter values from POST
$subcategory_filter = isset($_POST['subcategory_id']) ? $_POST['subcategory_id'] : '';
$smpdate_filter = isset($_POST['smpdate_added']) ? $_POST['smpdate_added'] : '';
$smpfund_cluster_filter = isset($_POST['smpfund_cluster']) ? $_POST['smpfund_cluster'] : '';
$value_type_filter = isset($_POST['value_type']) ? $_POST['value_type'] : '';
$assumption_date_semi = isset($_POST['assumption_date_semi']) ? $_POST['assumption_date_semi'] : '';

// Query for Property tab
$sql_props = "SELECT * FROM `properties` WHERE 1=1";

// Filter by subcategory (if selected)
if ($subcategory_filter) {
    $sql_props .= " AND subcategory_id = '".$db->escape($subcategory_filter)."'";
}

// Filter by date acquired (like in semi)
if ($smpdate_filter) {
    $sql_props .= " AND DATE(date_acquired) <= '".$db->escape($smpdate_filter)."'";
}

// Filter by fund cluster
if ($smpfund_cluster_filter) {
    $sql_props .= " AND fund_cluster = '".$db->escape($smpfund_cluster_filter)."'";
}

// Optional value type filter for properties (like in semi)
if ($value_type_filter) {
    if ($value_type_filter == 'low') {
        $sql_props .= " AND unit_cost < 5000";
    } elseif ($value_type_filter == 'high') {
        $sql_props .= " AND unit_cost >= 5000 AND unit_cost < 50000";
    }
}

$sql_props .= " ORDER BY property_no ASC";

$props = find_by_sql($sql_props);

// Get signatory values
$signatory_fields = [
    'certified_correct_1_ppe', 'certified_correct_2_ppe', 'certified_correct_3_ppe',
    'certified_correct_4_ppe', 'certified_correct_5_ppe', 'certified_correct_6_ppe',
    'approved_by_ppe', 'verified_by_ppe'
];

$signatories = [];
foreach ($signatory_fields as $field) {
    if (isset($_POST[$field]) && $_POST[$field] != '') {
        $signatory = find_by_id('signatories', (int)$_POST[$field]);
        $signatories[$field] = $signatory ? $signatory['name'] : '';
    } else {
        $signatories[$field] = '';
    }
}

// Get subcategory name
$subcategory_name = '';
if ($subcategory_filter) {
    $subcat = find_by_id('subcategories', (int)$subcategory_filter);
    $subcategory_name = $subcat ? $subcat['subcategory_name'] : '';
}

// Calculate total amount
$total_amount = 0;
foreach ($props as $item) {
    $line_total = $item['unit_cost'] * $item['qty'];
    $total_amount += $line_total;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>REPORT ON PHYSICAL COUNT OF PROPERTY, PLANT, AND EQUIPMENT (RPCSPPE)</title>
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
        
        .ppe-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
            table-layout: fixed;
        }
        
        .ppe-table th,
        .ppe-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            word-wrap: break-word;
            height: 30px;
        }
        
        .ppe-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        /* Specific column widths for landscape */
        .col-date { width: 8%; }
        .col-property { width: 12%; }
        .col-unit { width: 6%; }
        .col-article { width: 10%; }
        .col-description { width: 20%; }
        .col-unit-price { width: 8%; }
        .col-total { width: 8%; }
        .col-card { width: 6%; }
        .col-physical { width: 8%; }
        .col-shortage { width: 8%; }
        .col-remarks { width: 6%; }
        
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
            padding: 5px;
            vertical-align: top;
            text-align: center;
            width: 33.33%;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
            min-height: 40px;
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
            .ppe-table {
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
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                margin: 20px auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>Republic of the Philippines</div>
        <div>Benguet State University - BOKOD CAMPUS</div>
        <div>2605 Bokod, Benguet</div>
        <div style="margin: 10px 0;"><strong>SUPPLY AND PROPERTY MANAGEMENT OFFICE</strong></div>
        <div class="main-title">REPORT ON PHYSICAL COUNT OF PROPERTY, PLANT, AND EQUIPMENT (RPCSPPE)</div>
        <div class="sub-title"><?php echo $subcategory_name; ?></div>
        <div class="sub-title">As of <?php echo date('F j, Y', strtotime($smpdate_filter)); ?></div>
    </div>

    <div style="margin-bottom: 10px; line-height: 1.4; font-size: 10px;">
        <strong>FUND:</strong> 
        <span class="underline" style="min-width: 120px; margin-left: 5px;"><?php echo $smpfund_cluster_filter; ?></span><br>
        <strong>For which:</strong> 
        <span class="underline" style="min-width: 140px; margin-left: 5px;"><?php echo $current_user_name; ?></span>, 
        <span class="underline" style="min-width: 120px; margin-left: 5px;"><?php echo $current_user_position; ?></span>, 
        is accountable, having assumed accountability on 
        <span class="underline" style="min-width: 100px; margin-left: 5px;"><?php echo date('F j, Y', strtotime($assumption_date_semi)); ?></span>
    </div>

    <table class="ppe-table">
        <thead>
            <tr>
                <th class="col-date">Date Acquired</th>
                <th class="col-property">Property Number</th>
                <th class="col-unit">Unit</th>
                <th class="col-article">ARTICLE</th>
                <th class="col-description">Description</th>
                <th class="col-unit-price">Unit Price</th>
                <th class="col-total">Total Amount</th>
                <th class="col-card">Quantity per Card</th>
                <th class="col-physical">Quantity Per Physical Count</th>
                <th class="col-shortage">SHORTAGE/OVERAGE</th>
                <th class="col-remarks">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($props as $item): ?>
            <tr>
                <td>
                    <?php 
                    echo !empty($item['date_acquired']) 
                        ? date('d-M-y', strtotime($item['date_acquired'])) 
                        : '-'; 
                    ?>
                </td>
                <td><?php echo $item['property_no']; ?></td>
                <td><?php echo $item['unit']; ?></td>
                <td><?php echo $item['article']; ?></td>
                <td><?php echo $item['description']; ?></td>
                <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                <td>₱<?php echo number_format($item['unit_cost'] * $item['qty'], 2); ?></td>
                <td><?php echo $item['qty']; ?></td>
                <td><?php echo $item['qty']; ?></td>
                <td>0</td>
                <td><?php echo $item['remarks'] ?? ''; ?></td>
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
                <td>&nbsp;</td>
            </tr>
            <?php endfor; ?>
            
            <!-- Total Row -->
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL: </td>
                <td>₱<?php echo number_format($total_amount, 2); ?></td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
        <tfoot>
    <tr>
        <!-- Certified Correct by -->
        <td colspan="5" style="text-align: left; vertical-align: top; border-right:none; margin-top:20px">
            <strong>Certified Correct by:</strong><br><br>

           <table style="width: 100%; border: none; border-collapse: collapse; text-align: center; font-size: 10px;margin-top:20px">
    <tr>
        <td style="width: 33%; padding: 5px; border: none;">
            <div style="font-weight: bold;"><?php echo $signatories['certified_correct_1_ppe']; ?></div>
            <div style="font-size: 9px;"><?php echo $signatories['certified_correct_1_ppe_position'] ?? ''; ?></div>
        </td>
        <td style="width: 33%; padding: 5px; border: none;">
            <div style="font-weight: bold;"><?php echo $signatories['certified_correct_2_ppe']; ?></div>
            <div style="font-size: 9px;"><?php echo $signatories['certified_correct_2_ppe_position'] ?? ''; ?></div>
        </td>
        <td style="width: 33%; padding: 5px; border: none;">
            <div style="font-weight: bold;"><?php echo $signatories['certified_correct_3_ppe']; ?></div>
            <div style="font-size: 9px;"><?php echo $signatories['certified_correct_3_ppe_position'] ?? ''; ?></div>
        </td>
    </tr>
    <tr>
        <td style="padding: 5px; border: none;">
            <div style="font-weight: bold;"><?php echo $signatories['certified_correct_4_ppe']; ?></div>
            <div style="font-size: 9px;"><?php echo $signatories['certified_correct_4_ppe_position'] ?? ''; ?></div>
        </td>
        <td style="padding: 5px; border: none;">
            <div style="font-weight: bold;"><?php echo $signatories['certified_correct_5_ppe']; ?></div>
            <div style="font-size: 9px;"><?php echo $signatories['certified_correct_5_ppe_position'] ?? ''; ?></div>
        </td>
        <td style="padding: 5px; border: none;">
            <div style="font-weight: bold;"><?php echo $signatories['certified_correct_6_ppe']; ?></div>
            <div style="font-size: 9px;"><?php echo $signatories['certified_correct_6_ppe_position'] ?? ''; ?></div>
        </td>
    </tr>
</table>

        </td>

        <!-- Approved by -->
        <td colspan="3" style="vertical-align: top; border-right:none;">
    <div style="text-align: left;">
        <strong>Approved by:</strong>
    </div>
    <br>
    <div style="text-align: center;margin-top:20px">
        <div style="font-weight: bold; font-size: 10px;">
            <?php echo $signatories['approved_by_ppe']; ?>
        </div>
        <div style="font-size: 9px;">University President</div>
    </div>
</td>

<!-- Verified by -->
<td colspan="3" style="vertical-align: top;">
    <div style="text-align: left;">
        <strong>Verified by:</strong>
    </div>
    <br>
    <div style="text-align: center;">
        <div style="font-weight: bold; font-size: 10px;margin-top:20px">
            <?php echo $signatories['verified_by_ppe']; ?>
        </div>
        <div style="font-size: 9px;">COA Representative</div>
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