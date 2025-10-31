<?php
$page_title = 'Issued Items';
require_once('includes/load.php');
page_require_level(1);

// Fetch issued Semi-Expendable (ICS) items grouped by ICS_No
$sql_ics = "
    SELECT 
        t.ICS_No,
        t.transaction_date,
        t.status,
        GROUP_CONCAT(DISTINCT CONCAT(e.first_name, ' ', e.last_name) SEPARATOR ', ') AS employee_names,
        GROUP_CONCAT(DISTINCT o.office_name SEPARATOR ', ') AS office_names,
        COUNT(DISTINCT t.id) as transaction_count,
        SUM(t.quantity) as total_quantity,
        SUM(t.qty_returned) as total_returned,
        SUM(t.qty_re_issued) as total_re_issued,
        GROUP_CONCAT(DISTINCT p.item SEPARATOR ', ') as item_names
    FROM transactions t
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN offices o ON e.office = o.id
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    WHERE t.ICS_No IS NOT NULL 
      AND t.status IN ('Issued', 'Partially Returned', 'Partially Re-Issued')
      AND t.transaction_type = 'issue'
    GROUP BY t.ICS_No, t.transaction_date, t.status
    ORDER BY t.transaction_date DESC
";

// Fetch individual items for each ICS group (for modal/details)
$sql_ics_items = "
    SELECT 
        t.id,
        t.employee_id,
        t.item_id,
        t.quantity,
        t.qty_returned,
        t.qty_re_issued,
        t.PAR_No,
        t.ICS_No,
        t.transaction_type,
        t.transaction_date,
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
      AND t.status IN ('Issued', 'Partially Returned', 'Partially Re-Issued')
      AND t.transaction_type = 'issue'
    ORDER BY t.ICS_No, t.transaction_date DESC
";

// Fetch Returned Semi-Expendable (ICS) items
$sql_returned_ics = "
    SELECT 
        ri.id as return_id,
        ri.transaction_id,
        ri.RRSP_No,
        ri.ics_no,
        ri.qty,
        ri.return_date,
        ri.conditions,
        ri.remarks as return_remarks,
        t.employee_id,
        t.item_id,
        t.quantity,
        t.qty_returned,
        t.qty_re_issued,
        t.ICS_No,
        t.transaction_date,
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
    FROM return_items ri
    INNER JOIN transactions t ON ri.transaction_id = t.id
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN offices o ON e.office = o.id
    LEFT JOIN semi_exp_prop p ON t.item_id = p.id
    WHERE t.ICS_No IS NOT NULL 
      AND t.qty_re_issued < t.qty_returned
    ORDER BY ri.return_date DESC, ri.RRSP_No DESC
";

$returned_ics = find_by_sql($sql_returned_ics);
$ics_groups = find_by_sql($sql_ics);
$ics_items = find_by_sql($sql_ics_items);

// Group items by ICS_No for the modal
$items_by_ics = [];
foreach ($ics_items as $item) {
    $items_by_ics[$item['ICS_No']][] = $item;
}
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
        background: #007bff;
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

    /* Column widths for ICS Table */
    #icsTable th:nth-child(1),
    #icsTable td:nth-child(1) {
        width: 5%;
    }

    /* # */
    #icsTable th:nth-child(2),
    #icsTable td:nth-child(2) {
        width: 10%;
    }

    /* ICS No */
    #icsTable th:nth-child(3),
    #icsTable td:nth-child(3) {
        width: 15%;
    }

    /* Issued To */
    #icsTable th:nth-child(4),
    #icsTable td:nth-child(4) {
        width: 12%;
    }

    /* Office */
    #icsTable th:nth-child(5),
    #icsTable td:nth-child(5) {
        width: 18%;
    }

    /* Item Details */
    #icsTable th:nth-child(6),
    #icsTable td:nth-child(6) {
        width: 8%;
    }

    /* Qty Issued */
    #icsTable th:nth-child(7),
    #icsTable td:nth-child(7) {
        width: 8%;
    }

    /* Qty Returned */
    #icsTable th:nth-child(8),
    #icsTable td:nth-child(8) {
        width: 8%;
    }

    /* Qty Re-Issued */
    #icsTable th:nth-child(9),
    #icsTable td:nth-child(9) {
        width: 8%;
    }

    /* Condition */
    #icsTable th:nth-child(10),
    #icsTable td:nth-child(10) {
        width: 10%;
    }

    /* Status */
    #icsTable th:nth-child(11),
    #icsTable td:nth-child(11) {
        width: 10%;
    }

    /* Actions */

    /* Column widths for Returned ICS Table */
    #returnedIcsTable th:nth-child(1),
    #returnedIcsTable td:nth-child(1) {
        width: 5%;
    }

    /* # */
    #returnedIcsTable th:nth-child(2),
    #returnedIcsTable td:nth-child(2) {
        width: 10%;
    }

    /* ICS No */
    #returnedIcsTable th:nth-child(3),
    #returnedIcsTable td:nth-child(3) {
        width: 10%;
    }

    /* RRSP No */
    #returnedIcsTable th:nth-child(4),
    #returnedIcsTable td:nth-child(4) {
        width: 15%;
    }

    /* Returned By */
    #returnedIcsTable th:nth-child(5),
    #returnedIcsTable td:nth-child(5) {
        width: 18%;
    }

    /* Item Details */
    #returnedIcsTable th:nth-child(6),
    #returnedIcsTable td:nth-child(6) {
        width: 8%;
    }

    /* Qty Returned */
    #returnedIcsTable th:nth-child(7),
    #returnedIcsTable td:nth-child(7) {
        width: 8%;
    }

    /* Condition */
    #returnedIcsTable th:nth-child(8),
    #returnedIcsTable td:nth-child(8) {
        width: 12%;
    }

    /* Re-Issue Status */
    #returnedIcsTable th:nth-child(9),
    #returnedIcsTable td:nth-child(9) {
        width: 8%;
    }

    /* Return Date */
    #returnedIcsTable th:nth-child(10),
    #returnedIcsTable td:nth-child(10) {
        width: 12%;
    }

    /* Actions */

    .table-responsive {
        overflow-x: hidden;
    }

    /* Ultra-compact count column */
    #icsTable th:nth-child(1),
    #icsTable td:nth-child(1),
    #returnedIcsTable th:nth-child(1),
    #returnedIcsTable td:nth-child(1) {
        width: 5% !important;
        min-width: 30px;
        max-width: 40px;
        padding: 0.3rem 0.1rem !important;
    }

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
        <span class="badge bg-light text-dark"><?= count($ics_groups); ?> ICS documents</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($ics_groups)): ?>
            <div class="table-responsive">
                <table class="table table-custom" id="icsTable">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">ICS No.</th>
                            <th>Issued To</th>
                            <th>Items</th>
                            <th class="text-center">Total Qty Issued</th>
                            <th class="text-center">Returned</th>
                            <th class="text-center">Re-Issued</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Issue Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1;
                        foreach ($ics_groups as $group): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge badge-custom badge-issued"><?= $count++; ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="view_transactions.php?ics_no=<?= urlencode($group['ICS_No']); ?>" 
                                       class="document-number"
                                       title="Click to view all items in this ICS">
                                        <?= $group['ICS_No'] ?: '-'; ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="employee-info">
                                        <span class="employee-name"><?= $group['employee_names']; ?></span>
                                        <span class="employee-position"><?= $group['office_names']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="item-details">
                                        <div class="item-name">
                                            <?= $group['item_names']; ?>
                                            <span class="items-count" title="<?= $group['transaction_count']; ?> items">
                                                <?= $group['transaction_count']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-custom badge-issued">
                                        <?= $group['total_quantity'] ?> units
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-custom badge-returned">
                                        <?= $group['total_returned'] ?> units
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-custom badge-re-issued">
                                        <?= $group['total_re_issued'] ?: 0; ?> units
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($group['status'] == 'Partially Re-Issued'): ?>
                                        <span class="badge badge-custom badge-partially_re_issued" style="font-size:10px">
                                            <i class="fas fa-redo me-1"></i>Partially Re-Issued
                                        </span>
                                    <?php elseif ($group['status'] == 'Damaged'): ?>
                                        <span class="badge badge-custom badge-damaged">
                                            <i class="fas fa-times-circle me-1"></i>Damaged
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-custom badge-issued">
                                            <i class="fas fa-paper-plane me-1"></i><?= $group['status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <small><?= date('M j, Y', strtotime($group['transaction_date'])); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <a href="view_transaction.php?ics_no=<?= urlencode($group['ICS_No']); ?>"
                                            class="btn btn-outline-success-custom btn-sm"
                                            title="View All Items">
                                            <i class="fas fa-list"></i>
                                        </a>

                                        <!-- <?php if (strtolower(trim($group['status'])) !== 'returned' && strtolower(trim($group['status'])) !== 'damaged'): ?>
                                            <button class="btn btn-return btn-sm return-ics-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#returnModal"
                                                data-ics-no="<?= $group['ICS_No']; ?>"
                                                data-items-count="<?= $group['transaction_count']; ?>"
                                                title="Return Items">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php endif; ?> -->
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




    <!-- Returned Semi-Expendable (ICS) Table -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-undo me-2"></i> Returned Semi-Expendable Properties (Available for Re-Issue)</h5>
            <span class="badge bg-light text-dark"><?= count($returned_ics); ?> items</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($returned_ics)): ?>
                <div class="table-responsive">
                    <table class="table table-custom" id="returnedIcsTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">ICS No.</th>
                                <th class="text-center">RRSP No.</th>
                                <th>Returned By</th>
                                <th>Item Details</th>
                                <th class="text-center">Qty Returned</th>
                                <th class="text-center">Qty Available</th>
                                <th class="text-center">Condition</th>
                                <th class="text-center">Return Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1;
                            foreach ($returned_ics as $r):
                                // Calculate available quantity for re-issue
                                $available_qty = $r['qty_returned'] - $r['qty_re_issued'];
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued"><?= $count++; ?></span>
                                    </td>
                                    <td class="document-number"><?= $r['ICS_No'] ?: '-'; ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $r['RRSP_No'] ?: '-'; ?></span>
                                    </td>
                                    <td>
                                        <div class="employee-info">
                                            <span class="employee-name"><?= $r['employee_name']; ?></span>
                                            <span class="employee-position"><?= $r['position']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-name" title="<?= htmlspecialchars($r['item']); ?>"><?= htmlspecialchars($r['item']); ?></div>
                                            <div class="item-description"><?= htmlspecialchars($r['item_description']); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-returned"><?= $r['qty'] ?> <?= $r['unit']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-custom badge-issued" style="background: rgba(40, 167, 69, 0.2); color: #1e7e34;">
                                            <?= $available_qty ?> <?= $r['unit']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $condition = $r['conditions'];
                                        if ($condition == 'Functional') {
                                            echo '<span class="badge badge-success">
                                                    <i class="fas fa-check-circle me-1"></i> Functional
                                                </span>';
                                        } elseif ($condition == 'Damaged') {
                                            echo '<span class="badge badge-danger">
                                                    <i class="fas fa-times-circle me-1"></i> Damaged
                                                </span>';
                                        } else {
                                            echo '<span class="badge badge-secondary">
                                                    <i class="fas fa-info-circle me-1"></i> ' . ($condition ?: 'Unknown') . '
                                                </span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <small><?= date('M j, Y', strtotime($r['return_date'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <a href="view_transaction.php?id=<?= $r['transaction_id']; ?>"
                                                class="btn btn-outline-success-custom btn-sm"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php
                                            $condition = $r['conditions'];
                                            // Show re-issue button only for functional items
                                            if ($condition != 'Damaged' && $available_qty > 0): ?>
                                                <button class="btn btn-primary-custom btn-sm reissue-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#reissueModal"
                                                    data-id="<?= $r['transaction_id']; ?>"
                                                    data-item="<?= htmlspecialchars($r['item']); ?>"
                                                    data-returned="<?= $r['qty_returned']; ?>"
                                                    data-reissued="<?= $r['qty_re_issued'] ?? 0; ?>"
                                                    title="Re-Issue Item">
                                                    <i class="fas fa-redo"></i>
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
                    <div class="empty-state-icon"><i class="fas fa-box"></i></div>
                    <h4>No Items Available for Re-Issue</h4>
                    <p>All returned items have been re-issued or there are no returned items yet.</p>
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
                        <input type="hidden" id="functional_qty_available">

                        <p class="text-success fw-bold mb-3" id="reissue_item_name"></p>

                        <!-- Condition Status Alert -->
                        <div class="alert alert-info" id="conditionAlert" style="display: none;">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="conditionMessage"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Quantity to Re-Issue</label>
                            <input type="number" name="reissue_qty" class="form-control border-success" min="1" required>
                            <small id="reissueHint" class="text-muted fst-italic">
                                Maximum re-issuable quantity will appear once item is selected.
                            </small>
                            <div id="functionalQtyInfo" class="mt-1" style="display: none;">
                                <small class="text-success fw-bold">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <span id="functionalQtyText"></span> functional item(s) available for re-issue
                                </small>
                            </div>
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
                        <button type="submit" class="btn btn-success" id="reissueSubmitBtn">
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
                            <small id="remainingHint" class="text-muted fst-italic">
                                Maximum quantity to return will appear when you select an item.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">Date of Return</label>
                            <input type="date" name="return_date" class="form-control border-success" value="<?= date('Y-m-d'); ?>" required>
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
</div>

<?php include_once('layouts/footer.php'); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const returnModalEl = document.getElementById('returnModal');
        const returnForm = document.getElementById('returnForm');
        const returnQtyInput = returnForm.querySelector('input[name="return_qty"]');

        returnModalEl.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;

            const transId = button.dataset.id;
            const itemName = button.dataset.item;
            const issuedQty = parseInt(button.dataset.quantity || 0);
            const returnedQty = parseInt(button.dataset.returned || 0);
            const remainingQty = issuedQty - returnedQty;

            document.getElementById('return_transaction_id').value = transId;
            document.getElementById('return_item_name').textContent = `Return: ${itemName}`;

            returnQtyInput.value = '';
            returnQtyInput.max = remainingQty;
            returnQtyInput.min = 1;
            returnQtyInput.placeholder = `Max: ${remainingQty}`;
            returnQtyInput.dataset.remaining = remainingQty;

            if (remainingQty <= 0) {
                returnQtyInput.disabled = true;
                returnQtyInput.placeholder = 'No quantity left to return';
            } else {
                returnQtyInput.disabled = false;
            }
        });

        returnQtyInput.addEventListener('input', () => {
            const entered = parseInt(returnQtyInput.value || 0);
            const max = parseInt(returnQtyInput.dataset.remaining || 0);
            if (entered > max) {
                returnQtyInput.value = max;
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Limit Exceeded',
                    text: `You can only return up to ${max} item(s).`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });

        returnForm.addEventListener('submit', async e => {
            e.preventDefault();

            const formData = new FormData(returnForm);
            const submitBtn = returnForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

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
                        title: 'Item Returned Successfully',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    const modal = bootstrap.Modal.getInstance(returnModalEl);
                    modal.hide();

                    setTimeout(() => {
                        location.reload();
                    }, 500);

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Return Failed',
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

        // Handle Re-Issue Modal
        const reissueModalEl = document.getElementById('reissueModal');
        const reissueForm = document.getElementById('reissueForm');
        const reissueQtyInput = reissueForm.querySelector('input[name="reissue_qty"]');
        const functionalQtyAvailable = document.getElementById('functional_qty_available');
        const functionalQtyInfo = document.getElementById('functionalQtyInfo');
        const functionalQtyText = document.getElementById('functionalQtyText');
        const conditionAlert = document.getElementById('conditionAlert');
        const conditionMessage = document.getElementById('conditionMessage');
        const reissueSubmitBtn = document.getElementById('reissueSubmitBtn');

        reissueModalEl.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            if (!button) return;

            const transId = button.dataset.id;
            const itemName = button.dataset.item;
            const returnedQty = parseInt(button.dataset.returned || 0);
            const reissuedQty = parseInt(button.dataset.reissued || 0);

            document.getElementById('reissue_transaction_id').value = transId;
            document.getElementById('reissue_item_name').textContent = `Re-Issue: ${itemName}`;

            // Reset UI elements
            functionalQtyInfo.style.display = 'none';
            conditionAlert.style.display = 'none';
            reissueQtyInput.disabled = true;
            reissueSubmitBtn.disabled = true;
            reissueQtyInput.value = '';
            reissueQtyInput.placeholder = 'Checking item conditions...';

            try {
                // Fetch functional quantity from server - use process_reissue.php without reissue_qty
                const response = await fetch('process_reissue.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `transaction_id=${transId}` // No reissue_qty parameter = functional check
                });

                const data = await response.json();

                if (data.success) {
                    const functionalQty = parseInt(data.functional_qty);
                    const availableFunctional = functionalQty - reissuedQty;

                    // Update the hidden field
                    functionalQtyAvailable.value = availableFunctional;

                    if (availableFunctional <= 0) {
                        // No functional items available
                        reissueQtyInput.disabled = true;
                        reissueQtyInput.placeholder = 'No functional items available';
                        conditionAlert.style.display = 'block';
                        conditionMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> No functional items available for re-issue.';
                        conditionAlert.className = 'alert alert-warning';
                        reissueSubmitBtn.disabled = true;
                    } else {
                        // Functional items available
                        reissueQtyInput.disabled = false;
                        reissueQtyInput.max = availableFunctional;
                        reissueQtyInput.min = 1;
                        reissueQtyInput.placeholder = `Max: ${availableFunctional}`;
                        reissueQtyInput.dataset.remaining = availableFunctional;

                        // Show functional quantity info
                        functionalQtyInfo.style.display = 'block';
                        functionalQtyText.textContent = `${availableFunctional}`;

                        // Show condition status
                        conditionAlert.style.display = 'block';
                        if (functionalQty < returnedQty) {
                            const damagedQty = returnedQty - functionalQty;
                            conditionMessage.innerHTML = `<i class="fas fa-info-circle me-1"></i> ${damagedQty} item(s) are damaged and cannot be re-issued.`;
                            conditionAlert.className = 'alert alert-info';
                        } else {
                            conditionMessage.innerHTML = '<i class="fas fa-check-circle me-1"></i> All returned items are functional and available for re-issue.';
                            conditionAlert.className = 'alert alert-success';
                        }

                        reissueSubmitBtn.disabled = false;
                    }
                } else {
                    throw new Error(data.message || 'Failed to fetch item conditions');
                }
            } catch (error) {
                console.error('Error:', error);
                reissueQtyInput.disabled = true;
                reissueQtyInput.placeholder = 'Error checking conditions';
                conditionAlert.style.display = 'block';
                conditionMessage.textContent = 'Error checking item conditions. Please try again.';
                conditionAlert.className = 'alert alert-danger';
                reissueSubmitBtn.disabled = true;
            }
        });

        // Prevent exceeding functional quantity
        reissueQtyInput.addEventListener('input', () => {
            const entered = parseInt(reissueQtyInput.value || 0);
            const max = parseInt(reissueQtyInput.dataset.remaining || 0);
            if (entered > max) {
                reissueQtyInput.value = max;
                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Limit Exceeded',
                    text: `You can only re-issue up to ${max} functional item(s).`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });

        // Handle AJAX reissue submission
        reissueForm.addEventListener('submit', async (e) => {
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

        // Initialize DataTables
        $(document).ready(function() {
            $('#icsTable').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                ordering: true,
                searching: true,
                responsive: true,
                columnDefs: [{
                    orderable: false,
                    targets: [8] // Actions column
                }],
            });

            $('#returnedIcsTable').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                ordering: true,
                searching: true,
                responsive: true,
                autoWidth: false,
                columnDefs: [{
                    orderable: false,
                    targets: [9], // Actions column
                }],
            });
        });
    });
</script>