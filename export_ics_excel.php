<?php
require_once 'includes/load.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Increase memory and execution time
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Get the ICS number from URL parameter
$ics_no = isset($_GET['ics_no']) ? trim($db->escape($_GET['ics_no'])) : null;

if (!$ics_no) {
    $session->msg("d", "No ICS number provided.");
    redirect('logs.php');
}

// 🟩 Fetch ALL transactions with the same ICS number
$sql = "
    SELECT 
        t.id,
        t.ics_no,
        t.item_id,
        p.inv_item_no,
        p.item AS item_name,
        p.item_description AS description,
        p.unit_cost,
        p.unit,
        p.estimated_use,
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
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE t.ICS_No = '{$ics_no}'
      AND t.transaction_type = 'issue'
    ORDER BY p.item ASC
";

$transactions = find_by_sql($sql);

if (empty($transactions)) {
    $session->msg("d", "No transactions found for ICS: {$ics_no}");
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
error_log("ICS Export Debug:");
error_log("Current User Name: " . $current_user_name);
error_log("Current User Position: " . $current_user_position);
error_log("Transaction Date: " . $transaction_date);
error_log("Employee Name: " . $first_transaction['employee_name']);
error_log("Employee Position: " . $first_transaction['position']);

try {
    // Check if template exists
    $templatePath = 'templates/ICS_Template.xlsx';
    if (!file_exists($templatePath)) {
        throw new Exception("Template file not found: " . $templatePath);
    }

    // Load the template
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    // 🟩 Fill header information
    $sheet->setCellValue('G12', $ics_no);
    $sheet->setCellValue('G13', $fund_cluster_display);

    // 🟩 Fill in the items
    $row = 17;  // Starting row for items
    foreach ($transactions as $item) {
        $total_cost = ($item['unit_cost'] ?? 0) * ($item['quantity'] ?? 0);
        
        $sheet->setCellValue('A' . $row, $item['quantity']); // Qty
        $sheet->setCellValue('B' . $row, $item['unit']); // Unit
        $sheet->setCellValue('C' . $row, $item['unit_cost']); // Unit Cost
        $sheet->setCellValue('D' . $row, $total_cost); // Total Cost
        $sheet->setCellValue('E' . $row, $item['item_name']); // Item Description
        $sheet->setCellValue('F' . $row, $item['inv_item_no']); // Inventory Item No
        $sheet->setCellValue('G' . $row, $item['estimated_use']); // Estimated Useful Life
        
        $row++;
    }

    // 🟩 ADD SIGNATORIES - CORRECTED VERSION
    $signatory_row = 42; // Adjust this based on your template layout
    
    // 🟩 FIX: Issued by (Current User) - CORRECTED
    $sheet->setCellValue('A' . $signatory_row, strtoupper($current_user_name));
    $sheet->setCellValue('B' . ($signatory_row + 2), $current_user_position);
    $sheet->setCellValue('A' . ($signatory_row + 4), date('M d, Y', strtotime($transaction_date)));
    
    // 🟩 FIX: Received by (Employee)
    $sheet->setCellValue('E' . $signatory_row, strtoupper($first_transaction['employee_name'] ?? 'N/A'));
    $sheet->setCellValue('E' . ($signatory_row + 2), $first_transaction['position'] ?? 'N/A');
    $sheet->setCellValue('E' . ($signatory_row + 4), date('M d, Y', strtotime($transaction_date)));

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
    header('Content-Disposition: attachment;filename="ICS_' . $ics_no . '.xlsx"');
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
        
        // Header
        $sheet->setCellValue('A1', 'ISSUANCE AND ACCOUNTABILITY OF SEMI-EXPENDABLE PROPERTY');
        $sheet->setCellValue('G12', 'Fund Cluster: ' . $fund_cluster_display);
        $sheet->setCellValue('G13', 'ICS No: ' . $ics_no);
        
        // Set table headers
        $headers = ['Quantity', 'Unit', 'Unit Cost', 'Total Cost', 'Description', 'Inventory Item No', 'Estimated Useful Life'];
        $col = 'A';
        $header_row = 16;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $header_row, $header);
            $col++;
        }
        
        // Fill data
        $row = 17;
        foreach ($transactions as $item) {
            $total_cost = ($item['unit_cost'] ?? 0) * ($item['quantity'] ?? 0);
            
            $sheet->setCellValue('A' . $row, $item['quantity']);
            $sheet->setCellValue('B' . $row, $item['unit']);
            $sheet->setCellValue('C' . $row, $item['unit_cost']);
            $sheet->setCellValue('D' . $row, $total_cost);
            $sheet->setCellValue('E' . $row, $item['item_name']);
            $sheet->setCellValue('F' . $row, $item['inv_item_no']);
            $sheet->setCellValue('G' . $row, $item['estimated_use']);
            $row++;
        }
        
        // 🟩 FIX: Add signatories - CORRECTED
        $sig_row = $row + 5;
        
        // Issued by
        $sheet->setCellValue('B' . ($sig_row), 'Issued By:');
        $sheet->setCellValue('B' . ($sig_row + 1), strtoupper($current_user_name));
        $sheet->setCellValue('B' . ($sig_row + 2), $current_user_position);
        $sheet->setCellValue('B' . ($sig_row + 3), 'Date: ' . date('M d, Y', strtotime($transaction_date)));
        
        // Received by
        $sheet->setCellValue('E' . ($sig_row), 'Received By:');
        $sheet->setCellValue('E' . ($sig_row + 1), strtoupper($first_transaction['employee_name'] ?? 'N/A'));
        $sheet->setCellValue('E' . ($sig_row + 2), $first_transaction['position'] ?? 'N/A');
        $sheet->setCellValue('E' . ($sig_row + 3), 'Date: ' . date('M d, Y', strtotime($transaction_date)));
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Clear output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ICS_' . $ics_no . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
        
    } catch (Exception $fallbackError) {
        // Log the error
        error_log("ICS Export Error: " . $fallbackError->getMessage());
        
        // Ultimate fallback to CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ICS_' . $ics_no . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, ['ICS No', 'Fund Cluster', 'Date', 'Quantity', 'Unit', 'Unit Cost', 'Total Cost', 'Description', 'Inventory Item No', 'Estimated Useful Life', 'Issued By', 'Issued Position', 'Received By', 'Received Position']);
        
        // Write data
        foreach ($transactions as $item) {
            $total_cost = ($item['unit_cost'] ?? 0) * ($item['quantity'] ?? 0);
            fputcsv($output, [
                $ics_no,
                $fund_cluster_display,
                date('M d, Y', strtotime($transaction_date)),
                $item['quantity'],
                $item['unit'],
                $item['unit_cost'],
                $total_cost,
                $item['item_name'],
                $item['inv_item_no'],
                $item['estimated_use'],
                $current_user_name,
                $current_user_position,
                $first_transaction['employee_name'] ?? 'N/A',
                $first_transaction['position'] ?? 'N/A'
            ]);
        }
        
        fclose($output);
        exit();
    }
}
?>