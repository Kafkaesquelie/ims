<?php
$page_title = 'ICS Files';
require_once('includes/load.php');
page_require_level(3);

// Get current user
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Fetch distinct ICS files grouped by ICS_No with item count
$ics_files = find_by_sql("
    SELECT 
        t.ICS_No,
        COUNT(t.id) as item_count,
        SUM(t.quantity) as total_quantity,
        MIN(t.transaction_date) as transaction_date,
        e.first_name,
        e.middle_name,
        e.last_name,
        e.position
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE e.user_id = '{$user_id}'
      AND t.ICS_No IS NOT NULL
      AND t.ICS_No != ''
    GROUP BY t.ICS_No
    ORDER BY MIN(t.transaction_date) DESC
");

// Alternative query if you also want to include direct user assignments:
/*
$ics_files = find_by_sql("
    SELECT 
        t.ICS_No,
        COUNT(t.id) as item_count,
        SUM(t.quantity) as total_quantity,
        MIN(t.transaction_date) as transaction_date,
        COALESCE(e.first_name, u.name) as first_name,
        e.middle_name,
        COALESCE(e.last_name, '') as last_name,
        e.position,
        e.department
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE (e.user_id = '{$user_id}' OR t.user_id = '{$user_id}')
      AND t.ICS_No IS NOT NULL
      AND t.ICS_No != ''
    GROUP BY t.ICS_No
    ORDER BY MIN(t.transaction_date) DESC
");
*/

// Debug: Uncomment to see fetched data
// echo "<pre>"; print_r($ics_files); echo "</pre>";
?>

<?php include_once('layouts/header.php'); ?>
<style>
   .table th {
        background: #005113ff;
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        text-align: center;
    }
    
    /* Button Design Styles */
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
    
    .btn-print {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }
    
    .btn-print:hover {
        background: linear-gradient(135deg, #1e7e34, #155724);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
        color: white;
    }
    
    .btn-group-custom {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }
    
    .btn-icon {
        margin-right: 6px;
        font-size: 0.9em;
    }
    
    /* Action column styling */
    .action-column {
        min-width: 150px;
    }
    
    /* Table row hover effects */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
        transform: scale(1.01);
        transition: all 0.2s ease;
    }
    
    /* Badge styling */
    .item-count-badge {
        background: linear-gradient(135deg, #6f42c1, #5a3596);
        color: white;
        border-radius: 12px;
        padding: 4px 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .quantity-badge {
        background: linear-gradient(135deg, #fd7e14, #e55a00);
        color: white;
        border-radius: 12px;
        padding: 4px 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .employee-info {
        line-height: 1.4;
    }
    
    .employee-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .employee-details {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .ics-number {
        font-size: 1rem;
        font-weight: 700;
        color: #005113;
    }
    
    /* Remove horizontal scrollbar and fix table responsiveness */
    .table-responsive {
        border: none !important;
        overflow-x: hidden !important;
    }
    
    #icsTable {
        width: 100% !important;
        table-layout: auto;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .table-responsive {
            border: none;
            overflow-x: hidden;
            width: 100%;
        }
        
        /* Stack table headers for mobile */
        #icsTable thead {
            display: none;
        }
        
        #icsTable tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        
        #icsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0.5rem;
            border: none;
            border-bottom: 1px solid #f8f9fa;
            text-align: left !important;
            width: 100%;
            box-sizing: border-box;
        }
        
        #icsTable tbody td:last-child {
            border-bottom: none;
            justify-content: center;
        }
        
        #icsTable tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            min-width: 100px;
            margin-right: 1rem;
        }
        
        /* Adjust form controls for mobile */
        .btn-group-custom {
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }
        
        .btn-view, .btn-print {
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
        .item-count-badge, .quantity-badge {
            font-size: 0.75rem;
            padding: 6px 12px;
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
        
        .ics-number {
            font-size: 0.9rem;
        }
        
        /* Header adjustments */
        .card-header h3 {
            font-size: 1.3rem;
        }
    }
    
    /* Small mobile devices */
    @media (max-width: 576px) {
        #icsTable tbody td {
            flex-direction: column;
            align-items: flex-start;
            padding: 0.75rem 0.5rem;
        }
        
        #icsTable tbody td::before {
            margin-bottom: 0.25rem;
            min-width: auto;
            font-size: 0.8rem;
        }
        
        .btn-group-custom {
            flex-direction: row;
            justify-content: space-between;
            width: 100%;
        }
        
        .btn-view, .btn-print {
            flex: 1;
            margin: 0 2px;
            font-size: 0.8rem;
            padding: 8px 12px;
        }
        
        .btn-icon {
            margin-right: 4px;
            font-size: 0.8em;
        }
        
        .card {
            margin: 0;
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
        }
        
        .container-fluid {
            padding: 0 15px;
        }
        
        /* Adjust badge sizes for very small screens */
        .item-count-badge, .quantity-badge {
            font-size: 0.7rem;
            padding: 4px 8px;
        }
        
        .card-header {
            border-radius: 0.375rem 0.375rem 0 0;
        }
    }
    
    /* Medium devices adjustment */
    @media (max-width: 992px) and (min-width: 769px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .btn-view, .btn-print {
            font-size: 0.8rem;
            padding: 6px 12px;
        }
        
        .ics-number {
            font-size: 0.9rem;
        }
    }
    
    /* Extra small devices */
    @media (max-width: 400px) {
        .btn-group-custom {
            flex-direction: column;
        }
        
        .btn-view, .btn-print {
            width: 100%;
            margin: 2px 0;
        }
        
        #icsTable tbody td::before {
            min-width: 80px;
            font-size: 0.75rem;
        }
        
        .employee-name {
            font-size: 0.85rem;
        }
        
        .employee-details {
            font-size: 0.75rem;
        }
        
        .card-header h3 {
            font-size: 1.1rem;
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
        
        /* Hide DataTables scroll on mobile */
        .dataTables_scrollBody {
            overflow-x: hidden !important;
        }
    }
    
    /* Ensure no horizontal scroll on any device */
    body {
        overflow-x: hidden;
    }
    
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    .card-body {
        overflow-x: hidden;
    }
    
    /* DataTables wrapper adjustments */
    .dataTables_wrapper {
        overflow-x: hidden !important;
    }
    
    .dataTables_scroll {
        overflow-x: hidden !important;
    }
</style>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
        <h3 class="card-title"> <i class="nav-icon fa-solid fa-box-archive"></i> Inventory Custodian Slips</h3>
      </div>
      <div class="card-body">
        <?php if(!empty($ics_files)): ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover" id="icsTable">
              <thead>
                <tr>
                  <th>ICS Number</th>
                  <th>Items</th>
                  <th>Total Quantity</th>
                  <th>Issued To</th>
                  <th>Date Issued</th>
                  <th class="text-center action-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($ics_files as $ics): ?>
                  <tr>
                    <td data-label="ICS Number">
                      <strong class="ics-number"><?php echo remove_junk($ics['ICS_No']); ?></strong>
                    </td>
                    <td class="text-center" data-label="Items">
                      <span class="item-count-badge">
                        <i class="fa-solid fa-cube me-1"></i>
                        <?php echo (int)$ics['item_count']; ?> item<?php echo $ics['item_count'] > 1 ? 's' : ''; ?>
                      </span>
                    </td>
                    <td class="text-center" data-label="Total Quantity">
                      <span class="quantity-badge">
                        <i class="fa-solid fa-layer-group me-1"></i>
                        <?php echo (int)$ics['total_quantity']; ?>
                      </span>
                    </td>
                    <td data-label="Issued To">
                      <div class="employee-info">
                        <div class="employee-name">
                          <?php 
                            if (!empty($ics['first_name'])) {
                              echo remove_junk($ics['first_name']) . ' ';
                              if (!empty($ics['middle_name'])) {
                                echo remove_junk($ics['middle_name']) . ' ';
                              }
                              echo remove_junk($ics['last_name']);
                            } else {
                              echo 'Direct User Assignment';
                            }
                          ?>
                        </div>
                        <?php if (!empty($ics['position']) || !empty($ics['department'])): ?>
                        <div class="employee-details">
                          <?php 
                            if (!empty($ics['position'])) echo remove_junk($ics['position']);
                            if (!empty($ics['position']) && !empty($ics['department'])) echo ' • ';
                            if (!empty($ics['department'])) echo remove_junk($ics['department']);
                          ?>
                        </div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="text-center" data-label="Date Issued">
                      <?php echo date('M d, Y', strtotime($ics['transaction_date'])); ?>
                    </td>
                    <td class="text-center" data-label="Actions">
                      <div class="btn-group-custom">
                        <a href="ics_view_user.php?ics=<?php echo urlencode($ics['ICS_No']); ?>" class="btn btn-view" title="View ICS Details">
                          <i class="fa-solid fa-eye btn-icon"></i> View
                        </a>
                        <!-- Uncomment if you want to add print functionality -->
                        <!--
                        <a href="print_forms.php?ics=<?php echo urlencode($ics['ICS_No']); ?>" class="btn btn-print" target="_blank" title="Print ICS">
                          <i class="fa-solid fa-print btn-icon"></i> Print
                        </a>
                        -->
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info">
            <p>No Inventory Custodian Slips found for your account.</p>
          </div>
        <?php endif; ?>
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
    <?php if(!empty($ics_files)): ?>
    var table = $('#icsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: true,
        scrollX: false, // Disable horizontal scrolling
        order: [[4, 'desc']], // Sort by date descending
        columnDefs: [
            { orderable: false, targets: [5] }, // Make actions column non-orderable
            { responsivePriority: 1, targets: 0 }, // ICS Number - highest priority
            { responsivePriority: 2, targets: 3 }, // Issued To - second priority
            { responsivePriority: 3, targets: 5 }, // Actions - third priority
            { responsivePriority: 4, targets: 4 }, // Date - fourth priority
            { responsivePriority: 5, targets: 1 }, // Items - fifth priority
            { responsivePriority: 6, targets: 2 }  // Quantity - lowest priority
        ],
        // Mobile responsive settings for DataTables
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'ICS Details: ' + data[0];
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                    tableClass: 'table'
                })
            }
        },
        // Custom search for mobile
        language: {
            search: "Search ICS files:",
            searchPlaceholder: "Enter ICS number or name...",
            lengthMenu: "Show _MENU_ ICS files",
            info: "Showing _START_ to _END_ of _TOTAL_ ICS files",
            infoEmpty: "No ICS files available",
            infoFiltered: "(filtered from _MAX_ total ICS files)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });
    
    // Force no horizontal scroll
    $('.dataTables_scrollBody').css('overflow-x', 'hidden');
    $('.dataTables_wrapper').css('overflow-x', 'hidden');
    
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
                            <input type="text" class="form-control" id="mobileSearchInput" placeholder="Search ICS files...">
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
            
            // Ensure no horizontal scroll
            $('.dataTables_scrollBody').css('overflow-x', 'hidden');
            $('.dataTables_wrapper').css('overflow-x', 'hidden');
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
});
</script>