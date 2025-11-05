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

// Handle RIS number update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ris'])) {
    $new_ris_no = trim($db->escape($_POST['ris_no']));
    
    // Validate RIS number
    if (empty($new_ris_no)) {
        $session->msg("d", "RIS Number cannot be empty.");
        redirect("r_view.php?id={$request_id}", false);
    }
    
    // Check RIS format
    $ris_parts = explode('-', $new_ris_no);
    if (count($ris_parts) !== 3) {
        $session->msg("d", "Invalid RIS Number format. Should be YYYY-MMDD-XXXX.");
        redirect("r_view.php?id={$request_id}", false);
    }
    
    // Check middle digits should not be 0000
    if ($ris_parts[1] === '0000') {
        $session->msg("d", "RIS Number middle digits cannot be '0000'. Please edit the middle part.");
        redirect("r_view.php?id={$request_id}", false);
    }
    
    // Check if middle digits are duplicate
    $middle_digits = $ris_parts[1];
    $check_middle_sql = "SELECT r.id 
                        FROM requests r 
                        WHERE r.ris_no LIKE '%-{$middle_digits}-%' 
                        AND r.id != '{$request_id}' 
                        LIMIT 1";
    $check_middle_result = $db->query($check_middle_sql);
    
    if ($db->num_rows($check_middle_result) > 0) {
        $session->msg("d", "Middle digits '{$middle_digits}' are already used in another RIS Number. Please use different middle digits.");
        redirect("r_view.php?id={$request_id}", false);
    }
    
    // Check if full RIS number is duplicate (excluding current request)
    $check_sql = "SELECT id FROM requests WHERE ris_no = '{$new_ris_no}' AND id != '{$request_id}' LIMIT 1";
    $check_result = $db->query($check_sql);
    
    if ($db->num_rows($check_result) > 0) {
        $session->msg("d", "RIS Number '{$new_ris_no}' is already used by another request.");
        redirect("r_view.php?id={$request_id}", false);
    }
    
    // Update RIS number
    $update_sql = "UPDATE requests SET ris_no = '{$new_ris_no}' WHERE id = '{$request_id}'";
    if ($db->query($update_sql)) {
        $session->msg("s", "RIS Number updated successfully to: {$new_ris_no}");
        redirect("r_view.php?id={$request_id}", false);
    } else {
        $session->msg("d", "Failed to update RIS Number.");
        redirect("r_view.php?id={$request_id}", false);
    }
}

// Get requestor name
$user = find_by_id('users', $request['requested_by']);
if ($user) {
    $requestor_name = $user['name'];
    $requestor_position = $user['position'] ?? '';
} else {
    $employee = find_by_id('employees', $request['requested_by']);
    if ($employee) {
        $first = remove_junk($employee['first_name']);
        $middle = remove_junk($employee['middle_name']);
        $last = remove_junk($employee['last_name']);
        $requestor_name = trim("$first $middle $last");
        $requestor_position = $employee['position'] ?? '';
    } else {
        $requestor_name = 'Unknown';
        $requestor_position = '';
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
        i.fund_cluster
    FROM request_items ri
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE ri.req_id = '{$request_id}'
");

// Get unique fund clusters
$fund_clusters = [];
foreach ($items as $item) {
    if (!empty($item['fund_cluster'])) {
        $fund_clusters[] = $item['fund_cluster'];
    }
}
$fund_clusters = array_unique($fund_clusters);
$fund_cluster_display = !empty($fund_clusters) ? implode(', ', $fund_clusters) : '__________';

// Current logged-in user
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";
$current_user_position = $current_user['position'] ?? 'Administrator';

// Check RIS number status
$current_ris_no = $request['ris_no'] ?? '';
$is_ris_missing = empty($current_ris_no);
$is_ris_duplicate = false;
$is_ris_default_middle = false;
$is_middle_duplicate = false;

if (!$is_ris_missing) {
    // Check for duplicate full RIS number
    $check_duplicate_sql = "SELECT id FROM requests WHERE ris_no = '{$current_ris_no}' AND id != '{$request_id}' LIMIT 1";
    $duplicate_result = $db->query($check_duplicate_sql);
    $is_ris_duplicate = $db->num_rows($duplicate_result) > 0;
    
    // Parse RIS number parts
    $ris_parts = explode('-', $current_ris_no);
    if (count($ris_parts) === 3) {
        $is_ris_default_middle = ($ris_parts[1] === '0000');
        
        // Check for duplicate middle digits
        $middle_digits = $ris_parts[1];
        $check_middle_sql = "SELECT r.id 
                            FROM requests r 
                            WHERE r.ris_no LIKE '%-{$middle_digits}-%' 
                            AND r.id != '{$request_id}' 
                            LIMIT 1";
        $check_middle_result = $db->query($check_middle_sql);
        $is_middle_duplicate = $db->num_rows($check_middle_result) > 0;
    }
}

// Check if approval is allowed
$can_approve = !$is_ris_missing && 
               !$is_ris_duplicate && 
               !$is_ris_default_middle &&
               !$is_middle_duplicate &&
               strtolower($request['status']) !== 'approved' && 
               strtolower($request['status']) !== 'canceled';

// Check if request is canceled
$is_canceled = strtolower($request['status']) === 'canceled';

?>

<?php include_once('layouts/header.php'); ?>

<style>
:root {
    --primary-green: #1e7e34;
    --secondary-green: #28a745;
    --light-green: #d4edda;
    --dark-green: #155724;
    --accent-green: #34ce57;
    --border-color: #c3e6cb;
    --light-bg: #f8fff9;
}

.ris-form {
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(30, 126, 52, 0.15);
    overflow: hidden;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
}

.ris-header {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    padding: 1.5rem 2rem;
    border-bottom: 4px solid var(--accent-green);
}

.ris-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    text-align: center;
    letter-spacing: 1px;
}

.ris-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    text-align: center;
    margin: 0.5rem 0 0 0;
}

.ris-body {
    padding: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--light-bg);
    border-radius: 8px;
    border-left: 4px solid var(--accent-green);
    border: 1px solid var(--border-color);
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-weight: 600;
    color: var(--dark-green);
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-weight: 500;
    font-size: 1rem;
    color: var(--dark-green);
}

.ris-input-container {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.ris-input {
    background: var(--primary-green);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 1.1rem;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(30, 126, 52, 0.3);
    border: 2px solid transparent;
    transition: all 0.3s ease;
    min-width: 150px;
    text-align: center;
    flex: 0 0 auto;
}

.ris-input:focus {
    outline: none;
    border-color: var(--accent-green);
    background: var(--secondary-green);
    box-shadow: 0 4px 8px rgba(30, 126, 52, 0.4);
}

.ris-input.editable {
    background: var(--secondary-green);
    cursor: text;
}

.ris-input.readonly {
    background: var(--primary-green);
    cursor: not-allowed;
}

.ris-edit-btn {
    background: var(--accent-green);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
    flex: 0 0 auto;
}

.ris-edit-btn:hover {
    background: var(--dark-green);
    transform: translateY(-1px);
}

.ris-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
    width: 100%;
}

.ris-save-btn {
    background: var(--primary-green);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ris-save-btn:hover {
    background: var(--dark-green);
}

.ris-cancel-btn {
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ris-cancel-btn:hover {
    background: #5a6268;
}

.ris-status {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    width: 100%;
    padding: 0.5rem;
    border-radius: 4px;
    text-align: center;
}

.ris-valid {
    color: var(--primary-green);
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid var(--border-color);
}

.ris-invalid {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.ris-warning {
    color: #ffc107;
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.fund-cluster-display {
    background: var(--secondary-green);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.ris-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
}

.ris-input-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.ris-input.editable-middle {
    background: white;
    color: var(--dark-green);
    border: 2px solid var(--accent-green);
    min-width: 80px;
    text-align: center;
}

.ris-fixed-display {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.ris-fixed-part {
    font-weight: bold;
    color: white;
    font-size: 1.1rem;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(30, 126, 52, 0.1);
    border: 1px solid var(--border-color);
}

.items-table thead {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
}

.items-table th {
    padding: 1rem 0.75rem;
    font-weight: 600;
    text-align: center;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

.items-table td {
    padding: 1rem 0.75rem;
    text-align: center;
    border-bottom: 1px solid var(--light-green);
    vertical-align: middle;
}

.items-table tbody tr {
    transition: background-color 0.2s ease;
}

.items-table tbody tr:hover {
    background-color: var(--light-bg);
}

.items-table tbody tr:last-child td {
    border-bottom: none;
}

.stock-check {
    font-weight: bold;
    font-size: 1.1rem;
}

.stock-yes {
    color: var(--primary-green);
}

.stock-no {
    color: #dc3545;
}

.signatures-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-top: 2.5rem;
    padding-top: 2rem;
    border-top: 2px dashed var(--border-color);
}

.signature-box {
    text-align: center;
    padding: 1rem;
    background: var(--light-bg);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.signature-label {
    font-weight: 600;
    color: var(--dark-green);
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.signature-line {
    border-bottom: 2px solid var(--border-color);
    padding: 2rem 0 1rem 0;
    margin-bottom: 0.5rem;
    min-height: 60px;
}

.signature-name {
    font-weight: 700;
    color: var(--dark-green);
    font-size: 0.95rem;
}

.signature-position {
    font-size: 0.8rem;
    color: var(--primary-green);
    margin-top: 0.25rem;
}

.action-buttons {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 1000;
}

.action-btn {
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(30, 126, 52, 0.3);
    transition: all 0.3s ease;
    border: none;
    min-width: 140px;
    justify-content: center;
    font-size: 0.9rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(30, 126, 52, 0.4);
    text-decoration: none;
}

.action-btn:disabled {
    background: #6c757d !important;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.action-btn:disabled:hover {
    transform: none;
    box-shadow: 0 4px 15px rgba(30, 126, 52, 0.3);
}

.btn-back {
    background: #6c757d;
    color: white;
}

.btn-back:hover {
    background: #5a6268;
}

.btn-approve {
    background: var(--primary-green);
    color: white;
}

.btn-approve:hover {
    background: var(--dark-green);
}

.btn-print {
    background: var(--secondary-green);
    color: white;
}

.btn-print:hover {
    background: var(--primary-green);
}

@media print {
    body * {
        visibility: hidden;
    }
    
    .ris-form, .ris-form * {
        visibility: visible;
    }
    
    .action-buttons, header, footer, .breadcrumb, .ris-edit-btn, .ris-actions, .ris-status {
        display: none !important;
    }
    
    .ris-form {
        box-shadow: none;
        margin: 0;
        padding: 0;
        border: 1px solid #000;
    }
    
    .ris-body {
        padding: 1rem;
    }
    
    .items-table {
        font-size: 11px;
        border: 1px solid #000;
    }
    
    .signatures-grid {
        margin-top: 1rem;
        padding-top: 1rem;
    }
    
    .info-grid {
        background: white !important;
        border: 1px solid #000 !important;
    }
    
    .signature-box {
        background: white !important;
        border: 1px solid #000 !important;
    }
    
    @page {
        size: legal portrait;
        margin: 0.5in;
    }
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .signatures-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .action-buttons {
        bottom: 20px;
        right: 20px;
        left: 20px;
        flex-direction: row;
        justify-content: center;
    }
    
    .action-btn {
        min-width: auto;
        padding: 10px 20px;
        font-size: 0.8rem;
    }
    
    .items-table {
        font-size: 0.8rem;
    }
    
    .items-table th,
    .items-table td {
        padding: 0.5rem 0.25rem;
    }
    
    .ris-header {
        padding: 1rem 1.5rem;
    }
    
    .ris-title {
        font-size: 1.4rem;
    }
    
    .ris-input-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .ris-edit-btn {
        margin-top: 0.5rem;
    }
}

.ris-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-green), var(--accent-green), var(--secondary-green));
}

.breadcrumb {
    background: var(--light-bg);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
}

.breadcrumb-item.active {
    color: var(--primary-green);
    font-weight: 600;
}

.breadcrumb-item a {
    color: var(--secondary-green);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--dark-green);
    text-decoration: underline;
}
</style>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <!-- Display messages -->
            <?php echo display_msg($msg); ?>

            <!-- RIS Form -->
            <div class="ris-form position-relative">
                <div class="ris-header">
                    <h1 class="ris-title">REQUISITION AND ISSUE SLIP</h1>
                    <p class="ris-subtitle">BENGUET STATE UNIVERSITY - BOKOD CAMPUS</p>
                </div>
                
                <div class="ris-body">
                    <!-- Information Grid -->
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Entity Name</span>
                            <span class="info-value">BENGUET STATE UNIVERSITY - BOKOD CAMPUS</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fund Cluster</span>
                            <span class="fund-cluster-display"><?= $fund_cluster_display ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Responsibility Center Code</span>
                            <span class="info-value">BOKOD</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">RIS Number</span>
                            <div class="ris-input-group">
                                <div class="ris-input-row">
                                    <form id="risForm" method="post" style="display: contents;">
                                        <input type="hidden" name="update_ris" value="1">
                                        <input type="text" 
                                               name="ris_no" 
                                               value="<?= htmlspecialchars($current_ris_no) ?>" 
                                               class="ris-input readonly" 
                                               id="risInput"
                                               readonly
                                               maxlength="20"
                                               pattern="[A-Za-z0-9\-]+"
                                               title="RIS Number format: YYYY-MM-XXXX">
                                        <button type="button" class="ris-edit-btn" id="risEditBtn">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </form>
                                </div>
                                <div class="ris-actions" id="risActions" style="display: none;">
                                    <button type="submit" form="risForm" class="ris-save-btn">
                                        <i class="fas fa-check"></i> Save
                                    </button>
                                    <button type="button" class="ris-cancel-btn" id="risCancelBtn">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                                <!-- RIS Status Display -->
                                <div class="ris-status" id="risStatus">
                                    <?php if ($is_ris_missing): ?>
                                        <span class="ris-invalid"><i class="fas fa-exclamation-triangle"></i> RIS Number required</span>
                                    <?php elseif ($is_ris_duplicate): ?>
                                        <span class="ris-invalid"><i class="fas fa-times-circle"></i> Duplicate RIS Number</span>
                                    <?php elseif ($is_ris_default_middle): ?>
                                        <span class="ris-invalid"><i class="fas fa-exclamation-circle"></i> Edit middle digits (cannot be 0000)</span>
                                    <?php elseif ($is_middle_duplicate): ?>
                                        <span class="ris-invalid"><i class="fas fa-times-circle"></i> Middle digits already used</span>
                                    <?php else: ?>
                                        <span class="ris-valid"><i class="fas fa-check-circle"></i> Valid RIS Number</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Stock No.</th>
                                    <th>Unit</th>
                                    <th>Item Description</th>
                                    <th>Quantity</th>
                                    <th colspan="2">Stock Available</th>
                                    <th>Issue Quantity</th>
                                    <th>Remarks</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>Yes</th>
                                    <th>No</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><strong>0<?= (int)$item['stock_card'] ?></strong></td>
                                        <td><?= remove_junk($item['unit']) ?></td>
                                        <td><?= remove_junk($item['item_name']) ?></td>
                                        <td><strong><?= (float)$item['qty'] ?></strong></td>
                                        <td>
                                            <span class="stock-check stock-yes">✔</span>
                                        </td>
                                        <td>
                                        </td>
                                        <td><strong><?= (float)$item['qty'] ?></strong></td>
                                        <td><small><?= remove_junk($item['remarks']) ?: '-' ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <!-- Empty rows for additional items -->
                                <?php for ($i = 0; $i < 3; $i++): ?>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Signatures Section -->
                    <div class="signatures-grid">
                        <div class="signature-box">
                            <div class="signature-label">Requested by:</div>
                            <div class="signature-line"></div>
                            <div class="signature-name"><?= remove_junk($requestor_name) ?></div>
                            <div class="signature-position"><?= remove_junk($requestor_position) ?></div>
                        </div>
                        
                        <div class="signature-box">
                            <div class="signature-label">Approved by:</div>
                            <div class="signature-line"></div>
                            <div class="signature-name"><?= $current_user_name ?></div>
                            <div class="signature-position"><?= $current_user_position ?></div>
                        </div>
                        
                        <div class="signature-box">
                            <div class="signature-label">Issued by:</div>
                            <div class="signature-line"></div>
                            <div class="signature-name"><?= $current_user_name ?></div>
                            <div class="signature-position"><?= $current_user_position ?></div>
                        </div>
                        
                        <div class="signature-box">
                            <div class="signature-label">Received by:</div>
                            <div class="signature-line"></div>
                            <div class="signature-name">___________________</div>
                            <div class="signature-position">Signature over Printed Name</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="requests.php" class="action-btn btn-back">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <?php if(!$is_canceled && strtolower($request['status']) !== 'approved'): ?>
    <a href="approve_req.php?id=<?= (int)$request['id'] ?>" 
       class="action-btn btn-approve approve-btn" 
       id="approveBtn"
       <?= !$can_approve ? 'disabled' : '' ?>>
        <i class="fas fa-check"></i> 
        <?= $can_approve ? 'Approve' : 'Cannot Approve' ?>
    </a>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const risInput = document.getElementById('risInput');
    const risEditBtn = document.getElementById('risEditBtn');
    const risCancelBtn = document.getElementById('risCancelBtn');
    const risActions = document.getElementById('risActions');
    const risForm = document.getElementById('risForm');
    const risStatus = document.getElementById('risStatus');
    const approveBtn = document.getElementById('approveBtn');
    let originalRisValue = risInput.value;
    let editableInput = null;
    let fixedDisplay = null;

    // RIS number formatting functions
    function formatRISNumber(year, middle, sequence) {
        return `${year}-${middle}-${sequence}`;
    }

    function parseRISNumber(risValue) {
        if (!risValue) {
            const currentYear = new Date().getFullYear().toString();
            return { year: currentYear, middle: '0000', sequence: '0001' };
        }
        
        const parts = risValue.split('-');
        if (parts.length === 3) {
            return {
                year: parts[0] || '2024',
                middle: parts[1] || '0000',
                sequence: parts[2] || '0001'
            };
        }
        return { year: '2024', middle: '0000', sequence: '0001' };
    }

    function validateMiddleDigits(middle) {
        return /^\d{4}$/.test(middle) && middle !== '0000';
    }

    function isDefaultMiddleDigits(middle) {
        return middle === '0000';
    }

    // Initialize RIS number if empty
    if (!originalRisValue) {
        const parts = parseRISNumber('');
        originalRisValue = formatRISNumber(parts.year, parts.middle, parts.sequence);
        risInput.value = originalRisValue;
    }

    // RIS number editing - create editable middle part only
    risEditBtn.addEventListener('click', function() {
        const parts = parseRISNumber(risInput.value);
        
        // Hide the original input
        risInput.style.display = 'none';
        
        // Create display for fixed parts with editable middle
        fixedDisplay = document.createElement('div');
        fixedDisplay.className = 'ris-fixed-display';
        fixedDisplay.style.display = 'flex';
        fixedDisplay.style.alignItems = 'center';
        fixedDisplay.style.gap = '5px';
        fixedDisplay.style.marginBottom = '10px';
        fixedDisplay.style.flexWrap = 'wrap';
        
        // First part (fixed)
        const yearSpan = document.createElement('span');
        yearSpan.className = 'ris-fixed-part';
        yearSpan.textContent = parts.year + '-';
        yearSpan.style.fontWeight = 'bold';
        yearSpan.style.color = 'white';
        yearSpan.style.fontSize = '1.1rem';
        
        // Middle part (editable)
        editableInput = document.createElement('input');
        editableInput.type = 'text';
        editableInput.value = parts.middle;
        editableInput.className = 'ris-input editable-middle';
        editableInput.style.width = '80px';
        editableInput.style.textAlign = 'center';
        editableInput.style.fontSize = '1.1rem';
        editableInput.style.fontWeight = 'bold';
        editableInput.maxLength = 4;
        editableInput.pattern = '[0-9]{4}';
        editableInput.title = 'Enter 4 digits for the middle part (cannot be 0000)';
        editableInput.placeholder = '0000';
        
        // Last part (fixed)
        const sequenceSpan = document.createElement('span');
        sequenceSpan.className = 'ris-fixed-part';
        sequenceSpan.textContent = '-' + parts.sequence;
        sequenceSpan.style.fontWeight = 'bold';
        sequenceSpan.style.color = 'white';
        sequenceSpan.style.fontSize = '1.1rem';
        
        fixedDisplay.appendChild(yearSpan);
        fixedDisplay.appendChild(editableInput);
        fixedDisplay.appendChild(sequenceSpan);
        
        risInput.parentNode.insertBefore(fixedDisplay, risInput);
        
        // Focus and select the editable part
        editableInput.focus();
        editableInput.select();
        
        risActions.style.display = 'flex';
        risEditBtn.style.display = 'none';

        // Handle input validation for middle digits
        editableInput.addEventListener('input', function(e) {
            // Allow only digits
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 4 digits
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }
            
            // Real-time validation
            const middleValue = this.value;
            if (middleValue.length === 4) {
                if (isDefaultMiddleDigits(middleValue)) {
                    this.style.borderColor = '#dc3545';
                    this.style.backgroundColor = '#fff5f5';
                } else {
                    this.style.borderColor = '#28a745';
                    this.style.backgroundColor = '#f8fff9';
                }
            } else {
                this.style.borderColor = '#ffc107';
                this.style.backgroundColor = '#fffbf0';
            }
        });

        // Handle Enter key to save
        editableInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                saveRISChanges();
            } else if (e.key === 'Escape') {
                cancelRISEdit();
            }
        });
    });

    // Save RIS changes
    function saveRISChanges() {
        if (!editableInput) return;
        
        const middleValue = editableInput.value;
        
        // Validate middle digits
        if (middleValue.length !== 4) {
            showAlert('Invalid Input', 'Middle part must be exactly 4 digits.', 'error');
            editableInput.focus();
            editableInput.select();
            return;
        }
        
        if (isDefaultMiddleDigits(middleValue)) {
            showAlert('Invalid Input', 'Middle digits cannot be "0000". Please enter a different 4-digit number.', 'error');
            editableInput.focus();
            editableInput.select();
            return;
        }

        // Get the fixed parts
        const parts = parseRISNumber(risInput.value);
        
        // Reconstruct full RIS number
        const newRIS = formatRISNumber(parts.year, middleValue, parts.sequence);
        
        // Check for duplicates before saving
        checkRISDuplicate(newRIS, middleValue).then(({isDuplicate, isMiddleDuplicate}) => {
            if (isDuplicate && newRIS !== originalRisValue) {
                showAlert('Duplicate RIS', 'This RIS Number is already used by another request. Please use a different number.', 'error');
                editableInput.focus();
                editableInput.select();
                return;
            }
            
            if (isMiddleDuplicate && middleValue !== parseRISNumber(originalRisValue).middle) {
                showAlert('Duplicate Middle Digits', 'Middle digits "' + middleValue + '" are already used in another RIS Number. Please use different middle digits.', 'error');
                editableInput.focus();
                editableInput.select();
                return;
            }
            
            // If no duplicates, proceed with saving
            risInput.value = newRIS;
            risForm.submit();
        });
    }

    // Cancel RIS edit
    function cancelRISEdit() {
        // Restore original state
        risInput.style.display = 'inline-block';
        if (fixedDisplay) {
            fixedDisplay.remove();
            fixedDisplay = null;
        }
        editableInput = null;
        risActions.style.display = 'none';
        risEditBtn.style.display = 'block';
        updateRISStatus(originalRisValue);
    }

    // Handle save button click
    risForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveRISChanges();
    });

    // Handle cancel button click
    risCancelBtn.addEventListener('click', function() {
        cancelRISEdit();
    });

    // Real-time RIS validation for display
    risInput.addEventListener('input', function() {
        updateRISStatus(this.value);
    });

    // Form submission with validation (for direct form submission)
    risForm.addEventListener('submit', function(e) {
        // This prevents the default form submission when using the editable input
        if (editableInput) {
            e.preventDefault();
            return;
        }
        
        const risValue = risInput.value.trim();
        
        if (!risValue) {
            showAlert('Error', 'RIS Number cannot be empty.', 'error');
            e.preventDefault();
            return;
        }

        const parts = parseRISNumber(risValue);
        if (parts.middle.length !== 4) {
            showAlert('Invalid Format', 'Middle part must be exactly 4 digits.', 'error');
            e.preventDefault();
            return;
        }
        
        if (isDefaultMiddleDigits(parts.middle)) {
            showAlert('Invalid Format', 'Middle digits cannot be "0000". Please edit the middle part.', 'error');
            e.preventDefault();
            return;
        }

        // Check for duplicate RIS number
        checkRISDuplicate(risValue, parts.middle).then(({isDuplicate, isMiddleDuplicate}) => {
            if (isDuplicate && risValue !== originalRisValue) {
                showAlert('Duplicate RIS', 'This RIS Number is already used by another request. Please use a different number.', 'error');
                e.preventDefault();
                return;
            }
            
            if (isMiddleDuplicate && parts.middle !== parseRISNumber(originalRisValue).middle) {
                showAlert('Duplicate Middle Digits', 'Middle digits "' + parts.middle + '" are already used in another RIS Number. Please use different middle digits.', 'error');
                e.preventDefault();
                return;
            }
        });
    });

    // Approval confirmation with validation
    document.querySelectorAll('.approve-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (button.disabled) {
                e.preventDefault();
                
                const risValue = risInput.value.trim();
                let message = '';
                
                if (!risValue) {
                    message = 'RIS Number is required before approval. Please set a valid RIS Number.';
                } else {
                    const parts = parseRISNumber(risValue);
                    if (isDefaultMiddleDigits(parts.middle)) {
                        message = 'RIS Number middle digits cannot be "0000". Please edit the middle part before approval.';
                    } else {
                        message = 'RIS Number is either missing, duplicate, or has duplicate middle digits. Please fix the RIS Number before approval.';
                    }
                }
                
                showAlert('Cannot Approve', message, 'warning');
                return;
            }

            e.preventDefault();
            const url = this.getAttribute('href');
            
            Swal.fire({
                title: 'Approve This Request?',
                text: 'This action will approve the request and cannot be undone.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1e7e34',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve Request',
                cancelButtonText: 'Cancel',
                background: '#fff',
                backdrop: 'rgba(30, 126, 52, 0.1)'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    function updateRISStatus(risValue) {
        if (!risValue.trim()) {
            risStatus.innerHTML = '<span class="ris-invalid"><i class="fas fa-exclamation-triangle"></i> RIS Number required</span>';
            updateApproveButton(false);
            return;
        }

        const parts = parseRISNumber(risValue);
        if (parts.middle.length !== 4) {
            risStatus.innerHTML = '<span class="ris-invalid"><i class="fas fa-times-circle"></i> Middle part must be 4 digits</span>';
            updateApproveButton(false);
            return;
        }
        
        if (isDefaultMiddleDigits(parts.middle)) {
            risStatus.innerHTML = '<span class="ris-invalid"><i class="fas fa-exclamation-circle"></i> Edit middle digits (cannot be 0000)</span>';
            updateApproveButton(false);
            return;
        }

        checkRISDuplicate(risValue, parts.middle).then(({isDuplicate, isMiddleDuplicate}) => {
            if (isDuplicate && risValue !== originalRisValue) {
                risStatus.innerHTML = '<span class="ris-invalid"><i class="fas fa-times-circle"></i> Duplicate RIS Number</span>';
                updateApproveButton(false);
            } else if (isMiddleDuplicate && parts.middle !== parseRISNumber(originalRisValue).middle) {
                risStatus.innerHTML = '<span class="ris-invalid"><i class="fas fa-times-circle"></i> Middle digits already used</span>';
                updateApproveButton(false);
            } else {
                risStatus.innerHTML = '<span class="ris-valid"><i class="fas fa-check-circle"></i> Valid RIS Number</span>';
                updateApproveButton(true);
            }
        });
    }

    function checkRISDuplicate(risValue, middleDigits) {
        return fetch('check_ris_duplicate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ris_no=' + encodeURIComponent(risValue) + 
                  '&middle_digits=' + encodeURIComponent(middleDigits) + 
                  '&exclude_id=<?= $request_id ?>'
        })
        .then(response => response.json())
        .then(data => {
            return {
                isDuplicate: data.exists || false,
                isMiddleDuplicate: data.middle_exists || false
            };
        })
        .catch(error => {
            console.error('Error checking RIS duplicate:', error);
            return { isDuplicate: false, isMiddleDuplicate: false };
        });
    }

    function updateApproveButton(canApprove) {
        if (approveBtn) {
            approveBtn.disabled = !canApprove;
            approveBtn.innerHTML = canApprove ? 
                '<i class="fas fa-check"></i> Approve' : 
                '<i class="fas fa-times"></i> Cannot Approve';
        }
    }

    function showAlert(title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonColor: '#1e7e34',
            background: '#fff'
        });
    }

    // Initial status check
    updateRISStatus(risInput.value);
});
</script>

<?php include_once('layouts/footer.php'); ?>