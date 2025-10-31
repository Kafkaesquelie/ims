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
        t.transaction_type = 'issue'
        AND t.status = 'Issued'
        " . ($report_date ? " AND DATE(t.transaction_date) = '" . $db->escape($report_date) . "'" : "") . "
        " . ($selected_cluster && $selected_cluster !== 'all' ? " AND p.fund_cluster = '" . $db->escape($selected_cluster) . "'" : "") . "
        " . ($value_type === 'low' ? " AND p.unit_cost < 5000" : "") . "
        " . ($value_type === 'high' ? " AND p.unit_cost >= 5000 AND p.unit_cost <= 50000" : "") . "
    ORDER BY t.transaction_date ASC
");

$display_date = $report_date ? date("F d, Y", strtotime($report_date)) : date("F d, Y");
$rspi_serial_prefix = date("Y-m-");
?>

<?php include_once('layouts/header.php'); ?>

<style>
:root {
    --primary: #28a745;
    --primary-dark: #1e7e34;
    --primary-light: #34ce57;
    --secondary: #6c757d;
    --success: #28a745;
    --info: #17a2b8;
    --warning: #ffc107;
    --danger: #dc3545;
    --light: #f8f9fa;
    --dark: #343a40;
    --border-radius: 12px;
    --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
}

.card-header-custom {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    padding: 1.5rem;
    border: none;
}

.card-header-custom h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
    text-align: center;
}

/* Tabs Navigation - Original Design */
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
    color: var(--primary); /* Green icons */
}

/* Filter Section */
.filter-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary);
}

.filter-section h5 {
    color: var(--primary-dark);
    margin-bottom: 1.5rem;
    font-weight: 600;
    border-bottom: 2px solid var(--primary-light);
    padding-bottom: 0.5rem;
}

.filter-section h5 i {
    color: var(--primary); /* Green icon in section header */
}

/* FIXED: Filter Row Layout */
.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-group {
    flex: 1;
    min-width: 320px; /* Slightly increased minimum width */
    margin-bottom: 1rem;
}

.filter-group label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--dark);
    font-size: 0.95rem;
    width: 100%;
    white-space: nowrap;
    overflow: visible;
}

.filter-group label i {
    color: var(--primary); /* Green icons in labels */
    margin-right: 0.5rem;
}

/* FIXED: Dropdown Height and Styling */
.form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.85rem 1rem; /* Increased padding for more height */
    transition: var(--transition);
    font-size: 0.95rem;
    width: 100%;
    box-sizing: border-box;
    min-height: 50px; /* Minimum height for better visibility */
    line-height: 1.5;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Specific styling for select dropdowns */
select.form-control {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

/* Ensure dropdown options are properly visible */
select.form-control option {
    padding: 12px 15px;
    font-size: 0.9rem;
    line-height: 1.5;
    min-height: 40px;
}

/* Serial Number Input */
.serial-container {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
    min-height: 50px; /* Match height with other inputs */
}

.serial-prefix {
    background: var(--primary);
    color: white;
    padding: 0.85rem 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    height: 100%;
    display: flex;
    align-items: center;
}

.serial-suffix {
    border: none;
    padding: 0.85rem 1rem;
    font-size: 0.9rem;
    width: 80px;
    text-align: center;
    background: white;
    flex-shrink: 0;
    height: 100%;
    min-height: 50px;
}

.serial-suffix:focus {
    outline: none;
    background: #fff;
}

/* Buttons */
.btn {
    border-radius: 8px;
    padding: 0.85rem 1.5rem; /* Slightly increased padding */
    font-weight: 600;
    transition: var(--transition);
    border: none;
    min-height: 50px; /* Consistent height with inputs */
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn i {
    margin-right: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), #155724);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}

.btn-secondary {
    background: var(--secondary);
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-success {
    background: linear-gradient(135deg, var(--success), #1e7e34);
    color: white;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    min-height: 60px; /* Slightly taller for main action button */
}

.btn-success:hover {
    background: linear-gradient(135deg, #1e7e34, #155724);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

/* Preview Section */
.preview-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid #e9ecef;
}

.preview-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary-light);
}

.preview-header h4 {
    color: var(--primary-dark);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.preview-header h4 i {
    color: var(--primary); /* Green icon */
}

.preview-header p {
    color: var(--secondary);
    margin-bottom: 0;
    font-size: 0.95rem;
}

/* Table Styles */
.table-responsive {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.table {
    margin-bottom: 0;
    border: 1px solid #dee2e6;
}

.table thead {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.table th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    text-align: center;
    vertical-align: middle;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
    font-size: 0.8rem;
}

.table tbody tr {
    transition: var(--transition);
}

.table tbody tr:hover {
    background-color: rgba(40, 167, 69, 0.05);
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.text-left {
    text-align: left;
}

/* Print Controls */
.no-print {
    display: block;
}

@media print {
    .no-print {
        display: none !important;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-group {
        min-width: 100%; /* Full width on mobile */
    }
    
    .nav-tab-item {
        min-width: 100%;
    }
    
    .nav-tab-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .btn-success {
        width: 100%;
        margin-bottom: 1rem;
    }

    .filter-group label {
        white-space: normal;
    }
    
    .form-control {
        min-height: 45px; /* Slightly smaller on mobile */
        padding: 0.75rem 1rem;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease;
}

/* Badge Styles */
.badge {
    padding: 0.5rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.75rem;
}

.badge-primary {
    background: rgba(40, 167, 69, 0.15);
    color: var(--primary-dark);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--secondary);
}

.empty-state-icon {
    font-size: 4rem;
    color: var(--primary); /* Green empty state icon */
    margin-bottom: 1rem;
}

.empty-state h4 {
    color: var(--secondary);
    margin-bottom: 0.5rem;
}

.empty-state .btn i {
    color: white; /* White icons in buttons */
}
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header-custom">
            <h5>REPORTS OF SEMI-EXPENDABLE PROPERTY ISSUED (RSPI)</h5>
        </div>
    </div>

    <!-- Tabs Navigation - Original Design -->
    <div class="tabs-container">
        <ul class="nav-tabs-custom" id="categoriesTabs">
            <li class="nav-tab-item">
                <a href="rsmi.php" class="nav-tab-link" data-tab="smi">
                    <i class="fas fa-boxes tab-icon"></i> Supplies and Materials Issued (RSMI)
                </a>
            </li>
            <li class="nav-tab-item">
                <a href="rspi-rep.php" class="nav-tab-link active" data-tab="rspi">
                    <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property Issued (RSPI)
                </a>
            </li>
        </ul>
    </div>

    <!-- Filter Section -->
    <div class="card filter-section no-print">
        <h5><i class="fas fa-filter me-2"></i>Filter RSPI Report</h5>
        <form id="rspiFilterForm" method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="report_date"><i class="fas fa-calendar me-1"></i>Report Date</label>
                    <input type="date" name="report_date" id="report_date" 
                           class="form-control" value="<?= $report_date ?>">
                </div>
                <div class="filter-group">
                    <label for="fund_cluster"><i class="fas fa-money-bill-wave me-1"></i>Fund Cluster</label>
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
                    <label for="value_type"><i class="fas fa-tag me-1"></i>Property Value</label>
                    <select name="value_type" id="value_type" class="form-control">
                        <option value="">All Values</option>
                        <option value="low" <?= ($value_type == 'low') ? 'selected' : ''; ?>>Low Value (Below ₱5,000)</option>
                        <option value="high" <?= ($value_type == 'high') ? 'selected' : ''; ?>>High Value (₱5,000 - ₱50,000)</option>
                    </select>
                </div>
            </div>
            
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                </button>
                <button type="button" id="resetFilters" class="btn btn-secondary">
                    <i class="fas fa-redo me-1"></i> Reset Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Preview Section -->
    <div class="card preview-section">
        <div class="preview-header">
            <h4><i class="fas fa-eye me-2"></i>Filtered RSPI Items Preview</h4>
            <p>
                <span class="badge badge-primary me-2">Date: <?= $display_date ?></span>
                <span class="badge badge-primary me-2">Fund Cluster: <?= $selected_cluster ? $selected_cluster : 'All' ?></span>
                <span class="badge badge-primary me-2">Value: <?= $value_type ? ucfirst($value_type) : 'All' ?></span>
            </p>
        </div>

        <?php if (!empty($semi_expendable_items)): ?>
            <div class="table-responsive p-3">
                <table class="table table-hover" id="rspiTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Inventory Item No</th>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Unit</th>
                            <th>Issued Qty</th>
                            <th>Unit Cost</th>
                            <th>Amount</th>
                            <th>Issued To</th>
                            <th>Date Issued</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($semi_expendable_items as $index => $item): ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge badge-primary"><?= $index + 1 ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($item['inv_item_no']); ?></strong></td>
                            <td><?= htmlspecialchars($item['item']); ?></td>
                            <td><?= htmlspecialchars($item['item_description']); ?></td>
                            <td class="text-center"><?= htmlspecialchars($item['unit']); ?></td>
                            <td class="text-center">
                                <span class="badge badge-primary"><?= (int)$item['qty_issued']; ?></span>
                            </td>
                            <td class="text-right">₱<?= number_format($item['unit_cost'], 2); ?></td>
                            <td class="text-right"><strong class="text-success">₱<?= number_format($item['amount'], 2); ?></strong></td>
                            <td><?= htmlspecialchars($item['issued_to']); ?></td>
                            <td><?= date("M d, Y", strtotime($item['date_issued'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search empty-state-icon"></i>
                <h4>No Items Found</h4>
                <p>No semi-expendable properties found for the selected filters.</p>
                <button type="button" id="resetFiltersEmpty" class="btn btn-primary">
                    <i class="fas fa-redo me-1"></i> Reset Filters
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Signatories Selection -->
    <div class="card filter-section no-print">
        <h5><i class="fas fa-signature me-2"></i>Select Signatories</h5>
        <div class="filter-row">
            <div class="filter-group">
                <label for="property_custodian"><i class="fas fa-user-tie me-1"></i>Property Custodian</label>
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
                <label for="accounting_staff"><i class="fas fa-calculator me-1"></i>Accounting Staff</label>
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
            <i class="fa-solid fa-print me-2"></i> Generate RSPI Report
        </button>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    // Reset filters button for empty state
    $('#resetFiltersEmpty').on('click', function(){
        $('#rspiFilterForm')[0].reset();
        $('#serial_suffix').val('0000');
        window.location.href = window.location.pathname;
    });

    // Reset filters
    $('#resetFilters').on('click', function(){
        $('#rspiFilterForm')[0].reset();
        $('#serial_suffix').val('0000');
        window.location.href = window.location.pathname;
    });

    // Print RSPI
    // Print RSPI - FIXED VERSION
$('#printRspi').on('click', function(){
    const reportDate = $('#report_date').val();
    const fundCluster = $('#fund_cluster').val();
    const valueType = $('#value_type').val();
    const propertyCustodian = $('#property_custodian').val();
    const accountingStaff = $('#accounting_staff').val();

    // 🚫 Prevent printing if no date is selected
    if (!reportDate) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Report Date',
            text: 'Please select a report date before generating the RSPI report.',
            confirmButtonColor: '#28a745',
            confirmButtonText: 'OK'
        });
        return;
    }

    // 🚫 Prevent printing if signatories are not selected
    if (!propertyCustodian) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Property Custodian',
            text: 'Please select a Property Custodian before generating the RSPI report.',
            confirmButtonColor: '#28a745',
            confirmButtonText: 'OK'
        });
        return;
    }

    if (!accountingStaff) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Accounting Staff',
            text: 'Please select an Accounting Staff before generating the RSPI report.',
            confirmButtonColor: '#28a745',
            confirmButtonText: 'OK'
        });
        return;
    }

    let printUrl = 'print_rspi.php?';
    
    if (reportDate) printUrl += 'report_date=' + reportDate + '&';
    if (fundCluster && fundCluster !== 'all') printUrl += 'fund_cluster=' + fundCluster + '&';
    if (valueType) printUrl += 'value_type=' + valueType + '&';
    if (propertyCustodian) printUrl += 'property_custodian=' + encodeURIComponent(propertyCustodian) + '&';
    if (accountingStaff) printUrl += 'accounting_staff=' + encodeURIComponent(accountingStaff);

    // Remove trailing & if exists
    printUrl = printUrl.replace(/[&]$/, '');

    window.open(printUrl, '_blank');
});

    // Restrict serial suffix input to numbers only
    $('#serial_suffix').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 4) {
            this.value = this.value.slice(0, 4);
        }
    });

    // Add animation to cards on load
    $('.card').addClass('fade-in');
});
</script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
     $(document).ready(function() {
      var table = $('#rspiTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
      });
      }); 

</script>