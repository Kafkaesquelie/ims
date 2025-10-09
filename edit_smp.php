<?php
$page_title = 'Edit Semi-Expandable Property';
require_once('includes/load.php');
page_require_level(1); // Only admins

// ✅ Get ID
$id = (int)$_GET['id'];
if (!$id) {
    $session->msg("d", "Missing item ID.");
    redirect('smp.php');
}

$item = find_by_id('semi_exp_prop', $id);
if (!$item) {
    $session->msg("d", "Item not found.");
    redirect('smp.php');
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_item'])) {
    $fund_cluster     = remove_junk($db->escape($_POST['fund_cluster']));
    $property_no      = trim($_POST['property_no']) !== '' ? "'" . $db->escape($_POST['property_no']) . "'" : "NULL";
    $inv_item_no      = trim($_POST['inv_item_no']) !== '' ? "'" . $db->escape($_POST['inv_item_no']) . "'" : "NULL";
    $item = remove_junk($db->escape($_POST['item']));
    $item_description = remove_junk($db->escape($_POST['item_description']));
    $unit             = remove_junk($db->escape($_POST['unit']));
    $unit_cost        = (float)$_POST['unit_cost'];
    $estimated_use    = remove_junk($db->escape($_POST['estimated_use']));
    $status           = remove_junk($db->escape($_POST['status']));
    $semicategory_id  = (int)$_POST['semicategory_id'];

    $query  = "UPDATE semi_exp_prop SET 
        fund_cluster='{$fund_cluster}',
        inv_item_no={$inv_item_no},
        property_no={$property_no},
        item='{$item}',
        item_description='{$item_description}',
        unit='{$unit}',
        unit_cost='{$unit_cost}',
        estimated_use='{$estimated_use}',
        status='{$status}',
        semicategory_id='{$semicategory_id}',
        last_edited = NOW()
        WHERE id='{$id}'";

    if ($db->query($query)) {
        $session->msg("s", "✅ Item updated successfully!");
        redirect('smp.php', false);
    } else {
        $session->msg("d", "❌ Update failed: " . $db->error);
        redirect("edit_smp.php?id={$id}", false);
    }
}

// ✅ Fetch all semi-expendable categories
$semi_categories = $db->query("SELECT * FROM semicategories ORDER BY semicategory_name ASC");
?>

<?php include_once('layouts/header.php'); ?>

<style>
:root {
    --primary-green: #1e7e34;
    --dark-green: #155724;
    --light-green: #28a745;
    --accent-green: #34ce57;
    --primary-yellow: #ffc107;
    --dark-yellow: #e0a800;
    --light-yellow: #ffda6a;
    --card-bg: #ffffff;
    --text-dark: #343a40;
    --text-light: #6c757d;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --hover-shadow: 0 8px 25px rgba(30, 126, 52, 0.15);
}

/* Header Styling */
.edit-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border-left: 5px solid var(--primary-yellow);
}

.edit-header h4 {
    margin: 0;
    font-weight: 700;
    font-size: 1.8rem;
}

/* Card Styling */
.edit-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    border-top: 3px solid var(--primary-green);
    overflow: hidden;
}

.edit-card:hover {
    box-shadow: var(--hover-shadow);
    transform: translateY(-2px);
}

.card-body-custom {
    padding: 2rem;
}

/* Form Styling */
.form-label-custom {
    font-weight: 600;
    color: var(--dark-green);
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control-custom, .form-select-custom {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.form-control-custom:focus, .form-select-custom:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(30, 126, 52, 0.25);
    background-color: #f8fff9;
}

/* Buttons */
.btn-custom {
    border-radius: 10px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    border: none;
    min-width: 140px;
}

.btn-success-custom {
    background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
    color: white;
}

.btn-success-custom:hover {
    background: linear-gradient(135deg, var(--dark-green), #0f4019);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 126, 52, 0.3);
}

.btn-secondary-custom {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
}

.btn-secondary-custom:hover {
    background: linear-gradient(135deg, #5a6268, #495057);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

/* Info Badges */
.info-badge {
    background: linear-gradient(135deg, var(--light-green), var(--primary-green));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Status Badges */
.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.8rem;
}

.status-available { background: rgba(40, 167, 69, 0.15); color: var(--primary-green); border: 1px solid rgba(40, 167, 69, 0.3); }
.status-issued { background: rgba(255, 193, 7, 0.15); color: #856404; border: 1px solid rgba(255, 193, 7, 0.3); }
.status-returned { background: rgba(23, 162, 184, 0.15); color: #0c5460; border: 1px solid rgba(23, 162, 184, 0.3); }
.status-lost { background: rgba(220, 53, 69, 0.15); color: #721c24; border: 1px solid rgba(220, 53, 69, 0.3); }
.status-disposed { background: rgba(108, 117, 125, 0.15); color: #383d41; border: 1px solid rgba(108, 117, 125, 0.3); }
.status-archived { background: rgba(111, 66, 193, 0.15); color: #2d1a4b; border: 1px solid rgba(111, 66, 193, 0.3); }

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edit-card {
    animation: fadeInUp 0.6s ease forwards;
}

/* Responsive Design */
@media (max-width: 768px) {
    .edit-header {
        padding: 1rem;
        text-align: center;
    }
    
    .edit-header h4 {
        font-size: 1.5rem;
    }
    
    .card-body-custom {
        padding: 1.5rem;
    }
    
    .btn-custom {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        min-width: 120px;
    }
}

/* Metadata Styling */
.metadata-container {
    background: linear-gradient(135deg, #f8fff9, #e8f5e9);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.metadata-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.metadata-item:last-child {
    margin-bottom: 0;
}

.metadata-item i {
    color: var(--primary-green);
    width: 20px;
    margin-right: 0.5rem;
}

.metadata-item span {
    font-size: 0.9rem;
    color: var(--text-dark);
}

/* Cost Display */
.cost-display {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-green);
}

/* Section Headers */
.section-header {
    color: var(--dark-green);
    font-weight: 700;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--accent-green);
}

/* Input Groups */
.input-group-custom .input-group-text {
    background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
    color: white;
    border: 2px solid var(--primary-green);
    border-right: none;
    border-radius: 10px 0 0 10px;
    font-weight: 600;
}

.input-group-custom .form-control-custom {
    border-left: none;
    border-radius: 0 10px 10px 0;
}
</style>

<div class="container mt-4">
    <!-- Header Section -->
    <div class="edit-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-pen-to-square me-2"></i>Edit Semi-Expandable Property</h4>
                <div class="subtitle mt-2">Update property details and inventory information</div>
            </div>
            <div class="text-end">
                <span class="info-badge">
                    <i class="fas fa-hashtag me-1"></i>ID: <?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="edit-card">
                <div class="card-body-custom">
                    <!-- Metadata -->
                    <div class="metadata-container">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="metadata-item">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span><strong>Date Added:</strong> <?= !empty($item['date_added']) ? date('M d, Y h:i A', strtotime($item['date_added'])) : '—' ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metadata-item">
                                    <i class="fas fa-edit"></i>
                                    <span><strong>Last Edited:</strong> <?= !empty($item['last_edited']) ? date('M d, Y h:i A', strtotime($item['last_edited'])) : '—' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="update_item" value="1">

                        <!-- Section 1: Basic Information -->
                        <h5 class="section-header">
                            <i class="fas fa-info-circle me-2"></i> Basic Information
                        </h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-custom">Fund Cluster</label>
                                <input type="text" class="form-control-custom" name="fund_cluster" 
                                       value="<?= $item['fund_cluster'] ?>" 
                                       placeholder="Enter fund cluster" required>
                            </div>
                               <div class="col-md-4">
                                <label class="form-label-custom">SE-PROPERTY NAME</label>
                                <input type="text" class="form-control-custom" name="item" 
                                       value="<?= $item['item'] ?>"
                                       placeholder="Enter property name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Inventory Item No.</label>
                                <input type="text" class="form-control-custom" name="inv_item_no" 
                                       value="<?= $item['inv_item_no'] ?>"
                                       placeholder="Enter inventory item number">
                            </div>
                         
                        </div>

                        <!-- Section 2: Item Details -->
                        <h5 class="section-header">
                            <i class="fas fa-box me-2"></i>Item Details
                        </h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-custom"> Semi-Expendable Category</label>
                                <select class="form-select-custom" name="semicategory_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $semi_categories->data_seek(0); // Reset pointer
                                    while ($cat = $semi_categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id']; ?>" 
                                            <?= ($cat['id'] == $item['semicategory_id']) ? 'selected' : ''; ?>>
                                            <?= remove_junk($cat['semicategory_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom"> Item Description</label>
                                <input type="text" class="form-control-custom" name="item_description" 
                                       value="<?= $item['item_description'] ?>" 
                                       placeholder="Enter item description" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Unit</label><br>
                                <input type="text" class="form-control-custom" name="unit" 
                                       value="<?= $item['unit'] ?>" 
                                       placeholder="e.g., pcs, box, unit" required>
                            </div>
                        </div>

                        <!-- Section 3: Financial & Status -->
                        <h5 class="section-header">
                            <i class="fas fa-chart-line me-2"></i> Financial & Status
                        </h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-custom">Unit Cost</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control-custom" name="unit_cost" 
                                           value="<?= $item['unit_cost'] ?>" 
                                           placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Estimated Use</label>
                                <input type="text" class="form-control-custom" name="estimated_use" 
                                       value="<?= $item['estimated_use'] ?>"
                                       placeholder="Enter estimated use">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Status</label>
                                    <?php 
                                    $status_class = 'status-' . $item['status'];
                                    $status_text = ucfirst($item['status']);
                                    ?>
                                    <span class="status-badge <?= $status_class ?>">
                                        <i class="fas fa-circle me-1" style="font-size: 0.3rem;"></i>
                                       <?= $status_text ?>
                                    </span>
                                <br>
                                <select class="form-select-custom  w-100" name="status">
                                    <option value="available" <?= $item['status']=='available'?'selected':''; ?>>Available</option>
                                    <option value="issued" <?= $item['status']=='issued'?'selected':''; ?>>Issued</option>
                                    <option value="returned" <?= $item['status']=='returned'?'selected':''; ?>>Returned</option>
                                    <option value="lost" <?= $item['status']=='lost'?'selected':''; ?>>Lost</option>
                                    <option value="disposed" <?= $item['status']=='disposed'?'selected':''; ?>>Disposed</option>
                                    <option value="archived" <?= $item['status']=='archived'?'selected':''; ?>>Archived</option>
                                </select>
                               
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-5">
                            <div class="col-md-6 mb-2">
                                <button type="submit" class="btn btn-success-custom w-100 btn-custom">
                                    <i class="fas fa-save me-2"></i> Update Property
                                </button>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="smp.php" class="btn btn-secondary-custom w-100 btn-custom">
                                    <i class="fas fa-arrow-left me-2"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Unit cost validation
    const unitCostInput = document.querySelector('input[name="unit_cost"]');
    
    if (unitCostInput) {
        unitCostInput.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    }

    // Status change visual feedback
    const statusSelect = document.querySelector('select[name="status"]');
    const statusBadge = document.querySelector('.status-badge');
    
    if (statusSelect && statusBadge) {
        statusSelect.addEventListener('change', function() {
            const newStatus = this.value;
            const statusText = this.options[this.selectedIndex].text;
            
            // Remove all status classes
            statusBadge.className = 'status-badge status-' + newStatus;
            
            // Update text
            statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>New: ' + statusText;
        });
    }
});
</script>

<?php include_once('layouts/footer.php'); ?>