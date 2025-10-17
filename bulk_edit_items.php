<?php
$page_title = 'Bulk Edit Items';
require_once('includes/load.php');
page_require_level(1);

// Get IDs from query string
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    $session->msg('d', 'No items selected.');
    redirect('items.php');
}

$ids = array_map('intval', explode(',', $_GET['ids']));
$items = [];
foreach ($ids as $id) {
    $item = find_by_id('items', $id);
    if ($item) $items[] = $item;
}

$categories = find_all('categories');

// Handle bulk update
if (isset($_POST['update_bulk_items'])) {
    foreach ($ids as $id) {
        $name         = remove_junk($db->escape($_POST["name_$id"]));
        $fund_cluster = remove_junk($db->escape($_POST["fund_cluster_$id"]));
        $stock_card   = remove_junk($db->escape($_POST["stock_card_$id"]));
        $categorie_id = (int)$_POST["categorie_id_$id"];
        $UOM          = remove_junk($db->escape($_POST["unit_id_$id"]));
        $quantity     = (int)$_POST["quantity_$id"];
        $unit_cost    = (float)$_POST["unit_cost_$id"];
        $base_unit_id    = !empty($_POST["base_unit_id_$id"]) ? (int)$_POST["base_unit_id_$id"] : "NULL";
        $conversion_rate = !empty($_POST["conversion_rate_$id"]) ? (float)$_POST["conversion_rate_$id"] : 1;


        // Keep existing media_id
        $media_id = $_POST["existing_media_$id"];

        // Handle image upload per item
        if (isset($_FILES["image_$id"]) && $_FILES["image_$id"]["name"] != "") {
            $file_name = basename($_FILES["image_$id"]["name"]);
            $target_dir = "uploads/items/";
            $target_file = $target_dir . $file_name;
            $check = getimagesize($_FILES["image_$id"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["image_$id"]["tmp_name"], $target_file)) {
                    $db->query("INSERT INTO media (file_name) VALUES ('{$file_name}')");
                    $media_id = $db->insert_id();
                }
            }
        }

        // ✅ Get current school year ID
        function get_current_school_year_id()
        {
            global $db;
            $res = $db->query("SELECT id FROM school_years WHERE is_current = 1 LIMIT 1");
            if ($res && $db->num_rows($res) > 0) {
                $row = $res->fetch_assoc();
                return (int)$row['id'];
            }
            return null;
        }

        $current_sy_id = get_current_school_year_id();
        $current_user = current_user()['name'];

        // ✅ Get previous quantity before update
        $prev_qty_result = $db->query("SELECT quantity FROM items WHERE id='{$id}' LIMIT 1");
        $prev_qty_row = $prev_qty_result->fetch_assoc();
        $previous_qty = (int)$prev_qty_row['quantity'];

        // ✅ Update main items table
        $sql = "UPDATE items SET 
            name='{$name}',
            fund_cluster='{$fund_cluster}',
            stock_card='{$stock_card}',
            categorie_id='{$categorie_id}',
            unit_id='{$UOM}',
            base_unit_id={$base_unit_id},
            conversion_rate='{$conversion_rate}',
            quantity='{$quantity}',
            unit_cost='{$unit_cost}',
            media_id='{$media_id}'
        WHERE id='{$id}' LIMIT 1";

        $db->query($sql);

        // ✅ Sync with item_stocks_per_year
        if ($current_sy_id) {
            $check = $db->query("SELECT id FROM item_stocks_per_year WHERE item_id='{$id}' AND school_year_id='{$current_sy_id}' LIMIT 1");
            if ($db->num_rows($check) > 0) {
                $db->query("UPDATE item_stocks_per_year SET stock='{$quantity}' WHERE item_id='{$id}' AND school_year_id='{$current_sy_id}'");
            } else {
                $db->query("INSERT INTO item_stocks_per_year (item_id, school_year_id, stock) VALUES ('{$id}', '{$current_sy_id}', '{$quantity}')");
            }
        }

        // ✅ Log change in stock_history if quantity changed
        if ($quantity != $previous_qty) {
            $remarks = "Quantity changed from {$previous_qty} to {$quantity} during bulk edit.";
            $db->query("INSERT INTO stock_history (item_id, previous_qty, new_qty, change_type, changed_by, remarks, date_changed)
                VALUES ('{$id}', '{$previous_qty}', '{$quantity}', 'adjustment', '{$current_user}', '{$remarks}', NOW())");
        }
    }
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
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
        border-left: 5px solid var(--primary);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .item-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .card-header-custom {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 2px solid var(--primary-light);
        padding: 1.2rem 1.5rem;
        font-weight: 700;
        color: var(--primary-dark);
    }

    .image-section {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-right: 2px dashed #dee2e6;
        padding: 1.5rem;
    }

    .item-image {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .item-image:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: block;
        width: 100%;
        margin-top: 0.5rem;
    }

    .file-input-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .file-input-label {
        display: block;
        padding: 0.75rem 1rem;
        background: var(--primary);
        color: white;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }

    .file-input-label:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
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
        border-left: 5px solid var(--info);
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
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        border: 2px solid var(--primary-light);
        transition: all 0.3s ease;
    }

    .floating-buttons:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
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
    }

    /* Hide original action buttons */
    .original-buttons {
        display: none;
    }

    @media (max-width: 768px) {

        .form-col-half,
        .form-col-third,
        .form-col-quarter {
            flex: 0 0 100%;
            max-width: 100%;
        }
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
                <p class="mb-0 opacity-75">Update multiple items simultaneously with this bulk editing interface</p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="counter-badge">
                    <i class="fas fa-cube me-2"></i>Editing <?= count($ids); ?> Item(s)
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

    <form method="post" action="" enctype="multipart/form-data" id="bulkEditForm">
        <?php foreach ($items as $index => $item): ?>
            <div class="item-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-cube me-2"></i>Item #<?= $index + 1; ?> - ID: <?= $item['id']; ?>
                    </div>
                    <div class="text-muted small">
                        <i class="fas fa-edit me-1"></i>Editing Mode
                    </div>
                </div>

                <div class="row g-0">
                    <!-- Image Section -->
                    <div class="col-lg-4 image-section text-center">
                        <div class="form-group-custom">
                            <label class="form-label fw-bold text-dark">Current Image</label>
                            <div class="mb-3">
                                <?php if (!empty($item['media_id'])):
                                    $media = find_by_id('media', $item['media_id']); ?>
                                    <img src="uploads/items/<?php echo $media['file_name']; ?>"
                                        class="item-image rounded">
                                <?php else: ?>
                                    <img src="uploads/items/default.jpg"
                                        class="item-image rounded">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label fw-bold text-dark">Change Image</label>
                            <div class="file-input-wrapper">
                                <div class="file-input-label">
                                    <i class="fas fa-camera me-2"></i>Choose New Image
                                </div>
                                <input type="file" class="form-control-custom" name="image_<?= $item['id']; ?>" accept="image/*">
                            </div>
                            <input type="hidden" name="existing_media_<?= $item['id']; ?>" value="<?= $item['media_id']; ?>">
                        </div>
                    </div>

                    <!-- Form Fields Section -->
                    <div class="col-lg-8 form-section">
                        <div class="form-row">
                            <!-- Row 1: Item Name & Fund Cluster -->
                            <div class="form-col form-col-half">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Item Name</label>
                                    <input type="text" class="form-control-custom" name="name_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['name']); ?>" required>
                                </div>
                            </div>
                            <div class="form-col form-col-half">
                                <div class="form-group-custom">
                                    <label class="form-label">Fund Cluster</label>
                                    <input type="text" class="form-control-custom" name="fund_cluster_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['fund_cluster']); ?>">
                                </div>
                            </div>

                            <!-- Row 2: Stock Number & Category -->
                            <div class="form-col form-col-half">
                                <div class="form-group-custom">
                                    <label class="form-label">Stock Number</label>
                                    <input type="text" class="form-control-custom" name="stock_card_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['stock_card']); ?>">
                                </div>
                            </div>
                            <div class="form-col form-col-half">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Category</label>
                                    <select class="form-control-custom" name="categorie_id_<?= $item['id']; ?>" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int)$cat['id']; ?>" <?= $item['categorie_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?= remove_junk($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 3: Unit of Measure, Quantity & Unit Cost -->
                            <!-- Row 3: Unit of Measure, Base Unit, Conversion Rate, Quantity & Unit Cost -->
                            <div class="form-col form-col-quarter">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Unit of Measure</label>
                                    <select class="form-control-custom" name="unit_id_<?= $item['id']; ?>" required>
                                        <option value="">-- Select Unit --</option>
                                        <?php
                                        $units = find_all('units');
                                        foreach ($units as $unit) :
                                        ?>
                                            <option value="<?= $unit['id']; ?>" <?= $unit['id'] == $item['unit_id'] ? 'selected' : ''; ?>>
                                                <?= $unit['symbol']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-col form-col-quarter">
                                <div class="form-group-custom">
                                    <label class="form-label">Base Unit</label>
                                    <select class="form-control-custom" name="base_unit_id_<?= $item['id']; ?>">
                                        <option value="">Not Applicable</option>
                                        <?php
                                        $base_units = find_all('base_units');
                                        foreach ($base_units as $base) :
                                        ?>
                                            <option value="<?= $base['id']; ?>" <?= $base['id'] == $item['base_unit_id'] ? 'selected' : ''; ?>>
                                                <?= $base['symbol']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-col form-col-quarter">
                                <div class="form-group-custom">
                                    <label class="form-label">Conversion Rate</label>
                                    <input type="number" step="0.000001" class="form-control-custom"
                                        name="conversion_rate_<?= $item['id']; ?>"
                                        value="<?= isset($item['conversion_rate']) ? $item['conversion_rate'] : ''; ?>"
                                        placeholder="e.g., 10 (1 box = 10 pcs)">
                                </div>
                            </div>

                            <div class="form-col form-col-quarter">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Quantity</label>
                                    <input type="number" class="form-control-custom" name="quantity_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['quantity']); ?>" required min="0">
                                </div>
                            </div>

                            <div class="form-col form-col-quarter">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Unit Cost</label>
                                    <input type="number" step="0.01" class="form-control-custom" name="unit_cost_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['unit_cost']); ?>" required min="0">
                                </div>
                            </div>

                            <div class="form-col form-col-third">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Quantity</label>
                                    <input type="number" class="form-control-custom" name="quantity_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['quantity']); ?>" required min="0">
                                </div>
                            </div>
                            <div class="form-col form-col-third">
                                <div class="form-group-custom">
                                    <label class="form-label required-field">Unit Cost</label>
                                    <input type="number" step="0.01" class="form-control-custom" name="unit_cost_<?= $item['id']; ?>"
                                        value="<?= remove_junk($item['unit_cost']); ?>" required min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Original Action Buttons (Hidden) -->
        <div class="original-buttons text-center my-5">
            <button type="submit" name="update_bulk_items" class="btn btn-success-custom me-3">
                <i class="fas fa-save me-2"></i>Save All Changes
            </button>
            <a href="items.php" class="btn btn-secondary-custom">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </form>

    <!-- Floating Action Buttons -->
    <div class="floating-buttons">
        <div class="floating-buttons-label">
            <i class="fas fa-bolt me-1"></i>Quick Actions
        </div>
        <button type="submit" form="bulkEditForm" name="update_bulk_items" class="floating-btn floating-btn-save">
            <i class="fas fa-save"></i>
            Save All Changes
        </button>
        <a href="items.php" class="floating-btn floating-btn-cancel">
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