<?php
$page_title = 'View User Request';
require_once('includes/load.php');
page_require_level(3);

// Get request ID from URL
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$request_id) {
    $session->msg("d", "Invalid request ID.");
    redirect('home.php');
}

// Fetch current user
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Fetch request details
$requests = find_by_sql("
    SELECT r.*, u.name as requester_name
    FROM requests r
    LEFT JOIN users u ON r.requested_by = u.id
    WHERE r.id = '{$request_id}'
");

if (empty($requests)) {
    $session->msg("d", "Request not found.");
    redirect('home.php');
}

$request = $requests[0];

// Security: Ensure the user owns this request
if ($request['requested_by'] != $user_id) {
    $session->msg("d", "You are not authorized to view this request.");
    redirect('home.php');
}

// Fetch requested items
$items = find_by_sql("
    SELECT ri.qty, ri.unit, i.name, i.description, c.name as category 
    FROM request_items ri
    JOIN items i ON ri.item_id = i.id
    LEFT JOIN categories c ON i.categorie_id = c.id
    WHERE ri.req_id = '{$request_id}'
");

include_once('layouts/header.php');
?>

<style>
:root {
    --primary-green: #1e7e34;
    --dark-green: #155724;
    --light-green: #28a745;
    --accent-green: #34ce57;
    --primary-yellow: #ffc107;
    --dark-yellow: #e0a800;
    --light-yellow: #ffda6a;
    --card-bg: #ffffff;
    --text-dark: #343a40;
    --text-light: #6c757d;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --hover-shadow: 0 8px 25px rgba(30, 126, 52, 0.15);
}

/* Header Styling */
.request-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border-left: 5px solid var(--primary-yellow);
}

.request-header h3 {
    margin: 0;
    font-weight: 700;
    font-size: 1.8rem;
}

/* Card Styling */
.request-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    border-top: 5px solid var(--primary-green);
    overflow: hidden;
}

.request-card:hover {
    box-shadow: var(--hover-shadow);
    transform: translateY(-2px);
}

.card-header-custom {
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
    border-bottom: 2px solid #e8f5e9;
    padding: 1.25rem 1.5rem;
}

.card-header-custom h4 {
    margin: 0;
    font-weight: 600;
    color: var(--dark-green);
}

.card-header-custom i {
    color: var(--primary-yellow);
}

/* Form Styling */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label-custom {
    font-weight: 600;
    color: var(--dark-green);
    font-size: 1rem;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control-custom {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    background-color: #f8f9fa;
    font-weight: 500;
    transition: all 0.3s ease;
    width: 100%;
}

.form-control-custom:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(30, 126, 52, 0.25);
    background-color: white;
}

/* Table Styling */
.table-custom {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 0;
}

.table-custom thead th {
    background: #005113ff;
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
    text-align: center;
}

.table-custom tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-color: #f1f3f4;
    text-align: center;
}

.table-custom tbody tr:hover {
    background-color: rgba(40, 167, 69, 0.05);
}

/* Progress Bar */
.progress-custom {
    height: 25px;
    border-radius: 12px;
    background-color: #e9ecef;
    overflow: hidden;
}

.progress-bar-custom {
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Status Badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-block;
}

.badge-pending {
    background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));
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

.badge-completed {
    background: linear-gradient(135deg, var(--light-green), var(--primary-green));
    color: white;
}

/* Buttons */
.btn-custom {
    border-radius: 10px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-secondary-custom {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-secondary-custom:hover {
    background: linear-gradient(135deg, #5a6268, #495057);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    color: white;
    text-decoration: none;
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.request-card {
    animation: fadeInUp 0.6s ease forwards;
}

/* Request ID Highlight */
.request-id-highlight {
    background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));
    color: #000;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1.1rem;
    display: inline-block;
}

/* HORIZONTAL PROGRESS STYLING */
.horizontal-progress {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin: 1.5rem 0;
    border: 2px solid #e8f5e9;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin-bottom: 1rem;
}

.progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 4px;
    background: #e9ecef;
    z-index: 1;
}

.progress-bar-horizontal {
    position: absolute;
    top: 20px;
    left: 0;
    height: 4px;
    background: var(--primary-green);
    z-index: 2;
    transition: all 0.5s ease;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 3;
    flex: 1;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #6c757d;
    font-size: 1rem;
    margin-bottom: 0.5rem;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.step.active .step-icon {
    background: var(--primary-green);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(30, 126, 52, 0.3);
}

.step.completed .step-icon {
    background: var(--light-green);
    color: white;
}

.step-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6c757d;
    text-align: center;
    margin-bottom: 0.25rem;
}

.step.active .step-label {
    color: var(--dark-green);
    font-weight: 700;
}

.step.completed .step-label {
    color: var(--primary-green);
}

.step-date {
    font-size: 0.7rem;
    color: #adb5bd;
    text-align: center;
}

.progress-percentage {
    text-align: center;
    margin-top: 1rem;
}

.percentage-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark-green);
    margin-bottom: 0.25rem;
}

.percentage-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Empty State */
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

/* Mobile Responsive Design */
@media (max-width: 768px) {
    .request-header {
        padding: 1rem;
        text-align: center;
    }
    
    .request-header h3 {
        font-size: 1.5rem;
    }
    
    .form-label-custom {
        font-size: 0.9rem;
    }
    
    .btn-custom {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    
    .request-id-highlight {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
        margin-top: 0.5rem;
    }
    
    /* Horizontal Progress Mobile */
    .horizontal-progress {
        padding: 1rem;
        margin: 1rem 0;
    }
    
    .step-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    .step-label {
        font-size: 0.7rem;
    }
    
    .step-date {
        font-size: 0.65rem;
    }
    
    .progress-steps::before,
    .progress-bar-horizontal {
        top: 16px;
    }
}

/* Mobile Table Styles */
@media (max-width: 576px) {
    /* Hide desktop table on mobile */
    .desktop-table {
        display: none;
    }
    
    .mobile-cards-container {
        display: block;
    }
    
    /* Mobile Card Styles for Items */
    .mobile-item-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid var(--primary-green);
    }
    
    .mobile-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .mobile-item-name {
        font-weight: 700;
        color: var(--dark-green);
        font-size: 1rem;
        flex: 1;
    }
    
    .mobile-item-quantity {
        background: var(--primary-green);
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        margin-left: 0.5rem;
    }
    
    .mobile-item-detail {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding: 0.25rem 0;
    }
    
    .mobile-item-label {
        color: var(--text-light);
        font-weight: 500;
        font-size: 0.8rem;
        min-width: 80px;
    }
    
    .mobile-item-value {
        font-weight: 600;
        font-size: 0.8rem;
        text-align: right;
        flex: 1;
        margin-left: 1rem;
    }
    
    .mobile-item-description {
        background: #f8f9fa;
        padding: 0.75rem;
        border-radius: 8px;
        margin-top: 0.5rem;
        border-left: 3px solid var(--primary-yellow);
    }
    
    .mobile-item-description .mobile-item-label {
        font-weight: 600;
        color: var(--dark-green);
        margin-bottom: 0.25rem;
    }
    
    .mobile-item-description .mobile-item-value {
        font-weight: normal;
        color: var(--text-dark);
        text-align: left;
        margin-left: 0;
    }
}

@media (min-width: 577px) {
    .mobile-cards-container {
        display: none;
    }
    
    .desktop-table {
        display: block;
    }
}

/* Enhanced Table Responsiveness */
@media (max-width: 992px) {
    .table-custom thead th,
    .table-custom tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
    }
    
    .table-custom thead th:nth-child(2),
    .table-custom tbody td:nth-child(2) {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

@media (max-width: 768px) {
    .table-custom thead th,
    .table-custom tbody td {
        padding: 0.5rem 0.3rem;
        font-size: 0.85rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.5rem;
    }
}

/* Horizontal scroll for very small screens */
@media (max-width: 575px) {
    .table-responsive {
        border-radius: 10px;
    }
    
    .table-custom {
        min-width: 500px; /* Force horizontal scroll */
    }
}
</style>

<div class="container mt-4">
    <!-- Request Header -->
    <div class="request-header">
        <div class="d-flex justify-content-between align-items-center flex-column flex-md-row">
            <div class="text-center text-md-start">
                <h3><i class="fa-solid fa-file-lines me-2"></i>Request Details</h3>
                <div class="subtitle mt-2">View and track your submitted request</div>
            </div>
            <div class="text-center text-md-end mt-2 mt-md-0">
                <span class="request-id-highlight">
                    <i class="fa-solid fa-hashtag me-1"></i><?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Request Information -->
    <div class="row">
        <div class="col-md-12">
            <div class="request-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Request Information</h4>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <!-- Request ID -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom">Request No</label>
                            <div class="form-control-custom">
                                <?php echo remove_junk($request['ris_no']); ?>
                            </div>
                        </div>

                        <!-- Date Requested -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom">Date Requested</label>
                            <div class="form-control-custom">
                                <i class="fa-solid fa-calendar me-2 text-success"></i>
                                <?php echo date("F j, Y", strtotime($request['date'])); ?>
                            </div>
                        </div>

                        <!-- Current Status -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom">Current Status</label>
                            <?php 
                            $status = strtolower($request['status']);
                            $badge_class = 'badge-pending';
                            if ($status == 'approved') $badge_class = 'badge-approved';
                            if ($status == 'issued') $badge_class = 'badge-issued';
                            if ($status == 'completed') $badge_class = 'badge-completed';
                            ?>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <i class="fa-solid fa-circle me-1"></i>
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>

                        <!-- Requester Name -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom">Requested By</label>
                            <div class="form-control-custom">
                                <i class="fa-solid fa-user me-2 text-success"></i>
                                <?php echo remove_junk($request['requester_name']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- HORIZONTAL PROGRESS TRACKING -->
                    <div class="horizontal-progress">
                        <div class="progress-steps">
                            <div class="progress-bar-horizontal" id="progressBar" 
                                 style="width: <?php 
                                 $progress_width = 0;
                                 if ($status == 'pending') $progress_width = 25;
                                 elseif ($status == 'approved') $progress_width = 50;
                                 elseif ($status == 'issued') $progress_width = 75;
                                 elseif ($status == 'completed') $progress_width = 100;
                                 echo $progress_width; ?>%;"></div>
                            
                            <div class="step <?php echo in_array($status, ['pending', 'approved', 'issued', 'completed']) ? 'completed' : ''; ?> <?php echo $status == 'pending' ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </div>
                                <div class="step-label">Submitted</div>
                                <div class="step-date"><?php echo date("M j", strtotime($request['date'])); ?></div>
                            </div>
                            
                            <div class="step <?php echo in_array($status, ['approved', 'issued', 'completed']) ? 'completed' : ''; ?> <?php echo $status == 'approved' ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-thumbs-up"></i>
                                </div>
                                <div class="step-label">Approved</div>
                                <div class="step-date">
                                    <?php echo !empty($request['date_approved']) ? date("M j", strtotime($request['date_approved'])) : 'Pending'; ?>
                                </div>
                            </div>
                            
                            <div class="step <?php echo in_array($status, ['issued', 'completed']) ? 'completed' : ''; ?> <?php echo $status == 'issued' ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <div class="step-label">Issued</div>
                                <div class="step-date">
                                    <?php echo !empty($request['date_issued']) ? date("M j", strtotime($request['date_issued'])) : 'Pending'; ?>
                                </div>
                            </div>
                            
                            <div class="step <?php echo $status == 'completed' ? 'completed active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-check-circle"></i>
                                </div>
                                <div class="step-label">Completed</div>
                                <div class="step-date">
                                    <?php echo !empty($request['date_completed']) ? date("M j", strtotime($request['date_completed'])) : 'Pending'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="progress-percentage">
                            <div class="percentage-text"><?php echo $progress_width; ?>% Complete</div>
                            <div class="percentage-label">Request Progress</div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <?php if (!empty($request['remarks'])): ?>
                    <div class="mb-4">
                        <label class="form-label-custom">Remarks</label>
                        <div class="form-control-custom">
                            <i class="fa-solid fa-comment me-2 text-success"></i>
                            <?php echo remove_junk($request['remarks']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Requested Items -->
    <div class="row">
        <div class="col-12">
            <div class="request-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-boxes me-2"></i>Requested Items</h4>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($items)): ?>
                    <!-- Desktop Table View -->
                    <div class="desktop-table">
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-success"><?php echo remove_junk($item['name']); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo remove_junk($item['description']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary fs-6"><?php echo (int)$item['qty']; ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo remove_junk($item['unit']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?php echo remove_junk($item['category']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="mobile-cards-container p-3">
                        <?php foreach ($items as $item): ?>
                            <div class="mobile-item-card">
                                <div class="mobile-item-header">
                                    <div class="mobile-item-name"><?php echo remove_junk($item['name']); ?></div>
                                    <span class="mobile-item-quantity">
                                        <?php echo (int)$item['qty']; ?> <?php echo remove_junk($item['unit']); ?>
                                    </span>
                                </div>
                                
                                <div class="mobile-item-detail">
                                    <span class="mobile-item-label">Category:</span>
                                    <span class="mobile-item-value">
                                        <span class="badge bg-light text-dark"><?php echo remove_junk($item['category']); ?></span>
                                    </span>
                                </div>
                                
                                <?php if (!empty($item['description'])): ?>
                                <div class="mobile-item-description">
                                    <div class="mobile-item-label">Description:</div>
                                    <div class="mobile-item-value"><?php echo remove_junk($item['description']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h5>No Items Found</h5>
                        <p class="text-muted">No items were found for this request.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="home.php" class="btn btn-secondary-custom btn-custom">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>