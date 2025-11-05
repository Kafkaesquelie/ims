<?php
$page_title = 'Returned Items';
require_once('includes/load.php');
page_require_level(3);

// Get current user
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Fetch returned items for current user
$returned_items = find_by_sql("
    SELECT 
        ri.*,
        t.ICS_No,
        t.PAR_No,
        t.quantity as original_qty,
        s.item as item_name,
        s.item_description,
        s.inv_item_no,
        s.unit,
        s.unit_cost,
        e.first_name,
        e.middle_name,
        e.last_name,
        e.position,
        e.office
    FROM return_items ri
    JOIN transactions t ON ri.transaction_id = t.id
    LEFT JOIN semi_exp_prop s ON t.item_id = s.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE e.user_id = '{$user_id}'
      AND ri.return_date IS NOT NULL
    ORDER BY ri.return_date DESC, ri.created_at DESC
");

// Debug: Uncomment to see fetched data
// echo "<pre>"; print_r($returned_items); echo "</pre>";
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
    
    .card-header-custom {
        background: linear-gradient(135deg, #005113, #008000);
        color: white;
        border-radius: 10px 10px 0 0;
        padding: 1rem;
    }
    
    .btn-view {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
    }
    
    .btn-view:hover {
        background: linear-gradient(135deg, #0056b3, #004085);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4);
        color: white;
    }
    
    .badge-quantity {
        background: linear-gradient(135deg, #fd7e14, #e55a00);
        color: white;
        border-radius: 12px;
        padding: 4px 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .badge-returned {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        border-radius: 12px;
        padding: 4px 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.05);
        transition: all 0.2s ease;
    }
    
    .item-details {
        line-height: 1.4;
    }
    
    .document-info {
        line-height: 1.4;
    }
    
    .condition-text {
        font-weight: 500;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .condition-functional {
        color: #28a745;
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .condition-non-functional {
        color: #dc3545;
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .condition-repair {
        color: #ffc107;
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow-x: auto;
        }
        
        /* Stack table headers for mobile */
        #returnsTable thead {
            display: none;
        }
        
        #returnsTable tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        #returnsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0.5rem;
            border: none;
            border-bottom: 1px solid #f8f9fa;
            text-align: left !important;
        }
        
        #returnsTable tbody td:last-child {
            border-bottom: none;
            justify-content: center;
        }
        
        #returnsTable tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            min-width: 100px;
            margin-right: 1rem;
        }
        
        /* Adjust buttons for mobile */
        .btn-view {
            width: 100%;
            padding: 10px 16px;
            font-size: 0.9rem;
            text-align: center;
        }
        
        /* Card adjustments */
        .card-body {
            padding: 1rem;
        }
        
        /* Badge adjustments for mobile */
        .badge-quantity, .badge-returned {
            font-size: 0.75rem;
            padding: 6px 12px;
        }
        
        /* Header adjustments */
        .card-header-custom h3 {
            font-size: 1.3rem;
        }
        
        /* Item details adjustments */
        .item-details {
            text-align: left;
        }
        
        .item-details strong {
            font-size: 0.9rem;
        }
        
        .item-details small {
            font-size: 0.8rem;
        }
        
        /* Condition text adjustments */
        .condition-text {
            font-size: 0.8rem;
            padding: 3px 6px;
        }
        
        /* Modal adjustments for mobile */
        .modal-dialog {
            margin: 0.5rem;
        }
        
        .modal-content {
            border-radius: 0.5rem;
        }
    }
    
    /* Small mobile devices */
    @media (max-width: 576px) {
        #returnsTable tbody td {
            flex-direction: column;
            align-items: flex-start;
            padding: 0.75rem 0.5rem;
        }
        
        #returnsTable tbody td::before {
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
        .badge-quantity, .badge-returned {
            font-size: 0.7rem;
            padding: 4px 8px;
        }
        
        .card-header-custom {
            border-radius: 0;
        }
        
        /* Header adjustments for very small screens */
        .card-header-custom h3 {
            font-size: 1.1rem;
        }
        
        /* Button adjustments */
        .btn-view {
            font-size: 0.8rem;
            padding: 8px 12px;
        }
    }
    
    /* Medium devices adjustment */
    @media (max-width: 992px) and (min-width: 769px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .btn-view {
            font-size: 0.8rem;
            padding: 6px 12px;
        }
        
        .card-header-custom h3 {
            font-size: 1.2rem;
        }
    }
    
    /* Extra small devices */
    @media (max-width: 400px) {
        #returnsTable tbody td::before {
            min-width: 80px;
            font-size: 0.75rem;
        }
        
        .item-details strong {
            font-size: 0.85rem;
        }
        
        .item-details small {
            font-size: 0.75rem;
        }
        
        .card-header-custom h3 {
            font-size: 1rem;
        }
        
        .btn-view {
            font-size: 0.75rem;
            padding: 6px 10px;
        }
        
        /* Modal adjustments for extra small screens */
        .modal-dialog {
            margin: 0.25rem;
        }
        
        .modal-body {
            padding: 1rem 0.75rem;
        }
        
        .form-control-plaintext {
            padding: 6px 8px;
            font-size: 0.875rem;
        }
    }
    
    /* DataTables mobile adjustments */
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
    
    /* Modal styling */
    .modal-header {
        background: linear-gradient(135deg, #005113, #008000);
        border-bottom: none;
    }
    
    .modal-title {
        font-weight: 600;
    }
    
    .form-control-plaintext {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        min-height: 42px;
        display: flex;
        align-items: center;
    }
    
    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 4px;
    }
    
    .detail-value {
        color: #212529;
        font-weight: 500;
    }
    
    /* Center and widen close button */
    .modal-footer {
        display: flex;
        justify-content: center;
        padding: 1.5rem;
        border-top: 1px solid #dee2e6;
    }
    
    .btn-close-modal {
        width: 200px;
        padding: 12px 24px;
        font-size: 1.1rem;
        font-weight: 600;
        background: linear-gradient(135deg, #6c757d, #5a6268);
        border: none;
        border-radius: 8px;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
    }
    
    .btn-close-modal:hover {
        background: linear-gradient(135deg, #5a6268, #495057);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.4);
        color: white;
    }
    
    /* Mobile modal adjustments */
    @media (max-width: 768px) {
        .btn-close-modal {
            width: 100%;
            font-size: 1rem;
            padding: 10px 20px;
        }
        
        .modal-footer {
            padding: 1rem;
        }
        
        .form-control-plaintext {
            min-height: 38px;
            font-size: 0.9rem;
        }
        
        .detail-label {
            font-size: 0.9rem;
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header card-header-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="nav-icon fa-solid fa-rotate-left"></i> 
                        Returned Items
                    </h3>
                </div>
            </div>
            <div class="card-body">
                <?php if(!empty($returned_items)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="returnsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item Details</th>
                                    <th>Document No.</th>
                                    <th>Quantity</th>
                                    <th>Return Date</th>
                                    <th>Condition</th>
                                    <th>Remarks</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach($returned_items as $return): ?>
                                    <tr>
                                        <td class="text-center" data-label="#"><?php echo $counter++; ?></td>
                                        <td data-label="Item Details">
                                            <div class="item-details">
                                                <strong class="text-primary"><?php echo remove_junk($return['item_name'] ?? 'N/A'); ?></strong>
                                                <?php if(!empty($return['item_description'])): ?>
                                                    <br><small class="text-muted"><?php echo remove_junk($return['item_description']); ?></small>
                                                <?php endif; ?>
                                                <?php if(!empty($return['inv_item_no'])): ?>
                                                    <br><small><strong>Item No:</strong> <?php echo remove_junk($return['inv_item_no']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Document No.">
                                            <div class="document-info">
                                                <?php if(!empty($return['ICS_No'])): ?>
                                                    <div class="text-primary">
                                                        <strong>ICS:</strong> <?php echo remove_junk($return['ICS_No']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if(!empty($return['PAR_No'])): ?>
                                                    <div class="text-info">
                                                        <strong>PAR:</strong> <?php echo remove_junk($return['PAR_No']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if(empty($return['ICS_No']) && empty($return['PAR_No'])): ?>
                                                    <span class="text-muted">No Document</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center" data-label="Quantity">
                                            <span class="badge badge-quantity">
                                                <i class="fa-solid fa-layer-group"></i>
                                                <?php echo number_format((float)$return['qty'], 2); ?>
                                            </span>
                                            <?php if(!empty($return['original_qty'])): ?>
                                                <br><small class="text-muted">Original: <?php echo number_format((float)$return['original_qty'], 2); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center" data-label="Return Date">
                                            <span class="badge badge-returned">
                                                <i class="fa-solid fa-calendar-check"></i>
                                                <?php echo date('M d, Y', strtotime($return['return_date'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-center" data-label="Condition">
                                            <?php 
                                                $condition = strtolower($return['conditions'] ?? '');
                                                $condition_class = 'condition-text';
                                                
                                                if (strpos($condition, 'functional') !== false) {
                                                    $condition_class .= ' condition-functional';
                                                } elseif (strpos($condition, 'non-functional') !== false || strpos($condition, 'defective') !== false) {
                                                    $condition_class .= ' condition-non-functional';
                                                } elseif (strpos($condition, 'repair') !== false) {
                                                    $condition_class .= ' condition-repair';
                                                }
                                            ?>
                                            <span class="<?php echo $condition_class; ?>">
                                                <?php echo remove_junk($return['conditions'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td data-label="Remarks">
                                            <?php echo !empty($return['remarks']) ? remove_junk($return['remarks']) : '<span class="text-muted">No remarks</span>'; ?>
                                        </td>
                                        <td class="text-center" data-label="Actions">
                                            <button class="btn btn-view view-return-details" 
                                                    data-item="<?php echo remove_junk($return['item_name'] ?? 'N/A'); ?>"
                                                    data-description="<?php echo remove_junk($return['item_description'] ?? 'N/A'); ?>"
                                                    data-inv-item-no="<?php echo remove_junk($return['inv_item_no'] ?? 'N/A'); ?>"
                                                    data-ics="<?php echo remove_junk($return['ICS_No'] ?? 'N/A'); ?>"
                                                    data-par="<?php echo remove_junk($return['PAR_No'] ?? 'N/A'); ?>"
                                                    data-quantity="<?php echo number_format((float)$return['qty'], 2); ?>"
                                                    data-original="<?php echo number_format((float)$return['original_qty'], 2); ?>"
                                                    data-return-date="<?php echo date('M d, Y', strtotime($return['return_date'])); ?>"
                                                    data-condition="<?php echo remove_junk($return['conditions'] ?? 'N/A'); ?>"
                                                    data-remarks="<?php echo remove_junk($return['remarks'] ?? 'No remarks'); ?>"
                                                    data-unit-cost="<?php echo !empty($return['unit_cost']) ? '₱' . number_format((float)$return['unit_cost'], 2) : 'N/A'; ?>"
                                                    data-unit="<?php echo remove_junk($return['unit'] ?? 'N/A'); ?>">
                                                <i class="fa-solid fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fa-solid fa-info-circle fa-2x mb-3"></i>
                        <h4>No Returned Items Found</h4>
                        <p class="mb-0">You don't have any returned items at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Return Details -->
<div class="modal fade" id="returnDetailsModal" tabindex="-1" aria-labelledby="returnDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="returnDetailsModalLabel">
                    <i class="fa-solid fa-circle-info"></i> Return Item Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">Item Name:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-item-name" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">Inventory Item No:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-inv-item-no" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="detail-label">Description:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-item-description" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">ICS Number:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-ics" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">PAR Number:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-par" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="detail-label">Quantity Returned:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-quantity" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="detail-label">Original Quantity:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-original" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="detail-label">Unit:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-unit" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">Unit Cost:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-unit-cost" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">Return Date:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-return-date" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">Condition:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-condition" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="detail-label">Remarks:</label>
                            <div class="form-control-plaintext">
                                <span id="modal-remarks" class="detail-value"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    <?php if(!empty($returned_items)): ?>
    var table = $('#returnsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: true,
        scrollX: false,
        order: [[4, 'desc']], // Sort by return date descending
        columnDefs: [
            { orderable: false, targets: [0, 7] }, // Make # and Actions columns non-orderable
            { responsivePriority: 1, targets: 1 }, // Item Details - highest priority
            { responsivePriority: 2, targets: 7 }, // Actions - second priority
            { responsivePriority: 3, targets: 4 }, // Return Date - third priority
            { responsivePriority: 4, targets: 3 }, // Quantity - fourth priority
            { responsivePriority: 5, targets: 5 }, // Condition - fifth priority
            { responsivePriority: 6, targets: 2 }, // Document No. - sixth priority
            { responsivePriority: 7, targets: 6 }  // Remarks - lowest priority
        ],
        language: {
            search: "Search returns:",
            searchPlaceholder: "Search items...",
            lengthMenu: "Show _MENU_ returns",
            info: "Showing _START_ to _END_ of _TOTAL_ returns",
            infoEmpty: "No returns available",
            infoFiltered: "(filtered from _MAX_ total returns)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        // Mobile responsive settings
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Return Details: ' + data[1];
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
                $('.card-body').prepend(`
                    <div class="mb-3" id="mobileSearch">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="mobileSearchInput" placeholder="Search returned items...">
                        </div>
                    </div>
                `);
                
                // Bind search functionality
                $('#mobileSearchInput').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
            
            // Adjust pagination for mobile
            $('.dataTables_paginate').addClass('pagination-sm');
        } else {
            // Show original search box and remove mobile search
            $('.dataTables_filter').removeClass('d-none');
            $('#mobileSearch').remove();
            $('.dataTables_paginate').removeClass('pagination-sm');
        }
    }
    
    // Initialize mobile layout
    handleMobileLayout();
    $(window).on('resize', handleMobileLayout);
    
    <?php endif; ?>

    // View return details modal
    $(document).on('click', '.view-return-details', function() {
        var itemName = $(this).data('item');
        var description = $(this).data('description');
        var invItemNo = $(this).data('inv-item-no');
        var ics = $(this).data('ics');
        var par = $(this).data('par');
        var quantity = $(this).data('quantity');
        var original = $(this).data('original');
        var returnDate = $(this).data('return-date');
        var condition = $(this).data('condition');
        var remarks = $(this).data('remarks');
        var unitCost = $(this).data('unit-cost');
        var unit = $(this).data('unit');

        $('#modal-item-name').text(itemName);
        $('#modal-item-description').text(description);
        $('#modal-inv-item-no').text(invItemNo);
        $('#modal-ics').text(ics);
        $('#modal-par').text(par);
        $('#modal-quantity').text(quantity);
        $('#modal-original').text(original);
        $('#modal-return-date').text(returnDate);
        $('#modal-condition').text(condition);
        $('#modal-remarks').text(remarks);
        $('#modal-unit-cost').text(unitCost);
        $('#modal-unit').text(unit);

        $('#returnDetailsModal').modal('show');
    });
});
</script>