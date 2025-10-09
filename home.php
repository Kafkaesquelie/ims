<?php
$page_title = 'User Home Page';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user();
$user_id = $current_user['id'];


if (isset($_GET['received'])) {
    $request_id = (int)$_GET['received'];
    $sql = "UPDATE requests 
            SET status = 'Completed', date_completed = NOW() 
            WHERE id = '{$request_id}'";

    if ($db->query($sql)) {
        // Optional: move to logs table
        $session->msg("s", "Request marked as completed and moved to logs.");
    } else {
        $session->msg("d", "Failed to update request.");
    }
    redirect($_SERVER['PHP_SELF']);
}


// Total requests for this user
$total_requests = count_user_requests($user_id);

// Requests by status
$pending_count   = count_user_requests_by_status($user_id, 'pending');
$approved_count  = count_user_requests_by_status($user_id, 'approved');
$completed_count = count_user_requests_by_status($user_id, 'completed');

// (Optional) Fetch user's pending requests for the table
$pending_requests = find_by_sql("
    SELECT * 
    FROM requests 
    WHERE requested_by = '{$db->escape($user_id)}' 
       AND status IN ('Pending', 'Approved', 'Issued')
    ORDER BY date DESC
");

// Fetch pending requests for this user
$pending_requests = find_by_sql("SELECT * FROM requests WHERE requested_by = '{$user_id}' AND status IN ('Pending','Approved','Issued') ORDER BY date DESC");
?>

<?php include_once('layouts/header.php'); ?>

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

    /* Dashboard Header */
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        border-left: 5px solid var(--primary-yellow);
    }

    .dashboard-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.5rem;
    }

    .dashboard-header .subtitle {
        opacity: 0.9;
        font-size: 0.9rem;
    }

    /* Info Boxes - Green & Yellow Theme */
    .info-box {
        background: var(--card-bg);
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        border: none;
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
        border-top: 4px solid transparent;
    }

    .info-box:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .info-box-icon {
        width: 80px;
        height: 80px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .info-box:hover .info-box-icon {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    .info-box-content {
        flex: 1;
        text-align: right;
    }

    .info-box-number {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 0.2rem;
        color: var(--dark-green);
    }

    .info-box-text {
        color: var(--text-dark);
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Cards Styling */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-top: 3px solid var(--primary-green);
    }

    .card:hover {
        box-shadow: var(--hover-shadow);
    }

    .card-header {
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
        border-bottom: 2px solid #e8f5e9;
        border-radius: 15px 15px 0 0 !important;
        padding: 1.25rem 1.5rem;
    }

    .card-header h3 {
        margin: 0;
        font-weight: 700;
        color: var(--dark-green);
        font-size: 1.2rem;
    }

    /* Tables */
    .table {
        margin-bottom: 0;
        border-radius: 12px;
        overflow: hidden;
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

    /* Status Badges */
    .badge {
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
    }

    .badge.bg-warning {
        background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow)) !important;
        color: #000 !important;
    }

    .badge.bg-success {
        background: linear-gradient(135deg, var(--light-green), var(--primary-green)) !important;
    }

    .badge.bg-primary {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
    }

    /* Progress Bar */
    .progress {
        height: 20px;
        border-radius: 10px;
        background-color: #e9ecef;
    }

    .progress-bar {
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    /* Empty States */
    .text-center.p-5 {
        padding: 3rem !important;
    }

    .text-center.p-5 i {
        opacity: 0.5;
        margin-bottom: 1rem;
    }

    /* Buttons */
    .btn-outline-primary {
        border-color: var(--primary-green);
        color: var(--primary-green);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }

    /* Quick Actions - Super Admin Style */
    .quick-actions .btn {
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        padding: 1.5rem 0.5rem;
        height: 100%;
    }

    .quick-actions .btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .quick-actions .btn i {
        display: block;
        margin-bottom: 0.5rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1rem;
            text-align: center;
        }

        .info-box {
            margin-bottom: 1rem;
        }

        .info-box-icon {
            width: 60px;
            height: 60px;
            font-size: 1.8rem;
        }

        .info-box-number {
            font-size: 1.8rem;
        }

        .quick-actions .btn {
            padding: 1rem 0.5rem;
            margin-bottom: 1rem;
        }
    }

    /* Animation for cards */
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

    .card,
    .info-box {
        animation: fadeInUp 0.6s ease forwards;
    }

    /* Yellow accent elements */
    .yellow-accent {
        color: var(--primary-yellow);
    }

    .green-accent {
        color: var(--primary-green);
    }

    /* Custom scrollbar for tables */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--primary-green);
        border-radius: 10px;
    }
    .btn-received-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #28a745;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    border: 2px solid #28a745;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.btn-received-outline:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #28a745, #20c997);
    transition: all 0.4s ease;
    z-index: -1;
}

.btn-received-outline:hover {
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-received-outline:hover:before {
    left: 0;
}
.btn-view-elegant {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #007bff;
    padding: 0.6rem 1.3rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    border: 2px solid #007bff;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    z-index: 1;
    letter-spacing: 0.5px;
}

.btn-view-elegant:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #007bff, #0056b3);
    transition: all 0.4s ease;
    z-index: -1;
}

.btn-view-elegant:hover {
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
    border-color: #007bff;
}

.btn-view-elegant:hover:before {
    left: 0;
}

.btn-view-elegant:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(0, 123, 255, 0.3);
}
</style>

<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5><i class="nav-icon fa-solid fa-gauge-high me-2 yellow-accent"></i> User Dashboard</h5>
            <div class="subtitle">Welcome back, <?php echo remove_junk(ucfirst($current_user['name'])); ?>! Manage your requests and track their status.</div>
        </div>
        <div class="text-end">
            <div class="text-white-50 small">Last Login</div>
            <div class="fw-bold"><?php echo date('F j, Y g:i A'); ?></div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green));">
                <i class="fa-solid fa-pen-to-square"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $total_requests; ?></div>
                <span class="info-box-text">Total Requests</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));">
                <i class="fa-solid fa-clock"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $pending_count; ?></div>
                <span class="info-box-text">Pending</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <i class="fa-solid fa-thumbs-up"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $approved_count; ?></div>
                <span class="info-box-text">Approved</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, var(--light-green), var(--primary-green));">
                <i class="fa-solid fa-check-circle"></i>

            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $completed_count; ?></div>
                <span class="info-box-text">Completed</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions - Super Admin Style -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-bolt me-2 yellow-accent"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="row quick-actions">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="requests_form.php" class="btn btn-success w-100 py-3">
                            <i class="fa-solid fa-pen-to-square fa-2x mb-2"></i><br>
                            Submit New Request
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="user_logs.php" class="btn btn-warning w-100 py-3 text-dark">
                            <i class="fa-solid fa-list fa-2x mb-2"></i><br>
                            View Transactions
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="ICS.PHP" class="btn btn-info w-100 py-3">
                            <i class="fa-solid fa-file-lines fa-2x mb-2"></i><br>
                            ICS Documents
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="par.php" class="btn btn-secondary w-100 py-3">
                            <i class="fa-solid fa-handshake fa-2x mb-2"></i><br>
                            PAR Documents
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Requests DataTable -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-clock-rotate-left me-2 yellow-accent"></i>
                    My Pending Requests
                    <?php if (!empty($pending_requests)): ?>
                        <span class="badge bg-warning ms-2">
                            <?php echo count($pending_requests); ?> Pending
                        </span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (!empty($pending_requests)) : ?>
                        <table class="table table-hover align-middle" id="pendingRequestsTable">
                            <thead>
                                <tr>
                                    <th>Request No</th>
                                    <th>Date Requested</th>
                                    <th>Items Count</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_requests as $index => $req):
                                    //Count items in this request
                                    $item_count = count_request_items($req['id']);
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo ($req['ris_no']); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <i class="fa-regular fa-calendar-days me-1 text-muted"></i>
                                            <?php echo date("F j, Y", strtotime($req['date'])); ?><br>
                                            <i class="fa-regular fa-clock me-1 text-muted"></i>
                                            <?php echo date('h:i A', strtotime($req['date'])); ?>
                                        </td>


                                        <td>
                                            <span><?php echo $item_count; ?> Items</span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $status = strtolower($req['status']);
                                            $badgeClass = 'secondary'; // default color

                                            if ($status == 'pending') {
                                                $badgeClass = 'warning';
                                            } elseif ($status == 'approved') {
                                                $badgeClass = 'primary';
                                            } elseif ($status == 'issued') {
                                                $badgeClass = 'success';
                                            } elseif ($status == 'rejected') {
                                                $badgeClass = 'danger';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <i class="fa-solid 
                                                    <?php echo ($status == 'pending') ? 'fa-clock' : (($status == 'approved') ? 'fa-thumbs-up' : (($status == 'issued') ? 'fa-circle-check' : (($status == 'rejected') ? 'fa-xmark' : 'fa-info-circle'))); ?> me-1"></i>
                                                <?php echo ucfirst($req['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status = strtolower($req['status']);
                                            $progress_class = 'bg-warning';
                                            $progress_width = 25;
                                            $progress_text = 'Pending';

                                            if ($status === 'approved') {
                                                $progress_class = 'bg-primary';
                                                $progress_width = 50;
                                                $progress_text = 'Approved';
                                            } elseif ($status === 'issued' || $status === 'for confirmation') {
                                                $progress_class = 'bg-info progress-bar-striped progress-bar-animated';
                                                $progress_width = 75;
                                                $progress_text = 'Issued - Waiting Confirmation';
                                            } elseif ($status === 'completed') {
                                                $progress_class = 'bg-success progress-bar-striped';
                                                $progress_width = 100;
                                                $progress_text = 'Completed';
                                            } elseif ($status === 'rejected') {
                                                $progress_class = 'bg-danger';
                                                $progress_width = 100;
                                                $progress_text = 'Rejected';
                                            }
                                            ?>
                                            <div class="progress" style="height: 20px; border-radius: 10px;">
                                                <div class="progress-bar <?php echo $progress_class; ?>"
                                                    role="progressbar"
                                                    style="width: <?php echo $progress_width; ?>%;"
                                                    aria-valuenow="<?php echo $progress_width; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    <?php echo $progress_text; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Pending → Approved → Issued → Completed
                                            </small>
                                        </td>

                                        <td class="text-center">
                                            <?php if (strtolower($req['status']) == 'issued'): ?>
                                                <a href="?received=<?php echo (int)$req['id']; ?>" 
   class="btn-received-outline receive-btn"
   title="Mark as Received">
   <i class="fa-solid fa-hand-holding-box me-2"></i>
   Received
</a>
                                            <?php else: ?>
                                              <a href="view_user_request.php?id=<?php echo (int)$req['id']; ?>" 
   class="btn-view-elegant"
   title="View Request Details">
   <i class="fa fa-eye me-2"></i>
   View 
</a>
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Message if no pending requests -->
                        <div class="text-center p-5">
                            <i class="fa fa-folder-open fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Pending Requests Found</h5>
                            <p class="text-muted">You haven't submitted any requests yet. Start by creating a new request!</p>
                            <a href="requests_form.php" class="btn btn-success">
                                <i class="fa-solid fa-plus me-2"></i>Submit First Request
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.confirm-received-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.dataset.id;
                Swal.fire({
                    title: 'Confirm Receipt',
                    text: 'Have you received all requested items?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Confirm'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('confirm_received.php?id=' + requestId)
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Confirmed!', 'Your request has been marked as received.', 'success')
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire('Error', data.message || 'Unable to confirm receipt.', 'error');
                                }
                            })
                            .catch(() => Swal.fire('Error', 'Network issue while confirming.', 'error'));
                    }
                });
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.receive-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');

                Swal.fire({
                    title: 'Confirm Receipt?',
                    text: "Click confirm if you have received all issued items.",
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, received!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Completing...',
                            html: 'Updating the request status...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                                window.location.href = url;
                            }
                        });
                    }
                });
            });
        });
    });
</script>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<!-- DataTables JS -->
<script>
    $(document).ready(function() {
        $('#pendingRequestsTable').DataTable({
            pageLength: 5,
            lengthMenu: [
                [5, 10, 25, 50],
                [5, 10, 25, 50]
            ],
            order: [
                [1, 'desc']
            ],
            searchinh: 'false',
            columnDefs: [{
                    orderable: false,
                    targets: 5
                } // Disable sorting on Actions
            ],

        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add any custom JavaScript here for user interactions
        console.log('User dashboard loaded successfully');
    });
</script>