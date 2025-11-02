<?php
$page_title = 'Semi-Expendable Property';
require_once('includes/load.php');
page_require_level(1); // Only admins

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");

// Handle Add Item form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $fund_cluster     = remove_junk($db->escape($_POST['fund_cluster']));
    $inv_item_no      = remove_junk($db->escape($_POST['inv_item_no']));
    $item              = remove_junk($db->escape($_POST['item']));
    $item_description = remove_junk($db->escape($_POST['item_description']));
    $unit             = remove_junk($db->escape($_POST['unit']));
    $unit_cost        = (float)$_POST['unit_cost'];
    $semicategory_id  = (int) $db->escape($_POST['semicategory_id']);
    $total_qty        = (int) $db->escape($_POST['qty']);
    $qty_left         = $total_qty; // Initially, qty_left = total_qty
    $estimated_use    = remove_junk($db->escape($_POST['estimated_use']));

     if ($unit_cost > 50000) {
    $session->msg('d', '❌ Unit cost cannot exceed ₱50,000.');
    redirect('smp.php', false);
}
    // Check duplicates for the filled field
    if (!empty($inv_item_no)) {
        $check = $db->query("SELECT id FROM semi_exp_prop WHERE inv_item_no = '{$inv_item_no}' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $session->msg('d', '❌ Duplicate detected: Inventory Item No. already exists.');
            redirect('smp.php', false);
        }
    } 
    $semicategory_id = (int)$_POST['semicategory_id'];

   

// Insert query
$query = "INSERT INTO semi_exp_prop 
          (fund_cluster, inv_item_no,item, item_description, unit, semicategory_id,unit_cost, total_qty, qty_left, estimated_use, date_added)
          VALUES 
          ('{$fund_cluster}', {$inv_item_no},'{$item}', '{$item_description}', '{$unit}', '{$semicategory_id}','{$unit_cost}', '{$total_qty}', '{$qty_left}', '{$estimated_use}', NOW())";


    if ($db->query($query)) {
        $session->msg('s', '✅ Semi-expendable property added successfully!');
        redirect('smp.php', false);
    } else {
        $session->msg('d', '❌ Failed to add item: ' . $db->con->error);
        redirect('smp.php', false);
    }
}



// Search logic
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = trim($db->escape($_GET['search']));
    // run LIKE query
} else {
    $search = '';
    $semi_props = find_all('semi_exp_prop');
}


if (!empty($search)) {
    $sql = "SELECT s.*, sc.semicategory_name 
            FROM semi_exp_prop s
            LEFT JOIN semicategories sc ON s.semicategory_id = sc.id
            WHERE s.fund_cluster LIKE '%{$search}%'
               OR s.inv_item_no LIKE '%{$search}%'
               OR s.item LIKE '%{$search}%'
               OR s.item_description LIKE '%{$search}%'
               OR s.unit LIKE '%{$search}%'
               OR s.unit_cost LIKE '%{$search}%'
               OR s.total_qty LIKE '%{$search}%'
               OR s.estimated_use LIKE '%{$search}%'
               OR s.date_added LIKE '%{$search}%'
               OR s.last_edited LIKE '%{$search}%'
               OR s.status LIKE '%{$search}%'
               OR sc.semicategory_name LIKE '%{$search}%'";
    $semi_props = find_by_sql($sql);
} else {
    $sql = "SELECT s.*, sc.semicategory_name 
            FROM semi_exp_prop s
            LEFT JOIN semicategories sc ON s.semicategory_id = sc.id
            ORDER BY s.date_added DESC";
    $semi_props = find_by_sql($sql);
}



// Automatically update status based on qty_left
$db->query("UPDATE semi_exp_prop 
            SET status = CASE 
                             WHEN qty_left > 0 THEN 'available' 
                             ELSE 'issued' 
                         END");

function highlight($text, $search) {
    if (!$search) return $text;
    return preg_replace("/(" . preg_quote($search, '/') . ")/i", "<mark>$1</mark>", $text);
}

$subcategories = $db->query("SELECT * FROM semicategories ORDER BY semicategory_name ASC");

?>

<?php include_once('layouts/header.php'); 
$msg = $session->msg(); // get the flashed message

if (!empty($msg) && is_array($msg)): 
    $type = key($msg);        // "danger", "success", etc.
    $text = $msg[$type];      // The message itself
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

/* Checkbox styling */
.checkbox, #checkAll {
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

.form-section {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-control:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.form-hint {
  font-size: 0.8rem;
  color: var(--secondary);
  margin-top: 0.25rem;
}

/* IMPROVED FORM BUTTONS - WIDER AND CENTERED */
.form-buttons-container {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  margin-top: 2rem;
  width: 100%;
  padding-top: 1.5rem;
  border-top: 2px solid #e9ecef;
}

.form-btn {
  min-width: 180px;
  padding: 0.85rem 2rem;
  font-size: 1.1rem;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.form-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn-cancel {
  background: #6c757d;
  color: white;
  border: none;
}

.btn-cancel:hover {
  background: #5a6268;
  color: white;
}

.btn-save {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
}

.btn-save:hover {
  background: linear-gradient(135deg, var(--primary-dark), #155724);
  color: white;
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

  /* Responsive form buttons */
  .form-buttons-container {
    flex-direction: column;
    gap: 1rem;
  }

  .form-btn {
    min-width: 100%;
    width: 100%;
  }
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

/* Add form specific styles */
.add-form-container {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 0;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.add-form-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 1.5rem;
  display: flex;
  justify-content: between;
  align-items: center;
}

.add-form-header h5 {
  margin: 0;
  color: white;
}

.add-form-body {
  padding: 1.5rem;
}

.form-row-custom {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group-custom {
  margin-bottom: 0;
}
</style>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="card-header-custom">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div>
        <h4 class="page-title">Semi-Expendable Properties Management</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-custom">
            <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Semi-Expendable Properties</li>
          </ol>
        </nav>
      </div>
      <div class="header-actions">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="form-control" placeholder="Search semi-properties..." id="searchInput" 
                 value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </div>
        <button id="addItemBtn" class="btn btn-primary-custom">
          <i class="fas fa-plus me-2"></i> Add Semi-Exp-Property
        </button>
      </div>
    </div>
  </div>

 

  <!-- Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-item">
      <div class="stat-value"><?php echo count($semi_props); ?></div>
      <div class="stat-label">Total Properties</div>
    </div>
    <div class="stat-item">
      <div class="stat-value">₱<?php 
        $total_value = 0;
        foreach ($semi_props as $item) {
          $total_value += ($item['unit_cost'] * $item['total_qty']);
        }
        echo number_format($total_value, 2);
      ?></div>
      <div class="stat-label">Total Inventory Value</div>
    </div>
    <div class="stat-item">
      <div class="stat-value"><?php 
        $available_count = 0;
        foreach ($semi_props as $item) {
          if ($item['status'] === 'available') {
            $available_count++;
          }
        }
        echo $available_count;
      ?></div>
      <div class="stat-label">Available Properties</div>
    </div>
    <div class="stat-item">
      <div class="stat-value"><?php 
        $categories_count = array_unique(array_column($semi_props, 'semicategory_name'));
        echo count($categories_count);
      ?></div>
      <div class="stat-label">Active Categories</div>
    </div>
  </div>

   <!-- Bulk Actions Container -->
  <div class="bulk-actions-container" id="bulkActions">
    <div class="bulk-selection-count" id="selectedCount">0 items selected</div>
    <div class="d-flex gap-2">
      <button id="bulkEdit" class="btn btn-warning-custom ml-3" title="Edit Selected">
        <i class="fas fa-edit me-1"></i> Edit
      </button>
      <button id="bulkArchive" class="btn btn-danger-custom ml-3" title="Archive Selected">
                                 <i class="fa-solid fa-file-zipper"></i>  Archive
      </button>
      <button id="clearSelection" class="btn btn-secondary ml-3" title="Clear Selection">
        <i class="fas fa-times me-1"></i> Clear
      </button>
    </div>
  </div>
  <!-- Add Item Form (Hidden by Default) -->
  <div class="add-form-container" id="addItemCard" style="display: none;">
        <div class="add-form-header d-flex justify-content-between align-items-center mb-4 p-3 bg-primary bg-opacity-10 rounded">
      <h5 class="mb-0 text-light"><i class="fas fa-plus-circle me-2"></i>Add New Semi-Expendable Property</h5>
    
    </div>
    <div class="add-form-body">
      <form method="POST" action="">
        <input type="hidden" name="add_item" value="1">
        
        <!-- Form Rows -->
        <div class="form-row-custom">
           <div class="form-custom">
              <label for="fund_cluster" class="form-label fw-bold">
                Fund Cluster <span class="text-danger">*</span>
              </label>
              <select class="form-select w-100 p-2" id="fund_cluster" name="fund_cluster" required>
                <option value="" selected disabled>Select Fund Cluster</option>
                <?php foreach ($fund_clusters as $fc): ?>
                  <option value="<?php echo $fc['id']; ?>">
                    <?php echo remove_junk(ucwords($fc['name'])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">
                Please select a fund cluster.
              </div>
            </div>
          <div class="form-group-custom">
            <label class="form-label">Inventory Item No.</label>
            <input type="text" class="form-control" name="inv_item_no" placeholder="Inventory Item No.">
          </div>
          <div class="form-group-custom">
            <label class="form-label">Category</label>
            <select class="form-control" name="semicategory_id" required>
              <option value="">Select Category</option>
              <?php if ($subcategories && $subcategories->num_rows > 0): ?>
                <?php while ($sub = $subcategories->fetch_assoc()): ?>
                  <option value="<?= $sub['id']; ?>">
                    <?= remove_junk($sub['semicategory_name']); ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <div class="form-row-custom">
           <div class="form-group-custom">
            <label class="form-label">Item</label>
            <input type="text" class="form-control" name="item" placeholder="Item Name" required>
          </div>
          <div class="form-group-custom">
            <label class="form-label">Item Description</label>
            <input type="text" class="form-control" name="item_description" placeholder="Item Description" required>
          </div>
          <div class="form-group-custom">
            <label class="form-label">Unit</label>
            <input type="text" class="form-control" name="unit" placeholder="Unit" required>
          </div>
          <div class="form-group-custom">
            <label class="form-label">Quantity</label>
            <input type="number" class="form-control" name="qty" placeholder="Quantity" required>
          </div>
        </div>

        <div class="form-row-custom">
          <div class="form-group-custom">
            <label class="form-label">Unit Cost</label>
            <input type="number" step="0.01" class="form-control" name="unit_cost" placeholder="Unit Cost" required>
            <div class="form-hint">
              <i class="fas fa-info-circle me-1"></i>Should not exceed ₱50,000.00
            </div>
          </div>
          <div class="form-group-custom">
            <label class="form-label">Estimated Use</label>
            <input type="text" class="form-control" name="estimated_use" placeholder="Estimated Use">
          </div>
        </div>

        <!-- IMPROVED BUTTONS SECTION - WIDER AND CENTERED ON NEW ROW -->
        <div class="form-buttons-container">
          <button type="button" id="cancelAddBtn" class="btn btn-cancel form-btn">
            <i class="fas fa-times me-2"></i> Close
          </button>
          <button type="submit" class="btn btn-save form-btn">
            <i class="fas fa-save me-2"></i> Save Property
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card-header-custom" id="tableCard">
    <div class="table-responsive">
      <table class="table table-custom" id="smpTable">
        <thead>
          <tr>
            <th class="text-center" width="50">
              <input type="checkbox" id="checkAll">
            </th>
            <th>Item</th>
            <th>Description</th>
            <th class="text-center">Fund Cluster</th>
            <th class="text-center">Inventory Item No.</th>
            <th class="text-center">Unit Cost</th>
            <th class="text-center">Quantity</th>
            <th class="text-center">UOM</th>
            <th class="text-center">Status</th>
            <th class="text-center actions-column">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($semi_props)): ?>
            <?php foreach($semi_props as $item): ?>
              <tr>
                <td class="text-center">
                  <input class="checkbox" type="checkbox" name="selected_ids[]" value="<?= $item['id'] ?>">
                </td>
                <td>
                    <strong><?= highlight($item['item'], $search) ?></strong>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <strong><?= highlight($item['item_description'], $search) ?></strong>
                    <small class="text-muted mt-1">
                      <i class="fas fa-tag me-1"></i><?= $item['semicategory_name'] ?>
                    </small>
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
                  <code><?= !empty($item['inv_item_no']) ? $item['inv_item_no'] : '-' ?></code>
                </td>
                <td class="text-center">
                  <strong class="text-success">₱<?= number_format($item['unit_cost'], 2) ?></strong>
                </td>
                <td class="text-center">
                  <div class="d-flex flex-column align-items-center">
                    <span class="badge badge-custom badge-primary fs-6">
                      <?= (int)$item['qty_left']; ?>
                    </span>
                    <small class="text-muted mt-1">Total: <?= (int)$item['total_qty']; ?></small>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge badge-custom badge-primary"><?= $item['unit'] ?></span>
                </td>
                <td class="text-center">
                  <?php if ($item['status'] === 'available'): ?>
                    <span class="badge badge-custom badge-success">
                      <i class="fas fa-box me-1"></i> Available
                    </span>
                  <?php elseif ($item['status'] === 'issued'): ?>
                    <span class="badge badge-custom badge-primary">
                      <i class="fas fa-box-open me-1"></i> Issued
                    </span>
                  <?php elseif ($item['status'] === 'returned'): ?>
                    <span class="badge badge-custom badge-info">
                      <i class="fas fa-undo me-1"></i> Returned
                    </span>
                  <?php elseif ($item['status'] === 'lost'): ?>
                    <span class="badge badge-custom badge-danger">
                      <i class="fas fa-times-circle me-1"></i> Lost
                    </span>
                  <?php elseif ($item['status'] === 'disposed'): ?>
                    <span class="badge badge-custom badge-warning">
                      <i class="fas fa-trash-alt me-1"></i> Disposed
                    </span>
                  <?php elseif ($item['status'] === 'archived'): ?>
                    <span class="badge badge-custom badge-secondary">
                      <i class="fas fa-archive me-1"></i> Archived
                    </span>
                  <?php else: ?>
                    <span class="badge badge-custom badge-light">
                      <i class="fas fa-question-circle me-1"></i> Unknown
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-custom">
                    <a href="edit_smp.php?id=<?= $item['id'] ?>" class="btn btn-warning-custom" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="a_script.php?id=<?= $item['id'] ?>" class="btn btn-danger-custom archive-btn" title="Archive" data-id="<?= $item['id'] ?>">
                                               <i class="fa-solid fa-file-zipper"></i> 

                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9">
                <div class="empty-state">
                  <div class="empty-state-icon">
                    <i class="fas fa-tools"></i>
                  </div>
                  <h4>No Semi-Expendable Properties Found</h4>
                  <p>Get started by adding your first semi-expendable property.</p>
                  <button id="addFirstItemBtn" class="btn btn-primary-custom">
                    <i class="fas fa-plus me-2"></i> Add First Property
                  </button>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<?php include_once('layouts/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

   $(document).ready(function(){
    var table = $('#smpTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
    });
     // 🔍 Custom search box function
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    });
  // Bulk selection functionality
  function updateBulkActions() {
    const selectedCount = $('.checkbox:checked').length;
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
  $('#checkAll').on('change', function() {
    const checked = $(this).is(':checked');
    table.rows().nodes().to$().find('.checkbox').prop('checked', checked);
    updateBulkActions();
  });

  // Individual checkbox change
  $('#smpTable tbody').on('change', '.checkbox', updateBulkActions);

  // Clear selection
  $('#clearSelection').on('click', function() {
    table.rows().nodes().to$().find('.checkbox').prop('checked', false);
    $('#checkAll').prop('checked', false);
    updateBulkActions();
  });

  // Form toggle functionality
  document.getElementById('addItemBtn').addEventListener('click', function() {
    document.getElementById('tableCard').style.display = 'none';
    document.getElementById('addItemCard').style.display = 'block';
  });

  document.getElementById('cancelAddBtn').addEventListener('click', function() {
    document.getElementById('addItemCard').style.display = 'none';
    document.getElementById('tableCard').style.display = 'block';
  });

  document.getElementById('addFirstItemBtn')?.addEventListener('click', function() {
    document.getElementById('tableCard').style.display = 'none';
    document.getElementById('addItemCard').style.display = 'block';
  });

  // Archive confirmation
  document.querySelectorAll('.archive-btn').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      const id = this.getAttribute('data-id');

      Swal.fire({
        title: 'Archive Property?',
        text: "This property will be moved to archives. You can restore it later if needed.",
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

  // Bulk Edit
$('#bulkEdit').on('click', function() {
  const ids = table.$('.checkbox:checked').map(function(){
    return $(this).val();
  }).get();

  if(ids.length === 0){
    Swal.fire('No items selected', 'Please select items to edit.', 'info');
    return;
  }

  // Redirect to bulk edit page with selected IDs
  window.location.href = 'bulk_edit_smp.php?ids=' + ids.join(',');
});


  // Bulk Archive (you can implement this similarly to previous examples)
  $('#bulkArchive').on('click', function() {
    const ids = table.$('.checkbox:checked').map(function(){
      return $(this).val();
    }).get();

    if(ids.length === 0){
      Swal.fire('No items selected', 'Please select items to archive.', 'info');
      return;
    }

    Swal.fire({
      title: 'Archive Properties?',
      text: `You are about to archive ${ids.length} property(s). This action can be undone later.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, archive them!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if(result.isConfirmed){
        // Implement bulk archive logic here
        console.log('Archive IDs:', ids);
      }
    });
  });
});
    });

</script>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const table = document.getElementById("smpTable");
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