<?php
$page_title = 'Issued Items';
require_once('includes/load.php');
page_require_level(1);


// Fetch issued Semi-Expendable (ICS) items
$sql_ics = "
    SELECT 
        t.id,
        t.employee_id,
        t.item_id,
        t.quantity,
        t.qty_returned,
        t.qty_re_issued,
        t.PAR_No,
        t.ICS_No,
        t.RRSP_No,
        t.transaction_type,
        t.transaction_date,
        t.return_date,
        t.re_issue_date,
        t.status,
        t.remarks,
        CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS employee_name,
        e.position,
        e.office,
        p.inv_item_no,
        p.item,
        p.item_description,
        p.unit,
        p.unit_cost
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    WHERE t.ICS_No IS NOT NULL 
      AND (t.status NOT IN ('Returned', 'Damaged') OR t.status IS NULL)
    ORDER BY t.transaction_date DESC
";

// Fetch Returned Semi-Expendable (ICS) items
$sql_returned_ics = "
    SELECT 
        t.id,
        t.employee_id,
        t.item_id,
        t.quantity,
        t.qty_returned,
        t.qty_re_issued,
        t.ICS_No,
        t.transaction_date,
        t.return_date,
        t.re_issue_date,
        t.status,
        t.remarks,
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
    WHERE t.ICS_No IS NOT NULL 
      AND t.status IN ('Returned', 'Damaged', 'Partially Re-Issued', 'Partially Returned')
    ORDER BY t.return_date DESC
";


$returned_ics = find_by_sql($sql_returned_ics);
$ics_transactions = find_by_sql($sql_ics);

?>

<?php include_once('layouts/header.php'); ?>

<style>
    .card-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header-custom {
        color: #1e7e34;
        border-top: 5px solid #1e7e34;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }

    .table-custom {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 0;
        width: 100% !important;
        table-layout: fixed;
    }

    .table-custom thead {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        font-size: 13px;
    }

    .table-custom th {
        border: none;
        font-weight: 600;
        padding: 1rem 0.75rem;
        text-align: center;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-custom td {
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-custom tbody tr {
        transition: all 0.3s ease;
        font-size: 13px;

    }

    .table-custom tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.05);
        transform: translateY(-1px);
    }

    .badge-custom {
        padding: 0.5rem 0.75rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .badge-issued {
        background: rgba(40, 167, 69, 0.15);
        color: #1e7e34;
    }

    .badge-returned {
        background: rgba(40, 167, 69, 0.15);
        color: #1e7e34;
    }

    .badge-re-issued {
        background: rgba(40, 167, 69, 0.15);
        color: #1e7e34;
    }

    .badge-returned {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .badge-partially_returned {
        background: rgba(255, 193, 7, 0.15);
        color: #856404;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .badge-partially_re_issued {
        background: rgba(0, 123, 255, 0.15);
        color: #0056b3;
        border: 1px solid rgba(0, 123, 255, 0.3);
    }


    .badge-damaged {
        background: rgba(220, 53, 69, 0.15);
        color: #721c24;
    }

    .btn-outline-success-custom {
        border: 1px solid #28a745;
        color: #28a745;
        border-radius: 6px;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-outline-success-custom:hover {
        background: #28a745;
        color: white;
        transform: translateY(-1px);
    }

    .btn-primary-custom {
        background:  #007bff;
        color: #ffffffff;
        border-radius: 6px;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        background: #003e81ff;
        color: white;
        transform: translateY(-1px);
    }

    .btn-return {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #343a40;
        border: none;
        border-radius: 6px;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-return:hover {
        background: linear-gradient(135deg, #e0a800, #c69500);
        transform: translateY(-1px);
        color: #343a40;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    .document-number {
        font-weight: 700;
        color: #1e7e34;
    }

    .employee-info {
        display: flex;
        flex-direction: column;
    }

    .employee-name {
        font-weight: 600;
        color: #343a40;
    }

    .employee-position {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .item-details {
        max-width: 100%;
        overflow: hidden;
    }

    .item-name {
        font-weight: 600;
        color: #343a40;
        margin-bottom: 0.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .item-description {
        font-size: 0.85rem;
        color: #6c757d;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #icsTable th:nth-child(1),
    #icsTable td:nth-child(1) {
        width: 5%;
    }

    /* # */
    #icsTable th:nth-child(2),
    #icsTable td:nth-child(2) {
        width: 12%;
    }

    /* ICS No */
    #icsTable th:nth-child(3),
    #icsTable td:nth-child(3) {
        width: 18%;
    }

    /* Issued To */
    #icsTable th:nth-child(4),
    #icsTable td:nth-child(4) {
        width: 12%;
    }

    /* Office */
    #icsTable th:nth-child(5),
    #icsTable td:nth-child(5) {
        width: 20%;
    }

    /* Item Details */
    #icsTable th:nth-child(6),
    #icsTable td:nth-child(6) {
        width: 8%;
    }

    /* Quantity */
    #icsTable th:nth-child(7),
    #icsTable td:nth-child(7) {
        width: 10%;
    }

    /* Issue Date */
    #icsTable th:nth-child(8),
    #icsTable td:nth-child(8) {
        width: 10%;
    }

    /* Status */
    #icsTable th:nth-child(9),
    #icsTable td:nth-child(9) {
        width: 15%;
    }

    /* Actions */

    /* Ensure no horizontal scroll */
    .table-responsive {
        overflow-x: hidden;
    }
    /* Ultra-compact count column */
#icsTable th:nth-child(1),
#icsTable td:nth-child(1),
#returnedIcsTable th:nth-child(1),
#returnedIcsTable td:nth-child(1) {
    width: 8% !important; /* Even smaller */
    min-width: 30px;
    max-width: 40px;
    padding: 0.3rem 0.1rem !important;
}

/* Compact badge for count */
#icsTable td:nth-child(1) .badge,
#returnedIcsTable td:nth-child(1) .badge {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
    min-width: 25px;
}

</style>

<div class="container-fluid py-4">

    <!-- Semi-Expendable (ICS) -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-box-open me-2"></i> Issued Semi-Expendable Items Properties</h5>
            <span class="badge bg-light text-dark"><?= count($ics_transactions); ?> items</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($ics_transactions)): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="icsTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">ICS No.</th>
                                <th>Issued To</th>
                                <th>Item Details</th>
                                <th class="text-center">Qty_Issued</th>
                                <th class="text-center">Qty_Returned</th>
                                <th class="text-center">Qty Re-Issued</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1;
                            foreach ($ics_transactions as $t): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued"><?= $count++; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="document-number"><?= $t['ICS_No'] ?: '-'; ?></span>
                                    </td>
                                    <td>
                                        <div class="employee-info">
                                            <span class="employee-name"><?= $t['employee_name']; ?></span>
                                            <span class="employee-position"><?= $t['position']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-name" title="<?= htmlspecialchars($t['item']); ?>">
                                                <?= htmlspecialchars($t['item']); ?>
                                            </div>
                                            <div class="item-description" title="<?= htmlspecialchars($t['item_description']); ?>">
                                                <?= htmlspecialchars($t['item_description']); ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued">
                                            <?= $t['quantity'] ?> <?= $t['unit']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-returned">
                                            <?= $t['qty_returned'] ?> <?= $t['unit']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-re-issued">
                                            <?= $t['qty_re_issued'] ?: 0; ?> <?= $t['unit']; ?>
                                    </td>
                                    </span>


                                    <td class="text-center">
                                        <?php if ($t['status'] == 'Partially Re-Issued'): ?>
                                            <span class="badge badge-custom badge-partially_re_issued" style="font-size:10px">
                                                 <i class="fas fa-redo me-1"></i>Partially Re-Issued
                                            </span>
                                        <?php elseif ($t['status'] == 'Damaged'): ?>
                                            <span class="badge badge-custom badge-damaged">
                                                <i class="fas fa-times-circle me-1"></i>Damaged
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-custom badge-issued">
                                                <i class="fas fa-paper-plane me-1"></i>Issued
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">

                                        <div class="action-buttons">
                                            <a href="view_transaction.php?id=<?= $t['id']; ?>"
                                                class="btn btn-outline-success-custom btn-sm"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>


                                            <?php
                                            $status = strtolower(trim($t['status']));
                                            if ($status !== 'returned' && $status !== 'damaged'): // show if not fully returned or damaged
                                            ?>
                                                <button class="btn btn-return btn-sm return-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#returnModal"
                                                    data-id="<?= $t['id']; ?>"
                                                    data-item="<?= htmlspecialchars($t['item']); ?>"
                                                    data-quantity="<?= $t['quantity']; ?>"
                                                    data-returned="<?= $t['qty_returned'] ?? 0; ?>"
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
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h4>No Issued Semi-Expendable Items</h4>
                    <p>No semi-expendable property items have been issued yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Returned Semi-Expendable (ICS) -->
<div class="card-custom">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-undo me-2"></i> Returned Semi-Expendable Properties</h5>
        <span class="badge bg-light text-dark"><?= count($returned_ics); ?> items</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($returned_ics)): ?>
            <div class="table-responsive">
                <table class="table table-custom" id="returnedIcsTable">
                    <thead>
                        <tr>
                            <th class="text-center" >#</th>
                            <th class="text-center">ICS No.</th>
                            <th>Returned By</th>
                            <th>Office</th>
                            <th>Item Details</th>
                            <th class="text-center">Qty Returned</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1;
                        foreach ($returned_ics as $r): ?>
                            <tr>
                                <td class="text-center"><span class="badge badge-custom badge-issued"><?= $count++; ?></span></td>
                                <td class="document-number"><?= $r['ICS_No'] ?: '-'; ?></td>
                                <td>
                                    <div class="employee-info">
                                        <span class="employee-name"><?= $r['employee_name']; ?></span>
                                        <span class="employee-position"><?= $r['position']; ?></span>
                                    </div>
                                </td>
                                <td><?= $r['office_name']; ?></td>
                                <td>
                                    <div class="item-details">
                                        <div class="item-name" title="<?= htmlspecialchars($r['item']); ?>"><?= htmlspecialchars($r['item']); ?></div>
                                        <div class="item-description"><?= htmlspecialchars($r['item_description']); ?></div>
                                    </div>
                                </td>
                                <td class="text-center" ><span class="badge badge-custom badge-returned"><?= $r['qty_returned'] ?: 0; ?> <?= $r['unit']; ?></span></td>
                                <td class="text-center">
                                    <?php if ($r['status'] == 'Returned'): ?>
                                        <span class="badge badge-custom badge-returned">
                                            <i class="fas fa-check-circle me-1"></i>Returned
                                        </span>
                                    <?php elseif ($r['status'] == 'Partially Returned'): ?>
                                        <span class="badge badge-custom badge-partially_returned">
                                            <i class="fas fa-exclamation-circle me-1"></i>Partially Returned
                                        </span>
                                    <?php elseif ($r['status'] == 'Partially Re-Issued'): ?>
                                        <span class="badge badge-custom badge-partially_re_issued">
                                            <i class="fas fa-redo me-1"></i>Partially Re-Issued
                                        </span>
                                    <?php elseif ($r['status'] == 'Damaged'): ?>
                                        <span class="badge badge-custom badge-damaged">
                                            <i class="fas fa-times-circle me-1"></i>Damaged
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-info-circle me-1"></i>Unknown
                                        </span>
                                    <?php endif; ?>
                                </td>


                                <td class="text-center">
                                    <div class="action-buttons">
                                        <a href="view_transaction.php?id=<?= $r['id']; ?>"
                                            class="btn btn-outline-success-custom btn-sm"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <button class="btn btn-primary-custom btn-sm reissue-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#reissueModal"
                                            data-id="<?= $r['id']; ?>"
                                            data-item="<?= htmlspecialchars($r['item']); ?>"
                                            data-returned="<?= $r['qty_returned']; ?>"
                                            data-reissued="<?= $r['qty_re_issued'] ?? 0; ?>"
                                            title="Re-Issue Item">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-box"></i></div>
                <h4>No Returned Semi-Expendable Items</h4>
                <p>No items have been returned yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- Re-Issue Modal -->
<div class="modal fade" id="reissueModal" tabindex="-1" aria-labelledby="reissueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-success">
            <form id="reissueForm" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="reissueModalLabel"><i class="fas fa-redo me-2"></i> Re-Issue Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transaction_id" id="reissue_transaction_id">
                    <p class="text-success fw-bold mb-3" id="reissue_item_name"></p>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Quantity to Re-Issue</label>
                        <input type="number" name="reissue_qty" class="form-control border-success" min="1" required>
                        <small id="reissueHint" class="text-muted fst-italic">
                            Maximum re-issuable quantity will appear once item is selected.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Date of Re-Issue</label>
                        <input type="date" name="reissue_date" class="form-control border-success" value="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Remarks</label>
                        <textarea name="remarks" class="form-control border-success" rows="2" placeholder="Optional notes about re-issue"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Confirm Re-Issue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-success">
            <form id="returnForm" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="returnModalLabel"><i class="fas fa-undo me-2"></i>Return Issued Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transaction_id" id="return_transaction_id">
                    <p class="text-success fw-bold mb-3" id="return_item_name"></p>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Condition Upon Return</label><br>
                        <select name="return_status" class="form-select border-success w-100 p-2" required>
                            <option value="">Select Condition</option>
                            <option value="Returned">Good Condition</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Quantity to Return</label>
                            <input type="number" name="return_qty" class="form-control border-success" min="1" required>
                            <small id="remainingHint" class="text-muted fst-italic">
                                Maximum quantity to return will appear when you select an item.
                            </small>

                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Date of Return</label>
                            <input type="date" name="return_date" class="form-control border-success" value="<?= date('Y-m-d'); ?>" required>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">Remarks</label>
                        <textarea name="remarks" class="form-control border-success" rows="2" placeholder="Optional notes about the returned item"></textarea>
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
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('returnModal');
        const returnForm = document.getElementById('returnForm');
        const qtyInput = returnForm.querySelector('input[name="return_qty"]');

        modalEl.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;

            const transId = button.dataset.id;
            const itemName = button.dataset.item;
            const issuedQty = parseInt(button.dataset.quantity || 0);
            const returnedQty = parseInt(button.dataset.returned || 0);
            const remainingQty = issuedQty - returnedQty;

            document.getElementById('return_transaction_id').value = transId;
            document.getElementById('return_item_name').textContent = `Return: ${itemName}`;

            qtyInput.value = '';
            qtyInput.max = remainingQty;
            qtyInput.placeholder = `Max: ${remainingQty}`;
            qtyInput.dataset.remaining = remainingQty;

            if (remainingQty <= 0) {
                qtyInput.disabled = true;
                qtyInput.placeholder = 'No quantity left to return';
            } else {
                qtyInput.disabled = false;
            }
        });

        // 🟢 Real-time checker for exceeding values
        qtyInput.addEventListener('input', () => {
            const entered = parseInt(qtyInput.value || 0);
            const max = parseInt(qtyInput.dataset.remaining || 0);
            if (entered > max) {
                qtyInput.value = max;
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Limit Exceeded',
                    text: `You can only return up to ${max} item(s).`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });

        // 🟢 Handle AJAX submission for returning item
        returnForm.addEventListener('submit', async e => {
            e.preventDefault();

            const formData = new FormData(returnForm);
            const submitBtn = returnForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('process_returned.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Item Returned',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Close modal and refresh table
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    // Optional: Reload only the table via AJAX instead of the full page
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#3085d6'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'An unexpected error occurred while processing your request.'
                });
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // 🟢 Initialize DataTable with modern options
        $(document).ready(function() {
            $('#icsTable').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                ordering: true,
                searching: true,
                responsive: true,
                columnDefs: [{
                        orderable: false,
                        targets: [8]
                    } 
                ],
            });
        });
    });
</script>

<script>
    // 🟦 Handle Re-Issue Modal
    const reissueModalEl = document.getElementById('reissueModal');
    const reissueForm = document.getElementById('reissueForm');
    const reissueQtyInput = reissueForm.querySelector('input[name="reissue_qty"]');

    reissueModalEl.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        if (!button) return;

        const transId = button.dataset.id;
        const itemName = button.dataset.item;
        const returnedQty = parseInt(button.dataset.returned || 0);
        const reissuedQty = parseInt(button.dataset.reissued || 0);
        const remainingQty = returnedQty - reissuedQty;

        document.getElementById('reissue_transaction_id').value = transId;
        document.getElementById('reissue_item_name').textContent = `Re-Issue: ${itemName}`;
        reissueQtyInput.value = '';
        reissueQtyInput.max = remainingQty;
        reissueQtyInput.placeholder = `Max: ${remainingQty}`;
        reissueQtyInput.dataset.remaining = remainingQty;

        if (remainingQty <= 0) {
            reissueQtyInput.disabled = true;
            reissueQtyInput.placeholder = 'No quantity left to re-issue';
        } else {
            reissueQtyInput.disabled = false;
        }
    });

    // 🟦 Prevent exceeding reissue quantity
    reissueQtyInput.addEventListener('input', () => {
        const entered = parseInt(reissueQtyInput.value || 0);
        const max = parseInt(reissueQtyInput.dataset.remaining || 0);
        if (entered > max) {
            reissueQtyInput.value = max;
            Swal.fire({
                icon: 'warning',
                title: 'Quantity Limit Exceeded',
                text: `You can only re-issue up to ${max} item(s).`,
                timer: 1500,
                showConfirmButton: false
            });
        }
    });

    // 🟦 Handle AJAX reissue submission
    reissueForm.addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData(reissueForm);
        const submitBtn = reissueForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('process_reissue.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Item Re-Issued',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(reissueModalEl).hide();
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'An unexpected error occurred.'
            });
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // 🟦 Initialize DataTable for returned items
    $(document).ready(function() {
        $('#returnedIcsTable').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            searching: true,
            responsive: true,
            autoWidth:false,
            columnDefs: [{
                orderable: false,
                targets: [7],
            }
        ],
        });
    });
</script>