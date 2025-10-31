<?php
$page_title = 'Reports Page';
require_once('includes/load.php');
page_require_level(1);

// Helper function to define transaction order for same-date sorting
function getTransactionOrder($type) {
    switch($type) {
        case 'carry_forward': return 1;
        case 'stock_in': return 2;
        case 'issuance': return 3;
        default: return 4;
    }
}

// =====================
// Get filter parameters
// =====================
$stock_card_input = $_GET['stock_card'] ?? null;
$fund_cluster_filter = $_GET['fund_cluster'] ?? '';
$value_filter = $_GET['value_filter'] ?? '';
$category_filter = $_GET['category'] ?? '';
$item_filter = $_GET['item'] ?? '';
$active_tab = $_GET['tab'] ?? 'sc';

// Initialize conditions array
$where_conditions = ["1=1"]; // default true

// =====================
// Apply filters dynamically
// =====================
if (!empty($fund_cluster_filter)) {
  $where_conditions[] = "s.fund_cluster = '{$db->escape($fund_cluster_filter)}'";
}

if ($value_filter === 'high') {
  $where_conditions[] = "s.unit_cost >= 5000";
} elseif ($value_filter === 'low') {
  $where_conditions[] = "s.unit_cost < 5000";
}

// =====================
// Get unique fund clusters for filter dropdown
// =====================
$fund_clusters = find_by_sql("
    SELECT DISTINCT fund_cluster 
    FROM semi_exp_prop 
    WHERE fund_cluster IS NOT NULL 
    AND fund_cluster != '' 
    ORDER BY fund_cluster
");

// =====================
// Get categories for stock card filter
// =====================
$categories = find_by_sql("
    SELECT DISTINCT c.id, c.name 
    FROM categories c
    JOIN items i ON c.id = i.categorie_id
    WHERE i.categorie_id IS NOT NULL
    ORDER BY c.name
");

// =====================
// Get items based on selected category
// =====================
$items_list = [];
if (!empty($category_filter)) {
    $items_list = find_by_sql("
        SELECT i.id, i.name, i.stock_card
        FROM items i
        WHERE i.categorie_id = '{$db->escape($category_filter)}'
        ORDER BY i.name
    ");
}

// =====================
// STOCK CARD DATA - Fetch stock history and requests data
// =====================
$stock_transactions = [];
$stock_item = null;

// If item filter is selected, use it for search
$search_input = $item_filter ?: $stock_card_input;

if (!empty($search_input)) {
    // Fetch item details first
    $item_sql = "
        SELECT 
            i.id,
            i.name AS item_name,
            i.stock_card AS stock_number,
            i.unit_cost,
            i.fund_cluster,
            ui.name AS unit_name,
            i.description,
            i.quantity AS current_balance
        FROM items i
        LEFT JOIN units ui ON i.unit_id = ui.id
        WHERE i.stock_card LIKE '%{$db->escape($search_input)}%' 
           OR i.name LIKE '%{$db->escape($search_input)}%'
        LIMIT 1
    ";
    
    $items = find_by_sql($item_sql);
    $stock_item = !empty($items) ? $items[0] : null;
    
    if ($stock_item) {
        $item_id = $stock_item['id'];
        $item_unit_cost = $stock_item['unit_cost'];
        
        // Fetch ONLY stock_in transactions from stock history
        $stock_history_sql = "
            SELECT 
                sh.id,
                sh.date_changed AS date,
                '' AS reference,
                sh.new_qty AS quantity,
                sh.previous_qty AS prev_quantity,
                i.unit_cost,
                (sh.new_qty * i.unit_cost) AS total_cost,
                sh.change_type,
                sh.remarks,
                'stock_history' AS source,
                '' AS office_name
            FROM stock_history sh
            JOIN items i ON sh.item_id = i.id
            WHERE sh.item_id = '{$db->escape($item_id)}'
            AND sh.change_type = 'stock_in'
            ORDER BY sh.date_changed ASC
        ";
        $stock_history = find_by_sql($stock_history_sql);
        
        // Fetch requests (issuances) - FIXED QUERY to include completed and issued statuses with both dates
        $issuances_sql = "
            SELECT 
                r.id,
                -- Use date_completed if available, otherwise use date_issued
                CASE 
                    WHEN r.date_completed IS NOT NULL AND r.date_completed != '0000-00-00' THEN r.date_completed
                    ELSE r.date_issued 
                END AS date,
                CONCAT('RIS-', r.ris_no) AS reference,
                ri.qty AS quantity,
                0 AS prev_quantity,
                i.unit_cost,
                (ri.qty * i.unit_cost) AS total_cost,
                'issuance' AS change_type,
                CONCAT('Issued - ', r.status) AS remarks,
                'request' AS source,
                o.office_name,
                r.date_issued,
                r.date_completed
            FROM requests r
            INNER JOIN request_items ri ON r.id = ri.req_id
            INNER JOIN items i ON ri.item_id = i.id
            LEFT JOIN users u ON r.requested_by = u.id
            LEFT JOIN offices o ON u.office = o.id
            WHERE ri.item_id = '{$db->escape($item_id)}'
            AND (r.status = 'approved' OR r.status = 'completed' OR r.status = 'issued')
            ORDER BY date ASC
        ";
        $issuances = find_by_sql($issuances_sql);
        
        // Debug: Check if issuances are being fetched
        error_log("Issuances found: " . count($issuances));
        if (!empty($issuances)) {
            error_log("First issuance - Date issued: " . ($issuances[0]['date_issued'] ?? 'No date issued') . 
                     ", Date completed: " . ($issuances[0]['date_completed'] ?? 'No date completed') . 
                     ", Final date: " . ($issuances[0]['date'] ?? 'No final date'));
        }
        
        // Fetch carry forward transactions (if they exist as separate records)
        $carry_forward_sql = "
            SELECT 
                sh.id,
                sh.date_changed AS date,
                CONCAT('CF-', YEAR(sh.date_changed)) AS reference,
                sh.new_qty AS quantity,
                sh.previous_qty AS prev_quantity,
                i.unit_cost,
                (sh.new_qty * i.unit_cost) AS total_cost,
                'carry_forward' AS change_type,
                'Carried Forward' AS remarks,
                'stock_history' AS source,
                '' AS office_name,
                NULL AS date_issued,
                NULL AS date_completed
            FROM stock_history sh
            JOIN items i ON sh.item_id = i.id
            WHERE sh.item_id = '{$db->escape($item_id)}'
            AND sh.change_type = 'carry_forward'
            ORDER BY sh.date_changed ASC
        ";
        $carry_forwards = find_by_sql($carry_forward_sql);
        
        // If no carry_forward records, check for year-end adjustments
        if (empty($carry_forwards)) {
            $year_end_sql = "
                SELECT 
                    sh.id,
                    sh.date_changed AS date,
                    CONCAT('YE-', YEAR(sh.date_changed)) AS reference,
                    sh.new_qty AS quantity,
                    sh.previous_qty AS prev_quantity,
                    i.unit_cost,
                    (sh.new_qty * i.unit_cost) AS total_cost,
                    'carry_forward' AS change_type,
                    'Year End Balance' AS remarks,
                    'stock_history' AS source,
                    '' AS office_name,
                    NULL AS date_issued,
                    NULL AS date_completed
                FROM stock_history sh
                JOIN items i ON sh.item_id = i.id
                WHERE sh.item_id = '{$db->escape($item_id)}'
                AND (sh.remarks LIKE '%carry%' OR sh.remarks LIKE '%year%')
                ORDER BY sh.date_changed ASC
            ";
            $carry_forwards = find_by_sql($year_end_sql);
        }
        
        // Add date_issued and date_completed fields to stock_history records for consistency
        foreach ($stock_history as &$history) {
            $history['date_issued'] = null;
            $history['date_completed'] = null;
        }
        
        // Combine all transactions
        $all_transactions = array_merge($stock_history, $issuances, $carry_forwards);
        
        // Debug: Check combined transactions
        error_log("Total transactions: " . count($all_transactions));
        error_log("Stock history (stock_in only): " . count($stock_history));
        error_log("Issuances: " . count($issuances));
        error_log("Carry forwards: " . count($carry_forwards));
        
        // Sort by date and time - more precise sorting
        usort($all_transactions, function($a, $b) {
            $dateA = isset($a['date']) ? strtotime($a['date']) : 0;
            $dateB = isset($b['date']) ? strtotime($b['date']) : 0;
            
            // If dates are equal, sort by transaction type to maintain logical order
            if ($dateA === $dateB) {
                $orderA = getTransactionOrder($a['change_type'] ?? '');
                $orderB = getTransactionOrder($b['change_type'] ?? '');
                return $orderA - $orderB;
            }
            
            return $dateA - $dateB;
        });
        
        // Calculate running balance - START FROM CURRENT BALANCE AND WORK BACKWARDS
        // First, get the current balance from items table
        $current_balance = $stock_item['current_balance'];
        
        // Reverse the transactions to calculate historical balances
        $reversed_transactions = array_reverse($all_transactions);
        $running_balance = $current_balance;
        $running_total_cost = $current_balance * $item_unit_cost;
        
        foreach ($reversed_transactions as &$transaction) {
            // Ensure all required fields are set
            $transaction['date'] = $transaction['date'] ?? '';
            $transaction['reference'] = $transaction['reference'] ?? '';
            $transaction['quantity'] = $transaction['quantity'] ?? 0;
            $transaction['unit_cost'] = $transaction['unit_cost'] ?? $item_unit_cost;
            $transaction['total_cost'] = $transaction['total_cost'] ?? 0;
            $transaction['change_type'] = $transaction['change_type'] ?? '';
            $transaction['remarks'] = $transaction['remarks'] ?? '';
            $transaction['office_name'] = $transaction['office_name'] ?? '';
            $transaction['date_issued'] = $transaction['date_issued'] ?? null;
            $transaction['date_completed'] = $transaction['date_completed'] ?? null;
            
            // Store the running balance for this transaction
            $transaction['running_balance'] = $running_balance;
            $transaction['running_total_cost'] = $running_balance * $transaction['unit_cost'];
            
            // Adjust running balance based on transaction type
            if ($transaction['change_type'] === 'stock_in' || $transaction['change_type'] === 'carry_forward') {
                // For stock_in, subtract the quantity to get previous balance
                $running_balance -= $transaction['quantity'];
            } elseif ($transaction['change_type'] === 'issuance') {
                // For issuance, add the quantity to get previous balance
                $running_balance += $transaction['quantity'];
            }
            
            // Debug individual transaction
            error_log("Transaction: " . $transaction['change_type'] . 
                     " - Date: " . $transaction['date'] . 
                     " - Date Issued: " . ($transaction['date_issued'] ?? 'N/A') .
                     " - Date Completed: " . ($transaction['date_completed'] ?? 'N/A') .
                     " - Qty: " . $transaction['quantity'] . 
                     " - Balance: " . $transaction['running_balance']);
        }
        
        // Reverse back to chronological order
        $stock_transactions = array_reverse($reversed_transactions);
    }
}

// =====================
// PROPERTY CARD DATA - Fetch semi-expendable items (filtered)
// =====================
$where_sql = implode(' AND ', $where_conditions);
$sql_items = "
  SELECT 
      s.id,
      s.item,
      s.item_description,
      s.inv_item_no,
      s.unit_cost,
      s.qty_left AS balance_qty,
      s.fund_cluster
  FROM semi_exp_prop s
  WHERE {$where_sql}
";
$smpi_items = find_by_sql($sql_items);

// =====================
// PROPERTY CARD - Fetch transactions for issued items
// =====================
$smpi_transactions = [];
if (!empty($smpi_items)) {
  foreach ($smpi_items as $item) {
    $item_id = (int)$item['id'];

    $transactions_sql = "
      SELECT 
          t.id AS transaction_id,
          t.transaction_type,
          t.transaction_date,
          t.quantity AS issued_qty,
          s.unit_cost,
          (t.quantity * s.unit_cost) AS total_cost,
          t.ICS_No,
          ri.RRSP_No,
          CONCAT(e.first_name, ' ', e.last_name) AS officer,
          s.item AS item_name,
          s.item_description AS item_description,
          s.inv_item_no,
          s.fund_cluster,
          s.unit_cost AS item_unit_cost,
          s.qty_left AS current_balance
      FROM transactions t
      LEFT JOIN semi_exp_prop s ON t.item_id = s.id
      LEFT JOIN employees e ON t.employee_id = e.id
      LEFT JOIN return_items ri ON t.id = ri.transaction_id
      WHERE t.item_id = '{$db->escape($item_id)}'
      ORDER BY t.transaction_date ASC
    ";

    $transactions = find_by_sql($transactions_sql);

    if (!empty($transactions)) {
      foreach ($transactions as $tx) {
        $smpi_transactions[$item_id][] = $tx;
      }
    } else {
      // No transaction record
      $smpi_transactions[$item_id][] = [
        'transaction_id' => null,
        'transaction_type' => 'None',
        'transaction_date' => null,
        'issued_qty' => 0,
        'unit_cost' => $item['unit_cost'],
        'total_cost' => 0,
        'ICS_No' => null,
        'RRSP_No' => null,
        'officer' => null,
        'item_name' => $item['item'],
        'item_description' => $item['item_description'],
        'inv_item_no' => $item['inv_item_no'],
        'fund_cluster' => $item['fund_cluster'],
        'item_unit_cost' => $item['unit_cost'],
        'current_balance' => $item['balance_qty']
      ];
    }
  }
}
?>

<?php include_once('layouts/header.php'); ?>

<style>
  .nav-tabs-custom {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 2px solid #e9ecef;
    padding: 0;
    margin: 0 0 2rem 0;
  }

  .nav-tab-item {
    flex: 1;
    min-width: 200px;
    text-align: center;
  }

  .nav-tab-link {
    display: block;
    padding: 1rem 1.5rem;
    background-color: #f8f9fa;
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
    color: var(--success-dark);
  }

  .nav-tab-link.active {
    background-color: white;
    color: var(--success);
    border-bottom: 3px solid var(--success);
    border-top: 3px solid var(--success);
  }

  .tab-icon {
    margin-right: 8px;
    font-size: 1.1rem;
  }

  .tab-content {
    padding: 0;
    background: white;
  }

  .tab-pane {
    display: none;
    animation: fadeIn 0.5s ease;
  }

  .tab-pane.active {
    display: block;
  }

  .print-btn {
    position: fixed;
    bottom: 55px;
    right: 25px;
    background: #055919ff;
    color: white;
    border: none;
    padding: 12px 18px;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.2);
    font-size: 20px;
    transition: background 0.3s ease;
    z-index: 1000;
  }

  .print-btn:hover {
    background: #155c04ff;
  }

  .filter-card {
    background: #f8f9fa;
    border-left: 4px solid #28a745;
    border-radius: 15px;
    overflow: hidden;
  }

  .rounded-filters {
    border-radius: 12px;
    overflow: hidden;
  }

  .rounded-select {
    border-radius: 8px !important;
    border: 1px solid #ced4da;
  }

  .rounded-input {
    border-radius: 8px !important;
    border: 1px solid #ced4da;
  }

  .rounded-button {
    border-radius: 8px !important;
  }

  .clickable-row {
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .clickable-row:hover {
    background-color: #f8f9fa !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transform: translateY(-1px);
  }

  /* Print styles */
  @media print {
    body * {
      visibility: hidden;
    }

    #print-area,
    #print-area *,
    #print-area-smpi,
    #print-area-smpi * {
      visibility: visible;
    }

    #print-area,
    #print-area-smpi {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }

    @page {
      size: landscape;
    }

    .print-btn,
    .nav-tabs-custom,
    .card,
    .filter-section {
      display: none;
    }

    .editable {
      border: none;
      background: transparent;
    }
  }
</style>

<div class="card">
  <div class="card shadow-sm border-0">
    <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <h5 class="mb-2 mb-md-0 text-center" style="font-family: 'Times New Roman', serif;">
          <strong>CARDS: </strong>
        </h5>
      </div>
    </div>
  </div>

  <div class="tabs-container">
    <ul class="nav-tabs-custom" id="categoriesTabs">
      <li class="nav-tab-item">
        <a href="?tab=sc<?= $stock_card_input ? '&stock_card=' . urlencode($stock_card_input) : '' ?><?= $fund_cluster_filter ? '&fund_cluster=' . urlencode($fund_cluster_filter) : '' ?><?= $value_filter ? '&value_filter=' . urlencode($value_filter) : '' ?><?= $category_filter ? '&category=' . urlencode($category_filter) : '' ?><?= $item_filter ? '&item=' . urlencode($item_filter) : '' ?>"
          class="nav-tab-link <?= $active_tab === 'sc' ? 'active' : '' ?>"
          data-tab="sc">
          <i class="fas fa-boxes tab-icon"></i> Stock Card
        </a>
      </li>
      <li class="nav-tab-item">
        <a href="?tab=smpi<?= $stock_card_input ? '&stock_card=' . urlencode($stock_card_input) : '' ?><?= $fund_cluster_filter ? '&fund_cluster=' . urlencode($fund_cluster_filter) : '' ?><?= $value_filter ? '&value_filter=' . urlencode($value_filter) : '' ?>"
          class="nav-tab-link <?= $active_tab === 'smpi' ? 'active' : '' ?>"
          data-tab="smpi">
          <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property Card
        </a>
      </li>
    </ul>
  </div>
</div>

<!-- Tab Content -->
<div class="tab-content">
  <!-- Stock Card Tab -->
  <div id="tab-sc" class="tab-pane <?= $active_tab === 'sc' ? 'active' : '' ?>">
    <!-- Search and Filter Form for Stock Card -->
    <div class="card mb-3 filter-card" style="border-top:5px solid #055919; border-radius:15px; padding:20px;">
      <div class="row align-items-center">
        <div class="col-md-12">
          <h5 class="mb-3"><i class="nav-icon fas fa-clipboard-list"></i> Stock Card - Filters</h5>
          <form method="GET" action="" class="row g-3 align-items-end rounded-filters">
            <input type="hidden" name="tab" value="sc">

            <div class="col-md-3">
              <label class="form-label"><strong>Category</strong></label>
              <select class="form-select w-100 p-2 rounded-select" name="category" id="categorySelect" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= $category['id'] ?>"
                    <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                    <?= $category['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label"><strong>Item</strong></label>
              <select class="form-select w-100 p-2 rounded-select" name="item" id="itemSelect">
                <option value="">All Items</option>
                <?php foreach ($items_list as $item): ?>
                  <option value="<?= $item['stock_card'] ?>"
                    <?= $item_filter == $item['stock_card'] ? 'selected' : '' ?>>
                    <?= $item['name'] ?> (<?= $item['stock_card'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label"><strong>Search Item</strong></label>
              <div class="input-group">
                <input type="text" class="form-control rounded-input" name="stock_card" id="searchInput"
                  placeholder="Search by stock number or item name"
                  value="<?= htmlspecialchars($stock_card_input ?? '') ?>"
                  style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                <button type="submit" class="btn btn-success rounded-button" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                  <i class="fa-solid fa-search"></i>
                </button>
              </div>
            </div>

            <div class="col-md-2">
              <button type="submit" class="btn btn-success w-100 rounded-button">
                <i class="fa-solid fa-filter"></i> Apply
              </button>
              <?php if ($stock_card_input || $category_filter || $item_filter): ?>
                <a href="?tab=sc" class="btn btn-outline-secondary w-100 mt-2 rounded-button">
                  <i class="fa-solid fa-times"></i> Clear
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Stock Card Content -->
    <div id="print-area">
      <div class="text-center mb-3">
        <div class="d-flex align-items-center justify-content-center" style="gap:40px;">
          <!-- Left Logo -->
          <div style="flex:0 0 auto;">
            <img src="uploads/other/bsulogo.png" alt="Logo Left" style="max-width:80px; height:auto;">
          </div>
          <!-- Center Text -->
          <div style="flex:0 0 auto; text-align:center;">
            <h6 style="margin:0;font-family:'Times New Roman', serif;">Republic of the Philippines</h6>
            <h5 style="margin:0; font-family:'Old English Text MT', serif;">
              Benguet State University
            </h5>
            <p style="margin:0;"><strong> 2605 Bokod, Benguet </strong></p>
            <h6 style="margin-top:5px;"><strong>STOCK CARD</strong></h6>
          </div>
          <!-- Right Logos in a Row -->
          <div style="flex:0 0 auto; display:flex; gap:10px;">
            <img src="uploads/other/SPMO.png" alt="Logo Right 1" style="max-width:80px; height:auto;">
            <img src="uploads/other/BP.PNg" alt="Logo Right 2" style="max-width:100px; height:auto;">
          </div>
        </div>
      </div>

      <!-- Item Details -->
      <?php if ($stock_item): ?>
        <table class="mb-2 w-100" style="border-collapse: collapse;">
          <tr>
            <td>
              <strong style="font-size:12px;">Fund Cluster: <?= $stock_item['fund_cluster'] ?? 'N/A'; ?></strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong style="font-size:12px;">STOCK NUMBER:</strong>
              <strong><span style="margin-left:60px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                  <?= $stock_item['stock_number'] ?? 'N/A'; ?>
                </span></strong>
            </td>
            <td class="text-end">
              <strong style="font-size:12px;">Re-order Point:</strong>
              <input type="text" name="reorder_point" value=""
                style="margin-left:15px; border:none; border-bottom:1px solid #000; outline:none; min-width:150px; text-align:center;">
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <strong style="font-size:12px;">ITEM:</strong>
              <strong><span style="margin-left:120px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                  <?= strtoupper($stock_item['item_name'] ?? 'N/A'); ?>
                </span></strong>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <strong style="font-size:12px;">DESCRIPTION:</strong>
              <span style="margin-left:72px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                <?= $stock_item['description'] ?? 'N/A'; ?>
              </span>
            </td>
          </tr>
          <tr>
            <td>
              <strong style="font-size:12px;margin:0">UNIT OF MEASUREMENT:</strong>
              <span style="margin-left:15px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                <?= $stock_item['unit_name'] ?? 'N/A'; ?>
              </span>
            </td>
          </tr>
        </table>
      <?php endif; ?>

      <!-- Stock Card Table -->
      <table class="table table-bordered text-center w-100">
        <thead style="font-size:13px">
          <tr>
            <th rowspan="2">Date Received / Issued</th>
            <th rowspan="2">Reference</th>
            <th colspan="2">Receipt</th>
            <th colspan="4">Issuance</th>
            <th colspan="2">Balance</th>
            <th rowspan="2">No. of Days to Consume</th>
            <th rowspan="2">Remarks</th>
          </tr>
          <tr>
            <th>Qty</th>
            <th>Unit Cost</th>
            <th>Qty</th>
            <th>Unit Cost</th>
            <th>Total Cost</th>
            <th>Office</th>
            <th>Qty</th>
            <th>Total Cost</th>
          </tr>
        </thead>
        <tbody style="font-size:13px">
          <?php if (!empty($stock_transactions)): ?>
            <?php foreach ($stock_transactions as $transaction): ?>
              <tr>
                <td>
                  <?php if (!empty($transaction['date'])): ?>
                    <?= date("m/d/Y", strtotime($transaction['date'])) ?>
                  <?php else: ?>
                    <?= 'N/A' ?>
                  <?php endif; ?>
                </td>
                <td><?= $transaction['reference'] ?? '' ?></td>
                
                <!-- Receipt Columns -->
                <td>
                  <?= ($transaction['change_type'] === 'stock_in' || $transaction['change_type'] === 'carry_forward') ? $transaction['quantity'] : '' ?>
                </td>
                <td>
                  <?= ($transaction['change_type'] === 'stock_in' || $transaction['change_type'] === 'carry_forward') ? number_format($transaction['unit_cost'], 2) : '' ?>
                </td>
                
                <!-- Issuance Columns -->
                <td>
                  <?= $transaction['change_type'] === 'issuance' ? $transaction['quantity'] : '' ?>
                </td>
                <td>
                  <?= $transaction['change_type'] === 'issuance' ? number_format($transaction['unit_cost'], 2) : '' ?>
                </td>
                <td>
                  <?= $transaction['change_type'] === 'issuance' ? number_format($transaction['total_cost'], 2) : '' ?>
                </td>
                <td>
                  <?= $transaction['office_name'] ?? '' ?>
                </td>
                
                <!-- Balance Columns -->
                <td><?= $transaction['running_balance'] ?? 0 ?></td>
                <td><?= number_format($transaction['running_total_cost'] ?? 0, 2) ?></td>
                <td></td> <!-- No. of Days to Consume -->
                <td>
                  <?= $transaction['change_type'] === 'carry_forward' ? 'Carried Forward' : ($transaction['remarks'] ?? '') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="12" class="text-muted" style="font-size:35px; padding:5px"> 
                <i class="fa-solid fa-chalkboard-user"></i> 
                <?= $search_input ? 'No stock transactions found for this search.' : 'Select a category and item or enter a stock number to search.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tbody>
          <?php
          $count = !empty($stock_transactions) ? count($stock_transactions) : 0;
          $empty_rows = 10 - $count;
          if ($empty_rows > 0):
            for ($i = 0; $i < $empty_rows; $i++): ?>
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
          <?php endfor;
          endif; ?>
        </tbody>
      </table>
    </div>

    <button class="print-btn" onclick="openPrintPage('sc')">
      <i class="fa-solid fa-print"></i> Print Preview
    </button>
  </div>

  
</div>

<script>
  function viewPropertyCard(itemId) {
    const fundCluster = encodeURIComponent("<?= $fund_cluster_filter ?? '' ?>");
    const valueFilter = encodeURIComponent("<?= $value_filter ?? '' ?>");
    
    // Open print page for the specific item
    window.open(`print_smpi.php?item_id=${itemId}&fund_cluster=${fundCluster}&value_filter=${valueFilter}`, '_blank');
  }

  function openPrintPage(tab) {
    const stockCard = encodeURIComponent("<?= $stock_card_input ?? '' ?>");
    const fundCluster = encodeURIComponent("<?= $fund_cluster_filter ?? '' ?>");
    const valueFilter = encodeURIComponent("<?= $value_filter ?? '' ?>");
    const category = encodeURIComponent("<?= $category_filter ?? '' ?>");
    const item = encodeURIComponent("<?= $item_filter ?? '' ?>");

    if (tab === 'sc') {
      // For Stock Card, open in new tab
      window.open(`print_stock_card.php?stock_card=${stockCard}&category=${category}&item=${item}`, '_blank');
    } else {
      // For SMPI, if no specific item is selected, show all filtered items
      // Otherwise, the individual row click will handle specific items
      if (document.querySelector('.clickable-row:hover')) {
        // Let the row click handle it
        return;
      } else {
        // Show all filtered items
        window.open(`print_smpi.php?fund_cluster=${fundCluster}&value_filter=${valueFilter}`, '_blank');
      }
    }
  }

  // Add hover effects for clickable rows
  document.addEventListener('DOMContentLoaded', function() {
    const clickableRows = document.querySelectorAll('.clickable-row');
    
    clickableRows.forEach(row => {
      row.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#f8f9fa';
        this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
      });
      
      row.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
        this.style.boxShadow = '';
      });
    });
  });

  // Tab navigation handling
  document.addEventListener('DOMContentLoaded', function () {
    const tabLinks = document.querySelectorAll('.nav-tab-link');

    tabLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        // Remove active class from all tabs
        tabLinks.forEach(tab => tab.classList.remove('active'));

        // Add active class to clicked tab
        this.classList.add('active');

        // Hide all tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
          pane.classList.remove('active');
        });

        // Show selected tab pane
        const tabId = this.getAttribute('data-tab');
        document.getElementById(`tab-${tabId}`).classList.add('active');
      });
    });
  });

  // Auto-submit form when category changes to load items
  document.getElementById('categorySelect')?.addEventListener('change', function() {
    this.form.submit();
  });
</script>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    var table = $('#smpiTable').DataTable({
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: true,
      autoWidth: false,
      fixedColumns: true
    });
  }); 
</script>