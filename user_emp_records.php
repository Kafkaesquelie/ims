<?php
$page_title = 'Employee Records';
require_once('includes/load.php');
page_require_level(1);

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$employee_id) {
    redirect('users_employees.php', false);
}

// Fetch employee details with office and division names
$employee = find_by_sql("
    SELECT 
        e.*,
        o.office_name,
        d.division_name,
        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as employee_name,
        u.id as user_id,
        u.name as user_name
    FROM employees e
    LEFT JOIN offices o ON e.office = o.id
    LEFT JOIN divisions d ON e.division = d.id
    LEFT JOIN users u ON e.user_id = u.id
    WHERE e.id = '{$employee_id}'
")[0];

if (!$employee) {
    redirect('users_employees.php', false);
}

$person_name = $employee['employee_name'];
$person_position = $employee['position'];
$person_office = $employee['office_name'] ?? 'N/A';
$person_division = $employee['division_name'] ?? 'N/A';
$person_image = $employee['image'];
$has_user_account = !empty($employee['user_id']);

// Fetch employee requests summary (if they have a user account)
$requests_summary = array('total_requests' => 0, 'completed' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'first_request' => null, 'last_request' => null);
$requests_details = array();
$issued_items = array();

if ($has_user_account) {
    $requests_summary = find_by_sql("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
            MIN(date) as first_request,
            MAX(date) as last_request
        FROM requests 
        WHERE requested_by = '{$employee['user_id']}'
    ")[0] ?? $requests_summary;

    // Fetch employee requests details with proper office and division names
    $requests_details = find_by_sql("
        SELECT 
            r.*, 
            o.office_name,
            d.division_name,
            u.name as requested_by_name
        FROM requests r 
        LEFT JOIN users u ON r.requested_by = u.id
        LEFT JOIN offices o ON u.office = o.id 
        LEFT JOIN divisions d ON u.division = d.id 
        WHERE r.requested_by = '{$employee['user_id']}' 
        ORDER BY r.date DESC
    ");

    // Fetch issued supplies/materials with proper item details
    $issued_items = find_by_sql("
        SELECT 
            ri.*, 
            i.name as item_name,
            i.stock_card,
            i.unit_cost,
            r.ris_no,
            r.date as issue_date,
            o.office_name,
            d.division_name
        FROM request_items ri
        JOIN requests r ON ri.req_id = r.id
        JOIN items i ON ri.item_id = i.id
        LEFT JOIN users u ON r.requested_by = u.id
        LEFT JOIN offices o ON u.office = o.id
        LEFT JOIN divisions d ON u.division = d.id
        WHERE r.requested_by = '{$employee['user_id']}' AND r.status = 'Completed'
        ORDER BY r.date DESC
    ");
}

// Fetch employee PAR transactions
$par_transactions = find_by_sql("
    SELECT 
            t.id,
            t.par_no,
            t.item_id,
            p.property_no,
            p.article AS item_name,
            p.description,
            p.fund_cluster,
            p.unit,
            t.quantity,
            t.transaction_date,
            t.status,
            t.remarks,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            e.position AS position,
            o.office_name AS department,
            e.image
        FROM transactions t
        LEFT JOIN properties p ON t.item_id = p.id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        WHERE t.par_no IS NOT NULL
        ORDER BY t.transaction_date DESC
");

// Fetch employee ICS transactions
$ics_transactions = find_by_sql("
   SELECT 
            t.id,
            t.ics_no,
            t.item_id,
            s.inv_item_no,
            s.item AS item_name,
            s.item_description,
            s.fund_cluster,
            s.unit,
            t.quantity,
            t.transaction_date,
            t.status,
            t.remarks,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            e.position AS position,
            o.office_name AS department,
            e.image
        FROM transactions t
        LEFT JOIN semi_exp_prop s ON t.item_id = s.id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        WHERE t.ics_no IS NOT NULL 
          AND t.ics_no != ''
        ORDER BY t.transaction_date DESC
    
");

// Employee comprehensive summary stats
$emp_summary = find_by_sql("
    SELECT 
        COUNT(CASE WHEN par_no IS NOT NULL THEN 1 END) as total_par,
        COUNT(CASE WHEN ics_no IS NOT NULL THEN 1 END) as total_ics,
        SUM(CASE WHEN par_no IS NOT NULL AND status = 'Issued' THEN 1 ELSE 0 END) as active_par,
        SUM(CASE WHEN ics_no IS NOT NULL AND status = 'Issued' THEN 1 ELSE 0 END) as active_ics,
        SUM(CASE WHEN par_no IS NOT NULL AND status = 'Returned' THEN 1 ELSE 0 END) as returned_par,
        SUM(CASE WHEN ics_no IS NOT NULL AND status = 'Returned' THEN 1 ELSE 0 END) as returned_ics
    FROM transactions 
    WHERE employee_id = '{$employee_id}'
")[0] ?? array('total_par' => 0, 'total_ics' => 0, 'active_par' => 0, 'active_ics' => 0, 'returned_par' => 0, 'returned_ics' => 0);

// Calculate overall activity dates
$activity_dates = find_by_sql("
    SELECT 
        MIN(COALESCE(r.date, t.transaction_date)) as first_activity,
        MAX(COALESCE(r.date, t.transaction_date)) as last_activity
    FROM employees e
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN requests r ON u.id = r.requested_by
    LEFT JOIN transactions t ON e.id = t.employee_id
    WHERE e.id = '{$employee_id}'
")[0] ?? array('first_activity' => null, 'last_activity' => null);
?>

<?php include_once('layouts/header.php'); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col-sm-6">
           
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                <li class="breadcrumb-item"><a href="users_employees.php">Employees</a></li>
                <li class="breadcrumb-item active"><?php echo remove_junk($person_name); ?></li>
            </ol>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <img src="uploads/users/<?php echo $person_image ? remove_junk($person_image) : 'default.png'; ?>"
                         alt="Profile"
                         class="img-circle img-fluid mb-3"
                         style="width:150px; height:150px; object-fit:cover; border: 3px solid #dee2e6;">
                    <h4 class="mb-1"><?php echo remove_junk($person_name); ?></h4>
                    <p class="text-muted mb-1"><?php echo remove_junk($person_position); ?></p>
                    <div class="mt-2">
                        <span class="badge bg-success">Employee</span>
                        <?php if ($has_user_account): ?>
                            <span class="badge bg-warning">Has User Account</span>
                        <?php endif; ?>
                        <?php if ($emp_summary['total_par'] > 0 || $emp_summary['total_ics'] > 0): ?>
                            <span class="badge bg-warning">Has Property Records</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>Office:</strong></td>
                                    <td><?php echo remove_junk($person_office); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Division:</strong></td>
                                    <td><?php echo remove_junk($person_division); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Position:</strong></td>
                                    <td><?php echo remove_junk($person_position); ?></td>
                                </tr>
                                <?php if ($has_user_account && !empty($requests_summary['first_request'])): ?>
                                <tr>
                                    <td><strong>First Request:</strong></td>
                                    <td><?php echo date("M d, Y", strtotime($requests_summary['first_request'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Request:</strong></td>
                                    <td><?php echo date("M d, Y", strtotime($requests_summary['last_request'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($activity_dates['first_activity'])): ?>
                                <tr>
                                    <td><strong>First Activity:</strong></td>
                                    <td><?php echo date("M d, Y", strtotime($activity_dates['first_activity'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <!-- Quick Stats -->
                            <div class="row text-center">
                                <?php if ($has_user_account): ?>
                                    <div class="col-6 mb-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-success mb-0"><?php echo (int)$requests_summary['total_requests']; ?></h3>
                                            <small class="text-muted">Total Requests</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="border rounded p-3">
                                            <h3 class="text-success mb-0"><?php echo (int)$requests_summary['completed']; ?></h3>
                                            <small class="text-muted">Completed</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3">
                                        <h3 class="text-success mb-0"><?php echo (int)$emp_summary['total_par']; ?></h3>
                                        <small class="text-muted">PAR Records</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-3">
                                        <h3 class="text-success mb-0"><?php echo (int)$emp_summary['total_ics']; ?></h3>
                                        <small class="text-muted">ICS Records</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h3 class="text-success mb-0"><?php echo (int)$emp_summary['active_par'] + (int)$emp_summary['active_ics']; ?></h3>
                                        <small class="text-muted">Active Properties</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h3 class="text-warning mb-0"><?php echo (int)$emp_summary['returned_par'] + (int)$emp_summary['returned_ics']; ?></h3>
                                        <small class="text-muted">Returned Items</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   

    <!-- Detailed Sections -->
    <?php if ($has_user_account && count($requests_details) > 0): ?>
        <!-- Request History -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0"><i class="fas fa-history"></i> Request History</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive" >
                    <table class="table table-striped table-hover" id="detailTable">
                        <thead>
                            <tr>
                                <th>RIS No</th>
                                <th>Date</th>
                                <th>Office</th>
                                <th>Division</th>
                                <th>Items Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests_details as $request): ?>
                                <tr>
                                    <td><strong>RIS-<?php echo $request['ris_no']; ?></strong></td>
                                    <td><?php echo date("M d, Y", strtotime($request['date'])); ?></td>
                                    <td><?php echo remove_junk($request['office_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo remove_junk($request['division_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo count_request_items($request['id']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $request['status'] == 'Completed' ? 'success' : 
                                                 ($request['status'] == 'Pending' ? 'warning' : 
                                                 ($request['status'] == 'Approved' ? 'primary' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="print_ris.php?id=<?php echo (int)$request['id']; ?>" 
                                           class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Issued Items -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0"><i class="fas fa-box-open"></i> Issued Supplies & Materials</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reqTable">
                        <thead>
                            <tr>
                                <th>RIS No</th>
                                <th>Item Name</th>
                                <th>Stock No</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                                <th>Date Issued</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issued_items as $item): ?>
                                <tr>
                                    <td>RIS-<?php echo $item['ris_no']; ?></td>
                                    <td><?php echo remove_junk($item['item_name']); ?></td>
                                    <td><?php echo remove_junk($item['stock_card']); ?></td>
                                    <td><?php echo (int)$item['qty']; ?></td>
                                    <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['qty'] * $item['unit_cost'], 2); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($item['issue_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($has_user_account): ?>
        <!-- No Requests Message -->
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Requests Found</h5>
                <p class="text-muted">This employee has a user account but hasn't made any requests yet.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Property Records -->
    <div class="row">
        <!-- PAR Transactions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0"><i class="fas fa-file-contract"></i> PAR Transactions (Equipment)</h3>
                </div>
                <div class="card-body">
                    <?php if (count($par_transactions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="parTable">
                                <thead>
                                    <tr>
                                        <th>PAR No</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Date Issued</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($par_transactions as $par): ?>
                                        <tr>
                                            <td><?php echo remove_junk($par['par_no']); ?></td>
                                            <td><?php echo remove_junk($par['item_name']); ?></td>
                                            <td><?php echo (int)$par['quantity']; ?></td>
                                            <td><?php echo date("M d, Y", strtotime($par['transaction_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $par['status'] == 'Issued' ? 'success' : 
                                                         ($par['status'] == 'Returned' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($par['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-contract fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No PAR transactions found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ICS Transactions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0"><i class="fas fa-file-invoice"></i> ICS Transactions (Semi-Expendable)</h3>
                </div>
                <div class="card-body">
                    <?php if (count($ics_transactions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="icsTable">
                                <thead>
                                    <tr>
                                        <th>ICS No</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Date Issued</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ics_transactions as $ics): ?>
                                        <tr>
                                            <td><?php echo remove_junk($ics['ics_no']); ?></td>
                                            <td><?php echo remove_junk($ics['item_name']); ?></td>
                                            <td><?php echo (int)$ics['quantity']; ?></td>
                                            <td><?php echo date("M d, Y", strtotime($ics['transaction_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $ics['status'] == 'Issued' ? 'success' : 
                                                         ($ics['status'] == 'Returned' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($ics['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-invoice fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No ICS transactions found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-box {
    margin-bottom: 0;
    border-radius: 10px;
}
.card {
    border-radius: 10px;
}
.table th {
    border-top: none;
    font-weight: 600;
}
</style>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
    var table = $('#icsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    }); 
    }); 

</script>
<script>
    $(document).ready(function () {
    var table = $('#parTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    }); 
    }); 

</script>

<script>
    $(document).ready(function () {
    var table = $('#reqTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    }); 
    }); 

</script>
<script>
    $(document).ready(function () {
    var table = $('#detailTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    }); 
    }); 

</script>