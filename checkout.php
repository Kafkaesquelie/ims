<?php
$page_title = 'Request Form';
require_once('includes/load.php');
page_require_level(1);

// Fetch all available items
$all_items = find_by_sql("
    SELECT 
        i.*, 
        c.name AS cat_name,
        u.name AS main_unit_name,
        bu.name AS base_unit_name,
        COALESCE(uc.conversion_rate, 1) AS conversion_rate,
        uc.from_unit_id,
        uc.to_unit_id
    FROM items i
    LEFT JOIN categories c ON i.categorie_id = c.id
    LEFT JOIN units u ON i.unit_id = u.id
    LEFT JOIN base_units bu ON i.base_unit_id = bu.id
    LEFT JOIN unit_conversions uc ON i.id = uc.item_id
    WHERE i.archived = 0
");

// ✅ Get helper functions
function get_unit_name($unit_id)
{
    global $db;
    $res = $db->query("SELECT name FROM units WHERE id = '{$unit_id}' LIMIT 1");
    return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res)['name'] : '';
}

// ✅ Get base unit name from base_units table
function get_base_unit_name($base_unit_id)
{
    global $db;
    $res = $db->query("SELECT name FROM base_units WHERE id = '{$base_unit_id}' LIMIT 1");
    return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res)['name'] : 'Unit';
}

// Function to get expiry status
function get_expiry_status($expiry_date) {
    if (empty($expiry_date) || $expiry_date == '0000-00-00') {
        return array('status' => 'no_expiry', 'badge' => '', 'class' => '', 'days' => null);
    }
    
    $today = new DateTime();
    $expiry = new DateTime($expiry_date);
    $days_until_expiry = $today->diff($expiry)->days;
    
    // Check if expired
    if ($expiry < $today) {
        return array(
            'status' => 'expired', 
            'badge' => 'Expired', 
            'class' => 'badge-danger',
            'days' => $days_until_expiry
        );
    }
    
    // Check if expiring in 15 days
    if ($days_until_expiry <= 15) {
        return array(
            'status' => 'expiring_15', 
            'badge' => 'Expiring in ' . $days_until_expiry . ' days', 
            'class' => 'badge-warning',
            'days' => $days_until_expiry
        );
    }
    
    // Check if expiring in 30 days
    if ($days_until_expiry <= 30) {
        return array(
            'status' => 'expiring_30', 
            'badge' => 'Expiring in ' . $days_until_expiry . ' days', 
            'class' => 'badge-info',
            'days' => $days_until_expiry
        );
    }
    
    // Valid expiry date (more than 30 days)
    return array(
        'status' => 'valid', 
        'badge' => 'Expires: ' . date('M d, Y', strtotime($expiry_date)), 
        'class' => 'badge-success',
        'days' => $days_until_expiry
    );
}

// get logged-in user
$current_user = current_user();
$current_user_id = $current_user['id'] ?? null;
$current_user_name = $current_user['name'] ?? $current_user['username'] ?? '';

// Get all active employees (including those with user accounts)
function get_all_employees()
{
    global $db;
    $sql = "SELECT * FROM employees WHERE status = 1 ORDER BY last_name ASC, first_name ASC";
    $result = $db->query($sql);
    $employees = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $full_name = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
            $employees[] = [
                'id' => $row['id'],
                'full_name' => $full_name,
                'position' => $row['position'] ?? '',
                'employees_id' => $row['employees_id'] ?? '', // Use employees_id from employees table
                'user_id' => $row['user_id'] ?? null
            ];
        }
    }
    return $employees;
}

// Get requestors - only from employees table
function get_requestors()
{
    global $current_user_id;
    $requestors = [];
    $employees = get_all_employees();
    
    foreach ($employees as $employee) {
        // Skip admin users (you might need to adjust this condition based on your admin identification)
        $is_admin = false; // Add your admin check logic here if needed
        
        if (!$is_admin) {
            $requestors[] = [
                'source' => 'employees',
                'id' => $employee['id'],
                'full_name' => $employee['full_name'],
                'position' => $employee['position'] ?? '',
                'employees_id' => $employee['employees_id'] ?? '',
                'user_id' => $employee['user_id']
            ];
        }
    }
    return $requestors;
}

// Build list used in the select
$requestors = get_requestors();

// Find current user's employee record to set as default
$default_selected = 'employees_0'; // Default to first employee if current user not found
foreach ($requestors as $req) {
    if ($req['user_id'] == $current_user_id) {
        $default_selected = 'employees_' . $req['id'];
        break;
    }
}

// If no match found and there are requestors, use the first one
if ($default_selected === 'employees_0' && !empty($requestors)) {
    $default_selected = 'employees_' . $requestors[0]['id'];
}

// Get current year
$current_year = date("Y");
// Fixed middle part as requested
$fixed_middle = '0112';

// ---------- Form submission handling ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $db;
    $selected = $_POST['requestor'] ?? '';
    $parts = explode('_', $selected, 2);
    $source = $parts[0] ?? 'employees'; // Now only employees
    $rid = isset($parts[1]) ? (int)$parts[1] : 0;

    $requestor_row = find_by_id('employees', $rid);
    $requestor_name = $requestor_row['first_name'] . ' ' . $requestor_row['last_name'] ?? 'Unknown';
    $remarks = remove_junk($db->escape($_POST['remarks'] ?? ''));
    $qtys = array_filter($_POST['qty'] ?? [], fn($q) => (int)$q > 0);

    if (empty($qtys)) {
        $session->msg("d", "❌ No items selected.");
        redirect('checkout.php', false);
    }

    // Check duplicate pending requests (only for same employee and same item)
    foreach ($qtys as $item_id => $qty) {
        $check = $db->query("SELECT r.id 
                             FROM requests r 
                             JOIN request_items ri ON r.id = ri.req_id
                             WHERE r.requested_by = '{$rid}' 
                               AND ri.item_id = '{$item_id}' 
                               AND r.status = 'Pending' LIMIT 1");
        if ($db->num_rows($check) > 0) {
            $item = find_by_id('items', $item_id);
            $session->msg("d", "❌ You already have a pending request for item: {$item['name']}");
            redirect('checkout.php', false);
        }
    }

    $db->query("START TRANSACTION");

    // Build RIS number with fixed format: year-0112-employees_id
    $year_part = $current_year;
    $middle_part = $fixed_middle; // Fixed as 0112
    
    // Get employees_id from requestor
    $employee_part = '0000'; // Default
    $employee = find_by_id('employees', $rid);
    if ($employee && !empty($employee['employees_id'])) {
        $employee_part = $employee['employees_id'];
    }

    // Build complete RIS number
    $ris_no = $year_part . '-' . $middle_part . '-' . $employee_part;

    $query_request = "INSERT INTO requests (requested_by, date, status, ris_no)
                      VALUES ('{$rid}', NOW(), 'Pending', '{$ris_no}')";
    if (!$db->query($query_request)) {
        $db->query("ROLLBACK");
        $session->msg("d", "❌ Failed to create request.");
        redirect('checkout.php', false);
    }

    $req_id = $db->insert_id();
    $all_ok = true;

    // Handle each item
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty = (float)$qty;

        $item = find_by_id('items', $item_id);
        if (!$item) continue;

        // Get conversion data
        $conversion = find_by_sql("SELECT conversion_rate, from_unit_id, to_unit_id 
                                   FROM unit_conversions WHERE item_id = '{$item_id}' LIMIT 1");
        $conversion_rate = $conversion ? (float)$conversion[0]['conversion_rate'] : 1;
        $from_unit_id = $conversion ? $conversion[0]['from_unit_id'] : $item['unit_id'];
        $to_unit_id = $conversion ? $conversion[0]['to_unit_id'] : $item['unit_id'];

        $unit_name = get_unit_name($item['unit_id']);
        $base_unit_name = get_base_unit_name($item['base_unit_id']);

        // Determine requested unit type
        $requested_unit_type = $_POST['unit_type'][$item_id] ?? $unit_name;
        $is_requesting_base_unit = ($requested_unit_type === $base_unit_name);

        // Calculate quantity to deduct from inventory
        if ($is_requesting_base_unit && $conversion_rate > 1) {
            // Requesting pieces but stored in boxes: convert to boxes
            $qty_to_deduct = $qty / $conversion_rate;
        } else {
            // Same unit or no conversion needed
            $qty_to_deduct = $qty;
        }

        // ✅ Check stock availability
        if ($qty_to_deduct > $item['quantity']) {
            $all_ok = false;

            // Determine which unit to display available stock in based on what was requested
            if ($is_requesting_base_unit && $conversion_rate > 1) {
                // User requested base units, so show available in both units for clarity
                $available_main = floor($item['quantity']);
                $remaining_decimal = $item['quantity'] - $available_main;
                $available_base = (int)($remaining_decimal * $conversion_rate); // Cast to int to remove decimals

                if ($available_main > 0 && $available_base > 0) {
                    $available_display = $available_main . " " . $unit_name . " | " . $available_base . " " . $base_unit_name;
                } elseif ($available_main > 0) {
                    $available_display = $available_main . " " . $unit_name;
                } else {
                    $available_display = $available_base . " " . $base_unit_name;
                }
            } else {
                // User requested main units or no conversion, show in main units
                $available_main = floor($item['quantity']);
                $remaining_decimal = $item['quantity'] - $available_main;
                $available_base = (int)($remaining_decimal * $conversion_rate);

                if ($available_main > 0 && $available_base > 0) {
                    $available_display = $available_main . " " . $unit_name . " | " . $available_base . " " . $base_unit_name;
                } elseif ($available_main > 0) {
                    $available_display = $available_main . " " . $unit_name;
                } else {
                    $available_display = $available_base . " " . $base_unit_name;
                }
            }

            $session->msg("d", "❌ Not enough stock for item: {$item['name']} (Requested {$qty} {$requested_unit_type}, Available {$available_display})");
            break;
        }

        // Compute price
        $unit_cost = (float)$item['unit_cost'];
        $price = $unit_cost * $qty_to_deduct;

        // Insert into request_items
        $query_item = "INSERT INTO request_items (req_id, item_id, qty, unit, price, remarks) 
                       VALUES ('{$req_id}', '{$item_id}', '{$qty}', '{$requested_unit_type}', '{$price}', '{$remarks}')";
        if (!$db->query($query_item)) {
            $all_ok = false;
            break;
        }

        // 🔻 Update stock
        $db->query("UPDATE items SET quantity = quantity - {$qty_to_deduct} WHERE id = '{$item_id}'");

        // Update yearly stock
        $school_year = find_by_sql("SELECT id FROM school_years WHERE is_current = 1 LIMIT 1");
        $school_year_id = $school_year ? $school_year[0]['id'] : 0;
        $check_stock = $db->query("SELECT id FROM item_stocks_per_year 
                                   WHERE item_id = '{$item_id}' AND school_year_id = '{$school_year_id}' LIMIT 1");
        if ($db->num_rows($check_stock) > 0) {
            $db->query("UPDATE item_stocks_per_year 
                        SET stock = stock - {$qty_to_deduct}, updated_at = NOW()
                        WHERE item_id = '{$item_id}' AND school_year_id = '{$school_year_id}'");
        } else {
            $db->query("INSERT INTO item_stocks_per_year (item_id, school_year_id, stock, updated_at)
                        VALUES ('{$item_id}', '{$school_year_id}', 0, NOW())");
        }
    }

    if ($all_ok) {
        $db->query("COMMIT");
        $session->msg("s", "✅ Request successfully submitted!");
    } else {
        $db->query("ROLLBACK");
        $session->msg("d", "❌ Failed to submit request.");
    }
    redirect('checkout.php', false);
}

function get_category_name($cat_id)
{
    global $db;
    $id = (int)$cat_id;
    $result = $db->query("SELECT name FROM categories WHERE id = {$id} LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}

$all_items = find_by_sql("
    SELECT 
        i.*, 
        c.name AS cat_name,
        u.name AS main_unit_name,
        bu.name AS base_unit_name,
        COALESCE(uc.conversion_rate, 1) AS conversion_rate,
        uc.from_unit_id,
        uc.to_unit_id
    FROM items i
    LEFT JOIN categories c ON i.categorie_id = c.id
    LEFT JOIN units u ON i.unit_id = u.id
    LEFT JOIN base_units bu ON i.base_unit_id = bu.id
    LEFT JOIN unit_conversions uc ON i.id = uc.item_id
    WHERE i.archived = 0
");


function calculate_display_quantity($item)
{
    $quantity = (float)$item['quantity'];

    // If no conversion or conversion rate is 1, return simple quantity
    if ($item['conversion_rate'] <= 1 || $item['main_unit_name'] === $item['base_unit_name']) {
        return number_format($quantity, 2) . " " . $item['main_unit_name'];
    }

    // Calculate full main units and remaining base units
    $full_main_units = floor($quantity);
    $remaining_main_decimal = $quantity - $full_main_units;
    $remaining_base_units = $remaining_main_decimal * $item['conversion_rate'];

    // Format the display - ensure whole numbers for main units
    if ($full_main_units > 0 && $remaining_base_units > 0) {
        return $full_main_units . " " . $item['main_unit_name'] . " | " .
            (int)$remaining_base_units . " " . $item['base_unit_name'];
    } elseif ($full_main_units > 0) {
        return $full_main_units . " " . $item['main_unit_name'];
    } else {
        return (int)$remaining_base_units . " " . $item['base_unit_name'];
    }
}

// Process items for display
foreach ($all_items as &$item) {
    $quantity = (float)$item['quantity'];
    $conversion_rate = (float)$item['conversion_rate'];

    if ($conversion_rate <= 1 || $item['main_unit_name'] === $item['base_unit_name']) {
        $item['display_quantity'] = number_format($quantity, 2) . " " . $item['main_unit_name'];
    } else {
        $full_main = floor($quantity);
        $remaining_base = ($quantity - $full_main) * $conversion_rate;
        if ($full_main > 0 && $remaining_base > 0) {
            $item['display_quantity'] = "{$full_main} {$item['main_unit_name']} | " . (int)$remaining_base . " {$item['base_unit_name']}";
        } elseif ($full_main > 0) {
            $item['display_quantity'] = "{$full_main} {$item['main_unit_name']}";
        } else {
            $item['display_quantity'] = (int)$remaining_base . " {$item['base_unit_name']}";
        }
    }

   // stock status badge
if ($quantity == 0) {
    $item['stock_badge'] = '<span class="badge bg-danger">Out of Stock</span>';
    $item['stock_status'] = 'Out of Stock';
} elseif ($quantity <= 5) {
    $item['stock_badge'] = '<span class="badge bg-warning text-dark">Low Stock</span>';
    $item['stock_status'] = 'Low Stock';
} else {
    $item['stock_badge'] = '<span class="badge bg-success">In Stock</span>';
    $item['stock_status'] = 'In Stock';
}

    // Calculate expiry status
    $item['expiry_status'] = get_expiry_status($item['expiry_date']);
}


?>

<?php include_once('layouts/header.php'); ?>

<style>
    /* Table column adjustments */
    #itemsTable th,
    #itemsTable td {
        vertical-align: middle;
    }

    .unit-column {
        width: 100px;
        text-align: center;
    }

    .qty-column {
        width: 120px;
        text-align: center;
    }

    .stock-card-column {
        width: 120px;
    }

    .expiry-column {
        width: 150px;
        text-align: center;
    }

    /* DataTables spacing fix */
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1rem !important;
        padding-top: 1rem !important;
    }

    .dataTables_wrapper .dataTables_paginate {
        margin-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .search-box {
        position: relative;
        flex: 1;
        max-width: 300px;
    }

    .search-box input {
        padding-left: 2.5rem;
        border-radius: 25px;
        border: 1px solid #dee2e6;
    }

    .search-box .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--secondary);
    }

    .search-box {
        max-width: 100%;
    }

    .green-btn {
        background-color: #1e7e34;
        border-color: #28a745;
        color: white;
        border-radius: 5px;
    }

    .green-btn:hover {
        background-color: #004a04;
        border-color: #004a04;
        color: white;
    }

    .table-success {
        background-color: #e8f5e9 !important;
    }

    .form-control:focus {
        border-color: #006205;
        box-shadow: 0 0 0 0.2rem rgba(0, 98, 5, 0.25);
    }

    .border-success {
        border-color: #006205 !important;
    }

    .text-success {
        color: #006205 !important;
    }

    .modal-header.bg-success {
        background-color: #006205 !important;
    }

    .btn-success {
        background-color: #006205;
        border-color: #006205;
    }

    .btn-success:hover {
        background-color: #004a04;
        border-color: #004a04;
    }

    .card-header {
        background-color: #197707ff;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        color: white;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 20px;
    }

    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    /* RIS number field specific styling */
    .ris-input {
        text-align: center;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .ris-separator {
        font-weight: bold;
        color: #006205;
        margin: 0 5px;
    }

    .ris-container {
        position: relative;
    }

    .ris-error-message {
        position: absolute;
        bottom: -20px;
        left: 0;
        width: 100%;
        font-size: 0.8rem;
    }

    /* Stock indicator styles */
    .stock-badge {
        font-size: 0.7em;
        padding: 0.3em 0.6em;
        margin-left: 5px;
    }

    .table-out-of-stock {
        background-color: #f8d7da !important;
    }

    .table-low-stock {
        background-color: #fff3cd !important;
    }

    .table-good-stock {
        background-color: #e8f5e9 !important;
    }

    .stock-warning {
        border-left: 4px solid #ffc107;
    }

    .stock-danger {
        border-left: 4px solid #dc3545;
    }

    .stock-success {
        border-left: 4px solid #28a745;
    }

    /* Quantity input styling based on stock */
    .qty-input:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    .stock-indicator {
        font-size: 0.8rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 5px;
    }

    .indicator-out {
        background-color: #dc3545;
        color: white;
    }

    .indicator-low {
        background-color: #ffc107;
        color: #000;
    }

    .indicator-good {
        background-color: #28a745;
        color: white;
    }

    /* RIS number display styling */
    .ris-display {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .ris-part {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        background-color: #f8f9fa;
        min-width: 80px;
        text-align: center;
    }

    .ris-part.fixed {
        background-color: #e9ecef;
        color: #6c757d;
        font-weight: bold;
    }

    /* Expiry badge styles */
    .expiry-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        border-radius: 12px;
        display: inline-block;
        font-weight: 600;
    }

    .expiry-expired {
        background: #dc3545;
        color: white;
        animation: pulse 2s infinite;
    }

    .expiry-15-days {
        background: #ffc107;
        color: var(--text-dark);
        animation: pulse 1.5s infinite;
    }

    .expiry-30-days {
        background: #17a2b8;
        color: white;
    }

    .expiry-valid {
        background: #28a745;
        color: white;
    }

    .expiry-none {
        background: #6c757d;
        color: white;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
        }
        70% {
            box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    /* Expiry timeline indicator */
    .expiry-timeline {
        font-size: 0.75rem;
        margin-top: 4px;
    }

    .expiry-date {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: 2px;
    }
</style>

<!-- Header Card -->
<div class="card green-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-sm-9">
                <h5 class="mb-0"><i class="nav-icon fa-solid fa-pen-to-square"></i> Request Form</h5>
            </div>
            <div class=" d-flex justify-content-end">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control" placeholder="Search items..." id="searchInput">
                </div>
            </div>
        </div>
    </div>
</div>

<form id="requestForm" method="post" action="">
    <!-- Hidden fields for RIS number parts -->
    <input type="hidden" name="ris_year" id="risYear" value="<?= $current_year ?>">
    <input type="hidden" name="ris_employee" id="risEmployee" value="">

    <!-- Requestor Information Card -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user me-2"></i> Requestor Information
        </div>
        <div class="card-body">
            <!-- Labels Row -->
            <div class="row mb-1 text-muted fw-bold">
                <div class="col-md-4">Requestor's Name:</div>
                <div class="col-md-4">Position / Office:</div>
                <div class="col-md-4">RIS No:</div>
            </div>

            <!-- Fields Row -->
            <div class="row mb-3 align-items-center">
                <!-- Requestor Dropdown -->
                <div class="col-md-4">
                    <select name="requestor" id="requestorSelect"
                        class="form-control text-success border-success"
                        style="border-radius:5px;"
                        required>
                        <option value=""> Select Requestor </option>
                        <?php foreach ($requestors as $req):
                            $val = $req['source'] . '_' . $req['id'];
                            $sel = ($val === $default_selected) ? 'selected' : '';
                        ?>
                            <option value="<?= $val; ?>" <?= $sel; ?> 
                                data-employees-id="<?= $req['employees_id'] ?>" 
                                data-source="<?= $req['source'] ?>">
                                <?= htmlspecialchars($req['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Position -->
                <div class="col-md-4">
                    <input type="text" id="positionField"
                        class="form-control text-success border-success"
                        value=""
                        style="background:transparent;" readonly>
                </div>

                <!-- RIS No -->
                <div class="col-md-4">
                    <div class="ris-container position-relative">
                        <div class="ris-display">
                            <!-- Year Part (Fixed) -->
                            <div class="ris-part fixed">
                                <?= $current_year ?>
                            </div>
                            <span class="ris-separator">-</span>

                            <!-- Middle Part (Fixed as 0112) -->
                            <div class="ris-part fixed">
                                0112
                            </div>
                            <span class="ris-separator">-</span>

                            <!-- Employee Part (Auto-filled) -->
                            <div class="ris-part" id="risEmployeeDisplay">
                                0000
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Items Card -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-boxes me-2"></i> Available Items
            <span class="float-end">
                <small class="text-muted">
                    <span class="badge bg-success">✓ In Stock</span>
                    <span class="badge bg-warning text-dark">⚠ Low Stock</span>
                    <span class="badge bg-danger">✗ Out of Stock</span>
                </small>
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive mb-3">
                <?php if (!empty($all_items)): ?>
                    <table class="table table-striped table-hover align-middle" id="itemsTable">
                        <thead class="table-success">
                            <tr>
                                <th>Stock Card</th>
                                <th>Item Name</th>
                                <th class="text-center">Available Qty</th>
                                <th class="text-center">Expiry Status</th>
                                <th class="text-center">Request Unit</th>
                                <th class="text-center">Request Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_items as $it):
                                $row_class = '';
                                $indicator_class = '';
                                $indicator_text = '';

                                switch ($it['stock_status']) {
                                    case 'out-of-stock':
                                        $row_class = 'table-out-of-stock stock-danger';
                                        $indicator_class = 'indicator-out';
                                        $indicator_text = 'Out of Stock';
                                        break;
                                    case 'low-stock':
                                        $row_class = 'table-low-stock stock-warning';
                                        $indicator_class = 'indicator-low';
                                        $indicator_text = 'Low Stock';
                                        break;
                                    default:
                                        $row_class = 'table-good-stock stock-success';
                                        $indicator_class = 'indicator-good';
                                        $indicator_text = 'In Stock';
                                }

                                // Expiry status
                                $expiry_status = $it['expiry_status'];
                            ?>
                                <tr class="<?= $row_class ?>">
                                    <td>
                                        <?= htmlspecialchars($it['stock_card']); ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($it['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($it['cat_name']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= $it['display_quantity']; ?></strong>
                                    </td>
                                    <td class="text-center expiry-column">
                                        <?php if ($expiry_status['status'] !== 'no_expiry'): ?>
                                            <span class="expiry-badge <?= 
                                                $expiry_status['status'] === 'expired' ? 'expiry-expired' : 
                                                ($expiry_status['status'] === 'expiring_15' ? 'expiry-15-days' : 
                                                ($expiry_status['status'] === 'expiring_30' ? 'expiry-30-days' : 'expiry-valid')) 
                                            ?>">
                                                <?= $expiry_status['badge']; ?>
                                            </span>
                                            <?php if ($expiry_status['status'] !== 'valid'): ?>
                                                <div class="expiry-timeline">
                                                    <small class="text-muted">
                                                        <?php 
                                                        if ($expiry_status['status'] === 'expired') {
                                                            echo 'Expired ' . $expiry_status['days'] . ' days ago';
                                                        } else {
                                                            echo $expiry_status['days'] . ' days remaining';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($it['expiry_date']) && $it['expiry_date'] != '0000-00-00'): ?>
                                                <div class="expiry-date">
                                                    <small><?= date('M d, Y', strtotime($it['expiry_date'])) ?></small>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="expiry-badge expiry-none">No Expiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <select name="unit_type[<?= (int)$it['id']; ?>]"
                                            class="form-select form-select-sm p-2 w-100 unit-select"
                                            style="width: 120px;"
                                            data-itemid="<?= (int)$it['id']; ?>"
                                            data-conversion="<?= $it['conversion_rate']; ?>"
                                            data-mainunit="<?= htmlspecialchars($it['main_unit_name']); ?>"
                                            data-baseunit="<?= htmlspecialchars($it['base_unit_name']); ?>"
                                            <?= $it['quantity'] == 0 ? 'disabled' : '' ?>>
                                            <?php if ($it['conversion_rate'] > 1 && $it['main_unit_name'] !== $it['base_unit_name']): ?>
                                                <option value="<?= $it['main_unit_name']; ?>"><?= $it['main_unit_name']; ?></option>
                                                <option value="<?= $it['base_unit_name']; ?>"><?= $it['base_unit_name']; ?></option>
                                            <?php else: ?>
                                                <option value="<?= $it['main_unit_name']; ?>" selected><?= $it['main_unit_name']; ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                            name="qty[<?= (int)$it['id']; ?>]"
                                            min="0"
                                            step="1"
                                            value="0"
                                            class="form-control text-center border-success qty-input"
                                            style="max-width:120px;"
                                            <?= $it['quantity'] == 0 ? 'disabled' : '' ?>
                                            data-itemid="<?= (int)$it['id']; ?>"
                                            data-available="<?= (float)$it['quantity']; ?>"
                                            data-conversion="<?= $it['conversion_rate']; ?>"
                                            data-mainunit="<?= htmlspecialchars($it['main_unit_name']); ?>"
                                            data-baseunit="<?= htmlspecialchars($it['base_unit_name']); ?>"
                                            title="Available: <?= $it['display_quantity']; ?>">
                                        <?php if ($it['quantity'] == 0): ?>
                                            <small class="text-danger d-block mt-1">Not available</small>
                                        <?php elseif ($it['quantity'] <= 5): ?>
                                            <small class="text-warning d-block mt-1">Limited stock</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-2"></i>
                        <h5>No items available</h5>
                        <p class="text-muted mb-0">No items in the system to request.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Remarks & Submit Card -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-comment me-2"></i> Remarks & Submission
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <!-- Remarks field -->
                <div class="col-md-8">
                    <label class="fw-bold text-success">Remarks (Optional)</label>
                    <textarea class="form-control border-success" name="remarks" id="remarksField" rows="2" placeholder="Add remarks here..."></textarea>
                </div>

                <!-- Review Button -->
                <div class="col-md-2">
                    <button type="button" class="btn green-btn w-100" id="reviewBtn">
                        <i class="fa-solid fa-clipboard-check"></i> Review
                    </button>
                </div>

                <!-- Clear Button -->
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" id="clearBtn">
                        <i class="fa-solid fa-rotate-left"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal (kept outside the form) -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Request Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">Cancel</button>
                <button type="button" class="btn btn-success" id="finalSubmitBtn">Submit Request</button>
            </div>
        </div>
    </div>
</div>
<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
    // Pass PHP requestors to JS
    const requestors = <?= json_encode($requestors); ?>;
    const defaultSelected = "<?= $default_selected; ?>";
    const currentYear = "<?= $current_year; ?>";
    const fixedMiddle = "0112";

    // Initialize DataTable
    $(document).ready(function() {
        $('#itemsTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            searching: false,
            autoWidth: false,
            fixedColumns: true,
            deferRender: true,
            scrollY: 400,
            scroller: true,
            language: {
                search: "Search items:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ items",
                infoEmpty: "Showing 0 to 0 of 0 items",
                infoFiltered: "(filtered from _MAX_ total items)"
            }
        });
    });

    // Populate position field based on selected option
    function updatePositionFieldFromSelect() {
        const sel = document.getElementById('requestorSelect');
        const selectedOption = sel.options[sel.selectedIndex];
        const employeesId = selectedOption.getAttribute('data-employees-id') || '0000';
        const source = selectedOption.getAttribute('data-source') || 'employees';

        // Update employee part of RIS number
        document.getElementById('risEmployeeDisplay').textContent = employeesId.padStart(4, '0');
        document.getElementById('risEmployee').value = employeesId.padStart(4, '0');

        // Update position field
        const [sourceVal, idStr] = sel.value.split('_');
        const id = parseInt(idStr || '0', 10);
        const found = requestors.find(r => r.source === sourceVal && parseInt(r.id, 10) === id);
        document.getElementById('positionField').value = found ? (found.position || '') : '';
    }

    // Set initial position after page load / default
    document.addEventListener('DOMContentLoaded', function() {
        updatePositionFieldFromSelect();

        // Add event listeners for unit selection changes
        document.querySelectorAll('.unit-select').forEach(select => {
            select.addEventListener('change', function() {
                const itemId = this.dataset.itemid;
                const conversion = parseFloat(this.dataset.conversion) || 1;
                const mainUnit = this.dataset.mainunit;
                const baseUnit = this.dataset.baseunit;
                const qtyInput = document.querySelector(`input[name="qty[${itemId}]"]`);
                const available = parseFloat(qtyInput.dataset.available) || 0;

                // Update max value and step based on selected unit
                if (this.value === baseUnit && conversion > 1) {
                    // Requesting in base units (pieces) - max is available * conversion rate
                    const availableMain = parseFloat(available) || 0;
                    const fullMainUnits = Math.floor(availableMain);
                    const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);

                    let availableText = '';
                    if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                    } else if (fullMainUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit}`;
                    } else {
                        availableText = `${remainingBaseUnits} ${baseUnit}`;
                    }

                    qtyInput.max = Math.floor(availableMain * conversion);
                    qtyInput.step = "1"; // Whole numbers only for base units
                    qtyInput.title = `Available: ${availableText}`;

                    // Auto-adjust quantity if needed
                    const currentValue = parseInt(qtyInput.value) || 0;
                    if (currentValue > qtyInput.max) {
                        qtyInput.value = qtyInput.max;
                    }
                } else {
                    // Requesting in main units (boxes) - max is available
                    const availableMain = parseFloat(available) || 0;
                    const fullMainUnits = Math.floor(availableMain);
                    const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);

                    let availableText = '';
                    if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                    } else if (fullMainUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit}`;
                    } else {
                        availableText = `${remainingBaseUnits} ${baseUnit}`;
                    }

                    qtyInput.max = availableMain;
                    qtyInput.step = "1"; // Whole numbers only for main units
                    qtyInput.title = `Available: ${availableText}`;

                    // Auto-adjust quantity if needed
                    const currentValue = parseInt(qtyInput.value) || 0;
                    if (currentValue > qtyInput.max) {
                        qtyInput.value = qtyInput.max;
                    }
                }
            });
        });

        // Trigger change event on page load to set initial max values
        document.querySelectorAll('.unit-select').forEach(select => {
            select.dispatchEvent(new Event('change'));
        });

        // Ensure whole numbers in quantity inputs
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', function() {
                // Remove any decimal values and ensure whole numbers
                const value = parseFloat(this.value) || 0;
                if (!Number.isInteger(value)) {
                    this.value = Math.floor(value);
                }

                // Ensure value doesn't exceed max
                const max = parseFloat(this.max) || 0;
                if (value > max) {
                    this.value = max;
                }

                // Ensure value is not negative
                if (value < 0) {
                    this.value = 0;
                }
            });

            // Also handle blur event to clean up any invalid input
            input.addEventListener('blur', function() {
                const value = parseFloat(this.value) || 0;
                if (!Number.isInteger(value) || value < 0) {
                    this.value = Math.max(0, Math.floor(value));
                }
            });
        });
    });

    // On change for requestor select
    document.getElementById('requestorSelect').addEventListener('change', updatePositionFieldFromSelect);

    // Create (single) Bootstrap Modal instance and use it for show/hide
    let receiptModalEl = document.getElementById('receiptModal');
    let receiptModal = null;
    if (typeof bootstrap !== 'undefined' && receiptModalEl) {
        receiptModal = new bootstrap.Modal(receiptModalEl);
    }

    // Review button: build receipt HTML and show modal via the instance
    document.getElementById('reviewBtn').addEventListener('click', function() {
        // Build complete RIS number
        const yearPart = document.getElementById('risYear').value;
        const middlePart = fixedMiddle;
        const employeePart = document.getElementById('risEmployee').value;
        const fullRIS = yearPart + '-' + middlePart + '-' + employeePart;

        // Proceed with review
        proceedWithReview(fullRIS);
    });

    function proceedWithReview(fullRIS) {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        let receiptHTML = '<p><strong>Requestor:</strong> ' +
            document.getElementById('requestorSelect').selectedOptions[0].text +
            ' (' + document.getElementById('positionField').value + ')</p>';

        // Add RIS number to receipt
        receiptHTML += `<p><strong>RIS Number:</strong> ${fullRIS}</p>`;

        receiptHTML += '<table class="table table-bordered align-middle"><thead><tr><th>Item Name</th><th>Requested Qty</th><th>Unit</th><th>Available Stock</th><th>Stock Status</th><th>Expiry Status</th></tr></thead><tbody>';
        let hasItem = false;

        rows.forEach(row => {
            const input = row.querySelector('input.qty-input');
            const qty = parseFloat(input.value) || 0;
            if (qty <= 0) return;

            const itemId = input.dataset.itemid;
            const itemName = row.cells[1].innerText.trim();
            const available = row.cells[2].innerText.trim();
            const unitSelect = row.querySelector('select[name^="unit_type"]');
            const selectedUnit = unitSelect.selectedOptions[0].text;
            const expiryStatus = row.cells[3].innerText.trim();

            // Get stock status from the row class
            let stockStatus = 'In Stock';
            let statusClass = 'text-success';
            if (row.classList.contains('table-out-of-stock')) {
                stockStatus = 'Out of Stock';
                statusClass = 'text-danger';
            } else if (row.classList.contains('table-low-stock')) {
                stockStatus = 'Low Stock';
                statusClass = 'text-warning';
            }

            hasItem = true;

            receiptHTML += `
                <tr>
                    <td>${itemName}</td>
                    <td>${qty}</td>
                    <td>${selectedUnit}</td>
                    <td>${available}</td>
                    <td class="${statusClass}"><strong>${stockStatus}</strong></td>
                    <td>${expiryStatus}</td>
                </tr>`;
        });

        receiptHTML += '</tbody></table>';

        if (!hasItem) {
            Swal.fire({
                icon: 'warning',
                title: 'No items selected',
                text: 'Enter quantity greater than 0 for at least one item.'
            });
            return;
        }

        const remarks = document.querySelector('textarea[name="remarks"]').value.trim();
        if (remarks) receiptHTML += `<p><strong>Remarks:</strong> ${remarks}</p>`;

        document.getElementById('receiptBody').innerHTML = receiptHTML;

        // Show modal
        if (receiptModal) receiptModal.show();
    }

    // Final submit -> submit the form
    document.getElementById('finalSubmitBtn').addEventListener('click', function() {
        // Show confirmation dialog
        Swal.fire({
            title: "Submit Request?",
            text: "Do you want to finalize and send this request?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, submit it",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Close modal first
                if (receiptModal) {
                    receiptModal.hide();
                }
                // Submit the form
                document.getElementById('requestForm').submit();
            }
        });
    });

    // Fallback: ensure cancel and X buttons hide the modal
    document.querySelectorAll('#receiptModal [data-bs-dismiss], #modalCancelBtn, .btn-close').forEach(btn => {
        btn.addEventListener('click', function() {
            if (receiptModal) {
                receiptModal.hide();
            } else {
                // fallback hide
                const m = document.getElementById('receiptModal');
                m.classList.remove('show');
                m.style.display = 'none';
            }
        });
    });

    // Clear form functionality
    document.getElementById('clearBtn').addEventListener('click', function() {
        Swal.fire({
            title: "Clear Form?",
            text: "This will reset all quantities.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, clear it",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Reset all quantities
                document.querySelectorAll('.qty-input').forEach(input => {
                    input.value = 0;
                });

                // Reset unit selections to default
                document.querySelectorAll('.unit-select').forEach(select => {
                    select.selectedIndex = 0;
                });

                // Clear remarks
                document.getElementById('remarksField').value = '';

                // Close modal if open
                if (receiptModal) receiptModal.hide();

                Swal.fire({
                    icon: 'success',
                    title: 'Form Cleared',
                    showConfirmButton: false,
                    timer: 1200
                });
            }
        });
    });
</script>