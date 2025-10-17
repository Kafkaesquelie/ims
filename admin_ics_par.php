<?php
$page_title = 'PAR & ICS Forms';
require_once('includes/load.php');
page_require_level(1);

// Fetch PPE Properties (only available items)
$ppe_items = find_by_sql("
    SELECT p.id, p.fund_cluster, p.property_no, sc.subcategory_name, 
           p.article, p.description, p.unit, p.qty, p.unit_cost, p.date_acquired 
    FROM properties p
    LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
    WHERE p.qty > 0
    ORDER BY p.date_added DESC
");

// Fetch Semi-Expendable Properties (only available items)
$semi_items = find_by_sql("
    SELECT s.id, s.fund_cluster, s.inv_item_no, s.inv_item_no, 
           s.item, s.item_description, sc.semicategory_name, 
           s.unit, s.total_qty, s.qty_left, s.unit_cost, s.date_added
    FROM semi_exp_prop s
    LEFT JOIN semicategories sc ON s.semicategory_id = sc.id
    WHERE s.qty_left > 0
    ORDER BY s.date_added DESC
");
// Fetch issued items for both PPE and Semi-Expendable
$issued_items = find_by_sql("
    SELECT t.*, 
           e.first_name, e.middle_name, e.last_name, e.position, e.office,
           CASE 
               WHEN t.PAR_No != '' THEN p.article
               WHEN t.ICS_No != '' THEN s.item
           END as item_name,
           CASE 
               WHEN t.PAR_No != '' THEN p.property_no
               WHEN t.ICS_No != '' THEN s.inv_item_no
           END as stock_number,
           CASE 
               WHEN t.PAR_No != '' THEN p.unit
               WHEN t.ICS_No != '' THEN s.unit
           END as unit,
           CASE 
               WHEN t.PAR_No != '' THEN p.unit_cost
               WHEN t.ICS_No != '' THEN s.unit_cost
           END as unit_cost,
           CASE 
               WHEN t.PAR_No != '' THEN 'ppe'
               WHEN t.ICS_No != '' THEN 'semi'
           END as item_type
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN properties p ON t.item_id = p.id AND t.PAR_No != ''
    LEFT JOIN semi_exp_prop s ON t.item_id = s.id AND t.ICS_No != ''
    WHERE t.transaction_type = 'issue' AND t.status = 'completed'
    ORDER BY t.transaction_date DESC
");

$employees = find_by_sql("
    SELECT 
        id, 
        CONCAT(first_name, ' ', middle_name, ' ', last_name) AS fullname, 
        position,
        office
    FROM employees 
    ORDER BY last_name ASC
");

?>
<?php include_once('layouts/header.php'); ?>

<style>
    :root {
        --primary: #28a745;
        --primary-dark: #1e7e34;
        --secondary: #6c757d;
        --warning: #ffc107;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #343a40;
    }

    /* Tabs Styling */
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

    .card-header-custom {
        background: white;
        border-top: 5px solid var(--primary);
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .table-custom {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 0;
        width: 100% !important;
        table-layout: fixed;
    }

    .table-custom thead {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        font-size:15px;
    }

    .table-custom th {
        border: none;
        font-weight: 600;
        padding: 1rem;
        text-align: center;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-custom td {
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #dee2e6;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-custom tbody tr {
        transition: all 0.3s ease;
    }

    .table-custom tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.05);
        transform: translateY(-1px);
    }

    /* Fixed column widths for PPE table */
    #ppe-table th:nth-child(1), #ppe-table td:nth-child(1) { width: 12%; }  /* prop no */
    #ppe-table th:nth-child(2), #ppe-table td:nth-child(2) { width: 25%; } /* Property Details */
    #ppe-table th:nth-child(3), #ppe-table td:nth-child(3) { width: 15%; } /* Category */
    #ppe-table th:nth-child(4), #ppe-table td:nth-child(4) { width: 8%; }  /* Fund Cluster */
    #ppe-table th:nth-child(6), #ppe-table td:nth-child(6) { width: 8%; }  /* Quantity */
    #ppe-table th:nth-child(7), #ppe-table td:nth-child(7) { width: 10%; } /* Unit Cost */
    #ppe-table th:nth-child(8), #ppe-table td:nth-child(8) { width: 10%; } /* Total Value */
    #ppe-table th:nth-child(9), #ppe-table td:nth-child(9) { width: 10%; } /* Date Acquired */
    #ppe-table th:nth-child(10), #ppe-table td:nth-child(10) { width: 8%; } /* Actions */

    /* Fixed column widths for Semi table */
    #semi-table th:nth-child(1), #semi-table td:nth-child(1) { width: 15%; }   /* inv no */
    #semi-table th:nth-child(2), #semi-table td:nth-child(2) { width: 20%; }  /* Item Details */
    #semi-table th:nth-child(3), #semi-table td:nth-child(3) { width: 10%; }  /* Category */
    #semi-table th:nth-child(4), #semi-table td:nth-child(4) { width: 8%; }   /* Fund Cluster */
    #semi-table th:nth-child(6), #semi-table td:nth-child(6) { width: 10%; }  /* Property No */
    #semi-table th:nth-child(7), #semi-table td:nth-child(7) { width: 12%; }  /* Quantity Status */
    #semi-table th:nth-child(8), #semi-table td:nth-child(8) { width: 10%; }  /* Unit Cost */
    #semi-table th:nth-child(9), #semi-table td:nth-child(9) { width: 10%; }  /* Date Added */
    #semi-table th:nth-child(10), #semi-table td:nth-child(10) { width: 8%; } /* Actions */

    .btn-primary-custom {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        background: linear-gradient(135deg, var(--primary-dark), #155724);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
    }

    .btn-warning-custom {
        background: var(--warning);
        color: var(--dark);
        border: none;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-warning-custom:hover {
        background: #e0a800;
        transform: translateY(-1px);
        color: var(--dark);
    }

    .badge-custom {
        padding: 0.5rem 0.75rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .badge-success {
        background: rgba(40, 167, 69, 0.15);
        color: var(--primary-dark);
    }

    .badge-primary {
        background: rgba(0, 123, 255, 0.15);
        color: #0056b3;
    }

    .badge-warning {
        background: rgba(255, 193, 7, 0.15);
        color: #856404;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--secondary);
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    .stats-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--primary);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-top: 3px solid var(--primary);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: var(--secondary);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .quantity-badge {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }

    .available {
        background: rgba(40, 167, 69, 0.15);
        color: var(--primary-dark);
    }

    .low-stock {
        background: rgba(255, 193, 7, 0.15);
        color: #856404;
    }

    .out-of-stock {
        background: rgba(220, 53, 69, 0.15);
        color: var(--danger);
    }

    /* DataTables custom styling */
    .dataTables_wrapper {
        position: relative;
    }

    .dataTables_length,
    .dataTables_filter {
        margin-bottom: 1rem;
    }

    .dataTables_length select,
    .dataTables_filter input {
        border-radius: 6px;
        border: 1px solid #dee2e6;
        padding: 0.375rem 0.75rem;
    }

    .dataTables_info {
        padding: 1rem 0;
        color: var(--secondary);
    }

    .dataTables_paginate {
        margin-top: 1rem;
    }

    .dataTables_paginate .paginate_button {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        margin: 0 0.25rem;
        background: white;
        color: var(--secondary);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .dataTables_paginate .paginate_button:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .dataTables_paginate .paginate_button.current {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    /* Ensure no horizontal scroll */
    .table-responsive {
        overflow-x: hidden;
    }

    /* Text overflow handling */
    .text-truncate-custom {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Column specific text handling */
    .property-details {
        max-width: 100%;
        overflow: hidden;
    }

    .property-details strong {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .property-details small {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
     /* Add styles for issued items table */
    .issued-table {
        margin-top: 2rem;
        border-top: 2px solid #dee2e6;
        padding-top: 1.5rem;
    }
    
    .issued-table .table-custom thead {
        background: linear-gradient(135deg, #6c757d, #495057);
    }
    
    .btn-return {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: var(--dark);
        border: none;
        border-radius: 6px;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-return:hover {
        background: linear-gradient(135deg, #e0a800, #c69500);
        transform: translateY(-1px);
        color: var(--dark);
    }
    
    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .badge-issued {
        background: rgba(40, 167, 69, 0.15);
        color: var(--primary-dark);
    }
    
    .badge-returned {
        background: rgba(108, 117, 125, 0.15);
        color: #495057;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary);
    }
</style>

    <!-- Page Header -->
    <div class="card-header-custom">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div>
                <h4 class="page-title" style="font-family: 'Times New Roman', serif; font-weight: 700;">
                    PAR & ICS ISSUANCE
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-custom">
                        <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">PAR & ICS Forms</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <ul class="nav-tabs-custom" id="formsTabs">
            <li class="nav-tab-item">
                <a href="#ppe-tab" class="nav-tab-link active" data-tab="ppe">
                    <i class="fas fa-hard-hat tab-icon"></i> Property, Plant & Equipment (PAR)
                </a>
            </li>
            <li class="nav-tab-item">
                <a href="#semi-tab" class="nav-tab-link" data-tab="semi">
                    <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property (ICS)
                </a>
            </li>
        </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- PPE Tab -->
        <div id="ppe-tab" class="tab-pane active">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= count($ppe_items) ?></div>
                    <div class="stat-label">Total PPE Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">₱<?php 
                        $total_value = 0;
                        foreach($ppe_items as $item) {
                            $total_value += ($item['unit_cost'] * $item['qty']);
                        }
                        echo number_format($total_value, 2);
                    ?></div>
                    <div class="stat-label">Total PPE Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php 
                        $total_qty = 0;
                        foreach($ppe_items as $item) {
                            $total_qty += $item['qty'];
                        }
                        echo $total_qty;
                    ?></div>
                    <div class="stat-label">Total Quantity</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= date('M Y') ?></div>
                    <div class="stat-label">Current Period</div>
                </div>
            </div>

            <div class="card-header-custom">
                <h5 class="mb-0"><i class="fas fa-hard-hat me-2"></i>Property, Plant & Equipment (PPE)</h5>
                <p class="text-muted mb-0">Manage and issue Property, Plant and Equipment items</p>
            </div>

            <?php if(!empty($ppe_items)): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="ppe-table">
                        <thead>
                            <tr>
                              <th class="text-center">Property No.</th>
                                <th>Property Details</th>
                                <th class="text-center">Category</th>
                                <th class="text-center">Fund Cluster</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Unit Cost</th>
                                <th class="text-center">Total Value</th>
                                <th class="text-center">Date Acquired</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ppe_items as $index => $item): ?>
                                <tr>
                                     <td class="text-center">
                                        <strong class="text-truncate-custom"><?= !empty($item['property_no']) ? $item['property_no'] : 'N/A' ?></strong>
                                    </td>
                                    <td class="property-details">
                                        <div class="d-flex flex-column">
                                            <strong class="text-truncate-custom"><?= $item['article'] ?></strong>
                                          
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="">
                                            <?= $item['subcategory_name'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-warning">
                                            <?= strtoupper($item['fund_cluster']) ?>
                                        </span>
                                    </td>
                                   
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-success quantity-badge">
                                            <?= (int)$item['qty'] ?> <?= $item['unit'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-success text-truncate-custom">₱<?= number_format($item['unit_cost'], 2) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-primary text-truncate-custom">₱<?= number_format($item['unit_cost'] * $item['qty'], 2) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-truncate-custom"><?= date('M d, Y', strtotime($item['date_acquired'])) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-success issue-btn" 
                                               onclick="openIssueModalFromTable(<?= $item['id'] ?>, '<?= addslashes($item['article']) ?>', '<?= addslashes($item['property_no']) ?>', '<?= addslashes($item['subcategory_name']) ?>', '<?= addslashes($item['unit']) ?>', <?= $item['unit_cost'] ?>, <?= $item['qty'] ?>, 'ppe')">
                                            <i class="fas fa-paper-plane me-1"></i> Issue Item
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-hard-hat"></i>
                    </div>
                    <h4>No PPE Properties</h4>
                    <p>No Property, Plant and Equipment items found in the system.</p>
                    <a href="add_property.php" class="btn btn-success-custom mt-2">
                        <i class="fas fa-plus me-2"></i> Add New Property
                    </a>
                </div>
            <?php endif; ?>
        </div>

    



        <!-- Semi-Expendable Tab -->
        <div id="semi-tab" class="tab-pane">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= count($semi_items) ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">₱<?php 
                        $total_value = 0;
                        foreach($semi_items as $item) {
                            $total_value += ($item['unit_cost'] * $item['total_qty']);
                        }
                        echo number_format($total_value, 2);
                    ?></div>
                    <div class="stat-label">Total Inventory Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php 
                        $available_qty = 0;
                        foreach($semi_items as $item) {
                            $available_qty += $item['qty_left'];
                        }
                        echo $available_qty;
                    ?></div>
                    <div class="stat-label">Available Quantity</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php 
                        $issued_qty = 0;
                        foreach($semi_items as $item) {
                            $issued_qty += ($item['total_qty'] - $item['qty_left']);
                        }
                        echo $issued_qty;
                    ?></div>
                    <div class="stat-label">Issued Quantity</div>
                </div>
            </div>

            <div class="card-header-custom">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Semi-Expendable Properties</h5>
                <p class="text-muted mb-0">Manage and issue semi-expendable property items</p>
            </div>

            <?php if(!empty($semi_items)): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="semi-table">
                        <thead>
                            <tr>
                                <th class="text-center">Inventory No.</th>
                                <th>Item Details</th>
                                <th class="text-center">Category</th>
                                <th class="text-center">Fund Cluster</th>
                                <th class="text-center">Quantity Status</th>
                                <th class="text-center">Unit Cost</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($semi_items as $index => $item): ?>
                                <tr>
                                    <td class="text-center">
                                        <strong class="text-truncate-custom"><?= !empty($item['inv_item_no']) ? $item['inv_item_no'] : 'N/A' ?></strong>
                                    </td>
                                    <td class="property-details">
                                        <div class="d-flex flex-column">
                                            <strong><?= $item['item'] ?></strong>
                                            <small class="text-muted mt-1 text-truncate-custom">
                                                <?= $item['item_description'] ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-success">
                                            <?= $item['semicategory_name'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-warning">
                                            <?= strtoupper($item['fund_cluster']) ?>
                                        </span>
                                    </td>
                                                                    
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <?php
                                            $qty_left = $item['qty_left'];
                                            $total_qty = $item['total_qty'];
                                            $percentage = $total_qty > 0 ? ($qty_left / $total_qty) * 100 : 0;
                                            
                                            if ($qty_left == 0) {
                                                $badge_class = 'out-of-stock';
                                                $status_text = 'Out of Stock';
                                            } elseif ($percentage <= 20) {
                                                $badge_class = 'low-stock';
                                                $status_text = 'Low Stock';
                                            } else {
                                                $badge_class = 'available';
                                                $status_text = 'Available';
                                            }
                                            ?>
                                            <span class="badge badge-custom <?= $badge_class ?> quantity-badge mb-1">
                                                <?= $qty_left ?> / <?= $total_qty ?> <?= $item['unit'] ?>
                                            </span>
                                            <small class="text-muted text-truncate-custom"><?= $status_text ?></small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-success text-truncate-custom">₱<?= number_format($item['unit_cost'], 2) ?></strong>
                                    </td>
                                 
                                    <td class="text-center">
                                                <?php if ($item['qty_left'] > 0): ?>
                                                    <button type="button" class="btn btn-success issue-btn" 
                                                           onclick="openIssueModalFromTable(<?= $item['id'] ?>, '<?= addslashes($item['item']) ?>', '<?= addslashes($item['inv_item_no']) ?>', '<?= addslashes($item['semicategory_name']) ?>', '<?= addslashes($item['unit']) ?>', <?= $item['unit_cost'] ?>, <?= $item['qty_left'] ?>, 'semi')">
                                                        <i class="fas fa-paper-plane me-1"></i> Issue Item
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fas fa-times me-1"></i> Out of Stock
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                      
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h4>No Semi-Expendable Properties</h4>
                    <p>No semi-expendable property items found in the system.</p>
                    <a href="smp.php" class="btn btn-success-custom mt-2">
                        <i class="fas fa-plus me-2"></i> Add New Property
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>


<!-- PAR Issue Modal -->
<div class="modal fade" id="parIssueModal" tabindex="-1" aria-labelledby="parIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="parIssueForm" method="POST" action="process_issue.php">
                  <input type="hidden" name="doc_type" value="par">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="parIssueModalLabel">
                        <i class="fas fa-file-contract me-2"></i> Issue Property (PAR)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Hidden fields -->
                    <input type="hidden" name="item_id" id="par_item_id">
                    <input type="hidden" name="item_type" value="ppe">
                    
                    <!-- Item Information Card -->
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-hard-hat me-2"></i> Property Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Property Name</label>
                                        <input type="text" class="form-control bg-light" id="par_item_name" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Property Number</label>
                                        <input type="text" class="form-control bg-light" id="par_property_no" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Category</label>
                                        <input type="text" class="form-control bg-light" id="par_category" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Unit of Measure</label>
                                        <input type="text" class="form-control bg-light" id="par_unit_measure" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Unit Cost</label>
                                        <input type="text" class="form-control bg-light" id="par_unit_cost" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Available Quantity</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light" id="par_current_balance" readonly>
                                            <span class="input-group-text bg-light" id="par_balance_unit"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Issue Details Card -->
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Issue Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Quantity -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Quantity to Issue <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="issue_qty" id="par_issue_qty" min="1" required 
                                            onchange="validateParQuantity()" onkeyup="validateParQuantity()">
                                        <div class="form-text">
                                            <span class="text-muted">Available: </span>
                                            <span id="par_available_qty" class="fw-bold">0</span>
                                            <span id="par_available_unit"></span>
                                        </div>
                                        <div id="par_quantityError" class="text-danger small mt-1" style="display: none;">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Quantity exceeds available stock
                                        </div>
                                    </div>
                                    
                                    <!-- Issue Date -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Issue Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="issue_date" id="par_issue_date" required 
                                            value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- Requestor -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success d-flex"> Requestor <span class="text-danger">*</span></label>
                                        <select class="form-select p-2" name="requestor_id" id="par_requestor_id" required onchange="updateParDepartment()">
                                            <option value=""> Select Requestor </option>
                                            <?php foreach($employees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>" data-department="<?= $emp['office'] ?? $emp['department'] ?? '' ?>">
                                                    <?= $emp['fullname'] ?> - <?= $emp['position'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Department/Office -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Department/Office</label>
                                        <input type="text" class="form-control" name="department" id="par_department" 
                                            placeholder="Auto-fill based on requestor selection">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PAR Document Details -->
                    <div class="card mb-3 border-info">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-file-contract me-2"></i> PAR Document Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- PAR Number -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">PAR Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light" id="par_prefix"><?php echo date('Y-m'); ?>-</span>
                                            <input type="text" class="form-control" name="doc_number" id="par_doc_number" 
                                                    placeholder="000" maxlength="4" pattern="[0-9]{1,4}" 
                                                    oninput="formatParDocumentNumber()" required>
                                        </div>
                                        <small class="form-text text-muted">Format: <?php echo date('Y-m'); ?>-XXXX</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- PAR Date -->
                                    <!-- <div class="mb-3">
                                        <label class="form-label fw-bold text-success">PAR Date</label>
                                        <input type="date" class="form-control" name="doc_date" id="par_doc_date" 
                                                value="<?php echo date('Y-m-d'); ?>">
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Remarks -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Remarks</label>
                        <textarea class="form-control" name="remarks" id="par_remarks" rows="2" 
                                placeholder="Additional notes or remarks regarding this property issuance"></textarea>
                    </div>
                    
                    <!-- Required Fields Note -->
                    <div class="alert alert-info py-2">
                        <small><i class="fas fa-info-circle me-1"></i> Fields marked with <span class="text-danger">*</span> are required.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="par_submitBtn">
                        <i class="fas fa-check me-1"></i> Issue Property (PAR)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

  

<!-- ICS Issue Modal -->
<div class="modal fade" id="icsIssueModal" tabindex="-1" aria-labelledby="icsIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="icsIssueForm" method="POST" action="process_issue.php">
                  <input type="hidden" name="doc_type" value="ics">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="icsIssueModalLabel">
                        <i class="fas fa-file-invoice me-2"></i> Issue Item (ICS)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Hidden fields -->
                    <input type="hidden" name="item_id" id="ics_item_id">
                    
                    <!-- Item Information Card -->
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-light-success">
                            <h6 class="mb-0 text-success"><i class="fas fa-tools me-2"></i> Item Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Item Name</label>
                                        <input type="text" class="form-control bg-light-success" id="ics_item_name" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Inventory Item No</label>
                                        <input type="text" class="form-control bg-light-success" id="ics_inv_item_no" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Category</label>
                                        <input type="text" class="form-control bg-light-success" id="ics_category" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Unit of Measure</label>
                                        <input type="text" class="form-control bg-light-success" id="ics_unit_measure" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Unit Cost</label>
                                        <input type="text" class="form-control bg-light-success" id="ics_unit_cost" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Available Quantity</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light-success" id="ics_current_balance" readonly>
                                            <span class="input-group-text bg-light-success text-success fw-bold" id="ics_balance_unit"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Issue Details Card -->
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-light-warning">
                            <h6 class="mb-0 text-success"><i class="fas fa-clipboard-list me-2"></i> Issue Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Quantity -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Quantity to Issue <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control border-success" name="issue_qty" id="ics_issue_qty" min="1" required 
                                            onchange="validateIcsQuantity()" onkeyup="validateIcsQuantity()">
                                        <div class="form-text text-success">
                                            <span>Available: </span>
                                            <span id="ics_available_qty" class="fw-bold">0</span>
                                            <span id="ics_available_unit"></span>
                                        </div>
                                        <div id="ics_quantityError" class="text-danger small mt-1" style="display: none;">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Quantity exceeds available stock
                                        </div>
                                    </div>
                                    
                                    <!-- Issue Date -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Issue Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control border-success" name="issue_date" id="ics_issue_date" required 
                                            value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- Requestor -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success d-flex"> Requestor <span class="text-danger">*</span></label>
                                        <select class="form-select p-2 border-success" name="requestor_id" id="ics_requestor_id" required onchange="updateIcsDepartment()">
                                            <option value=""> Select Requestor </option>
                                            <?php foreach($employees as $emp): ?>
                                                <option value="<?= $emp['id'] ?>" data-department="<?= $emp['office'] ?? $emp['department'] ?? '' ?>">
                                                    <?= $emp['fullname'] ?> - <?= $emp['position'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Department/Office -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">Department/Office</label>
                                        <input type="text" class="form-control border-success" name="department" id="ics_department" 
                                            placeholder="Auto-fill based on requestor selection">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ICS Document Details -->
                    <div class="card mb-3 border-info">
                        <div class="card-header bg-light-info">
                            <h6 class="mb-0 text-success"><i class="fas fa-file-invoice me-2"></i> ICS Document Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- ICS Number -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-success">ICS Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-success text-white fw-bold" id="ics_prefix"><?php echo date('Y-m'); ?>-</span>
                                            <input type="text" class="form-control border-success" name="doc_number" id="ics_doc_number" 
                                                    placeholder="000" maxlength="4" pattern="[0-9]{1,4}" 
                                                    oninput="formatIcsDocumentNumber()" required>
                                        </div>
                                        <small class="form-text text-success">Format: <?php echo date('Y-m'); ?>-XXXX</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- ICS Date -->
                                    <!-- <div class="mb-3">
                                        <label class="form-label fw-bold text-success">ICS Date</label>
                                        <input type="date" class="form-control border-success" name="doc_date" id="ics_doc_date" 
                                                value="<?php echo date('Y-m-d'); ?>">
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Remarks -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Remarks</label>
                        <textarea class="form-control border-success" name="remarks" id="ics_remarks" rows="2" 
                                placeholder="Additional notes or remarks regarding this item issuance"></textarea>
                    </div>
                    
                    <!-- Required Fields Note -->
                    <div class="alert alert-success py-2 border-success">
                        <small><i class="fas fa-info-circle me-1"></i> Fields marked with <span class="text-danger">*</span> are required.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="ics_submitBtn">
                        <i class="fas fa-check me-1"></i> Issue Item (ICS)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>







<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include_once('layouts/footer.php'); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// PAR Modal Functions
function openParModal(itemId, itemName, propertyNo, category, unitMeasure, unitCost, currentBalance) {
    // Reset and populate PAR modal
    document.getElementById('par_item_id').value = itemId;
    document.getElementById('par_item_name').value = itemName;
    document.getElementById('par_property_no').value = propertyNo;
    document.getElementById('par_category').value = category;
    document.getElementById('par_unit_measure').value = unitMeasure;
    document.getElementById('par_unit_cost').value = '₱' + parseFloat(unitCost).toFixed(2);
    document.getElementById('par_current_balance').value = currentBalance;
    document.getElementById('par_available_qty').textContent = currentBalance;
    document.getElementById('par_available_unit').textContent = unitMeasure;
    document.getElementById('par_balance_unit').textContent = unitMeasure;
    
    // Reset form fields
    document.getElementById('par_issue_qty').value = '';
    document.getElementById('par_requestor_id').value = '';
    document.getElementById('par_department').value = '';
    document.getElementById('par_doc_number').value = '';
    document.getElementById('par_remarks').value = '';
    
    // Reset validation
    document.getElementById('par_quantityError').style.display = 'none';
    document.getElementById('par_submitBtn').disabled = false;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('parIssueModal'));
    modal.show();
}

// ICS Modal Functions
function openIcsModal(itemId, itemName, invItemNo, category, unitMeasure, unitCost, currentBalance) {
    // Reset and populate ICS modal
    document.getElementById('ics_item_id').value = itemId;
    document.getElementById('ics_item_name').value = itemName;
    document.getElementById('ics_inv_item_no').value = invItemNo;
    document.getElementById('ics_category').value = category;
    document.getElementById('ics_unit_measure').value = unitMeasure;
    document.getElementById('ics_unit_cost').value = '₱' + parseFloat(unitCost).toFixed(2);
    document.getElementById('ics_current_balance').value = currentBalance;
    document.getElementById('ics_available_qty').textContent = currentBalance;
    document.getElementById('ics_available_unit').textContent = unitMeasure;
    document.getElementById('ics_balance_unit').textContent = unitMeasure;
    
    // Reset form fields
    document.getElementById('ics_issue_qty').value = '';
    document.getElementById('ics_requestor_id').value = '';
    document.getElementById('ics_department').value = '';
    document.getElementById('ics_doc_number').value = '';
    document.getElementById('ics_remarks').value = '';
    
    // Reset validation
    document.getElementById('ics_quantityError').style.display = 'none';
    document.getElementById('ics_submitBtn').disabled = false;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('icsIssueModal'));
    modal.show();
}

// Update the table button clicks to use separate modals
function openIssueModalFromTable(itemId, itemName, stockNumber, category, unitMeasure, unitCost, currentBalance, itemType) {
    if (itemType === 'ppe') {
        openParModal(itemId, itemName, stockNumber, category, unitMeasure, unitCost, currentBalance);
    } else if (itemType === 'semi') {
        openIcsModal(itemId, itemName, stockNumber, category, unitMeasure, unitCost, currentBalance);
    }
}

// PAR Validation Functions
function validateParQuantity() {
    const issueQty = parseInt(document.getElementById('par_issue_qty').value) || 0;
    const availableQty = parseInt(document.getElementById('par_available_qty').textContent) || 0;
    const quantityError = document.getElementById('par_quantityError');
    const submitBtn = document.getElementById('par_submitBtn');
    
    if (issueQty > availableQty) {
        quantityError.style.display = 'block';
        submitBtn.disabled = true;
    } else {
        quantityError.style.display = 'none';
        submitBtn.disabled = false;
    }
}

// ICS Validation Functions
function validateIcsQuantity() {
    const issueQty = parseInt(document.getElementById('ics_issue_qty').value) || 0;
    const availableQty = parseInt(document.getElementById('ics_available_qty').textContent) || 0;
    const quantityError = document.getElementById('ics_quantityError');
    const submitBtn = document.getElementById('ics_submitBtn');
    
    if (issueQty > availableQty) {
        quantityError.style.display = 'block';
        submitBtn.disabled = true;
    } else {
        quantityError.style.display = 'none';
        submitBtn.disabled = false;
    }
}

// PAR Department Update
function updateParDepartment() {
    const requestorSelect = document.getElementById('par_requestor_id');
    const departmentInput = document.getElementById('par_department');
    const selectedOption = requestorSelect.options[requestorSelect.selectedIndex];
    
    if (selectedOption.value !== '') {
        const department = selectedOption.getAttribute('data-department');
        departmentInput.value = department || '';
    } else {
        departmentInput.value = '';
    }
}

// ICS Department Update
function updateIcsDepartment() {
    const requestorSelect = document.getElementById('ics_requestor_id');
    const departmentInput = document.getElementById('ics_department');
    const selectedOption = requestorSelect.options[requestorSelect.selectedIndex];
    
    if (selectedOption.value !== '') {
        const department = selectedOption.getAttribute('data-department');
        departmentInput.value = department || '';
    } else {
        departmentInput.value = '';
    }
}

// PAR Document Number Formatting
function formatParDocumentNumber() {
    const docNumberInput = document.getElementById('par_doc_number');
    docNumberInput.value = docNumberInput.value.replace(/[^0-9]/g, '');
    if (docNumberInput.value.length > 4) {
        docNumberInput.value = docNumberInput.value.slice(0, 4);
    }
}

// ICS Document Number Formatting
function formatIcsDocumentNumber() {
    const docNumberInput = document.getElementById('ics_doc_number');
    docNumberInput.value = docNumberInput.value.replace(/[^0-9]/g, '');
    if (docNumberInput.value.length > 4) {
        docNumberInput.value = docNumberInput.value.slice(0, 4);
    }
}

// Helper function to get full document number
function getFullDocumentNumber(type, suffix) {
    const current_year = new Date().getFullYear();
    const current_month = String(new Date().getMonth() + 1).padStart(2, '0');
    return `${current_year}-${current_month}-${String(suffix).padStart(4, '0')}`;
}

// Add real-time duplicate checking for document numbers
function checkDuplicateDocumentNumber(type, suffix, callback) {
    const fullDocNumber = getFullDocumentNumber(type, suffix);
    
    fetch('check_docu_no.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `doc_type=${type}&doc_number=${fullDocNumber}`
    })
    .then(response => response.json())
    .then(data => {
        callback(data.exists);
    })
    .catch(error => {
        console.error('Error checking document number:', error);
        callback(false);
    });
}

function createErrorDiv(fieldId) {
    const errorDiv = document.createElement('div');
    errorDiv.id = fieldId + '_error';
    errorDiv.className = 'text-danger small mt-1';
    errorDiv.style.display = 'none';
    document.getElementById(fieldId).parentNode.appendChild(errorDiv);
    return errorDiv;
}

// Add duplicate checking to document number inputs
document.addEventListener('DOMContentLoaded', function() {
    // PAR document number duplicate check
    const parDocNumberInput = document.getElementById('par_doc_number');
    if (parDocNumberInput) {
        parDocNumberInput.addEventListener('blur', function() {
            const suffix = this.value;
            if (suffix) {
                checkDuplicateDocumentNumber('par', suffix, function(exists) {
                    const errorDiv = document.getElementById('par_doc_number_error') || createErrorDiv('par_doc_number');
                    if (exists) {
                        errorDiv.style.display = 'block';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> This PAR number already exists';
                        document.getElementById('par_submitBtn').disabled = true;
                    } else {
                        errorDiv.style.display = 'none';
                        document.getElementById('par_submitBtn').disabled = false;
                    }
                });
            }
        });
    }

    // ICS document number duplicate check
    const icsDocNumberInput = document.getElementById('ics_doc_number');
    if (icsDocNumberInput) {
        icsDocNumberInput.addEventListener('blur', function() {
            const suffix = this.value;
            if (suffix) {
                checkDuplicateDocumentNumber('ics', suffix, function(exists) {
                    const errorDiv = document.getElementById('ics_doc_number_error') || createErrorDiv('ics_doc_number');
                    if (exists) {
                        errorDiv.style.display = 'block';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> This ICS number already exists';
                        document.getElementById('ics_submitBtn').disabled = true;
                    } else {
                        errorDiv.style.display = 'none';
                        document.getElementById('ics_submitBtn').disabled = false;
                    }
                });
            }
        });
    }
});

// Form Submission Handlers
document.addEventListener('DOMContentLoaded', function() {
    // PAR form submission
    const parForm = document.getElementById('parIssueForm');
    if (parForm) {
        parForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitIssueForm(this, 'par');
        });
    }

    // ICS form submission
    const icsForm = document.getElementById('icsIssueForm');
    if (icsForm) {
        icsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitIssueForm(this, 'ics');
        });
    }
});

function submitIssueForm(form, type) {
    const submitBtn = document.getElementById(type + '_submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
    submitBtn.disabled = true;
    
    // Get form data
    const formData = new FormData(form);
    
    // Add doc_type to form data
    formData.append('doc_type', type);
    
    // Validate document number
    const docNumber = formData.get('doc_number');
    if (!docNumber || docNumber.trim() === '') {
        Swal.fire({
            icon: 'error',
            title: 'Missing Document Number',
            text: 'Please enter a document number.',
            confirmButtonColor: '#3085d6'
        });
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        return;
    }
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Confirm Issue?',
        text: `Are you sure you want to issue this item with ${type.toUpperCase()} number: ${getFullDocumentNumber(type, docNumber)}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Issue Item'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request
            fetch('process_issue.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Item Issued!',
                        html: `
                            <div class="text-center">
                                <p>${data.message}</p>
                                <p class="fw-bold">Document Number: ${data.document_number}</p>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById(type + 'IssueModal'));
                        modal.hide();
                        
                        // Refresh the page to update statistics and tables
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message,
                        confirmButtonColor: '#3085d6'
                    });
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'An error occurred while processing your request. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        } else {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Function to remove item from table when quantity becomes 0
function removeItemFromTable(itemId, type) {
    const tableId = type === 'par' ? 'ppe-table' : 'semi-table';
    const table = $('#' + tableId).DataTable();
    
    // Find and remove the row
    table.rows().every(function() {
        const row = this;
        const rowData = row.data();
        // Assuming the item ID is in the first column or you can adjust the selector
        if (rowData[0] == itemId || rowData.includes(itemId.toString())) {
            row.remove().draw();
            return false; // Break the loop
        }
    });
}

// Initialize modals on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set today's date for both modals
    const today = new Date().toISOString().split('T')[0];
    
    const parIssueDate = document.getElementById('par_issue_date');
    const parDocDate = document.getElementById('par_doc_date');
    const icsIssueDate = document.getElementById('ics_issue_date');
    const icsDocDate = document.getElementById('ics_doc_date');
    
    if (parIssueDate) parIssueDate.value = today;
    if (parDocDate) parDocDate.value = today;
    if (icsIssueDate) icsIssueDate.value = today;
    if (icsDocDate) icsDocDate.value = today;
});

// Tab functionality
$(document).ready(function() {
    $('.nav-tab-link').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        
        $('.nav-tab-link').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active');
        $(target).addClass('active');
        
        if (history.pushState) {
            history.pushState(null, null, target);
        }
    });

    // Check URL hash on page load
    function checkHash() {
        const hash = window.location.hash;
        if (hash) {
            const targetTab = $('.nav-tab-link[href="' + hash + '"]');
            if (targetTab.length) {
                $('.nav-tab-link').removeClass('active');
                targetTab.addClass('active');
                $('.tab-pane').removeClass('active');
                $(hash).addClass('active');
            }
        }
    }
    checkHash();

    // Handle browser back/forward buttons
    $(window).on('popstate', function() {
        checkHash();
    });
});

// DataTables initialization
$(document).ready(function() {
    $('#ppe-table').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        searching: false,
        autoWidth: false,
        scrollX: false,
        responsive: false,
    });

    $('#semi-table').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        searching: false,
        autoWidth: false,
        scrollX: false,
        responsive: false,
    });
});
</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>