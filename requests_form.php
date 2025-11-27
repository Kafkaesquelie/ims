<?php
$page_title = 'Request Form';
require_once('includes/load.php');
page_require_level(3);

// Check login
if (!$session->isUserLoggedIn(true)) {
    redirect('index.php', false);
}

$current_user = current_user();
$user_id = (int)$current_user['id']; 
$user_name = $current_user['name']; 

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

function calculate_display_quantity($item)
{
    $quantity = (float)$item['quantity'];
    
    // If no conversion or conversion rate is 1, return simple quantity
    if ($item['conversion_rate'] <= 1 || $item['main_unit_name'] === $item['base_unit_name']) {
        return ($quantity) . " " . $item['main_unit_name'];
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    global $db;

    $qtys = $_POST['qty'] ?? [];
    $remarks = remove_junk($db->escape($_POST['remarks'] ?? ''));

    // Filter out items with zero quantity
    $qtys = array_filter($qtys, fn($q) => (int)$q > 0);

    if (empty($qtys)) {
        $session->msg("d", "❌ No items selected.");
        redirect('requests_form.php', false);
    }

    // Check for duplicate pending requests
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $check = $db->query("SELECT r.id 
                             FROM requests r 
                             JOIN request_items ri ON r.id = ri.req_id
                             WHERE r.requested_by = '{$user_id}' 
                               AND ri.item_id = '{$item_id}' 
                               AND r.status = 'Pending' LIMIT 1");
        if ($db->num_rows($check) > 0) {
            $item = find_by_id('items', $item_id);
            $session->msg("d", "❌ You already have a pending request for item: {$item['name']}");
            redirect('requests_form.php', false);
        }
    }

    // Start transaction
    $db->query("START TRANSACTION");

    // --- Generate RIS No with 4 zeros in middle and user_id as last 4 digits ---
    $year = date("Y");
    // Format user_id to 4 digits with leading zeros
    $user_id_formatted = str_pad($user_id, 4, '0', STR_PAD_LEFT);
    $ris_no = "{$year}-0000-{$user_id_formatted}";

    // ✅ Insert the request header WITH ris_no
    $query_request = "INSERT INTO requests (requested_by, date, status, ris_no)
                      VALUES ('{$user_id}', NOW(), 'Pending', '{$ris_no}')";
    if (!$db->query($query_request)) {
        $db->query("ROLLBACK");
        $session->msg("d", "❌ Failed to create request: " . $db->error());
        redirect('requests_form.php', false);
    }

    $req_id = $db->insert_id();

    // ✅ Insert request items
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

        // Check stock availability
        if ($qty_to_deduct > $item['quantity']) {
            $db->query("ROLLBACK");
            
            // Determine which unit to display available stock
            if ($is_requesting_base_unit && $conversion_rate > 1) {
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
            } else {
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
            redirect('requests_form.php', false);
        }

        // Compute price
        $unit_cost = (float)$item['unit_cost'];
        $price = $unit_cost * $qty_to_deduct;

        // Insert into request_items
        $query_item = "INSERT INTO request_items (req_id, item_id, qty, unit, price, remarks) 
                       VALUES ('{$req_id}', '{$item_id}', '{$qty}', '{$requested_unit_type}', '{$price}', '{$remarks}')";
        if (!$db->query($query_item)) {
            $db->query("ROLLBACK");
            $session->msg("d", "❌ Failed to add item: " . $db->error());
            redirect('requests_form.php', false);
        }

        // Update stock
        $new_qty = $item['quantity'] - $qty_to_deduct;
        if (!$db->query("UPDATE items SET quantity = '{$new_qty}' WHERE id = '{$item_id}'")) {
            $db->query("ROLLBACK");
            $session->msg("d", "❌ Failed to update stock for {$item['name']}");
            redirect('requests_form.php', false);
        }

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

    $db->query("COMMIT");
    $session->msg("s", "✅ Request successfully created! RIS No: {$ris_no}");
    redirect('requests_form.php', false);
}
 

// Fetch items and process for display
// $all_items = find_by_sql("SELECT * FROM items WHERE archived = 0");
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


// Process items for display
foreach ($all_items as &$item) {
   
    // Calculate display quantity
    $item['display_quantity'] = calculate_display_quantity($item);
    
    // Add stock status for sorting
    $quantity = (float)$item['quantity'];
    if ($quantity == 0) {
        $item['stock_status'] = 0; // Out of stock - show last
    } elseif ($quantity <= 5) {
        $item['stock_status'] = 1; // Low stock
    } else {
        $item['stock_status'] = 2; // Good stock - show first
    }

    // Calculate expiry status
    $item['expiry_status'] = get_expiry_status($item['expiry_date']);
}

// Sort items: available items first (good stock -> low -> out of stock)
usort($all_items, function($a, $b) {
    // First sort by stock status (descending - available first)
    if ($a['stock_status'] != $b['stock_status']) {
        return $b['stock_status'] - $a['stock_status'];
    }
    // Then sort by item name alphabetically
    return strcmp($a['name'], $b['name']);
});
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
      confirmButtonText: 'OK'
    });
  });
</script>
<?php endif; ?>

<style>
    :root {
        --good-stock: #28a745;
        --low-stock: #fd7e14;
        --out-stock: #6c757d;
        --primary-green: #006205;
    }
   
    .form-control:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 0.2rem rgba(0, 98, 5, 0.25);
    }

    .border-success {
        border-color: var(--primary-green) !important;
    }

    .text-success {
        color: var(--primary-green) !important;
    }

    .modal-header.bg-success {
        background-color: var(--primary-green) !important;
    }

    .btn-success {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
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

    /* Stock status color indicators */
    .stock-good {
        background-color: rgba(40, 167, 69, 0.05) !important;
        border-left: 4px solid var(--good-stock);
    }

    .stock-low {
        background-color: rgba(253, 126, 20, 0.05) !important;
        border-left: 4px solid var(--low-stock);
    }

    .stock-out {
        background-color: rgba(108, 117, 125, 0.15) !important;
        border-left: 4px solid var(--out-stock);
        color: #6c757d !important;
    }

    /* Darkened row for out-of-stock items */
    .stock-out td {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
        opacity: 0.7;
    }

    .stock-out .text-muted {
        color: #8a939b !important;
    }

    .table th {
        background: #005113ff;
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        text-align: center;
    }

    /* Stock status indicators in Available Qty column */
    .stock-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        vertical-align: middle;
    }

    .indicator-good { background-color: var(--good-stock); }
    .indicator-low { background-color: var(--low-stock); }
    .indicator-out { background-color: var(--out-stock); }

    /* Stock status legend */
    .stock-legend {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #495057;
    }

    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .legend-good { background-color: var(--good-stock); }
    .legend-low { background-color: var(--low-stock); }
    .legend-out { background-color: var(--out-stock); }

    /* Rounded dropdowns */
    .unit-select {
        border-radius: 8px !important;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        font-size: 0.875rem;
    }

    .unit-select:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 0.2rem rgba(0, 98, 5, 0.25);
    }

    /* Rounded quantity inputs */
    .qty-input {
        border-radius: 8px !important;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        font-size: 0.875rem;
    }

    .qty-input:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 0.2rem rgba(0, 98, 5, 0.25);
    }

    /* Table row hover effects */
    .table-hover tbody tr:hover:not(.stock-out) {
        background-color: rgba(0, 98, 5, 0.02) !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    /* Disable hover effect for out-of-stock items */
    .table-hover tbody tr.stock-out:hover {
        background-color: #f8f9fa !important;
        transform: none;
        box-shadow: none;
    }

    /* Subtle badge styles */
    .stock-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-weight: 600;
        border: 1px solid;
    }

    .badge-good {
        background-color: rgba(40, 167, 69, 0.1);
        color: #155724;
        border-color: var(--good-stock);
    }

    .badge-low {
        background-color: rgba(253, 126, 20, 0.1);
        color: #cc5500;
        border-color: var(--low-stock);
    }

    .badge-out {
        background-color: rgba(108, 117, 125, 0.1);
        color: #495057;
        border-color: var(--out-stock);
    }

    /* Style disabled inputs for out-of-stock items */
    .stock-out .unit-select:disabled,
    .stock-out .qty-input:disabled {
        background-color: #e9ecef;
        border-color: #ced4da;
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* DataTable customizations */
    .dataTables_wrapper {
        margin-top: 1rem;
    }
    
    .dataTables_length,
    .dataTables_filter,
    .dataTables_info,
    .dataTables_paginate {
        margin: 0.5rem 0;
    }
    
    .dataTables_filter input {
        border-radius: 20px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #dee2e6;
    }
    
    .dataTables_paginate .paginate_button {
        border-radius: 5px !important;
        margin: 0 2px;
    }
    
    .dataTables_paginate .paginate_button.current {
        background: var(--primary-green) !important;
        border-color: var(--primary-green) !important;
    }

    /* Expiry badge styles */
    .expiry-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        border-radius: 12px;
        display: inline-block;
        font-weight: 600;
        margin-bottom: 4px;
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

    /* Expiry column specific styles */
    .expiry-column {
        width: 150px;
        text-align: center;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow-x: auto;
        }
        
        /* Stack table headers for mobile */
        #itemsTable thead {
            display: none;
        }
        
        #itemsTable tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            background: white;
        }
        
        #itemsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border: none;
            border-bottom: 1px solid #f8f9fa;
        }
        
        #itemsTable tbody td:last-child {
            border-bottom: none;
        }
        
        #itemsTable tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            min-width: 120px;
        }
        
        /* Adjust form controls for mobile */
        .unit-select, .qty-input {
            width: 100% !important;
            max-width: none !important;
            font-size: 1rem;
        }
        
        .qty-input {
            text-align: center;
        }
        
        /* Card adjustments */
        .card-body {
            padding: 1rem;
        }
        
        /* Button adjustments */
        .btn-success {
            font-size: 1rem;
            padding: 0.75rem 1rem;
        }
        
        /* Search box adjustments */
        .search-box {
            max-width: 100%;
            margin-bottom: 1rem;
        }
        
        /* Header adjustments */
        .row.mb-2 {
            flex-direction: column;
        }
        
        .col-sm-9, .col-sm-3 {
            width: 100%;
        }
        
        .col-sm-3 {
            margin-top: 1rem;
        }
        
        /* Form row adjustments */
        .row.mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .col-md-6, .col-md-9, .col-md-3 {
            margin-bottom: 1rem;
        }
        
        /* Modal adjustments */
        .modal-dialog {
            margin: 0.5rem;
        }
        
        .modal-content {
            border-radius: 0.5rem;
        }
        
        /* Stock legend adjustments */
        .stock-legend {
            justify-content: flex-start;
            gap: 0.5rem;
        }
        
        .legend-item {
            font-size: 0.8rem;
        }

        /* Expiry column adjustments for mobile */
        .expiry-column {
            width: auto;
        }
        
        .expiry-badge {
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
        }
    }
    
    /* Small mobile devices */
    @media (max-width: 576px) {
        #itemsTable tbody td {
            flex-direction: column;
            align-items: flex-start;
            padding: 0.75rem 0.5rem;
        }
        
        #itemsTable tbody td::before {
            margin-bottom: 0.25rem;
            min-width: auto;
        }
        
        .unit-select, .qty-input {
            width: 100% !important;
        }
        
        .card {
            margin: 0 -0.75rem;
            border-radius: 0;
            border-left: none;
            border-right: none;
        }
        
        .container-fluid {
            padding: 0;
        }
    }
    
    /* Medium devices adjustment */
    @media (max-width: 992px) and (min-width: 769px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .unit-select, .qty-input {
            font-size: 0.8rem;
        }
    }
</style>

<div class="row mb-2 align-items-center" style="border-top: 5px solid #006205; border-radius: 10px;">
    <div class="col-sm-9 mt-3">                  
        <h5 class="mb-0"><i class="nav-icon fa-solid fa-pen-to-square"></i> Request Form</h5>
    </div>
    <div class="col-sm-3 d-flex justify-content-end mt-3">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control" placeholder="Search items..." id="searchInput">
        </div>
    </div>
</div>

<div class="card">
  <div class="card-body">
    <form id="requestForm" method="post" action="">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="fw-bold text-success">Requestor's Name:</label>
          <input type="text" class="form-control-plaintext border-bottom text-success" value="<?= $user_name; ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="fw-bold text-success">Position / Office:</label>
          <input type="text" class="form-control-plaintext border-bottom text-success" value="<?= $current_user['position'] ?? ''; ?>" readonly>
        </div>
      </div>

      <!-- Stock Status Legend -->
      <div class="stock-legend">
        <div class="legend-item">
            <div class="legend-color legend-good"></div>
            <span>Good Stock</span>
        </div>
        <div class="legend-item">
            <div class="legend-color legend-low"></div>
            <span>Low Stock</span>
        </div>
        <div class="legend-item">
            <div class="legend-color legend-out"></div>
            <span>Out of Stock</span>
        </div>
      </div>

      <label class="fw-bold text-success">Available Items <small class="text-muted">(Sorted by availability)</small></label>
      <div class="table-responsive mb-3">
        <?php if(!empty($all_items)): ?>
        <table class="table table-striped table-hover align-middle" id="itemsTable">
          <thead class="table-success">
            <tr>
              <th>Stock Card</th>
              <th>Item Name</th>
              <th class="text-center">Available Qty</th>
              <th class="text-center expiry-column">Expiry Status</th>
              <th class="text-center">Request Unit</th>
              <th class="text-center">Request Qty</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $current_stock_status = null;
            foreach($all_items as $it): 
                $quantity = (float)$it['quantity'];
                $conversion_rate = (float)$it['conversion_rate'];
                
                // Determine stock status and color class
                if ($quantity == 0) {
                    $stock_class = 'stock-out';
                    $indicator_class = 'indicator-out';
                    $stock_badge = '<span class="stock-badge badge-out">Out of Stock</span>';
                    $stock_status_text = 'Out of Stock';
                } elseif ($quantity <= 5) {
                    $stock_class = 'stock-low';
                    $indicator_class = 'indicator-low';
                    $stock_badge = '<span class="stock-badge badge-low">Low</span>';
                    $stock_status_text = 'Low Stock';
                } else {
                    $stock_class = 'stock-good';
                    $indicator_class = 'indicator-good';
                    $stock_badge = '<span class="stock-badge badge-good">Good</span>';
                    $stock_status_text = 'Good Stock';
                }

                // Expiry status
                $expiry_status = $it['expiry_status'];
            ?>
            <tr class="<?= $stock_class ?>" data-quantity="<?= $quantity ?>">
              <td data-label="Stock Card"><?= htmlspecialchars($it['stock_card']); ?></td>         
              <td data-label="Item Name">
                <strong><?= htmlspecialchars($it['name']); ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($it['cat_name']); ?></small>
              </td>
              <td class="text-center" data-label="Available Qty">
                <div class="d-flex align-items-center justify-content-center">
                  <strong><?= $it['display_quantity']; ?></strong>
                </div>
                <div class="mt-1">
                  <span class="stock-indicator <?= $indicator_class ?>"></span>
                  <?= $stock_badge ?>
                </div>
              </td>
              <td class="text-center expiry-column" data-label="Expiry Status">
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
              <td class="text-center" data-label="Request Unit">
                <select name="unit_type[<?= (int)$it['id']; ?>]"
                        class="form-select form-select-sm p-2 w-100 unit-select"
                        style="width: 120px;"
                        data-itemid="<?= (int)$it['id']; ?>"
                        data-conversion="<?= $it['conversion_rate']; ?>"
                        data-mainunit="<?= htmlspecialchars($it['main_unit_name']); ?>"
                        data-baseunit="<?= htmlspecialchars($it['base_unit_name']); ?>"
                        <?= $quantity == 0 ? 'disabled' : '' ?>>
                    <?php if ($it['conversion_rate'] > 1 && $it['main_unit_name'] !== $it['base_unit_name']): ?>
                        <!-- Items with conversion - show both units -->
                        <option value="<?= $it['main_unit_name']; ?>"><?= $it['main_unit_name']; ?></option>
                        <option value="<?= $it['base_unit_name']; ?>"><?= $it['base_unit_name']; ?></option>
                    <?php else: ?>
                        <!-- Items without conversion or same units - show only main unit -->
                        <option value="<?= $it['main_unit_name']; ?>"><?= $it['main_unit_name']; ?></option>
                    <?php endif; ?>
                </select>
              </td>
              <td class="text-center" data-label="Request Qty">
                <input type="number" 
                       name="qty[<?= (int)$it['id']; ?>]" 
                       min="0" 
                       step="1"
                       value="0" 
                       class="form-control text-center border-success qty-input" 
                       style="max-width:120px;" 
                       <?= $quantity == 0 ? 'disabled' : '' ?>
                       data-itemid="<?= (int)$it['id']; ?>"
                       data-available="<?= $quantity; ?>"
                       data-conversion="<?= $it['conversion_rate']; ?>"
                       data-mainunit="<?= htmlspecialchars($it['main_unit_name']); ?>"
                       data-baseunit="<?= htmlspecialchars($it['base_unit_name']); ?>"
                       title="Available: <?= $it['display_quantity']; ?>">
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

      <div class="row mb-3">
        <div class="col-md-9">
          <label class="fw-bold text-success">Remarks (Optional)</label>
          <textarea class="form-control border-success" name="remarks" rows="2" placeholder="Add remarks here..."></textarea>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-success w-100" id="reviewBtn">
            <i class="fa-solid fa-clipboard-check"></i> Review Request
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Request Receipt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="receiptBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="finalSubmitBtn">Submit Request</button>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
// Initialize DataTable
$(document).ready(function() {
    var table = $('#itemsTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        autoWidth: false,
        responsive: true,
        language: {
            search: "Search items:",
            lengthMenu: "Show _MENU_ items per page",
            info: "Showing _START_ to _END_ of _TOTAL_ items",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { orderable: false, targets: [3, 4, 5] }, // Make action columns non-orderable
            { width: "15%", targets: [0] }, // Stock Card column
            { width: "20%", targets: [1] }, // Item Name column
            { width: "15%", targets: [2] }, // Available Qty column
            { width: "15%", targets: [3] }, // Expiry Status column
            { width: "15%", targets: [4] }, // Request Unit column
            { width: "15%", targets: [5] }  // Request Qty column
        ],
        // Initial sort by stock status (using the data-quantity attribute)
        order: [[2, 'desc']],
        drawCallback: function(settings) {
            // Update any dynamic content after table redraw
            updateUnitSelects();
        }
    });

    // Custom search functionality
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Initialize unit selects after table is drawn
    function updateUnitSelects() {
        document.querySelectorAll('.unit-select').forEach(select => {
            select.addEventListener('change', function() {
                const itemId = this.dataset.itemid;
                const conversion = parseFloat(this.dataset.conversion) || 1;
                const mainUnit = this.dataset.mainunit;
                const baseUnit = this.dataset.baseunit;
                const qtyInput = document.querySelector(`input[name="qty[${itemId}]"]`);
                const available = parseFloat(qtyInput.dataset.available) || 0;

                // Update max value based on selected unit
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
                    qtyInput.title = `Available: ${availableText}`;
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
                    qtyInput.title = `Available: ${availableText}`;
                }
            });

            // Trigger initial setup
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
    }

    // Initialize unit selects
    updateUnitSelects();
});

// Review before submit
document.getElementById('reviewBtn').addEventListener('click', function() {
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    let receiptHTML = '<p><strong>Requestor:</strong> ' +
        document.querySelector('input[readonly]').value + '</p>';

    receiptHTML += '<table class="table table-bordered align-middle"><thead><tr><th>Item Name</th><th>Requested Qty</th><th>Unit</th><th>Available Stock</th><th>Expiry Status</th></tr></thead><tbody>';
    let hasItem = false;

    rows.forEach(row => {
        const input = row.querySelector('input.qty-input');
        const qty = parseFloat(input.value) || 0;
        if (qty <= 0) return;

        const itemId = input.dataset.itemid;
        const itemName = row.cells[1].innerText.trim();
        const available = row.cells[2].innerText.trim();
        const expiryStatus = row.cells[3].innerText.trim();
        const unitSelect = row.querySelector('select[name^="unit_type"]');
        const selectedUnit = unitSelect.selectedOptions[0].text;

        hasItem = true;

        receiptHTML += `
            <tr>
                <td>${itemName}</td>
                <td>${qty}</td>
                <td>${selectedUnit}</td>
                <td>${available}</td>
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
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
});

// Final submit
document.getElementById('finalSubmitBtn').addEventListener('click', function() {
    document.getElementById('requestForm').submit();
});

// Mobile-specific adjustments
function handleMobileLayout() {
    const isMobile = window.innerWidth <= 768;
    const table = document.getElementById('itemsTable');
    
    if (isMobile) {
        // Add mobile-specific classes
        table.classList.add('mobile-table');
        
        // Adjust form controls for mobile
        document.querySelectorAll('.unit-select, .qty-input').forEach(input => {
            input.style.fontSize = '1rem';
            input.style.padding = '0.5rem';
        });
    } else {
        // Remove mobile-specific classes
        table.classList.remove('mobile-table');
        
        // Reset form controls for desktop
        document.querySelectorAll('.unit-select, .qty-input').forEach(input => {
            input.style.fontSize = '';
            input.style.padding = '';
        });
    }
}

// Initialize mobile layout on load and resize
window.addEventListener('load', handleMobileLayout);
window.addEventListener('resize', handleMobileLayout);
</script>