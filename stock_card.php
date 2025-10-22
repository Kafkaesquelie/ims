<?php
$page_title = 'Reports Page';
require_once('includes/load.php');
page_require_level(1);

// =====================
// Get filter parameters
// =====================
$stock_card_input = $_GET['stock_card'] ?? null;
$fund_cluster_filter = $_GET['fund_cluster'] ?? '';
$value_filter = $_GET['value_filter'] ?? '';
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
// Fetch all semi-expendable items (filtered)
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
          ri.RRSP_No,  -- Changed from t.RRSP_No to ri.RRSP_No
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
      LEFT JOIN return_items ri ON t.id = ri.transaction_id  -- Added join to return_items table
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
        <a href="?tab=sc<?= $stock_card_input ? '&stock_card=' . urlencode($stock_card_input) : '' ?><?= $fund_cluster_filter ? '&fund_cluster=' . urlencode($fund_cluster_filter) : '' ?><?= $value_filter ? '&value_filter=' . urlencode($value_filter) : '' ?>"
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
    <!-- Search Form for Stock Card -->
    <div class="card mb-3" style="border-top:5px solid #055919; border-radius:8px; padding:15px;">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h5 class="mb-0"><i class="nav-icon fas fa-clipboard-list"></i> Stock Card</h5>
        </div>
        <div class="col-md-6 text-end">
          <form method="GET" action="" class="d-flex justify-content-end align-items-center">
            <input type="hidden" name="tab" value="sc">
            <input type="hidden" name="fund_cluster" value="<?= $fund_cluster_filter ?>">
            <input type="hidden" name="value_filter" value="<?= $value_filter ?>">
            <div style="position: relative; width:250px;">
              <input type="text" class="form-control" name="stock_card" id="searchInput"
                placeholder="Search by stock number or item name"
                value="<?= htmlspecialchars($stock_card_input ?? '') ?>"
                style="padding-right:30px; font-size:13px;">
            </div>
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-search"></i> </button>
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
            <img src="uploads/other/bsulogo.png" alt="Logo Right 1" style="max-width:80px; height:auto;">
            <img src="uploads/other/bsulogo.png" alt="Logo Right 2" style="max-width:80px; height:auto;">
          </div>
        </div>
      </div>

      <!-- Item Details -->
      <?php if ($stock): ?>
        <table class="mb-2 w-100" style="border-collapse: collapse;">
          <tr>
            <td>
              <strong style="font-size:12px;">Fund Cluster: <?= $stock['fund_cluster'] ?? 'N/A'; ?></strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong style="font-size:12px;">STOCK NUMBER:</strong>
              <strong><span style="margin-left:60px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                  <?= $stock['stock_card'] ?? 'N/A'; ?>
                </span></strong>
            </td>
            <td class="text-end">
              <strong style="font-size:12px;">Re-order Point:</strong>
              <input type="text" name="reorder_point"
                value="<?= $stock['reorder_point'] ?? '' ?>"
                style="margin-left:15px; border:none; border-bottom:1px solid #000; outline:none; min-width:150px; text-align:center;">
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <strong style="font-size:12px;">ITEM:</strong>
              <strong><span style="margin-left:120px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                  <?= strtoupper($stock['name'] ?? 'N/A'); ?>
                </span></strong>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <strong style="font-size:12px;">DESCRIPTION:</strong>
              <span style="margin-left:72px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                <?= $stock['description'] ?? 'N/A'; ?>
              </span>
            </td>
          </tr>
          <tr>
            <td>
              <strong style="font-size:12px;margin:0">UNIT OF MEASUREMENT:</strong>
              <span style="margin-left:15px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
                <?= $stock['UOM'] ?? 'N/A'; ?>
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
          <?php if (!empty($transactions)): ?>
            <?php
            $starting_qty = $stock['qty'] ?? 0;
            $balance_qty = $starting_qty;
            $balance_total = $balance_qty * ($stock['unit_cost'] ?? 0);

            foreach ($transactions as $trx):
              $issue_qty = $trx['issue_qty'];
              $unit_cost = $trx['unit_cost'];
              $issue_total = $trx['issue_total_cost'];
              $balance_qty -= $issue_qty;
              $balance_total -= $issue_total;
            ?>
              <tr>
                <td><?= date("m/d/Y", strtotime($trx['request_date'])) ?></td>
                <td><?= !empty($trx['ris_no']) ? $trx['ris_no'] : ("REQ-" . $trx['req_id']); ?></td>
                <td>0</td>
                <td><?= number_format($unit_cost, 2) ?></td>
                <td><?= $issue_qty ?></td>
                <td><?= number_format($unit_cost, 2) ?></td>
                <td><?= number_format($issue_total, 2) ?></td>
                <td><?= $trx['office'] ?></td>
                <td><?= $trx['balance_qty'] ?></td>
                <td><?= number_format($trx['balance_qty'] * $trx['unit_cost'], 2) ?></td>
                <td></td>
                <td></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="12" class="text-muted" style="font-size:35px; padding:5px"> 
                <i class="fa-solid fa-chalkboard-user"></i> No transactions found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
        <tbody>
          <?php
          $count = !empty($transactions) ? count($transactions) : 0;
          $empty_rows = 5 - $count;
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

    
    <button class="print-btn" onclick="openPrintPage('smpi')">
      <i class="fa-solid fa-print"></i> Print Preview
    </button>
  </div>

  <!-- Semi-Expendable Property Card Tab -->
  <div id="tab-smpi" class="tab-pane <?= $active_tab === 'smpi' ? 'active' : '' ?>">
    <!-- Search and Filter Form for SMPI -->
    <div class="card mb-3 filter-card" style="border-radius:8px; padding:15px;">
      <div class="row align-items-center">
        <div class="col-md-12">
          <h5 class="mb-3"><i class="nav-icon fas fa-tools"></i> Semi-Expendable Property Card - Filters</h5>
          <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="smpi">


            <div class="col-md-3">
              <label class="form-label"><strong>Fund Cluster</strong></label><br>
              <select class="form-select w-100 p-2" name="fund_cluster">
                <option value="">All Fund Clusters</option>
                <?php foreach ($fund_clusters as $cluster): ?>
                  <option value="<?= $cluster['fund_cluster'] ?>"
                    <?= $fund_cluster_filter === $cluster['fund_cluster'] ? 'selected' : '' ?>>
                    <?= $cluster['fund_cluster'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label"><strong>Value Range</strong></label>
              <select class="form-select w-100 p-2" name="value_filter">
                <option value="">All Values</option>
                <option value="high" <?= $value_filter === 'high' ? 'selected' : '' ?>>High Value (₱5,000 - ₱50,000)</option>
                <option value="low" <?= $value_filter === 'low' ? 'selected' : '' ?>>Low Value (Below ₱5,000)</option>
              </select>
            </div>

            <div class="col-md-3">
              <button type="submit" class="btn btn-success w-100">
                <i class="fa-solid fa-filter"></i> Apply Filters
              </button>
              <?php if ($stock_card_input || $fund_cluster_filter || $value_filter): ?>
                <a href="?tab=smpi" class="btn btn-outline-secondary w-100 mt-2">
                  <i class="fa-solid fa-times"></i> Clear Filters
                </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- SMPI Items Summary -->
    <?php if (!empty($smpi_items)): ?>
      <div class="card mb-3">
        <div class="card-header bg-light">
          <h6 class="mb-0"><i class="fas fa-list me-2"></i>Semi-Expendable Items (<?= count($smpi_items) ?> found)</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="smpiTable">
              <thead class="table-light">
                <tr>
                  <th>Inventory Item No</th>
                  <th>Item Name</th>
                  <th>Description</th>
                  <th>Fund Cluster</th>
                  <th>Unit Cost</th>
                  <th>Balance Qty</th>
                  <th>Total Value</th>
                  <th>Value Category</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($smpi_items as $item): ?>
                  <?php
                  $total_value = $item['unit_cost'] * $item['balance_qty'];
                  $value_category = $item['unit_cost'] >= 5000 ? 'High Value' : 'Low Value';
                  $value_class = $item['unit_cost'] >= 5000 ? 'text-danger fw-bold' : 'text-success';
                  ?>
                  <tr>
                    <td><strong><?= $item['inv_item_no'] ?? 'N/A' ?></strong></td>
                    <td><?= $item['item'] ?></td>
                    <td><?= $item['item_description'] ?? 'N/A' ?></td>
                    <td><?= $item['fund_cluster'] ?? 'N/A' ?></td>
                    <td class="text-end">₱<?= number_format($item['unit_cost'], 2) ?></td>
                    <td class="text-center"><?= $item['balance_qty'] ?></td>
                    <td class="text-end">₱<?= number_format($total_value, 2) ?></td>
                    <td class="<?= $value_class ?>"><?= $value_category ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>


           

    <?php if (empty($smpi_items)): ?>
      <div class="alert alert-warning text-center">
        <i class="fa-solid fa-exclamation-triangle me-2"></i>
        No semi-expendable items found matching your criteria.
      </div>
    <?php endif; ?>

    <!-- Single Print Button for SMPI -->
    <button class="print-btn" onclick="openPrintPage()">
      <i class="fa-solid fa-print"></i> Print Preview
    </button>
  </div>
</div>

<script>
  function openPrintPage(tab) {
    const stockCard = encodeURIComponent("<?= $stock_card_input ?? '' ?>");
    const fundCluster = encodeURIComponent("<?= $fund_cluster_filter ?? '' ?>");
    const valueFilter = encodeURIComponent("<?= $value_filter ?? '' ?>");

    if (tab === 'sc') {
      // For Stock Card, open in new tab
      window.open(`print_stock_card.php?stock_card=${stockCard}&fund_cluster=${fundCluster}&value_filter=${valueFilter}`, '_blank');
    } else {
      // For SMPI, open in new tab
      window.open(`print_smpi.php?stock_card=${stockCard}&fund_cluster=${fundCluster}&value_filter=${valueFilter}`, '_blank');
    }
  }

  function printCurrentTab(tab) {
    if (tab === 'sc') {
      // Print stock card from current page
      const printContent = document.getElementById('print-area').innerHTML;
      const originalContent = document.body.innerHTML;

      document.body.innerHTML = printContent;
      window.print();
      document.body.innerHTML = originalContent;

      // Reload to restore functionality
      window.location.reload();
    } else {
      // For SMPI, use the dedicated print page in new tab
      openPrintPage('smpi');
    }
  }

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
      searching: false,
      autoWidth: false,
      fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    }); 
    }); 

</script> 