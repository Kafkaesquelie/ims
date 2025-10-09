<?php
$page_title = 'Printable Stock Card';
require_once('includes/load.php');
page_require_level(1);


$stock_card_input = $_GET['stock_card'] ?? null;
$stock = null;

// Fetch stock info
if ($stock_card_input) {
    // Try by ID first
    $stock = find_by_id('items', $stock_card_input);

    if (!$stock) {
        $stock_result = find_by_sql("
            SELECT * FROM items 
            WHERE stock_card = '{$db->escape($stock_card_input)}' 
               OR name LIKE '%{$db->escape($stock_card_input)}%' 
            LIMIT 1
        ");
        $stock = $stock_result[0] ?? null;
    }
}

$transactions = [];
if ($stock) {
    $stock_id = $stock['id'];
    $sql = "
        SELECT 
            r.id AS req_id,
            r.date AS request_date,
            u.name AS requested_by,
            d.dpt AS department,
            ri.qty AS issue_qty,
            i.unit_cost,
            (ri.qty * i.unit_cost) AS issue_total_cost,
            i.name,
            i.description,
            i.UOM,
            i.stock_card
        FROM request_items ri
        JOIN requests r ON ri.req_id = r.id
        JOIN items i ON ri.item_id = i.id
        JOIN users u ON r.requested_by = u.id
        JOIN departments d ON u.department = d.id
        WHERE r.status = 'Approved' AND i.id = '{$db->escape($stock_id)}'
        ORDER BY r.date ASC
    ";
    $transactions = find_by_sql($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $page_title ?></title>
<link rel="stylesheet" href="path/to/bootstrap.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="icon" type="image/png"  href="uploads/other/imslogo.png">

<style>
#stock-card {
    width: 1000px;      
    max-width: 100%;    
    margin: 30px auto;  /* added margin around preview */
    padding: 15px;
}

.print-btn {
    background: #190361ff;
    color: white;
    border: none;
    cursor: pointer;
    box-shadow: 0px 3px 6px rgba(0,0,0,0.2);
    font-size: 20px;
    transition: background 0.3s ease;
    float:right;
    margin-right:40px;
    font-family:'Times New Roman', serif;
    
}
.footer{
    font-family:'Times New Roman', serif;
    font-size:10px;
}
.print-btn:hover {
    background: #220184ff;
}

table {
    width: 100%;         
    table-layout: fixed;
    
}

@media print {
    body {
        margin: 10px;
    }
    .print-btn {
        display: none;
    }
    #stock-card {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    @page {
        size: A4 landscape;
        margin: 10mm;
    }
}

/* Make table borders thicker and more visible */
table.table-bordered, 
table.table-bordered th, 
table.table-bordered td {
    border: 1.5px solid #000 !important; /* thicker solid black borders */
}

/* Optional: make the header stand out */
table.table-bordered thead th {
    border-bottom: 2px solid #000;
}

</style>


</head>
<body>

<button class="print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>


<div id="print-area">
<div id="stock-card">
  <div class="d-flex align-items-center justify-content-center" style="gap:40px;">

    <!-- Left Logo -->
    <div style="flex:0 0 auto;">
      <img src="uploads/other/bsulogo.png" alt="Logo Left" style="max-width:80px; height:auto;">
    </div>

    <!-- Center Text -->
    <div style="flex:0 0 auto; text-align:center;">
      <h6 style="margin:0;font-family:'Times New Roman', serif;">Republic of the Philippines</h6>
     <h5 class=" text-success"style="margin:0; font-family:'Old English Text MT', serif;">
        Benguet State University
      </h5>
      <p style="margin:0; font-size:11px"><strong> 2605 Bokod, Benguet </strong></p>
      <h6 style="margin-top:5px;"><strong>STOCK CARD</strong></h6>
    </div>

    <!-- Right Logos in a Row -->
    <div style="flex:0 0 auto; display:flex; gap:10px;">
      <img src="uploads/other/bsulogo.png" alt="Logo Right 1" style="max-width:80px; height:auto;">
      <img src="uploads/other/bsulogo.png" alt="Logo Right 2" style="max-width:80px; height:auto;">
    </div>

  </div>



  <!-- Item Details -->
  <?php if($stock): ?>
  <table class="mb-2 w-100 " style="border-collapse: collapse;">
    <tr>
      <td>
        <strong style="font-size:10px;">Fund Cluster: <?= $stock['fund_cluster'] ?? 'N/A'; ?></strong>    
      </td>
    </tr>
    <tr>
      <td>
        <strong style="font-size:10px;" >STOCK NUMBER:</strong> 
       <strong><span style="margin-left:60px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
          <?= $stock['stock_card'] ?? 'N/A'; ?>
        </span></strong> 
      </td>
       <td class="text-end">
        <strong style="font-size:10px;">Re-order Point:</strong> 
        <input type="text" name="reorder_point" 
               value="<?= $stock['reorder_point'] ?? '' ?>" 
               style="margin-left:15px; border:none; border-bottom:1px solid #000; outline:none; min-width:150px; text-align:center;">
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <strong style="font-size:10px;">ITEM:</strong> 
       <strong><span style="margin-left:120px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
             <?= strtoupper($stock['name'] ?? 'N/A'); ?>
        </span></strong> 
      </td>
    </tr>

    <tr>
      <td colspan="2">
        <strong style="font-size:10px;">DESCRIPTION:</strong> 
        <span style="margin-left:72px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
          <?= $stock['description'] ?? 'N/A'; ?>
        </span>
      </td>
    </tr>

    <tr>
      <td>
        <strong style="font-size:10px;margin:0">UNIT OF MEASUREMENT:</strong> 
        <span style="margin-left:15px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
          <?= $stock['UOM'] ?? 'N/A'; ?>
        </span>
      </td>
     
    </tr>
  </table>
<?php endif; ?>


  <!-- Stock Card Table -->
  <table class="table table-bordered text-center w-100 ;">
    <thead style="font-size:11px">
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
    <tbody style="font-size:10px">
      <?php if(!empty($transactions)): ?>
        <?php 
          $balance_qty = 0;
          $balance_total = 0;
          foreach($transactions as $trx): 
              $receipt_qty = $trx['issue_qty'];
              $unit_cost = $trx['unit_cost'];
              $issue_total = $trx['issue_total_cost'];
              $balance_qty += $trx['issue_qty'];
              $balance_total += $trx['issue_total_cost'];
        ?>
        <tr>
          <td><?= date("m/d/Y", strtotime($trx['request_date'])) ?></td>
          <td><?= "REQ-" . $trx['req_id'] ?></td>
          <!-- Receipt -->
          <td><?= $receipt_qty ?></td>
          <td><?= number_format($unit_cost, 2) ?></td>
          <!-- Issuance -->
          <td><?= $trx['issue_qty'] ?></td>
          <td><?= number_format($unit_cost, 2) ?></td>
          <td><?= number_format($issue_total, 2) ?></td>
          <td><?= $trx['department'] ?></td>
          <!-- Balance -->
          <td><?= $balance_qty ?></td>
          <td><?= number_format($balance_total, 2) ?></td>
          <td></td>
          <td></td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="12" class="text-muted" style="font-size:35px; padding:5px"> <i class="fa-solid fa-chalkboard-user"></i> No transactions found.</td></tr>
      <?php endif; ?>
    </tbody>
     <tbody>
  <?php 
    $count = !empty($transactions) ? count($transactions) : 0;
  $empty_rows = 9 - $count;
  if ($empty_rows > 0):
    for ($i=0; $i<$empty_rows; $i++): ?>
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
  <?php endfor; endif; ?>
</tbody>
  </table>
</div>
</div>
</div>
 
<p class="text-center text-muted footer"> This form was generated electronically by the School Inventory Mangement System</p>
</div>

</body>
</html>
