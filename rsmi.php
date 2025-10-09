<?php
$page_title = 'Report of Supplies and Materials Issued';
require_once('includes/load.php');
page_require_level(1); // Only admins

$signatories = find_by_sql("
    SELECT id, name, position, agency 
    FROM signatories 
    ORDER BY name ASC
");

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
$current_user = current_user(); 

// Fetch approved request items along with item details
$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;

$where_date = '';
if ($report_date) {
    $where_date = " AND DATE(r.date) = '" . $db->escape($report_date) . "'";
}

$where_cluster = '';
if ($selected_cluster && $selected_cluster !== 'all') {
    $where_cluster = " AND i.fund_cluster = '" . $db->escape($selected_cluster) . "'";
}

$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : '';

$issued_items = find_by_sql("
    SELECT 
        r.id ,
        r.ris_no,
        i.stock_card,
        i.name AS item_name,
        i.UOM AS unit,
        ri.qty AS qty_issued,
        i.unit_cost,
        (ri.qty * i.unit_cost) AS amount,
        i.fund_cluster
    FROM request_items ri
    JOIN requests r ON ri.req_id = r.id
    JOIN items i ON ri.item_id = i.id
    WHERE r.status = 'Approved'
      AND i.stock_card IS NOT NULL
      AND i.stock_card != ''
      $where_date
      $where_cluster
    ORDER BY r.date ASC
");

$year = $report_date ? date("Y", strtotime($report_date)) : date("Y");
$month = $report_date ? date("m", strtotime($report_date)) : date("m");
$serial_no_prefix = $year . '-' . $month . '-';
?>
<?php include_once('layouts/header.php'); ?>

<style>
    /* your existing styles */
    .container { max-width: 800px; margin: 0 auto;  padding: 20px; box-sizing: border-box; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { font-size: 16px; text-transform: uppercase; margin: 0; font-weight: bold; }
    .entity-info, .serial-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 5px; font-size: 14px; }
    th { text-align: center; font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .signature-section { margin-top: 40px; }
    .signature-line { display: inline-block; width: 250px; border-bottom: 1px solid #000; margin-bottom: 5px; }
    .signature-label { font-size: 14px; text-align: center; display: block; }
    .recap-table { width: 60%; }
   .inline-select {
    background-color: #f5f5f6ff;
    border-bottom: 1px solid #000000ff;
    padding:8px;
    font-size: 13px;
    min-width: 180px;
    max-width: 200px;
    width: 100%;
    box-sizing: border-box;
    text-align: center;
}

.form-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0;
}

.form-group label {
    margin-bottom: 0;
    white-space: nowrap;
    font-weight: bold;
}

.nav-tabs-custom {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 2px solid #e9ecef;
    padding: 0;
    margin: 0 0 2rem 0;
}

.nav-tab-item {
    flex: 1;
    min-width: 200px;
    text-align: center;
}

.nav-tab-link {
    display: block;
    padding: 1rem 1.5rem;
    background-color: #f8f9fa;
    color: var(--secondary);
    text-decoration: none;
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 600;
    position: relative;
    overflow: hidden;
}

.nav-tab-link:hover {
    background-color: #e9ecef;
    color: var(--success-dark);
}

.nav-tab-link.active {
    background-color: white;
    color: var(--success);
    border-bottom: 3px solid var(--success);
    border-top: 3px solid var(--success);
}

.tab-icon {
    margin-right: 8px;
    font-size: 1.1rem;
}

.tab-content {
    padding: 0;
    background: white;
}

.tab-pane {
    display: none;
    animation: fadeIn 0.5s ease;
}

.tab-pane.active {
    display: block;
}

/* SweetAlert custom styling */
.swal2-popup {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.swal2-title {
    color: #dc3545 !important;
    font-weight: 600;
}

.swal2-icon.swal2-warning {
    border-color: #dc3545;
    color: #dc3545;
}
</style>

<div class="card">
    <div class="card shadow-sm border-0">
        <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <h5 class="mb-2 mb-md-0 text-center" style="font-family: 'Times New Roman', serif;">
                    <strong>REPORTS OF</strong>
                </h5>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <ul class="nav-tabs-custom" id="categoriesTabs">
            <li class="nav-tab-item">
                <a href="rsmi.php" class="nav-tab-link active" data-tab="smi">
                    <i class="fas fa-boxes tab-icon"></i> Supplies and Materials Issued
                </a>
            </li>
            <li class="nav-tab-item">
                <a href="rspi-rep.php" class="nav-tab-link" data-tab="rspi">
                    <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property (RSPI)
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-8" id="printable-area">
        <div class="container bg-light text-dark">
            <div class="header text-center">
                <!-- <h4 style="margin:0; font-family:'Times New Roman', serif;"><strong>REPORT OF SUPPLIES AND MATERIALS ISSUED </strong></h4> -->
            </div>

            <table style="width:100%; margin-bottom:20px;">
                <tr class="no-border">
                    <td style="width:50%;">
                        <strong>Entity Name:</strong> 
                        <span contenteditable="true" style="margin-left:5px;display:inline-block; min-width:150px;border-bottom:1px solid #000; min-width:200px;">BSU - BOKOD CAMPUS</span>
                    </td>
                    <td style="width:50%;">
                        <strong>Serial No:</strong> 
                        <span contenteditable="true" style="margin-left:5px;display:inline-block; min-width:200px; border-bottom:1px solid #000;">
                            <?= $serial_no_prefix; ?>0000
                        </span>
                    </td>
                </tr>
                <tr class="no-border">
                    <td>
                        <!-- Fund Cluster Filter inline -->
                        <div class="form-group" style="display: flex; align-items: center;">
                            <label for="fund_cluster" style="margin-right: 10px; font-weight: bold;">Fund Cluster:</label>
                            <select name="fund_cluster" id="fund_cluster" class="form-control inline-select">
                                <option value="all">GAA / IGI</option>
                                <?php foreach($fund_clusters as $fc): ?>
                                    <option value="<?= $fc['name']; ?>" 
                                        <?= ($selected_cluster == $fc['name']) ? 'selected' : ''; ?>>
                                        <?= $fc['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </td>
                    <td>
                        <strong>Date:</strong> <span style="margin-left:27px; border-bottom:1px solid #000; min-width:200px;"> <?= $display_date; ?></span>
                    </td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <td colspan="6" class="text-center" style="background:#f2f2f2;">
                            <i>To be filled up by the Supply and/or Property Division/Unit</i>
                        </td>
                        <td colspan="2" class="text-center" style="background:#f2f2f2;">
                            <i>To be filled up by Accounting Division Unit</i>
                        </td>
                    </tr>
                    <tr>
                        <th>RIS No.</th>
                        <th>Responsibility Center Code</th>
                        <th>Stock No.</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Qty. Issued</th>
                        <th>Unit Cost</th>
                        <th>Amount</th>
                    </tr>
                </thead>
               
                <tbody>
                    <?php
                    $max_rows = 10;
                    $num_items = !empty($issued_items) ? count($issued_items) : 0;
                    $empty_rows = max(0, $max_rows - $num_items);
                    $recap = [];
                    ?>

                    <?php if(!empty($issued_items)): ?>
                        <?php foreach($issued_items as $item): ?>
                            <tr>
                                <td class="text-center"><?= "RIS-" . $item['ris_no']; ?></td>
                                <td class="text-center"></td>
                                <td class="text-center"><?= $item['stock_card']; ?></td>
                                <td><?= $item['item_name']; ?></td>
                                <td class="text-center"><?= $item['unit']; ?></td>
                                <td class="text-center"><?= $item['qty_issued']; ?></td>
                                <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                                <td class="text-right">₱<?= number_format($item['amount'], 2); ?></td>
                            </tr>

                            <?php
                            // Build recap (group by stock no.)
                            $stock = $item['stock_card'];
                            if (!isset($recap[$stock])) {
                                $recap[$stock] = [
                                    'qty' => 0,
                                    'unit_cost' => $item['unit_cost'],
                                    'total_cost' => 0,
                                    'uacs' => '---'
                                ];
                            }
                            $recap[$stock]['qty'] += $item['qty_issued'];
                            $recap[$stock]['total_cost'] += $item['amount'];
                            ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Empty filler rows -->
                    <?php for($i = 0; $i < $empty_rows; $i++): ?>
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

                    <?php for($i = 0; $i < $empty_rows; $i++): ?>
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
                </tbody>

               <tfoot>
    <tr>
        <!-- Certified Section -->
        <td colspan="4">
            <div style="margin-bottom: 6px; text-align: left;">
                I hereby certify to the correctness of the above information.
            </div>
            <div style="display: flex; justify-content: center;">
                <span class="text-center" style="display:inline-block; width:180px; border-bottom:1px solid #000;">
                    <?= $current_user['name']; ?>
                </span>
            </div>
            <div style="margin-top: 2px; font-size: 12px; text-align: center;">
                <?= $current_user['position'] ?? 'Position'; ?>
            </div>
        </td>

        <!-- Posted By Section -->
        <td colspan="4">
            <div style="margin-bottom: 6px; text-align: left;">
                Posted by:
            </div>
            <div style="text-align: center;">
                <select id="posted_by" class="form-control" style="display:inline-block; max-width:220px; text-align:center;" required>
                    <option value="">Select Signatory</option>
                    <?php foreach($signatories as $sig): ?>
                        <option value="<?= $sig['name']; ?>" 
                                data-position="<?= $sig['position']; ?>" 
                                data-agency="<?= $sig['agency']; ?>">
                            <?= $sig['name']; ?> (<?= $sig['position']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </td>
    </tr>
</tfoot>

            </table>
        </div>
    </div>

    <div class="col-md-4" style="padding: 10px; border-left: 1px solid #000;">
        <h5>Filter Report</h5>
        <div id="inline-datepicker"></div>
        <button id="print" class="btn btn-success btn-block mt-2"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</div>

<style>
    #inline-datepicker {
        width: 100%;
    }

    #inline-datepicker .datepicker {
        width: 100%;
        max-width: 100%;
    }

    #inline-datepicker table {
        width: 100% !important;
    }

    #inline-datepicker .day {
        padding: 10px 0;
        text-align: center;
    }

    table {
        width: 100%;
    }

    th, td {
        border: 1px solid #000 !important;
        padding: 5px;
        font-size: 14px;
    }

    @media print {
        @page {
            margin: 1in;
        }
        body * {
            visibility: hidden;
        }
        #printable-area, #printable-area * {
            visibility: visible;
        }
        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-border td, 
        .no-border th {
            border: none !important;
        }
    }

    table th:nth-child(1), table td:nth-child(1) { width: 15%; }
    table th:nth-child(2), table td:nth-child(2) { width: 15%; }
    table th:nth-child(3), table td:nth-child(3) { width: 10%; }
    table th:nth-child(4), table td:nth-child(4) { width: 20%; }
    table th:nth-child(5), table td:nth-child(5) { width: 5%; }
    table th:nth-child(6), table td:nth-child(6) { width: 10%; }
    table th:nth-child(7), table td:nth-child(7) { width: 10%; }
    table th:nth-child(8), table td:nth-child(8) { width: 15%; }
</style>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    // Auto reload on fund cluster change
    $('#fund_cluster').on('change', function(){
        const selectedCluster = $(this).val();
        const urlParams = new URLSearchParams(window.location.search);
        const reportDate = urlParams.get('report_date') || '';

        let newUrl = window.location.pathname + '?fund_cluster=' + selectedCluster;
        if(reportDate){
            newUrl += '&report_date=' + reportDate;
        }

        window.location.href = newUrl;
    });

    // Initialize inline datepicker
    $('#inline-datepicker').datepicker({
        format: 'yyyy-mm-dd',
        todayHighlight: true,
        autoclose: false,
        todayBtn: false,
        orientation: "bottom"
    }).on('changeDate', function(e){
        const selectedDate = e.format();
        const selectedCluster = $('#fund_cluster').val() || 'all';

        window.location.href = window.location.pathname + '?report_date=' + selectedDate + '&fund_cluster=' + selectedCluster;
    });

    // Print button with validation
    $('#print').click(function(){
        const signatoryName = $('#posted_by').val();
        
        // Check if signatory is selected
        if (!signatoryName) {
            Swal.fire({
                icon: 'warning',
                title: 'Signatory Required',
                text: 'Please select a signatory before printing.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545',
                customClass: {
                    popup: 'sweet-alert-popup',
                    title: 'sweet-alert-title'
                }
            });
            return false;
        }

        // Get other values
        const signatoryPosition = $('#posted_by').find(':selected').data('position') || '';
        const signatoryAgency = $('#posted_by').find(':selected').data('agency') || '';
        
        const urlParams = new URLSearchParams(window.location.search);
        const reportDate = urlParams.get('report_date') || '';
        const fundCluster = $('#fund_cluster').val() || 'all';

        // Build print URL with all parameters
        let printUrl = 'print_rsmi.php?fund_cluster=' + fundCluster;
        
        if(reportDate){
            printUrl += '&report_date=' + reportDate;
        }
        if(signatoryName){
            printUrl += '&signatory_name=' + encodeURIComponent(signatoryName);
        }
        if(signatoryPosition){
            printUrl += '&signatory_position=' + encodeURIComponent(signatoryPosition);
        }
        if(signatoryAgency){
            printUrl += '&signatory_agency=' + encodeURIComponent(signatoryAgency);
        }

        // Show success message before printing
        Swal.fire({
            icon: 'success',
            title: 'Print Preview',
            text: 'Your report is being prepared for printing.',
            confirmButtonText: 'Continue',
            confirmButtonColor: '#28a745',
            showCancelButton: true,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Open in a new tab
                window.open(printUrl, '_blank');
            }
        });
    });

    // Set calendar to current selected date if any
    const urlParams = new URLSearchParams(window.location.search);
    const reportDate = urlParams.get('report_date');
    if(reportDate){
        $('#inline-datepicker').datepicker('update', reportDate);
    }

    // Optional: Show warning when trying to close without selecting signatory
    let signatorySelected = false;
    
    $('#posted_by').on('change', function(){
        signatorySelected = $(this).val() !== '';
    });

    // Optional: Prevent form submission without signatory
    $('form').on('submit', function(e){
        const signatoryName = $('#posted_by').val();
        if (!signatoryName) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Signatory Required',
                text: 'Please select a signatory before proceeding.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        }
    });
});
</script>