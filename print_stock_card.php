<?php
$page_title = 'Printable Stock Card';
require_once('includes/load.php');
page_require_level(1);

$stock_card_input = $_GET['stock_card'] ?? null;
$category = $_GET['category'] ?? '';
$item = $_GET['item'] ?? '';
$stock = null;
$stock_transactions = [];

// If item filter is selected, use it for search
$search_input = $item ?: $stock_card_input;

if (!empty($search_input)) {
    // Fetch item details first with unit from units table
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
    $stock = !empty($items) ? $items[0] : null;
    
    if ($stock) {
        $item_id = $stock['id'];
        $item_unit_cost = $stock['unit_cost'];
        
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
                '' AS office_name
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
                    '' AS office_name
                FROM stock_history sh
                JOIN items i ON sh.item_id = i.id
                WHERE sh.item_id = '{$db->escape($item_id)}'
                AND (sh.remarks LIKE '%carry%' OR sh.remarks LIKE '%year%')
                ORDER BY sh.date_changed ASC
            ";
            $carry_forwards = find_by_sql($year_end_sql);
        }
        
        // Combine all transactions
        $all_transactions = array_merge($stock_history, $issuances, $carry_forwards);
        
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
        $current_balance = $stock['current_balance'];
        
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
        }
        
        // Reverse back to chronological order
        $stock_transactions = array_reverse($reversed_transactions);
    }
}

// Helper function to define transaction order for same-date sorting
function getTransactionOrder($type) {
    switch($type) {
        case 'carry_forward': return 1;
        case 'stock_in': return 2;
        case 'issuance': return 3;
        default: return 4;
    }
}

// Prepare data for Word export
$word_data = [
    'stock_number' => $stock['stock_number'] ?? 'N/A',
    'item_name' => strtoupper($stock['item_name'] ?? 'N/A'),
    'description' => $stock['description'] ?? 'N/A',
    'unit_of_measurement' => $stock['unit_name'] ?? 'N/A',
    'fund_cluster' => $stock['fund_cluster'] ?? 'N/A',
    'reorder_point' => '',
    'transactions' => $stock_transactions
];
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

.button-container {
    position: fixed;
    top: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 1000;
    background: #f8fff9;
    border: 3px solid #28a745;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.action-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'Times New Roman', serif;
    min-width: 180px;
    justify-content: center;
}

.print-btn {
    background: #190361ff;
    color: white;
}

.print-btn:hover {
    background: #220184ff;
    transform: translateY(-2px);
}

.word-btn {
    background: #1d6f42;
    color: white;
}

.word-btn:hover {
    background: #155c33;
    transform: translateY(-2px);
}

.excel-btn {
    background: #217346;
    color: white;
}

.excel-btn:hover {
    background: #1a5c38;
    transform: translateY(-2px);
}

.close-btn {
    background: #6c757d;
    color: white;
}

.close-btn:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.footer{
    font-family:'Times New Roman', serif;
    font-size:10px;
}

table {
    width: 100%;         
    table-layout: fixed;
}

@media print {
    body {
        margin: 10px;
    }
    .button-container {
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

<div class="button-container">
    <button class="action-btn print-btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print
    </button>
    <button class="action-btn word-btn" onclick="exportToWord()">
        <i class="fa-solid fa-file-word"></i> Export to Word
    </button>
    <button class="action-btn excel-btn" onclick="exportToExcel()">
        <i class="fa-solid fa-file-excel"></i> Export to Excel
    </button>
    <button class="action-btn close-btn" onclick="closeWindow()">
        <i class="fa-solid fa-times"></i> Close
    </button>
</div>

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
      <img src="uploads/other/SPMO.png" alt="Logo Right 1" style="max-width:80px; height:auto;">
      <img src="uploads/other/BP.PNg" alt="Logo Right 2" style="max-width:100px; height:auto;">
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
          <?= $stock['stock_number'] ?? 'N/A'; ?>
        </span></strong> 
      </td>
       <td class="text-end">
        <strong style="font-size:10px;">Re-order Point:</strong> 
        <input type="text" name="reorder_point" 
               value="" 
               style="margin-left:15px; border:none; border-bottom:1px solid #000; outline:none; min-width:150px; text-align:center;">
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <strong style="font-size:10px;">ITEM:</strong> 
       <strong><span style="margin-left:120px;font-size:12px; display:inline-block; border-bottom:1px solid #000; min-width:200px;text-align:center;">
             <?= strtoupper($stock['item_name'] ?? 'N/A'); ?>
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
          <?= $stock['unit_name'] ?? 'N/A'; ?>
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
      <?php if(!empty($stock_transactions)): ?>
        <?php foreach($stock_transactions as $transaction): ?>
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
        <tr><td colspan="12" class="text-muted" style="font-size:35px; padding:5px"> 
          <i class="fa-solid fa-chalkboard-user"></i> 
          <?= $search_input ? 'No stock transactions found for this search.' : 'Enter a stock number to search.' ?>
        </td></tr>
      <?php endif; ?>
    </tbody>
     <tbody>
  <?php 
    $count = !empty($stock_transactions) ? count($stock_transactions) : 0;
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

<p class="text-center text-muted footer"> This form was generated electronically by the School Inventory Management System</p>

<script>
function closeWindow() {
    if (window.opener) {
        window.close();
    } else {
        window.history.back();
    }
}

function exportToWord() {
    // Create a temporary form to submit data to Word template
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_stock_card.php';
    form.target = '_blank';
    
    // Add data as hidden inputs with template reference
    const data = <?= json_encode($word_data); ?>;
    
    // Add template reference
    const templateInput = document.createElement('input');
    templateInput.type = 'hidden';
    templateInput.name = 'template';
    templateInput.value = 'STOCKCARD_Template';
    form.appendChild(templateInput);
    
    // Add all data fields
    for (const key in data) {
        if (key === 'transactions') {
            // Handle transactions array
            data[key].forEach((transaction, index) => {
                for (const field in transaction) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `transactions[${index}][${field}]`;
                    input.value = transaction[field];
                    form.appendChild(input);
                }
            });
        } else {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            form.appendChild(input);
        }
    }
    
    // Add calculated fields
    const currentDateInput = document.createElement('input');
    currentDateInput.type = 'hidden';
    currentDateInput.name = 'current_date';
    currentDateInput.value = new Date().toISOString().split('T')[0];
    form.appendChild(currentDateInput);
    
    const generationDateInput = document.createElement('input');
    generationDateInput.type = 'hidden';
    generationDateInput.name = 'generation_date';
    generationDateInput.value = new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    form.appendChild(generationDateInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function exportToExcel() {
    // Create a temporary form to submit data to Excel export
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_stock_card_excel.php';
    form.target = '_blank';
    
    // Add data as hidden inputs
    const data = <?= json_encode($word_data); ?>;
    
    // Add all data fields
    for (const key in data) {
        if (key === 'transactions') {
            // Handle transactions array
            data[key].forEach((transaction, index) => {
                for (const field in transaction) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `transactions[${index}][${field}]`;
                    input.value = transaction[field];
                    form.appendChild(input);
                }
            });
        } else {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            form.appendChild(input);
        }
    }
    
    // Add current date
    const currentDateInput = document.createElement('input');
    currentDateInput.type = 'hidden';
    currentDateInput.name = 'export_date';
    currentDateInput.value = new Date().toISOString().split('T')[0];
    form.appendChild(currentDateInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>

</body>
</html>