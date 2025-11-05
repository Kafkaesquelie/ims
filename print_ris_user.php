<?php
$page_title = 'Print RIS';
require_once('includes/load.php');
page_require_level(3);

// Check if request ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $session->msg("d", "No request ID provided.");
    redirect('home.php');
}

$request_id = (int)$_GET['id'];
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Fetch request info and verify ownership
$request = find_by_id('requests', $request_id);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('home.php');
}

// Security check: Ensure the user owns this request
if ($request['requested_by'] != $user_id) {
    $session->msg("d", "You are not authorized to view this request.");
    redirect('home.php');
}

// Fetch requestor info from users table
$user = find_by_id('users', $request['requested_by']);
$requestor_name = $user ? remove_junk(ucwords($user['name'])) : 'Unknown';
$requestor_position = $user['position'] ?? '________________';

// Fetch division and office information
$division_name = '________________';
$office_name = '________________';

if ($user) {
    // If you have separate division_id and office_id in users table
    if (isset($user['division_id']) && !empty($user['division_id'])) {
        $division = find_by_id('divisions', $user['division_id']);
        if ($division) {
            $division_name = remove_junk($division['division_name']);
        }
    }
    
    if (isset($user['office_id']) && !empty($user['office_id'])) {
        $office = find_by_id('offices', $user['office_id']);
        if ($office) {
            $office_name = remove_junk($office['office_name']);
        }
    }
    
    // Alternative: If you store division/office names directly in users table
    if (empty($division_name) && isset($user['division'])) {
        $division_name = remove_junk($user['division']);
    }
    
    if (empty($office_name) && isset($user['office'])) {
        $office_name = remove_junk($user['office']);
    }
}

// Fetch requested items
$items = find_by_sql("
    SELECT 
        ri.item_id,
        ri.qty,
        ri.unit,
        ri.remarks,
        i.name as item_name,
        i.stock_card,
        i.quantity as current_stock
    FROM request_items ri
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE ri.req_id = '{$request_id}'
");

// Get the request remarks
$request_remarks = $request['remarks'] ?? '';

// Current logged-in user (for approved by/issued by)
$current_user_name = remove_junk(ucwords($current_user['name']));
$current_user_position = $current_user['position'] ?? '';

// Determine availability based on request status
$request_status = strtolower($request['status']);
$all_items_available = ($request_status == 'approved' || $request_status == 'issued' || $request_status == 'completed');

// Fetch admin user (user with level 1 or 2 for issued by)
$admin_user = find_by_sql("
    SELECT name, position 
    FROM users 
    WHERE user_level IN (1) 
    ORDER BY id DESC 
    LIMIT 1
");

$issued_by_name = '________________';
$issued_by_position = '________________';

if ($admin_user && count($admin_user) > 0) {
    $issued_by_name = remove_junk(ucwords($admin_user[0]['name']));
    $issued_by_position = $admin_user[0]['position'] ?? '________________';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print RIS - <?php echo isset($request['ris_no']) ? $request['ris_no'] : 'Request'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background: white;
            margin: 0;
            padding: 0;
        }

        .legal-container {
            width: 8.5in;
            min-height: 13in;
            margin: 0 auto;
            padding: 0.2in;
            box-sizing: border-box;
        }

        .copy-container {
            margin-bottom: 0.5in;
            padding: 0.1in;
            page-break-inside: avoid;
            border: 1px solid #000; /* Border for screen view */
        }

        .copy-header {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 0.05in;
        }

        .entity-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.05in;
            font-size: 10px;
        }

        .ris-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.05in;
            font-size: 11px;
            border: 1px solid #000; /* Ensure table has border */
        }

        .ris-table th,
        .ris-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 11px;
            line-height: 1;
        }

        .ris-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .empty-row td {
            height: 9px;
            border: 1px solid #000 !important; /* Force border on empty rows */
        }

        .purpose-cell {
            text-align: left;
            vertical-align: top;
            font-size: 7px;
            border: 1px solid #000 !important; /* Ensure purpose cell has border */
        }

        .ris-table tr{
            padding:2px;
        }

        td.text-center,
        th.text-center,
        .text-center {
            text-align: center !important;
            vertical-align: middle !important;
        }

        td.text-start,
        th.text-start,
        .text-start {
            text-align: left !important;
            vertical-align: middle !important;
        }

        td.text-end,
        th.text-end,
        .text-end {
            text-align: right !important;
            vertical-align: middle !important;
        }

        .signatory-section td {
            height: 13px;
            vertical-align: middle !important;
            font-size: 7px;
            border: 1px solid #000 !important; /* Ensure signatory cells have borders */
        }

        .signatory-section strong {
            font-size: 10px;
        }

        .action-buttons {
            position: fixed;
            left: 250px;
            top: 10%;
            transform: translateY(-50%);
            z-index: 1000;
        }

        .action-buttons .btn {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Checkbox styling */
        .checkbox-cell {
            text-align: center;
            font-size: 9px;
        }

        /* Copy labels */
        .copy-label {
            position: absolute;
            right: 0.2in;
            top: 0.3in;
            font-size: 8px;
            font-weight: bold;
            background: #f8f9fa;
            padding: 1px 4px;
            border: 1px solid #000;
        }

        @media print {
            @page {
                size: legal;
                margin: 0.2in;
            }
            
            body {
                margin: 0;
                padding: 0;
                font-size: 8px;
                background: white;
            }

            .no-print {
                display: none;
            }

            .legal-container {
                width: 100%;
                min-height: 13in;
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            .copy-container {
                border: none !important; /* Remove border when printing */
                margin-bottom: 0.1in;
                page-break-inside: avoid;
                padding: 0;
            }

            .action-buttons {
                display: none !important;
            }
            
            /* Ensure tables don't break across pages */
            .ris-table {
                page-break-inside: avoid;
                border: 1px solid #000 !important; /* Keep table border when printing */
            }

            /* Ensure all table cells have borders when printing */
            .ris-table th,
            .ris-table td {
                border: 1px solid #000 !important;
            }

            .empty-row td {
                border: 1px solid #000 !important;
            }

            .purpose-cell {
                border: 1px solid #000 !important;
            }

            .signatory-section td {
                border: 1px solid #000 !important;
            }
        }

        @media (max-width: 768px) {
            .action-buttons {
                position: relative;
                left: 0;
                top: 0;
                transform: none;
                display: flex;
                justify-content: center;
                margin-bottom: 20px;
            }
            
            .action-buttons .btn {
                margin: 0 5px;
            }
            
            .legal-container {
                width: 100%;
                padding: 10px;
            }
        }

        /* Compress content further for 3 copies */
        .compressed-content {
            transform: scale(0.95);
            transform-origin: top left;
        }
    </style>
</head>
<body>

<!-- Action Buttons - Fixed on the left side -->
<div class="action-buttons no-print">
    <!-- Print Button -->
    <button onclick="window.print()"
        class="btn btn-primary rounded-circle shadow-sm"
        title="Print">
        <i class="fa-solid fa-print"></i>
    </button>

    <!-- Back Button -->
    <a href="home.php"
        class="btn btn-secondary rounded-circle shadow-sm"
        title="Back to Dashboard">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
</div>

<!-- Main Container for Legal Paper -->
<div class="legal-container">
    <?php for ($copy = 1; $copy <= 3; $copy++): ?>
        <div class="copy-container position-relative">
            
            <div class="compressed-content">
                <div class="copy-header">
                    REQUISITION AND ISSUE SLIP
                </div>

                <div class="entity-info">
                    <div>
                        <strong>Entity Name:</strong> BENGUET STATE UNIVERSITY - BOKOD CAMPUS
                    </div>
                    <div>
                        <strong>Fund Cluster:</strong> __________
                    </div>
                </div>

                <table class="ris-table">
                    <thead>
                        <tr>
                            <th colspan="5" class="text-start">
                                <strong>Division:</strong> <?php echo $division_name; ?><br>
                                <strong>Office:</strong> <?php echo $office_name; ?>
                            </th>
                            <th colspan="3" class="text-start">
                                <strong>Responsibility Center Code:</strong> __________<br>
                                <strong>RIS No:</strong> <?php echo isset($request['ris_no']) ? $request['ris_no'] : '__________'; ?>
                            </th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-center"><i><b>Requisition</b></i></th>
                            <th colspan="2" class="text-center"><i><b>Stock Available?</b></i></th>
                            <th colspan="2" class="text-center"><i><b>Issue</b></i></th>
                        </tr>

                        <tr>
                            <th>Stock No.</th>
                            <th>Unit</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Yes</th>
                            <th>No</th>
                            <th>Quantity</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <tr class="text-center">
                                <td><?php echo (int)$item['stock_card']; ?></td>
                                <td><?php echo remove_junk($item['unit']); ?></td>
                                <td class="text-start"><?php echo remove_junk($item['item_name']); ?></td>
                                <td><?php echo (float)$item['qty']; ?></td>
                                <td class="checkbox-cell"><?php echo $all_items_available ? '✔' : ''; ?></td>
                                <td class="checkbox-cell"><?php echo !$all_items_available ? '✔' : ''; ?></td>
                                <td><?php echo $all_items_available ? (float)$item['qty'] : '0'; ?></td>
                                <td class="text-start">
                                    <?php if ($index === 0 && !empty($request_remarks)): ?>
                                        <?php echo remove_junk($request_remarks); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php
                        $item_count = count($items);
                        $empty_rows = max(0, 7 - $item_count);
                        for ($i = 0; $i < $empty_rows; $i++): ?>
                            <tr class="empty-row">
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php endfor; ?>

                        <tr>
                            <td colspan="8" class="purpose-cell">
                                <strong>Purpose:</strong> <?php echo !empty($request_remarks) ? remove_junk($request_remarks) : ''; ?>
                            </td>
                        </tr>

                        <tr class="empty-row">
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>

                        <tr class="signatory-section">
                            <td colspan="2"></td>
                            <td><strong>Requested By</strong></td>
                            <td colspan="2"><strong>Approved By</strong></td>
                            <td colspan="2"><strong>Issued By</strong></td>
                            <td><strong>Received By</strong></td>
                        </tr>

                        <tr class="signatory-section">
                            <td colspan="2" class="text-start">Signature:</td>
                            <td>&nbsp;</td>
                            <td colspan="2">&nbsp;</td>
                            <td colspan="2">&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>

                        <tr class="signatory-section">
                            <td colspan="2" class="text-start">Printed Name:</td>
                            <td class="text-center"><?php echo $requestor_name; ?></td>
                            <td colspan="2" class="text-center">________________</td>
                            <td colspan="2" class="text-center"><?php echo $issued_by_name; ?></td>
                            <td class="text-center">________________</td>
                        </tr>

                        <tr class="signatory-section">
                            <td colspan="2" class="text-start">Designation:</td>
                            <td class="text-center"><?php echo $requestor_position; ?></td>
                            <td colspan="2" class="text-center">________________</td>
                            <td colspan="2" class="text-center"><?php echo $issued_by_position; ?></td>
                            <td class="text-center">________________</td>
                        </tr>

                        <tr class="signatory-section">
                            <td colspan="2" class="text-start">Date:</td>
                            <td class="text-center"><?php echo date('m/d/Y', strtotime($request['date'])); ?></td>
                            <td colspan="2" class="text-center">________________</td>
                            <td colspan="2" class="text-center">________________</td>
                            <td class="text-center">________________</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endfor; ?>
</div>

<script>
// Auto-print when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.print();
});

// Function to handle print completion
window.onafterprint = function() {
    // Optional: Redirect back after printing
    // window.location.href = 'home.php';
};
</script>

</body>
</html>