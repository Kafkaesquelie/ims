<?php
$page_title = 'Edit Property';
require_once('includes/load.php');
page_require_level(1);

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");

// Get property ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $session->msg("d", "No property ID provided.");
    redirect('ppe.php', false);
}

$property_id = (int)$_GET['id'];
$property = find_by_id('properties', $property_id);
if (!$property) {
    $session->msg("d", "Property not found.");
    redirect('ppe.php', false);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_property'])) {
    $req_fields = array('fund_cluster', 'property_no', 'subcategory_id', 'article', 'description', 'unit', 'unit_cost', 'qty');
    validate_fields($req_fields);

    // Check for errors
    if (isset($errors) && !empty($errors)) {
        $session->msg("d", $errors);
        redirect('edit_ppe.php?id=' . $property_id, false);
    }

    if (empty($errors)) {
        $fund_cluster   = remove_junk($db->escape($_POST['fund_cluster']));
        $property_no    = remove_junk($db->escape($_POST['property_no']));
        $subcategory_id = (int)$_POST['subcategory_id'];
        $article        = remove_junk($db->escape($_POST['article']));
        $description    = remove_junk($db->escape($_POST['description']));
        $unit           = remove_junk($db->escape($_POST['unit']));
        $unit_cost      = floatval($_POST['unit_cost']);
        $qty            = (int)$_POST['qty'];
        $date_acquired  = !empty($_POST['date_acquired']) ? $db->escape($_POST['date_acquired']) : NULL;
        $remarks        = remove_junk($db->escape($_POST['remarks']));

        // Add current timestamp for last_edited
        $current_time = date('Y-m-d H:i:s');
        
        $query = "UPDATE properties SET 
                    fund_cluster='{$fund_cluster}', 
                    property_no='{$property_no}', 
                    subcategory_id='{$subcategory_id}', 
                    article='{$article}', 
                    description='{$description}', 
                    unit='{$unit}', 
                    unit_cost='{$unit_cost}', 
                    qty='{$qty}', 
                    date_acquired='{$date_acquired}', 
                    remarks='{$remarks}',
                    date_updated='{$current_time}'
                  WHERE id='{$property_id}'";

        if ($db->query($query)) {
            $session->msg("s", "Property updated successfully.");
            redirect('ppe.php', false);
        } else {
            $session->msg("d", "Sorry, failed to update property: " . $db->get_last_error());
            redirect('edit_ppe.php?id=' . $property_id, false);
        }
    }
}

// Get subcategories for dropdown
$all_subcategories = find_all('subcategories');

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

    .card-header-custom {
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
        border-bottom: 2px solid #e8f5e9;
        padding: 1.5rem;
        border-radius: 15px 15px 0 0 !important;
    }

    .card-header-custom h5 {
        margin: 0;
        font-weight: 700;
        color: var(--dark-green);
        font-size: 1.3rem;
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

    textarea.form-control-custom {
        min-height: 100px;
        resize: vertical;
    }

    /* Buttons */
    .btn-custom {
        border-radius: 10px;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        border: none;
        min-width: 160px;
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

        .edit-header h4 {
            font-size: 1.5rem;
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        .btn-custom {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            min-width: 140px;
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

    /* Value Calculation */
    .value-calculation {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .value-calculation .value-item {
        display: flex;
        justify-content: between;
        margin-bottom: 0.5rem;
    }

    .value-calculation .value-item:last-child {
        margin-bottom: 0;
    }
</style>

<div class="container mt-4">
    <!-- Header Section -->
    <div class="edit-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-building me-2"></i> Edit Property</h4>
                <div class="subtitle mt-2">Update property, plant, and equipment details</div>
            </div>
            <div class="text-end">
                <span class="info-badge">
                    <i class="fas fa-hashtag me-1"></i>ID: <?php echo str_pad($property['id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="edit-card">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-edit me-2 p-2"></i> Property Information</h5>
                </div>
                <div class="card-body-custom">
                    <!-- Display messages -->
                    <?php echo display_msg($msg); ?>
                    
                    <!-- Metadata -->
                    <div class="metadata-container">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="metadata-item">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span><strong>Date Added:</strong> <?php echo !empty($property['date_added']) ? date('M d, Y h:i A', strtotime($property['date_added'])) : '—'; ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metadata-item">
                                    <i class="fas fa-edit"></i>
                                    <span><strong>Last Edited:</strong> <?php echo !empty($property['last_edited']) ? date('M d, Y h:i A', strtotime($property['last_edited'])) : '—'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="post" action="edit_ppe.php?id=<?php echo $property_id; ?>">
                        <!-- Section 1: Basic Information -->
                        <h6 class="section-header">
                            <i class="fas fa-info-circle me-2 p-2"></i> Basic Information
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Fund Cluster</label>
                                <select class="form-select-custom" id="fund_cluster" name="fund_cluster" required>
                                    <option value="">Select Fund Cluster</option>
                                    <?php foreach ($fund_clusters as $cluster): ?>
                                        <option value="<?php echo remove_junk($cluster['name']); ?>"
                                            <?php if ($property['fund_cluster'] == $cluster['name']) echo 'selected'; ?>>
                                            <?php echo remove_junk($cluster['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Property Number</label>
                                <input type="text" name="property_no" class="form-control-custom"
                                    value="<?php echo remove_junk($property['property_no']); ?>"
                                    placeholder="Enter property number" required>
                            </div>
                        </div>

                        <!-- Section 2: Classification -->
                        <h6 class="section-header">
                            <i class="fas fa-tags me-2 p-2"></i> Classification
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Subcategory</label>
                                <select name="subcategory_id" class="form-select-custom" required>
                                    <option value="">Select Subcategory</option>
                                    <?php foreach ($all_subcategories as $sub): ?>
                                        <option value="<?php echo $sub['id']; ?>"
                                            <?php if ($sub['id'] == $property['subcategory_id']) echo 'selected'; ?>>
                                            <?php echo remove_junk($sub['subcategory_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Article</label>
                                <input type="text" name="article" class="form-control-custom"
                                    value="<?php echo remove_junk($property['article']); ?>"
                                    placeholder="Enter article name" required>
                            </div>
                        </div>

                        <!-- Section 3: Description -->
                        <h6 class="section-header">
                            <i class="fas fa-align-left me-2"></i> Description
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <textarea name="description" class="form-control-custom w-100"
                                    placeholder="Enter property description" required><?php echo remove_junk($property['description']); ?></textarea>
                            </div>
                        </div>

                        <!-- Section 4: Inventory Details -->
                        <h6 class="section-header">
                            <i class="fas fa-boxes me-2 p-2"></i> Inventory Details
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-custom">Unit of Measurement</label><br>
                                <input type="text" name="unit" class="form-control-custom"
                                    value="<?php echo remove_junk($property['unit']); ?>"
                                    placeholder="e.g., pcs, unit, set" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Quantity</label>
                                <?php
                                $status_class = 'status-good';
                                if ($property['qty'] == 0) $status_class = 'status-low';
                                elseif ($property['qty'] <= 10) $status_class = 'status-warning';
                                ?>
                                <span class="quantity-status <?php echo $status_class; ?>">
                                    <?php
                                    if ($property['qty'] == 0) echo 'Out of Stock';
                                    elseif ($property['qty'] <= 10) echo 'Low Stock';
                                    else echo 'In Stock';
                                    ?>
                                </span>
                                <div class="input-group">
                                    <input type="number" name="qty" class="form-control-custom"
                                        value="<?php echo remove_junk($property['qty']); ?>"
                                        min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Unit Cost</label>
                                <div class="input-group input-group-custom">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" name="unit_cost" class="form-control-custom"
                                        value="<?php echo remove_junk($property['unit_cost']); ?>"
                                        placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <!-- Value Calculation -->
                        <div class="value-calculation">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Unit Cost</small>
                                    <div class="cost-display">₱<?php echo number_format($property['unit_cost'], 2); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Quantity</small>
                                    <div class="cost-display"><?php echo (int)$property['qty']; ?></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Total Value</small>
                                    <div class="cost-display">₱<?php echo number_format($property['unit_cost'] * $property['qty'], 2); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Additional Information -->
                        <h6 class="section-header">
                            <i class="fas fa-calendar-alt me-2 p-2"></i> Additional Information
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-custom">Date Acquired</label><br>
                                <input type="date" name="date_acquired" class="form-control-custom"
                                    value="<?php echo remove_junk($property['date_acquired']); ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label-custom">Remarks</label><br>
                                <textarea name="remarks" class="form-control-custom w-100"
                                    placeholder="Enter any remarks or notes"><?php echo remove_junk($property['remarks']); ?></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-5">
                            <div class="col-md-6 mb-2">
                                <button type="submit" name="edit_property" class="btn btn-success-custom w-100 btn-custom">
                                    <i class="fas fa-save me-2"></i> Update Property
                                </button>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="ppe.php" class="btn btn-secondary-custom w-100 btn-custom">
                                    <i class="fas fa-arrow-left me-2"></i> Cancel
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
        // Quantity and cost validation
        const quantityInput = document.querySelector('input[name="qty"]');
        const unitCostInput = document.querySelector('input[name="unit_cost"]');

        if (quantityInput) {
            quantityInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
                updateTotalValue();
            });
        }

        if (unitCostInput) {
            unitCostInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
                updateTotalValue();
            });
        }

        function updateTotalValue() {
            const quantity = parseFloat(quantityInput?.value) || 0;
            const unitCost = parseFloat(unitCostInput?.value) || 0;
            const totalValue = quantity * unitCost;

            // You could update a total value display here if needed
            console.log('Total Value:', totalValue);
        }
    });
</script>

<?php include_once('layouts/footer.php'); ?>