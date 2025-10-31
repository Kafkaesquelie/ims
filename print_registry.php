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
    font-size: 11px;
    margin: 0.5in;
    position: relative;
}
h3 { text-align: center; margin-bottom: 5px; }
p { margin: 2px 0; }

/* FIXED TABLE STYLES - PREVENT OVERFLOW */
.table-container {
    width: 100%;
    overflow-x: auto;
    max-width: 100%;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    table-layout: fixed;
    word-wrap: break-word;
}

th, td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
    vertical-align: top;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* FIXED COLUMN WIDTHS - PROPER SUBCOLUMN STRUCTURE */
.date-col { width: 6% !important; min-width: 60px; }
.ref-col { width: 10% !important; min-width: 100px; }
.prop-col { width: 8% !important; min-width: 80px; }
.desc-col { width: 12% !important; min-width: 10px; }
.life-col { width: 6% !important; min-width: 60px; }

/* ISSUED, RETURNED, RE-ISSUED SECTIONS - WIDER COLUMNS */
.issued-section { width: 12% !important; min-width: 120px; }
.returned-section { width: 12% !important; min-width: 120px; }
.reissued-section { width: 12% !important; min-width: 120px; }

/* SUBCOLUMNS WITHIN EACH SECTION */
.qty-subcol { width: 30% !important; }
.officer-subcol { width: 70% !important; }

.disposed-col { width: 4% !important; min-width: 40px; }
.balance-col { width: 4% !important; min-width: 40px; }
.amount-col { width: 6% !important; min-width: 60px; }
.remarks-col { width: 12% !important; min-width: 120px; }

/* SPECIFIC COLUMN STYLING */
.remarks-col {
    max-width: 180px;
    white-space: normal;
    word-break: break-word;
    line-height: 1.2;
}

.desc-col {
    max-width: 180px;
    white-space: normal;
    word-break: break-word;
    line-height: 1.2;
}

.officer-subcol {
    max-width: 100px;
    white-space: normal;
    word-break: break-word;
    line-height: 1.2;
}

.ref-col {
    max-width: 90px;
    white-space: normal;
    word-break: break-word;
}

.prop-col {
    max-width: 90px;
    white-space: normal;
    word-break: break-word;
}

/* Ensure table fits within viewport */
#registryTable {
    max-width: 100%;
    margin: 0 auto;
}

/* Header styling for grouped columns */
.section-header {
    background: #e8e8e8 !important;
    font-weight: bold;
}

.subcol-header {
    background: #f0f0f0 !important;
}

th { background: #f8f8f8; }
.meta { display: flex; justify-content: space-between; margin-top: 10px; margin-bottom: 5px; }

/* VERTICAL BUTTON CONTAINER */
.print-btn-container {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    z-index: 1000;
    border: 2px solid #28a745;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 140px;
}

.print-btn, .word-btn, .close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    width: 100%;
    min-width: 120px;
}

.print-btn {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.print-btn:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
}

.word-btn {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
}

.word-btn:hover {
    background: linear-gradient(135deg, #1e7e34, #155724);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.close-btn {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.close-btn:hover {
    background: linear-gradient(135deg, #5a6268, #495057);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
}

.print-btn:active, .word-btn:active, .close-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.print-btn i, .word-btn i, .close-btn i {
    font-size: 16px;
    margin-right: 8px;
}

@page { 
    size: 8.5in 13in landscape; 
    margin: 0.5in; 
}
@media print { 
    .print-btn-container { display: none !important; } 
    body { margin: 0.4in; } 
    table { font-size: 10px; } 
    
    /* Ensure print layout maintains fixed widths */
    .date-col, .ref-col, .prop-col, .desc-col, .life-col, 
    .issued-section, .returned-section, .reissued-section,
    .disposed-col, .balance-col, .amount-col, .remarks-col {
        width: auto !important;
    }
    
    th, td {
        padding: 3px;
    }
}

/* Additional overflow protection */
.content-wrapper {
    max-width: 100%;
    overflow-x: hidden;
}

/* Better text handling */
.text-content {
    line-height: 1.2;
    padding: 2px;
}
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

function handleClose() {
    // Check if this is a popup window or main window
    if (window.opener && !window.opener.closed) {
        // This is a popup window - close it
        window.close();
    } else {
        // This might be a main window - go back or show message
        if (history.length > 1) {
            history.back();
        } else {
            // If no history, just close the window/tab
            window.close();
        }
    }
}

// Handle keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+P or Cmd+P for print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    // Escape key to close
    if (e.key === 'Escape') {
        handleClose();
    }
});
</script>

</head>
<body>

<!-- VERTICAL BUTTON CONTAINER -->
<div class="print-btn-container">
    <!-- <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print Report
    </button> -->
    
    <form method="POST" style="width: 100%; margin: 0;">
        <input type="hidden" name="export_excel" value="1">
        <input type="hidden" name="fund_cluster" value="<?= htmlspecialchars($fund_cluster) ?>">
        <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm ?? '') ?>">
        <input type="hidden" name="tableData" value='<?= json_encode($tableData) ?>'>
        <button type="submit" class="word-btn">
            <i class="fas fa-file-excel"></i> Export to Excel
        </button>
    </form>
    
    <button class="close-btn" onclick="handleClose()">
        <i class="fas fa-times"></i> Close
    </button>
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

<div class="table-container">
    <table id="registryTable">
        <thead>
            <tr>
                <th class="date-col" rowspan="3">DATE</th>
                <th class="ref-col" colspan="2" >REFERENCE</th>
                <th class="desc-col" rowspan="3">ITEM DESCRIPTION</th>
                <th class="life-col" rowspan="3">Estimated Useful Life</th>
                <th class="issued-section section-header" colspan="2">ISSUED</th>
                <th class="returned-section section-header" colspan="2">RETURNED</th>
                <th class="reissued-section section-header" colspan="2">RE-ISSUED</th>
                <th class="disposed-col" rowspan="3">Disposed</th>
                <th class="balance-col" rowspan="3">Balance</th>
                <th class="amount-col" rowspan="3">Amount</th>
                <th class="remarks-col" rowspan="3">Remarks</th>
            </tr>
            <tr>
                 <th class="ref-col">ICS/RRSP No.</th>
                <th class="prop-col">Semi-expandable Property No.</th>
                <!-- ISSUED Subcolumns -->
                <th class="qty-subcol subcol-header">QTY</th>
                <th class="officer-subcol subcol-header">Officer</th>
                
                <!-- RETURNED Subcolumns -->
                <th class="qty-subcol subcol-header">QTY</th>
                <th class="officer-subcol subcol-header">Officer</th>
                
                <!-- RE-ISSUED Subcolumns -->
                <th class="qty-subcol subcol-header">QTY</th>
                <th class="officer-subcol subcol-header">Officer</th>
            </tr>
          
        </thead>
        <tbody>
            <?php
            $count = 0;
            if (!empty($tableData)):
                foreach ($tableData as $row):
                    $count++;
                    echo '<tr>';
                    // Apply CSS classes to each cell based on column position
                    $columnClasses = [
                        'date-col', 
                        'ref-col', 'prop-col', 
                        'desc-col', 
                        'life-col',
                        // ISSUED section (2 columns)
                        'qty-subcol', 'officer-subcol',
                        // RETURNED section (2 columns)
                        'qty-subcol', 'officer-subcol',
                        // RE-ISSUED section (2 columns)
                        'qty-subcol', 'officer-subcol',
                        'disposed-col', 
                        'balance-col', 
                        'amount-col', 
                        'remarks-col'
                    ];
                    
                    foreach ($row as $index => $cell) {
                        $class = $columnClasses[$index] ?? '';
                        $truncatedCell = $cell;
                        
                        // Truncate long text for specific columns
                        if (in_array($class, ['desc-col', 'remarks-col']) && strlen($cell) > 60) {
                            $truncatedCell = substr($cell, 0, 57) . '...';
                        } elseif (in_array($class, ['officer-subcol']) && strlen($cell) > 25) {
                            $truncatedCell = substr($cell, 0, 22) . '...';
                        } elseif (in_array($class, ['ref-col', 'prop-col']) && strlen($cell) > 20) {
                            $truncatedCell = substr($cell, 0, 17) . '...';
                        }
                        
                        echo '<td class="' . $class . ' text-content" title="' . htmlspecialchars($cell) . '">' . htmlspecialchars($truncatedCell) . '</td>';
                    }
                    echo '</tr>';
                endforeach;
            endif;

            // Fill empty rows to reach 25
            for ($i = $count; $i < 25; $i++):
                echo '<tr>';
                for ($j = 0; $j < 15; $j++) {
                    $columnClasses = [
                        'date-col', 
                        'ref-col', 'prop-col', 
                        'desc-col', 
                        'life-col',
                        'qty-subcol', 'officer-subcol',
                        'qty-subcol', 'officer-subcol',
                        'qty-subcol', 'officer-subcol',
                        'disposed-col', 
                        'balance-col', 
                        'amount-col', 
                        'remarks-col'
                    ];
                    $class = $columnClasses[$j] ?? '';
                    echo '<td class="' . $class . ' text-content">&nbsp;</td>';
                }
                echo '</tr>';
            endfor;
            ?>
        </tbody>
    </table>
</div>

</body>
</html>