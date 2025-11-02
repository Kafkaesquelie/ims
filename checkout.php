<?php
$page_title = 'Request Form';
require_once('includes/load.php');
page_require_level(1);

// Fetch all available items
$all_items = find_all('items');

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

// get logged-in user
$current_user = current_user();
$current_user_id = $current_user['id'] ?? null;
$current_user_name = $current_user['name'] ?? $current_user['username'] ?? '';

function get_users_table()
{
    global $db;
    // fetch common user fields and build a display name
    $sql = "SELECT * FROM users ORDER BY name ASC";
    $result = $db->query($sql);
    $users = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // build a reasonable display name from whatever columns exist
            if (!empty($row['name'])) {
                $display = $row['name'];
            } elseif (!empty($row['first_name'])) {
                $display = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            } else {
                $display = $row['username'] ?? ('User ' . $row['id']);
            }
            $users[] = [
                'id' => $row['id'],
                'full_name' => $display,
                'position' => $row['position'] ?? '',
            ];
        }
    }
    return $users;
}

// Get employees WITHOUT user accounts
function get_employees_without_users()
{
    global $db;
    $sql = "SELECT * FROM employees WHERE (user_id IS NULL OR user_id = 0) AND status = 1 ORDER BY last_name ASC, first_name ASC";
    $result = $db->query($sql);
    $employees = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $full_name = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
            $employees[] = [
                'id' => $row['id'],
                'full_name' => $full_name,
                'position' => $row['position'] ?? ''
            ];
        }
    }
    return $employees;
}

function get_requestors()
{
    // Only include users and employees without user accounts
    $requestors = [];
    $users = get_users_table();
    foreach ($users as $u) {
        $requestors[] = [
            'source' => 'users',
            'id' => $u['id'],
            'full_name' => $u['full_name'],
            'position' => $u['position'] ?? ''
        ];
    }

    // Only include employees without user accounts
    $employees = get_employees_without_users();
    foreach ($employees as $e) {
        $requestors[] = [
            'source' => 'employees',
            'id' => $e['id'],
            'full_name' => $e['full_name'],
            'position' => $e['position'] ?? ''
        ];
    }
    return $requestors;
}

// Build list used in the select
$requestors = get_requestors();

// default selected requestor value (current logged user if present in users)
$default_selected = 'users_' . ($current_user_id ?? '0');

function is_ris_no_duplicate($ris_no)
{
    global $db;
    $ris_no = $db->escape($ris_no);
    $result = $db->query("SELECT id FROM requests WHERE ris_no = '{$ris_no}' LIMIT 1");
    return $db->num_rows($result) > 0;
}

// Get current year and admin-set middle part
$current_year = date("Y");
// Get admin-set middle part from settings or use default
$admin_middle = '0000'; // Default, will be updated via AJAX

// ---------- Form submission handling ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $db;
    $selected = $_POST['requestor'] ?? '';
    $parts = explode('_', $selected, 2);
    $source = $parts[0] ?? 'users';
    $rid = isset($parts[1]) ? (int)$parts[1] : 0;

    $requestor_row = ($source === 'users') ? find_by_id('users', $rid) : find_by_id('employees', $rid);
    $requestor_name = $requestor_row['name'] ?? ($requestor_row['username'] ?? 'Unknown');
    $remarks = remove_junk($db->escape($_POST['remarks'] ?? ''));
    $qtys = array_filter($_POST['qty'] ?? [], fn($q) => (int)$q > 0);

    if (empty($qtys)) {
        $session->msg("d", "❌ No items selected.");
        redirect('checkout.php', false);
    }

    // Check duplicate pending requests
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
    
    // Get RIS number parts
    $year_part = $_POST['ris_year'] ?? $current_year;
    $middle_part = $_POST['ris_middle'] ?? '0000';
    $employee_part = $_POST['ris_employee'] ?? '0000';
    
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

$all_items = find_by_sql("SELECT * FROM items WHERE archived = 0");

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
    $item['cat_name'] = get_category_name($item['categorie_id']);

    // FIXED: Get main unit from units table and base unit from base_units table
    $item['main_unit_name'] = get_unit_name($item['unit_id']);  // From units table
    $item['base_unit_name'] = get_base_unit_name($item['base_unit_id']);  // From base_units table

    // Get conversion data
    $conversion = find_by_sql("SELECT conversion_rate, from_unit_id, to_unit_id 
                              FROM unit_conversions WHERE item_id = '{$item['id']}' LIMIT 1");

    if ($conversion && count($conversion) > 0) {
        $item['conversion_rate'] = (float)$conversion[0]['conversion_rate'];
        $item['from_unit_id'] = $conversion[0]['from_unit_id'];
        $item['to_unit_id'] = $conversion[0]['to_unit_id'];

        // FIXED: Use proper unit names from respective tables
        $item['main_unit_name'] = get_unit_name($item['from_unit_id']);  // From units table
        $item['base_unit_name'] = get_base_unit_name($item['to_unit_id']);  // From base_units table
    } else {
        $item['conversion_rate'] = 1;
        // Keep the original values from items table
        $item['main_unit_name'] = get_unit_name($item['unit_id']);
        $item['base_unit_name'] = get_base_unit_name($item['base_unit_id']);
    }

    // Calculate display quantity using the FIXED function
    $item['display_quantity'] = calculate_display_quantity($item);

    // Determine stock status
    $item['stock_status'] = 'good';
    $item['stock_badge'] = '';
    
    if ($item['quantity'] == 0) {
        $item['stock_status'] = 'out-of-stock';
        $item['stock_badge'] = '<span class="badge bg-danger stock-badge"><i class="fas fa-times-circle"></i> Out of Stock</span>';
    } elseif ($item['quantity'] <= 5) {
        $item['stock_status'] = 'low-stock';
        $item['stock_badge'] = '<span class="badge bg-warning text-dark stock-badge"><i class="fas fa-exclamation-triangle"></i> Low Stock</span>';
    } else {
        $item['stock_status'] = 'good';
        $item['stock_badge'] = '<span class="badge bg-success stock-badge"><i class="fas fa-check-circle"></i> In Stock</span>';
    }
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

    .ris-part.editable {
        background-color: white;
        border-color: #006205;
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
    <input type="hidden" name="ris_middle" id="risMiddle" value="">
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
                            <option value="<?= $val; ?>" <?= $sel; ?> data-employee-id="<?= $req['id']; ?>">
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
                            <div class="ris-part">
                                <?= $current_year ?>
                            </div>
                            <span class="ris-separator">-</span>
                            
                            <!-- Middle Part (Editable) -->
                            <input type="text" 
                                name="ris_middle_input" 
                                id="risMiddleInput"
                                class="form-control ris-input ris-part editable border-success"
                                maxlength="4"
                                placeholder="0000"
                                style="width: 80px;"
                                required>
                            <span class="ris-separator">-</span>
                            
                            <!-- Employee Part (Auto-filled) -->
                            <div class="ris-part" id="risEmployeeDisplay">
                                0000
                            </div>
                        </div>
                        <!-- Error message will be inserted here by JavaScript -->
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
                                <th class="text-center">Request Unit</th>
                                <th class="text-center">Request Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_items as $it): 
                                $row_class = '';
                                $indicator_class = '';
                                $indicator_text = '';
                                
                                switch($it['stock_status']) {
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

    // Initialize DataTable
    $(document).ready(function() {
        $('#itemsTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            searching: true,
            autoWidth: false,
            fixedColumns: true,
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
        const employeeId = selectedOption.getAttribute('data-employee-id') || '0000';
        
        // Update employee part of RIS number
        document.getElementById('risEmployeeDisplay').textContent = employeeId.padStart(4, '0');
        document.getElementById('risEmployee').value = employeeId.padStart(4, '0');
        
        // Update position field
        const [source, idStr] = sel.value.split('_');
        const id = parseInt(idStr || '0', 10);
        const found = requestors.find(r => r.source === source && parseInt(r.id, 10) === id);
        document.getElementById('positionField').value = found ? (found.position || '') : '';
        
        // Trigger RIS validation when requestor changes
        validateRIS();
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

        // RIS Middle Input validation - only numbers and max 4 digits
        document.getElementById('risMiddleInput').addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 4 digits
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }
            
            // Update hidden field
            document.getElementById('risMiddle').value = this.value.padStart(4, '0');
            
            // Real-time validation
            validateRIS();
        });

        // Get admin-set middle part on page load
        fetchAdminMiddlePart();
    });

    // Fetch admin-set middle part from server
    function fetchAdminMiddlePart() {
        fetch('get_ris_mid.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.middlePart) {
                    document.getElementById('risMiddleInput').value = data.middlePart;
                    document.getElementById('risMiddle').value = data.middlePart.padStart(4, '0');
                    // Validate after setting the middle part
                    setTimeout(validateRIS, 100);
                }
            })
            .catch(error => {
                console.error('Error fetching RIS middle part:', error);
                // Set default value if fetch fails
                document.getElementById('risMiddleInput').value = '0001';
                document.getElementById('risMiddle').value = '0001';
                setTimeout(validateRIS, 100);
            });
    }

    // On change for requestor select
    document.getElementById('requestorSelect').addEventListener('change', updatePositionFieldFromSelect);

    // Create (single) Bootstrap Modal instance and use it for show/hide
    let receiptModalEl = document.getElementById('receiptModal');
    let receiptModal = null;
    if (typeof bootstrap !== 'undefined' && receiptModalEl) {
        receiptModal = new bootstrap.Modal(receiptModalEl);
    }

    // Real-time RIS validation function
    let risValidationTimeout;
    function validateRIS() {
        clearTimeout(risValidationTimeout);
        
        risValidationTimeout = setTimeout(() => {
            const risMiddle = document.getElementById('risMiddleInput').value.trim();
            const reviewBtn = document.getElementById('reviewBtn');
            const risMiddleInput = document.getElementById('risMiddleInput');

            // Remove previous validation states
            risMiddleInput.classList.remove('is-invalid');
            removeRISErrorMessage();

            if (!risMiddle || risMiddle.length !== 4) {
                // RIS middle part not complete
                reviewBtn.disabled = true;
                reviewBtn.title = 'Please enter a complete 4-digit RIS middle part';
                reviewBtn.classList.add('disabled');
                return;
            }

            // Build complete RIS number for validation
            const yearPart = document.getElementById('risYear').value;
            const middlePart = risMiddle.padStart(4, '0');
            const employeePart = document.getElementById('risEmployee').value;
            const fullRIS = yearPart + '-' + middlePart + '-' + employeePart;

            // Check for duplicate RIS number
            checkRISDuplicate(fullRIS, function(isDuplicate) {
                if (isDuplicate) {
                    risMiddleInput.classList.add('is-invalid');
                    reviewBtn.disabled = true;
                    reviewBtn.title = 'RIS number is already used. Please choose a different middle part.';
                    reviewBtn.classList.add('disabled');
                    showRISErrorMessage('RIS Number already used. Please choose a different number.');
                } else {
                    // REMOVED: No checkmark validation, just enable the button
                    reviewBtn.disabled = false;
                    reviewBtn.title = 'Review request';
                    reviewBtn.classList.remove('disabled');
                    removeRISErrorMessage(); // Remove any existing error messages
                }
            });
        }, 500);
    }

    // RIS message handling functions
    function removeRISErrorMessage() {
        const risContainer = document.querySelector('.ris-container');
        const existingError = risContainer.querySelector('.ris-error-message');
        if (existingError) {
            existingError.remove();
        }
    }

    function showRISErrorMessage(message) {
        removeRISErrorMessage();
        const risContainer = document.querySelector('.ris-container');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'ris-error-message invalid-feedback';
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        risContainer.appendChild(errorDiv);
    }

    // Review button: build receipt HTML and show modal via the instance
    document.getElementById('reviewBtn').addEventListener('click', function() {
        const risMiddle = document.getElementById('risMiddleInput').value.trim();
        if (!risMiddle || risMiddle.length !== 4) {
            Swal.fire({
                icon: 'warning',
                title: 'RIS Number Required',
                text: 'Please enter a complete 4-digit RIS middle part.',
                confirmButtonColor: '#dc3545'
            });
            document.getElementById('risMiddleInput').focus();
            return;
        }

        // Build complete RIS number for validation
        const yearPart = document.getElementById('risYear').value;
        const middlePart = risMiddle.padStart(4, '0');
        const employeePart = document.getElementById('risEmployee').value;
        const fullRIS = yearPart + '-' + middlePart + '-' + employeePart;

        // Final validation before review
        if (document.getElementById('risMiddleInput').classList.contains('is-invalid')) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate RIS Number',
                text: 'This RIS Number is already used. Please choose a different middle part.',
                confirmButtonColor: '#dc3545'
            });
            document.getElementById('risMiddleInput').focus();
            return;
        }

        // Check for duplicate RIS number one more time
        checkRISDuplicate(fullRIS, function(isDuplicate) {
            if (isDuplicate) {
                document.getElementById('risMiddleInput').classList.add('is-invalid');
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate RIS Number',
                    text: 'This RIS Number is already used. Please choose a different middle part.',
                    confirmButtonColor: '#dc3545'
                });
                document.getElementById('risMiddleInput').focus();
                return;
            }

            // Proceed with review if not duplicate
            proceedWithReview(fullRIS);
        });
    });

    function proceedWithReview(fullRIS) {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        let receiptHTML = '<p><strong>Requestor:</strong> ' +
            document.getElementById('requestorSelect').selectedOptions[0].text +
            ' (' + document.getElementById('positionField').value + ')</p>';

        // Add RIS number to receipt
        receiptHTML += `<p><strong>RIS Number:</strong> ${fullRIS}</p>`;

        receiptHTML += '<table class="table table-bordered align-middle"><thead><tr><th>Item Name</th><th>Requested Qty</th><th>Unit</th><th>Available Stock</th><th>Stock Status</th></tr></thead><tbody>';
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
        const yearPart = document.getElementById('risYear').value;
        const middlePart = document.getElementById('risMiddleInput').value.padStart(4, '0');
        const employeePart = document.getElementById('risEmployee').value;
        const fullRIS = yearPart + '-' + middlePart + '-' + employeePart;

        // Final duplicate check before submission
        checkRISDuplicate(fullRIS, function(isDuplicate) {
            if (isDuplicate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate RIS Number',
                    text: 'This RIS Number is already used. Please choose a different middle part.',
                    confirmButtonColor: '#dc3545'
                });
                if (receiptModal) receiptModal.hide();
                document.getElementById('risMiddleInput').focus();
                return;
            }

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
                    document.getElementById('requestForm').submit();
                }
            });
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
            text: "This will reset all quantities and RIS middle part.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, clear it",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Clear RIS middle part
                document.getElementById('risMiddleInput').value = '';
                document.getElementById('risMiddle').value = '';
                document.getElementById('risMiddleInput').classList.remove('is-invalid');
                removeRISErrorMessage();

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

                // Re-fetch admin middle part
                fetchAdminMiddlePart();

                Swal.fire({
                    icon: 'success',
                    title: 'Form Cleared',
                    showConfirmButton: false,
                    timer: 1200
                });
            }
        });
    });

    // RIS duplicate checking function
    function checkRISDuplicate(fullRIS, callback) {
        fetch('check_ris_duplicate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ris_no=' + encodeURIComponent(fullRIS)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            callback(data.exists || false);
        })
        .catch(error => {
            console.error('Error checking RIS number:', error);
            callback(false); // Continue on error
        });
    }
</script>