<?php
$page_title = 'Edit Semi-Expendable Property';
require_once('includes/load.php');
page_require_level(1); // Only admins

// ✅ Collect multiple IDs
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    $session->msg("d", "No items selected for bulk edit.");
    redirect('smp.php');
}

$ids = explode(',', $_GET['ids']);
$ids = array_map('intval', $ids);
$id_list = implode(',', $ids);

// ✅ Fetch items
$query = "SELECT * FROM semi_exp_prop WHERE id IN ($id_list)";
$items = $db->query($query);
if (!$items || $items->num_rows == 0) {
    $session->msg("d", "No valid items found for bulk edit.");
    redirect('smp.php');
}

// ✅ Fetch categories
$semi_categories = $db->query("SELECT * FROM semicategories ORDER BY semicategory_name ASC");

// ✅ Handle bulk update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update'])) {
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $id => $data) {
            $fund_cluster     = remove_junk($db->escape($data['fund_cluster']));
            $inv_item_no      = !empty($data['inv_item_no']) ? "'" . $db->escape($data['inv_item_no']) . "'" : "NULL";
            $item_description = remove_junk($db->escape($data['item_description']));
            $unit             = remove_junk($db->escape($data['unit']));
            $unit_cost        = (float)$data['unit_cost'];
            $estimated_use    = remove_junk($db->escape($data['estimated_use']));
            $status           = remove_junk($db->escape($data['status']));
            $semicategory_id  = (int)$data['semicategory_id'];

            $query  = "UPDATE semi_exp_prop SET 
                fund_cluster='{$fund_cluster}',
                inv_item_no={$inv_item_no},
                item_description='{$item_description}',
                unit='{$unit}',
                unit_cost='{$unit_cost}',
                estimated_use='{$estimated_use}',
                status='{$status}',
                semicategory_id='{$semicategory_id}',
                last_edited = NOW()
                WHERE id='{$id}'";
            $db->query($query);
        }
        $session->msg("s", "✅ Bulk update applied to selected items!");
    } else {
        $session->msg("d", "❌ No items submitted.");
    }
    redirect('smp.php', false);
}
?>

<?php include_once('layouts/header.php'); ?>

<style>
    :root {
        --primary: #28a745;
        --primary-light: #d4edda;
        --primary-dark: #1e7e34;
        --secondary: #6c757d;
        --light: #f8f9fa;
        --dark: #343a40;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);
    }

    .item-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        border-left: 5px solid var(--primary);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .item-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }

    .form-section {
        padding: 1.5rem;
    }

    .form-group-custom {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        display: block;
        width: 100%;
    }

    .form-control-custom {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        width: 100%;
        box-sizing: border-box;
    }

    .form-control-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .form-select-custom {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        width: 100%;
        box-sizing: border-box;
    }

    .form-select-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .btn-success-custom {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 10px;
        padding: 1rem 2.5rem;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-success-custom:hover {
        background: linear-gradient(135deg, var(--primary-dark), #155724);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .btn-secondary-custom {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 1rem 2.5rem;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .btn-secondary-custom:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        transform: translateY(-2px);
        color: white;
    }

    .info-alert {
        background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        border: none;
        border-radius: 12px;
        border-left: 5px solid #17a2b8;
        color: #0c5460;
        font-weight: 500;
    }

    .counter-badge {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .required-field::after {
        content: " *";
        color: #dc3545;
    }

    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }

    .form-col {
        flex: 1;
        min-width: 0;
        padding: 0 10px;
        margin-bottom: 1rem;
    }

    .form-col-full {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .form-col-half {
        flex: 0 0 50%;
        max-width: 50%;
    }

    .form-col-third {
        flex: 0 0 33.333%;
        max-width: 33.333%;
    }

    .form-col-quarter {
        flex: 0 0 25%;
        max-width: 25%;
    }

    .item-header {
        background: linear-gradient(135deg, var(--primary-light), #c8e6c9);
        padding: 1.2rem 1.5rem;
        border-bottom: 2px solid var(--primary);
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .item-header-content {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
    }

    .item-header-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }

    .item-description {
        font-weight: 600;
        color: var(--primary-dark);
        font-size: 1rem;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
        min-width: 0;
    }

    .item-badge {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .item-counter {
        color: var(--secondary);
        font-size: 0.85rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Floating Action Buttons */
    .floating-buttons {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 15px;
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        border: 2px solid var(--primary-light);
        transition: all 0.3s ease;
    }

    .floating-buttons:hover {
        box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        transform: translateY(-2px);
    }

    .floating-btn {
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        min-width: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .floating-btn-save {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .floating-btn-save:hover {
        background: linear-gradient(135deg, var(--primary-dark), #155724);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .floating-btn-cancel {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .floating-btn-cancel:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        color: white;
    }

    .floating-buttons-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary-dark);
        text-align: center;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-light);
    }

    @media (max-width: 768px) {
        .form-col-half,
        .form-col-third,
        .form-col-quarter {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .floating-buttons {
            bottom: 20px;
            right: 20px;
            left: 20px;
            flex-direction: row;
            justify-content: center;
        }
        
        .floating-btn {
            min-width: 140px;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
        }

        .item-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .item-header-info {
            width: 100%;
            justify-content: space-between;
        }

        .item-description {
            font-size: 0.9rem;
        }
    }

    /* Hide original action buttons */
    .original-buttons {
        display: none;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-2">
                    <i class="fas fa-pen-to-square me-3"></i>Bulk Edit Semi-Expendable Properties
                </h2>
                <p class="mb-0 opacity-75">Update multiple semi-expendable properties simultaneously with this bulk editing interface</p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="counter-badge">
                    <i class="fas fa-boxes me-2"></i>Editing <?= count($ids); ?> Item(s)
                </span>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert info-alert mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x me-3"></i>
            <div>
                <h6 class="alert-heading mb-1">Bulk Editing Instructions</h6>
                <p class="mb-0">Edit the fields below for each item. Each card represents one semi-expendable property item.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="" id="bulkEditForm">
        <input type="hidden" name="bulk_update" value="1">

        <?php 
        $items->data_seek(0); 
        $item_count = 0;
        while ($row = $items->fetch_assoc()): 
            $item_count++;
        ?>
            <div class="item-card">
                <!-- Fixed Header -->
                <div class="item-header">
                    <div class="item-header-info">
                        <div class="item-header-content">
                            <i class="fas fa-box me-2 text-success"></i>
                            <h6 class="item-description"><?= remove_junk($row['item_description']); ?></h6>
                        </div>
                        <span class="item-badge"><?= $row['inv_item_no']; ?></span>
                    </div>
                    <div class="item-counter">
                        <i class="fas fa-edit me-1"></i>Item #<?= $item_count; ?>
                    </div>
                </div>

                <div class="form-section">
                    <input type="hidden" name="items[<?= $row['id']; ?>][id]" value="<?= $row['id']; ?>">

                    <div class="form-row">
                        <!-- Row 1: Fund Cluster & Inventory Item No -->
                        <div class="form-col form-col-half">
                            <div class="form-group-custom">
                                <label class="form-label required-field">Fund Cluster</label>
                                <input type="text" class="form-control-custom" name="items[<?= $row['id']; ?>][fund_cluster]" 
                                    value="<?= $row['fund_cluster']; ?>" required>
                            </div>
                        </div>
                        <div class="form-col form-col-half">
                            <div class="form-group-custom">
                                <label class="form-label">Inventory Item No.</label>
                                <input type="text" class="form-control-custom" name="items[<?= $row['id']; ?>][inv_item_no]" 
                                    value="<?= $row['inv_item_no']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <!-- Row 2: Category, Description & Unit -->
                        <div class="form-col form-col-third">
                            <div class="form-group-custom">
                                <label class="form-label required-field">Semi-Expendable Category</label>
                                <select class="form-select-custom" name="items[<?= $row['id']; ?>][semicategory_id]" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    $semi_categories->data_seek(0); 
                                    while ($cat = $semi_categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id']; ?>" <?= ($cat['id']==$row['semicategory_id'])?'selected':''; ?>>
                                            <?= remove_junk($cat['semicategory_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col form-col-third">
                            <div class="form-group-custom">
                                <label class="form-label required-field">Item Description</label>
                                <input type="text" class="form-control-custom" name="items[<?= $row['id']; ?>][item_description]" 
                                    value="<?= $row['item_description']; ?>" required>
                            </div>
                        </div>
                        <div class="form-col form-col-third">
                            <div class="form-group-custom">
                                <label class="form-label required-field">Unit</label>
                                <input type="text" class="form-control-custom" name="items[<?= $row['id']; ?>][unit]" 
                                    value="<?= $row['unit']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <!-- Row 3: Unit Cost, Estimated Use & Status -->
                        <div class="form-col form-col-third">
                            <div class="form-group-custom">
                                <label class="form-label required-field">Unit Cost</label>
                                <input type="number" step="0.01" class="form-control-custom" name="items[<?= $row['id']; ?>][unit_cost]" 
                                    value="<?= $row['unit_cost']; ?>" required>
                            </div>
                        </div>
                        <div class="form-col form-col-third">
                            <div class="form-group-custom">
                                <label class="form-label">Estimated Use</label>
                                <input type="text" class="form-control-custom" name="items[<?= $row['id']; ?>][estimated_use]" 
                                    value="<?= $row['estimated_use']; ?>">
                            </div>
                        </div>
                        <div class="form-col form-col-third">
                            <div class="form-group-custom">
                                <label class="form-label">Status</label>
                                <select class="form-select-custom" name="items[<?= $row['id']; ?>][status]">
                                    <option value="available" <?= $row['status']=='available'?'selected':''; ?>>Available</option>
                                    <option value="issued" <?= $row['status']=='issued'?'selected':''; ?>>Issued</option>
                                    <option value="returned" <?= $row['status']=='returned'?'selected':''; ?>>Returned</option>
                                    <option value="lost" <?= $row['status']=='lost'?'selected':''; ?>>Lost</option>
                                    <option value="disposed" <?= $row['status']=='disposed'?'selected':''; ?>>Disposed</option>
                                    <option value="archived" <?= $row['status']=='archived'?'selected':''; ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <!-- Original Action Buttons (Hidden) -->
        <div class="original-buttons text-center my-5">
            <button type="submit" class="btn btn-success-custom me-3">
                <i class="fas fa-save me-2"></i>Apply Changes to All Items
            </button>
            <a href="smp.php" class="btn btn-secondary-custom">
                <i class="fas fa-arrow-left me-2"></i>Back to Properties
            </a>
        </div>
    </form>

    <!-- Floating Action Buttons -->
    <div class="floating-buttons">
        <div class="floating-buttons-label">
            <i class="fas fa-bolt me-1"></i>Quick Actions
        </div>
        <button type="submit" form="bulkEditForm" class="floating-btn floating-btn-save">
            <i class="fas fa-save"></i>
            Save All Changes
        </button>
        <a href="smp.php" class="floating-btn floating-btn-cancel">
            <i class="fas fa-times"></i>
            Cancel
        </a>
    </div>
</div>

<script>
// Smooth scroll to top when clicking floating buttons
document.addEventListener('DOMContentLoaded', function() {
    const floatingButtons = document.querySelector('.floating-buttons');
    
    // Add scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            floatingButtons.style.opacity = '0.9';
            floatingButtons.style.transform = 'translateY(0)';
        } else {
            floatingButtons.style.opacity = '1';
            floatingButtons.style.transform = 'translateY(0)';
        }
    });

    // Form validation before submit
    const form = document.getElementById('bulkEditForm');
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let valid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '#e9ecef';
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields (marked with *) before saving.');
        }
    });
});
</script>

<?php include_once('layouts/footer.php'); ?>