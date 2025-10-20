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
    $ris_no = date("Y-m-") . remove_junk($db->escape($_POST['ris_no'] ?? '0000'));

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

    .is-valid {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
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
    #risNoField {
        transition: all 0.3s ease;
    }

    /* RIS number container styling for proper error message placement */
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

    <!-- Requestor Information Card -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user me-2"></i> Requestor Information
        </div>
        <div class="card-body">
            <?php
            $year = date("Y");
            $month = date("m");
            $ris_prefix = $year . '-' . $month . '-';
            ?>

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
                            <option value="<?= $val; ?>" <?= $sel; ?>>
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
                        <div class="d-flex align-items-center">
                            <span class="me-1 text-success fw-bold" style="margin-left:10px"><?= $ris_prefix; ?></span>
                            <input type="number" name="ris_no" id="risNoField"
                                class="form-control text-success border-success"
                                placeholder="0000"
                                maxlength="4"
                                style="background: transparent; width: 50%;" required>
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
                                        <?= $it['stock_badge'] ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($it['name']); ?></strong>
                                        <span class="stock-indicator <?= $indicator_class ?>"><?= $indicator_text ?></span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Pass PHP requestors to JS
    const requestors = <?= json_encode($requestors); ?>;
    const defaultSelected = "<?= $default_selected; ?>";

    // Populate position field based on selected option
    function updatePositionFieldFromSelect() {
        const sel = document.getElementById('requestorSelect').value;
        const [source, idStr] = sel.split('_');
        const id = parseInt(idStr || '0', 10);
        const found = requestors.find(r => r.source === source && parseInt(r.id, 10) === id);
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

    // Search filter for the items table
    document.getElementById('searchInput').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });

    // Create (single) Bootstrap Modal instance and use it for show/hide
    let receiptModalEl = document.getElementById('receiptModal');
    let receiptModal = null;
    if (typeof bootstrap !== 'undefined' && receiptModalEl) {
        receiptModal = new bootstrap.Modal(receiptModalEl);
    }

    // Review button: build receipt HTML and show modal via the instance
    document.getElementById('reviewBtn').addEventListener('click', function() {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        let receiptHTML = '<p><strong>Requestor:</strong> ' +
            document.getElementById('requestorSelect').selectedOptions[0].text +
            ' (' + document.getElementById('positionField').value + ')</p>';

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
    });

    // Final submit -> submit the form
    document.getElementById('finalSubmitBtn').addEventListener('click', function() {
        document.getElementById('requestForm').submit();
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
</script>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#itemsTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            searching: false,
            autoWidth: true,
        });
        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput = document.getElementById("searchInput");
        const table = document.getElementById("itemsTable");
        const rows = table.getElementsByTagName("tr");

        searchInput.addEventListener("keyup", function() {
            const filter = this.value.toLowerCase();

            // Loop through table rows (skip header)
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName("td");
                let match = false;

                // Check every cell for a match
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }

                // Show or hide the row based on match
                rows[i].style.display = match ? "" : "none";
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('finalSubmitBtn').addEventListener('click', function() {
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
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Select all quantity input fields
        document.querySelectorAll('.qty-input').forEach(input => {
            // Listen for any change or typing
            input.addEventListener('input', function() {
                const max = parseFloat(this.max);
                let val = parseFloat(this.value) || 0;
                
                // Ensure whole numbers only
                if (!Number.isInteger(val)) {
                    val = Math.floor(val);
                    this.value = val;
                }
                
                const unitSelect = document.querySelector(`select[name="unit_type[${this.dataset.itemid}]"]`);
                const selectedUnit = unitSelect ? unitSelect.selectedOptions[0].text : '';
                const conversion = parseFloat(this.dataset.conversion) || 1;
                const mainUnit = this.dataset.mainunit;
                const baseUnit = this.dataset.baseunit;

                // If user input exceeds available quantity
                if (val > max) {
                    let availableText = '';
                    if (selectedUnit === baseUnit && conversion > 1) {
                        const availableMain = parseFloat(this.dataset.available) || 0;
                        const fullMainUnits = Math.floor(availableMain);
                        const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);
                        
                        if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                            availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                        } else if (fullMainUnits > 0) {
                            availableText = `${fullMainUnits} ${mainUnit}`;
                        } else {
                            availableText = `${remainingBaseUnits} ${baseUnit}`;
                        }
                    } else {
                        const availableMain = parseFloat(this.dataset.available) || 0;
                        const fullMainUnits = Math.floor(availableMain);
                        const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);
                        
                        if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                            availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                        } else if (fullMainUnits > 0) {
                            availableText = `${fullMainUnits} ${mainUnit}`;
                        } else {
                            availableText = `${remainingBaseUnits} ${baseUnit}`;
                        }
                    }

                    Swal.fire({
                        icon: 'warning',
                        title: 'Quantity Exceeded',
                        text: `Only ${availableText} available in stock.`,
                        confirmButtonColor: '#28a745',
                    });

                    // Reset to maximum allowed
                    this.value = Math.floor(max);
                } else if (val < 0) {
                    // prevent negative input
                    this.value = 0;
                }
            });
        });
    });
</script>

<script>
    document.getElementById('clearBtn').addEventListener('click', function() {
        Swal.fire({
            title: "Clear Form?",
            text: "This will reset all quantities, requestor, and RIS number.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, clear it",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Reset requestor
                document.getElementById('requestorSelect').value = "";
                document.getElementById('positionField').value = "";

                // Clear RIS number
                document.getElementById('risNoField').value = "";

                // Reset all quantities
                document.querySelectorAll('.qty-input').forEach(input => {
                    input.value = 0;
                });

                // Reset unit selections to default
                document.querySelectorAll('.unit-select').forEach(select => {
                    select.selectedIndex = 0;
                });

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
<script>
    // Real-time RIS number duplicate checking
    document.addEventListener('DOMContentLoaded', function() {
        const risNoField = document.getElementById('risNoField');
        const reviewBtn = document.getElementById('reviewBtn');
        const risContainer = document.querySelector('.ris-container');
        const risPrefix = "<?= date('Y-m-'); ?>";
        let checkTimeout = null;

        // Set max length to 4 for RIS number
        risNoField.setAttribute('max', '9999');
        risNoField.setAttribute('min', '0');

        function updateReviewButtonState() {
            const risNumber = risNoField.value.trim();

            if (!risNumber) {
                // No RIS number entered - disable button
                reviewBtn.disabled = true;
                reviewBtn.title = 'Please enter a RIS number';
                reviewBtn.classList.add('disabled');
                return;
            }

            if (risNoField.classList.contains('is-invalid')) {
                // RIS number is duplicate - disable button
                reviewBtn.disabled = true;
                reviewBtn.title = 'RIS number is already used. Please choose a different number.';
                reviewBtn.classList.add('disabled');
                return;
            }

            if (risNoField.classList.contains('is-valid')) {
                // RIS number is valid and not duplicate - enable button
                reviewBtn.disabled = false;
                reviewBtn.title = 'Review request';
                reviewBtn.classList.remove('disabled');
                return;
            }

            // Default case - waiting for validation, disable button
            reviewBtn.disabled = true;
            reviewBtn.title = 'Validating RIS number...';
            reviewBtn.classList.add('disabled');
        }

        // Initialize button state on page load
        updateReviewButtonState();

        // Check RIS number on input
        risNoField.addEventListener('input', function() {
            clearTimeout(checkTimeout);

            // Limit input to 4 digits
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }

            const risNumber = this.value.trim();
            if (!risNumber) {
                this.classList.remove('is-invalid', 'is-valid');
                removeErrorMessage();
                updateReviewButtonState();
                return;
            }

            // Update button state immediately
            updateReviewButtonState();

            // Debounce the duplicate check
            checkTimeout = setTimeout(() => {
                checkRISDuplicate(risNumber);
            }, 500);
        });

        function removeErrorMessage() {
            const existingError = risContainer.querySelector('.ris-error-message');
            if (existingError) {
                existingError.remove();
            }
        }

        function showErrorMessage(message) {
            removeErrorMessage();

            const errorDiv = document.createElement('div');
            errorDiv.className = 'ris-error-message invalid-feedback';
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';

            risContainer.appendChild(errorDiv);
        }

        function showSuccessMessage(message) {
            // Remove any existing messages
            removeErrorMessage();

            const successDiv = document.createElement('div');
            successDiv.className = 'ris-error-message valid-feedback';
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            successDiv.style.color = '#28a745';

            risContainer.appendChild(successDiv);
        }

        function checkRISDuplicate(risNumber) {
            const fullRIS = risPrefix + risNumber;

            fetch('check_ris_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ris_no=' + encodeURIComponent(fullRIS)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        risNoField.classList.add('is-invalid');
                        risNoField.classList.remove('is-valid');
                        showErrorMessage('RIS Number already used. Please choose a different number.');
                    } else {
                        risNoField.classList.add('is-valid');
                        risNoField.classList.remove('is-invalid');
                        removeErrorMessage();
                        showSuccessMessage('RIS Number is available');
                        
                        // Auto-remove success message after 2 seconds
                        setTimeout(() => {
                            removeErrorMessage();
                        }, 2000);
                    }
                    updateReviewButtonState();
                })
                .catch(error => {
                    console.error('Error checking RIS number:', error);
                    // On error, remove validation states
                    risNoField.classList.remove('is-invalid', 'is-valid');
                    removeErrorMessage();
                    updateReviewButtonState();
                });
        }

        function closeModalIfOpen() {
            if (receiptModal && document.getElementById('receiptModal').classList.contains('show')) {
                receiptModal.hide();
            }
        }

        // Also check on form review to prevent submission of duplicate RIS
        document.getElementById('reviewBtn').addEventListener('click', function(e) {
            const risNumber = risNoField.value.trim();
            if (!risNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'RIS Number Required',
                    text: 'Please enter a RIS number before reviewing.',
                    confirmButtonColor: '#dc3545'
                });
                risNoField.focus();
                e.preventDefault();
                return;
            }

            const fullRIS = risPrefix + risNumber;

            fetch('check_ris_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ris_no=' + encodeURIComponent(fullRIS)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        risNoField.classList.add('is-invalid');
                        risNoField.classList.remove('is-valid');
                        showErrorMessage('RIS Number already used. Please choose a different number.');

                        Swal.fire({
                            icon: 'error',
                            title: 'Duplicate RIS Number',
                            text: 'This RIS Number is already used. Please choose a different number.',
                            confirmButtonColor: '#dc3545'
                        });
                        e.preventDefault();
                        risNoField.focus();

                        // Ensure modal is closed
                        closeModalIfOpen();
                    } else {
                        // Show success message and proceed with review
                        showSuccessMessage('RIS Number validated successfully!');
                        // Continue with normal review process
                        setTimeout(() => {
                            proceedWithReview();
                        }, 500);
                    }
                })
                .catch(error => {
                    console.error('Error checking RIS number:', error);
                    // Continue with review if check fails but show warning
                    Swal.fire({
                        title: 'Proceed with Review?',
                        text: 'RIS number verification failed. Do you want to proceed anyway?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        confirmButtonText: 'Yes, proceed',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            proceedWithReview();
                        }
                    });
                });
        });

        function proceedWithReview() {
            const rows = document.querySelectorAll('#itemsTable tbody tr');
            let receiptHTML = '<p><strong>Requestor:</strong> ' +
                document.getElementById('requestorSelect').selectedOptions[0].text +
                ' (' + document.getElementById('positionField').value + ')</p>';

            // Add RIS number to receipt
            const risNumber = document.getElementById('risNoField').value.trim();
            const fullRIS = risPrefix + risNumber;
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

        // Clear form should also clear RIS validation
        document.getElementById('clearBtn').addEventListener('click', function() {
            risNoField.classList.remove('is-invalid', 'is-valid');
            removeErrorMessage();
            closeModalIfOpen();
            updateReviewButtonState();
        });

        // Also check when modal final submit button is clicked (extra safety)
        document.getElementById('finalSubmitBtn').addEventListener('click', function(e) {
            const risNumber = risNoField.value.trim();
            if (!risNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'RIS Number Required',
                    text: 'Please enter a RIS number before submitting.',
                    confirmButtonColor: '#dc3545'
                });
                e.preventDefault();
                closeModalIfOpen();
                risNoField.focus();
                return;
            }

            const fullRIS = risPrefix + risNumber;

            fetch('check_ris_duplicate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ris_no=' + encodeURIComponent(fullRIS)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        risNoField.classList.add('is-invalid');
                        risNoField.classList.remove('is-valid');
                        showErrorMessage('RIS Number already used. Please choose a different number.');

                        Swal.fire({
                            icon: 'error',
                            title: 'Duplicate RIS Number',
                            text: 'This RIS Number is already used. Please choose a different number.',
                            confirmButtonColor: '#dc3545'
                        });

                        e.preventDefault();
                        closeModalIfOpen();
                        risNoField.focus();
                    } else {
                        // Show success message before final submission
                        showSuccessMessage('RIS Number validated! Submitting request...');
                        
                        // Proceed with the SweetAlert confirmation after a brief delay
                        setTimeout(() => {
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
                                    // Show final success message before form submission
                                    Swal.fire({
                                        title: "Success!",
                                        text: "Your request has been submitted successfully.",
                                        icon: "success",
                                        confirmButtonColor: "#28a745",
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        document.getElementById('requestForm').submit();
                                    });
                                }
                            });
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error checking RIS number:', error);
                    // If check fails, still show confirmation but warn user
                    Swal.fire({
                        title: "Submit Request?",
                        text: "Do you want to finalize and send this request? (RIS number verification failed)",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#28a745",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Yes, submit it",
                        cancelButtonText: "Cancel"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: "Success!",
                                text: "Your request has been submitted successfully.",
                                icon: "success",
                                confirmButtonColor: "#28a745",
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                document.getElementById('requestForm').submit();
                            });
                        }
                    });
                });

            e.preventDefault();
        });
    });
</script>