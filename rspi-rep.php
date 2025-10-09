<?php
$page_title = 'Report of Semi-Expendable Property Issued';
require_once('includes/load.php');
page_require_level(1);

$signatories = find_by_sql("
    SELECT id, name, position, agency 
    FROM signatories 
    ORDER BY name ASC
");

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
$current_user = current_user(); 

// Get filter parameters
$report_date = $_GET['report_date'] ?? null;
$selected_cluster = $_GET['fund_cluster'] ?? null;
$value_type = $_GET['value_type'] ?? null;
$serial_suffix = $_GET['serial_suffix'] ?? '0000';

// Fetch semi-expendable items that were issued
$semi_expendable_items = find_by_sql("
    SELECT 
        p.inv_item_no,
        p.item,
        p.item_description,
        p.unit,
        p.total_qty,
        t.quantity AS qty_issued,
        p.unit_cost,
        (t.quantity * p.unit_cost) AS amount,
        p.fund_cluster,
        t.transaction_date AS date_issued,
        sc.semicategory_name AS category,
        CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS issued_to
    FROM transactions t
    INNER JOIN semi_exp_prop p ON t.item_id = p.id
    LEFT JOIN semicategories sc ON p.semicategory_id = sc.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE 
        t.transaction_type = 'issued'
        AND t.status = 'approved'
        " . ($report_date ? " AND DATE(t.transaction_date) = '" . $db->escape($report_date) . "'" : "") . "
        " . ($selected_cluster && $selected_cluster !== 'all' ? " AND p.fund_cluster = '" . $db->escape($selected_cluster) . "'" : "") . "
        " . ($value_type === 'low' ? " AND p.unit_cost < 5000" : "") . "
        " . ($value_type === 'high' ? " AND p.unit_cost >= 5000 AND p.unit_cost <= 50000" : "") . "
    ORDER BY t.transaction_date ASC
");

$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : date("F d, Y");
$rspi_serial_prefix = date("Y-m-");
$serial_number = $rspi_serial_prefix . $serial_suffix;
?>


<?php include_once('layouts/header.php'); ?>

<style>
    .container { max-width: 800px; margin: 0 auto; padding: 20px; box-sizing: border-box; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { font-size: 16px; text-transform: uppercase; margin: 0; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 5px; font-size: 14px; }
    th { text-align: center; font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    
    .inline-select {
        background-color: #f5f5f6ff;
        border-bottom: 1px solid #000000ff;
        padding: 8px;
        font-size: 13px;
        min-width: 180px;
        max-width: 200px;
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }

    .serial-input {
        background-color: #f5f5f6ff;
        border-bottom: 1px solid #000000ff;
        padding: 8px;
        font-size: 13px;
        min-width: 180px;
        max-width: 200px;
        width: 100%;
        box-sizing: border-box;
        text-align: center;
        border: none;
        outline: none;
    }

    .serial-container {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .serial-prefix {
        background-color: #f5f5f6ff;
        padding: 8px;
        font-size: 13px;
        font-weight: bold;
        color: #495057;
    }

    .serial-suffix {
        background-color: #f5f5f6ff;
        border-bottom: 1px solid #000000ff;
        padding: 8px;
        font-size: 13px;
        width: 80px;
        text-align: center;
        border: none;
        outline: none;
    }

    .form-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .form-group label {
        margin-bottom: 0;
        white-space: nowrap;
        font-weight: bold;
        min-width: 120px;
    }

    /* RSPI Specific Styles */
    .rspi-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .rspi-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .rspi-subtitle {
        font-size: 14px;
        margin-bottom: 5px;
    }

    .rspi-section {
        margin-bottom: 15px;
    }

    .rspi-info-table {
        width: 100%;
        border: none;
        margin-bottom: 20px;
    }

    .rspi-info-table td {
        border: none;
        padding: 2px 5px;
        vertical-align: top;
    }

    .rspi-property-table {
        width: 100%;
        border: 1px solid #000;
    }

    .rspi-property-table th,
    .rspi-property-table td {
        border: 1px solid #000;
        padding: 5px;
        text-align: center;
    }

    .rspi-signature-section {
        margin-top: 30px;
    }

    .rspi-signature-box {
        display: inline-block;
        width: 45%;
        vertical-align: top;
        margin-right: 5%;
    }

    .rspi-signature-line {
        border-bottom: 1px solid #000;
        margin-bottom: 5px;
        height: 20px;
    }

    .rspi-signature-label {
        font-size: 12px;
        text-align: center;
    }

    /* Filter Section */
    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }

    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 15px;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #495057;
    }

    /* Preview Section */
    .preview-section {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .preview-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }

    /* Print Styles */
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 100%;
            padding: 0;
        }
    }

    /* Column widths */
    .rspi-property-table th:nth-child(1),
    .rspi-property-table td:nth-child(1) { width: 20%; }
    .rspi-property-table th:nth-child(2),
    .rspi-property-table td:nth-child(2) { width: 35%; }
    .rspi-property-table th:nth-child(3),
    .rspi-property-table td:nth-child(3) { width: 10%; }
    .rspi-property-table th:nth-child(4),
    .rspi-property-table td:nth-child(4) { width: 10%; }
    .rspi-property-table th:nth-child(5),
    .rspi-property-table td:nth-child(5) { width: 15%; }
    .rspi-property-table th:nth-child(6),
    .rspi-property-table td:nth-child(6) { width: 15%; }


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
</style>
<div class="card">
    <div class="card shadow-sm border-0">
        <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <h5 class="mb-2 mb-md-0 text-center text-center" style="font-family: 'Times New Roman', serif;">
                    <strong>REPORTS OF</strong>
                </h5>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <ul class="nav-tabs-custom" id="categoriesTabs">
            <li class="nav-tab-item">
                <a href="rsmi.php" class="nav-tab-link" data-tab="smi">
                    <i class="fas fa-boxes tab-icon"></i> Supplies and Materials Issued (RSMI)
                </a>
            </li>
            <li class="nav-tab-item">
                <a href="rspi-rep.php" class="nav-tab-link active" data-tab="rspi">
                    <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property Isuued (RSPI)
                </a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Filter Section -->
            <div class="filter-section no-print">
                <h5>Filter RSPI Report</h5>
                <form id="rspiFilterForm" method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="report_date">Date:</label>
                            <input type="date" name="report_date" id="report_date" 
                                   class="form-control" value="<?= $report_date ?>">
                        </div>
                        <div class="filter-group">
                            <label for="fund_cluster">Fund Cluster:</label>
                            <select name="fund_cluster" id="fund_cluster" class="form-control">
                                <option value="all">All Fund Clusters</option>
                                <?php foreach($fund_clusters as $fc): ?>
                                    <option value="<?= $fc['name']; ?>" 
                                        <?= ($selected_cluster == $fc['name']) ? 'selected' : ''; ?>>
                                        <?= $fc['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="value_type">Property Value:</label>
                            <select name="value_type" id="value_type" class="form-control">
                                <option value="">All Values</option>
                                <option value="low" <?= ($value_type == 'low') ? 'selected' : ''; ?>>Low Value (Below ₱5,000)</option>
                                <option value="high" <?= ($value_type == 'high') ? 'selected' : ''; ?>>High Value (₱5,000 - ₱50,000)</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="serial_suffix">Serial Number:</label>
                            <div class="serial-container">
                                <span class="serial-prefix"><?= $rspi_serial_prefix ?></span>
                                <input type="text" name="serial_suffix" id="serial_suffix" 
                                       class="form-control serial-suffix" value="<?= htmlspecialchars($serial_suffix) ?>"
                                       placeholder="0000" maxlength="4" pattern="[0-9]{4}" title="Enter 4 digits">
                            </div>
                            <small class="form-text text-muted">Format: YYYY-MM-<strong>XXXX</strong> (Only last 4 digits are editable)</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="button" id="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </form>
            </div>

            <div class="preview-section">
                <div class="preview-header">
                    <h4>Filtered RSPI Items</h4>
                    <p>Date: <?= $display_date ?> | Fund Cluster: <?= $selected_cluster ? $selected_cluster : 'All' ?> | 
                       Value: <?= $value_type ? ucfirst($value_type) : 'All' ?> | Serial No: <?= $serial_number ?></p>
                </div>

                <?php if (!empty($semi_expendable_items)): ?>
                    <table class="rspi-property-table">
                        <thead>
                            <tr>
                                <th>Inv Item No</th>
                                <th>Article</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Total Qty</th>
                                <th>Issued Qty</th>
                                <th>Unit Cost</th>
                                <th>Amount</th>
                                <th>Fund Cluster</th>
                                <th>Date Added</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($semi_expendable_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['inv_item_no']); ?></td>
                                <td><?= htmlspecialchars($item['item']); ?></td>
                                <td><?= htmlspecialchars($item['item_description']); ?></td>
                                <td><?= htmlspecialchars($item['unit']); ?></td>
                                <td class="text-center"><?= (int)$item['total_qty']; ?></td>
                                <td class="text-center"><?= (int)$item['qty_issued']; ?></td>
                                <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                                <td class="text-right">₱<?= number_format($item['amount'], 2); ?></td>
                                <td><?= htmlspecialchars($item['fund_cluster']); ?></td>
                                <td><?= date("M d, Y", strtotime($item['date_added'])); ?></td>
                                <td><?= htmlspecialchars($item['category']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No items found for the selected filters.</p>
                <?php endif; ?>
            </div>

            <!-- Signatories Selection -->
            <div class="filter-section no-print">
                <h5>Select Signatories</h5>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="property_custodian">Property Custodian:</label>
                        <select id="property_custodian" class="form-control">
                            <option value="">Select Property Custodian</option>
                            <?php foreach($signatories as $sig): ?>
                                <option value="<?= $sig['name']; ?>" 
                                        data-position="<?= $sig['position']; ?>">
                                    <?= $sig['name']; ?> (<?= $sig['position']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="accounting_staff">Accounting Staff:</label>
                        <select id="accounting_staff" class="form-control">
                            <option value="">Select Accounting Staff</option>
                            <?php foreach($signatories as $sig): ?>
                                <option value="<?= $sig['name']; ?>" 
                                        data-position="<?= $sig['position']; ?>">
                                    <?= $sig['name']; ?> (<?= $sig['position']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Print Button -->
            <div class="text-center no-print mb-4">
                <button id="printRspi" class="btn btn-success btn-lg">
                    <i class="fa-solid fa-print"></i> Generate RSPI Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    // Update preview when filters change
    function updatePreview() {
        $('#previewDate').text($('#report_date').val() ? new Date($('#report_date').val()).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }) : '<?= date("F d, Y") ?>');
        
        $('#previewFundCluster').text($('#fund_cluster').val() !== 'all' ? $('#fund_cluster').val() : 'CAA');
        $('#previewFundCluster2').text($('#fund_cluster').val() !== 'all' ? $('#fund_cluster').val() : 'CAA');
    }

    // Update signatories in preview
    $('#property_custodian').on('change', function(){
        const selected = $(this).find(':selected');
        $('#previewCustodianName').text(selected.val() || 'BRIGIDA A. BENSOSAN');
    });

    $('#accounting_staff').on('change', function(){
        const selected = $(this).find(':selected');
        $('#previewAccountingName').text(selected.val() || 'FREDALYN JOY Y. FINMARA');
    });

    // Reset filters
    $('#resetFilters').on('click', function(){
        $('#rspiFilterForm')[0].reset();
        // Reset serial suffix to default
        $('#serial_suffix').val('0000');
        window.location.href = window.location.pathname;
    });

    // Print RSPI
    $('#printRspi').on('click', function(){
        const reportDate = $('#report_date').val();
        const fundCluster = $('#fund_cluster').val();
        const valueType = $('#value_type').val();
        const propertyCustodian = $('#property_custodian').val();
        const accountingStaff = $('#accounting_staff').val();
        const serialSuffix = $('#serial_suffix').val();

        // 🚫 Prevent printing if no date is selected
        if (!reportDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Report Date',
                text: 'Please select a report date before generating the RSPI report.',
                confirmButtonColor: '#28a745'
            });
            return; // stop execution
        }

        // 🚫 Prevent printing if serial suffix is empty or invalid
        if (!serialSuffix.trim() || !/^\d{4}$/.test(serialSuffix)) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Serial Number',
                text: 'Please enter a valid 4-digit serial number suffix.',
                confirmButtonColor: '#28a745'
            });
            return; // stop execution
        }

        let printUrl = 'print_rspi.php?';
        
        if (reportDate) printUrl += 'report_date=' + reportDate + '&';
        if (fundCluster && fundCluster !== 'all') printUrl += 'fund_cluster=' + fundCluster + '&';
        if (valueType) printUrl += 'value_type=' + valueType + '&';
        if (propertyCustodian) printUrl += 'property_custodian=' + encodeURIComponent(propertyCustodian) + '&';
        if (accountingStaff) printUrl += 'accounting_staff=' + encodeURIComponent(accountingStaff) + '&';
        if (serialSuffix) printUrl += 'serial_suffix=' + encodeURIComponent(serialSuffix);

        // Remove trailing & or ? if no parameters
        printUrl = printUrl.replace(/[&?]$/, '');

        window.open(printUrl, '_blank');
    });

    // Restrict serial suffix input to numbers only
    $('#serial_suffix').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 4) {
            this.value = this.value.slice(0, 4);
        }
    });

    // Initial preview update
    updatePreview();

    // Update preview on filter changes
    $('#report_date, #fund_cluster').on('change', updatePreview);
});
</script>