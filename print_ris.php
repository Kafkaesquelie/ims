<?php
$page_title = 'View Request';
require_once('includes/load.php');
page_require_level(1);

$request_id = (int)$_GET['id'];

// Fetch request info
$request = find_by_id('requests', $request_id);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('requests.php');
}

// Fetch requestor info from users & employees table
$user = find_by_id('users', $request['requested_by']);

$employee = find_by_sql("SELECT * FROM employees WHERE user_id = '{$request['requested_by']}' LIMIT 1");
$employee = !empty($employee) ? $employee[0] : null;

$requestor_name = $user ? $user['name'] : 'Unknown';
$requestor_position = $employee ? $employee['position'] ?? $employee['position'] : ($user['position'] ?? '');
$requestor_division = $employee ? $employee['division'] ?? '________________' : '________________';
$requestor_office   = $employee ? $employee['office'] ?? '________________' : '________________';

// Fetch requested items with unit directly from request_items
$items = find_by_sql("
    SELECT 
        ri.item_id,
        ri.qty,
        ri.unit,  -- This is the actual unit used in the request
        ri.remarks,
        i.name as item_name,
        i.stock_card,
        i.quantity as current_stock
    FROM request_items ri
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE ri.req_id = '{$request_id}'
");

// Current logged-in user (for approved by/issued by)
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";
$current_user_position = isset($current_user['position']) ? remove_junk($current_user['position']) : "";

// Dark mode flag
$is_dark = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'];
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


<style>
    body {
        font-family: Arial, sans-serif;
    }

    .ris-container {
        width: 100%;
        margin: 0 auto;
    }

    .ris-copy {
        width: 100%;
        margin-bottom: 20px;
        page-break-inside: avoid;
    }

    .ris-header {
        text-align: center;
        margin-bottom: 10px;
        font-weight: bold;
        font-size: 16px;
    }

    .entity-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .ris-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .ris-table th,
    .ris-table td {
        border: 1px solid #000;
        padding: 5px;
        font-size: 12px;
    }

    .ris-table th {
        background-color: #f0f0f0;
    }

    .empty-row td {
        height: 15px;
        /* makes the empty row visible */
        border: 1px solid #000;
        /* ensures border is visible */
    }

    /* Purpose cell adjustments */
    .purpose-cell {
        text-align: left;
        /* align purpose text to right */
        vertical-align: top;
    }

    .entity-info {
        font-size: 13px;
    }

    @page {
        size: 8.5in 13in;
        /* Long bond paper size */
        margin: 0.5in;
        /* Adjust margin as needed */
    }

    body {
        font-family: Arial, sans-serif;
        background: white;
        margin: 0;
        padding: 0;
    }

    .ris-container {
        width: 100%;
        margin: 0 auto;
    }

    .card {
        width: 8.5in;
        min-height: 13in;
        margin: auto;
        padding: 0.5in;
        box-sizing: border-box;
        border: 1px solid #000;
    }

    .ris-copy {
        width: 100%;
        page-break-after: always;
    }

    /* Adjust text and table sizing */
    .ris-table th,
    .ris-table td {
        padding: 4px;
        font-size: 11px;
        border: 1px solid #000;
    }

    .ris-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ris-header {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 10px;
    }

    .entity-info {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .purpose-cell {
        text-align: left;
        vertical-align: top;
    }

    /* --- Text alignment fix --- */
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

    /* --- Signatory section styling --- */
    .signatory-section td {
        height: 25px;
        vertical-align: middle !important;
    }

    .signatory-section strong {
        font-size: 11px;
    }

    /* Optional: make all signatory columns equal width for perfect alignment */
    .signatory-section td:nth-child(3),
    .signatory-section td:nth-child(4),
    .signatory-section td:nth-child(5),
    .signatory-section td:nth-child(6),
    .signatory-section td:nth-child(7),
    .signatory-section td:nth-child(8) {
        width: 12.5%;
    }

    @media print {
        body {
            margin: 0;
            padding: 0;
            font-size: 10px;
        }

        .no-print {
            display: none;
        }

        .card {
            width: 8.5in;
            min-height: 13in;
            border: none;
            box-shadow: none;
            padding: 0.4in;
        }

        .ris-copy {
            page-break-after: always;
        }

        .no-print {
            display: none !important;
        }
    }
</style>

<div class="container-fluid my-2 d-flex">
    <!-- Sidebar Icon Buttons -->
    <div class="no-print d-flex flex-column align-items-center me-3" style="gap:12px;">
        <!-- Print Button -->
        <button onclick="window.print()"
            class="btn btn-outline-primary rounded-circle shadow-sm"
            style="width: 50px; height: 50px;"
            title="Print">
            <i class="fa-solid fa-print"></i>
        </button>

        <!-- Back Button -->
        <a href="logs.php"
            class="btn btn-outline-secondary rounded-circle shadow-sm"
            style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;"
            title="Back">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </div>



    <!-- Main Form -->
    <div class="row flex-grow-1">
        <div class="col-md-10 mx-auto">

            <div class="card shadow-sm border-light 
                  <?php echo $is_dark ? 'bg-dark text-light' : 'bg-white text-dark'; ?>">

                <div class="card-body p-0">
                    <?php for ($copy = 1; $copy <= 3; $copy++): ?>
                        <div class="ris-copy p-3">
                            <div class="ris-header">
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

                            <table class="ris-table" style="border:1px solid #000; font-size: 10px;">
                                <thead>
                                    <tr>
                                        <th colspan="5" class="text-start">
                                            <strong>Division:</strong> <?php echo remove_junk($requestor_division); ?><br>
                                            <strong>Office:</strong> <?php echo remove_junk($requestor_office); ?>
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
                                    <?php foreach ($items as $item):
                                        $stock_available = isset($item['current_stock']) ? ($item['qty'] <= $item['current_stock']) : true;
                                    ?>
                                        <tr class="text-center">
                                            <td><?php echo (int)$item['stock_card']; ?></td>
                                            <td><?php echo remove_junk($item['unit']); ?></td> <!-- Direct from request_items -->
                                            <td><?php echo remove_junk($item['item_name']); ?></td>
                                            <td><?php echo (float)$item['qty']; ?></td>
                                            <td><?php echo $stock_available ? '✔' : ''; ?></td>
                                            <td><?php echo !$stock_available ? '✔' : ''; ?></td>
                                            <td><?php echo (float)$item['qty']; ?></td>
                                            <td><?php echo remove_junk($item['remarks']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php
                                    $item_count = count($items);
                                    $empty_rows = max(0, 4 - $item_count);
                                    for ($i = 0; $i < $empty_rows; $i++): ?>
                                        <tr>
                                            <td colspan="8">&nbsp;</td>
                                        </tr>
                                    <?php endfor; ?>

                                    <tr>
                                        <td colspan="8" class="purpose-cell"><strong>Purpose:</strong></td>
                                    </tr>

                                    <tr class="empty-row">
                                        <td colspan="8">&nbsp;</td>
                                    </tr>

                                    <tr>
                                        <td colspan="2"></td>
                                        <td><strong>Requested By</strong></td>
                                        <td colspan="2"><strong>Approved By</strong></td>
                                        <td colspan="2"><strong>Issued By</strong></td>
                                        <td><strong>Received By</strong></td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" class="text-start">Signature:</td>
                                        <td></td>
                                        <td colspan="2"></td>
                                        <td colspan="2"></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" class="text-start">Printed Name:</td>
                                        <td class="text-center"><?php echo remove_junk($requestor_name); ?></td>
                                        <td colspan="2" class="text-center"><?php echo $current_user_name; ?></td>
                                        <td colspan="2" class="text-center"><?php echo $current_user_name; ?></td>
                                        <td class="text-center"><?php echo remove_junk($requestor_name); ?></td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" class="text-start">Designation:</td>
                                        <td class="text-center"><?php echo $requestor_position; ?></td>
                                        <td colspan="2" class="text-center"><?php echo $current_user_position; ?></td>
                                        <td colspan="2" class="text-center"><?php echo $current_user_position; ?></td>
                                        <td class="text-center"><?php echo $requestor_position; ?></td>
                                    </tr>

                                    <tr>
                                        <td colspan="2" class="text-start">Date:</td>
                                        <td class="text-center"></td>
                                        <td colspan="2" class="text-center"></td>
                                        <td colspan="2" class="text-center"></td>
                                        <td class="text-center"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endfor; ?>
                </div>

            </div>
        </div>
    </div>
</div>