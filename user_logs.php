<?php
$page_title = 'User Logs';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user(); 
$user_id = (int)$current_user['id'];

// Get all requests grouped by RIS number
$user_requests = find_by_sql("
    SELECT 
        r.id,
        r.ris_no,
        r.date,
        r.status as original_status,
        r.remarks,
        LOWER(r.status) as status_lower,
        COUNT(ri.id) as item_count,
        SUM(ri.qty) as total_quantity,
        SUM(ri.price) as total_cost,
        GROUP_CONCAT(CONCAT(i.name, ' (', ri.qty, ')') SEPARATOR '<br>') as items_list
    FROM requests r
    LEFT JOIN request_items ri ON r.id = ri.req_id
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE r.requested_by = '{$user_id}'
    AND r.status IN ('Completed', 'Cancelled', 'Canceled', 'Approved', 'Declined')
    GROUP BY r.id, r.ris_no, r.date, r.status, r.remarks
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
    
    .badge-declined {
        background: linear-gradient(135deg, var(--danger), #c82333);
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
        white-space: nowrap;
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
    
    /* ✅ FIXED COLUMN WIDTHS */
    #userReqTable {
        table-layout: fixed;
        width: 100%;
    }
    
    #userReqTable th:nth-child(1), /* No. */
    #userReqTable td:nth-child(1) {
        width: 60px !important;
        min-width: 60px;
        max-width: 60px;
    }
    
    #userReqTable th:nth-child(2), /* RIS No */
    #userReqTable td:nth-child(2) {
        width: 120px !important;
        min-width: 120px;
        max-width: 120px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    #userReqTable th:nth-child(3), /* Items Requested */
    #userReqTable td:nth-child(3) {
        width: 250px !important;
        min-width: 250px;
        max-width: 250px;
    }
    
    #userReqTable th:nth-child(4), /* Date */
    #userReqTable td:nth-child(4) {
        width: 120px !important;
        min-width: 120px;
        max-width: 120px;
        white-space: nowrap;
    }
    
    #userReqTable th:nth-child(5), /* Remarks */
    #userReqTable td:nth-child(5) {
        width: 200px !important;
        min-width: 200px;
        max-width: 200px;
    }
    
    #userReqTable th:nth-child(6), /* Status */
    #userReqTable td:nth-child(6) {
        width: 130px !important;
        min-width: 130px;
        max-width: 130px;
    }
    
    #userReqTable th:nth-child(7), /* View Button */
    #userReqTable td:nth-child(7) {
        width: 100px !important;
        min-width: 100px;
        max-width: 100px;
    }
    
    /* ✅ NEW: View Button Styling */
    .btn-view {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none;
        cursor: pointer;
    }
    
    .btn-view:hover {
        background: linear-gradient(135deg, var(--primary-dark), #155724);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        text-decoration: none;
    }
    
    .btn-view-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
    }
    
    /* ✅ NEW: Declined row styling */
    .table tbody tr.declined-row {
        background: linear-gradient(90deg, #f8d7da 0%, rgba(248, 215, 218, 0.3) 100%);
    }
    
    .table tbody tr.declined-row:hover {
        background: linear-gradient(90deg, #f8d7da 0%, #f5c6cb 100%);
    }
    
    .mobile-cards-container .card.declined-card {
        border-left: 4px solid var(--danger);
        background: linear-gradient(90deg, #f8d7da 0%, rgba(248, 215, 218, 0.1) 100%);
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
    
    .items-list {
        max-height: 120px;
        overflow-y: auto;
        text-align: left;
        font-size: 0.85rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        word-wrap: break-word;
        word-break: break-word;
    }
    
    /* ✅ NEW: Remarks styling */
    .remarks-text {
        max-height: 100px;
        overflow-y: auto;
        text-align: left;
        font-size: 0.85rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        font-style: italic;
        color: #6c757d;
        word-wrap: break-word;
        word-break: break-word;
    }
    
    .remarks-text.empty {
        color: #adb5bd;
        font-style: normal;
    }
    
    .items-list::-webkit-scrollbar,
    .remarks-text::-webkit-scrollbar {
        width: 6px;
    }
    
    .items-list::-webkit-scrollbar-track,
    .remarks-text::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .items-list::-webkit-scrollbar-thumb,
    .remarks-text::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .items-list::-webkit-scrollbar-thumb:hover,
    .remarks-text::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
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
        
        .items-list {
            max-height: 80px;
            font-size: 0.8rem;
        }
        
        .remarks-text {
            max-height: 80px;
            font-size: 0.8rem;
        }
        
        .btn-view {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
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
        
        .mobile-items-list {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            max-height: 100px;
            overflow-y: auto;
        }
        
        .mobile-remarks {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            font-style: italic;
            color: #6c757d;
        }
        
        .mobile-remarks.empty {
            color: #adb5bd;
            font-style: normal;
        }
        
        .mobile-view-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.8rem;
            width: 100%;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            text-decoration: none;
        }
        
        .mobile-view-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), #155724);
            color: white;
            text-decoration: none;
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
        <p class="text-muted">View your complete request history grouped by RIS number.</p>
    </div>
</div>

<?php 
// Calculate counts for each status
$all_count = count($user_requests);
$completed_count = count(array_filter($user_requests, fn($req) => strtolower($req['original_status']) === 'completed'));
$canceled_count = count(array_filter($user_requests, fn($req) => in_array(strtolower($req['original_status']), ['canceled', 'cancelled'])));
$approved_count = count(array_filter($user_requests, fn($req) => strtolower($req['original_status']) === 'approved'));
$declined_count = count(array_filter($user_requests, fn($req) => strtolower($req['original_status']) === 'declined'));

if(count($user_requests) > 0): ?>
    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">
            All Requests <span class="counter-badge"><?php echo $all_count; ?></span>
        </button>
        <button class="filter-btn" data-filter="completed">
            Completed <span class="counter-badge"><?php echo $completed_count; ?></span>
        </button>
        <button class="filter-btn" data-filter="canceled">
            Canceled <span class="counter-badge"><?php echo $canceled_count; ?></span>
        </button>
        <button class="filter-btn" data-filter="approved">
            Approved <span class="counter-badge"><?php echo $approved_count; ?></span>
        </button>
        <button class="filter-btn" data-filter="declined">
            Declined <span class="counter-badge"><?php echo $declined_count; ?></span>
        </button>
    </div>

    <!-- Desktop Table View -->
    <div class="table-responsive p-2">
        <table id="userReqTable" class="table table-striped table-hover" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">RIS No</th>
                    <th class="text-center">Items Requested</th>
                    <th class="text-center">Date</th>            
                    <th class="text-center">Remarks</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
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
                    
                    // Add row class for declined requests
                    $row_class = $status === 'declined' ? 'declined-row' : '';
                    
                    // Process remarks
                    $remarks = isset($row['remarks']) ? htmlspecialchars($row['remarks']) : '';
                    $remarks_class = empty($remarks) ? 'empty' : '';
                    $remarks_display = empty($remarks) ? 'No remarks' : $remarks;
                    ?>
                    <tr data-status="<?php echo $status; ?>" class="<?php echo $row_class; ?>">
                        <td class="text-center">
                            <span class="badge badge-custom badge-primary"><?php echo $counter++; ?></span>
                        </td>
                        <td class="text-center">
                            <strong><?php echo isset($row['ris_no']) ? htmlspecialchars($row['ris_no']) : 'N/A'; ?></strong>
                        </td>
                        <td>
                            <div class="items-list">
                                <?php echo isset($row['items_list']) ? $row['items_list'] : 'No items'; ?>
                            </div>
                        </td>                
                        <td class="text-center">
                            <?php echo isset($row['date']) ? date('M j, Y', strtotime($row['date'])) : 'N/A'; ?>
                        </td>
                        <td>
                            <div class="remarks-text <?php echo $remarks_class; ?>">
                                <?php echo $remarks_display; ?>
                            </div>
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
                                    case 'declined':
                                        $badgeClass = 'badge-declined';
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
                                        case 'declined': echo 'fa-times-circle'; break;
                                        default: echo 'fa-info-circle';
                                    }
                                    ?> me-1">
                                </i>
                                <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="view_request.php?id=<?php echo $row['id']; ?>" class="btn-view btn-view-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
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
                case 'declined': $badgeClass = 'badge-declined'; break;
                default: $badgeClass = 'badge-secondary';
            }
            
            // Add card class for declined requests
            $card_class = $status === 'declined' ? 'declined-card' : '';
            
            // Process remarks for mobile
            $remarks = isset($row['remarks']) ? htmlspecialchars($row['remarks']) : '';
            $remarks_class = empty($remarks) ? 'empty' : '';
            $remarks_display = empty($remarks) ? 'No remarks' : $remarks;
            ?>
            <div class="card mb-3 <?php echo $card_class; ?>" data-status="<?php echo $status; ?>">
                <div class="card-body">
                    <div class="mobile-row">
                        <span class="mobile-label">RIS No:</span>
                        <span class="mobile-value">
                            <strong><?php echo isset($row['ris_no']) ? htmlspecialchars($row['ris_no']) : 'N/A'; ?></strong>
                        </span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Date:</span>
                        <span class="mobile-value"><?php echo isset($row['date']) ? date('M j, Y', strtotime($row['date'])) : 'N/A'; ?></span>
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
                                        case 'declined': echo 'fa-times-circle'; break;
                                        default: echo 'fa-info-circle';
                                    }
                                    ?> me-1">
                                </i>
                                <?php echo ucfirst($status); ?>
                            </span>
                        </span>
                    </div>
                    <div class="mobile-row">
                        <span class="mobile-label">Remarks:</span>
                        <div class="mobile-remarks <?php echo $remarks_class; ?>">
                            <?php echo $remarks_display; ?>
                        </div>
                    </div>
                    <?php if(isset($row['items_list']) && !empty($row['items_list'])): ?>
                    <div class="mobile-row">
                        <span class="mobile-label">Items:</span>
                        <div class="mobile-items-list">
                            <?php echo $row['items_list']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <a href="view_request.php?id=<?php echo $row['id']; ?>" class="mobile-view-btn">
                        <i class="fas fa-eye"></i> View Full Details
                    </a>
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
    // Initialize DataTable for desktop with fixed columns
    var table = $('#userReqTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: false, // Disable DataTables responsive to use our fixed widths
        scrollX: false,
        deferRender: true,        
        processing: true,
        serverSide: false,
        language: {
            search: "Search transactions:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ transactions",
            infoEmpty: "Showing 0 to 0 of 0 transactions",
            infoFiltered: "(filtered from _MAX_ total transactions)"
        },
        columnDefs: [
            {
                targets: 5, // Status column
                render: function(data, type, row) {
                    if (type === 'filter') {
                        return $(data).text().toLowerCase();
                    }
                    return data;
                }
            },
            {
                targets: 6, // Action column
                orderable: false,
                searchable: false
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

    // Show all data initially
    table.search('').draw();
});
</script>