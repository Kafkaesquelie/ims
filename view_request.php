<?php
$page_title = 'Request Details';
require_once('includes/load.php');
page_require_level(3);

// Check if request ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $session->msg("d", "Request ID not specified.");
    redirect('user_logs.php');
}

$request_id = (int)$_GET['id'];
$current_user = current_user(); 
$user_id = (int)$current_user['id'];

// Get request details with all items
$request = find_by_sql("
    SELECT 
        r.*,
        u.name as requester_name,
        d.division_name,
        o.office_name,
        fc.name as fund_cluster_name,
        COUNT(ri.id) as total_items,
        SUM(ri.qty) as total_quantity,
        SUM(ri.price * ri.qty) as total_cost
    FROM requests r
    LEFT JOIN users u ON r.requested_by = u.id
    LEFT JOIN divisions d ON u.division = d.id
    LEFT JOIN offices o ON u.office = o.id
    LEFT JOIN request_items ri ON r.id = ri.req_id
    LEFT JOIN items i ON ri.item_id = i.id
    LEFT JOIN fund_clusters fc ON i.fund_cluster = fc.id
    WHERE r.id = '{$request_id}'
    AND r.requested_by = '{$user_id}'
    GROUP BY r.id
");

if(!$request || count($request) === 0) {
    $session->msg("d", "Request not found or you don't have permission to view it.");
    redirect('user_logs.php');
}

$request = $request[0];

// Get all request items
$request_items = find_by_sql("
    SELECT 
        ri.*,
        i.name as item_name,
        i.description as item_description,
        u.name as unit_name,
        u.symbol as unit_symbol
    FROM request_items ri
    LEFT JOIN items i ON ri.item_id = i.id
    LEFT JOIN units u ON i.unit_id = u.id
    WHERE ri.req_id = '{$request_id}'
    ORDER BY i.name
");

// Handle status display
$status = strtolower($request['status']);
if ($status === 'cancelled') {
    $status = 'canceled';
}

// Determine badge class
$badgeClass = 'badge-secondary';
switch($status) {
    case 'completed': $badgeClass = 'badge-completed'; break;
    case 'canceled': $badgeClass = 'badge-canceled'; break;
    case 'pending': $badgeClass = 'badge-pending'; break;
    case 'approved': $badgeClass = 'badge-approved'; break;
    case 'declined': $badgeClass = 'badge-declined'; break;
    default: $badgeClass = 'badge-secondary';
}
?>

<?php include_once('layouts/header.php'); ?>
<style>
    :root {
        --primary: #28a745;
        --primary-dark: #1e7e34;
        --primary-light: #34ce57;
        --primary-lighter: #d4edda;
        --primary-soft: #e8f5e8;
        --secondary: #6c757d;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #2d3748;
        --border-radius: 12px;
        --shadow: 0 4px 15px rgba(40, 167, 69, 0.15);
        --shadow-light: 0 2px 8px rgba(40, 167, 69, 0.1);
    }
    
    body {
        background: linear-gradient(135deg, #f8fff8 0%, #f0f9f0 100%);
        min-height: 100vh;
    }
    
    .form-container {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 0;
        margin-bottom: 2rem;
        border: 1px solid #e1f5e1;
        overflow: hidden;
    }
    
    .form-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 1.5rem 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .form-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: rotate(45deg);
    }
    
    .form-title {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        position: relative;
        z-index: 2;
    }
    
    .form-content {
        padding: 2rem;
    }
    
    .form-section {
        margin-bottom: 2.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e8f5e8;
        background: var(--primary-soft);
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: var(--shadow-light);
    }
    
    .form-section:last-of-type {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 1.5rem;
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--primary-light);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .section-title i {
        color: var(--primary);
        background: var(--primary-lighter);
        padding: 0.5rem;
        border-radius: 8px;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -12px;
        margin-left: -12px;
        margin-bottom: 1rem;
    }
    
    .form-group {
        padding-right: 12px;
        padding-left: 12px;
        margin-bottom: 1.25rem;
        flex: 1 0 0%;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--primary-dark);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-control-static {
        display: block;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        font-weight: 500;
        color: var(--dark);
        background: white;
        border: 2px solid #e8f5e8;
        border-radius: 8px;
        min-height: auto;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .form-control-static:hover {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }
    
    .status-badge {
        padding: 0.75rem 1.25rem;
        border-radius: 25px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 120px;
        text-align: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 2px solid transparent;
    }
    
    .badge-completed {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-color: var(--primary-dark);
    }
    
    .badge-canceled {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border-color: #5a6268;
    }
    
    .badge-pending {
        background: linear-gradient(135deg, var(--warning), #e0a800);
        color: #000;
        border-color: #e0a800;
    }
    
    .badge-approved {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border-color: #138496;
    }
    
    .badge-declined {
        background: linear-gradient(135deg, var(--danger), #c82333);
        color: white;
        border-color: #c82333;
    }
    
    .table-custom {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow-light);
        border: 1px solid #e8f5e8;
    }
    
    .table-custom thead th {
        background-color:#1e7e34;
        border-bottom: none;
        font-weight: 700;
        color: white;
        padding: 1.25rem 1rem;
        text-align: center;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table-custom tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        border-color: #f1f8f1;
        font-weight: 500;
    }
    
    .table-custom tbody tr {
        transition: all 0.3s ease;
    }
    
    .table-custom tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.08);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
    }
    
    .total-row {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.08)) !important;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-dark);
    }
    
    .remarks-box {
        background: white;
        border-radius: 10px;
        padding: 1.25rem;
        border: 2px solid #e8f5e8;
        color: #4a5568;
        min-height: 120px;
        font-style: italic;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        line-height: 1.6;
    }
    
    .remarks-box.empty {
        color: #a0aec0;
        font-style: normal;
    }
    
    .btn-back {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .btn-back::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn-back:hover {
        background: linear-gradient(135deg, var(--primary-dark), #155724);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        text-decoration: none;
    }
    
    .btn-back:hover::before {
        left: 100%;
    }
    
    .btn-print {
        background: linear-gradient(135deg, #2d3748, #4a5568);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.875rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(45, 55, 72, 0.3);
    }
    
    .btn-print:hover {
        background: linear-gradient(135deg, #4a5568, #2d3748);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(45, 55, 72, 0.4);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #718096;
        background: white;
        border-radius: 10px;
        border: 2px dashed #e8f5e8;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: var(--primary-light);
        opacity: 0.7;
    }
    
    .badge-primary-custom {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .badge-secondary-custom {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .page-header {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-light);
        border-left: 4px solid var(--primary);
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .form-content {
            padding: 1.5rem;
        }
        
        .form-header {
            padding: 1.25rem 1.5rem;
        }
        
        .form-title {
            font-size: 1.2rem;
        }
        
        .section-title {
            font-size: 1rem;
        }
        
        .form-group {
            flex: 0 0 100%;
        }
        
        .status-badge {
            min-width: 100px;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
        }
        
        .table-custom thead th,
        .table-custom tbody td {
            padding: 1rem 0.75rem;
            font-size: 0.85rem;
        }
        
        .btn-back, .btn-print {
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            width: 100%;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        
        .page-header {
            padding: 1.25rem 1.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .form-content {
            padding: 1rem;
        }
        
        .form-header {
            padding: 1rem 1.25rem;
        }
        
        .form-section {
            padding: 1rem;
        }
        
        .empty-state {
            padding: 2rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
        }
        
        .section-title i {
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
        }
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-3 mb-md-0">
                    <h5 class="mb-2"><i class="fas fa-file-invoice me-2" style="color: var(--primary);"></i>Request Details</h5>
                    <p class="text-muted mb-0">Complete information for RIS No: <strong class="text-primary"><?php echo htmlspecialchars($request['ris_no']); ?></strong></p>
                </div>
                <a href="user_logs.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to History
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Form Container -->
<div class="form-container">
    <div class="form-header">
        <h5 class="form-title">
            <i class="fas fa-file-alt"></i> Request Information
        </h5>
    </div>
    
    <div class="form-content">
        <!-- Basic Information Section -->
        <div class="form-section">
            <h6 class="section-title">
                <i class="fas fa-info-circle"></i> Basic Information
            </h6>
            
            <div class="form-row">
                <div class="form-group" style="flex: 0 0 25%;">
                    <label class="form-label">RIS Number</label>
                    <div class="form-control-static"><?php echo htmlspecialchars($request['ris_no']); ?></div>
                </div>
                
                <div class="form-group" style="flex: 0 0 25%;">
                    <label class="form-label">Status</label>
                    <div>
                        <span class="status-badge <?php echo $badgeClass; ?>">
                            <i class="fas 
                                <?php 
                                switch($status) {
                                    case 'completed': echo 'fa-check-circle'; break;
                                    case 'canceled': echo 'fa-ban'; break;
                                    case 'pending': echo 'fa-clock'; break;
                                    case 'approved': echo 'fa-thumbs-up'; break;
                                    case 'declined': echo 'fa-times-circle'; break;
                                    default: echo 'fa-info-circle';
                                }
                                ?>">
                            </i>
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                </div>
                
                <div class="form-group" style="flex: 0 0 25%;">
                    <label class="form-label">Request Date</label>
                    <div class="form-control-static"><?php echo date('F j, Y', strtotime($request['date'])); ?></div>
                </div>
                
                <div class="form-group" style="flex: 0 0 25%;">
                    <label class="form-label">Fund Cluster</label>
                    <div class="form-control-static"><?php echo htmlspecialchars($request['fund_cluster_name'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group" style="flex: 0 0 33.333%;">
                    <label class="form-label">Division</label>
                    <div class="form-control-static"><?php echo htmlspecialchars($request['division_name'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="form-group" style="flex: 0 0 33.333%;">
                    <label class="form-label">Office</label>
                    <div class="form-control-static"><?php echo htmlspecialchars($request['office_name'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="form-group" style="flex: 0 0 33.333%;">
                    <label class="form-label">Requester</label>
                    <div class="form-control-static"><?php echo htmlspecialchars($request['requester_name']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Summary Section -->
        <div class="form-section">
            <h6 class="section-title">
                <i class="fas fa-chart-bar"></i> Request Summary
            </h6>
            
            <div class="form-row">
                <div class="form-group" style="flex: 0 0 33.333%;">
                    <label class="form-label">Total Items</label>
                    <div class="form-control-static text-center">
                        <span class="badge-primary-custom"><?php echo number_format($request['total_items']); ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="flex: 0 0 33.333%;">
                    <label class="form-label">Total Quantity</label>
                    <div class="form-control-static text-center">
                        <span class="badge-primary-custom"><?php echo number_format($request['total_quantity']); ?></span>
                    </div>
                </div>
                
                <div class="form-group" style="flex: 0 0 33.333%;">
                    <label class="form-label">Total Cost</label>
                    <div class="form-control-static text-center fw-bold" style="color: var(--primary-dark);">
                        ₱<?php echo number_format($request['total_cost'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Remarks Section -->
        <?php if(!empty($request['remarks'])): ?>
        <div class="form-section">
            <h6 class="section-title">
                <i class="fas fa-comment-dots"></i> Remarks
            </h6>
            
            <div class="form-row">
                <div class="form-group" style="flex: 0 0 100%;">
                    <div class="remarks-box">
                        <?php echo nl2br(htmlspecialchars($request['remarks'])); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Requested Items Section -->
        <div class="form-section">
            <h6 class="section-title">
                <i class="fas fa-list-check"></i> Requested Items
            </h6>
            
            <div class="form-row">
                <div class="form-group" style="flex: 0 0 100%;">
                    <?php if(count($request_items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>Item Name</th>
                                        <th>Description</th>
                                        <th width="10%">Quantity</th>
                                        <th width="15%">Unit</th>
                                        <th width="15%">Unit Price</th>
                                        <th width="15%">Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    $grand_total = 0;
                                    ?>
                                    <?php foreach($request_items as $item): ?>
                                        <?php 
                                        $item_total = $item['qty'] * $item['price'];
                                        $grand_total += $item_total;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $counter++; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td>
                                                <?php if(!empty($item['item_description'])): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['item_description']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge-primary-custom"><?php echo number_format($item['qty']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if(!empty($item['unit_name'])): ?>
                                                    <span class="badge-secondary-custom"><?php echo htmlspecialchars($item['unit_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold">₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="text-end fw-bold" style="color: var(--primary-dark);">₱<?php echo number_format($item_total, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Grand Total Row -->
                                    <tr class="total-row">
                                        <td colspan="5"></td>
                                        <td class="text-end"><strong>Grand Total:</strong></td>
                                        <td class="text-end"><strong style="color: var(--primary-dark);">₱<?php echo number_format($grand_total, 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No Items Found</h5>
                            <p class="text-muted">No items were found for this request.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Additional Information Section -->
        <div class="form-section">
            <h6 class="section-title">
                <i class="fas fa-clock"></i> Additional Information
            </h6>
            
            <div class="form-row">
                <div class="form-group" style="flex: 0 0 50%;">
                    <label class="form-label">Created At</label>
                    <div class="form-control-static"><?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></div>
                </div>
                
                <div class="form-group" style="flex: 0 0 50%;">
                    <label class="form-label">Last Updated</label>
                    <div class="form-control-static">
                        <?php 
                        if(!empty($request['updated_at']) && $request['updated_at'] != '0000-00-00 00:00:00') {
                            echo date('F j, Y g:i A', strtotime($request['updated_at']));
                        } else {
                            echo 'Never updated';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12 text-center">
        <a href="user_logs.php" class="btn-back me-3">
            <i class="fas fa-arrow-left"></i> Back to History
        </a>
      
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<script>
$(document).ready(function () {
    // Add print styles
    const printStyle = `
        @media print {
            .btn-back, .btn-print, .form-header {
                display: none !important;
            }
            .form-container {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
            .form-section {
                border-bottom: 1px solid #dee2e6 !important;
                background: white !important;
                box-shadow: none !important;
            }
            body {
                background: white !important;
            }
            .page-header {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
        }
    `;
    
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = printStyle;
    document.head.appendChild(styleSheet);
    
    // Add subtle animations
    $('.form-section').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
            $(this).css('box-shadow', '0 6px 20px rgba(40, 167, 69, 0.15)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
            $(this).css('box-shadow', '0 2px 8px rgba(40, 167, 69, 0.1)');
        }
    );
});
</script>