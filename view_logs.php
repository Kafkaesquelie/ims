<?php
$page_title = 'View Issued Items Details';
require_once('includes/load.php');
page_require_level(1);

// Get ICS number with proper validation
$ics_no = isset($_GET['ics_no']) ? trim($db->escape($_GET['ics_no'])) : null;

if (!$ics_no) {
    $session->msg("d", "No ICS number provided.");
    redirect('logs.php');
}

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
    redirect('logs.php');
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

// Fetch return history for all transactions in this ICS
$return_history = [];
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

// Check if there are any returns for this ICS
$has_returns = !empty($return_history);
?>

<?php include_once('layouts/header.php'); ?>

<!-- Your existing CSS styles remain the same -->
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

    .btn-ics {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
    }

    .btn-ics:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .btn-rrsp {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #343a40;
    }

    .btn-rrsp:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        color: #343a40;
    }

    .btn-par {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }

    .btn-par:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
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

    .export-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .btn-rrsp {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }

    .btn-rrsp:hover {
        background: linear-gradient(135deg, #218838, #1aa179);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
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
                                <h5 class="mb-1">ICS Document Details</h5>
                                <p class="mb-0 opacity-75">Multiple items issued under ICS document</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="document-badge">
                                <i class="fas fa-file-alt me-1"></i> ICS: <?= $ics_no; ?>
                            </div>
                            <div class="document-badge">
                                <i class="fas fa-cube me-1"></i> <?= $total_items; ?> Item<?= $total_items > 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <i class="fas fa-boxes me-2"></i> Issued Items List
                    </h6>

                    <!-- Table view for multiple items -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th class="text-center">Inventory No.</th>
                                    <th class="text-center">Unit Cost</th>
                                    <th class="text-center">Qty Issued</th>
                                    <th class="text-center">Qty Returned</th>
                                    <th class="text-center">Status</th>
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
                                            <span class="badge <?= $transaction['qty_returned'] > 0 ? 'bg-warning' : 'bg-secondary'; ?>">
                                                <?= $transaction['qty_returned']; ?> <?= $transaction['unit']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($transaction['status'] == 'Returned'): ?>
                                                <span class="badge bg-success">Returned</span>
                                            <?php elseif ($transaction['status'] == 'Partially Returned'): ?>
                                                <span class="badge bg-warning">Partially Returned</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Issued</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-between align-items-center">
                                <a href="logs.php" class="btn btn-custom btn-back">
                                    <i class="fas fa-arrow-left me-2"></i> Back to List
                                </a>

                            
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>