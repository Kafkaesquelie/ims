<?php
$page_title = 'View PAR Details';
require_once('includes/load.php');
page_require_level(3);

// Get current user
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Get PAR number from URL
$par_no = isset($_GET['par_no']) ? $_GET['par_no'] : '';

if(empty($par_no)) {
    $session->msg("d", "PAR number not specified.");
    redirect('par.php');
}

// Fetch PAR items from transactions table linked with properties
$par_items = find_by_sql("
    SELECT 
        t.*,
        p.property_no,
        p.article,
        p.description,
        p.unit,
        p.unit_cost,
        p.date_acquired,
        t.remarks,
        e.first_name,
        e.middle_name,
        e.last_name,
        e.position,
        e.office
    FROM transactions t
    JOIN properties p ON t.properties_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.PAR_No = '{$db->escape($par_no)}'
      AND e.user_id = '{$user_id}'
      AND t.PAR_No IS NOT NULL
      AND t.PAR_No != ''
    ORDER BY t.transaction_date DESC, t.id DESC
");

if(empty($par_items)) {
    $session->msg("d", "PAR not found or you don't have permission to view it.");
    redirect('par.php');
}

// Calculate totals
$total_items = count($par_items);
$total_quantity = 0;
$total_amount = 0;

foreach($par_items as $item) {
    $quantity = (int)$item['quantity'];
    $unit_cost = (float)$item['unit_cost'];
    $item_total = $quantity * $unit_cost;
    
    $total_quantity += $quantity;
    $total_amount += $item_total;
}
?>

<?php include_once('layouts/header.php'); ?>

<style>
    .table th {
        background: #005113;
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        text-align: center;
    }
    
    .info-card {
        border-left: 4px solid #28a745;
        border-radius: 10px;
        background: linear-gradient(135deg, #f8fff8, #e8f5e8);
        box-shadow: 0 4px 6px rgba(0, 80, 0, 0.1);
    }
    
    .total-badge {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        border-radius: 12px;
        padding: 6px 12px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .amount-badge {
        background: linear-gradient(135deg, #20c997, #198754);
        color: white;
        border-radius: 12px;
        padding: 6px 12px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .employee-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .employee-details {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .btn-back {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }
    
    .btn-back:hover {
        background: linear-gradient(135deg, #1e7e34, #155724);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
        color: white;
    }
    
    .property-card {
        background: linear-gradient(135deg, #f8fff8, #e8f5e8);
        border-radius: 10px;
        border-left: 4px solid #28a745;
        box-shadow: 0 4px 6px rgba(0, 80, 0, 0.1);
    }
    
    .text-right {
        text-align: right;
    }
    
    .card-title {
        color: #005113;
        font-weight: 600;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.05);
        transform: scale(1.01);
        transition: all 0.2s ease;
    }
    
    .badge-primary {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }
    
    .badge-success {
        background: linear-gradient(135deg, #20c997, #198754);
    }
    
    .badge-info {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }
    
    .table-secondary {
        background: linear-gradient(135deg, #e8f5e8, #d4edda) !important;
        font-weight: 600;
    }
    
    .green-gradient-header {
        background-color: #005113;
        color: white;
        padding: 1rem;
        border-radius: 10px 10px 0 0;
    }
    
    .leaf-icon {
        color: #28a745;
        margin-right: 8px;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }
        
        /* Header adjustments */
        .green-gradient-header {
            padding: 0.75rem;
        }
        
        .green-gradient-header h3 {
            font-size: 1.2rem;
        }
        
        .btn-back {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        /* Summary cards stack on mobile */
        .row.mb-4 .col-md-4 {
            margin-bottom: 1rem;
        }
        
        .info-card {
            margin-bottom: 1rem;
        }
        
        /* Table responsive design */
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow-x: auto;
        }
        
        /* Stack table headers for mobile */
        #parItemsTable thead {
            display: none;
        }
        
        #parItemsTable tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        #parItemsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0.5rem;
            border: none;
            border-bottom: 1px solid #f8f9fa;
            text-align: left !important;
        }
        
        #parItemsTable tbody td:last-child {
            border-bottom: none;
        }
        
        #parItemsTable tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            min-width: 120px;
            margin-right: 1rem;
        }
        
        /* Adjust badges for mobile */
        .total-badge, .amount-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
        
        /* Employee info adjustments */
        .employee-info {
            text-align: left;
        }
        
        .employee-name {
            font-size: 0.9rem;
        }
        
        .employee-details {
            font-size: 0.8rem;
        }
        
        /* Card title adjustments */
        .card-title {
            font-size: 1.1rem;
        }
        
        /* Hide table footer on mobile - will be shown in summary cards */
        #parItemsTable tfoot {
            display: none;
        }
        
        /* Mobile summary section to replace table footer */
        .mobile-summary {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid #28a745;
        }
    }
    
    /* Small mobile devices */
    @media (max-width: 576px) {
        #parItemsTable tbody td {
            flex-direction: column;
            align-items: flex-start;
            padding: 0.75rem 0.5rem;
        }
        
        #parItemsTable tbody td::before {
            margin-bottom: 0.25rem;
            min-width: auto;
            font-size: 0.8rem;
        }
        
        .card {
            margin: 0 -0.75rem;
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        
        .container-fluid {
            padding: 0;
        }
        
        /* Adjust badge sizes for very small screens */
        .total-badge, .amount-badge {
            font-size: 0.75rem;
            padding: 3px 6px;
        }
        
        .green-gradient-header {
            border-radius: 0;
        }
        
        .btn-back {
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .green-gradient-header .d-flex {
            flex-direction: column;
            text-align: center;
        }
    }
    
    /* Medium devices adjustment */
    @media (max-width: 992px) and (min-width: 769px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .btn-back {
            font-size: 0.8rem;
            padding: 6px 12px;
        }
        
        .card-title {
            font-size: 1.1rem;
        }
    }
    
    /* Extra small devices */
    @media (max-width: 400px) {
        #parItemsTable tbody td::before {
            min-width: 80px;
            font-size: 0.75rem;
        }
        
        .employee-name {
            font-size: 0.85rem;
        }
        
        .employee-details {
            font-size: 0.75rem;
        }
        
        .info-card .card-body {
            padding: 1rem 0.5rem;
        }
        
        .total-badge, .amount-badge {
            font-size: 0.7rem;
        }
    }
    
    /* Ensure proper spacing for mobile DataTables */
    .dataTables_wrapper {
        overflow-x: hidden;
    }
    
    /* Mobile search and pagination adjustments */
    @media (max-width: 768px) {
        .dataTables_length,
        .dataTables_filter {
            margin-bottom: 0.5rem;
        }
        
        .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
            font-size: 0.875rem;
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header green-gradient-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="nav-icon fa-solid fa-file-invoice"></i> 
                        PAR Details: <?php echo remove_junk($par_no); ?>
                    </h3>
                    <a href="par.php" class="btn btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- PAR Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fa-solid fa-info-circle leaf-icon"></i>
                                    PAR Information
                                </h5>
                                <div class="mb-3">
                                    <strong>PAR Number:</strong><br>
                                    <span class="text-success font-weight-bold fs-5"><?php echo remove_junk($par_no); ?></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Total Items:</strong><br>
                                    <span class="total-badge">
                                        <i class="fa-solid fa-cube"></i>
                                        <?php echo $total_items; ?> item<?php echo $total_items > 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fa-solid fa-calculator leaf-icon"></i>
                                    Quantity & Amount
                                </h5>
                                <div class="mb-3">
                                    <strong>Total Quantity:</strong><br>
                                    <span class="total-badge">
                                        <i class="fa-solid fa-layer-group"></i>
                                        <?php echo number_format($total_quantity); ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Total Amount:</strong><br>
                                    <span class="amount-badge">
                                        <i class="fa-solid fa-peso-sign"></i>
                                        ₱<?php echo number_format($total_amount, 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fa-solid fa-user-check leaf-icon"></i>
                                    Issued To
                                </h5>
                                <?php if(!empty($par_items[0]['first_name'])): ?>
                                    <div class="employee-name mb-2">
                                        <?php 
                                            echo remove_junk($par_items[0]['first_name']) . ' ';
                                            if(!empty($par_items[0]['middle_name'])) {
                                                echo remove_junk($par_items[0]['middle_name']) . ' ';
                                            }
                                            echo remove_junk($par_items[0]['last_name']);
                                        ?>
                                    </div>
                                    <div class="employee-details">
                                        <?php if(!empty($par_items[0]['position'])): ?>
                                            <div><i class="fa-solid fa-briefcase text-success"></i> <?php echo remove_junk($par_items[0]['position']); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($par_items[0]['office'])): ?>
                                            <div><i class="fa-solid fa-location-dot text-success"></i> <?php echo remove_junk($par_items[0]['office']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted"><i class="fa-solid fa-user-slash"></i> No employee information available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="card property-card">
                    <div class="card-header bg-transparent border-bottom">
                        <h4 class="card-title mb-0 text-success">
                            <i class="fa-solid fa-list-check leaf-icon"></i> 
                            Property Items
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="parItemsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Item Name</th>
                                        <th>Description</th>
                                        <th>Property No.</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Unit Cost</th>
                                        <th>Total Amount</th>
                                        <th>Date Acquired</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach($par_items as $item): ?>
                                        <?php 
                                            $quantity = (int)$item['quantity'];
                                            $unit_cost = (float)$item['unit_cost'];
                                            $item_total = $quantity * $unit_cost;
                                        ?>
                                        <tr>
                                            <td class="text-center" data-label="#"><?php echo $counter++; ?></td>
                                            <td data-label="Item Name"><?php echo remove_junk($item['article'] ?? 'N/A'); ?></td>
                                            <td data-label="Description"><?php echo remove_junk($item['description'] ?? 'N/A'); ?></td>
                                            <td class="text-center" data-label="Property No.">
                                                <?php if(!empty($item['property_no'])): ?>
                                                    <span class="badge badge-info"><?php echo remove_junk($item['property_no']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center" data-label="Quantity">
                                                <span class="badge badge-primary"><?php echo number_format($quantity); ?></span>
                                            </td>
                                            <td class="text-center" data-label="Unit"><?php echo remove_junk($item['unit'] ?? 'N/A'); ?></td>
                                            <td class="text-right" data-label="Unit Cost">
                                                <?php if(!empty($unit_cost)): ?>
                                                    ₱<?php echo number_format($unit_cost, 2); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right" data-label="Total Amount">
                                                <span class="badge badge-success">
                                                    ₱<?php echo number_format($item_total, 2); ?>
                                                </span>
                                            </td>
                                            <td class="text-center" data-label="Date Acquired">
                                                <?php 
                                                    if (!empty($item['date_acquired']) && $item['date_acquired'] != '0000-00-00') {
                                                        echo date('M d, Y', strtotime($item['date_acquired']));
                                                    } else {
                                                        echo '<span class="text-muted">N/A</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td data-label="Remarks"><?php echo remove_junk($item['remarks'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary">
                                        <td colspan="4" class="text-right"><strong>Grand Totals:</strong></td>
                                        <td class="text-center"><strong><?php echo number_format($total_quantity); ?></strong></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-right">
                                            <strong class="text-success">₱<?php echo number_format($total_amount, 2); ?></strong>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Mobile Summary (shown only on mobile) -->
                        <div class="mobile-summary d-md-none">
                            <h5 class="text-success mb-3"><i class="fa-solid fa-calculator"></i> Summary</h5>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <strong>Total Quantity</strong><br>
                                        <span class="total-badge"><?php echo number_format($total_quantity); ?></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <strong>Total Amount</strong><br>
                                        <span class="amount-badge">₱<?php echo number_format($total_amount, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
$(document).ready(function () {
    <?php if(!empty($par_items)): ?>
    var table = $('#parItemsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: true,
        scrollX: false,
        scrollCollapse: true,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0] }, // Make the # column non-orderable
            { responsivePriority: 1, targets: 1 }, // Item Name - high priority
            { responsivePriority: 2, targets: 3 }, // Property No. - high priority
            { responsivePriority: 3, targets: 4 }, // Quantity - high priority
            { responsivePriority: 4, targets: 8 }, // Date Acquired - medium priority
            { responsivePriority: 5, targets: 7 }, // Total Amount - medium priority
            { responsivePriority: 6, targets: 6 }, // Unit Cost - low priority
            { responsivePriority: 7, targets: 5 }, // Unit - low priority
            { responsivePriority: 8, targets: 2 }, // Description - low priority
            { responsivePriority: 9, targets: 9 }  // Remarks - lowest priority
        ],
        language: {
            search: "Search items:",
            lengthMenu: "Show _MENU_ items",
            info: "Showing _START_ to _END_ of _TOTAL_ items",
            infoEmpty: "No items available",
            infoFiltered: "(filtered from _MAX_ total items)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        // Mobile responsive settings
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Item Details: ' + data[1];
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                    tableClass: 'table'
                })
            }
        }
    });
    
    // Mobile-specific adjustments
    function handleMobileLayout() {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Hide the original search box and create a mobile-friendly one
            $('.dataTables_filter').addClass('d-none');
            
            // Create mobile search if it doesn't exist
            if ($('#mobileSearch').length === 0) {
                $('.property-card .card-body').prepend(`
                    <div class="mb-3" id="mobileSearch">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="mobileSearchInput" placeholder="Search property items...">
                        </div>
                    </div>
                `);
                
                // Bind search functionality
                $('#mobileSearchInput').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
        } else {
            // Show original search box and remove mobile search
            $('.dataTables_filter').removeClass('d-none');
            $('#mobileSearch').remove();
        }
    }
    
    // Initialize mobile layout
    handleMobileLayout();
    $(window).on('resize', handleMobileLayout);
    
    <?php endif; ?>
});
</script>