<?php
require_once 'includes/load.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Increase memory and execution time
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Get the PAR number from URL parameter
$par_no = isset($_GET['par_no']) ? trim($db->escape($_GET['par_no'])) : null;

if (!$par_no) {
    $session->msg("d", "No PAR number provided.");
    redirect('logs.php');
}

// 🟩 Fetch ALL transactions with the same PAR number
$sql = "
    SELECT 
        t.id,
        t.par_no,
        t.item_id,
        p.property_no,
        p.article AS item_name,
        p.description,
        p.unit_cost,
        p.date_acquired,
        p.unit,
        p.fund_cluster,
        t.quantity,
        t.transaction_date,
        t.status,
        t.remarks,
        CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
        e.position,
        e.office,
        e.image
    FROM transactions t
    LEFT JOIN properties p ON t.properties_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.par_no = '{$par_no}'
    ORDER BY p.article ASC
";

$transactions = find_by_sql($sql);

if (empty($transactions)) {
    $session->msg("d", "No transactions found for PAR: {$par_no}");
    redirect('logs.php');
}

$first_transaction = $transactions[0];
$current_user = current_user();

// 🟩 DEBUG: Check what's in current_user
error_log("Current User Data: " . print_r($current_user, true));

// 🟩 FIX: Get current user details properly
$current_user_name = '';
$current_user_position = '';

// Try to get user name from different possible fields
if (isset($current_user['name']) && !empty($current_user['name'])) {
    $current_user_name = $current_user['name'];
} elseif (isset($current_user['username']) && !empty($current_user['username'])) {
    $current_user_name = $current_user['username'];
} elseif (isset($current_user['first_name']) && !empty($current_user['first_name'])) {
    $current_user_name = trim($current_user['first_name'] . ' ' . ($current_user['last_name'] ?? ''));
} else {
    // Fallback to session or default
    $current_user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'System Administrator';
}

// Try to get position from different possible fields
if (isset($current_user['position']) && !empty($current_user['position'])) {
    $current_user_position = $current_user['position'];
} elseif (isset($current_user['role']) && !empty($current_user['role'])) {
    $current_user_position = $current_user['role'];
} else {
    $current_user_position = 'Administrator';
}

// Use transaction date or current date
$transaction_date = !empty($first_transaction['transaction_date']) ? 
    $first_transaction['transaction_date'] : date('Y-m-d');

$fund_cluster_display = !empty($first_transaction['fund_cluster']) ? $first_transaction['fund_cluster'] : 'General Fund';

// 🟩 DEBUG: Check the values we're going to use
error_log("PAR Export Debug:");
error_log("Current User Name: " . $current_user_name);
error_log("Current User Position: " . $current_user_position);
error_log("Transaction Date: " . $transaction_date);
error_log("Employee Name: " . $first_transaction['employee_name']);
error_log("Employee Position: " . $first_transaction['position']);

try {
    // Check if template exists
    $templatePath = 'templates/PAR_Template.xlsx';
    if (!file_exists($templatePath)) {
        throw new Exception("Template file not found: " . $templatePath);
    }

    // Load the template
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // 🟩 Fill header information - PAR SPECIFIC
    $sheet->setCellValue('F9', $par_no);  // PAR No
    $sheet->setCellValue('C9', $fund_cluster_display);  // Fund Cluster

    // 🟩 Fill in the items - PAR SPECIFIC COLUMNS
    $row = 12;  // Starting row for items
    foreach ($transactions as $item) {
        $total_cost = ($item['unit_cost'] ?? 0) * ($item['quantity'] ?? 0);
        
        $sheet->setCellValue('A' . $row, $item['quantity']); // Qty
        $sheet->setCellValue('B' . $row, $item['unit']); // Unit
        $sheet->setCellValue('C' . $row, $item['item_name']); // Item Description
        $sheet->setCellValue('D' . $row, $item['property_no']); // Property No
        $sheet->setCellValue('E' . $row, !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : 'N/A'); // Date Acquired
        $sheet->setCellValue('F' . $row, '₱' . number_format($total_cost, 2)); // Amount
        
        $row++;
    }

    // 🟩 ADD SIGNATORIES - CORRECTED VERSION FOR PAR
    $signatory_row = 43; // Adjust this based on your PAR template layout
    
    // 🟩 FIX: Issued by (Current User) - CORRECTED FOR PAR
    $sheet->setCellValue('D' . $signatory_row, strtoupper($current_user_name));
    $sheet->setCellValue('D' . ($signatory_row + 2), $current_user_position);
    $sheet->setCellValue('D' . ($signatory_row + 4), date('M d, Y', strtotime($transaction_date)));
    
    // 🟩 FIX: Received by (Employee) - CORRECTED FOR PAR
    $sheet->setCellValue('A' . $signatory_row, strtoupper($first_transaction['employee_name'] ?? 'N/A'));
    $sheet->setCellValue('A' . ($signatory_row + 2), $first_transaction['position'] ?? 'N/A');
    $sheet->setCellValue('A' . ($signatory_row + 4), date('M d, Y', strtotime($transaction_date)));

    // 🟩 TEMPORARY DEBUG: Uncomment to see what's being written to Excel
    /*
    echo "<h3>DEBUG INFORMATION:</h3>";
    echo "<p><strong>Current User Name:</strong> " . $current_user_name . "</p>";
    echo "<p><strong>Current User Position:</strong> " . $current_user_position . "</p>";
    echo "<p><strong>Transaction Date:</strong> " . date('M d, Y', strtotime($transaction_date)) . "</p>";
    echo "<p><strong>Employee Name:</strong> " . ($first_transaction['employee_name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Employee Position:</strong> " . ($first_transaction['position'] ?? 'N/A') . "</p>";
    echo "<p><strong>Current User Array:</strong></p>";
    echo "<pre>";
    print_r($current_user);
    echo "</pre>";
    exit();
    */

    // Clear output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }

    // 🟩 Export the spreadsheet
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="PAR_' . $par_no . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    // Fallback: Create new spreadsheet if template fails
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
    
        // Fund Cluster and PAR No
        $sheet->setCellValue('C9', 'Fund Cluster: ' . $fund_cluster_display);
        $sheet->setCellValue('F9', 'PAR No: ' . $par_no);
        
        // Set table headers - PAR SPECIFIC
        $headers = ['Quantity', 'Unit', 'Description', 'Property No', 'Date Acquired', 'Amount'];
        $col = 'A';
        $header_row = 11;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $header_row, $header);
            $col++;
        }
        
        // Fill data - PAR SPECIFIC
        $row = 12;
        foreach ($transactions as $item) {
            $total_cost = ($item['unit_cost'] ?? 0) * ($item['quantity'] ?? 0);
            
            $sheet->setCellValue('A' . $row, $item['quantity']);
            $sheet->setCellValue('B' . $row, $item['unit']);
            $sheet->setCellValue('C' . $row, $item['ARTICLE']);
            $sheet->setCellValue('D' . $row, $item['property_no']);
            $sheet->setCellValue('E' . $row, !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : 'N/A');
            $sheet->setCellValue('F' . $row, '₱' . number_format($total_cost, 2));
            $row++;
        }
        
        // Add empty rows for formatting (like in PAR form)
        $empty_rows = 12;
        for ($i = 0; $i < $empty_rows; $i++) {
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, '');
            $sheet->setCellValue('C' . $row, '');
            $sheet->setCellValue('D' . $row, '');
            $sheet->setCellValue('E' . $row, '');
            $sheet->setCellValue('F' . $row, '');
            $row++;
        }
        
        // 🟩 FIX: Add signatories - CORRECTED FOR PAR
        $sig_row = 43;
        
        // Received by (Left side - Employee)
        $sheet->setCellValue('A' . $sig_row , strtoupper($first_transaction['employee_name'] ?? 'N/A'));
        $sheet->setCellValue('A' . ($sig_row + 2), $first_transaction['position'] ?? 'N/A');
        $sheet->setCellValue('A' . ($sig_row + 4), 'Date: ' . date('M d, Y', strtotime($transaction_date)));
        
        // Issued by (Right side - Current User)
        $sheet->setCellValue('D' . $sig_row, strtoupper($current_user_name));
        $sheet->setCellValue('D' . ($sig_row + 2), $current_user_position);
        $sheet->setCellValue('D' . ($sig_row + 4), 'Date: ' . date('M d, Y', strtotime($transaction_date)));
        
        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set column widths for better formatting
        $sheet->getColumnDimension('A')->setWidth(10);  // Quantity
        $sheet->getColumnDimension('B')->setWidth(8);   // Unit
        $sheet->getColumnDimension('C')->setWidth(35);  // Description
        $sheet->getColumnDimension('D')->setWidth(15);  // Property No
        $sheet->getColumnDimension('E')->setWidth(15);  // Date Acquired
        $sheet->getColumnDimension('F')->setWidth(15);  // Amount
        
        // Center align specific columns
        $sheet->getStyle('A12:A' . ($row - 1))->getAlignment()->setHorizontal('center'); // Quantity
        $sheet->getStyle('B12:B' . ($row - 1))->getAlignment()->setHorizontal('center'); // Unit
        $sheet->getStyle('D12:D' . ($row - 1))->getAlignment()->setHorizontal('center'); // Property No
        $sheet->getStyle('E12:E' . ($row - 1))->getAlignment()->setHorizontal('center'); // Date Acquired
        $sheet->getStyle('F12:F' . ($row - 1))->getAlignment()->setHorizontal('right');  // Amount
        
        // Add borders to table
        $table_range = 'A11:F' . ($row - $empty_rows - 1);
        $sheet->getStyle($table_range)->getBorders()->getAllBorders()->setBorderStyle('thin');
        
        // Clear output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="PAR_' . $par_no . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        
    } catch (Exception $fallbackError) {
        // Log the error
        error_log("PAR Export Error: " . $fallbackError->getMessage());
        
        // Ultimate fallback to CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="PAR_' . $par_no . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $output = fopen('php://output', 'w');
        
        // Write headers - PAR SPECIFIC
        fputcsv($output, ['PAR No', 'Fund Cluster', 'Date', 'Quantity', 'Unit', 'Description', 'Property No', 'Date Acquired', 'Amount', 'Received By', 'Received Position', 'Issued By', 'Issued Position']);
        
        // Write data - PAR SPECIFIC
        foreach ($transactions as $item) {
            $total_cost = ($item['unit_cost'] ?? 0) * ($item['quantity'] ?? 0);
            fputcsv($output, [
                $par_no,
                $fund_cluster_display,
                date('M d, Y', strtotime($transaction_date)),
                $item['quantity'],
                $item['unit'],
                $item['item_name'],
                $item['property_no'],
                !empty($item['date_acquired']) ? date('M d, Y', strtotime($item['date_acquired'])) : 'N/A',
                '₱' . number_format($total_cost, 2),
                $first_transaction['employee_name'] ?? 'N/A',
                $first_transaction['position'] ?? 'N/A',
                $current_user_name,
                $current_user_position
            ]);
        }
        
        fclose($output);
        exit();
    }
}
?>