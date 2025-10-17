<?php
$page_title = 'Reports Page';
require_once('includes/load.php');
page_require_level(1);

// Fetch all approved items (for dropdown search autocomplete if needed)
$all_items = find_by_sql("
    SELECT DISTINCT i.*
    FROM items i
    JOIN request_items ri ON i.id = ri.item_id
    JOIN requests r ON ri.req_id = r.id
    WHERE r.status = 'Completed'
    ORDER BY i.name ASC
");

// Get stock info
$stock = null;
$stock_id = null;
$stock_card_input = $_GET['stock_card'] ?? null;

// Stock search improvement
if ($stock_card_input) {
  // Try by ID first
  $stock = find_by_id('items', $stock_card_input);
  $stock_id = $stock['id'] ?? null;

  // If not found, try by stock_card OR name
  if (!$stock) {
    $stock_result = find_by_sql("
            SELECT * FROM items 
            WHERE stock_card = '{$db->escape($stock_card_input)}' 
               OR name LIKE '%{$db->escape($stock_card_input)}%' 
            LIMIT 1
        ");
    $stock = $stock_result[0] ?? null;
    $stock_id = $stock['id'] ?? null;
  }
}


// Fetch transactions for selected stock_id
$transactions = [];
if ($stock_id) {
  $sql = "
       SELECT 
    r.id AS req_id,
    r.ris_no,
    r.date AS request_date,
    COALESCE(CONCAT(e.first_name, ' ', e.last_name), u.name) AS requested_by,
    ri.qty AS issue_qty,
    i.unit_cost,
    (ri.qty * i.unit_cost) AS issue_total_cost,
    i.name,
    i.description,
    un.name AS UOM,
    i.stock_card,
    COALESCE(ofc.office_name, ofc_user.office_name) AS office,
    i.quantity AS balance_qty  -- fetch actual remaining stock from DB
FROM request_items ri
JOIN requests r ON ri.req_id = r.id
JOIN items i ON ri.item_id = i.id
JOIN users u ON r.requested_by = u.id
LEFT JOIN employees e ON u.id = e.user_id
LEFT JOIN units un ON i.unit_id = un.id
LEFT JOIN offices ofc ON e.office = ofc.id
LEFT JOIN offices ofc_user ON u.office = ofc_user.id
WHERE r.status = 'Completed' 
  AND i.id = '{$db->escape($stock_id)}'
ORDER BY r.date ASC

    ";
  $transactions = find_by_sql($sql);
}



?>
<?php include_once('layouts/header.php'); ?>



<!-- Page Header -->
<!-- Card Wrapper for Title and Search -->
<div class="card mb-3" style="border-top:5px solid #055919; border-radius:8px; padding:15px;">
  <div class="row align-items-center">
    <div class="col-md-6">
      <h5 class="mb-0"><i class="nav-icon fas fa-clipboard-list"></i> Stock Card</h5>
    </div>
    <div class="col-md-6 text-end">
      <!-- Search Form -->
      <form method="GET" action="" class="d-flex justify-content-end align-items-center">
        <div style="position: relative; width:250px;">
          <input type="text" class="form-control" name="stock_card" id="searchInput"
            placeholder="Search"
            value="<?= htmlspecialchars($stock_card_input ?? '') ?>"
            style="padding-right:30px; font-size:13px;">
        </div>
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-search"></i> </button>
      </form>
    </div>
  </div>
</div>


<div id="print-area">
  <!-- Header with logos -->
  <!-- Header with logos -->
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
  <table class="table table-bordered text-center w-100 ;">
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
        // Assume $stock['qty'] holds the current stock BEFORE any issuance
        $starting_qty = $stock['qty'] ?? 0;
        $balance_qty = $starting_qty; // start with initial stock
        $balance_total = $balance_qty * ($stock['unit_cost'] ?? 0);

        foreach ($transactions as $trx):
          $issue_qty = $trx['issue_qty'];
          $unit_cost = $trx['unit_cost'];
          $issue_total = $trx['issue_total_cost'];

          // Update balance AFTER the issuance
          $balance_qty -= $issue_qty;
          $balance_total -= $issue_total;
        ?>
          <tr>
            <td><?= date("m/d/Y", strtotime($trx['request_date'])) ?></td>
            <td><?= !empty($trx['ris_no']) ? $trx['ris_no'] : ("REQ-" . $trx['req_id']); ?></td>
            <!-- Receipt (if any, else 0) -->
            <td>0</td>
            <td><?= number_format($unit_cost, 2) ?></td>
            <!-- Issuance -->
            <td><?= $issue_qty ?></td>
            <td><?= number_format($unit_cost, 2) ?></td>
            <td><?= number_format($issue_total, 2) ?></td>
            <td><?= $trx['office'] ?></td>
            <!-- Balance -->
    <!-- Balance -->
<td><?= $trx['balance_qty'] ?></td>
<td><?= number_format($trx['balance_qty'] * $trx['unit_cost'], 2) ?></td>

            <td></td>
            <td></td>
          </tr>
        <?php endforeach; ?>

      <?php else: ?>
        <tr>
          <td colspan="12" class="text-muted" style="font-size:35px; padding:5px"> <i class="fa-solid fa-chalkboard-user"></i> No transactions found.</td>
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

<!-- Floating Print Button -->
<!-- <button class="print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button> -->

<!-- Floating Print Button -->
<button class="print-btn" onclick="openPrintPage()"><i class="fa-solid fa-print"></i> Print Preview</button>

<script>
  function openPrintPage() {
    const stockCard = encodeURIComponent("<?= $stock_card_input ?? '' ?>");
    window.open(`print_stock_card.php?stock_card=${stockCard}`, '_blank');
  }
</script>




<?php include_once('layouts/footer.php'); ?>


<style>
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
  }

  .print-btn:hover {
    background: #155c04ff;
  }


  /* 🖨 Print only form */
  @media print {
    body * {
      visibility: hidden;
    }

    #print-area,
    #print-area * {
      visibility: visible;
    }

    #print-area {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }

    @page {
      size: landscape;
    }

    .print-btn {
      display: none;
    }

    .editable {
      border: none;
      background: transparent;
    }
  }
</style>