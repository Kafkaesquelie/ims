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
    position: relative;
}
h3 { text-align: center; margin-bottom: 5px; }
p { margin: 2px 0; }
table { width: 100%; border-collapse: collapse; font-size: 12px; }
th, td { border: 1px solid #000; padding: 4px; text-align: center; word-wrap: break-word; }
th { background: #f8f8f8; }
.meta { display: flex; justify-content: space-between; margin-top: 10px; margin-bottom: 5px; }
.print-btn-container {
    float: right; top: 0; right: 10px; z-index: 1000;
    display: flex; gap: 10px;
}
.print-btn, .word-btn {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white; border: none; border-radius: 8px;
    padding: 12px 20px; font-size: 14px; font-weight: 600;
    cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex; align-items: center; gap: 8px; min-width: 120px; justify-content: center;
    transition: all 0.3s ease;
}
.print-btn:hover, .word-btn:hover {
    background: linear-gradient(135deg, #1e7e34, #155724);
    transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}
.print-btn:active, .word-btn:active {
    transform: translateY(0); box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.print-btn i, .word-btn i { font-size: 16px; }

@page { size: 8.5in 13in landscape; margin: 0.5in; }
@media print { .print-btn-container { display: none !important; } body { margin: 0.4in; } table { font-size: 11px; } }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
function exportToWord() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_registry.php'; // Separate PHP file for Word export

    const fundInput = document.createElement('input');
    fundInput.type = 'hidden';
    fundInput.name = 'fund_cluster';
    fundInput.value = '<?= htmlspecialchars($fund_cluster) ?>';
    form.appendChild(fundInput);

    const tableInput = document.createElement('input');
    tableInput.type = 'hidden';
    tableInput.name = 'tableData';
    tableInput.value = JSON.stringify(<?= json_encode($tableData) ?>);
    form.appendChild(tableInput);

    document.body.appendChild(form);
    form.submit();
}
</script>

</head>
<body>

<div class="print-btn-container">
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
    <button class="word-btn" onclick="exportToWord()"><i class="fas fa-file-word"></i> Export to Word</button>
</div>

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

<table id="registryTable">
    <thead>
        <tr>
            <th rowspan="2">DATE</th>
            <th colspan="2">REFERENCE</th>
            <th rowspan="2">ITEM DESCRIPTION</th>
            <th rowspan="2">Estimated Useful Life</th>
            <th colspan="2">ISSUED</th>
            <th colspan="2">RETURNED</th>
            <th colspan="2">RE-ISSUED</th>
            <th>Disposed</th>
            <th>Balance</th>
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
        if (!empty($tableData)):
            foreach ($tableData as $row):
                $count++;
                echo '<tr>';
                foreach ($row as $cell) echo '<td>' . htmlspecialchars($cell) . '</td>';
                echo '</tr>';
            endforeach;
        endif;

        // Fill empty rows to reach 25
        for ($i = $count; $i < 25; $i++):
            echo '<tr>';
            for ($j = 0; $j < 15; $j++) echo '<td>&nbsp;</td>';
            echo '</tr>';
        endfor;
        ?>
    </tbody>
</table>

</body>
</html>
