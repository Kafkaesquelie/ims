<?php
$page_title = 'View Issued Item Details';
require_once('includes/load.php');
page_require_level(1);

// Get transaction ID
$transaction_id = (int)$_GET['id'];

// Fetch transaction details
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
$transaction = find_by_sql($sql);
$transaction = !empty($transaction) ? $transaction[0] : null;

if (!$transaction) {
    $session->msg("d", "Transaction not found.");
    redirect('issued_items.php');
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
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        overflow: hidden;
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
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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

    .detail-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .detail-table th {
        background: linear-gradient(135deg, var(--primary-light), #c8e6c9);
        color: var(--primary-dark);
        font-weight: 600;
        padding: 1rem 1.2rem;
        border: none;
        width: 35%;
    }

    .detail-table td {
        padding: 1rem 1.2rem;
        border-bottom: 1px solid #e3e6f0;
        background: white;
    }

    .detail-table tr:last-child td {
        border-bottom: none;
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

    .timeline-item {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1.5rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.5rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--primary);
    }

    .timeline-item::after {
        content: '';
        position: absolute;
        left: 5px;
        top: 1.5rem;
        bottom: -1.5rem;
        width: 2px;
        background: var(--primary-light);
    }

    .timeline-item:last-child::after {
        display: none;
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
                                <i class="fas fa-eye"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Issued Item Details</h5>
                                <p class="mb-0 opacity-75">Complete transaction information and item details</p>
                            </div>
                        </div>
                        <div class="document-badge">
                            Transaction #<?= str_pad($transaction_id, 6, '0', STR_PAD_LEFT); ?>
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
                        <i class="fas fa-user-circle me-2"></i>Employee Information
                    </h6>
                    <div class="info-card">
                        <div class="mb-3">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= remove_junk(ucwords($transaction['employee_name'])); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Position</div>
                            <div class="info-value"><?= remove_junk($transaction['position']); ?></div>
                        </div>
                        <div class="mb-0">
                            <div class="info-label">Office/Department</div>
                            <div class="info-value"><?= remove_junk($transaction['office_name']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Information -->
            <div class="card card-custom">
                <div class="card-body">
                    <h6 class="section-title">
                        <i class="fas fa-file-contract me-2"></i>Document Information
                    </h6>
                    <div class="info-card">
                        <div class="mb-3">
                            <div class="info-label">ICS Number</div>
                            <div class="info-value"><?= $transaction['ICS_No'] ?: '<span class="text-muted">N/A</span>'; ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">RRSP Number</div>
                            <div class="info-value"><?= $transaction['RRSP_No'] ?: '<span class="text-muted">N/A</span>'; ?></div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Item Details & Timeline -->
        <div class="col-lg-8">
            <!-- Item Details -->
            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h6 class="section-title">
                        <i class="fas fa-box me-2"></i>Item Details
                    </h6>
                    <div class="detail-table">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th>Inventory Number</th>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $transaction['inv_item_no']; ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Item Name</th>
                                    <td><strong><?= $transaction['item']; ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><?= $transaction['item_description'] ?: '<span class="text-muted">No description</span>'; ?></td>
                                </tr>
                                <tr>
                                    <th>Unit of Measure</th>
                                    <td><?= $transaction['unit']; ?></td>
                                </tr>
                                <tr>
                                    <th>Unit Cost</th>
                                    <td>
                                        <span class="text-success fw-bold">
                                            ₱<?= number_format($transaction['unit_cost'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Quantity Issued</th>
                                    <td>
                                        <span class="quantity-display">
                                            <?= $transaction['quantity']; ?> <?= $transaction['unit']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Quantity Returned</th>
                                    <td>
                                        <span class="text-muted">
                                            <?= $transaction['qty_returned'] ?: '0'; ?> <?= $transaction['unit']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Current Status</th>
                                    <td>
                                        <?php
                                        $status = $transaction['status'];
                                        $badge_class = 'badge-secondary';
                                        if ($status == 'completed' || $status == 'Issued') {
                                            $badge_class = 'badge-issued';
                                            $status = 'Issued';
                                        } elseif ($status == 'Partially Returned') {
                                            $badge_class = 'badge-partial';
                                        } elseif ($status == 'Returned') {
                                            $badge_class = 'badge-returned';
                                        } elseif ($status == 'Damaged') {
                                            $badge_class = 'badge-damaged';
                                        }
                                        ?>
                                        <span class="status-badge <?= $badge_class; ?>">
                                            <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>
                                            <?= $status ?: 'Unknown'; ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transaction Timeline & Additional Details -->
            <div class="card card-custom">
                <div class="card-body">
                    <h6 class="section-title">
                        <i class="fas fa-history me-2"></i>Transaction Timeline
                    </h6>
                    
                    <div class="timeline-container">
                        <div class="timeline-item">
                            <div class="info-label">ISSUED DATE</div>
                            <div class="info-value">
                                <i class="fas fa-calendar-check me-2 text-success"></i>
                                <?= date('F d, Y', strtotime($transaction['transaction_date'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($transaction['return_date']): ?>
                        <div class="timeline-item">
                            <div class="info-label">DUE DATE</div>
                            <div class="info-value">
                                <i class="fas fa-clock me-2 text-warning"></i>
                                <?= date('F d, Y', strtotime($transaction['return_date'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction['remarks'])): ?>
                        <div class="timeline-item">
                            <div class="info-label">REMARKS & NOTES</div>
                            <div class="info-value">
                                <i class="fas fa-sticky-note me-2 text-info"></i>
                                <?= $transaction['remarks']; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="issued_properties.php" class="btn btn-custom btn-back">
                                    <i class="fas fa-arrow-left me-2"></i> Back to List
                                </a>
                               
                                <?php if ($transaction['status'] == 'completed' || $transaction['status'] == 'Issued'): ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>