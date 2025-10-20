<?php
$page_title = 'Printable Reports on Physical Count';
require_once('includes/load.php');
page_require_level(1);

// ✅ Get current semester dates from school_years table
$current_semester = find_by_sql("SELECT * FROM school_years WHERE is_current = 1 LIMIT 1");
$current_semester = $current_semester ? $current_semester[0] : null;

$semester_start_date = $current_semester ? $current_semester['start_date'] : date('Y-m-d');
$semester_end_date = $current_semester ? $current_semester['end_date'] : date('Y-m-d');

// Format dates for display
$start_date_display = date('M d, Y', strtotime($semester_start_date));
$end_date_display = date('M d, Y', strtotime($semester_end_date));

// ensure current user info is always defined
$current_user = current_user();
$current_user_name = isset($current_user['name']) ? $current_user['name'] : '';
$current_user_position = isset($current_user['position']) ? $current_user['position'] : '';

// ✅ Get active tab from POST or default to inventories
$active_tab = $_POST['active_tab'] ?? 'inventories';

// ✅ Function to calculate stock based on date selection for items
function calculate_stock_for_date($item_id, $selected_date, $is_start_date = true) {
    global $db;
    
    if ($is_start_date) {
        // For start date: show total stock_in quantity until the selected date
        $stock_in_query = find_by_sql("
            SELECT SUM(new_qty - previous_qty) as total_stock_in 
            FROM stock_history 
            WHERE item_id = '{$db->escape($item_id)}' 
            AND change_type = 'stock_in' 
            AND date_changed <= '{$db->escape($selected_date)} 23:59:59'
        ");
        
        $total_stock_in = $stock_in_query ? ($stock_in_query[0]['total_stock_in'] ?? 0) : 0;
        
        // Also get any initial stock from item_stocks_per_year before the semester
        $initial_stock_query = find_by_sql("
            SELECT stock FROM item_stocks_per_year 
            WHERE item_id = '{$db->escape($item_id)}' 
            AND updated_at < '{$db->escape($selected_date)} 00:00:00'
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        
        $initial_stock = $initial_stock_query ? ($initial_stock_query[0]['stock'] ?? 0) : 0;
        
        return $initial_stock + $total_stock_in;
        
    } else {
        // For end date: show current/remaining quantity until that day
        // Get the latest stock record from item_stocks_per_year on or before the selected date
        $current_stock_query = find_by_sql("
            SELECT stock FROM item_stocks_per_year 
            WHERE item_id = '{$db->escape($item_id)}' 
            AND updated_at <= '{$db->escape($selected_date)} 23:59:59'
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        
        if ($current_stock_query && isset($current_stock_query[0]['stock'])) {
            return $current_stock_query[0]['stock'];
        }
        
        // If no record found in item_stocks_per_year, calculate from stock_history
        $stock_calculation = find_by_sql("
            SELECT 
                SUM(CASE 
                    WHEN change_type = 'stock_in' THEN (new_qty - previous_qty)
                    WHEN change_type = 'stock_out' THEN (previous_qty - new_qty)
                    WHEN change_type = 'adjustment' THEN (new_qty - previous_qty)
                    ELSE 0 
                END) as net_change
            FROM stock_history 
            WHERE item_id = '{$db->escape($item_id)}' 
            AND date_changed <= '{$db->escape($selected_date)} 23:59:59'
        ");
        
        $net_change = $stock_calculation ? ($stock_calculation[0]['net_change'] ?? 0) : 0;
        
        // Get initial stock before any transactions
        $initial_query = find_by_sql("
            SELECT stock FROM item_stocks_per_year 
            WHERE item_id = '{$db->escape($item_id)}' 
            ORDER BY updated_at ASC 
            LIMIT 1
        ");
        
        $initial_stock = $initial_query ? ($initial_query[0]['stock'] ?? 0) : 0;
        
        return $initial_stock + $net_change;
    }
}

// ✅ Function to get stock details for display for items
function get_stock_details($item_id, $selected_date, $is_start_date = true) {
    global $db;
    
    if ($is_start_date) {
        // For start date: get all stock_in transactions
        $stock_details = find_by_sql("
            SELECT 
                sh.change_type,
                sh.previous_qty,
                sh.new_qty,
                sh.date_changed,
                sh.remarks
            FROM stock_history sh
            WHERE sh.item_id = '{$db->escape($item_id)}' 
            AND sh.change_type = 'stock_in'
            AND sh.date_changed <= '{$db->escape($selected_date)} 23:59:59'
            ORDER BY sh.date_changed ASC
        ");
        
        return $stock_details ?: [];
    } else {
        // For end date: get current stock and recent transactions
        $stock_details = find_by_sql("
            SELECT 
                sh.change_type,
                sh.previous_qty,
                sh.new_qty,
                sh.date_changed,
                sh.remarks
            FROM stock_history sh
            WHERE sh.item_id = '{$db->escape($item_id)}' 
            AND sh.date_changed <= '{$db->escape($selected_date)} 23:59:59'
            ORDER BY sh.date_changed DESC
            LIMIT 10
        ");
        
        return $stock_details ?: [];
    }
}

// ✅ Function to calculate quantity for semi-expendable properties based on date
function calculate_semi_exp_qty_for_date($semi_id, $selected_date, $is_start_date = true) {
    global $db;
    
    if ($is_start_date) {
        // For start date: total quantity (assuming initial quantity is the total)
        $semi_item = find_by_sql("SELECT total_qty FROM semi_exp_prop WHERE id = '{$db->escape($semi_id)}' LIMIT 1");
        return $semi_item ? ($semi_item[0]['total_qty'] ?? 0) : 0;
    } else {
        // For end date: remaining quantity
        $semi_item = find_by_sql("SELECT qty_left FROM semi_exp_prop WHERE id = '{$db->escape($semi_id)}' LIMIT 1");
        return $semi_item ? ($semi_item[0]['qty_left'] ?? 0) : 0;
    }
}

// ✅ Function to calculate quantity for properties based on date
function calculate_property_qty_for_date($property_id, $selected_date, $is_start_date = true) {
    global $db;
    
    // Properties typically don't change quantity, so return the fixed quantity
    $property = find_by_sql("SELECT qty FROM properties WHERE id = '{$db->escape($property_id)}' LIMIT 1");
    return $property ? ($property[0]['qty'] ?? 0) : 0;
}

// ✅ SEMI-EXPENDABLES - Corrected query (no connection to item_stocks_per_year)
$sql_semi = "
  SELECT 
    sep.*, 
    sc.semicategory_name
  FROM semi_exp_prop sep
  JOIN transactions t ON sep.id = t.item_id
  LEFT JOIN semicategories sc ON sep.semicategory_id = sc.id
 WHERE 
        (t.transaction_type = 'issue' AND t.ICS_No IS NOT NULL)
        OR t.status = 'Partially Re-Issued'
  GROUP BY sep.id
";

// ✅ PROPERTIES - Corrected query (no connection to item_stocks_per_year)
$sql_props = "
  SELECT 
    p.*, 
    s.subcategory_name
  FROM properties p
  JOIN transactions t ON p.id = t.item_id
  LEFT JOIN subcategories s ON p.subcategory_id = s.id
  WHERE t.transaction_type = 'issue'
    AND t.PAR_No IS NOT NULL
  GROUP BY p.id
";

// ✅ REGULAR ITEMS - Updated to handle semester dates
$sql = "
  SELECT 
    i.*, 
    c.name AS category_name,
    un.symbol AS unit_name,
    ispy.stock as start_stock,
    ispy_end.stock as end_stock
  FROM items i
  JOIN request_items ri ON ri.item_id = i.id
  JOIN units un ON i.unit_id = un.id
  JOIN requests r ON r.id = ri.req_id
  LEFT JOIN categories c ON i.categorie_id = c.id
  LEFT JOIN item_stocks_per_year ispy ON i.id = ispy.item_id 
    AND ispy.school_year_id = (SELECT id FROM school_years WHERE is_current = 1)
  LEFT JOIN item_stocks_per_year ispy_end ON i.id = ispy_end.item_id 
    AND ispy_end.school_year_id = (SELECT id FROM school_years WHERE is_current = 1)
  WHERE 1=1
";

// Initialize with empty arrays to prevent undefined variable warnings
$props = [];
$semi_items = [];
$items = [];

try {
    $props = find_by_sql($sql_props) ?: [];
    $semi_items = find_by_sql($sql_semi) ?: [];
    $items = find_by_sql($sql) ?: [];
} catch (Exception $e) {
    // Log error and ensure variables are arrays
    error_log("Database error: " . $e->getMessage());
    $props = [];
    $semi_items = [];
    $items = [];
}

// Process items to calculate dynamic stock based on selected date
$processed_items = [];
$selected_date = isset($_POST['date_added']) ? $_POST['date_added'] : '';
$is_start_date_selected = ($selected_date == $semester_start_date);

foreach ($items as $item) {
    $item_id = $item['id'];
    
    if ($selected_date) {
        if ($selected_date == $semester_start_date) {
            // Calculate stock for start date (total stock_in)
            $item['calculated_stock'] = calculate_stock_for_date($item_id, $selected_date, true);
            $item['stock_details'] = get_stock_details($item_id, $selected_date, true);
        } elseif ($selected_date == $semester_end_date) {
            // Calculate stock for end date (remaining quantity)
            $item['calculated_stock'] = calculate_stock_for_date($item_id, $selected_date, false);
            $item['stock_details'] = get_stock_details($item_id, $selected_date, false);
        } else {
            // Default to current stock if no specific date selected
            $item['calculated_stock'] = $item['start_stock'] ?? 0;
            $item['stock_details'] = [];
        }
    } else {
        // Default to current stock if no date selected
        $item['calculated_stock'] = $item['start_stock'] ?? 0;
        $item['stock_details'] = [];
    }
    
    $processed_items[] = $item;
}

$items = $processed_items;

// Process semi-expendable items to calculate quantities based on selected date
$processed_semi_items = [];
foreach ($semi_items as $item) {
    $semi_id = $item['id'];
    
    if ($selected_date) {
        if ($selected_date == $semester_start_date) {
            // For start date: use total quantity
            $item['calculated_qty'] = calculate_semi_exp_qty_for_date($semi_id, $selected_date, true);
        } elseif ($selected_date == $semester_end_date) {
            // For end date: use remaining quantity
            $item['calculated_qty'] = calculate_semi_exp_qty_for_date($semi_id, $selected_date, false);
        } else {
            // Default to current quantity
            $item['calculated_qty'] = $item['qty_left'] ?? $item['total_qty'] ?? 0;
        }
    } else {
        // Default to current quantity
        $item['calculated_qty'] = $item['qty_left'] ?? $item['total_qty'] ?? 0;
    }
    
    $processed_semi_items[] = $item;
}

$semi_items = $processed_semi_items;

// Process property items to calculate quantities based on selected date
$processed_props = [];
foreach ($props as $item) {
    $property_id = $item['id'];
    
    if ($selected_date) {
        // Properties typically have fixed quantities
        $item['calculated_qty'] = calculate_property_qty_for_date($property_id, $selected_date);
    } else {
        // Default to current quantity
        $item['calculated_qty'] = $item['qty'] ?? 0;
    }
    
    $processed_props[] = $item;
}

$props = $processed_props;
?>

<?php include_once('layouts/header.php'); ?>

<style>
  :root {
    --primary: #28a745;
    --primary-dark: #1e7e34;
    --primary-light: #34ce57;
    --secondary: #6c757d;
    --light: #f8f9fa;
    --dark: #343a40;
    --border-radius: 10px;
  }

  .card-container {
    max-width: 1300px;
    margin: 0 auto;
  }

  .card-header-custom {
    border-top: 5px solid green;
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    padding: 1rem 1.5rem;
  }

  .registry-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.3rem;
    font-weight: 700;
    text-align: center;
    margin: 0;
    line-height: 1.3;
  }

  .tabs-container {
    background: white;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .nav-tabs-custom {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 2px solid #e9ecef;
    padding: 0;
    margin: 0;
  }

  .nav-tab-item {
    flex: 1;
    min-width: 200px;
    text-align: center;
  }

  .nav-tab-link {
    display: block;
    padding: 1rem 1.5rem;
    background-color: #ffffffff;
    color: var(--secondary);
    text-decoration: none;
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 600;
    position: relative;
    overflow: hidden;
  }

  .nav-tab-link:hover {
    background-color: #e9ecef;
    color: var(--primary-dark);
  }

  .nav-tab-link.active {
    background-color: #ccffd9ff;
    color: var(--primary);
    border-bottom: 3px solid var(--primary);
  }

  .nav-tab-link.active:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--primary);
  }

  .tab-icon {
    margin-right: 8px;
    font-size: 1.1rem;
  }

  .tab-content {
    padding: 2rem;
    background: white;
    min-height: 300px;
  }

  .tab-pane {
    display: none;
    animation: fadeIn 0.5s ease;
  }

  .tab-pane.active {
    display: block;
  }

  .tab-description {
    color: var(--secondary);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    line-height: 1.6;
    text-align: center;
  }

  .btn-tab-action {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .btn-tab-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
    color: white;
  }

  .action-container {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
  }

  .stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: #f8f9fa;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-align: center;
    border-left: 4px solid var(--primary);
    transition: transform 0.3s ease;
  }

  .stat-card:hover {
    transform: translateY(-5px);
  }

  .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
  }

  .stat-label {
    color: var(--secondary);
    font-size: 0.9rem;
  }

  /* RPCI Form Styles */
  .rpci-form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 2rem;
  }

  .rpci-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .rpci-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .rpci-subtitle {
    font-style: italic;
    color: var(--secondary);
  }

  .form-section {
    margin-bottom: 2rem;
  }

  .form-section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--primary-dark);
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
  }

  .form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -0.5rem;
  }

  .form-group {
    flex: 1;
    min-width: 200px;
    padding: 0 0.5rem;
    margin-bottom: 1rem;
  }

  .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark);
  }

  .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .form-control:focus {
    border-color: var(--primary);
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
  }

  .table-responsive {
    overflow-x: auto;
  }

  .rpci-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
  }

  .rpci-table th,
  .rpci-table td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: left;
  }

  .rpci-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  .signature-section {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-top: 2rem;
  }

  .signature-box {
    flex: 1;
    min-width: 200px;
    margin: 0 0.5rem 1.5rem;
    text-align: center;
  }

  .signature-line {
    border-top: 1px solid #000;
    margin-top: 5px;
    padding-top: 0.5rem;
  }

  .btn-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
  }

  .btn-secondary {
    background: var(--secondary);
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-secondary:hover {
    background: #5a6268;
    color: white;
  }

  .underline {
    border-bottom: 1px solid #000;
    display: inline-block;
    min-width: 150px;
    text-align: center;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  @media (max-width: 768px) {
    .nav-tabs-custom {
      flex-direction: column;
    }

    .nav-tab-item {
      min-width: 100%;
    }

    .registry-title {
      font-size: 1.1rem;
    }

    .tab-content {
      padding: 1.5rem;
    }

    .card-header-custom {
      padding: 0.8rem 1rem;
    }

    .signature-section {
      flex-direction: column;
    }

    .signature-box {
      margin-bottom: 2rem;
    }
  }

  /* RPCSP Form Styles */
  .rpcsp-form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 2rem;
  }

  .rpcsp-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .rpcsp-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .rpcsp-subtitle {
    font-style: italic;
    color: var(--secondary);
  }

  .rpcsp-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
  }

  .rpcsp-table th,
  .rpcsp-table td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: center;
  }

  .rpcsp-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  /* RPCSPPE Form Styles */
  .rpcppe-form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 2rem;
  }

  .rpcppe-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .rpcppe-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .rpcppe-subtitle {
    font-style: italic;
    color: var(--secondary);
    margin-bottom: 0.5rem;
  }

  .rpcppe-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
  }

  .rpcppe-table th,
  .rpcppe-table td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: center;
    vertical-align: middle;
  }

  .rpcppe-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  /* Multi-signature styles */
  .multi-signature-section {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-top: 2rem;
  }

  .multi-signature-column {
    flex: 1;
    min-width: 200px;
    margin: 0 0.5rem 1.5rem;
  }

  .multi-signature-box {
    text-align: center;
    margin-bottom: 1.5rem;
  }

  .multi-signature-line {
    border-top: 1px solid #000;
    margin-top: 5px;
    padding-top: 0.5rem;
    min-height: 60px;
  }

  .multi-signature-caption {
    font-size: 0.8rem;
    margin-top: 3px;
    line-height: 1.2;
  }
</style>

<div class="card-container mt-3">
  <div class="card shadow-sm border-0">
    <div class="card-header-custom">
      <h3 class="registry-title">REPORTS ON PHYSICAL COUNT OF:</h3>
    </div>

    <div class="tabs-container">
      <ul class="nav-tabs-custom" id="registryTabs">
        <li class="nav-tab-item">
          <a href="#inventories" class="nav-tab-link <?= $active_tab === 'inventories' ? 'active' : '' ?>" data-tab="inventories">
            <i class="fas fa-boxes tab-icon"></i> Inventories
          </a>
        </li>
        <li class="nav-tab-item">
          <a href="#property" class="nav-tab-link <?= $active_tab === 'property' ? 'active' : '' ?>" data-tab="property">
            <i class="fas fa-building tab-icon"></i> Property, Plant & Equipment
          </a>
        </li>
        <li class="nav-tab-item">
          <a href="#semi-expendable" class="nav-tab-link <?= $active_tab === 'semi-expendable' ? 'active' : '' ?>" data-tab="semi-expendable">
            <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property
          </a>
        </li>
      </ul>

      <div class="tab-content">
        <!-- Inventories Tab -->
        <div id="inventories" class="tab-pane <?= $active_tab === 'inventories' ? 'active' : '' ?>">
          <!-- RPCI Form Section -->
          <div class="rpci-form-container">
            <div class="rpci-header">
              <h2 class="rpci-title">REPORT ON THE PHYSICAL COUNT OF INVENTORIES</h2>
            </div>

            <form method="post" action="" id="filter-form">
              <!-- Hidden field to track active tab -->
              <input type="hidden" name="active_tab" value="inventories" id="active_tab">
              
              <!-- Category and Date Selection -->
              <div class="form-section">
                <h4 class="form-section-title">Filter</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control filter-input" name="categorie_id" id="categorie_id" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Categories</option>
                      <?php
                      $categories = find_by_sql("SELECT id, name FROM categories ORDER BY name ASC");
                      foreach ($categories as $cat) {
                        $selected = (isset($_POST['categorie_id']) && $_POST['categorie_id'] == $cat['id']) ? 'selected' : '';
                        echo "<option value=\"{$cat['id']}\" $selected>{$cat['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">As at</label>
                    <select class="form-control filter-input" name="date_added" id="date_added" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">Select Semester Date</option>
                      <option value="<?= $semester_start_date ?>" <?= (isset($_POST['date_added']) && $_POST['date_added'] == $semester_start_date) ? 'selected' : '' ?>>
                        Start of Semester (<?= $start_date_display ?>)
                      </option>
                      <option value="<?= $semester_end_date ?>" <?= (isset($_POST['date_added']) && $_POST['date_added'] == $semester_end_date) ? 'selected' : '' ?>>
                        End of Semester (<?= $end_date_display ?>)
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Fund Cluster</label>
                    <select class="form-control filter-input" name="fund_cluster" id="fund_cluster" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Fund Clusters</option>
                      <?php
                      $clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
                      foreach ($clusters as $cluster) {
                        $selected = (isset($_POST['fund_cluster']) && $_POST['fund_cluster'] == $cluster['name']) ? 'selected' : '';
                        echo "<option value=\"{$cluster['name']}\" $selected>{$cluster['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div style="margin-bottom: 15px; line-height: 1.8;">
                  <strong>For which</strong>
                  <span class="underline" style="min-width: 180px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
                  <span class="underline" style="min-width: 150px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
                  BSU-BOKOD CAMPUS is accountable, having assumed such accountability on
                  <input type="date" class="form-control filter-input" name="assumption_date" id="assumption_date" value="<?php echo isset($_POST['assumption_date']) ? $_POST['assumption_date'] : date('Y-m-d'); ?>" style="display:inline-block; width:auto; min-width:150px; border:none; border-bottom: 1px solid #000; background:transparent;">
                </div>
              </div>

              <!-- Inventory Table -->
              <div class="form-section">
                <h4 class="form-section-title">Inventory Items</h4>
                <div class="table-responsive">
                  <table class="rpci-table">
                    <thead>
                      <tr>
                        <th>Article</th>
                        <th>Description</th>
                        <th>Stock Number</th>
                        <th>Unit of Measure</th>
                        <th>Unit Value</th>
                        <th>Balance Per Card (Quantity)</th>
                        <th>On Hand Per Count (Quantity)</th>
                        <th>Shortage/Overage Quantity</th>
                        <th>Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($items as $item): ?>
                        <tr
                          data-category="<?php echo $item['categorie_id']; ?>"
                          data-date="<?php echo substr($item['date_added'], 0, 10); ?>"
                          data-fund-cluster="<?php echo $item['fund_cluster'] ?? ''; ?>">
                          <td><?php echo $item['id']; ?></td>
                          <td><?php echo $item['name']; ?></td>
                          <td><?php echo $item['stock_card']; ?></td>
                          <td><?php echo $item['unit_name']; ?></td>
                          <td><?php echo $item['unit_cost']; ?></td>
                          <td class="balance-per-card"><?php echo $item['calculated_stock']; ?></td>
                          <td class="on-hand-count"></td>
                          <td class="shortage-overage"></td>
                          <td class="remarks"></td>
                        </tr>
                      <?php endforeach; ?>

                      <!-- Add empty rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                      <?php endfor; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Certifications Section -->
              <div class="form-section">
                <h4 class="form-section-title">Certifications</h4>
                <div class="signature-section">
                  <!-- Certified Correct by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Certified Correct by:</label>
                      <select class="form-control signature-select" name="certified_correct_by" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Inventory Committee Chair/Member</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['certified_correct_by']) && $_POST['certified_correct_by'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Inventory Committee Chair and Members</p>
                  </div>

                  <!-- Approved by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Approved by:</label>
                      <select class="form-control signature-select" name="approved_by" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Head of Agency/Entity</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['approved_by']) && $_POST['approved_by'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Head of Agency/Entity or Authorized Representative</p>
                  </div>

                  <!-- Verified by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Verified by:</label>
                      <select class="form-control signature-select" name="verified_by" style="height: 47px; font-size: 1rem;">
                        <option value="">Select COA Representative</option>
                        <?php
                        $coa_reps = find_by_sql("SELECT id, name FROM signatories WHERE position = 'COA Representative' ORDER BY name ASC");
                        foreach ($coa_reps as $rep) {
                          $selected = (isset($_POST['verified_by']) && $_POST['verified_by'] == $rep['id']) ? 'selected' : '';
                          echo "<option value=\"{$rep['id']}\" $selected>{$rep['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of COA Representative</p>
                  </div>
                </div>
              </div>

              <div class="btn-group">
                <button type="submit" name="add_inventory_item" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save Inventory Report
                </button>
                <button type="button" class="btn btn-secondary" id="print-report">
                  <i class="fas fa-print"></i> Print Report
                </button>
                <button type="reset" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Reset Form
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Property, Plant & Equipment Tab -->
        <div id="property" class="tab-pane <?= $active_tab === 'property' ? 'active' : '' ?>">
          <!-- RPCSPPE Form Section -->
          <div class="rpcppe-form-container">
            <div class="rpcppe-header">
              <h2 class="rpcppe-title">REPORT ON PHYSICAL COUNT OF PROPERTY, PLANT, AND EQUIPMENT (RPCSPPE)</h2>
            </div>

            <form method="post" action="" id="filter-form-ppe">
              <!-- Hidden field to track active tab -->
              <input type="hidden" name="active_tab" value="property" id="active_tab_ppe">
              
              <!-- Category and Date Selection -->
              <div class="form-section">
                <h4 class="form-section-title">Filter</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control filter-input" name="ppe_category_id" id="ppe_category_id" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Categories</option>
                      <?php
                      $ppe_categories = find_by_sql("SELECT id, subcategory_name FROM subcategories ORDER BY subcategory_name ASC");
                      foreach ($ppe_categories as $cat) {
                        $selected = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] == $cat['id']) ? 'selected' : '';
                        echo "<option value=\"{$cat['id']}\" $selected>{$cat['subcategory_name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">As at</label>
                    <select class="form-control filter-input" name="ppedate_added" id="ppedate_added" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">Select Semester Date</option>
                      <option value="<?= $semester_start_date ?>" <?= (isset($_POST['ppedate_added']) && $_POST['ppedate_added'] == $semester_start_date) ? 'selected' : '' ?>>
                        Start of Semester (<?= $start_date_display ?>)
                      </option>
                      <option value="<?= $semester_end_date ?>" <?= (isset($_POST['ppedate_added']) && $_POST['ppedate_added'] == $semester_end_date) ? 'selected' : '' ?>>
                        End of Semester (<?= $end_date_display ?>)
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Fund Cluster</label>
                    <select class="form-control filter-input" name="ppefund_cluster" id="ppefund_cluster" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Fund Clusters</option>
                      <?php
                      $ppefund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
                      foreach ($ppefund_clusters as $cluster) {
                        $selected = (isset($_POST['fund_cluster']) && $_POST['fund_cluster'] == $cluster['name']) ? 'selected' : '';
                        echo "<option value=\"{$cluster['name']}\" $selected>{$cluster['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div style="margin-bottom: 15px; line-height: 1.8;">
                  <strong>For which:</strong>
                  <span class="underline" style="min-width: 180px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
                  <span class="underline" style="min-width: 150px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
                  is accountable, having assumed accountability on
                  <input type="date" class="form-control filter-input" name="assumption_date_ppe" id="assumption_date_ppe" value="<?php echo isset($_POST['assumption_date_ppe']) ? $_POST['assumption_date_ppe'] : date('Y-m-d'); ?>" style="display:inline-block; width:auto; min-width:150px; border:none; border-bottom: 1px solid #000; background:transparent;">
                </div>
              </div>

              <!-- Property, Plant & Equipment Table -->
              <div class="form-section">
                <h4 class="form-section-title">Property, Plant & Equipment Items</h4>
                <div class="table-responsive">
                  <table class="rpcppe-table">
                    <thead>
                      <tr>
                        <th>Date Acquired</th>
                        <th>Property Number</th>
                        <th>Unit</th>
                        <th>ARTICLE</th>
                        <th>Description</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                        <th>Quantity per Card</th>
                        <th>Quantity Per Physical Count</th>
                        <th>Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($props as $item): ?>
                        <tr
                          data-category-ppe="<?php echo $item['subcategory_id'] ?? ''; ?>"
                          data-date-ppe="<?php echo !empty($item['date_acquired']) ? substr($item['date_acquired'], 0, 10) : ''; ?>"
                          data-fund-cluster-ppe="<?php echo $item['fund_cluster'] ?? ''; ?>">

                          <td>
                            <?php
                            echo !empty($item['date_acquired'])
                              ? date('d-M-y', strtotime($item['date_acquired']))
                              : '-';
                            ?>
                          </td>
                          <td><?php echo $item['property_no']; ?></td>
                          <td><?php echo $item['unit']; ?></td>
                          <td><?php echo $item['article']; ?></td>
                          <td><?php echo $item['description']; ?></td>
                          <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                          <td>₱<?php echo number_format($item['unit_cost'] * $item['qty'], 2); ?></td>
                          <td>
                          <td>
                          <td><?php echo $item['remarks'] ?? ''; ?></td>
                        </tr>
                      <?php endforeach; ?>

                      <!-- Add empty rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                      <?php endfor; ?>

                      <!-- Total Row -->
                      <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL: </td>
                        <td>₱<?php
                              $total_amount = 0;
                              foreach ($semi_items as $item) {
                                $line_total = $item['unit_cost'] * $item['total_qty']; // or $item['total_qty']
                                $total_amount += $line_total;
                              }
                              echo number_format($total_amount, 2);
                              ?></td>

                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Multi-Signature Certifications Section -->
              <div class="form-section">
                <h4 class="form-section-title">Certifications</h4>
                <div class="multi-signature-section">
                  <!-- Left Column - Certified Correct (6 signatories) -->
                  <div class="multi-signature-column">
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <label class="form-label">Certified Correct by:</label>
                      </div>
                      <div class="multi-signature-line"></div>
                      <p class="multi-signature-caption">Signature over Printed Name of IC Chair and Members</p>
                    </div>

                    <!-- Certified Correct Signatory 1 -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="certified_correct_1_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Committee Member</option>
                          <?php
                          $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_1_ppe']) && $_POST['certified_correct_1_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>

                    <!-- Certified Correct Signatory 2 -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="certified_correct_2_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Committee Member</option>
                          <?php
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_2_ppe']) && $_POST['certified_correct_2_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>

                    <!-- Certified Correct Signatory 3 -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="certified_correct_3_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Committee Member</option>
                          <?php
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_3_ppe']) && $_POST['certified_correct_3_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>
                  </div>

                  <!-- Middle Column - Certified Correct (continued) -->
                  <div class="multi-signature-column">
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <label class="form-label">Certified Correct by (cont.):</label>
                      </div>
                      <div class="multi-signature-line"></div>
                      <p class="multi-signature-caption">Signature over Printed Name of IC Chair and Members</p>

                    </div>

                    <!-- Certified Correct Signatory 4 -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="certified_correct_4_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Committee Member</option>
                          <?php
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_4_ppe']) && $_POST['certified_correct_4_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>

                    <!-- Certified Correct Signatory 5 -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="certified_correct_5_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Committee Vice Chair</option>
                          <?php
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_5_ppe']) && $_POST['certified_correct_5_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>

                    <!-- Certified Correct Signatory 6 -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="certified_correct_6_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Committee Chair</option>
                          <?php
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_6_ppe']) && $_POST['certified_correct_6_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>
                  </div>

                  <!-- Right Column - Approved by and Verified by -->
                  <div class="multi-signature-column">
                    <!-- Approved by Section -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <label class="form-label">Approved by:</label>
                      </div>
                      <!-- <div class="multi-signature-line"></div> -->
                      <p class="multi-signature-caption">Signature over Printed Name of Head of Agency/Entity</p>
                    </div>

                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="approved_by_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select Head of Agency</option>
                          <?php
                          foreach ($signatories as $sign) {
                            $selected = (isset($_POST['approved_by_ppe']) && $_POST['approved_by_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>

                    <!-- Spacer between sections -->

                    <!-- Verified by Section -->
                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <label class="form-label">Verified by:</label>
                      </div>
                      <!-- <div class="multi-signature-line"></div> -->
                      <p class="multi-signature-caption">Signature over Printed Name of COA Representative</p>
                    </div>

                    <div class="multi-signature-box">
                      <div class="signature-field">
                        <select class="form-control signature-select" name="verified_by_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                          <option value="">Select COA Representative</option>
                          <?php
                          $coa_reps = find_by_sql("SELECT id, name FROM signatories WHERE position = 'COA Representative' ORDER BY name ASC");
                          foreach ($coa_reps as $rep) {
                            $selected = (isset($_POST['verified_by_ppe']) && $_POST['verified_by_ppe'] == $rep['id']) ? 'selected' : '';
                            echo "<option value=\"{$rep['id']}\" $selected>{$rep['name']}</option>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="multi-signature-line"></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="btn-group">
                <button type="submit" name="add_ppe_item" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save PPE Report
                </button>
                <button type="button" class="btn btn-secondary" id="print-report-ppe">
                  <i class="fas fa-print"></i> Print Report
                </button>
                <button type="reset" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Reset Form
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Semi-Expendable Property Tab -->
        <div id="semi-expendable" class="tab-pane <?= $active_tab === 'semi-expendable' ? 'active' : '' ?>">
          <!-- RPCSP Form Section -->
          <div class="rpcsp-form-container">
            <div class="rpcsp-header">
              <h2 class="rpcsp-title">REPORT ON THE PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY (RPCSP)</h2>
            </div>

            <form method="post" action="" id="filter-form-semi-expendable">
              <!-- Hidden field to track active tab -->
              <input type="hidden" name="active_tab" value="semi-expendable" id="active_tab_semi">
              
              <!-- Category and Date Selection -->
              <div class="form-section">
                <h4 class="form-section-title">Filter</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control filter-input" name="semicategory_id" id="semicategory_id" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Categories</option>
                      <?php
                      $semicategories = find_by_sql("SELECT id, semicategory_name FROM semicategories ORDER BY semicategory_name ASC");
                      foreach ($semicategories as $semicat) {
                        $selected = (isset($_POST['semicategory_id']) && $_POST['semicategory_id'] == $semicat['id']) ? 'selected' : '';
                        echo "<option value=\"{$semicat['id']}\" $selected>{$semicat['semicategory_name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">As at</label>
                    <select class="form-control filter-input" name="smpdate_added" id="smpdate_added" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">Select Semester Date</option>
                      <option value="<?= $semester_start_date ?>" <?= (isset($_POST['smpdate_added']) && $_POST['smpdate_added'] == $semester_start_date) ? 'selected' : '' ?>>
                        Start of Semester (<?= $start_date_display ?>)
                      </option>
                      <option value="<?= $semester_end_date ?>" <?= (isset($_POST['smpdate_added']) && $_POST['smpdate_added'] == $semester_end_date) ? 'selected' : '' ?>>
                        End of Semester (<?= $end_date_display ?>)
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Fund Cluster</label>
                    <select class="form-control filter-input" name="smpfund_cluster" id="smpfund_cluster" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Fund Clusters</option>
                      <?php
                      $smpclusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
                      foreach ($smpclusters as $smpcluster) {
                        $selected = (isset($_POST['smpfund_cluster']) && $_POST['smpfund_cluster'] == $smpcluster['name']) ? 'selected' : '';
                        echo "<option value=\"{$smpcluster['name']}\" $selected>{$smpcluster['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Value Type</label>
                    <select class="form-control filter-input" name="value_type" id="value_type" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Values</option>
                      <option value="low" <?php echo (isset($_POST['value_type']) && $_POST['value_type'] == 'low') ? 'selected' : ''; ?>>Low Value (Below ₱5,000)</option>
                      <option value="high" <?php echo (isset($_POST['value_type']) && $_POST['value_type'] == 'high') ? 'selected' : ''; ?>>High Value (₱5,000 - ₱50,000)</option>
                    </select>
                  </div>
                </div>
                <div style="margin-bottom: 15px; line-height: 1.8;">
                  <strong>For which</strong>
                  <span class="underline" style="min-width: 180px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
                  <span class="underline" style="min-width: 150px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
                  BSU-BOKOD CAMPUS is accountable, having assumed such accountability on
                  <input type="date" class="form-control filter-input" name="assumption_date_semi" id="assumption_date_semi" value="<?php echo isset($_POST['assumption_date_semi']) ? $_POST['assumption_date_semi'] : date('Y-m-d'); ?>" style="display:inline-block; width:auto; min-width:150px; border:none; border-bottom: 1px solid #000; background:transparent;">
                </div>
              </div>

              <!-- Semi-Expendable Property Table -->
              <div class="form-section">
                <h4 class="form-section-title">Semi-Expendable Property Items</h4>
                <div class="table-responsive">
                  <table class="rpcsp-table">
                    <thead>
                      <tr>
                        <th rowspan="2">ARTICLE</th>
                        <th rowspan="2">Description</th>
                        <th rowspan="2">Semi-expendable Property No.</th>
                        <th rowspan="2">Unit of Measure</th>
                        <th rowspan="2">Unit Value</th>
                        <th colspan="1">Balance per Card</th>
                        <th colspan="1">On Hand Per Count</th>
                        <th colspan="2">Shortage/Overage</th>
                        <th rowspan="2">Remarks</th>
                      </tr>
                      <tr>
                        <th>Qty</th>
                        <th>Qty</th>
                        <th>Qty</th>
                        <th>Value</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($semi_items as $item): ?>
                        <tr
                          data-category-smp="<?php echo $item['semicategory_id']; ?>"
                          data-date-smp="<?php echo substr($item['date_added'], 0, 10); ?>"
                          data-fund-cluster-smp="<?php echo $item['fund_cluster'] ?? ''; ?>"
                          data-value-type="<?php echo ($item['unit_cost'] < 5000) ? 'low' : (($item['unit_cost'] >= 5000 && $item['unit_cost'] < 50000) ? 'high' : ''); ?>">
                          <td><?php echo $item['id']; ?></td>
                          <td><?php echo $item['item_description']; ?></td>
                          <td><?php echo $item['inv_item_no']; ?></td>
                          <td><?php echo $item['unit']; ?></td>
                          <td><?php echo $item['total_qty']; ?></td>
                          <td>-</td>
                          <td>₱0.00</td>
                          <td>-</td>
                          <td>₱0.00</td>
                          <td></td>
                        </tr>
                      <?php endforeach; ?>

                      <!-- Add empty rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                      <?php endfor; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Certifications Section -->
              <div class="form-section">
                <h4 class="form-section-title">Certifications</h4>
                <div class="signature-section">
                  <!-- Certified Correct by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Certified Correct by:</label>
                      <select class="form-control signature-select" name="certified_correct_by_semi" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Inventory Committee Chair/Member</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['certified_correct_by_semi']) && $_POST['certified_correct_by_semi'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Inventory Committee Chair and Members</p>
                  </div>

                  <!-- Approved by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Approved by:</label>
                      <select class="form-control signature-select" name="approved_by_semi" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Head of Agency/Entity</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['approved_by_semi']) && $_POST['approved_by_semi'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Head of Agency/Entity or Authorized Representative</p>
                  </div>

                  <!-- Witnessed by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Witnessed by:</label>
                      <select class="form-control signature-select" name="witnessed_by_semi" style="height: 47px; font-size: 1rem;">
                        <option value="">Select COA Representative</option>
                        <?php
                        $coa_reps = find_by_sql("SELECT id, name FROM signatories WHERE position = 'COA Representative' ORDER BY name ASC");
                        foreach ($coa_reps as $rep) {
                          $selected = (isset($_POST['witnessed_by_semi']) && $_POST['witnessed_by_semi'] == $rep['id']) ? 'selected' : '';
                          echo "<option value=\"{$rep['id']}\" $selected>{$rep['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of COA Representative</p>
                  </div>
                </div>
              </div>

              <div class="btn-group">
                <button type="submit" name="add_semi_expendable_item" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save Semi-Expendable Report
                </button>
                <button type="button" class="btn btn-secondary" id="print-report-semi">
                  <i class="fas fa-print"></i> Print Report
                </button>
                <button type="reset" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Reset Form
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Hidden div for print preview -->
<div id="print-preview" style="display:none;"></div>

<?php include_once('layouts/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// For Inventories tab
document.getElementById('print-report').addEventListener('click', function() {
    const categorySelect = document.querySelector('select[name="categorie_id"]');
    const dateInput = document.querySelector('select[name="date_added"]');
    const fundClusterSelect = document.querySelector('select[name="fund_cluster"]');
    const assumptionDateInput = document.querySelector('input[name="assumption_date"]');
    const certifiedCorrect = document.querySelector('select[name="certified_correct_by"]');
    const approvedBy = document.querySelector('select[name="approved_by"]');
    const verifiedBy = document.querySelector('select[name="verified_by"]');

    // Validation
    if (!categorySelect.value) {
        Swal.fire('Missing Category', 'Please select a category before printing.', 'warning');
        return;
    }
    if (!dateInput.value) {
        Swal.fire('Missing Date', 'Please select a date before printing.', 'warning');
        return;
    }
    if (!fundClusterSelect.value) {
        Swal.fire('Missing Fund Cluster', 'Please select a fund cluster before printing.', 'warning');
        return;
    }
    if (!assumptionDateInput.value) {
        Swal.fire('Missing Assumption Date', 'Please select an assumption date before printing.', 'warning');
        return;
    }
    if (!certifiedCorrect.value || !approvedBy.value || !verifiedBy.value) {
        Swal.fire('Missing Certifications', 'Please select all required certifications before printing the report.', 'warning');
        return;
    }

    // Submit form to printable page
    const form = document.getElementById('filter-form');
    form.target = '_blank';
    form.action = 'rpci_print.php';
    form.submit();
});

// Auto-calculation for on-hand count changes
document.addEventListener('DOMContentLoaded', function() {
    const onHandCells = document.querySelectorAll('.on-hand-count');
    
    onHandCells.forEach(cell => {
        cell.addEventListener('input', function() {
            const row = this.closest('tr');
            const balanceCell = row.querySelector('.balance-per-card');
            const shortageCell = row.querySelector('.shortage-overage');
            const remarksCell = row.querySelector('.remarks');
            
            const balanceQty = parseInt(balanceCell.textContent) || 0;
            const onHandQty = parseInt(this.textContent) || 0;
            const difference = onHandQty - balanceQty;
            
            shortageCell.textContent = difference;
            
            // Set remarks based on difference
            if (difference > 0) {
                remarksCell.textContent = 'Overage';
                remarksCell.style.color = 'green';
            } else if (difference < 0) {
                remarksCell.textContent = 'Shortage';
                remarksCell.style.color = 'red';
            } else {
                remarksCell.textContent = 'Correct';
                remarksCell.style.color = 'blue';
            }
        });
    });
    
    // Make on-hand cells editable
    onHandCells.forEach(cell => {
        cell.setAttribute('contenteditable', 'true');
        cell.style.minWidth = '50px';
        cell.style.border = '1px dashed #ccc';
        cell.style.padding = '2px';
    });
});

// Tab switching and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.nav-tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all tabs and panes
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            const tabId = this.getAttribute('href');
            document.querySelector(tabId).classList.add('active');
        });
    });

    // ==================== INVENTORIES TAB FILTERS ====================
    const categorySelect = document.querySelector('select[name="categorie_id"]');
    const dateInput = document.querySelector('select[name="date_added"]');
    const fundClusterSelect = document.querySelector('select[name="fund_cluster"]');
    const inventoryTableRows = document.querySelectorAll('#inventories .rpci-table tbody tr');

    function filterInventoryTable() {
        const selectedCategory = categorySelect?.value || '';
        const selectedDate = dateInput?.value || '';
        const selectedFundCluster = fundClusterSelect?.value || '';

        inventoryTableRows.forEach(row => {
            const rowCategory = row.getAttribute('data-category') || '';
            const rowDate = row.getAttribute('data-date') || '';
            const rowFundCluster = row.getAttribute('data-fund-cluster') || '';
            let show = true;

            // Filter by category
            if (selectedCategory && selectedCategory !== rowCategory) {
                show = false;
            }

            // Filter by date - show items with date <= selected date
            if (selectedDate && rowDate > selectedDate) {
                show = false;
            }

            // Filter by fund cluster
            if (selectedFundCluster && selectedFundCluster !== rowFundCluster) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    // Add event listeners to inventory filter inputs
    if (categorySelect) categorySelect.addEventListener('change', filterInventoryTable);
    if (dateInput) dateInput.addEventListener('change', filterInventoryTable);
    if (fundClusterSelect) fundClusterSelect.addEventListener('change', filterInventoryTable);

    // ==================== PROPERTIES TAB FILTERS ====================
    const ppeCategorySelect = document.querySelector('select[name="ppe_category_id"]');
    const ppeDateInput = document.querySelector('select[name="ppedate_added"]');
    const ppeFundClusterSelect = document.querySelector('select[name="ppefund_cluster"]');
    const ppeTableRows = document.querySelectorAll('#property .rpcppe-table tbody tr:not(:last-child)');

    function filterPPETable() {
        const selectedCategory = ppeCategorySelect?.value || '';
        const selectedDate = ppeDateInput?.value || '';
        const selectedFundCluster = ppeFundClusterSelect?.value || '';

        ppeTableRows.forEach(row => {
            const rowCategory = row.getAttribute('data-category-ppe') || '';
            const rowDate = row.getAttribute('data-date-ppe') || '';
            const rowFundCluster = row.getAttribute('data-fund-cluster-ppe') || '';
            let show = true;

            // Filter by category
            if (selectedCategory && selectedCategory !== rowCategory) {
                show = false;
            }

            // Filter by date - show items with date <= selected date
            if (selectedDate && rowDate > selectedDate) {
                show = false;
            }

            // Filter by fund cluster
            if (selectedFundCluster && selectedFundCluster !== rowFundCluster) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    // Add event listeners to PPE filter inputs
    if (ppeCategorySelect) ppeCategorySelect.addEventListener('change', filterPPETable);
    if (ppeDateInput) ppeDateInput.addEventListener('change', filterPPETable);
    if (ppeFundClusterSelect) ppeFundClusterSelect.addEventListener('change', filterPPETable);

    // ==================== SEMI-EXPENDABLE TAB FILTERS ====================
    const semiCategorySelect = document.querySelector('select[name="semicategory_id"]');
    const semiDateInput = document.querySelector('select[name="smpdate_added"]');
    const semiFundClusterSelect = document.querySelector('select[name="smpfund_cluster"]');
    const semiValueTypeSelect = document.querySelector('select[name="value_type"]');
    const semiTableRows = document.querySelectorAll('#semi-expendable .rpcsp-table tbody tr');

    function filterSemiTable() {
        const selectedCategory = semiCategorySelect?.value || '';
        const selectedDate = semiDateInput?.value || '';
        const selectedFundCluster = semiFundClusterSelect?.value || '';
        const selectedValueType = semiValueTypeSelect?.value || '';

        semiTableRows.forEach(row => {
            const rowCategory = row.getAttribute('data-category-smp') || '';
            const rowDate = row.getAttribute('data-date-smp') || '';
            const rowFundCluster = row.getAttribute('data-fund-cluster-smp') || '';
            const rowValueType = row.getAttribute('data-value-type') || '';
            let show = true;

            // Filter by category
            if (selectedCategory && selectedCategory !== rowCategory) {
                show = false;
            }

            // Filter by date - show items with date <= selected date
            if (selectedDate && rowDate > selectedDate) {
                show = false;
            }

            // Filter by fund cluster
            if (selectedFundCluster && selectedFundCluster !== rowFundCluster) {
                show = false;
            }

            // Filter by value type
            if (selectedValueType && selectedValueType !== rowValueType) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    // Add event listeners to semi-expendable filter inputs
    if (semiCategorySelect) semiCategorySelect.addEventListener('change', filterSemiTable);
    if (semiDateInput) semiDateInput.addEventListener('change', filterSemiTable);
    if (semiFundClusterSelect) semiFundClusterSelect.addEventListener('change', filterSemiTable);
    if (semiValueTypeSelect) semiValueTypeSelect.addEventListener('change', filterSemiTable);

    // Initial filter on page load
    if (inventoryTableRows.length > 0) filterInventoryTable();
    if (ppeTableRows.length > 0) filterPPETable();
    if (semiTableRows.length > 0) filterSemiTable();
});

// For Property, Plant & Equipment tab
document.getElementById('print-report-ppe').addEventListener('click', function() {
    const categorySelect = document.querySelector('select[name="ppe_category_id"]');
    const dateInput = document.querySelector('select[name="ppedate_added"]');
    const fundClusterSelect = document.querySelector('select[name="ppefund_cluster"]');
    const assumptionDateInput = document.querySelector('input[name="assumption_date_ppe"]');

    // Validation
    if (!categorySelect.value) {
        Swal.fire('Missing Category', 'Please select a category before printing.', 'warning');
        return;
    }
    if (!dateInput.value) {
        Swal.fire('Missing Date', 'Please select a date before printing.', 'warning');
        return;
    }
    if (!fundClusterSelect.value) {
        Swal.fire('Missing Fund Cluster', 'Please select a fund cluster before printing.', 'warning');
        return;
    }
    if (!assumptionDateInput.value) {
        Swal.fire('Missing Assumption Date', 'Please select an assumption date before printing.', 'warning');
        return;
    }

    // Submit form to printable page
    const form = document.getElementById('filter-form-ppe');
    form.target = '_blank';
    form.action = 'rpcppe_print.php';
    form.submit();
});

// For Semi-Expendable Property tab
document.getElementById('print-report-semi').addEventListener('click', function() {
    const subcategorySelect = document.querySelector('select[name="semicategory_id"]');
    const dateInput = document.querySelector('select[name="smpdate_added"]');
    const fundClusterSelect = document.querySelector('select[name="smpfund_cluster"]');
    const assumptionDateInput = document.querySelector('input[name="assumption_date_semi"]');
    const certifiedCorrect = document.querySelector('select[name="certified_correct_by_semi"]');
    const approvedBy = document.querySelector('select[name="approved_by_semi"]');
    const witnessedBy = document.querySelector('select[name="witnessed_by_semi"]');

    // Validation
    if (!subcategorySelect.value) {
        Swal.fire('Missing Subcategory', 'Please select a subcategory before printing.', 'warning');
        return;
    }
    if (!dateInput.value) {
        Swal.fire('Missing Date', 'Please select a date before printing.', 'warning');
        return;
    }
    if (!fundClusterSelect.value) {
        Swal.fire('Missing Fund Cluster', 'Please select a fund cluster before printing.', 'warning');
        return;
    }
    if (!assumptionDateInput.value) {
        Swal.fire('Missing Assumption Date', 'Please select an assumption date before printing.', 'warning');
        return;
    }
    if (!certifiedCorrect.value || !approvedBy.value || !witnessedBy.value) {
        Swal.fire('Missing Certifications', 'Please select all required certifications before printing the report.', 'warning');
        return;
    }

    // Submit form to printable page
    const form = document.getElementById('filter-form-semi-expendable');
    form.target = '_blank';
    form.action = 'rpcsp_print.php';
    form.submit();
});

// Handle date change for all tabs
document.addEventListener('DOMContentLoaded', function() {
    // Handle date change for inventory tab
    const dateSelect = document.querySelector('select[name="date_added"]');
    if (dateSelect) {
        dateSelect.addEventListener('change', function() {
            // When date changes, submit the form to recalculate stocks
            const form = document.getElementById('filter-form');
            form.submit();
        });
    }

    // Handle date change for PPE tab
    const ppeDateSelect = document.querySelector('select[name="ppedate_added"]');
    if (ppeDateSelect) {
        ppeDateSelect.addEventListener('change', function() {
            const form = document.getElementById('filter-form-ppe');
            form.submit();
        });
    }

    // Handle date change for semi-expendable tab
    const semiDateSelect = document.querySelector('select[name="smpdate_added"]');
    if (semiDateSelect) {
        semiDateSelect.addEventListener('change', function() {
            const form = document.getElementById('filter-form-semi-expendable');
            form.submit();
        });
    }

    // Show loading indicator when form is submitting
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                submitBtn.disabled = true;
            }
        });
    });
});

// Update active tab when user clicks on tabs and update hidden fields
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.nav-tab-link');
    const activeTabInputs = document.querySelectorAll('input[name="active_tab"]');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const tabId = this.getAttribute('href').substring(1);
            
            // Update all hidden active_tab inputs
            activeTabInputs.forEach(input => {
                input.value = tabId;
            });
            
            // Remove active class from all tabs and panes
            document.querySelectorAll('.nav-tab-link').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>