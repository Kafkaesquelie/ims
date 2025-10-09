<?php
$page_title = 'Issued Items';
require_once('includes/load.php');
page_require_level(2); // Staff or Admin only

// Fetch issued PPE and Semi-Expendable items from transactions
// Fetch issued PPE (PAR) items
$sql_par = "
    SELECT 
        t.id,
        t.employee_id,
        t.item_id,
        t.quantity,
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
        p.property_no,
        p.article,
        p.description,
        p.unit,
        p.unit_cost
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN properties p ON t.item_id = p.id
    WHERE t.transaction_type = 'issue' AND t.PAR_No IS NOT NULL
    ORDER BY t.transaction_date DESC
";

// Fetch issued Semi-Expendable (ICS) items
$sql_ics = "
    SELECT 
        t.id,
        t.employee_id,
        t.item_id,
        t.quantity,
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
        p.property_no,
        p.item,
        p.item_description,
        p.unit,
        p.unit_cost
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    WHERE t.transaction_type = 'issue' AND t.ICS_No IS NOT NULL
    ORDER BY t.transaction_date DESC
";

$par_transactions = find_by_sql($sql_par);
$ics_transactions = find_by_sql($sql_ics);

?>

<?php include_once('layouts/header.php'); ?>

<style>
    .card-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 0;
        width: 100% !important;
        table-layout: fixed;
    }
    
    .table-custom thead {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        font-size: 14px;
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
        background: rgba(108, 117, 125, 0.15);
        color: #495057;
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
    
    .btn-outline-primary-custom {
        border: 1px solid #007bff;
        color: #007bff;
        border-radius: 6px;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-outline-primary-custom:hover {
        background: #007bff;
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
    
    /* Fixed column widths to prevent horizontal scroll */
    #parTable th:nth-child(1), #parTable td:nth-child(1) { width: 5%; }  /* # */
    #parTable th:nth-child(2), #parTable td:nth-child(2) { width: 12%; } /* PAR No */
    #parTable th:nth-child(3), #parTable td:nth-child(3) { width: 18%; } /* Issued To */
    #parTable th:nth-child(4), #parTable td:nth-child(4) { width: 12%; } /* Office */
    #parTable th:nth-child(5), #parTable td:nth-child(5) { width: 20%; } /* Item Details */
    #parTable th:nth-child(6), #parTable td:nth-child(6) { width: 8%; }  /* Quantity */
    #parTable th:nth-child(7), #parTable td:nth-child(7) { width: 10%; } /* Issue Date */
    #parTable th:nth-child(8), #parTable td:nth-child(8) { width: 10%; } /* Status */
    #parTable th:nth-child(9), #parTable td:nth-child(9) { width: 15%; } /* Actions */
    
    #icsTable th:nth-child(1), #icsTable td:nth-child(1) { width: 5%; }  /* # */
    #icsTable th:nth-child(2), #icsTable td:nth-child(2) { width: 12%; } /* ICS No */
    #icsTable th:nth-child(3), #icsTable td:nth-child(3) { width: 18%; } /* Issued To */
    #icsTable th:nth-child(4), #icsTable td:nth-child(4) { width: 12%; } /* Office */
    #icsTable th:nth-child(5), #icsTable td:nth-child(5) { width: 20%; } /* Item Details */
    #icsTable th:nth-child(6), #icsTable td:nth-child(6) { width: 8%; }  /* Quantity */
    #icsTable th:nth-child(7), #icsTable td:nth-child(7) { width: 10%; } /* Issue Date */
    #icsTable th:nth-child(8), #icsTable td:nth-child(8) { width: 10%; } /* Status */
    #icsTable th:nth-child(9), #icsTable td:nth-child(9) { width: 15%; } /* Actions */
    
    /* Ensure no horizontal scroll */
    .table-responsive {
        overflow-x: hidden;
    }
</style>

<div class="container-fluid py-4">
    <!-- PPE / Property Accountability Receipt (PAR) -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Issued PPE Items (PAR)</h5>
            <span class="badge bg-light text-dark"><?= count($par_transactions); ?> items</span>
        </div>
        <div class="card-body p-0">
            <?php if(!empty($par_transactions)): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="parTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">PAR No.</th>
                                <th>Issued To</th>
                                <th>Office</th>
                                <th>Item Details</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach ($par_transactions as $t): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued"><?= $count++; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="document-number"><?= $t['PAR_No'] ?: '-'; ?></span>
                                    </td>
                                    <td>
                                        <div class="employee-info">
                                            <span class="employee-name"><?= $t['employee_name']; ?></span>
                                            <span class="employee-position"><?= $t['position']; ?></span>
                                        </div>
                                    </td>
                                    <td><?= $t['office']; ?></td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-name" title="<?= htmlspecialchars($t['article']); ?>">
                                                <?= $t['article']; ?>
                                            </div>
                                            <div class="item-description" title="<?= htmlspecialchars($t['description']); ?>">
                                                <?= $t['description']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued">
                                            <?= $t['quantity'] ?> <?= $t['unit']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($t['transaction_date'])); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['status'] == 'Returned'): ?>
                                            <span class="badge badge-custom badge-returned">
                                                <i class="fas fa-check-circle me-1"></i>Returned
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
                                            <a href="print_transaction.php?id=<?= $t['id']; ?>" 
                                               class="btn btn-outline-primary-custom btn-sm" 
                                               target="_blank"
                                               title="Print Document">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if ($t['status'] == 'Issued'): ?>
                                            <button class="btn btn-return btn-sm return-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#returnModal" 
                                                    data-id="<?= $t['id']; ?>" 
                                                    data-item="<?= htmlspecialchars($t['article']); ?>"
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
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h4>No Issued PPE Items</h4>
                    <p>No Property, Plant and Equipment items have been issued yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Semi-Expendable (ICS) -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-box-open me-2"></i> Issued Semi-Expendable Items (ICS)</h5>
            <span class="badge bg-light text-dark"><?= count($ics_transactions); ?> items</span>
        </div>
        <div class="card-body p-0">
            <?php if(!empty($ics_transactions)): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="icsTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">ICS No.</th>
                                <th>Issued To</th>
                                <th>Office</th>
                                <th>Item Details</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Issue Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach ($ics_transactions as $t): ?>
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
                                    <td><?= $t['office']; ?></td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-name" title="<?= htmlspecialchars($t['item']); ?>">
                                                <?= $t['article']; ?>
                                            </div>
                                            <div class="item-description" title="<?= htmlspecialchars($t['item_description']); ?>">
                                                <?= $t['description']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued">
                                            <?= $t['quantity'] ?> <?= $t['unit']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($t['transaction_date'])); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($t['status'] == 'Returned'): ?>
                                            <span class="badge badge-custom badge-returned">
                                                <i class="fas fa-check-circle me-1"></i>Returned
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
                                            <a href="print_transaction.php?id=<?= $t['id']; ?>" 
                                               class="btn btn-outline-primary-custom btn-sm" 
                                               target="_blank"
                                               title="Print Document">
                                                <i class="fas fa-print"></i>
                                            </a>
                                           <?php if (strtolower($t['status']) == 'issued'): ?>
                                            <button class="btn btn-return btn-sm return-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#returnModal" 
                                                    data-id="<?= $t['id']; ?>" 
                                                    data-item="<?= htmlspecialchars($t['item']); ?>"
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
                        <select name="return_status " class="form-select border-success w-200 p-2" required>
                            <option value="">Select Condition</option>
                            <option value="Returned">Good Condition</option>
                            <option value="Damaged">Damaged</option>
                        </select>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('returnModal');
    const returnForm = document.getElementById('returnForm');

    // When modal opens, populate item info
    modalEl.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        if (!button) return;
        const transId = button.getAttribute('data-id');
        const itemName = button.getAttribute('data-item');
        document.getElementById('return_transaction_id').value = transId;
        document.getElementById('return_item_name').textContent = `Return: ${itemName}`;
    });

    // Handle form submission (AJAX)
    returnForm.addEventListener('submit', async e => {
        e.preventDefault();

        const formData = new FormData(returnForm);
        const submitBtn = returnForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('process_return.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Item Returned',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    location.reload();
                });
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
                text: 'An unexpected error occurred while processing the request.'
            });
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // Initialize DataTables
 $(document).ready(function () {
    var table = $('#icsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    });

     $(document).ready(function () {
    var table = $('#parTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    });
  }
    );

</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
