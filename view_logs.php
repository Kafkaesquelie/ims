<?php
$page_title = 'View Issued Items Details';
require_once('includes/load.php');
page_require_level(1);

// Get document number and type with proper validation
$ics_no = isset($_GET['ics_no']) ? trim($db->escape($_GET['ics_no'])) : null;
$par_no = isset($_GET['par_no']) ? trim($db->escape($_GET['par_no'])) : null;

// Determine document type
if ($ics_no) {
    $doc_type = 'ics';
    $doc_no = $ics_no;
    $doc_field = 'ICS_No';
    $item_table = 'semi_exp_prop';
    $doc_title = 'ICS';
} elseif ($par_no) {
    $doc_type = 'par';
    $doc_no = $par_no;
    $doc_field = 'PAR_No';
    $item_table = 'properties';
    $doc_title = 'PAR';
} else {
    $session->msg("d", "No document number provided.");
    redirect('logs.php');
}

// Build SQL query based on document type
if ($doc_type === 'ics') {
    // For ICS - semi_exp_prop table
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
        WHERE t.{$doc_field} = '{$doc_no}'
          AND t.transaction_type = 'issue'
        ORDER BY t.transaction_date DESC, p.item ASC
    ";
} else {
    // For PAR - properties table - FIXED JOIN CONDITION
    $sql = "
        SELECT 
            t.*,
            CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
            e.position,
            o.office_name,
            p.property_no,
            p.article AS item,
            p.description AS item_description,
            p.unit,
            p.unit_cost,
            p.date_acquired,
            (SELECT COUNT(*) FROM return_items ri WHERE ri.transaction_id = t.id) as return_count,
            (SELECT SUM(ri.qty) FROM return_items ri WHERE ri.transaction_id = t.id) as total_returned_qty
        FROM transactions t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        LEFT JOIN properties p ON t.properties_id = p.id  
        WHERE t.{$doc_field} = '{$doc_no}'
          AND t.transaction_type = 'issue'
        ORDER BY t.transaction_date DESC, p.article ASC
    ";
}

$transactions = find_by_sql($sql);

if (!$transactions) {
    $session->msg("d", "No transactions found for {$doc_title}: {$doc_no}");
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

// Fetch return history for all transactions in this document
$return_history = [];
if ($doc_type === 'ics') {
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
        WHERE t.{$doc_field} = '{$doc_no}'
        ORDER BY ri.return_date DESC
    ";
} else {
    $return_sql = "
        SELECT 
            ri.*,
            t.properties_id,
            p.article AS item,
            p.unit,
            CONCAT(e.first_name, ' ', e.last_name) as returned_by
        FROM return_items ri
        INNER JOIN transactions t ON ri.transaction_id = t.id
        LEFT JOIN properties p ON t.properties_id = p.id
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.{$doc_field} = '{$doc_no}'
        ORDER BY ri.return_date DESC
    ";
}

$return_history = find_by_sql($return_sql);

// Check if there are any returns for this document
$has_returns = !empty($return_history);

// Check if there are items that can be returned (not fully returned)
$can_return_items = false;
$returnable_transactions = [];
foreach ($transactions as $transaction) {
    $remaining_qty = $transaction['quantity'] - $transaction['qty_returned'];
    if ($remaining_qty > 0) {
        $can_return_items = true;
        $returnable_transactions[] = $transaction;
    }
}

// For bulk return - get ALL ICS items (including fully returned ones)
$all_ics_items = [];
if ($doc_type === 'ics') {
    $all_ics_items = $transactions; // All transactions are ICS items
}
?>

<?php include_once('layouts/header.php'); ?>

<!-- SweetAlert2 CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

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

    .btn-return {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-return:hover {
        background: linear-gradient(135deg, #218838, #1aa179);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    .btn-print {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }

    .btn-print:hover {
        background: linear-gradient(135deg, #138496, #117a8b);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
    }

    .btn-return-sm {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-return-sm:hover {
        background: linear-gradient(135deg, #218838, #1aa179);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-print-sm {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-print-sm:hover {
        background: linear-gradient(135deg, #138496, #117a8b);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
    }

    .btn-return-sm:disabled,
    .btn-print-sm:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
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
        width: 100%;
        table-layout: fixed;
    }

    .table-custom th {
        background: linear-gradient(135deg, var(--primary-light), #c8e6c9);
        color: var(--primary-dark);
        font-weight: 600;
        padding: 1rem;
        border: none;
        vertical-align: middle;
        text-align: center;
    }

    .table-custom td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #e3e6f0;
        text-align: center;
    }

    /* Fixed column widths */
    .table-custom th:nth-child(1),
    .table-custom td:nth-child(1) {
        width: 25%; /* Item Details */
    }

    .table-custom th:nth-child(2),
    .table-custom td:nth-child(2) {
        width: 15%; /* Property No / Inventory No */
    }

    .table-custom th:nth-child(3),
    .table_custom td:nth-child(3) {
        width: 15%; /* Qty Issued */
    }

    .table-custom th:nth-child(4),
    .table-custom td:nth-child(4) {
        width: 10%; /* Qty Returned */
    }

    .table-custom th:nth-child(5),
    .table-custom td:nth-child(5) {
        width: 10%; /* Status */
    }

    .table-custom th:nth-child(6),
    .table-custom td:nth-child(6) {
        width: 15%; /* Actions */
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

    /* Modal Styles */
    .modal-header-custom {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-bottom: none;
    }

    .modal-footer-custom {
        border-top: 1px solid #e9ecef;
        padding: 1rem;
    }

    .bulk-return-item {
        background: #f8f9fa;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .bulk-return-item:last-child {
        margin-bottom: 0;
    }

    .fully-returned {
        opacity: 0.6;
        background: #f0f0f0;
    }

    .fully-returned .form-control {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        flex-wrap: wrap;
    }
</style>

<!-- Return Item Modal -->
<div class="modal fade" id="returnItemModal" tabindex="-1" aria-labelledby="returnItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="returnItemModalLabel">Return Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="returnItemForm">
                <div class="modal-body">
                    <input type="hidden" id="return_transaction_id" name="transaction_id">
                    <input type="hidden" id="return_doc_type" name="doc_type" value="<?= $doc_type; ?>">
                    <input type="hidden" id="return_doc_no" name="doc_no" value="<?= $doc_no; ?>">
                    
                    <div class="mb-3">
                        <label for="return_item_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="return_item_name" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_issued_qty" class="form-label">Issued Quantity</label>
                                <input type="text" class="form-control" id="return_issued_qty" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_current_returned" class="form-label">Already Returned</label>
                                <input type="text" class="form-control" id="return_current_returned" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="return_qty" class="form-label">Quantity to Return *</label>
                        <input type="number" class="form-control" id="return_qty" name="return_qty" min="1" required>
                        <div class="form-text" id="return_max_qty">Maximum: 0</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="return_conditions" class="form-label">Condition *</label>
                        <select class="form-control" id="return_conditions" name="conditions" required>
                            <option value="">Select Condition</option>
                            <option value="Functional">Functional</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" class="form-control" id="return_date" name="return_date" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="return_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="return_remarks" name="remarks" rows="3" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitReturnBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Process Return 
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Return Modal for ICS Items -->
<div class="modal fade" id="bulkReturnModal" tabindex="-1" aria-labelledby="bulkReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="bulkReturnModalLabel">Bulk Return Items - ICS: <?= $doc_no; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkReturnForm">
                <div class="modal-body">
                    <input type="hidden" name="doc_type" value="<?= $doc_type; ?>">
                    <input type="hidden" name="doc_no" value="<?= $doc_no; ?>">
                    
                    <div class="mb-3">
                        <label for="bulk_return_date" class="form-label">Return Date *</label>
                        <input type="date" class="form-control" id="bulk_return_date" name="return_date" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    
                    <h6 class="section-title">All Items in ICS Document</h6>
                    <div id="bulkReturnItemsContainer">
                        <?php foreach ($all_ics_items as $transaction): 
                            $remaining_qty = $transaction['quantity'] - $transaction['qty_returned'];
                            $is_fully_returned = $remaining_qty <= 0;
                        ?>
                            <div class="bulk-return-item <?= $is_fully_returned ? 'fully-returned' : ''; ?>">
                                <input type="hidden" name="transaction_ids[]" value="<?= $transaction['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong><?= $transaction['item']; ?></strong>
                                            <?php if ($transaction['item_description']): ?>
                                                <br><small class="text-muted"><?= $transaction['item_description']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small">
                                            Inventory No: <?= $transaction['inv_item_no']; ?>
                                            <?php if ($is_fully_returned): ?>
                                                <span class="badge bg-success ms-2">Fully Returned</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-muted small">Issued:</div>
                                        <div><?= $transaction['quantity']; ?> <?= $transaction['unit']; ?></div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-muted small">Returned:</div>
                                        <div><?= $transaction['qty_returned']; ?> <?= $transaction['unit']; ?></div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-2">
                                            <label class="form-label small">Return Qty</label>
                                            <input type="number" class="form-control form-control-sm" 
                                                   name="return_qty[]" 
                                                   min="0" 
                                                   max="<?= $remaining_qty; ?>" 
                                                   value="0"
                                                   data-max="<?= $remaining_qty; ?>"
                                                   onchange="validateBulkQty(this)"
                                                   <?= $is_fully_returned ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Condition</label>
                                            <select class="form-control form-control-sm" name="conditions[]" <?= $is_fully_returned ? 'disabled' : ''; ?>>
                                                <option value="">Select</option>
                                                <option value="Functional">Functional</option>
                                                <option value="Damaged">Damaged</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-3">
                        <label for="bulk_remarks" class="form-label">Remarks (Optional)</label>
                        <textarea class="form-control" id="bulk_remarks" name="remarks" rows="2" placeholder="Optional remarks for all items..."></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBulkReturnBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Process Bulk Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                                <h5 class="mb-1"><?= $doc_title; ?> Document Details</h5>
                                <p class="mb-0 opacity-75">Multiple items issued under <?= $doc_title; ?> document</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="document-badge">
                                <i class="fas fa-file-alt me-1"></i> <?= $doc_title; ?>: <?= $doc_no; ?>
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
                    <i class="fas fa-chart-bar me-2"></i><?= $doc_title; ?> Document Summary
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
                            <div class="info-label"><?= $doc_title; ?> Number</div>
                            <div class="info-value"><?= $doc_no; ?></div>
                        </div>
                        <?php if ($doc_type === 'ics'): ?>
                            <div class="mb-3">
                                <div class="info-label">PAR Number</div>
                                <div class="info-value"><?= $first_transaction['PAR_No'] ?: '<span class="text-muted">N/A</span>'; ?></div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <div class="info-label">ICS Number</div>
                                <div class="info-value"><?= $first_transaction['ICS_No'] ?: '<span class="text-muted">N/A</span>'; ?></div>
                            </div>
                        <?php endif; ?>
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
                                    <?php if ($doc_type === 'par'): ?>
                                        <th>Property No.</th>
                                    <?php else: ?>
                                        <th>Inventory No.</th>
                                    <?php endif; ?>
                                    <th>Qty Issued</th>
                                    <th>Qty Returned</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction):
                                    $remaining_qty = $transaction['quantity'] - $transaction['qty_returned'];
                                    $return_percentage = $transaction['quantity'] > 0 ? ($transaction['qty_returned'] / $transaction['quantity']) * 100 : 0;
                                    $can_return = $remaining_qty > 0;
                                    $has_returns = $transaction['qty_returned'] > 0;
                                ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong class="d-block"><?= $transaction['item']; ?></strong>
                                                <small class="text-muted"><?= $transaction['item_description'] ?: 'No description'; ?></small>
                                            </div>
                                        </td>
                                        <?php if ($doc_type === 'par'): ?>
                                            <td>
                                                <span class="badge bg-light text-dark"><?= $transaction['property_no'] ?: 'N/A'; ?></span>
                                            </td>
                                        <?php else: ?>
                                            <td>
                                                <span class="badge bg-light text-dark"><?= $transaction['inv_item_no']; ?></span>
                                            </td>
                                        <?php endif; ?>
                                        
                                        <td>
                                            <div class="quantity-display">
                                                <?= $transaction['quantity']; ?> <?= $transaction['unit']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $transaction['qty_returned'] > 0 ? 'bg-warning' : 'bg-secondary'; ?>">
                                                <?= $transaction['qty_returned']; ?> <?= $transaction['unit']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($transaction['status'] == 'Returned'): ?>
                                                <span class="badge bg-success">Returned</span>
                                            <?php elseif ($transaction['status'] == 'Partially Returned'): ?>
                                                <span class="badge bg-warning">Partially Returned</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Issued</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($can_return): ?>
                                                    <button type="button" class="btn btn-return-sm return-item-btn" 
                                                            data-transaction-id="<?= $transaction['id']; ?>"
                                                            data-item-name="<?= htmlspecialchars($transaction['item']); ?>"
                                                            data-issued-qty="<?= $transaction['quantity']; ?>"
                                                            data-current-returned="<?= $transaction['qty_returned']; ?>"
                                                            data-unit="<?= $transaction['unit']; ?>"
                                                            data-max-qty="<?= $remaining_qty; ?>">
                                                        <i class="fas fa-undo me-1"></i> Return
                                                    </button>
                                                <?php else: ?>
                                                  
                                                <?php endif; ?>
                                                
                                                <?php if ($has_returns && $doc_type === 'ics'): ?>
                                                    <button type="button" class="btn btn-print-sm print-rrsp-btn"
                                                            data-transaction-id="<?= $transaction['id']; ?>"
                                                            data-item-name="<?= htmlspecialchars($transaction['item']); ?>"
                                                            data-ics-no="<?= $doc_no; ?>">
                                                        <i class="fas fa-print me-1"></i> RRSP
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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
                                
                                <?php if ($can_return_items && $doc_type === 'ics'): ?>
                                    <button type="button" class="btn btn-custom btn-return" id="bulkReturnBtn">
                                        <i class="fas fa-undo-alt me-2"></i> Bulk Return Items
                                    </button>
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

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    const returnModal = new bootstrap.Modal(document.getElementById('returnItemModal'));
    const bulkReturnModal = new bootstrap.Modal(document.getElementById('bulkReturnModal'));
    
    // Individual return button click
    $('.return-item-btn').click(function() {
        const transactionId = $(this).data('transaction-id');
        const itemName = $(this).data('item-name');
        const issuedQty = $(this).data('issued-qty');
        const currentReturned = $(this).data('current-returned');
        const unit = $(this).data('unit');
        const maxQty = $(this).data('max-qty');
        
        // Populate modal fields
        $('#return_transaction_id').val(transactionId);
        $('#return_item_name').val(itemName);
        $('#return_issued_qty').val(issuedQty + ' ' + unit);
        $('#return_current_returned').val(currentReturned + ' ' + unit);
        $('#return_qty').attr('max', maxQty).val(1);
        $('#return_max_qty').text('Maximum: ' + maxQty + ' ' + unit);
        $('#return_conditions').val('');
        $('#return_remarks').val('');
        
        // Show modal
        returnModal.show();
    });
    
    // Form submission for individual return
    $('#returnItemForm').submit(function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submitReturnBtn');
        const spinner = submitBtn.find('.spinner-border');
        
        // Show loading state
        spinner.removeClass('d-none');
        submitBtn.prop('disabled', true);
        
        // Submit via AJAX
        $.ajax({
            url: 'process_returned.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show SweetAlert success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        returnModal.hide();
                        location.reload();
                    });
                } else {
                    // Show SweetAlert error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Show SweetAlert error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing the return: ' + error,
                    confirmButtonColor: '#dc3545'
                });
            },
            complete: function() {
                // Reset loading state
                spinner.addClass('d-none');
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Bulk return button click - only for ICS items
    $('#bulkReturnBtn').click(function() {
        // Show bulk return modal
        bulkReturnModal.show();
    });
    
    // Form submission for bulk return
    $('#bulkReturnForm').submit(function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submitBulkReturnBtn');
        const spinner = submitBtn.find('.spinner-border');
        
        // Validate that at least one item has quantity > 0 and condition selected
        let hasValidItems = false;
        $('input[name="return_qty[]"]').each(function() {
            if (!$(this).is(':disabled')) {
                const qty = parseInt($(this).val());
                const condition = $(this).closest('.bulk-return-item').find('select[name="conditions[]"]').val();
                if (qty > 0 && condition) {
                    hasValidItems = true;
                }
            }
        });
        
        if (!hasValidItems) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please enter return quantity and select condition for at least one item.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }
        
        // Show loading state
        spinner.removeClass('d-none');
        submitBtn.prop('disabled', true);
        
        // Submit via AJAX
        $.ajax({
            url: 'process_bulk_return.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        bulkReturnModal.hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing bulk return: ' + error,
                    confirmButtonColor: '#dc3545'
                });
            },
            complete: function() {
                // Reset loading state
                spinner.addClass('d-none');
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Print RRSP button click for individual items
    $('.print-rrsp-btn').click(function() {
        const transactionId = $(this).data('transaction-id');
        const itemName = $(this).data('item-name');
        const icsNo = $(this).data('ics-no');
        
        Swal.fire({
            title: 'Print RRSP Receipt?',
            html: `<strong>Item:</strong> ${itemName}<br>
                  <strong>ICS No:</strong> ${icsNo}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, print it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Open print window for individual RRSP
                window.open(`print_rrsp.php?transaction_id=${transactionId}`, '_blank');
            }
        });
    });
    
    // Print All RRSP button click
    $('#printAllRRSPBtn').click(function() {
        Swal.fire({
            title: 'Print All RRSP Receipts?',
            html: `This will print RRSP receipts for all returned items in ICS: <strong><?= $doc_no; ?></strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, print all!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Open print window for all RRSP receipts
                window.open(`print_rrsp.php?ics_no=<?= $doc_no; ?>`, '_blank');
            }
        });
    });
    
    // Quantity validation for individual return
    $('#return_qty').on('change', function() {
        const maxQty = parseInt($(this).attr('max'));
        const currentQty = parseInt($(this).val());
        
        if (currentQty > maxQty) {
            $(this).val(maxQty);
            Swal.fire({
                icon: 'warning',
                title: 'Quantity Exceeded',
                text: `Return quantity cannot exceed ${maxQty}`,
                confirmButtonColor: '#ffc107',
                timer: 2000,
                timerProgressBar: true
            });
        }
        
        if (currentQty < 1) {
            $(this).val(1);
        }
    });
});

// Quantity validation for bulk return
function validateBulkQty(input) {
    const maxQty = parseInt($(input).data('max'));
    const currentQty = parseInt($(input).val());
    
    if (currentQty > maxQty) {
        $(input).val(maxQty);
        Swal.fire({
            icon: 'warning',
            title: 'Quantity Exceeded',
            text: `Return quantity cannot exceed ${maxQty}`,
            confirmButtonColor: '#ffc107',
            timer: 2000,
            timerProgressBar: true
        });
    }
    
    if (currentQty < 0) {
        $(input).val(0);
    }
}
</script>