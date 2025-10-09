<?php
$page_title = 'IT Dashboard';
require_once('includes/load.php');
page_require_level(2); 

// Fetch statistics
$total_users = count_by_id('users');
$total_employees = count_by_id('employees');
$active_users = count_by_status('users', '1');
$recent_users = find_recent_users('5');
$recent_employees = find_recent_employees('5');

// User levels for display
$user_levels = [
    1 => 'Admin',
    2 => 'IT',
    3 => 'User'
];



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

/* Info Boxes */
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
    border-top: 5px solid var(--primary-green);
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
    background: #025f17ff;
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
    border-color: #f1f3f4;
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

.badge-user {
    background: linear-gradient(135deg, var(--light-green), var(--primary-green)) !important;
    color: white;
}

.badge-admin {
    background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow)) !important;
    color: #000;
}

.badge-it {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
    color: white;
}

.badge-active {
    background: linear-gradient(135deg, var(--accent-green), var(--light-green)) !important;
    color: white;
}

.badge-inactive {
    background: linear-gradient(135deg, #6c757d, #5a6268) !important;
    color: white;
}

/* Quick Actions */
.quick-actions .btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.quick-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Empty States */
.text-center.p-5 {
    padding: 3rem !important;
}

.text-center.p-5 i {
    opacity: 0.5;
    margin-bottom: 1rem;
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

.card, .info-box {
    animation: fadeInUp 0.6s ease forwards;
}

/* Yellow accent elements */
.yellow-accent {
    color: var(--primary-yellow);
}

.green-accent {
    color: var(--primary-green);
}

/* User avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

/* Action buttons */
.action-buttons .btn {
    margin: 0 2px;
    border-radius: 6px;
}
</style>

<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5><i class="nav-icon fa-solid fa-gauge-high me-2 yellow-accent"></i> IT Administration Dashboard</h5>
            <div class="subtitle">Manage users, employees, and system configurations</div>
        </div>
        <!-- <div class="text-end">
            <div class="text-white-50 small">System Status</div>
            <div class="fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i> All Systems Operational</div>
        </div> -->
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green));">
                <i class="fa-solid fa-users"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $total_users['total']; ?></div>
                <span class="info-box-text">Total Users</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));">
                <i class="fa-solid fa-user-check"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $active_users; ?></div>
                <span class="info-box-text">Active Users</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <i class="fa-solid fa-id-card"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number"><?php echo $total_employees['total']; ?></div>
                <span class="info-box-text">Employees</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <div class="info-box d-flex align-items-center">
            <span class="info-box-icon" style="background: linear-gradient(135deg, var(--accent-green), var(--light-green));">
                <i class="fa-solid fa-shield-halved"></i>
            </span>
            <div class="info-box-content">
                <div class="info-box-number">3</div>
                <span class="info-box-text">User Levels</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
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
                        <a href="users.php" class="btn btn-success w-100 py-3">
                            <i class="fa-solid fa-user-plus fa-2x mb-2"></i><br>
                            Add New User
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="emps.php" class="btn btn-warning w-100 py-3 text-dark">
                            <i class="fa-solid fa-id-card-clip fa-2x mb-2"></i><br>
                            Manage Employees
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="user_reports.php" class="btn btn-info w-100 py-3">
                            <i class="fa-solid fa-chart-bar fa-2x mb-2"></i><br>
                            User Reports
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="system_logs.php" class="btn btn-secondary w-100 py-3">
                            <i class="fa-solid fa-server fa-2x mb-2"></i><br>
                            System Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="row">
    <!-- Recent Users -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-user-clock me-2 green-accent"></i>
                    Recently Added Users
                </h3>
                <div class="card-tools">
                    <a href="users.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (!empty($recent_users)) : ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="uploads/users/<?php echo $user['image']; ?>" 
                                                     class="user-avatar me-3" 
                                                     alt="<?php echo $user['name']; ?>">
                                                <div>
                                                    <div class="fw-bold"><?php echo $user['name']; ?></div>
                                                    <small class="text-muted"><?php echo $user['username']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($user_levels[$user['user_level']]); ?>">
                                                <?php echo $user_levels[$user['user_level']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['status'] == 1 ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['status'] == 1 ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $user['created_at']; ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fa-solid fa-users text-muted fa-4x mb-3"></i>
                            <h5 class="text-muted mb-2">No Users Found</h5>
                            <p class="text-muted">No users have been added to the system yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Employees -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-briefcase me-2 yellow-accent"></i>
                    Recently Added Employees
                </h3>
                <div class="card-tools">
                    <a href="emps.php" class="btn btn-sm btn-outline-warning text-dark">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (!empty($recent_employees)) : ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Position</th>
                                    <th>Office</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_employees as $employee): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></div>
                                            <small class="text-muted">ID: <?php echo $employee['id']; ?></small>
                                        </td>
                                        <td>
                                            <span class="text-success"><?php echo $employee['position']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $employee['office']; ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $employee['created_at']; ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fa-solid fa-id-card text-muted fa-4x mb-3"></i>
                            <h5 class="text-muted mb-2">No Employees Found</h5>
                            <p class="text-muted">No employees have been added to the system yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Management Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-sliders me-2 green-accent"></i>
                    System Management
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <i class="fa-solid fa-database fa-3x text-success mb-3"></i>
                                <h5>Database Management</h5>
                                <p class="text-muted">Manage database backups and maintenance</p>
                                <a href="database.php" class="btn btn-outline-success">Manage</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <i class="fa-solid fa-shield-alt fa-3x text-warning mb-3"></i>
                                <h5>Security Settings</h5>
                                <p class="text-muted">Configure system security and permissions</p>
                                <a href="security.php" class="btn btn-outline-warning text-dark">Configure</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <i class="fa-solid fa-archive fa-3x text-info mb-3"></i>
                                <h5>Archive Management</h5>
                                <p class="text-muted">Manage archived users and data</p>
                                <a href="it_archive.php" class="btn btn-outline-info">Manage Archive</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script>
$(document).ready(function () {
    // Initialize DataTables if needed
    $('table').DataTable({
        pageLength: 5,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        order: [[3, 'desc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries to show"
        }
    });
});
</script>

<?php include_once('layouts/footer.php'); ?>

<?php
// Count users by status
function count_by_status($table, $status) {
    global $db;
    $sql = "SELECT COUNT(*) as total FROM {$table} WHERE status='{$status}'";
    $result = $db->query($sql);
    $data = $result->fetch_assoc();
    return (int)$data['total'];
}

// Find recent users
function find_recent_users($limit = 5) {
    global $db;
    $sql = "SELECT * FROM users ORDER BY user_level DESC LIMIT {$limit}";
    return find_by_sql($sql);
}

// Find recent employees
function find_recent_employees($limit = 5) {
    global $db;
    $sql = "SELECT * FROM employees ORDER BY created_at DESC LIMIT {$limit}";
    return find_by_sql($sql);
}
?>