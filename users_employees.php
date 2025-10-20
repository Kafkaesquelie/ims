<?php
$page_title = 'Employees Directory';
require_once('includes/load.php');
page_require_level(1);

// Fetch employees with comprehensive summaries (requests + property records)
$employees_summary = find_by_sql("
    SELECT 
        e.id,
        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) AS employee_name,
        e.position,
        o.office_name,
        d.division_name,
        e.image,
        e.user_id,
        -- Request statistics (if employee has user account)
        COUNT(DISTINCT r.id) as total_requests,
        SUM(CASE WHEN r.status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN r.status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN r.status = 'Approved' THEN 1 ELSE 0 END) as approved_requests,
        SUM(CASE WHEN r.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_requests,
        -- PAR records
        COUNT(DISTINCT t_par.id) as total_par,
        SUM(CASE WHEN t_par.status = 'Issued' THEN 1 ELSE 0 END) as active_par,
        SUM(CASE WHEN t_par.status = 'Returned' THEN 1 ELSE 0 END) as returned_par,
        -- ICS records
        COUNT(DISTINCT t_ics.id) as total_ics,
        SUM(CASE WHEN t_ics.status = 'Issued' THEN 1 ELSE 0 END) as active_ics,
        SUM(CASE WHEN t_ics.status = 'Returned' THEN 1 ELSE 0 END) as returned_ics,
        -- Activity dates
        MIN(COALESCE(r.date, t_par.transaction_date, t_ics.transaction_date)) as first_activity,
        MAX(COALESCE(r.date, t_par.transaction_date, t_ics.transaction_date)) as last_activity,
        MIN(r.date) as first_request,
        MAX(r.date) as last_request,
        MIN(t_par.transaction_date) as first_par,
        MAX(t_par.transaction_date) as last_par,
        MIN(t_ics.transaction_date) as first_ics,
        MAX(t_ics.transaction_date) as last_ics
    FROM employees e
    LEFT JOIN offices o ON e.office = o.id 
    LEFT JOIN divisions d ON e.division = d.id 
    -- Join with users table to get request data
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN requests r ON u.id = r.requested_by
    -- Property records
    LEFT JOIN transactions t_par ON e.id = t_par.employee_id AND t_par.par_no IS NOT NULL
    LEFT JOIN transactions t_ics ON e.id = t_ics.employee_id AND t_ics.ics_no IS NOT NULL
    WHERE e.status = 1
    GROUP BY e.id
    ORDER BY e.last_name ASC, e.first_name ASC
");

// Calculate overall statistics
$total_employees = count($employees_summary);
?>

<?php include_once('layouts/header.php'); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-sm-6">
            <h5 class="mb-0"><i class="nav-icon fas fa-users"></i> Employees Directory</h5>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                <li class="breadcrumb-item active">Employees</li>
            </ol>
        </div>
    </div>

    <!-- Employees Section -->
    <div class="card">
        <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="nav-icon fas fa-id-card"></i> All Employees (<?php echo $total_employees; ?>)</h3>
                <span class="badge bg-success"><?php echo $total_employees; ?> Employees</span>
            </div>
        </div>
        <div class="card-body">
            <?php if ($total_employees > 0): ?>
                <div class="row">
                    <?php foreach ($employees_summary as $employee): ?>
                        <div class="col-xl-4 col-lg-4 col-md-6 mb-4">
                            <div class="card employee-card h-100 shadow-sm">
                                <div class="card-header bg-success text-white text-center py-3">
                                    <div class="employee-avatar mb-2">
                                        <img src="uploads/users/<?php echo $employee['image'] ? remove_junk($employee['image']) : 'default.png'; ?>"
                                             alt="Profile"
                                             class="img-circle img-fluid"
                                             style="width:80px; height:80px; object-fit:cover; border: 3px solid white;">
                                    </div>
                                    <h5 class="card-title mb-1"><?php echo remove_junk($employee['employee_name']); ?></h5>
                                    <p class="card-subtitle mb-0"><?php echo remove_junk($employee['position']); ?></p>
                                    <div class="mt-1">
                                        <?php if ($employee['user_id']): ?>
                                            <span class="badge bg-info">Has User Account</span>
                                        <?php endif; ?>
                                       
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Office & Division -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted"><i class="fas fa-building mr-1"></i> Office:</small>
                                            <small class="text-dark font-weight-bold"><?php echo remove_junk($employee['office_name'] ?? 'N/A'); ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted"><i class="fas fa-sitemap mr-1"></i> Division:</small>
                                            <small class="text-dark font-weight-bold"><?php echo remove_junk($employee['division_name'] ?? 'N/A'); ?></small>
                                        </div>
                                    </div>

                                    <!-- Request Statistics (if employee has user account) -->
                                    <?php if ($employee['total_requests'] > 0): ?>
                                        <div class="request-stats border-top pt-3">
                                            <h6 class="text-center mb-3 text-success"><i class="fas fa-file-alt mr-1"></i> Request Summary</h6>
                                            <div class="row text-center">
                                                <div class="col-4 mb-2">
                                                    <div class="stat-item border rounded p-2">
                                                        <h6 class="mb-0 text-success font-weight-bold"><?php echo (int)$employee['total_requests']; ?></h6>
                                                        <small class="text-muted">Total</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 mb-2">
                                                    <div class="stat-item border rounded p-2">
                                                        <h6 class="mb-0 text-success font-weight-bold"><?php echo (int)$employee['completed_requests']; ?></h6>
                                                        <small class="text-muted">Completed</small>
                                                    </div>
                                                </div>
                                                <div class="col-4 mb-2">
                                                    <div class="stat-item border rounded p-2">
                                                        <h6 class="mb-0 text-warning font-weight-bold"><?php echo (int)$employee['pending_requests']; ?></h6>
                                                        <small class="text-muted">Pending</small>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Property Statistics -->
                                    <div class="property-stats border-top pt-3">
                                        <h6 class="text-center mb-3 text-success"><i class="fas fa-tools mr-1"></i> Property Records</h6>
                                        
                                        <!-- PAR Statistics -->
                                        <?php if ($employee['total_par'] > 0): ?>
                                            <div class="mb-3">
                                                <h6 class="text-success mb-2">
                                                    <i class="fas fa-wrench mr-1"></i> Equipment (PAR)
                                                </h6>
                                                <div class="row text-center">
                                                    <div class="col-4 mb-2">
                                                        <div class="stat-item border rounded p-2">
                                                            <h6 class="mb-0 text-success font-weight-bold"><?php echo (int)$employee['total_par']; ?></h6>
                                                            <small class="text-muted">Total</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 mb-2">
                                                        <div class="stat-item border rounded p-2">
                                                            <h6 class="mb-0 text-success font-weight-bold"><?php echo (int)$employee['active_par']; ?></h6>
                                                            <small class="text-muted">Active</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-4 mb-2">
                                                        <div class="stat-item border rounded p-2">
                                                            <h6 class="mb-0 text-warning font-weight-bold"><?php echo (int)$employee['returned_par']; ?></h6>
                                                            <small class="text-muted">Returned</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- ICS Statistics -->
                                        <?php if ($employee['total_ics'] > 0): ?>
                                            <div>
                                                <h6 class="text-success mb-2">
                                                    <i class="fas fa-cube mr-1"></i> Semi-Expendable (ICS)
                                                </h6>
                                                <div class="row text-center">
                                                    <div class="col-4">
                                                        <div class="stat-item border rounded p-2">
                                                            <h6 class="mb-0 text-success font-weight-bold"><?php echo (int)$employee['total_ics']; ?></h6>
                                                            <small class="text-muted">Total</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="stat-item border rounded p-2">
                                                            <h6 class="mb-0 text-success font-weight-bold"><?php echo (int)$employee['active_ics']; ?></h6>
                                                            <small class="text-muted">Active</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="stat-item border rounded p-2">
                                                            <h6 class="mb-0 text-secondary font-weight-bold"><?php echo (int)$employee['returned_ics']; ?></h6>
                                                            <small class="text-muted">Returned</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Activity Timeline -->
                                    <div class="activity-timeline mt-3 border-top pt-3">
                                        <?php if ($employee['first_activity']): ?>
                                            <small class="text-muted d-block mb-1">
                                                <i class="fas fa-calendar-alt mr-1"></i> 
                                                First Activity: <?php echo date("M d, Y", strtotime($employee['first_activity'])); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($employee['last_activity']): ?>
                                            <small class="text-muted d-block mb-1">
                                                <i class="fas fa-calendar-check mr-1"></i> 
                                                Last Activity: <?php echo date("M d, Y", strtotime($employee['last_activity'])); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($employee['total_requests'] > 0): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-file-alt mr-1"></i> 
                                                Requests: <?php echo (int)$employee['total_requests']; ?> total
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer text-center bg-light">
                                    <a href="user_emp_records.php?id=<?php echo (int)$employee['id']; ?>&type=employee" 
                                       class="btn btn-success btn-sm btn-block">
                                        <i class="fas fa-eye mr-1"></i> View Full Profile & Records
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-id-card fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Employees Found</h5>
                    <p class="text-muted">There are no active employees in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.employee-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e3e6f0;
}
.employee-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.125);
}
.stat-item {
    transition: all 0.2s ease;
}
.stat-item:hover {
    background-color: #f8f9fa;
    transform: scale(1.05);
}
.employee-card .card-header {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}
.activity-timeline {
    font-size: 0.8rem;
}
.btn-success {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    border-color: #28a745;
}
.badge.bg-success {
    background: linear-gradient(135deg, #28a745, #1e7e34) !important;
}
</style>

<?php include_once('layouts/footer.php'); ?>