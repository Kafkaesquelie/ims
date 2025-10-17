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
$fund_clusters = array_unique(array_column($items, 'fund_cluster'));
$fund_cluster_display = !empty($fund_clusters) ? implode(', ', array_filter($fund_clusters)) : '__________';

// Current logged-in user
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";
$current_user_position = $current_user['position'] ?? 'Administrator';

// Generate RIS format
$ris_no_display = !empty($request['ris_no']) ? $request['ris_no'] : date("Y-m") . '-0000';

?>

<?php include_once('layouts/header.php'); ?>

<style>
/* Main Styling - Green Theme */
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

.ris-display {
    background: var(--primary-green);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 1.1rem;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(30, 126, 52, 0.3);
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

/* Table Styling */
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

/* Signatures Section */
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

/* Action Buttons */
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

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    .ris-form, .ris-form * {
        visibility: visible;
    }
    
    .action-buttons, header, footer, .breadcrumb {
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

/* Responsive Design */
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
}

/* Additional Green Accents */
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
                            <span class="ris-display"><?= $ris_no_display ?></span>
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
                                <?php foreach ($items as $item): 
                                    $item_stock = find_by_id('items', $item['item_id']);
                                    $stock_available = $item_stock ? ($item['qty'] <= $item_stock['quantity']) : false;
                                ?>
                                <tr>
                                    <td><strong>0<?= (int)$item['stock_card'] ?></strong></td>
                                    <td><?= remove_junk($item['unit']) ?></td>
                                    <td><?= remove_junk($item['item_name']) ?></td>
                                    <td><strong><?= (float)$item['qty'] ?></strong></td>
                                    <td>
                                        <?php if ($stock_available): ?>
                                            <span class="stock-check stock-yes">✔</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$stock_available): ?>
                                            <span class="stock-check stock-no">✘</span>
                                        <?php endif; ?>
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

    
    <?php if(strtolower($request['status']) !== 'approved'): ?>
    <a href="approve_req.php?id=<?= (int)$request['id'] ?>" class="action-btn btn-approve approve-btn">
        <i class="fas fa-check"></i> Approve
    </a>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Approval confirmation
    document.querySelectorAll('.approve-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
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
});
</script>

<?php include_once('layouts/footer.php'); ?>