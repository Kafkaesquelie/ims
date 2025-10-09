<?php
$page_title = 'Registry of Semi-Expendable Property Issued';
require_once('includes/load.php');
page_require_level(1); // Only admins

// Fetch issued semi-expendable property from transactions table
$issued_items = find_by_sql("
    SELECT 
        t.transaction_date AS date,
        t.ICS_No AS ics_no,
        sep.inv_item_no AS sep_inv_item_no,
        sep.property_no AS sep_property_no,
        sep.item_description,
        sep.unit_cost,
        sep.estimated_use,
        t.quantity AS qty,
        CONCAT(e.first_name, ' ', 
               COALESCE(CONCAT(LEFT(e.middle_name, 1), '. '), ''), 
               e.last_name) AS officer,
        t.remarks
    FROM transactions t
    LEFT JOIN semi_exp_prop sep ON t.item_id = sep.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.transaction_type = 'issue'
      AND sep.inv_item_no IS NOT NULL
    ORDER BY t.transaction_date DESC
");
?>

<?php include_once('layouts/header.php'); ?>

<style>
  table, th, td, .header, .meta {
    font-family: 'Times New Roman', serif;
    font-size: 12px;
  }
  .header { text-align: center; margin-bottom: 10px; }
  .header h2 { margin: 0; font-size: 16px; }
  .controls {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 15px; padding: 10px; border-radius: 5px 5px 0 0;
  }
  .controls input {
    padding: 5px 8px; font-size: 12px; width: 200px;
  }
  .controls button {
    background: #055919ff; color: white; border: none;
    padding: 6px 12px; border-radius: 5px; cursor: pointer;
  }
  .controls button:hover { background: #155c04ff; }
  .meta {
    display: flex; justify-content: space-between;
    margin-bottom: 10px; margin-right: 40px; line-height: 1.6;
  }
  .editable {
    border: none; border-bottom: 1px dashed #555;
    min-width: 230px; padding: 2px 4px; font-size: 12px;
  }
  table {
    width: 100%; border-collapse: collapse; font-size: 12px;
  }
  table, th, td { border: 1px solid #000; }
  th, td { padding: 4px; text-align: center; }
  @media print {
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area { position: absolute; left:0; top:0; width:100%; }
    @page { size: landscape; }
    .editable { border: none; background: transparent; }
    .controls { display: none; }
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
</style>

<div class="card p-2" style="border-top: 5px solid #28a745; border-radius: 10px;">
  <div class="card shadow-sm border-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
      <h5 class="mb-2 mb-md-0 text-center text-md-start" style="font-family: 'Times New Roman', serif;">
        <strong>REGISTRY OF SEMI-EXPANDABLE PROPERTY ISSUED</strong>
      </h5>
      <div class="text-center text-md-end">
        <div class="controls">
          <div style="position: relative; display: inline-block; width: 250px;">
            <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="form-control" placeholder="Search items..." id="searchInput">
        </div>
          </div>
          <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="print-area">

  <div class="meta">
    <div>
      <strong>Entity Name:</strong> 
      <input type="text" class="editable" value="Benguet State University - Bokod Campus"> <br>
      <strong>Semi-expandable Property:</strong>
    </div>
    <div>
      <strong>Fund Cluster:</strong> 
      <input type="text" class="editable" placeholder="__________"> <br>
      <strong>Sheet No.:</strong> 
      <input type="text" class="editable" placeholder="______________">
    </div>
  </div>

  <table id="reportTable">
    <thead>
      <tr>
        <th rowspan="2">DATE</th>
        <th rowspan="2">ICS/RRSP No.</th>
        <th rowspan="2">Semi-expandable Property No.</th>
        <th rowspan="2">ITEM DESCRIPTION</th>
        <th rowspan="2">Estimated Useful Life</th>
        <th colspan="2">ISSUED</th>
        <th colspan="2">RETURNED</th>
        <th colspan="2">RE-ISSUED</th>
        <th colspan="1">Disposed</th>
        <th colspan="1">Balance</th>
        <th rowspan="2">Amount</th>
        <th rowspan="2">REMARKS</th>
      </tr>
      <tr>
        <th>QTY.</th>
        <th>Office/Officer</th>
        <th>QTY.</th>
        <th>Office/Officer</th>
        <th>QTY.</th>
        <th>Office/Officer</th>
        <th>QTY</th>
        <th>QTY</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $count = 0;
      if (!empty($issued_items)):
        foreach ($issued_items as $item):
          $count++;
      ?>
        <tr>
          <td><?= date('Y-m-d', strtotime($item['date'])); ?></td>
          <td><?= htmlspecialchars($item['ics_no']); ?></td>
          <td><?= htmlspecialchars($item['sep_inv_item_no']); ?></td>
          <td><?= htmlspecialchars($item['item_description']); ?></td>
          <td><?= htmlspecialchars($item['estimated_use']); ?></td>
          <td><?= (int)$item['qty']; ?></td>
          <td><?= htmlspecialchars($item['officer']); ?></td>
          <td>&nbsp;</td><td>&nbsp;</td>
          <td>&nbsp;</td><td>&nbsp;</td>
          <td>&nbsp;</td><td>&nbsp;</td>
          <td><?= htmlspecialchars(number_format($item['unit_cost'], 2)); ?></td>
          <td><?= htmlspecialchars($item['remarks']); ?></td>
        </tr>
      <?php
        endforeach;
      endif;

      $empty_rows = max(0, 14 - $count);
      for ($i=0; $i<$empty_rows; $i++):
      ?>
        <tr>
          <?php for ($j=0; $j<16; $j++): ?>
            <td>&nbsp;</td>
          <?php endfor; ?>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
  const filter = this.value.toLowerCase();
  document.querySelectorAll('#reportTable tbody tr').forEach(row => {
    const propertyNo = row.cells[2].innerText.toLowerCase();
    const itemDesc = row.cells[3].innerText.toLowerCase();
    row.style.display = (propertyNo.includes(filter) || itemDesc.includes(filter)) ? '' : 'none';
  });
});
</script>

<?php include_once('layouts/footer.php'); ?>
