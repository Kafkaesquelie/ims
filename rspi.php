<?php
$page_title = 'Registry of Semi-Expendable Property Issued';
require_once('includes/load.php');
page_require_level(1);

// ✅ Fetch fund clusters
$fund_clusters = find_all('fund_clusters');

// ✅ Fetch issued semi-expendable property
$issued_items = find_by_sql("
    SELECT 
        t1.transaction_date AS date,
        t1.ICS_No AS ics_no,
        sep.inv_item_no AS sep_inv_item_no,
        sep.item_description,
        sep.unit_cost,
        sep.estimated_use,
        t1.quantity AS qty_issued,
        t1.qty_returned AS qty_returned,
        t1.qty_re_issued AS qty_re_issued,
        CONCAT(e.first_name, ' ', 
               COALESCE(CONCAT(LEFT(e.middle_name, 1), '. '), ''), 
               e.last_name) AS officer,
        t1.status,
        t1.remarks,
        sep.fund_cluster
    FROM transactions t1
    LEFT JOIN semi_exp_prop sep ON t1.item_id = sep.id
    LEFT JOIN employees e ON t1.employee_id = e.id
    WHERE sep.inv_item_no IS NOT NULL
      AND t1.transaction_type IN ('issue', 'return')
    ORDER BY t1.transaction_date DESC
");
?>

<?php include_once('layouts/header.php'); ?>

<style>
  table,
  th,
  td,
  .header,
  .meta {
    font-family: 'Times New Roman', serif;
    font-size: 12px;
  }

  .controls {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    gap: 10px;
  }

  .filter-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
  }

  .search-container {
    position: relative;
    display: inline-block;
  }

  .search-container i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 10;
  }

  .controls input,
  .controls select {
    padding: 8px 12px;
    font-size: 12px;
    width: 180px;
    border: 1px solid #ced4da;
    border-radius: 25px;
    background: white;
    transition: all 0.3s ease;
  }

  .search-container input {
    padding-left: 35px !important;
  }

  .controls input:focus,
  .controls select:focus {
    outline: none;
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
  }

  .controls button {
    background: #055919ff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .controls button:hover {
    background: #155c04ff;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    margin-right: 40px;
    line-height: 1.6;
  }

  .editable {
    border: none;
    border-bottom: 1px dashed #555;
    min-width: 230px;
    padding: 2px 4px;
    font-size: 12px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }

  table,
  th,
  td {
    border: 1px solid #000;
  }

  th,
  td {
    padding: 4px;
    text-align: center;
  }

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

    .editable {
      border: none;
      background: transparent;
    }

    .controls {
      display: none;
    }
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .controls {
      flex-direction: column;
      align-items: stretch;
    }

    .filter-group {
      justify-content: center;
    }

    .controls input,
    .controls select {
      width: 100%;
      max-width: 200px;
    }

    .controls button {
      width: 100%;
      max-width: 200px;
      justify-content: center;
      margin: 0 auto;
    }
  }
</style>

<!-- 🔽 FILTERS PANEL -->
<div class="card p-2" style="border-top: 5px solid #28a745; border-radius: 10px;">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <h5 class="mb-2 mb-md-0 text-center text-md-start" style="font-family: 'Times New Roman', serif;">
      <strong>REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED</strong>
    </h5>
    <div class="text-center text-md-end">
      <div class="controls">
        <div class="filter-group">
          <select id="fundSelect" class="rounded-select">
            <option value=""> Select Fund Cluster </option>
            <?php foreach ($fund_clusters as $fund): ?>
              <option value="<?= htmlspecialchars($fund['name']); ?>">
                <?= htmlspecialchars($fund['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select id="valueFilter" class="rounded-select">
            <option value=""> Value Filter </option>
            <option value="low">Low Value (≤ ₱5,000)</option>
            <option value="high">High Value (₱5,001–₱50,000)</option>
          </select>

          <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search items..." class="rounded-input">
          </div>
        </div>
        <button id="printPreviewBtn">
          <i class="fa-solid fa-print"></i>
        </button>

      </div>
    </div>
  </div>
</div>

<form id="printForm" action="print_registry.php" method="POST" target="_blank" style="display:none;">
  <input type="hidden" name="fund_cluster" id="formFundCluster">
  <input type="hidden" name="value_filter" id="formValueFilter">
  <input type="hidden" name="search" id="formSearch">
  <input type="hidden" name="tableData" id="formTableData">
</form>


<!-- 🔽 PRINT AREA -->
<div id="print-area">
  <div class="meta">
    <div>
      <strong>Entity Name:</strong>
      <span>Benguet State University - Bokod Campus</span><br>
      <strong>Semi-expandable Property:</strong>
    </div>
    <div>
      <strong>Fund Cluster:</strong>
      <span id="fundClusterField">__________</span><br>
      <strong>Sheet No.:</strong>
      <input type="text" class="editable" placeholder="______________">
    </div>
  </div>


  <table id="reportTable">
    <thead>
      <tr>
        <th rowspan="2">DATE</th>
        <th colspan="2">REFERENCE</th>
        <th rowspan="2">ITEM DESCRIPTION</th>
        <th rowspan="2">Estimated Useful Life</th>
        <th colspan="2">ISSUED</th>
        <th colspan="2">RETURNED</th>
        <th colspan="2">RE-ISSUED</th>
        <th colspan="1">Disposed</th>
        <th colspan="1">Balance</th>
        <th rowspan="2">Amount</th>
        <th rowspan="2">Remarks</th>
      </tr>
      <tr>
        <th>ICS/RRSP No.</th>
        <th>Semi-expandable Property No.</th>
        <th>QTY</th>
        <th>Officer</th>
        <th>QTY</th>
        <th>Officer</th>
        <th>QTY</th>
        <th>Officer</th>
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
          <tr data-fund-cluster="<?= htmlspecialchars($item['fund_cluster']); ?>" data-unit-cost="<?= $item['unit_cost']; ?>">
            <td><?= date('Y-m-d', strtotime($item['date'])); ?></td>
            <td><?= htmlspecialchars($item['ics_no']); ?></td>
            <td><?= htmlspecialchars($item['sep_inv_item_no']); ?></td>
            <td><?= htmlspecialchars($item['item_description']); ?></td>
            <td><?= htmlspecialchars($item['estimated_use']); ?></td>
            <td><?= (int)$item['qty_issued']; ?></td>
            <td><?= htmlspecialchars($item['officer']); ?></td>
            <td><?= (int)$item['qty_returned']; ?></td>
            <td><?= $item['qty_returned'] > 0 ? htmlspecialchars($item['officer']) : ''; ?></td>
            <td><?= (int)$item['qty_re_issued']; ?></td>
            <td><?= $item['qty_re_issued'] > 0 ? htmlspecialchars($item['officer']) : ''; ?></td>
            <td></td>
            <td><?= max(0, (int)$item['qty_issued'] - (int)$item['qty_returned']); ?></td>
            <td><?= number_format($item['unit_cost'], 2); ?></td>
            <td><?= nl2br(htmlspecialchars($item['remarks'])); ?></td>
          </tr>
      <?php endforeach;
      endif; ?>

      <?php
      $total_rows = 20;
      $empty_rows = max(0, $total_rows - $count);
      for ($i = 0; $i < $empty_rows; $i++): ?>
        <tr><?php for ($j = 0; $j < 15; $j++): ?><td>&nbsp;</td><?php endfor; ?></tr>
      <?php endfor; ?>
    </tbody>
  </table>
</div>

<!-- 🔽 SCRIPT -->
<script>
  const searchInput = document.getElementById('searchInput');
  const fundSelect = document.getElementById('fundSelect');
  const valueFilter = document.getElementById('valueFilter');
  const fundClusterField = document.getElementById('fundClusterField');

  function filterTable() {
    const search = searchInput.value.toLowerCase();
    const fund = fundSelect.value.toLowerCase();
    const value = valueFilter.value;

    document.querySelectorAll('#reportTable tbody tr').forEach(row => {
      // Check if row is empty (no data in first few cells)
      const firstCell = row.cells[0]?.innerText.trim();
      const isEmptyRow = !firstCell; // true if it's one of the blank rows

      // Skip filtering for empty rows
      if (isEmptyRow) {
        row.style.display = '';
        return;
      }

      const propertyNo = row.cells[2].innerText.toLowerCase();
      const desc = row.cells[3].innerText.toLowerCase();
      const fundText = row.dataset.fundCluster?.toLowerCase() || '';
      
      // Get unit cost from data attribute (more reliable than parsing formatted text)
      const unitCost = parseFloat(row.dataset.unitCost) || 0;

      let show = true;

      if (search && !(propertyNo.includes(search) || desc.includes(search))) show = false;
      if (fund && !fundText.includes(fund)) show = false;

      // Fixed value filter logic
      if (value === 'low') {
        show = show && unitCost <= 5000;
      } else if (value === 'high') {
        show = show && unitCost > 5000 && unitCost <= 50000;
      }

      row.style.display = show ? '' : 'none';
    });
  }

  // 🔄 Filter and update fund cluster field when dropdown changes
  fundSelect.addEventListener('change', () => {
    fundClusterField.textContent = fundSelect.value || '__________';
    filterTable();
  });

  searchInput.addEventListener('input', filterTable);
  valueFilter.addEventListener('change', filterTable);

  // Add Enter key support for search
  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      filterTable();
    }
  });

  // Initial filter on page load
  document.addEventListener('DOMContentLoaded', filterTable);
</script>

<script>
  document.getElementById('printPreviewBtn').addEventListener('click', () => {
    const fund = fundSelect.value;
    const value = valueFilter.value;
    const search = searchInput.value;

    // Gather currently visible table rows (after filtering)
    const rows = [];
    document.querySelectorAll('#reportTable tbody tr').forEach(row => {
      if (row.style.display !== 'none' && row.cells[0]?.innerText.trim() !== '') {
        const cells = Array.from(row.cells).map(td => td.innerText.trim());
        rows.push(cells);
      }
    });

    // Fill form data
    document.getElementById('formFundCluster').value = fund;
    document.getElementById('formValueFilter').value = value;
    document.getElementById('formSearch').value = search;
    document.getElementById('formTableData').value = JSON.stringify(rows);

    // Submit to open in new tab
    document.getElementById('printForm').submit();
  });
</script>

<?php include_once('layouts/footer.php'); ?>