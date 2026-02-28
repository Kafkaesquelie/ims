<?php
$page_title = 'All Equipment';
require_once('includes/load.php');
page_require_level(1);

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_equipment'])) {
  $req_fields = array('fund_cluster', 'property_no', 'subcategory_id', 'article', 'description', 'unit', 'unit_cost', 'date_acquired');
  validate_fields($req_fields);

  if (empty($errors)) {
    $fund_cluster   = remove_junk($db->escape($_POST['fund_cluster']));
    $property_no    = remove_junk($db->escape($_POST['property_no']));
    $serial_no      = !empty($_POST['serial_no']) ? remove_junk($db->escape($_POST['serial_no'])) : NULL; // NEW: Serial No
    $subcategory_id = (int)$_POST['subcategory_id'];
    $article        = remove_junk($db->escape($_POST['article']));
    $description    = remove_junk($db->escape($_POST['description']));
    $unit           = remove_junk($db->escape($_POST['unit']));
    $unit_cost      = floatval($_POST['unit_cost']);
    // $qty            = (int)$_POST['qty'];
    $date_acquired  = !empty($_POST['date_acquired']) ? $db->escape($_POST['date_acquired']) : NULL;
    $remarks        = remove_junk($db->escape($_POST['remarks']));

    // Check for duplicate serial number (if provided)
    if (!empty($serial_no)) {
        $check_serial = $db->query("SELECT id FROM properties WHERE serial_no = '{$serial_no}' LIMIT 1");
        if ($check_serial && $check_serial->num_rows > 0) {
            $session->msg("d", "❌ Duplicate detected: Serial No. already exists.");
            redirect('ppe.php', false);
        }
    }

    $query = "INSERT INTO properties (
                    fund_cluster, property_no, serial_no, subcategory_id, article, description, unit, unit_cost, qty, date_acquired, remarks
                  ) VALUES (
                    '{$fund_cluster}', '{$property_no}', '{$serial_no}', '{$subcategory_id}', '{$article}', '{$description}', 
                    '{$unit}', '{$unit_cost}', '{$qty}', '{$date_acquired}', '{$remarks}'
                  )";

    if ($db->query($query)) {
      $session->msg("s", "Equipment added successfully.");
      redirect('ppe.php', false);
    } else {
      $session->msg("d", "Sorry, failed to add equipment.");
      redirect('ppe.php', false);
    }
  } else {
    $session->msg("d", $errors);
    redirect('ppe.php', false);
  }
}

// Get subcategories for dropdown
$all_subcategories = find_all('subcategories');

// Fetch all equipment (fixed query to include all equipment)
$all_equipment = find_by_sql("SELECT p.*, s.subcategory_name as category_name 
                             FROM properties p 
                             LEFT JOIN subcategories s ON p.subcategory_id = s.id 
                             WHERE s.subcategory_name LIKE '%equipment%' OR s.subcategory_name LIKE '%Equipment%'
                             ORDER BY p.article ASC");

$equipment_count = count($all_equipment);

// Calculate total value
function calculate_total_value($items)
{
  $total = 0;
  foreach ($items as $item) {
    $total += ($item['unit_cost'] * $item['qty']);
  }
  return $total;
}

$equipment_value = calculate_total_value($all_equipment);

// Get active categories count
$active_categories = find_by_sql("SELECT COUNT(DISTINCT subcategory_id) as category_count 
                                 FROM properties p 
                                 LEFT JOIN subcategories s ON p.subcategory_id = s.id 
                                 WHERE s.subcategory_name LIKE '%equipment%' OR s.subcategory_name LIKE '%Equipment%'");
$category_count = $active_categories[0]['category_count'];

// Get total properties count (all equipment items)
$total_properties = $equipment_count;
?>
<?php include_once('layouts/header.php'); ?>

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
    --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
    padding: 0.75rem 0.5rem;
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
    padding: 0.35rem 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.875rem;
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
    padding: 0.35rem 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.875rem;
  }

  .btn-danger-custom:hover {
    background: #c82333;
    transform: translateY(-1px);
    color: white;
  }

  /* Table Styles */
  .table-custom {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    margin-bottom: 0;
    width: 100%;
  }

  .table-custom thead {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    font-size: 14px;
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
    font-size:13px;
  }

  .table-custom tbody tr {
    transition: all 0.3s ease;
  }

  .table-custom tbody tr:hover {
    background-color: rgba(40, 167, 69, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

  .actions-column {
    width: 100px;
    text-align: center;
  }

  .btn-group-custom {
    display: flex;
    gap: 0.25rem;
  }

  .btn-group-custom .btn {
    width: 100%;
    padding: 0.25rem 0.4rem;
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

  /* Stats Cards */
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
    border-top: 3px solid;
    transition: all 0.3s ease;
  }

  .stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
  }

  .stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .stat-label {
    color: var(--secondary);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  /* Stat item colors */
  .stat-total {
    border-top-color: #28a745;
  }

  .stat-total .stat-value {
    color: #28a745;
  }

  .stat-categories {
    border-top-color: #17a2b8;
  }

  .stat-categories .stat-value {
    color: #17a2b8;
  }

  .stat-value {
    border-top-color: #6f42c1;
  }

  .stat-value .stat-value {
    color: #6f42c1;
  }

  /* Add Equipment Form Styles */
  .add-equipment-form {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    overflow: hidden;
    display: none;
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

  /* Table Container - No scrollbar */
  .table-container {
    width: 100%;
    overflow: visible;
  }

  /* Ensure text doesn't break in table cells */
  .table-custom td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Allow description to show full text on hover */
  .custom-desc {
    position: relative;
    cursor: help;
  }

  .custom-desc:hover::after {
    content: attr(title);
    position: absolute;
    left: 0;
    top: 100%;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    white-space: normal;
    width: 300px;
    z-index: 1000;
    font-size: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  /* Form improvements */
  .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
  }

  .form-label {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
  }

  .form-hint {
    font-size: 0.8rem;
    color: var(--secondary);
    margin-top: 0.25rem;
  }

  /* Serial No specific styling */
  .serial-no-input {
    font-family: monospace;
    font-weight: 600;
    letter-spacing: 0.5px;
  }

  .serial-no-display {
    font-family: monospace;
    font-weight: 600;
    color: var(--primary-green);
    background: rgba(40, 167, 69, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid rgba(40, 167, 69, 0.2);
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }

    .header-actions {
      flex-direction: column;
      gap: 0.5rem;
    }

    .search-box {
      max-width: 100%;
    }

    /* On mobile, allow horizontal scroll for table */
    .table-container {
      overflow-x: auto;
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

    .form-section {
      padding: 1rem;
    }
  }
</style>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="card-header-custom">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div>
        <h4 class="page-title">Equipment Management</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-custom">
            <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Equipment</li>
          </ol>
        </nav>
      </div>
      <div class="header-actions">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="form-control" placeholder="Search equipment..." id="searchInput">
        </div>
        <button type="button" id="showAddFormBtn" class="btn btn-primary-custom">
          <i class="fas fa-plus me-2"></i> Add Equipment
        </button>
       

      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-item stat-total">
      <div class="stat-value"><?php echo $total_properties; ?></div>
      <div class="stat-label">Total Equipment</div>
      <small class="text-muted">Items in inventory</small>
    </div>
    <div class="stat-item stat-categories">
      <div class="stat-value"><?php echo $category_count; ?></div>
      <div class="stat-label">Active Categories</div>
      <small class="text-muted">Equipment types</small>
    </div>
    <div class="stat-item stat-value">
      <div class="stat-value">₱<?php echo number_format($equipment_value, 2); ?></div>
      <div class="stat-label">Total Value</div>
    </div>
  </div>

  <!-- Equipment Table Section -->
  <div class="card-header-custom" id="equipmentTableSection">
    <?php if ($equipment_count > 0): ?>
      <div class="table-container">
        <table class="table table-custom" id="equipmentTable">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Fund Cluster</th>
              <th>Property No.</th>
              <th>Serial No.</th> 
              <th>Article</th>
              <th class="custom-desc">Description</th>
              <th class="text-center">Unit Cost</th>
              <!-- <th class="text-center">Qty</th> -->
              <!-- <th class="text-center">Total Value</th> -->
              <th class="text-center">Date Acquired</th>
              <th class="text-center actions-column">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_equipment as $index => $equipment): ?>
              <?php
              $total_value = $equipment['unit_cost'] * $equipment['qty'];
              $date_acquired = !empty($equipment['date_acquired'])
                ? date('M d, Y', strtotime($equipment['date_acquired']))
                : '-';
              ?>
              <tr>
                <td class="text-center">
                  <span class="badge badge-custom badge-primary"><?= $index + 1 ?></span>
                </td>
                <td><?= remove_junk($equipment['fund_cluster']); ?></td>
                <td><strong><?= remove_junk($equipment['property_no']); ?></strong></td>
                <!-- NEW: Serial No Display -->
                <td>
                  <?php if (!empty($equipment['serial_no'])): ?>
                    <span class="serial-no-display">
                      <?= remove_junk($equipment['serial_no']); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?= remove_junk($equipment['article']); ?><br>
                  <small class="text-muted"> <?= remove_junk($equipment['unit']); ?></small>
                </td>
                <td class="custom-desc" title="<?= htmlspecialchars(remove_junk($equipment['description'])); ?>">
                  <?php
                  $desc = remove_junk($equipment['description']);
                  echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                  ?>
                </td>
                <td class="text-center"><strong class="text-success">₱<?= number_format($equipment['unit_cost'], 2); ?></strong></td>
                <!-- <td class="text-center"><span class="badge badge-custom badge-primary"><?= remove_junk($equipment['qty']); ?></span></td>
                <td class="text-center"><strong class="text-success">₱<?= number_format($total_value, 2); ?></strong></td> -->
                <td class="text-center"><?= $date_acquired; ?></td>
                <td class="text-center">
                  <div class="btn-group btn-group-custom">
                    <a href="edit_ppe.php?id=<?= (int)$equipment['id']; ?>"
                      class="btn btn-warning-custom" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="a_script.php?id=<?= (int)$equipment['id']; ?>"
                      class="btn btn-danger-custom archive-btn"
                      data-id="<?= (int)$equipment['id']; ?>"
                      title="Archive">
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
      <div class="empty-state">
        <i class="fas fa-tools empty-state-icon"></i>
        <h4>No Equipment Found</h4>
        <p>Get started by adding your first equipment</p>
        <button type="button" class="btn btn-primary-custom" id="showAddFormEmptyBtn">
          <i class="fas fa-plus me-2"></i> Add Equipment
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Add Equipment Form (Hidden by default) -->
  <div class="add-equipment-form" id="addEquipmentForm">
    <div class="add-form-header">
      <h5><i class="fas fa-plus-circle me-2"></i> Add New Equipment</h5>
    </div>

    <form method="post" action="ppe.php" class="needs-validation" novalidate>
      <div class="form-section">
        <h6 class="section-title">Basic Information</h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="fund_cluster" class="form-label fw-bold">
                Fund Cluster <span class="text-danger">*</span>
              </label>
              <select class="form-control w-100 " id="fund_cluster" name="fund_cluster" required>
                <option value="">Select Fund Cluster</option>
                <?php foreach ($fund_clusters as $cluster): ?>
                  <option value="<?php echo remove_junk($cluster['name']); ?>">
                    <?php echo remove_junk($cluster['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">
                Please select a fund cluster.
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="property_no" class="form-label fw-bold">Property No. <span class="text-danger">*</span></label><br>
              <input type="text" class="form-control w-100" id="property_no" name="property_no" required>
              <div class="invalid-feedback">
                Please provide a property number.
              </div>
            </div>
          </div>

          <!-- NEW: Serial No Field -->
          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="serial_no" class="form-label fw-bold">Serial No.</label>
              <input type="text" class="form-control serial-no-input" id="serial_no" name="serial_no" 
                     placeholder="Enter serial number">
              <div class="form-hint">
                <i class="fas fa-info-circle me-1"></i>Unique serial number for tracking
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="subcategory_id" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
              <select name="subcategory_id" id="subcategory_id" class="form-control" required>
                <option value="">Select Category</option>
                <?php foreach ($all_subcategories as $sub): ?>
                  <?php if (stripos($sub['subcategory_name'], 'equipment') !== false): ?>
                    <option value="<?php echo (int)$sub['id']; ?>">
                      <?php echo remove_junk($sub['subcategory_name']); ?>
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">
                Please select a category.
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="article" class="form-label fw-bold">
                Article <span class="text-danger">*</span>
                <small class="text-muted ms-1">
                  <i class="fas fa-info-circle"></i> Shortened item name
                </small>
              </label>
              <input type="text" class="form-control" id="article" name="article" required>
              <div class="invalid-feedback">
                Please provide an article name.
              </div>
            </div>
          </div>

          <div class="col-12 mb-3">
            <div class="form-group">
              <label for="description" class="form-label fw-bold">Description <span class="text-danger">*</span></label>
              <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
              <div class="invalid-feedback">
                Please provide a description.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h6 class="section-title">Quantity and Cost Information</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="form-group">
              <label for="unit" class="form-label fw-bold">Unit Of Measurement<span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="unit" name="unit" required>
              <div class="invalid-feedback">
                Please provide a unit.
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="form-group">
              <label for="unit_cost" class="form-label fw-bold">Unit Cost <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" min="0" class="form-control" id="unit_cost" name="unit_cost" required>
              </div>
              <div class="invalid-feedback">
                Please provide a valid unit cost.
              </div>
            </div>
          </div>

          <!-- <div class="col-md-4 mb-3">
            <div class="form-group">
              <label for="qty" class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" id="qty" name="qty" required>
              <div class="invalid-feedback">
                Please provide a valid quantity.
              </div>
            </div>
          </div> -->
        </div>
      </div>

      <div class="form-section">
        <h6 class="section-title">Additional Information</h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="date_acquired" class="form-label fw-bold">Date Acquired</label>
              <input type="date" class="form-control" id="date_acquired" name="date_acquired">
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="remarks" class="form-label fw-bold">Remarks</label>
              <input type="text" class="form-control" id="remarks" name="remarks">
            </div>
          </div>
        </div>
      </div>

      <!-- IMPROVED BUTTONS SECTION - WIDER AND CENTERED -->
      <div class="form-section">
        <div class="form-buttons-container">
          <button type="button" id="cancelFormBtn" class="btn btn-cancel form-btn">
            <i class="fas fa-times me-2"></i> Close
          </button>
          <button type="submit" name="add_equipment" class="btn btn-save form-btn">
            <i class="fas fa-save me-2"></i> Save Equipment
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- SweetAlert for flash messages -->
<?php if ($msg = $session->msg()):
  $type = key($msg);
  $text = $msg[$type];
?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        icon: '<?php echo $type === "danger" ? "error" : $type; ?>',
        title: '<?php echo ucfirst($type); ?>',
        text: '<?php echo addslashes($text); ?>',
        confirmButtonText: 'OK'
      });
    });
  </script>
<?php endif; ?>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    // Initialize DataTable
    var equipmentTable = $('#equipmentTable').DataTable({
      pageLength: 10,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: true,
      autoWidth: false,
    });

    // Global search functionality
    $('#searchInput').on('keyup', function() {
      equipmentTable.search(this.value).draw();
    });

    // Show add form buttons
    $('#showAddFormBtn, #showAddFormEmptyBtn').on('click', function() {
      $('#addEquipmentForm').slideDown(300);
      $('#equipmentTableSection').slideUp(300); // Hide table section
      $('html, body').animate({
        scrollTop: $('#addEquipmentForm').offset().top - 20
      }, 300);
    });

    // Hide add form
    $('#cancelFormBtn').on('click', function() {
      $('#addEquipmentForm').slideUp(300);
      $('#equipmentTableSection').slideDown(300); // Show table section
    });

    // Archive confirmation
    document.querySelectorAll('.archive-btn').forEach(function(button) {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');
        const id = this.getAttribute('data-id');

        Swal.fire({
          title: 'Archive Equipment?',
          text: "This equipment will be moved to archives. You can restore it later if needed.",
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

    // Serial No duplicate check
    const serialNoInput = document.getElementById('serial_no');
    if (serialNoInput) {
      serialNoInput.addEventListener('blur', function() {
        const serialNo = this.value.trim();
        if (serialNo) {
          // You could add AJAX validation here for real-time duplicate checking
          console.log('Serial No entered:', serialNo);
        }
      });
    }
  });
</script>