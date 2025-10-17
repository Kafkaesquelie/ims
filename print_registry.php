<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fund_cluster = $_POST['fund_cluster'] ?? '';
  $value_filter = $_POST['value_filter'] ?? '';
  $search = $_POST['search'] ?? '';
  $tableData = json_decode($_POST['tableData'], true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registry of Semi-Expendable Property Issued - Print Preview</title>
<style>
  body {
    font-family: 'Times New Roman', serif;
    font-size: 12px;
    margin: 0.5in;
  }

  h3 {
    text-align: center;
    margin-bottom: 5px;
  }

  p {
    margin: 2px 0;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    table-layout: fixed;
  }

  th, td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
    word-wrap: break-word;
  }

  th {
    background: #f8f8f8;
  }

  /* Set proportional widths to fit long bond paper (13 inches) */
  th:nth-child(1), td:nth-child(1) { width: 6%; }   /* DATE */
  th:nth-child(2), td:nth-child(2) { width: 7%; }   /* ICS/RRSP No. */
  th:nth-child(3), td:nth-child(3) { width: 8%; }   /* Property No. */
  th:nth-child(4), td:nth-child(4) { width: 15%; }  /* Item Description */
  th:nth-child(5), td:nth-child(5) { width: 7%; }   /* Estimated Use */
  th:nth-child(6), td:nth-child(6) { width: 5%; }   /* Qty Issued */
  th:nth-child(7), td:nth-child(7) { width: 9%; }   /* Officer */
  th:nth-child(8), td:nth-child(8) { width: 5%; }   /* Qty Returned */
  th:nth-child(9), td:nth-child(9) { width: 9%; }   /* Returned Officer */
  th:nth-child(10), td:nth-child(10){ width: 5%; }   /* Qty Re-Issued */
  th:nth-child(11), td:nth-child(11){ width: 9%; }   /* Re-Issued Officer */
  th:nth-child(12), td:nth-child(12){ width: 5%; }   /* Balance */
  th:nth-child(13), td:nth-child(13){ width: 6%; }   /* Amount */
  th:nth-child(14), td:nth-child(14){ width: 5%; }   /* Fund Cluster */
  th:nth-child(15), td:nth-child(15){ width: 9%; }   /* Remarks */

  .meta {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    margin-bottom: 5px;
  }

  .editable {
    border: none;
    border-bottom: 1px dashed #555;
    min-width: 120px;
  }

  @page {
    size: 8.5in 13in landscape;
    margin: 0.5in;
  }

  @media print {
    button { display: none; }
  }
</style>
</head>
<body>

  <h3>REGISTRY OF SEMI-EXPENDABLE PROPERTY ISSUED</h3>

  <div class="meta">
    <div>
      <p><strong>Entity Name:</strong> Benguet State University - Bokod Campus</p>
      <p><strong>Semi-expandable Property:</strong> _________________________</p>
    </div>
    <div>
      <p><strong>Fund Cluster:</strong> <?= htmlspecialchars($fund_cluster ?: '__________') ?></p>
      <p><strong>Sheet No.:</strong> _________________________</p>
    </div>
  </div>

  <table>
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
        <th rowspan="2">Balance</th>
        <th rowspan="2">Amount</th>
        <th rowspan="2">Fund Cluster</th>
        <th rowspan="2">Remarks</th>
      </tr>
      <tr>
        <th>QTY</th>
        <th>Officer</th>
        <th>QTY</th>
        <th>Officer</th>
        <th>QTY</th>
        <th>Officer</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $count = 0;
        if (!empty($tableData)):
          foreach ($tableData as $row):
            $count++;
            echo '<tr>';
            foreach ($row as $cell) {
              echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
          endforeach;
        endif;

        // Fill up to 25 rows for full long bond page
        $total_rows = 25;
        $empty_rows = max(0, $total_rows - $count);
        for ($i = 0; $i < $empty_rows; $i++):
          echo '<tr>';
          for ($j = 0; $j < 15; $j++) echo '<td>&nbsp;</td>';
          echo '</tr>';
        endfor;
      ?>
    </tbody>
  </table>

  <button onclick="window.print()">🖨️ Print</button>

  <script>
    // Auto-print on load (optional: remove if you want manual print)
    // window.onload = () => window.print();
  </script>

</body>
</html>
