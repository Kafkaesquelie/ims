<?php
$page_title = 'Edit Item';
require_once('includes/load.php');
page_require_level(1); // Only admins

// ✅ Check if id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $session->msg('d', 'Missing item ID.');
    redirect('items.php');
}
$item_id = (int)$_GET['id'];

// ✅ Fetch item by ID
$item = find_by_id('items', $item_id);
if (!$item) {
    $session->msg('d', 'Item not found.');
    redirect('items.php');
}

// ✅ Dropdown data
$base_units = find_all('base_units');
$units = find_all('units');
$categories = find_all('categories');
$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");

if (isset($_POST['update_item'])) {
    $req_fields = ['name', 'categorie_id', 'unit_id', 'quantity', 'unit_cost'];
    validate_fields($req_fields);

    if (empty($errors)) {
        $name         = remove_junk($db->escape($_POST['name']));
        $fund_cluster = remove_junk($db->escape($_POST['fund_cluster']));
        $stock_card   = remove_junk($db->escape($_POST['stock_card']));
        $categorie_id = (int)$db->escape($_POST['categorie_id']);
        $unit_id      = (int)$db->escape($_POST['unit_id']);
        $quantity     = (int)$db->escape($_POST['quantity']);
        $unit_cost    = (float)$db->escape($_POST['unit_cost']);
        $base_unit_id = (int)$db->escape($_POST['base_unit_id']);
        $conversion_rate = isset($_POST['conversion_rate']) ? (float)$db->escape($_POST['conversion_rate']) : 1;

        $user = current_user();
        $media_id = $item['media_id'];

        // ✅ Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
            $file_name = basename($_FILES['image']['name']);
            $target_dir = "uploads/items/";
            $target_file = $target_dir . $file_name;
            $check = getimagesize($_FILES["image"]["tmp_name"]);

            if ($check !== false) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $db->query("INSERT INTO media (file_name) VALUES ('{$file_name}')");
                    $media_id = $db->insert_id();
                } else {
                    $session->msg('d', 'Failed to upload image.');
                    redirect('edit_item.php?id=' . $item_id);
                }
            } else {
                $session->msg('d', 'File is not an image.');
                redirect('edit_item.php?id=' . $item_id);
            }
        }

        $old_quantity = (int)$item['quantity'];

        // ✅ Update item details
        $sql = "UPDATE items SET 
                    name = '{$name}',
                    fund_cluster = '{$fund_cluster}',
                    stock_card = '{$stock_card}',
                    categorie_id = '{$categorie_id}',
                    unit_id = '{$unit_id}',
                    base_unit_id = '{$base_unit_id}',
                    quantity = '{$quantity}',
                    unit_cost = '{$unit_cost}',
                    media_id = '{$media_id}',
                    last_edited = NOW()
                WHERE id = '{$item_id}' LIMIT 1";

        if ($db->query($sql)) {

            // ✅ Get current school year
            function get_current_school_year_id() {
                global $db;
                $res = $db->query("SELECT id FROM school_years WHERE is_current = 1 LIMIT 1");
                if ($res && $db->num_rows($res) > 0) {
                    $row = $res->fetch_assoc();
                    return (int)$row['id'];
                }
                return null;
            }
            $current_sy_id = get_current_school_year_id();
            $changed_by = $user['name'];

            // ✅ Update or insert into unit_conversions (always)
            if ($base_unit_id && $conversion_rate > 0 && $base_unit_id != $unit_id) {
                $check_conv = $db->query("SELECT id FROM unit_conversions 
                                          WHERE item_id = '{$item_id}' 
                                          AND from_unit_id = '{$unit_id}' 
                                          AND to_unit_id = '{$base_unit_id}' LIMIT 1");
                if ($db->num_rows($check_conv) > 0) {
                    $db->query("UPDATE unit_conversions 
                                SET conversion_rate = '{$conversion_rate}' 
                                WHERE item_id = '{$item_id}' 
                                AND from_unit_id = '{$unit_id}' 
                                AND to_unit_id = '{$base_unit_id}'");
                } else {
                    $db->query("INSERT INTO unit_conversions (item_id, from_unit_id, to_unit_id, conversion_rate) 
                                VALUES ('{$item_id}', '{$unit_id}', '{$base_unit_id}', '{$conversion_rate}')");
                }
            }

            // ✅ Log stock history if quantity changed
            if ($old_quantity != $quantity) {
                $change_type = $quantity > $old_quantity ? 'stock_in' : 'adjustment';
                $remarks = "Quantity changed from {$old_quantity} to {$quantity}.";
                $db->query("INSERT INTO stock_history 
                            (item_id, previous_qty, new_qty, change_type, changed_by, remarks, date_changed) 
                            VALUES 
                            ('{$item_id}', '{$old_quantity}', '{$quantity}', '{$change_type}', '{$changed_by}', '{$remarks}', NOW())");
            }

            // ✅ Sync yearly stock
            if ($current_sy_id) {
                $check = $db->query("SELECT id FROM item_stocks_per_year 
                                     WHERE item_id='{$item_id}' AND school_year_id='{$current_sy_id}' LIMIT 1");
                if ($db->num_rows($check) > 0) {
                    $db->query("UPDATE item_stocks_per_year 
                                SET stock='{$quantity}', updated_at=NOW() 
                                WHERE item_id='{$item_id}' AND school_year_id='{$current_sy_id}'");
                } else {
                    $db->query("INSERT INTO item_stocks_per_year (item_id, school_year_id, stock, updated_at) 
                                VALUES ('{$item_id}', '{$current_sy_id}', '{$quantity}', NOW())");
                }
            }

            $session->msg('s', 'Item updated successfully!');
            redirect('items.php', false);
        }
    }
}
?>


<?php include_once('layouts/header.php');  ?>

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

    .edit-header h3 {
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

    .card-header-custom {
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
        border-bottom: 2px solid #e8f5e9;
        padding: 1.5rem;
        border-radius: 15px 15px 0 0 !important;
    }

    .card-header-custom h3 {
        margin: 0;
        font-weight: 700;
        color: var(--dark-green);
        font-size: 1.4rem;
    }

    /* Form Styling */
    .form-label-custom {
        font-weight: 600;
        color: var(--dark-green);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control-custom,
    .form-select-custom {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .form-control-custom:focus,
    .form-select-custom:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 0.2rem rgba(30, 126, 52, 0.25);
        background-color: #f8fff9;
    }

    /* Image Section */
    .image-container {
        position: relative;
        display: inline-block;
    }

    .item-image {
        width: 200px;
        height: 200px;
        border-radius: 15px;
        object-fit: cover;
        border: 3px solid var(--primary-green);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .item-image:hover {
        border-color: var(--primary-yellow);
        transform: scale(1.05);
    }

    .file-upload-container {
        position: relative;
    }

    .file-upload-container input[type="file"] {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 1rem;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }

    .file-upload-container input[type="file"]:hover {
        border-color: var(--primary-green);
        background-color: rgba(40, 167, 69, 0.05);
    }

    /* Buttons */
    .btn-custom {
        border-radius: 10px;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        border: none;
        min-width: 150px;
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

        .edit-header h3 {
            font-size: 1.5rem;
        }

        .item-image {
            width: 150px;
            height: 150px;
        }

        .btn-custom {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            min-width: 120px;
        }

        .card-header-custom {
            padding: 1rem;
        }
    }

    /* Cost Display */
    .cost-display {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary-green);
    }

    /* Quantity Status */
    .quantity-status {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .status-low {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .status-good {
        background: rgba(40, 167, 69, 0.15);
        color: var(--primary-green);
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .status-warning {
        background: rgba(255, 193, 7, 0.15);
        color: #856404;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    /* Section Divider */
    .section-divider {
        border-left: 2px solid #e9ecef;
        height: 100%;
    }

    @media (max-width: 768px) {
        .section-divider {
            border-left: none;
            border-top: 2px solid #e9ecef;
            margin: 2rem 0;
            padding-top: 2rem;
        }
    }

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
</style>

<div class="container mt-4">
    <!-- Header Section -->
    <div class="edit-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><i class="fa-solid fa-pen-to-square me-2"></i>Edit Item</h3>
                <div class="subtitle mt-2">Update item details and inventory information</div>
            </div>
            <div class="text-end">
                <span class="info-badge">
                    <i class="fa-solid fa-hashtag me-1"></i>ID: <?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="edit-card">
                <div class="card-header-custom">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">

                        <div class="col-md-6">
                            <div class="metadata-item">
                                <i class="fas fa-calendar-plus"></i>
                                <span><strong>Date Added:</strong> <?php echo !empty($item['date_added']) ? date('M d, Y h:i A', strtotime($item['date_added'])) : '—'; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="metadata-item">
                                <i class="fas fa-edit"></i>
                                <span><strong>Last Edited:</strong> <?php echo !empty($item['last_edited']) ? date('M d, Y h:i A', strtotime($item['last_edited'])) : '—'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="row">

                            <!-- LEFT COLUMN (Image Section) -->
                            <div class="col-md-4 text-center pe-md-4">
                                <div class="mb-4">
                                    <label class="form-label-custom d-block mb-3">Current Image</label>
                                    <div class="image-container">
                                        <?php if (!empty($item['media_id'])):
                                            $media = find_by_id('media', $item['media_id']); ?>
                                            <img src="uploads/items/<?php echo $media['file_name']; ?>"
                                                class="item-image"
                                                alt="<?php echo remove_junk($item['name']); ?>"
                                                onerror="this.src='uploads/items/no_image.png'">
                                        <?php else: ?>
                                            <img src="uploads/items/no_image.png"
                                                class="item-image"
                                                alt="No Image Available">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="file-upload-container">
                                    <label class="form-label-custom d-block mb-2">Change Image</label>
                                    <input type="file" class="form-control-custom" name="image" accept="image/*">
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        Supported: JPG, PNG, GIF (Max: 2MB)
                                    </small>
                                </div>

                                <!-- Quick Stats -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="text-success mb-3"><i class="fa-solid fa-chart-bar me-2"></i>Quick Stats</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Current Stock:</span>
                                        <span class="fw-bold <?php echo $item['quantity'] <= 10 ? 'text-warning' : 'text-success'; ?>">
                                            <?php echo (int)$item['quantity']; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Unit Cost:</span>
                                        <span class="cost-display">₱<?php echo number_format($item['unit_cost'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Value:</span>
                                        <span class="fw-bold text-success">₱<?php echo number_format($item['quantity'] * $item['unit_cost'], 2); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Vertical Divider -->
                            <div class="col-md-1 section-divider d-none d-md-block"></div>

                            <!-- RIGHT COLUMN (Form Fields) -->
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label-custom">Item Name</label>
                                        <input type="text" class="form-control-custom w-100" name="name"
                                            value="<?php echo remove_junk($item['name']); ?>"
                                            placeholder="Enter item name" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Fund Cluster</label>
                                        <select class="form-select-custom w-100" name="fund_cluster" required>
                                            <option value="">Select Fund Cluster</option>
                                            <?php foreach ($fund_clusters as $cluster): ?>
                                                <option value="<?php echo remove_junk($cluster['name']); ?>"
                                                    <?php if ($item['fund_cluster'] == $cluster['name']) echo 'selected'; ?>>
                                                    <?php echo remove_junk($cluster['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Stock Number</label>
                                        <input type="text" class="form-control-custom w-100" name="stock_card"
                                            value="<?php echo remove_junk($item['stock_card']); ?>"
                                            placeholder="Enter stock number">
                                    </div>
                                </div>



                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Category</label><br>
                                        <select class="form-select-custom w-100" name="categorie_id" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo (int)$cat['id']; ?>"
                                                    <?php if ($item['categorie_id'] == $cat['id']) echo 'selected'; ?>>
                                                    <?php echo remove_junk($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Unit of Measure</label><br>
                                        <select class="form-select-custom p-2 w-100" name="unit_id" required>
                                            <option value="">Select unit</option>
                                            <?php foreach ($units as $unit): ?>
                                                <option value="<?php echo (int)$unit['id']; ?>"
                                                    <?php if ($item['unit_id'] == $unit['id']) echo 'selected'; ?>>
                                                    <?php echo remove_junk($unit['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Base Unit</label>
                                        <select class="form-select-custom p-2 w-100" name="base_unit_id" required>
                                            <?php foreach ($base_units as $bunit): ?>
                                                <option value="<?php echo (int)$bunit['id']; ?>"
                                                    <?php if ($item['base_unit_id'] == $bunit['id']) echo 'selected'; ?>>
                                                    <?php echo remove_junk($bunit['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Select <strong>Not Applicable</strong> if no conversion is needed.</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Conversion Rate</label>
                                        <input type="number" step="0.0001" min="0" class="form-control-custom w-100"
                                            name="conversion_rate" placeholder="e.g., 12 (items per box)">
                                        <small class="text-muted">Enter how many base units are in one unit (leave 1 if not applicable).</small>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Quantity</label>

                                        <?php
                                        $status_class = 'status-good';
                                        if ($item['quantity'] == 0) $status_class = 'status-low';
                                        elseif ($item['quantity'] <= 10) $status_class = 'status-warning';
                                        ?>
                                        <span class="quantity-status <?php echo $status_class; ?>">
                                            <?php
                                            if ($item['quantity'] == 0) echo 'Out of Stock';
                                            elseif ($item['quantity'] <= 10) echo 'Low Stock';
                                            else echo 'In Stock';
                                            ?>
                                        </span>

                                        <div class="input-group">
                                            <input type="number" class="form-control-custom w-100" name="quantity"
                                                value="<?php echo remove_junk($item['quantity']); ?>"
                                                min="0" required>

                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-custom">Unit Cost</label>
                                        <div class="input-group input-group-custom">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" name="unit_cost" class="form-control-custom"
                                                value="<?php echo remove_junk($item['unit_cost']); ?>"
                                                placeholder="0.00" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-2">
                                        <button type="submit" name="update_item" class="btn btn-success-custom w-100 btn-custom">
                                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                                        </button>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <a href="items.php" class="btn btn-secondary-custom w-100 btn-custom">
                                            <i class="fa-solid fa-arrow-left me-2"></i> Cancel
                                        </a>
                                    </div>
                                </div>
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
        // Quantity validation
        const quantityInput = document.querySelector('input[name="quantity"]');
        const unitCostInput = document.querySelector('input[name="unit_cost"]');

        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        }

        if (unitCostInput) {
            unitCostInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        }
    });
</script>

<?php include_once('layouts/footer.php'); ?>