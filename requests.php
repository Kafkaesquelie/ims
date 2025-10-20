<?php
$page_title = 'All Requests';
require_once('includes/load.php');
page_require_level(1);

$current_user = current_user();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Define helper functions FIRST, before they are used
if (!function_exists('get_unit_name')) {
    function get_unit_name($unit_id) {
        global $db;
        $res = $db->query("SELECT name FROM units WHERE id = '{$unit_id}' LIMIT 1");
        return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res)['name'] : '';
    }
}

if (!function_exists('get_base_unit_name')) {
    function get_base_unit_name($base_unit_id) {
        global $db;
        $res = $db->query("SELECT name FROM base_units WHERE id = '{$base_unit_id}' LIMIT 1");
        return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res)['name'] : 'Unit';
    }
}

if (isset($_GET['issued'])) {
    $request_id = (int)$_GET['issued'];

    // Check if request contains archived items
    $archived_check = find_by_sql("
        SELECT COUNT(*) AS total 
        FROM request_items ri
        JOIN items i ON ri.item_id = i.id
        WHERE ri.req_id = '{$request_id}' AND i.archived = 1
    ");

    if ($archived_check && $archived_check[0]['total'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot issue this request because one or more items are archived.'
        ]);
        exit;
    }

    // Begin transaction
    $db->query("START TRANSACTION");

    try {
        // ✅ Get current school year
        $school_year = find_by_sql("SELECT id FROM school_years WHERE is_current = 1 LIMIT 1");
        $school_year_id = $school_year ? $school_year[0]['id'] : 0;

        // ✅ Get requestor info
        $req_info = find_by_sql("SELECT requested_by FROM requests WHERE id = '{$request_id}' LIMIT 1");
        $requested_by = $req_info ? (int)$req_info[0]['requested_by'] : 0;

        // ✅ Check if requestor is a user (admin/staff) or an employee
        $user_check = find_by_sql("SELECT user_level FROM users WHERE id = '{$requested_by}' LIMIT 1");
        $is_user = $user_check ? true : false;
        $is_employee = !$is_user;

        // ✅ Get all items from the request
        $req_items = find_by_sql("SELECT item_id, qty, unit FROM request_items WHERE req_id = '{$request_id}'");

        foreach ($req_items as $ri) {
            $item_id = (int)$ri['item_id'];
            $req_qty = (float)$ri['qty'];
            $req_unit = $ri['unit']; // This is the unit name as string

            // Fetch item info
            $item_sql = "SELECT i.id, i.quantity, i.unit_id, u.name as unit_name, i.base_unit_id, bu.name as base_unit_name
                         FROM items i 
                         LEFT JOIN units u ON i.unit_id = u.id 
                         LEFT JOIN base_units bu ON i.base_unit_id = bu.id 
                         WHERE i.id = '{$item_id}' LIMIT 1";
            $item_data = find_by_sql($item_sql);
            if (!$item_data) continue;

            $item_qty = (float)$item_data[0]['quantity'];
            $item_unit_id = (int)$item_data[0]['unit_id'];
            $item_unit_name = $item_data[0]['unit_name'];
            $base_unit_id = (int)$item_data[0]['base_unit_id'];
            $base_unit_name = $item_data[0]['base_unit_name'];

            // Default: same unit, no conversion
            $converted_qty = $req_qty;

            // Check if unit conversion is needed
            if ($req_unit !== $item_unit_name) {
                // Get conversion data
                $conv_sql = "
                    SELECT conversion_rate, from_unit_id, to_unit_id 
                    FROM unit_conversions 
                    WHERE item_id = '{$item_id}' 
                    LIMIT 1
                ";
                $conv_data = find_by_sql($conv_sql);

                if ($conv_data && count($conv_data) > 0) {
                    $conversion_rate = (float)$conv_data[0]['conversion_rate'];
                    $from_unit_id = $conv_data[0]['from_unit_id'];
                    $to_unit_id = $conv_data[0]['to_unit_id'];
                    
                    // Get unit names for conversion
                    $from_unit_name = get_unit_name($from_unit_id);
                    $to_unit_name = get_base_unit_name($to_unit_id);

                    // If requesting base units but stored in main units
                    if ($req_unit === $to_unit_name && $item_unit_name === $from_unit_name) {
                        $converted_qty = $req_qty / $conversion_rate;
                    }
                    // If requesting main units but stored in base units (less common)
                    else if ($req_unit === $from_unit_name && $item_unit_name === $to_unit_name) {
                        $converted_qty = $req_qty * $conversion_rate;
                    }
                }
            }

            // Subtract from inventory
            $new_qty = $item_qty - $converted_qty;
            if ($new_qty < 0) $new_qty = 0;

            $db->query("UPDATE items SET quantity = '{$new_qty}' WHERE id = '{$item_id}'");

            // ✅ Update or insert into item_stocks_per_year
            $check = $db->query("
                SELECT id FROM item_stocks_per_year 
                WHERE item_id = '{$item_id}' AND school_year_id = '{$school_year_id}' LIMIT 1
            ");

            if ($db->num_rows($check) > 0) {
                $db->query("
                    UPDATE item_stocks_per_year 
                    SET stock = '{$new_qty}', updated_at = NOW()
                    WHERE item_id = '{$item_id}' AND school_year_id = '{$school_year_id}'
                ");
            } else {
                $db->query("
                    INSERT INTO item_stocks_per_year (item_id, school_year_id, stock, updated_at)
                    VALUES ('{$item_id}', '{$school_year_id}', '{$new_qty}', NOW())
                ");
            }

            // ✅ Log stock history
            $db->query("
                INSERT INTO stock_history (item_id, previous_qty, new_qty, change_type, changed_by, remarks)
                VALUES (
                    '{$item_id}',
                    '{$item_qty}',
                    '{$new_qty}',
                    'stock_out',
                    '{$current_user['id']}',
                    'Request #{$request_id} issued'
                )
            ");
        }

               //  Determine new status - Employees are automatically completed, users stay as Issued
        if ($is_employee) {
            // Employee requests: mark as Completed immediately
            $new_status = 'Completed';
            $date_field = "date_completed = NOW()";
        } else {
            // User requests: mark as Issued (for confirmation)
            $new_status = 'Issued';
            $date_field = "date_issued = NOW()";
        }

        //  Update request status
        $db->query("UPDATE requests SET status = '{$new_status}', {$date_field} WHERE id = '{$request_id}'");

        //  Update request status
        $db->query("UPDATE requests SET status = '{$new_status}', {$date_field} WHERE id = '{$request_id}'");

        $db->query("COMMIT");
        echo json_encode(['success' => true, 'message' => "Request marked as {$new_status} and stock updated."]);

    } catch (Exception $e) {
        $db->query("ROLLBACK");
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $e->getMessage()]);
    }

    exit;
}


// Simple fix: Use a custom query to get requests with office and division names
$sql = "
    SELECT 
        r.*,
        COALESCE(u.name, CONCAT(e.first_name, ' ', e.last_name)) as req_by,
        COALESCE(u.position, e.position) as position,
        COALESCE(ud.division_name, ed.division_name, u.division, e.division) as division,
        COALESCE(uo.office_name, eo.office_name, u.office, e.office) as office,
        COALESCE(u.image, e.image, 'no_image.png') as image
    FROM requests r
    LEFT JOIN users u ON r.requested_by = u.id
    LEFT JOIN employees e ON r.requested_by = e.id
    -- Join with divisions table for users
    LEFT JOIN divisions ud ON u.division = ud.id
    -- Join with divisions table for employees  
    LEFT JOIN divisions ed ON e.division = ed.id
    -- Join with offices table for users
    LEFT JOIN offices uo ON u.office= uo.id
    -- Join with offices table for employees
    LEFT JOIN offices eo ON e.office = eo.id
    WHERE r.status != 'archived'
    ORDER BY r.date DESC
";

$requests = find_by_sql($sql);
?>

<?php include_once('layouts/header.php'); 
$msg = $session->msg(); // get the flashed message

if (!empty($msg) && is_array($msg)): 
    $type = key($msg);        // "danger", "success", etc.
    $text = $msg[$type];      // The message itself
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      icon: '<?php echo $type === "danger" ? "error" : $type; ?>',
      title: '<?php echo ucfirst($type); ?>',
      text: '<?php echo addslashes($text); ?>',
      confirmButtonText: 'OK',
      background: '#ffffff',
      backdrop: 'rgba(0,0,0,0.4)'
    });
  });
</script>
<?php endif; ?>

<style>
:root {
    --primary-green: #1e7e34;
    --secondary-green: #28a745;
    --light-green: #d4edda;
    --dark-green: #155724;
    --accent-green: #34ce57;
    --border-color: #c3e6cb;
    --light-bg: #f8fff9;
    --card-shadow: 0 10px 40px rgba(30, 126, 52, 0.15);
    --hover-shadow: 0 15px 50px rgba(30, 126, 52, 0.25);
    --gradient-primary: linear-gradient(135deg, #1e7e34 0%, #28a745 100%);
    --gradient-secondary: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
}

body {
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 50%, #d4edda 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.card-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 30px 20px;
}

.card-custom {
    border: none;
    border-radius: 20px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-bottom: 2rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}


.card-header-custom {
    background: var(--gradient-primary);
    color: white;
    border-radius: 20px 20px 0 0 !important;
    padding: 2rem 2.5rem;
    border-bottom: none;
    position: relative;
    overflow: hidden;
}

.card-header-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.card-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-title i {
    font-size: 1.5rem;
    background: rgba(255,255,255,0.2);
    padding: 12px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
}

.stats-counter {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.8rem 1.5rem;
    border-radius: 15px;
    font-weight: 700;
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(10px);
    font-size: 1.1rem;
}

.table-custom {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);
    margin: 0;
    border: 1px solid var(--border-color);
    background: white;
    width: 100%;
}

.table-custom thead th {
    
    border: none;
    padding: 1.5rem 1rem;
    font-weight: 700;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    position: relative;
}

.table-custom tbody td {
    padding: 1.5rem 1rem;
    vertical-align: middle;
    border-color: var(--light-green);
    font-weight: 500;
}

.table-custom tbody tr {
    transition: all 0.3s ease;
}

.table-custom tbody tr:hover {
    background: linear-gradient(90deg, var(--light-bg) 0%, rgba(212, 237, 218, 0.3) 100%);
}

.btn-action {
    padding: 0.6rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    text-decoration: none;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    text-decoration: none;
}

.btn-view {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.btn-view:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #212529;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e0a800, #c69500);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333, #a71e2a);
}

.profile-img {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid var(--light-green);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border: 1px solid transparent;
}

.badge-pending {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border-color: #ffeaa7;
}

.badge-approved {
    background: linear-gradient(135deg, var(--light-green), #c3e6cb);
    color: var(--dark-green);
    border-color: var(--border-color);
}

.badge-rejected {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border-color: #f5c6cb;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--secondary);
    background: linear-gradient(135deg, #f8fff9, #e8f5e9);
    border-radius: 20px;
    margin: 2rem;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    color: var(--light-green);
    opacity: 0.8;
}

.empty-state h5 {
    margin-bottom: 1rem;
    color: var(--primary-green);
    font-weight: 700;
    font-size: 1.3rem;
}

.empty-state p {
    margin-bottom: 2rem;
    color: #6c757d;
    font-size: 1.1rem;
    line-height: 1.6;
}

/* Remove horizontal scrollbar */
.table-responsive {
    overflow-x: visible;
}

/* Animation for table rows */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.table-custom tbody tr {
    animation: fadeInUp 0.4s ease-out;
    animation-fill-mode: both;
}

.table-custom tbody tr:nth-child(1) { animation-delay: 0.1s; }
.table-custom tbody tr:nth-child(2) { animation-delay: 0.2s; }
.table-custom tbody tr:nth-child(3) { animation-delay: 0.3s; }
.table-custom tbody tr:nth-child(4) { animation-delay: 0.4s; }
.table-custom tbody tr:nth-child(5) { animation-delay: 0.5s; }

/* Responsive Design */
@media (max-width: 768px) {
    .card-container {
        padding: 15px 10px;
    }
    
    .table-custom thead th,
    .table-custom tbody td {
        padding: 1rem 0.5rem;
        font-size: 0.85rem;
    }
    
    .btn-action {
        padding: 0.5rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .profile-img {
        width: 40px;
        height: 40px;
    }
    
    .card-header-custom {
        padding: 1.5rem 1.5rem;
    }
    
    .card-title {
        font-size: 1.4rem;
    }
    
    .stats-counter {
        font-size: 1rem;
        padding: 0.6rem 1.2rem;
    }
}

/* Floating action button for new request */
.floating-action {
    position: fixed;
    bottom: 60px;
    right: 30px;
    z-index: 1000;
}

.floating-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--gradient-primary);
    color: white;
    border: none;
    box-shadow: 0 8px 30px rgba(30, 126, 52, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    text-decoration: none;
}

.floating-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 40px rgba(30, 126, 52, 0.6);
    color: white;
}
</style>

<div class="card-container">
    <!-- Stock Requests Card -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-clipboard-list"></i> Stock Requests Management
            </h5>
            <div class="stats-counter">
                <i class="fas fa-chart-line me-2"></i>Total: <?php echo count($requests); ?> requests
            </div>
        </div>
        
        <div class="card-body p-4">
            <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table id="reqTable" class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="text-center">RIS NO</th>
                                <th class="text-center">Profile</th>
                                <th>Requested By</th>
                                <th>Division</th>
                                <th>Office</th>
                                <th class="text-center">Date</th>
                                <th>Remarks</th>               
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td class="text-center fw-bold" style="color: var(--primary-green);">
                                        <?php echo remove_junk($req['ris_no']); ?>
                                    </td>
                                    <td class="text-center">
                                        <img src="uploads/users/<?php echo !empty($req['image']) ? $req['image'] : 'no_image.png'; ?>" 
                                             alt="Profile" class="profile-img">
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong class="text-dark"><?php echo remove_junk($req['req_by']); ?></strong>
                                            <small class="text-muted"><?php echo remove_junk($req['position']); ?></small>
                                        </div>
                                    </td>
                                    <td class="fw-medium text-dark"><?php echo remove_junk($req['division']); ?></td>
                                    <td class="fw-medium text-dark"><?php echo remove_junk($req['office']); ?></td>
                                    
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark rounded-pill px-3 py-2 border">
                                            <i class="far fa-calendar-alt me-2"></i><?php echo date("M d, Y", strtotime($req['date'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted fst-italic">
                                            <?php echo !empty($req['remarks']) ? htmlspecialchars($req['remarks']) : 'No remarks'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <?php if (strtolower($req['status']) == 'approved'): ?>
                                                <!-- Approved → Show Issue button -->
                                                <a href="?issued=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-warning issue-btn" 
                                                    title="Mark as Issued">
                                                    <i class="fa-solid fa-box-open"></i> Issue
                                                </a>

                                            <?php elseif (strtolower($req['status']) == 'issued'): ?>
                                                <!-- For Confirmation → Show loader text -->
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2">
                                                    <i class="fa-solid fa-spinner fa-spin"></i> For Confirmation
                                                </span>

                                            <?php else: ?>
                                                <!-- Default View + Archive -->
                                                <a href="r_view.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-view" 
                                                    title="View Request Details">
                                                    <i class="fas fa-eye"></i> 
                                                </a>
                                                <a href="a_script.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-danger archive-btn" 
                                                    title="Archive Request">
                                                    <i class="fa-solid fa-file-zipper"></i> 
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>No Requests Found</h5>
                    <p>There are currently no stock requests in the system. Start by creating a new request.</p>
                    <a href="checkout.php" class="btn btn-success btn-lg px-4 py-3 rounded-pill shadow">
                        <i class="fas fa-plus-circle me-2"></i>Create New Request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<div class="floating-action">
    <a href="checkout.php" class="floating-btn" title="Create New Request">
        <i class="fas fa-plus"></i>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.issue-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Issue Items?',
                text: "This will mark the request as 'For Confirmation' and notify the requester.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel',
                background: '#ffffff',
                backdrop: 'rgba(0,0,0,0.4)'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we update the stock and status.',
                        allowOutsideClick: false,
                        background: '#ffffff',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Issued!',
                                    text: data.message,
                                    confirmButtonText: 'OK',
                                    background: '#ffffff'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message,
                                    background: '#ffffff'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An unexpected error occurred.',
                                background: '#ffffff'
                            });
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Archive confirmation
    document.querySelectorAll('.archive-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Archive Request?',
                text: "This request will be moved to archives and cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it!',
                cancelButtonText: 'Cancel',
                background: '#ffffff',
                backdrop: 'rgba(0,0,0,0.4)'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    // Initialize DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#reqTable').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            searching: true,
            autoWidth: false,
            responsive: true,
            language: {
                 search: "" ,
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ requests",
                paginate: {
                    previous: "‹ Previous",
                    next: "Next ›"
                }
            },
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control rounded-pill border-0 shadow-sm');
                $('.dataTables_filter input').attr('placeholder', 'Search requests...');
                $('.dataTables_length select').addClass('form-select rounded-pill border-0 shadow-sm');
            }
        });
    }
});
</script>