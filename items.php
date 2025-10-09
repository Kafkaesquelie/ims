<?php
$page_title = 'All Items';
require_once('includes/load.php');
page_require_level(1);

$units = find_by_sql("SELECT id, name, symbol FROM units ORDER BY name ASC");
$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");

// Handle form submission for adding items
if (isset($_POST['add_item'])) {
    if (empty($errors)) {
        $fund_cluster   = $db->escape($_POST['fund_cluster']);
        $stock_card   = $db->escape($_POST['stock_card']);     
        $name         = $db->escape($_POST['name']);
        $quantity     = (int) $db->escape($_POST['quantity']);
       $unit_id = (int) $db->escape($_POST['unit_id']);

        $unit_cost    = $db->escape($_POST['unit_cost']);
        $categorie_id = (int) $db->escape($_POST['categorie_id']);
        $desc         = $db->escape($_POST['description']);
        $media_id     = 0;

        // Handle image upload
        if (isset($_FILES['item_image']) && $_FILES['item_image']['name'] != "") {
            $file_name = basename($_FILES['item_image']['name']);
            $target_dir = "uploads/items/";
            $target_file = $target_dir . $file_name;
            $check = getimagesize($_FILES["item_image"]["tmp_name"]);

            if ($check !== false) {
                if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
                    $db->query("INSERT INTO media (file_name) VALUES ('{$file_name}')");
                    $media_id = $db->insert_id();
                } else {
                    $session->msg('d', 'Failed to upload image.');
                    redirect('items.php', false);
                }
            } else {
                $session->msg('d', 'File is not an image.');
                redirect('items.php', false);
            }
        } else {
            // Use no_image.png if no file uploaded
            $default_file = 'no_image.png';
            $db->query("INSERT INTO media (file_name) VALUES ('{$default_file}')");
            $media_id = $db->insert_id();
        }

        // Check for duplicate Name
        $check_name_sql = "SELECT id FROM items WHERE name = '{$name}' LIMIT 1";
        $check_name_result = $db->query($check_name_sql);

        if ($db->num_rows($check_name_result) > 0) {
            $_SESSION['form_data'] = $_POST;
            $session->msg('d', "Item Name already exists.");
            redirect('items.php', false);
        }   
        
        // Check for duplicate Stock Card
        $check_sql = "SELECT id FROM items WHERE stock_card = '{$stock_card}' LIMIT 1";
        $check_result = $db->query($check_sql);

        if ($db->num_rows($check_result) > 0) {
            $_SESSION['form_data'] = $_POST;
            $session->msg('d', "Stock Card <b>{$stock_card}</b> already exists.");
            redirect('items.php', false);
        }

        // Insert item
       $sql  = "INSERT INTO items (fund_cluster, stock_card, name, quantity, unit_id, unit_cost, categorie_id, description, media_id, date_added) VALUES ";
$sql .= "('{$fund_cluster}', '{$stock_card}', '{$name}', '{$quantity}', '{$unit_id}', '{$unit_cost}', '{$categorie_id}', '{$desc}', '{$media_id}', NOW())";


        if ($db->query($sql)) {
            unset($_SESSION['form_data']);
            $session->msg('s', "Item added successfully.");
            redirect('items.php', false);
        } else {
            $_SESSION['form_data'] = $_POST;
            $session->msg('d', 'Failed to add item!');
            redirect('items.php', false);
        }
    } else {
        $_SESSION['form_data'] = $_POST;
        $session->msg("d", $errors);
        redirect('items.php', false);
    }
}

// Handle Bulk Archive
if(isset($_POST['bulk_archive_ids']) && !empty($_POST['bulk_archive_ids'])){
    $ids = array_map('intval', $_POST['bulk_archive_ids']);
    foreach($ids as $id){
        $db->query("UPDATE items SET archived=1 WHERE id='{$id}' LIMIT 1");
    }
    $session->msg('s', 'Selected items archived successfully.');
    redirect('items.php');
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$items = get_items_paginated(10, $page, $category);

$categories = find_all('categories'); 
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>

<?php include_once('layouts/header.php');

$msg = $session->msg();
if (!empty($msg) && is_array($msg)): 
    $type = key($msg);
    $text = $msg[$type];
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      icon: '<?php echo $type === "danger" ? "error" : $type; ?>',
      title: '<?php echo ucfirst($type); ?>',
      text: '<?php echo addslashes($text); ?>',
      confirmButtonText: 'OK'
    });
  });
</script>
<?php endif; ?>

<style>
:root {
  --primary: #28a745;
  --primary-dark: #1e7e34;
  --primary-light: #34ce57;
  --secondary: #6c757d;
  --warning: #ffc107;
  --danger: #dc3545;
  --light: #f8f9fa;
  --dark: #343a40;
  --border-radius: 12px;
  --shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card-header-custom {
  background: white;
  border-top: 5px solid var(--primary);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.page-title {
  font-family: 'Times New Roman', serif;
  font-weight: 700;
  margin: 0;
  color: var(--dark);
}

.btn-primary-custom {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary-custom:hover {
  background: linear-gradient(135deg, var(--primary-dark), #155724);
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}

.btn-warning-custom {
  background: var(--warning);
  color: var(--dark);
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-warning-custom:hover {
  background: #e0a800;
  transform: translateY(-1px);
  color: var(--dark);
}

.btn-danger-custom {
  background: var(--danger);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-danger-custom:hover {
  background: #c82333;
  transform: translateY(-1px);
  color: white;
}

.table-custom {
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  margin-bottom: 0;
}

.table-custom thead {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.table-custom th {
  border: none;
  font-weight: 600;
  padding: 1rem;
  text-align: center;
  vertical-align: middle;
}

.table-custom td {
  padding: 0.75rem;
  vertical-align: middle;
  border-bottom: 1px solid #dee2e6;
}

.table-custom tbody tr {
  transition: all 0.3s ease;
}

.table-custom tbody tr:hover {
  background-color: rgba(40, 167, 69, 0.05);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table-custom tbody tr.table-danger {
  background-color: rgba(220, 53, 69, 0.1);
}

.table-custom tbody tr.table-danger:hover {
  background-color: rgba(220, 53, 69, 0.15);
}

.badge-custom {
  padding: 0.5rem 0.75rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.8rem;
}

.badge-primary {
  background: rgba(40, 167, 69, 0.15);
  color: var(--primary-dark);
}

.badge-success {
  background: rgba(40, 167, 69, 0.15);
  color: var(--primary-dark);
}

.badge-warning {
  background: rgba(255, 193, 7, 0.15);
  color: #856404;
}

.actions-column {
  width: 120px;
  text-align: center;
}

.btn-group-custom {
  display: flex;
}

.btn-group-custom .btn {
  width: 100%;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--secondary);
}

.empty-state-icon {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state h4 {
  color: var(--secondary);
  margin-bottom: 0.5rem;
}

.empty-state p {
  margin-bottom: 1.5rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 300px;
}

.search-box input {
  padding-left: 2.5rem;
  border-radius: 25px;
  border: 1px solid #dee2e6;
}

.search-box .search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--secondary);
}

.stats-card {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border-left: 4px solid var(--primary);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  border-top: 3px solid var(--primary);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 0.5rem;
}

.stat-label {
  color: var(--secondary);
  font-size: 0.9rem;
  font-weight: 500;
}

/* Add Item Form Styles */
.add-item-form {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  margin-bottom: 2rem;
  overflow: hidden;
  display: none; /* Hidden by default */
}

.add-form-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.add-form-header h5 {
  margin: 0;
  font-weight: 600;
}

.form-section {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.form-section:last-child {
  border-bottom: none;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--primary);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--primary-light);
}

/* Checkbox styling */
.item-checkbox, #selectAll {
  width: 18px;
  height: 18px;
  accent-color: var(--primary);
  cursor: pointer;
}

.bulk-actions-container {
  background: rgba(40, 167, 69, 0.1);
  border: 1px solid var(--primary-light);
  border-radius: var(--border-radius);
  padding: 1rem;
  margin-bottom: 1rem;
  display: none;
}

.bulk-actions-container.show {
  display: flex;
  align-items: center;
  gap: 1rem;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.bulk-selection-count {
  font-weight: 600;
  color: var(--primary-dark);
}

.item-image {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  object-fit: cover;
  border: 2px solid #e9ecef;
  transition: all 0.3s ease;
}

.item-image:hover {
  border-color: var(--primary);
  transform: scale(1.05);
}

.low-stock-badge {
  background: var(--danger);
  color: white;
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  margin-left: 0.5rem;
}

/* Animation for form show/hide */
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .card-header-custom {
    padding: 1rem;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .search-box {
    max-width: 100%;
  }
  

  
  .table-responsive {
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .bulk-actions-container {
    flex-direction: column;
    text-align: center;
  }
  
  .add-form-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background: var(--primary-light) !important;
  color: white !important;
  border: none !important;
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
  margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_filter input {
  border-radius: 6px;
  border: 1px solid #dee2e6;
  padding: 0.375rem 0.75rem;
}

.dataTables_wrapper .dataTables_length select {
  border-radius: 6px;
  border: 1px solid #dee2e6;
}
</style>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="card-header-custom">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div>
        <h4 class="page-title">Inventory Items Management</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-custom">
            <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Inventory Items</li>
          </ol>
        </nav>
      </div>
      <div class="header-actions">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="form-control" placeholder="Search items..." id="searchInput">
        </div>
        <button type="button" id="showAddFormBtn" class="btn btn-primary-custom">
          <i class="fas fa-plus me-2"></i> Add New Item
        </button>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-item">
      <div class="stat-value"><?php echo count($items); ?></div>
      <div class="stat-label">Total Items</div>
    </div>
    <div class="stat-item">
      <div class="stat-value">₱<?php 
        $total_value = 0;
        foreach ($items as $item) {
          $total_value += ($item['unit_cost'] * $item['quantity']);
        }
        echo number_format($total_value, 2);
      ?></div>
      <div class="stat-label">Total Inventory Value</div>
    </div>
    <div class="stat-item">
      <div class="stat-value"><?php 
        $low_stock_count = 0;
        foreach ($items as $item) {
          if ((int)$item['quantity'] < 10) {
            $low_stock_count++;
          }
        }
        echo $low_stock_count;
      ?></div>
      <div class="stat-label">Low Stock Items</div>
    </div>
    <div class="stat-item">
      <div class="stat-value"><?php 
        $categories_count = array_unique(array_column($items, 'category'));
        echo count($categories_count);
      ?></div>
      <div class="stat-label">Active Categories</div>
    </div>
  </div>

  
  <!-- Add Item Form (Hidden by default) -->
  <div class="add-item-form" id="addItemForm">
    <div class="add-form-header">
      <h5><i class="fas fa-plus-circle me-2"></i>Add New Inventory Item</h5>
      <button type="button" id="cancelAddBtn" class="btn btn-light btn-sm">
        <i class="fas fa-times me-1"></i> Cancel
      </button>
    </div>
    
    <form method="POST" action="items.php" enctype="multipart/form-data" class="needs-validation" novalidate>
      <div class="form-section">
        <h6 class="section-title">Basic Information</h6>
        <div class="row">
          <div class="col-md-2 mb-3">
            <label for="fund_cluster" class="form-label fw-bold">Fund Cluster <span class="text-danger">*</span></label>
            <input type="text" name="fund_cluster" id="fund_cluster" class="form-control" 
              value="<?php echo isset($form_data['fund_cluster']) ? $form_data['fund_cluster'] : ''; ?>" required>
            <div class="invalid-feedback">
              Please provide a fund cluster.
            </div>
          </div>
          
          <div class="col-md-4 mb-3">
            <label for="name" class="form-label fw-bold">Item Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" 
              value="<?php echo isset($form_data['name']) ? $form_data['name'] : ''; ?>" required>
            <div class="invalid-feedback">
              Please provide an item name.
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <label for="stock_card" class="form-label fw-bold">Stock No. <span class="text-danger">*</span></label>
            <input type="text" name="stock_card" id="stock_card" class="form-control" 
              value="<?php echo isset($form_data['stock_card']) ? $form_data['stock_card'] : ''; ?>" placeholder="e.g. 010" required>
            <div class="invalid-feedback">
              Please provide a stock number.
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <label for="categorie_id" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
            <select name="categorie_id" id="categorie_id" class="form-control" required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>" data-name="<?php echo remove_junk($cat['name']); ?>"
                  <?php echo (isset($form_data['categorie_id']) && $form_data['categorie_id'] == $cat['id']) ? 'selected' : ''; ?>>
                  <?php echo remove_junk($cat['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
              Please select a category.
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
  <label for="unit_id" class="form-label fw-bold">Unit of Measure <span class="text-danger">*</span></label>
  <select name="unit_id" id="unit_id" class="form-select w-100 p-2" required>
    <option value="">Select Unit</option>
    <?php foreach ($units as $unit): ?>
      <option value="<?php echo (int)$unit['id']; ?>"
        <?php echo (isset($form_data['unit_id']) && $form_data['unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
        <?php echo remove_junk($unit['name']); ?>
        <?php echo $unit['symbol'] ? " ({$unit['symbol']})" : ''; ?>
      </option>
    <?php endforeach; ?>
  </select>
  <div class="invalid-feedback">
    Please select a unit of measure.
  </div>
</div>

          
          <div class="col-md-3 mb-3">
            <label for="quantity" class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
            <input type="number" name="quantity" id="quantity" class="form-control" 
              value="<?php echo isset($form_data['quantity']) ? $form_data['quantity'] : ''; ?>" min="0" required>
            <div class="invalid-feedback">
              Please provide a valid quantity.
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <label for="unit_cost" class="form-label fw-bold">Unit Cost <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" step="0.01" name="unit_cost" id="unit_cost" class="form-control"  
                value="<?php echo isset($form_data['unit_cost']) ? $form_data['unit_cost'] : ''; ?>" min="0" required>
            </div>
            <div class="invalid-feedback">
              Please provide a valid unit cost.
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h6 class="section-title">Additional Information</h6>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label for="description" class="form-label fw-bold">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"><?php 
              echo isset($form_data['description']) ? $form_data['description'] : ''; ?></textarea>
          </div>
          
          <div class="col-md-4 mb-3">
            <label for="item_image" class="form-label fw-bold">Item Image</label>
            <input type="file" name="item_image" id="item_image" class="form-control">
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="d-flex justify-content-between">
          <button type="button" id="cancelFormBtn" class="btn btn-secondary">
            <i class="fas fa-times me-1"></i> Cancel
          </button>
          <button type="submit" name="add_item" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save Item
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Bulk Actions Container -->
  <div class="bulk-actions-container" id="bulkActions">
    <div class="bulk-selection-count" id="selectedCount">0 items selected</div>
    <div class="d-flex gap-2">
      <button id="bulkEdit" class="btn btn-warning-custom" title="Edit Selected">
        <i class="fas fa-edit me-1"></i> Edit
      </button>
      <button id="bulkArchive" class="btn btn-danger-custom" title="Archive Selected">
                                 <i class="fa-solid fa-file-zipper"></i> Archive
      </button>
      <button id="clearSelection" class="btn btn-secondary" title="Clear Selection">
        <i class="fas fa-times me-1"></i> Clear
      </button>
    </div>
  </div>

  

  <!-- Items Table -->
  <div class="card-header-custom" id="itemsTableSection">
    <?php if($items && count($items) > 0): ?>
      <div class="table-responsive">
        <table class="table table-custom" id="itemsTable">
          <thead>
            <tr>
              <th class="text-center" width="50">
                <input type="checkbox" id="selectAll">
              </th>
              <th width="250">Item Description</th>
              <th class="text-center" width="120">Fund Cluster</th>
              <th class="text-center" width="120">Stock No.</th>
              <th class="text-center" width="80">Photo</th>
              <th class="text-center" width="150">Category</th>
              <th class="text-center" width="120">Unit Cost</th>        
              <th class="text-center" width="100">In-Stock</th>           
              <th class="text-center actions-column" width="120">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($items as $item): 
              $lowStock = ((int)$item['quantity'] < 10);
              $rowClass = $lowStock ? 'table-danger' : '';
            ?>
              <tr class="<?= $rowClass; ?>" data-category="<?php echo remove_junk($item['category']); ?>">
                <td class="text-center">
                  <input type="checkbox" class="item-checkbox" data-id="<?= (int)$item['id']; ?>">
                </td>
                <td>
                  <div class="d-flex align-items-start">
                    <div>
                      <strong><?= remove_junk($item['name']); ?></strong>
                      <div class="text-muted small mt-1">
                        <i class="fas fa-ruler me-1"></i>UOM: <?= remove_junk($item['unit_id']); ?>
                        <?php if($lowStock): ?>
                          <span class="low-stock-badge">Low Stock</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="text-center">
                  <?php 
                    $fund_cluster = strtoupper($item['fund_cluster']); 
                    $badgeClass = ($fund_cluster === 'GAA') ? 'badge-success' : 'badge-primary'; 
                  ?>
                  <span class="badge badge-custom <?= $badgeClass ?>">
                    <?= htmlspecialchars($fund_cluster) ?>
                  </span>
                </td>
                <td class="text-center">
                  <code><?= remove_junk($item['stock_card']); ?></code>
                </td>
                <td class="text-center">
                  <img class="item-image"
                    src="uploads/items/<?= !empty($item['image']) ? $item['image'] : 'no_image.png'; ?>"
                    alt="<?= remove_junk($item['name']); ?>">
                </td>
                <td class="text-center">
                  <span class="badge badge-custom badge-primary">
                    <?= remove_junk($item['category']); ?>
                  </span>
                </td>
                <td class="text-center">
                  <strong class="text-success">₱<?= number_format($item['unit_cost'], 2); ?></strong>
                </td>
                <td class="text-center">
                  <div class="d-flex flex-column align-items-center">
                    <span class="badge badge-custom <?= $lowStock ? 'badge-warning' : 'badge-primary'; ?> fs-6">
                      <?= remove_junk($item['quantity']); ?>
                    </span>
                    <?php if($lowStock): ?>
                      <small class="text-danger mt-1">Reorder needed</small>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-custom">
                    <a href="edit_item.php?id=<?= (int)$item['id'];?>" class="btn btn-warning-custom" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="a_script.php?id=<?= (int)$item['id']; ?>" class="btn btn-danger-custom archive-btn" title="Archive" data-id="<?= (int)$item['id']; ?>">
                                               <i class="fa-solid fa-file-zipper"></i> 

                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <!-- Empty State -->
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="fas fa-box-open"></i>
        </div>
        <h4>No Inventory Items Found</h4>
        <p>Get started by adding your first inventory item.</p>
        <button type="button" id="showAddFormEmptyBtn" class="btn btn-primary-custom">
          <i class="fas fa-plus me-2"></i> Add First Item
        </button>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    var table = $('#itemsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    }); // 🔍 Custom search box function
    

    // Show add item form
    function showAddForm() {
        $('#addItemForm').fadeIn(300).addClass('fade-in');
        $('#itemsTableSection').hide();
        $('html, body').animate({
            scrollTop: $('#addItemForm').offset().top - 20
        }, 300);
    }

    // Hide add item form
    function hideAddForm() {
        $('#addItemForm').fadeOut(300);
        $('#itemsTableSection').fadeIn(300);
        $('html, body').animate({
            scrollTop: $('#itemsTableSection').offset().top - 20
        }, 300);
    }

    // Event listeners for showing form
    $('#showAddFormBtn, #showAddFormEmptyBtn').on('click', showAddForm);

    // Event listeners for hiding form
    $('#cancelAddBtn, #cancelFormBtn').on('click', hideAddForm);

    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Bulk selection functionality
    function updateBulkActions() {
        const selectedCount = $('.item-checkbox:checked').length;
        const bulkActions = $('#bulkActions');
        const selectedCountElement = $('#selectedCount');
        
        selectedCountElement.text(selectedCount + ' item' + (selectedCount !== 1 ? 's' : '') + ' selected');
        
        if (selectedCount > 0) {
            bulkActions.addClass('show');
        } else {
            bulkActions.removeClass('show');
        }
    }

    // Select All functionality
    $('#selectAll').on('change', function() {
        const checked = $(this).is(':checked');
        table.rows().nodes().to$().find('.item-checkbox').prop('checked', checked);
        updateBulkActions();
    });

    // Individual checkbox change
    $('#itemsTable tbody').on('change', '.item-checkbox', updateBulkActions);

    // Clear selection
    $('#clearSelection').on('click', function() {
        table.rows().nodes().to$().find('.item-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBulkActions();
    });

    // Bulk Edit
    $('#bulkEdit').on('click', function() {
        const ids = table.$('.item-checkbox:checked').map(function(){
            return $(this).data('id');
        }).get();

        if(ids.length > 0){
            window.location.href = "bulk_edit_items.php?ids=" + ids.join(',');
        } else {
            Swal.fire('No items selected', 'Please select items to edit.', 'info');
        }
    });

    // Bulk Archive
    $('#bulkArchive').on('click', function() {
        const ids = table.$('.item-checkbox:checked').map(function(){
            return $(this).data('id');
        }).get();

        if(ids.length === 0){
            Swal.fire('No items selected', 'Please select items to archive.', 'info');
            return;
        }

        Swal.fire({
            title: 'Archive Items?',
            text: `You are about to archive ${ids.length} item(s). This action can be undone later.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if(result.isConfirmed){
                const form = $('<form method="POST" action="items.php"></form>');
                ids.forEach(id => {
                    form.append(`<input type="hidden" name="bulk_archive_ids[]" value="${id}">`);
                });
                $('body').append(form);
                form.submit();
            }
        });
    });

    // Individual archive confirmation
    document.querySelectorAll('.archive-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const id = this.getAttribute('data-id');

            Swal.fire({
                title: 'Archive Item?',
                text: "This item will be moved to archives. You can restore it later if needed.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    // Add row hover effects
    const tableRows = document.querySelectorAll('#itemsTable tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-group-custom') && !e.target.closest('.item-checkbox')) {
                const checkbox = this.querySelector('.item-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateBulkActions();
                }
            }
        });
    });
});
</script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const table = document.getElementById("itemsTable");
    const rows = table.getElementsByTagName("tr");

    searchInput.addEventListener("keyup", function () {
      const filter = this.value.toLowerCase();

      // Loop through table rows (skip header)
      for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName("td");
        let match = false;

        // Check every cell for a match
        for (let j = 0; j < cells.length; j++) {
          const cellText = cells[j].textContent || cells[j].innerText;
          if (cellText.toLowerCase().indexOf(filter) > -1) {
            match = true;
            break;
          }
        }

        // Show or hide the row based on match
        rows[i].style.display = match ? "" : "none";
      }
    });
  });
</script>