<?php
$page_title = 'View User Request';
require_once('includes/load.php');
page_require_level(3);

// Get request ID from URL
$request_id = (int)$_GET['id'];

$request = find_by_sql("
    SELECT r.*
    FROM requests r
    LEFT JOIN users u ON r.requested_by = u.id
    WHERE r.id = $request_id
");

$request = $request[0]; // assuming find_by_sql returns an array of results



// Fetch current user
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Security: Ensure the user owns this request
if (!$request || $request['requested_by'] != $user_id) {
    $session->msg("d", "You are not authorized to view this request.");
    redirect('home.php');
}

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

.card-header-custom h4 {
    margin: 0;
    font-weight: 600;
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
}

.form-control-custom {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    background-color: #f8f9fa;
    font-weight: 500;
    transition: all 0.3s ease;
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
}

.badge-pending {
    background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));
    color: #000;
}

.badge-approved {
    background: linear-gradient(135deg, #17a2b8, #138496);
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
}

.btn-secondary-custom {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-secondary-custom:hover {
    background: linear-gradient(135deg, #5a6268, #495057);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
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

/* Responsive Design */
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

/* Timeline */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--primary-green);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2rem;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-green);
    border: 2px solid white;
    box-shadow: 0 0 0 3px var(--primary-green);
}

.timeline-item.active::before {
    background: var(--primary-yellow);
    box-shadow: 0 0 0 3px var(--primary-yellow);
}

.timeline-item.completed::before {
    background: var(--light-green);
    box-shadow: 0 0 0 3px var(--light-green);
}
</style>

<div class="container mt-4">
    <!-- Request Header -->
    <div class="request-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><i class="fa-solid fa-file-lines me-2"></i>Request Details</h3>
                <div class="subtitle mt-2">View and track your submitted request</div>
            </div>
            <div class="text-end">
                <span class="request-id-highlight">
                    <i class="fa-solid fa-hashtag me-1"></i><?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
        </div>
    </div>

        <!-- Request Information -->
        <div class="col-md-12">
            <div class="request-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-info-circle me-2 p-3"></i>Request Information</h4>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <!-- Request ID -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom d-block">Request No</label>
                            <div class="form-control-custom">
                                <?php echo ($request['ris_no']); ?>
                            </div>
                        </div>

                        <!-- Date Requested -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom d-block">Date Requested</label>
                            <div class="form-control-custom">
                                <i class="fa-solid fa-calendar me-2 text-success"></i>
                                <?php echo read_date($request['date']); ?>
                            </div>
                        </div>

                        <!-- Current Status -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label-custom d-block">Current Status</label>
                            <?php 
                            $status = strtolower($request['status']);
                            $badge_class = 'badge-pending';
                            if ($status == 'approved') $badge_class = 'badge-approved';
                            if ($status == 'completed') $badge_class = 'badge-completed';
                            ?>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <i class="fa-solid fa-circle me-1"></i>
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Tracking -->
                    <div class="mb-4">
                        <label class="form-label-custom d-block mb-3">Request Progress</label>
                        <div class="progress-custom mb-2">
                            <?php 
                            $progress = ($status == 'pending') ? 33 : (($status == 'approved') ? 66 : 100);
                            $color = ($status == 'pending') ? 'bg-warning' : (($status == 'approved') ? 'bg-info' : 'bg-success');
                            ?>
                            <div class="progress-bar progress-bar-custom <?php echo $color; ?>" 
                                 style="width: <?php echo $progress; ?>%">
                                <?php echo $progress; ?>% Complete
                            </div>
                        </div>
                        
                        <!-- Timeline -->
                        <div class="timeline mt-4">
                            <div class="timeline-item <?php echo $status == 'pending' ? 'active' : ($status == 'approved' || $status == 'completed' ? 'completed' : ''); ?>">
                                <strong>Request Submitted</strong>
                                <div class="text-muted small">Your request has been received and is under review</div>
                            </div>
                            <div class="timeline-item <?php echo $status == 'approved' ? 'active' : ($status == 'completed' ? 'completed' : ''); ?>">
                                <strong>Approval Process - Ready for Pickup</strong>
                                <div class="text-muted small">Items are prepared and ready for collection</div>
                            </div>
                            <div class="timeline-item <?php echo $status == 'issued' ? 'active' : ''; ?>">
                                <strong>Requests have been picked up.</strong>
                                <div class="text-muted small">Items are collected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       

    <!-- Requested Items -->
    <div class="row">
        <div class="col-12">
            <div class="request-card">
                <div class="card-header-custom">
                    <h4 class="mb-0"><i class="fa-solid fa-boxes me-2 p-3"></i> Requested Items</h4>
                </div>
                <div class="card-body p-0">
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
                                <?php
                                $items = find_by_sql("SELECT ri.qty, i.name, i.description, i.UOM, c.name as category 
                                                      FROM request_items ri
                                                      JOIN items i ON ri.item_id = i.id
                                                      LEFT JOIN categories c ON i.categorie_id = c.id
                                                      WHERE ri.req_id = '{$request_id}'");
                                foreach ($items as $item): ?>
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
                                            <span class="text-muted"><?php echo remove_junk($item['UOM']); ?></span>
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
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>