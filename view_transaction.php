<?php
$page_title = 'View Issued Items Details';
require_once('includes/load.php');
page_require_level(1);

// Get ICS number or transaction ID
$ics_no = isset($_GET['ics_no']) ? $_GET['ics_no'] : null;
$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($ics_no) {
    // Fetch all transactions with the same ICS number
    $sql = "
        SELECT 
            t.*,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name,
            p.inv_item_no,
            p.item,
            p.item_description,
            p.unit,
            p.unit_cost,
            (SELECT COUNT(*) FROM return_items ri WHERE ri.transaction_id = t.id) as return_count,
            (SELECT SUM(ri.qty) FROM return_items ri WHERE ri.transaction_id = t.id) as total_returned_qty
        FROM transactions t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        LEFT JOIN semi_exp_prop p ON t.item_id = p.id
        WHERE t.ICS_No = '{$ics_no}'
          AND t.transaction_type = 'issue'
        ORDER BY t.transaction_date DESC, p.item ASC
    ";
    $transactions = find_by_sql($sql);

    if (!$transactions) {
        $session->msg("d", "No transactions found for ICS: {$ics_no}");
        redirect('issued_items.php');
    }

    // Get summary information for the header
    $first_transaction = $transactions[0];
    $total_items = count($transactions);
    $total_quantity = array_sum(array_column($transactions, 'quantity'));
    $total_returned = array_sum(array_column($transactions, 'qty_returned'));
    $total_re_issued = array_sum(array_column($transactions, 'qty_re_issued'));
    $total_value = 0;
    foreach ($transactions as $trans) {
        $total_value += $trans['quantity'] * $trans['unit_cost'];
    }
} elseif ($transaction_id) {
    // Single transaction view (backward compatibility)
    $sql = "
        SELECT 
            t.*,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name,
            p.inv_item_no,
            p.item,
            p.item_description,
            p.unit,
            p.unit_cost
        FROM transactions t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        LEFT JOIN semi_exp_prop p ON t.item_id = p.id
        WHERE t.id = '{$transaction_id}'
        LIMIT 1
    ";
    $transactions = find_by_sql($sql);

    if (!$transactions) {
        $session->msg("d", "Transaction not found.");
        redirect('issued_items.php');
    }

    $first_transaction = $transactions[0];
    $total_items = 1;
    $total_quantity = $first_transaction['quantity'];
    $total_returned = $first_transaction['qty_returned'];
    $total_re_issued = $first_transaction['qty_re_issued'];
    $total_value = $first_transaction['quantity'] * $first_transaction['unit_cost'];
    $ics_no = $first_transaction['ICS_No'];
} else {
    $session->msg("d", "No ICS number or transaction ID provided.");
    redirect('issued_items.php');
}

// Fetch return history for all transactions in this ICS
$return_history = [];
if ($ics_no) {
    $return_sql = "
        SELECT 
            ri.*,
            t.item_id,
            p.item,
            p.unit,
            CONCAT(e.first_name, ' ', e.last_name) as returned_by
        FROM return_items ri
        INNER JOIN transactions t ON ri.transaction_id = t.id
        LEFT JOIN semi_exp_prop p ON t.item_id = p.id
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.ICS_No = '{$ics_no}'
        ORDER BY ri.return_date DESC
    ";
    $return_history = find_by_sql($return_sql);
}
?>

<?php include_once('layouts/header.php'); ?>

<style>
    :root {
        --primary: #28a745;
        --primary-light: #d4edda;
        --primary-dark: #1e7e34;
        --secondary: #6c757d;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #343a40;
    }

    .card-custom {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header-custom {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-bottom: none;
        padding: 1.5rem 2rem;
    }

    .card-header-custom h5 {
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .card-body {
        padding: 2rem;
    }

    .section-title {
        color: var(--primary-dark);
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1.2rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-light);
        position: relative;
    }

    .section-title::before {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 50px;
        height: 2px;
        background: var(--primary);
    }

    .info-card {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        padding: 1.5rem;
        height: 100%;
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .info-label {
        color: var(--secondary);
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
    }

    .info-value {
        color: var(--dark);
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .badge-issued {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: var(--primary-dark);
    }

    .badge-returned {
        background: linear-gradient(135deg, #e2e3e5, #d6d8db);
        color: #495057;
    }

    .badge-partial {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
    }

    .badge-damaged {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }

    .btn-custom {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-back {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        color: white;
    }

    .btn-print {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        color: white;
    }

    .document-badge {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #1565c0;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .quantity-display {
        background: linear-gradient(135deg, var(--primary-light), #c8e6c9);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        color: var(--primary-dark);
    }

    .icon-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        background: var(--primary-light);
        color: var(--primary);
    }

    .summary-card {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .summary-item {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .summary-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 0.5rem;
    }

    .summary-label {
        color: var(--secondary);
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-custom {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .table-custom th {
        background: linear-gradient(135deg, var(--primary-light), #c8e6c9);
        color: var(--primary-dark);
        font-weight: 600;
        padding: 1rem;
        border: none;
        vertical-align: middle;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #e3e6f0;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .btn-sm-custom {
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        border: none;
        transition: all 0.3s ease;
    }

    .btn-view {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }

    .btn-view:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        color: white;
    }

    .btn-return {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #343a40;
    }

    .btn-return:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        color: #343a40;
    }

    .progress-container {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin: 1rem 0;
    }

    .progress-label {
        display: flex;
        justify-content: between;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    .progress {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
    }

    .progress-bar {
        border-radius: 4px;
    }

    .return-history {
        max-height: 400px;
        overflow-y: auto;
    }

    .return-item {
        padding: 1rem;
        border-left: 4px solid var(--primary);
        background: #f8f9fa;
        margin-bottom: 0.5rem;
        border-radius: 0 8px 8px 0;
    }

    .item-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .detail-card {
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .detail-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header card-header-custom text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">
                                    <?= $ics_no ? "ICS Document Details" : "Issued Item Details"; ?>
                                </h5>
                                <p class="mb-0 opacity-75">
                                    <?= $ics_no ? "Multiple items issued under ICS document" : "Complete transaction information and item details"; ?>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($ics_no): ?>
                                <div class="document-badge">
                                    <i class="fas fa-file-alt me-1"></i> ICS: <?= $ics_no; ?>
                                </div>
                            <?php endif; ?>
                            <div class="document-badge">
                                <i class="fas fa-cube me-1"></i> <?= $total_items; ?> Item<?= $total_items > 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($ics_no && count($transactions) > 1): ?>
        <!-- Summary Section for Multiple Items -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="summary-card">
                    <h6 class="section-title text-primary mb-3">
                        <i class="fas fa-chart-bar me-2"></i>ICS Document Summary
                    </h6>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-value"><?= $total_items; ?></div>
                            <div class="summary-label">Total Items</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value"><?= $total_quantity; ?></div>
                            <div class="summary-label">Total Quantity</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value"><?= $total_returned; ?></div>
                            <div class="summary-label">Total Returned</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value">₱<?= number_format($total_value, 2); ?></div>
                            <div class="summary-label">Total Value</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Overall Return Progress</span>
                            <span><?= $total_returned; ?>/<?= $total_quantity; ?> (<?= round(($total_returned / $total_quantity) * 100, 1); ?>%)</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success"
                                style="width: <?= ($total_returned / $total_quantity) * 100; ?>%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column - Employee & Document Info -->
        <div class="col-lg-4 mb-4">
            <!-- Employee Information -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h6 class="section-title">
                        <i class="fas fa-user-circle me-2"></i> Employee Information
                    </h6>
                    <div class="info-card">
                        <div class="mb-3">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= remove_junk(ucwords($first_transaction['employee_name'])); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Position</div>
                            <div class="info-value"><?= remove_junk($first_transaction['position']); ?></div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label">Office/Department</div>
                            <div class="info-value"><?= remove_junk($first_transaction['office_name']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Information -->
            <div class="card card-custom">
                <div class="card-body">
                    <h6 class="section-title">
                        <i class="fas fa-file-contract me-2"></i> Document Information
                    </h6>
                    <div class="info-card">
                        <div class="mb-3">
                            <div class="info-label">ICS Number</div>
                            <div class="info-value"><?= $first_transaction['ICS_No'] ?: '<span class="text-muted">N/A</span>'; ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">PAR Number</div>
                            <div class="info-value"><?= $first_transaction['PAR_No'] ?: '<span class="text-muted">N/A</span>'; ?></div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label">Issue Date</div>
                            <div class="info-value">
                                <i class="fas fa-calendar-check me-2 text-success"></i>
                                <?= date('F d, Y', strtotime($first_transaction['transaction_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return History -->
            <?php if (!empty($return_history)): ?>
                <div class="card card-custom">
                    <div class="card-body">
                        <h6 class="section-title">
                            <i class="fas fa-history me-2"></i>Return History
                        </h6>
                        <div class="return-history">
                            <?php foreach ($return_history as $return): ?>
                                <div class="return-item">
                                    <div class="info-label"><?= date('M j, Y', strtotime($return['return_date'])); ?></div>
                                    <div class="info-value"><?= $return['item']; ?></div>
                                    <div class="text-muted small">
                                        <?= $return['qty']; ?> <?= $return['unit']; ?> •
                                        <span class="badge <?= $return['conditions'] == 'Functional' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?= $return['conditions']; ?>
                                        </span>
                                    </div>
                                    <?php if ($return['remarks']): ?>
                                        <div class="text-muted small mt-1"><?= $return['remarks']; ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Items Details -->
        <div class="col-lg-8">
            <!-- Items List -->
            <div class="card card-custom">
                <div class="card-body">
                    <h6 class="section-title">
                        <i class="fas fa-boxes me-2"></i>
                        <?= count($transactions) > 1 ? ' Issued Items List' : ' Item Details'; ?>
                    </h6>

                    <?php if (count($transactions) > 1): ?>
                        <!-- Table view for multiple items -->
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>Item Details</th>
                                        <th class="text-center">Inventory No.</th>
                                        <th class="text-center">Unit Cost</th>
                                        <th class="text-center">Qty Issued</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction):
                                        $remaining_qty = $transaction['quantity'] - $transaction['qty_returned'];
                                        $return_percentage = $transaction['quantity'] > 0 ? ($transaction['qty_returned'] / $transaction['quantity']) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong class="d-block"><?= $transaction['item']; ?></strong>
                                                    <small class="text-muted"><?= $transaction['item_description'] ?: 'No description'; ?></small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark"><?= $transaction['inv_item_no']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-success fw-bold">
                                                    ₱<?= number_format($transaction['unit_cost'], 2); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="quantity-display">
                                                    <?= $transaction['quantity']; ?> <?= $transaction['unit']; ?>
                                                </span>
                                            </td>


                                            <td class="text-center">
                                                <div class="action-buttons">
                                                    <a href="view_transaction.php?id=<?= $transaction['id']; ?>"
                                                        class="btn btn-sm-custom btn-view"
                                                        title="View Individual Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (strtolower(trim($transaction['status'])) !== 'returned' && strtolower(trim($transaction['status'])) !== 'damaged' && $remaining_qty > 0): ?>
                                                        <button class="btn btn-sm-custom btn-return return-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#returnModal"
                                                            data-id="<?= $transaction['id']; ?>"
                                                            data-item="<?= htmlspecialchars($transaction['item']); ?>"
                                                            data-quantity="<?= $transaction['quantity']; ?>"
                                                            data-returned="<?= $transaction['qty_returned'] ?? 0; ?>"
                                                            title="Return Item">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Single item detailed view -->
                        <div class="item-details-grid">
                            <div class="detail-card">
                                <div class="info-label">Inventory Number</div>
                                <div class="info-value"><?= $first_transaction['inv_item_no']; ?></div>
                            </div>
                            <div class="detail-card">
                                <div class="info-label">Item Name</div>
                                <div class="info-value"><?= $first_transaction['item']; ?></div>
                            </div>
                            <div class="detail-card">
                                <div class="info-label">Unit of Measure</div>
                                <div class="info-value"><?= $first_transaction['unit']; ?></div>
                            </div>
                            <div class="detail-card">
                                <div class="info-label">Unit Cost</div>
                                <div class="info-value">₱<?= number_format($first_transaction['unit_cost'], 2); ?></div>
                            </div>
                            <div class="detail-card">
                                <div class="info-label">Quantity Issued</div>
                                <div class="info-value">
                                    <span class="quantity-display">
                                        <?= $first_transaction['quantity']; ?> <?= $first_transaction['unit']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-card">
                                <div class="info-label">Quantity Returned</div>
                                <div class="info-value">
                                    <?= $first_transaction['qty_returned'] ?: '0'; ?> <?= $first_transaction['unit']; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($first_transaction['item_description']): ?>
                            <div class="detail-card mt-3">
                                <div class="info-label">Description</div>
                                <div class="info-value"><?= $first_transaction['item_description']; ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-between">
                                <a href="issued_properties.php" class="btn btn-custom btn-back">
                                    <i class="fas fa-arrow-left me-2"></i> Back to List
                                </a>
                                <?php if (count($transactions) > 1): ?>
                                    <!-- Multiple items - Show Bulk Return button -->
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkReturnModal" id="bulkReturnBtn">
                                        <i class="fas fa-undo me-2"></i> Return All
                                    </button>
                                <?php else: ?>
                                    <!-- Single item - Show individual return button -->
                                    <?php $single_transaction = $transactions[0]; ?>
                                    <?php if (strtolower(trim($single_transaction['status'])) !== 'returned' && strtolower(trim($single_transaction['status'])) !== 'damaged'): ?>
                                        <?php
                                        $remaining_qty = $single_transaction['quantity'] - $single_transaction['qty_returned'];
                                        ?>
                                        <?php if ($remaining_qty > 0): ?>
                                            <button type="button" class="btn btn-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#returnModal"
                                                data-id="<?= $single_transaction['id']; ?>"
                                                data-item="<?= htmlspecialchars($single_transaction['item']); ?>"
                                                data-quantity="<?= $single_transaction['quantity']; ?>"
                                                data-returned="<?= $single_transaction['qty_returned'] ?? 0; ?>">
                                                <i class="fas fa-undo me-2"></i> Return Item
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Return Modal -->
<div class="modal fade" id="bulkReturnModal" tabindex="-1" aria-labelledby="bulkReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-success">
            <form id="bulkReturnForm" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="bulkReturnModalLabel">
                        <i class="fas fa-undo me-2"></i> Bulk Return Items
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ics_no" value="<?= $ics_no; ?>">
                    <p class="text-success fw-bold mb-3">Return multiple items from ICS: <?= $ics_no; ?></p>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Issued Qty</th>
                                    <th>Returned</th>
                                    <th>Remaining</th>
                                    <th>Return Qty</th>
                                    <th>Condition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction):
                                    $remaining = $transaction['quantity'] - $transaction['qty_returned'];
                                ?>
                                    <?php if ($remaining > 0): ?>
                                        <tr>
                                            <td>
                                                <strong><?= $transaction['item']; ?></strong>
                                                <input type="hidden" name="transaction_ids[]" value="<?= $transaction['id']; ?>">
                                            </td>
                                            <td><?= $transaction['quantity']; ?></td>
                                            <td><?= $transaction['qty_returned']; ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark"><?= $remaining; ?></span>
                                            </td>
                                            <td>
                                                <input type="number"
                                                    name="return_qty[]"
                                                    class="form-control form-control-sm"
                                                    min="0"
                                                    max="<?= $remaining; ?>"
                                                    value="0"
                                                    data-max="<?= $remaining; ?>">
                                            </td>
                                            <td>
                                                <select name="conditions[]" class="form-select form-select-sm p-2">
                                                    <option value="Functional">Functional</option>
                                                    <option value="Damaged">Damaged</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Date of Return</label>
                        <input type="date" name="return_date" class="form-control border-success" value="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Remarks</label>
                        <textarea name="remarks" class="form-control border-success" rows="2" placeholder="Optional notes about the returned items"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Process Bulk Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Individual Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-success">
            <form id="returnForm" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="returnModalLabel">
                        <i class="fas fa-undo me-2"></i> Return Item
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transaction_id" id="return_transaction_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Item to Return</label>
                        <p class="form-control-plaintext fw-bold" id="return_item_name"></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Condition</label><br>
                        <select name="conditions" class="form-select border-success w-100 p-2" required>
                            <option value="">Select Condition</option>
                            <option value="Functional">Functional</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Quantity to Return</label>
                        <input type="number" name="return_qty" class="form-control border-success" min="1" required>
                        <div class="form-text">
                            Maximum quantity available to return: <span id="max_quantity" class="fw-bold text-success">0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Date of Return</label>
                        <input type="date" name="return_date" class="form-control border-success" value="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Remarks</label>
                        <textarea name="remarks" class="form-control border-success" rows="3" placeholder="Optional notes about the returned item"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ===== INDIVIDUAL RETURN MODAL FUNCTIONALITY =====
        const returnModal = document.getElementById('returnModal');
        const returnForm = document.getElementById('returnForm');

        if (returnModal && returnForm) {
            // When individual return modal is shown, populate with data
            returnModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                // Get data from the button that triggered the modal
                const transactionId = button.getAttribute('data-id');
                const itemName = button.getAttribute('data-item');
                const quantity = parseInt(button.getAttribute('data-quantity'));
                const returned = parseInt(button.getAttribute('data-returned') || 0);
                const remaining = quantity - returned;

                console.log('Individual return modal opening with data:', {
                    transactionId,
                    itemName,
                    quantity,
                    returned,
                    remaining
                });

                // Populate modal fields
                document.getElementById('return_transaction_id').value = transactionId;
                document.getElementById('return_item_name').textContent = itemName;
                document.getElementById('max_quantity').textContent = remaining;

                const qtyInput = document.querySelector('input[name="return_qty"]');
                qtyInput.value = '';
                qtyInput.max = remaining;
                qtyInput.min = 1;
                qtyInput.placeholder = `Enter 1-${remaining}`;
            });

            // Handle individual return form submission
            returnForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
                submitBtn.disabled = true;

                console.log('Submitting individual return...');

                // Submit via AJAX
                fetch('process_returned.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Individual return response:', data);

                        if (data.success) {
                            showAlert('success', 'Success!', data.message, 2000);

                            // Close modal after delay
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(returnModal);
                                modal.hide();

                                // Reload page to show updated data
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }, 1500);

                        } else {
                            showAlert('error', 'Error', data.message);
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('error', 'Request Failed', 'An unexpected error occurred.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });

            // Reset individual return form when modal is hidden
            returnModal.addEventListener('hidden.bs.modal', function() {
                returnForm.reset();
                const submitBtn = returnForm.querySelector('button[type="submit"]');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Confirm Return';
            });

            // Individual return quantity input validation
            const returnQtyInput = document.querySelector('input[name="return_qty"]');
            if (returnQtyInput) {
                returnQtyInput.addEventListener('input', function() {
                    const max = parseInt(this.max) || 0;
                    const value = parseInt(this.value) || 0;

                    if (value > max) {
                        this.value = max;
                        showToast('Quantity cannot exceed available quantity: ' + max, 'warning');
                    }
                    if (value < 1 && this.value !== '') {
                        this.value = 1;
                    }
                });
            }
        }

        // ===== BULK RETURN MODAL FUNCTIONALITY =====
        const bulkReturnModal = document.getElementById('bulkReturnModal');
        const bulkReturnForm = document.getElementById('bulkReturnForm');

        if (bulkReturnModal && bulkReturnForm) {
            // Handle bulk return form submission
            bulkReturnForm.addEventListener('submit', function(e) {
                e.preventDefault();

                console.log('Bulk return form submitted');

                const returnQtyInputs = document.querySelectorAll('input[name="return_qty[]"]');
                let hasValidReturn = false;
                let totalReturnQty = 0;
                let itemsToReturn = [];

                // Check if at least one item has return quantity > 0
                returnQtyInputs.forEach((input, index) => {
                    const qty = parseInt(input.value) || 0;
                    if (qty > 0) {
                        hasValidReturn = true;
                        totalReturnQty += qty;
                        itemsToReturn.push({
                            index: index,
                            quantity: qty,
                            max: input.dataset.max
                        });
                    }
                });

                if (!hasValidReturn) {
                    showAlert('warning', 'No Items Selected', 'Please enter return quantities for at least one item.');
                    return;
                }

                if (totalReturnQty === 0) {
                    showAlert('warning', 'Invalid Quantities', 'Please enter valid return quantities.');
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Confirm Bulk Return',
                    html: `You are about to return <strong>${totalReturnQty} item(s)</strong> across <strong>${itemsToReturn.length} different items</strong>.<br><br>Continue?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Process Return',
                    cancelButtonText: 'Cancel',
                    width: 500
                }).then((result) => {
                    if (result.isConfirmed) {
                        processBulkReturn();
                    }
                });
            });

            // Limit bulk return quantities to maximum available
            document.querySelectorAll('input[name="return_qty[]"]').forEach(input => {
                input.addEventListener('input', function() {
                    const max = parseInt(this.dataset.max);
                    let value = parseInt(this.value) || 0;

                    if (value > max) {
                        this.value = max;
                        showToast('Quantity cannot exceed available quantity: ' + max, 'warning');
                    }
                    if (value < 0) {
                        this.value = 0;
                    }

                    // Update the visual feedback
                    updateBulkReturnSummary();
                });
            });

            // Update summary when modal opens
            bulkReturnModal.addEventListener('show.bs.modal', function() {
                updateBulkReturnSummary();
            });
        }

        // Replace your current bulk return processing function with this debug version:
        function processBulkReturn() {
            const formData = new FormData(bulkReturnForm);
            const submitBtn = bulkReturnForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            submitBtn.disabled = true;

            console.log('Processing bulk return...');
            console.log('Form data:', formData);

            // Log all form values for debugging
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            fetch('process_bulk_return.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text(); // First get as text to see what's returned
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed JSON:', data);

                        if (data.success) {
                            showAlert('success', 'Success!', data.message, 2500);

                            // Close modal after delay
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(bulkReturnModal);
                                modal.hide();

                                // Reload page to show updated data
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }, 2000);

                        } else {
                            showAlert('error', 'Processing Error', data.message);
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response that failed to parse:', text);
                        showAlert('error', 'Server Error', 'Invalid response from server. Check console for details.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('error', 'Request Failed', 'An unexpected error occurred: ' + error.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }

        // Update bulk return summary
        function updateBulkReturnSummary() {
            const returnQtyInputs = document.querySelectorAll('input[name="return_qty[]"]');
            let totalReturnQty = 0;
            let itemsWithReturn = 0;

            returnQtyInputs.forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    totalReturnQty += qty;
                    itemsWithReturn++;
                }
            });

            // You can display this summary in the modal if you want
            console.log(`Bulk return summary: ${totalReturnQty} items across ${itemsWithReturn} products`);
        }

        // Reset bulk return form when modal is hidden
        if (bulkReturnModal) {
            bulkReturnModal.addEventListener('hidden.bs.modal', function() {
                if (bulkReturnForm) {
                    // Reset all quantity inputs to 0
                    document.querySelectorAll('input[name="return_qty[]"]').forEach(input => {
                        input.value = 0;
                    });

                    const submitBtn = bulkReturnForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Process Bulk Return';
                    }
                }
            });
        }

        // ===== HELPER FUNCTIONS =====

        // Function for SweetAlert2 dialogs
        function showAlert(icon, title, text, timer = null) {
            const config = {
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#3085d6'
            };

            if (timer) {
                config.timer = timer;
                config.showConfirmButton = false;
            }

            Swal.fire(config);
        }

        // Function for toast notifications
        function showToast(message, type) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            Toast.fire({
                icon: type,
                title: message
            });
        }

        // ===== DEBUGGING AND ERROR HANDLING =====

        // Add click listeners for debugging
        document.querySelectorAll('.return-btn').forEach(button => {
            button.addEventListener('click', function() {
                console.log('Return button clicked:', {
                    id: this.getAttribute('data-id'),
                    item: this.getAttribute('data-item'),
                    quantity: this.getAttribute('data-quantity'),
                    returned: this.getAttribute('data-returned')
                });
            });
        });

        // Global error handler for fetch requests
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            showToast('A network error occurred. Please check your connection.', 'error');
        });

        // Log when modals are initialized
        console.log('Return modals initialized successfully');
        console.log('Individual return modal:', returnModal ? 'Found' : 'Not found');
        console.log('Bulk return modal:', bulkReturnModal ? 'Found' : 'Not found');
        console.log('Individual return form:', returnForm ? 'Found' : 'Not found');
        console.log('Bulk return form:', bulkReturnForm ? 'Found' : 'Not found');
    });
</script>