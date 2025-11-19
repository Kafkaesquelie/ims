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

// Handle decline request
if (isset($_POST['decline_request'])) {
    $request_id = (int)$_POST['request_id'];
    $decline_reason = $db->escape($_POST['decline_reason']);
    
    if (empty($decline_reason)) {
        $session->msg("d", "Please provide a reason for declining the request.");
        redirect('requests.php');
    }
    
    // Start transaction
    $db->query("START TRANSACTION");
    
    try {
        // Update request status to Declined and add reason to remarks
        $update_sql = "UPDATE requests SET status = 'Declined', remarks = '{$decline_reason}', date_completed = NOW() WHERE id = '{$request_id}'";
        
        if (!$db->query($update_sql)) {
            throw new Exception("Failed to update request status");
        }
        
        // Commit transaction
        $db->query("COMMIT");
        $session->msg("s", "Request has been declined successfully.");
    } catch (Exception $e) {
        // Rollback transaction if any query failed
        $db->query("ROLLBACK");
        $session->msg("d", "Failed to decline request: " . $e->getMessage());
    }
    
    redirect('all_requests.php');
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

        // ✅ MODIFIED: Check if current user is admin (level 1)
        $current_user_is_admin = ($current_user['user_level'] == 1);

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

        // ✅ MODIFIED: Determine new status - Admin and Employees are automatically completed, regular users stay as Issued
        if ($current_user_is_admin || $is_employee) {
            // Admin or Employee requests: mark as Completed immediately
            $new_status = 'Completed';
            $date_field = "date_completed = NOW()";
        } else {
            // Regular user requests: mark as Issued (for confirmation)
            $new_status = 'Issued';
            $date_field = "date_issued = NOW()";
        }

        // Update request status
        $db->query("UPDATE requests SET status = '{$new_status}', {$date_field} WHERE id = '{$request_id}'");

        $db->query("COMMIT");
        echo json_encode(['success' => true, 'message' => "Request marked as {$new_status} and stock updated."]);

    } catch (Exception $e) {
        $db->query("ROLLBACK");
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $e->getMessage()]);
    }

    exit;
}

// ✅ MODIFIED: Show all active requests including canceled ones
// Exclude completed and archived requests
$sql = "
    SELECT 
        r.*,
        COALESCE(u.name, CONCAT(e.first_name, ' ', e.last_name)) as req_by,
        COALESCE(u.position, e.position) as position,
        COALESCE(ud.division_name, ed.division_name, u.division, e.division) as division,
        COALESCE(uo.office_name, eo.office_name, u.office, e.office) as office,
        COALESCE(u.image, e.image, 'no_image.png') as image,
        TIMESTAMPDIFF(MINUTE, r.date, NOW()) as minutes_old
    FROM requests r
    LEFT JOIN users u ON r.requested_by = u.id
    LEFT JOIN employees e ON r.requested_by = e.id
    -- Join with divisions table for users
    LEFT JOIN divisions ud ON u.division = ud.id
    -- Join with divisions table for employees  
    LEFT JOIN divisions ed ON e.division = ed.id
    -- Join with offices table for users
    LEFT JOIN offices uo ON u.office = uo.id
    -- Join with offices table for employees
    LEFT JOIN offices eo ON e.office = eo.id
    WHERE r.status != 'archived' 
    AND r.status != 'completed'  -- ✅ ADDED: Exclude completed requests
    AND LOWER(r.status) != 'completed'  -- ✅ ADDED: Case-insensitive check
    AND r.status != 'Declined'  -- ✅ ADDED: Exclude declined requests
    ORDER BY 
        CASE 
            WHEN r.status = 'Canceled' OR r.status = 'Cancelled' THEN 2  -- Show canceled requests at the bottom
            ELSE 1 
        END,
        r.date DESC
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
    --canceled-color: #6c757d;
    --canceled-bg: #f8f9fa;
    --expiring-color: #dc3545;
    --declined-color: #dc3545;
    --declined-bg: #f8d7da;
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

/* Canceled row styling */
.table-custom tbody tr.canceled-row {
    background-color: var(--canceled-bg);
    opacity: 0.8;
    position: relative;
}

.table-custom tbody tr.canceled-row:hover {
    background: linear-gradient(90deg, #e9ecef 0%, #dee2e6 100%);
}

.table-custom tbody tr.canceled-row.expiring-soon {
    background: linear-gradient(90deg, #fff5f5 0%, #fed7d7 100%);
    border-left: 4px solid var(--expiring-color);
}

.table-custom tbody tr.canceled-row.expired {
    display: none; /* Hide expired canceled requests */
}

.table-custom tbody tr.canceled-row td {
    color: var(--canceled-color);
    border-color: #dee2e6;
}

.expiry-timer {
    position: absolute;
    top: 5px;
    right: 10px;
    background: var(--expiring-color);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
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

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #5a6268, #495057);
}

.btn-decline {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.btn-decline:hover {
    background: linear-gradient(135deg, #c82333, #a71e2a);
    color: white;
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

.badge-canceled {
    background: linear-gradient(135deg, #e9ecef, #dee2e6);
    color: #495057;
    border-color: #ced4da;
}

.badge-issued {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
    border-color: #bee5eb;
}

.badge-declined {
    background: linear-gradient(135deg, var(--declined-bg), #f5c6cb);
    color: var(--declined-color);
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
    
    .expiry-timer {
        position: relative;
        top: 0;
        right: 0;
        margin-bottom: 5px;
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
.table th {
        background: #005113ff;
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        text-align: center;
    }

/* Modal Styles */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 15px 15px 0 0;
    border: none;
    padding: 1.5rem;
}

.modal-header .modal-title {
    font-weight: 700;
    font-size: 1.3rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border: none;
    padding: 1.5rem 2rem;
    border-radius: 0 0 15px 15px;
}

.reason-textarea {
    width: 100%;
    min-height: 120px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    font-size: 0.95rem;
    resize: vertical;
    transition: all 0.3s ease;
}

.reason-textarea:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.reason-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
}

.btn-cancel:hover {
    background: #5a6268;
    color: white;
}

.btn-confirm-decline {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border: none;
}

.btn-confirm-decline:hover {
    background: linear-gradient(135deg, #c82333, #a71e2a);
    color: white;
}
</style>

<div class="card-container">
    <!-- Stock Requests Card -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-clipboard-list"></i> Active Stock Requests
            </h5>
            <div class="stats-counter">
                <i class="fas fa-chart-line me-2"></i>Active: <span id="activeCount"><?php echo count($requests); ?></span> requests
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
                                <th>Office</th>
                                <th class="text-center">Date</th>
                                <th>Status</th>
                                <th>Remarks</th>               
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <?php 
                                $status = strtolower($req['status']);
                                $isCanceled = ($status === 'canceled' || $status === 'cancelled');
                                $minutesOld = isset($req['minutes_old']) ? (int)$req['minutes_old'] : 0;
                                $isExpiringSoon = $isCanceled && $minutesOld >= 25; // Show warning at 25 minutes
                                $isExpired = $isCanceled && $minutesOld >= 30; // Hide after 30 minutes
                                $timeLeft = 30 - $minutesOld;
                                ?>
                                <tr class="<?php echo $isCanceled ? 'canceled-row' : ''; echo $isExpiringSoon ? ' expiring-soon' : ''; echo $isExpired ? ' expired' : ''; ?>" 
                                    data-canceled="<?php echo $isCanceled ? 'true' : 'false'; ?>"
                                    data-minutes-old="<?php echo $minutesOld; ?>">
                                    <?php if ($isCanceled && $isExpiringSoon && !$isExpired): ?>
                                        <div class="expiry-timer">
                                            <i class="fas fa-clock me-1"></i><?php echo max(0, $timeLeft); ?>m
                                        </div>
                                    <?php endif; ?>
                                    
                                    <td class="text-center fw-bold" style="color: <?php echo $isCanceled ? 'var(--canceled-color)' : 'var(--primary-green)'; ?>;">
                                        <?php echo remove_junk($req['ris_no']); ?>
                                    </td>
                                    <td class="text-center">
                                        <img src="uploads/users/<?php echo !empty($req['image']) ? $req['image'] : 'no_image.png'; ?>" 
                                             alt="Profile" class="profile-img" style="<?php echo $isCanceled ? 'opacity: 0.7;' : ''; ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong class="<?php echo $isCanceled ? 'text-muted' : 'text-dark'; ?>">
                                                <?php echo remove_junk($req['req_by']); ?>
                                            </strong>
                                            <small class="text-muted"><?php echo remove_junk($req['position']); ?></small>
                                        </div>
                                    </td>
                                    <td class="fw-medium <?php echo $isCanceled ? 'text-muted' : 'text-dark'; ?>">
                                        <?php echo remove_junk($req['office']); ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-2 border <?php echo $isCanceled ? 'bg-light text-muted' : 'bg-light text-dark'; ?>">
                                            <i class="far fa-calendar-alt me-2"></i><?php echo date("M d, Y", strtotime($req['date'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            $badgeClass = 'badge-secondary';
                                            
                                            switch($status) {
                                                case 'pending':
                                                    $badgeClass = 'badge-pending';
                                                    break;
                                                case 'approved':
                                                    $badgeClass = 'badge-approved';
                                                    break;
                                                case 'issued':
                                                    $badgeClass = 'badge-issued';
                                                    break;
                                                case 'canceled':
                                                case 'cancelled':
                                                    $badgeClass = 'badge-canceled';
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
                                                    case 'pending': echo 'fa-clock'; break;
                                                    case 'approved': echo 'fa-thumbs-up'; break;
                                                    case 'issued': echo 'fa-box'; break;
                                                    case 'canceled':
                                                    case 'cancelled': echo 'fa-ban'; break;
                                                    case 'declined': echo 'fa-times-circle'; break;
                                                    default: echo 'fa-info-circle';
                                                }
                                                ?> me-1">
                                            </i>
                                            <?php echo ucfirst($req['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?php echo $isCanceled ? 'text-muted fst-italic' : 'text-muted fst-italic'; ?>">
                                            <?php echo !empty($req['remarks']) ? htmlspecialchars($req['remarks']) : 'No remarks'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <?php if ($isCanceled): ?>
                                                <!-- Canceled requests - View only -->
                                                <a href="r_view.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-secondary" 
                                                    title="View Canceled Request">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php elseif (strtolower($req['status']) == 'approved'): ?>
                                                <!-- Approved → Show Issue button -->
                                                <a href="?issued=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-warning issue-btn" 
                                                    title="Mark as Issued">
                                                    <i class="fa-solid fa-box-open"></i>
                                                </a>
                                                <a href="r_view.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-view" 
                                                    title="View Request Details">
                                                    <i class="fas fa-eye"></i> 
                                                </a>
                                            <?php elseif (strtolower($req['status']) == 'issued'): ?>
                                                <!-- For Confirmation → Show loader text -->
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2">
                                                    <i class="fa-solid fa-spinner fa-spin"></i> For Confirmation
                                                </span>
                                            <?php else: ?>
                                                <!-- Default View + Decline -->
                                                <a href="r_view.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-view" 
                                                    title="View Request Details">
                                                    <i class="fas fa-eye"></i> 
                                                </a>
                                                <button type="button" 
                                                    class="btn-action btn-decline decline-btn" 
                                                    title="Decline Request"
                                                    data-request-id="<?php echo (int)$req['id']; ?>"
                                                    data-request-no="<?php echo remove_junk($req['ris_no']); ?>">
                                                    <i class="fa-solid fa-thumbs-down"></i>
                                                </button>
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
                    <h5>No Active Requests</h5>
                    <p>There are currently no active stock requests in the system. Start by creating a new request.</p>
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

<!-- Decline Request Modal -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="declineModalLabel">
                    <i class="fas fa-thumbs-down me-2"></i>Decline Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p class="mb-3">You are about to decline request: <strong id="declineRequestNo"></strong></p>
                    <p class="text-muted small mb-3">Please provide a reason for declining this request. This will be visible to the requester.</p>
                    
                    <div class="mb-3">
                        <label for="decline_reason" class="reason-label">Reason for Declining</label>
                        <textarea class="reason-textarea" id="decline_reason" name="decline_reason" 
                                  placeholder="Enter the reason for declining this request..." required></textarea>
                    </div>
                    
                    <input type="hidden" name="request_id" id="declineRequestId">
                    <input type="hidden" name="decline_request" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-confirm-decline">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update expired canceled requests
    function updateExpiredRequests() {
        const canceledRows = document.querySelectorAll('tr.canceled-row');
        let activeCount = 0;
        
        canceledRows.forEach(row => {
            const minutesOld = parseInt(row.getAttribute('data-minutes-old')) || 0;
            
            if (minutesOld >= 30) {
                // Mark as expired and hide
                row.classList.add('expired');
                row.style.display = 'none';
            } else {
                // Count as active if not expired
                activeCount++;
            }
        });
        
        // Count non-canceled rows
        const nonCanceledRows = document.querySelectorAll('tr:not(.canceled-row)');
        activeCount += nonCanceledRows.length;
        
        // Update active count
        document.getElementById('activeCount').textContent = activeCount;
    }
    
    // Initial update
    updateExpiredRequests();
    
    // Update every minute
    setInterval(updateExpiredRequests, 60000); // 1 minute

    // Decline button functionality
    document.querySelectorAll('.decline-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const requestId = this.getAttribute('data-request-id');
            const requestNo = this.getAttribute('data-request-no');
            
            // Set the request details in the modal
            document.getElementById('declineRequestId').value = requestId;
            document.getElementById('declineRequestNo').textContent = requestNo;
            
            // Show the modal
            const declineModal = new bootstrap.Modal(document.getElementById('declineModal'));
            declineModal.show();
        });
    });

    // Issue button functionality
    document.querySelectorAll('.issue-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');

            // ✅ MODIFIED: Check if current user is admin (user_level = 1)
            const isAdmin = <?php echo ($current_user['user_level'] == 1) ? 'true' : 'false'; ?>;
            
            let confirmText, successText;
            
            if (isAdmin) {
                confirmText = "This will mark the request as 'Completed' immediately and update stock.";
                successText = "completed";
            } else {
                confirmText = "This will mark the request as 'For Confirmation' and notify the requester.";
                successText = "issued for confirmation";
            }

            Swal.fire({
                title: 'Issue Items?',
                text: confirmText,
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
                                    title: 'Success!',
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

    // Auto-refresh the page every 10 minutes for data consistency
    setInterval(function() {
        location.reload();
    }, 600000); // 10 minutes
});
</script>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
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
                search: "",
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