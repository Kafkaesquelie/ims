<?php
$page_title = ' PAR Files';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user();
$current_user_id = (int)$current_user['id'];

// Fetch distinct PAR files grouped by PAR_No with item count and totals
$par_files = find_by_sql("
    SELECT 
        t.PAR_No,
        COUNT(t.id) as item_count,
        SUM(t.quantity) as total_quantity,
        MIN(t.transaction_date) as date_acquired,
        e.first_name,
        e.middle_name,
        e.last_name,
        e.position
    FROM transactions t
    JOIN properties p ON t.properties_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE e.user_id = '{$current_user_id}'
      AND t.PAR_No IS NOT NULL
      AND t.PAR_No != ''
    GROUP BY t.PAR_No
    ORDER BY MIN(t.transaction_date) DESC
");

// Alternative query if you also want to include direct user assignments:
/*
$par_files = find_by_sql("
    SELECT 
        t.PAR_No,
        COUNT(t.id) as item_count,
        SUM(t.quantity) as total_quantity,
        MIN(t.transaction_date) as date_acquired,
        COALESCE(e.first_name, u.name) as first_name,
        e.middle_name,
        COALESCE(e.last_name, '') as last_name,
        e.position,
        e.department
    FROM transactions t
    JOIN properties p ON t.properties_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE (e.user_id = '{$current_user_id}' OR t.user_id = '{$current_user_id}')
      AND t.PAR_No IS NOT NULL
      AND t.PAR_No != ''
    GROUP BY t.PAR_No
    ORDER BY MIN(t.transaction_date) DESC
");
*/

// Debug: Uncomment below to see what data is being fetched
// echo "<pre>"; print_r($par_files); echo "</pre>";
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
        min-width: 120px;
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
    
    .par-number {
        font-size: 1rem;
        font-weight: 700;
        color: #005113;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow-x: auto;
        }
        
        /* Stack table headers for mobile */
        #parTable thead {
            display: none;
        }
        
        #parTable tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        #parTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0.5rem;
            border: none;
            border-bottom: 1px solid #f8f9fa;
            text-align: left !important;
        }
        
        #parTable tbody td:last-child {
            border-bottom: none;
            justify-content: center;
        }
        
        #parTable tbody td::before {
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
        
        .par-number {
            font-size: 0.9rem;
        }
        
        /* Header adjustments */
        .card-header h5 {
            font-size: 1.1rem;
        }
    }
    
    /* Small mobile devices */
    @media (max-width: 576px) {
        #parTable tbody td {
            flex-direction: column;
            align-items: flex-start;
            padding: 0.75rem 0.5rem;
        }
        
        #parTable tbody td::before {
            margin-bottom: 0.25rem;
            min-width: auto;
            font-size: 0.8rem;
        }
        
        .btn-group-custom {
            flex-direction: row;
            justify-content: space-between;
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
            margin: 0 -0.75rem;
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        
        .container-fluid {
            padding: 0;
        }
        
        /* Adjust badge sizes for very small screens */
        .item-count-badge, .quantity-badge {
            font-size: 0.7rem;
            padding: 4px 8px;
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
        
        .par-number {
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
        
        #parTable tbody td::before {
            min-width: 80px;
            font-size: 0.75rem;
        }
        
        .employee-name {
            font-size: 0.85rem;
        }
        
        .employee-details {
            font-size: 0.75rem;
        }
    }
</style>

<div class="card">
  <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <h5 class="card-title"><i class="nav-icon fas fa-handshake"></i> Property Acknowledgement Receipts</h5>
  </div>
  <div class="card-body">
    <?php if (!empty($par_files)): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover" id="parTable">
          <thead>
            <tr>
              <th><b>PAR Number</b></th>
              <th><b>Items</b></th>
              <th><b>Total Quantity</b></th>
              <th><b>Issued To</b></th>
              <th><b>Date Acquired</b></th>
              <th class="text-center action-column"><b>Actions</b></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($par_files as $par): ?>
              <tr>
                <td data-label="PAR Number">
                  <span class="par-number"><?php echo remove_junk($par['PAR_No']); ?></span>
                </td>
                <td class="text-center" data-label="Items">
                  <span class="item-count-badge">
                    <i class="fa-solid fa-cube me-1"></i>
                    <?php echo (int)$par['item_count']; ?> item<?php echo $par['item_count'] > 1 ? 's' : ''; ?>
                  </span>
                </td>
                <td class="text-center" data-label="Total Quantity">
                  <span class="quantity-badge">
                    <i class="fa-solid fa-layer-group me-1"></i>
                    <?php echo (int)$par['total_quantity']; ?>
                  </span>
                </td>
                <td data-label="Issued To">
                  <div class="employee-info">
                    <div class="employee-name">
                      <?php 
                        if (!empty($par['first_name'])) {
                          echo remove_junk($par['first_name']) . ' ';
                          if (!empty($par['middle_name'])) {
                            echo remove_junk($par['middle_name']) . ' ';
                          }
                          echo remove_junk($par['last_name']);
                        } else {
                          echo 'Direct User Assignment';
                        }
                      ?>
                    </div>
                    <?php if (!empty($par['position']) || !empty($par['department'])): ?>
                    <div class="employee-details">
                      <?php 
                        if (!empty($par['position'])) echo remove_junk($par['position']);
                        if (!empty($par['position']) && !empty($par['department'])) echo ' • ';
                        if (!empty($par['department'])) echo remove_junk($par['department']);
                      ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="text-center" data-label="Date Acquired">
                  <?php echo date('M d, Y', strtotime($par['date_acquired'])); ?>
                </td>
                <td class="text-center" data-label="Actions">
                  <div class="btn-group-custom">
                    <a href="par_view_user.php?par_no=<?php echo urlencode($par['PAR_No']); ?>" class="btn btn-view" title="View PAR Details">
                      <i class="fa-solid fa-eye btn-icon"></i> View
                    </a>
                    <!-- Uncomment if you want to add print functionality -->
                    <!--
                    <a href="print_forms.php?par=<?php echo urlencode($par['PAR_No']); ?>" class="btn btn-print" target="_blank" title="Print PAR">
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
        <p>No Property Acknowledgement Receipts found for your account.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>  

<!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script> -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
$(document).ready(function () {
    <?php if (!empty($par_files)): ?>
    var table = $('#parTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: true,
        scrollX: false,
        order: [[4, 'desc']], // Sort by date descending
        columnDefs: [
            { orderable: false, targets: [5] }, // Make actions column non-orderable
            { responsivePriority: 1, targets: 0 }, // PAR Number - highest priority
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
                        return 'PAR Details: ' + data[0];
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                    tableClass: 'table'
                })
            }
        },
        // Custom search for mobile
        language: {
            search: "Search PAR files:",
            searchPlaceholder: "Enter PAR number or name...",
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
        const table = $('#parTable');
        
        if (isMobile) {
            // Hide the original search box and create a mobile-friendly one
            $('.dataTables_filter').addClass('d-none');
            
            // Create mobile search if it doesn't exist
            if ($('#mobileSearch').length === 0) {
                $('.card-body').prepend(`
                    <div class="mb-3" id="mobileSearch">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="mobileSearchInput" placeholder="Search PAR files...">
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