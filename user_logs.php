<?php
$page_title = 'User Logs';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user(); 
$user_id = (int)$current_user['id'];

// Get all requests including canceled ones using direct query
$user_requests = find_by_sql("
    SELECT r.*, ri.qty, ri.price as total_cost, i.name,
           r.status as original_status,
           LOWER(r.status) as status_lower
    FROM requests r
    LEFT JOIN request_items ri ON r.id = ri.req_id
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE r.requested_by = '{$user_id}'
    AND r.status IN ('Completed', 'Cancelled', 'Canceled', 'Approved')
    ORDER BY r.date DESC
");
?>

<?php include_once('layouts/header.php'); ?>
<style>
    :root {
        --primary: #28a745;
        --primary-dark: #1e7e34;
        --primary-light: #34ce57;
        --secondary: #6c757d;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #343a40;
        --border-radius: 12px;
        --shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .badge-custom {
        padding: 0.5rem 0.75rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .badge-primary {
        background: rgba(40, 167, 69, 0.15);
        color: var(--primary-dark);
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-block;
        min-width: 100px;
        text-align: center;
    }
    
    .badge-completed {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
    }
    
    .badge-rejected {
        background: linear-gradient(135deg, var(--danger), #c82333);
        color: white;
    }
    
    .badge-canceled {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }
    
    .badge-pending {
        background: linear-gradient(135deg, var(--warning), #e0a800);
        color: #000;
    }
    
    .badge-approved {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }
    
    .badge-issued {
        background: linear-gradient(135deg, #6f42c1, #5a2d9c);
        color: white;
    }
    
    .table-responsive {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow);
    }
    
    .table th {
        background: #005113ff;
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        text-align: center;
    }
    
    .table td {
        padding: 1rem;
        vertical-align: middle;
        border-color: #f1f3f4;
        text-align: center;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.05);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #718096;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    /* Filter buttons */
    .filter-buttons {
        margin-bottom: 1.5rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .filter-btn {
        border: 2px solid var(--primary);
        background: transparent;
        color: var(--primary);
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    
    .filter-btn:hover,
    .filter-btn.active {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    .counter-badge {
        background: var(--primary);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        margin-left: 0.5rem;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 0 10px;
        }
        
        .filter-buttons {
            gap: 0.3rem;
            margin-bottom: 1rem;
        }
        
        .filter-btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            flex: 1;
            min-width: calc(50% - 0.3rem);
            text-align: center;
        }
        
        .table th,
        .table td {
            padding: 0.5rem;
            font-size: 0.85rem;
        }
        
        .status-badge {
            min-width: 80px;
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
        }
        
        .badge-custom {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
        }
        
        .empty-state {
            padding: 2rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
        }
        
        h5.mb-3 {
            font-size: 1.25rem;
            text-align: center;
        }
        
        .text-muted {
            text-align: center;
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 576px) {
        .filter-btn {
            min-width: 100%;
            margin-bottom: 0.3rem;
        }
        
        .filter-buttons {
            flex-direction: column;
        }
        
        .table th,
        .table td {
            padding: 0.4rem;
            font-size: 0.8rem;
        }
        
        .status-badge {
            min-width: 70px;
            padding: 0.3rem 0.6rem;
            font-size: 0.7rem;
        }
        
        .counter-badge {
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
        }
        
        /* Mobile card view for table rows */
        .mobile-card-view .card {
            margin-bottom: 0.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .mobile-card-view .card-body {
            padding: 0.75rem;
        }
        
        .mobile-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .mobile-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .mobile-label {
            font-weight: 600;
            color: #555;
            font-size: 0.8rem;
        }
        
        .mobile-value {
            text-align: right;
            font-size: 0.8rem;
        }
    }
    
    /* Hide table on mobile, show cards */
    @media (max-width: 576px) {
        .table-responsive {
            display: none;
        }
        
        .mobile-cards-container {
            display: block;
        }
    }
    
    @media (min-width: 577px) {
        .mobile-cards-container {
            display: none;
        }
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <h5 class="mb-3"><i class="nav-icon fas fa-file-invoice me-2"></i>Transaction History</h5>
        <p class="text-muted">View your complete request history including completed, rejected, and canceled requests.</p>
    </div>
</div>

<?php if(count($user_requests) > 0): ?>
    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">
            All Requests <span class="counter-badge"><?php echo count($user_requests); ?></span>
        </button>
        <button class="filter-btn" data-filter="completed">
            Completed <span class="counter-badge"><?php echo count(array_filter($user_requests, fn($req) => strtolower($req['original_status']) === 'completed')); ?></span>
        </button>
        <button class="filter-btn" data-filter="canceled">
            Canceled <span class="counter-badge"><?php echo count(array_filter($user_requests, fn($req) => in_array(strtolower($req['original_status']), ['canceled', 'cancelled']))); ?></span>
        </button>
        <button class="filter-btn" data-filter="approved">
            Approved <span class="counter-badge"><?php echo count(array_filter($user_requests, fn($req) => strtolower($req['original_status']) === 'approved')); ?></span>
        </button>
    </div>

    <!-- Desktop Table View -->
    <div class="table-responsive">
        <table id="userReqTable" class="table table-striped table-hover" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">Request No</th>
                    <th class="text-center">Item</th>
                    <th class="text-center">Date</th>            
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Total Cost</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach($user_requests as $row): ?>
                    <?php 
                    $status = strtolower($row['original_status']);
                    // Handle both 'canceled' and 'cancelled' spellings
                    if ($status === 'cancelled') {
                        $status = 'canceled';
                    }
                    ?>
                    <tr data-status="<?php echo $status; ?>">
                        <td class="text-center">
                            <span class="badge badge-custom badge-primary"><?php echo $counter++; ?></span>
                        </td>
                        <td class="text-center">
                            <strong><?php echo isset($row['ris_no']) ? htmlspecialchars($row['ris_no']) : 'N/A'; ?></strong>
                        </td>
                        <td>
                            <strong><?php echo isset($row['name']) ? htmlspecialchars($row['name']) : 'Item Not Found'; ?></strong>
                        </td>                
                        <td class="text-center">
                            <?php echo isset($row['date']) ? date('M j, Y', strtotime($row['date'])) : 'N/A'; ?>
                        </td>
                        <td class="text-center">
                            <?php echo isset($row['qty']) ? htmlspecialchars($row['qty']) : '0'; ?>
                        </td>
                        <td class="text-center">
                            ₱<?php echo isset($row['total_cost']) ? htmlspecialchars(number_format($row['total_cost'], 2)) : '0.00'; ?>
                        </td> 
                        <td class="text-center">
                            <?php 
                                $badgeClass = 'badge-secondary';
                                
                                switch($status) {
                                    case 'completed':
                                        $badgeClass = 'badge-completed';
                                        break;
                                    case 'canceled':
                                        $badgeClass = 'badge-canceled';
                                        break;
                                    case 'pending':
                                        $badgeClass = 'badge-pending';
                                        break;
                                    case 'approved':
                                        $badgeClass = 'badge-approved';
                                        break;
                                    default:
                                        $badgeClass = 'badge-secondary';
                                }
                            ?>
                            <span class="status-badge <?php echo $badgeClass; ?>">
                                <i class="fas 
                                    <?php 
                                    switch($status) {
                                        case 'completed': echo 'fa-check-circle'; break;
                                        case 'canceled': echo 'fa-ban'; break;
                                        case 'pending': echo 'fa-clock'; break;
                                        case 'approved': echo 'fa-thumbs-up'; break;
                                        case 'issued': echo 'fa-box'; break;
                                        default: echo 'fa-info-circle';
                                    }
                                    ?> me-1">
                                </i>
                                <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="mobile-cards-container mobile-card-view">
        <?php $counter = 1; ?>
        <?php foreach($user_requests as $row): ?>
            <?php 
            $status = strtolower($row['original_status']);
            if ($status === 'cancelled') {
                $status = 'canceled';
            }
            
            $badgeClass = 'badge-secondary';
            switch($status) {
                case 'completed': $badgeClass = 'badge-completed'; break;
                case 'canceled': $badgeClass = 'badge-canceled'; break;
                case 'pending': $badgeClass = 'badge-pending'; break;
                case 'approved': $badgeClass = 'badge-approved'; break;
                default: $badgeClass = 'badge-secondary';
            }
            ?>
            <div class="card mb-3" data-status="<?php echo $status; ?>">
                <div class="card-body">
                    <div class="mobile-row">
                        <span class="mobile-label">Request No:</span>
                        <span class="mobile-value">
                            <strong><?php echo isset($row['ris_no']) ? htmlspecialchars($row['ris_no']) : 'N/A'; ?></strong>
                        </span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Item:</span>
                        <span class="mobile-value"><?php echo isset($row['name']) ? htmlspecialchars($row['name']) : 'Item Not Found'; ?></span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Date:</span>
                        <span class="mobile-value"><?php echo isset($row['date']) ? date('M j, Y', strtotime($row['date'])) : 'N/A'; ?></span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Quantity:</span>
                        <span class="mobile-value"><?php echo isset($row['qty']) ? htmlspecialchars($row['qty']) : '0'; ?></span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Total Cost:</span>
                        <span class="mobile-value">₱<?php echo isset($row['total_cost']) ? htmlspecialchars(number_format($row['total_cost'], 2)) : '0.00'; ?></span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Status:</span>
                        <span class="mobile-value">
                            <span class="status-badge <?php echo $badgeClass; ?>">
                                <i class="fas 
                                    <?php 
                                    switch($status) {
                                        case 'completed': echo 'fa-check-circle'; break;
                                        case 'canceled': echo 'fa-ban'; break;
                                        case 'pending': echo 'fa-clock'; break;
                                        case 'approved': echo 'fa-thumbs-up'; break;
                                        case 'issued': echo 'fa-box'; break;
                                        default: echo 'fa-info-circle';
                                    }
                                    ?> me-1">
                                </i>
                                <?php echo ucfirst($status); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="empty-state">
        <i class="fa-solid fa-clipboard-list"></i>
        <h5 class="mb-2">No transaction history available</h5>
        <p class="text-muted">You don't have any completed, rejected, or canceled requests yet.</p>
        <a href="requests_form.php" class="btn btn-success mt-3">
            <i class="fa-solid fa-plus me-2"></i>Submit Your First Request
        </a>
    </div>
<?php endif; ?>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    // Initialize DataTable for desktop
    var table = $('#userReqTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: true,
        language: {
            search: "Search transactions:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ transactions",
            infoEmpty: "Showing 0 to 0 of 0 transactions",
            infoFiltered: "(filtered from _MAX_ total transactions)"
        },
        columnDefs: [
            {
                targets: 6, // Status column
                render: function(data, type, row) {
                    if (type === 'filter') {
                        return $(data).text().toLowerCase();
                    }
                    return data;
                }
            }
        ]
    });

    // Filter functionality for both table and cards
    $('.filter-btn').on('click', function() {
        var filter = $(this).data('filter');
        
        // Update active button
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        if (filter === 'all') {
            // Show all rows in table
            table.search('').draw();
            
            // Show all cards in mobile view
            $('.mobile-cards-container .card').show();
        } else {
            // Filter table
            table.search(filter).draw();
            
            // Filter mobile cards
            $('.mobile-cards-container .card').hide();
            $('.mobile-cards-container .card[data-status="' + filter + '"]').show();
        }
    });

    // Mobile card filtering function
    function filterMobileCards(filter) {
        if (filter === 'all') {
            $('.mobile-cards-container .card').show();
        } else {
            $('.mobile-cards-container .card').hide();
            $('.mobile-cards-container .card[data-status="' + filter + '"]').show();
        }
    }

    // Show all data initially
    table.search('').draw();
});
</script>