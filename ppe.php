<?php
$page_title = 'All Properties, Plant and Equipment';
require_once('includes/load.php');
page_require_level(1);

$fund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_property'])) {
  $req_fields = array('fund_cluster', 'property_no', 'subcategory_id', 'article', 'description', 'unit', 'unit_cost', 'qty', 'date_acquired');
  validate_fields($req_fields);

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

    $query = "INSERT INTO properties (
                    fund_cluster, property_no, subcategory_id, article, description, unit, unit_cost, qty, date_acquired, remarks
                  ) VALUES (
                    '{$fund_cluster}', '{$property_no}', '{$subcategory_id}', '{$article}', '{$description}', 
                    '{$unit}', '{$unit_cost}', '{$qty}', '{$date_acquired}', '{$remarks}'
                  )";

    if ($db->query($query)) {
      $session->msg("s", "Property added successfully.");
      redirect('ppe.php', false);
    } else {
      $session->msg("d", "Sorry, failed to add property.");
      redirect('ppe.php', false);
    }
  } else {
    $session->msg("d", $errors);
    redirect('ppe.php', false);
  }
}

// Get subcategories for dropdown
$all_subcategories = find_all('subcategories');

// Fetch all properties
$all_properties = find_all('properties');

// Separate properties by type (Property, Plant, Equipment)
$properties_by_type = [
  'property' => [],
  'plant' => [],
  'equipment' => []
];

foreach ($all_properties as $property) {
  $category_name = strtolower($property['category_name'] ?? '');
  
  if (strpos($category_name, 'plant') !== false) {
    $properties_by_type['plant'][] = $property;
  } elseif (strpos($category_name, 'equipment') !== false) {
    $properties_by_type['equipment'][] = $property;
  } else {
    $properties_by_type['property'][] = $property;
  }
}

// Get counts for each type
$property_count = count($properties_by_type['property']);
$plant_count = count($properties_by_type['plant']);
$equipment_count = count($properties_by_type['equipment']);
$total_count = count($all_properties);

// Calculate total values for each type
function calculate_total_value($items) {
  $total = 0;
  foreach ($items as $item) {
    $total += ($item['unit_cost'] * $item['qty']);
  }
  return $total;
}

$property_value = calculate_total_value($properties_by_type['property']);
$plant_value = calculate_total_value($properties_by_type['plant']);
$equipment_value = calculate_total_value($properties_by_type['equipment']);
$total_value = $property_value + $plant_value + $equipment_value;
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

  /* Enhanced Tab Styles */
  .nav-tabs-custom {
    border: none;
    border-bottom: 3px solid #dee2e6;
    border-top: 2px solid #dee2e6;
    background: white;
    border-radius: 0;
    width: 100%;
    display: flex;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }

  .nav-tabs-custom .nav-item {
    flex: 1;
    text-align: center;
    margin: 0;
  }

  .nav-tabs-custom .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    color: var(--secondary);
    font-weight: 600;
    padding: 1.25rem 1rem;
    margin: 0;
    transition: all 0.3s ease;
    background: transparent;
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .nav-tabs-custom .nav-link:hover {
    border-color: var(--primary-light);
    color: var(--primary);
    background: rgba(40, 167, 69, 0.05);
  }

  .nav-tabs-custom .nav-link.active {
    background: white;
    border-color: var(--primary);
    color: var(--primary);
    font-weight: 700;
  }

  .nav-tabs-custom .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--primary);
  }

  .tab-badge {
    background: var(--primary);
    color: white;
    border-radius: 50px;
    padding: 0.3rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 30px;
  }

  .nav-tabs-custom .nav-link.active .tab-badge {
    background: var(--primary-dark);
  }

  .tab-pane {
    animation: fadeIn 0.3s ease-in-out;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Table Styles */
  .table-custom {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    margin-bottom: 0;
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
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
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

  /* Type-specific colors */
  .stat-property {
    border-top-color: #28a745;
  }

  .stat-property .stat-value {
    color: #28a745;
  }

  .stat-plant {
    border-top-color: #17a2b8;
  }

  .stat-plant .stat-value {
    color: #17a2b8;
  }

  .stat-equipment {
    border-top-color: #6f42c1;
  }

  .stat-equipment .stat-value {
    color: #6f42c1;
  }

  .tab-property .table-custom thead {
    background: linear-gradient(135deg, #28a745, #1e7e34);
  }

  .tab-plant .table-custom thead {
    background: linear-gradient(135deg, #17a2b8, #138496);
  }

  .tab-equipment .table-custom thead {
    background: linear-gradient(135deg, #6f42c1, #59359a);
  }

  /* Add Property Form Styles */
  .add-property-form {
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

  /* Table Container Fix */
  .table-responsive {
    overflow-x: auto;
    max-width: 100%;
  }

  /* Fixed Table Layout */
  .table-custom {
    table-layout: fixed;
    width: 100%;
  }

  /* Specific Column Widths */
  .table-custom th:nth-child(1),
  .table-custom td:nth-child(1) {
    width: 5%;
  }

  .table-custom th:nth-child(2),
  .table-custom td:nth-child(2) {
    width: 8%;
  }

  .table-custom th:nth-child(3),
  .table-custom td:nth-child(3) {
    width: 10%;
  }

  .table-custom th:nth-child(4),
  .table-custom td:nth-child(4) {
    width: 12%;
  }

  .table-custom th:nth-child(5),
  .table-custom td:nth-child(5) {
    width: 20%;
  }

  .table-custom th:nth-child(6),
  .table-custom td:nth-child(6) {
    width: 8%;
  }

  .table-custom th:nth-child(7),
  .table-custom td:nth-child(7) {
    width: 5%;
  }

  .table-custom th:nth-child(8),
  .table-custom td:nth-child(8) {
    width: 10%;
  }

  .table-custom th:nth-child(9),
  .table-custom td:nth-child(9) {
    width: 10%;
  }

  .table-custom th:nth-child(10),
  .table-custom td:nth-child(10) {
    width: 12%;
  }

  /* Ensure text doesn't break in table cells */
  .table-custom td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 0;
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .nav-tabs-custom {
      flex-direction: column;
    }
    
    .nav-tabs-custom .nav-item {
      flex: none;
    }
    
    .nav-tabs-custom .nav-link {
      padding: 1rem 0.5rem;
      font-size: 0.9rem;
    }
    
    .tab-badge {
      font-size: 0.7rem;
      padding: 0.2rem 0.4rem;
      min-width: 25px;
    }
    
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
  }
</style>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="card-header-custom">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div>
        <h4 class="page-title">Properties, Plant and Equipment Management</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-custom">
            <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Properties</li>
          </ol>
        </nav>
      </div>
      <div class="header-actions">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="form-control" placeholder="Search properties..." id="searchInput">
        </div>
        <button type="button" id="showAddFormBtn" class="btn btn-primary-custom">
          <i class="fas fa-plus me-2"></i> Add Property
        </button>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-item stat-property">
      <div class="stat-value"><?php echo $property_count; ?></div>
      <div class="stat-label">Properties</div>
      <small class="text-muted">₱<?php echo number_format($property_value, 2); ?></small>
    </div>
    <div class="stat-item stat-plant">
      <div class="stat-value"><?php echo $plant_count; ?></div>
      <div class="stat-label">Plant Items</div>
      <small class="text-muted">₱<?php echo number_format($plant_value, 2); ?></small>
    </div>
    <div class="stat-item stat-equipment">
      <div class="stat-value"><?php echo $equipment_count; ?></div>
      <div class="stat-label">Equipment</div>
      <small class="text-muted">₱<?php echo number_format($equipment_value, 2); ?></small>
    </div>
    <div class="stat-item">
      <div class="stat-value"><?php echo $total_count; ?></div>
      <div class="stat-label">Total Items</div>
      <small class="text-muted">₱<?php echo number_format($total_value, 2); ?></small>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <ul class="nav nav-tabs nav-tabs-custom" id="ppeTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="property-tab" data-bs-toggle="tab" data-bs-target="#property" type="button" role="tab">
        <i class="fas fa-building me-2"></i>Properties
        <span class="tab-badge"><?php echo $property_count; ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="plant-tab" data-bs-toggle="tab" data-bs-target="#plant" type="button" role="tab">
        <i class="fas fa-seedling me-2"></i>Plant
        <span class="tab-badge"><?php echo $plant_count; ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="equipment-tab" data-bs-toggle="tab" data-bs-target="#equipment" type="button" role="tab">
        <i class="fas fa-tools me-2"></i>Equipment
        <span class="tab-badge"><?php echo $equipment_count; ?></span>
      </button>
    </li>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content" id="ppeTabsContent">
    
    <!-- Properties Tab -->
    <div class="tab-pane fade show active tab-property" id="property" role="tabpanel">
      <?php if ($property_count > 0): ?>
        <div class="table-responsive">
          <table class="table table-custom" id="propertyTable">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th>Fund Cluster</th>
                <th>Property No.</th>
                <th>Article</th>
                <th class="custom-desc">Description</th>
                <th class="text-center">Unit Cost</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Total Value</th>
                <th class="text-center">Date Acquired</th>
                <th class="text-center actions-column">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($properties_by_type['property'] as $index => $property): ?>
                <?php
                $total_value = $property['unit_cost'] * $property['qty'];
                $date_acquired = !empty($property['date_acquired'])
                  ? date('M d, Y', strtotime($property['date_acquired']))
                  : '-';
                ?>
                <tr>
                  <td class="text-center">
                    <span class="badge badge-custom badge-primary"><?= $index + 1 ?></span>
                  </td>
                  <td><?= remove_junk($property['fund_cluster']); ?></td>
                  <td><strong><?= remove_junk($property['property_no']); ?></strong></td>
                  <td><?= remove_junk($property['article']); ?><br>
                    <small class="text-muted"> <?= remove_junk($property['unit']); ?></small>
                  </td>
                  <td class="custom-desc" title="<?= htmlspecialchars(remove_junk($property['description'])); ?>">
                    <?php
                    $desc = remove_junk($property['description']);
                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                    ?>
                  </td>
                  <td class="text-center"><strong>₱<?= number_format($property['unit_cost'], 2); ?></strong></td>
                  <td class="text-center"><span class="badge badge-custom badge-primary"><?= remove_junk($property['qty']); ?></span></td>
                  <td class="text-center"><strong class="text-success">₱<?= number_format($total_value, 2); ?></strong></td>
                  <td class="text-center"><?= $date_acquired; ?></td>
                  <td class="text-center">
                    <div class="btn-group btn-group-custom">
                      <a href="edit_ppe.php?id=<?= (int)$property['id']; ?>"
                        class="btn btn-warning-custom" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="archive_property.php?id=<?= (int)$property['id']; ?>"
                        class="btn btn-danger-custom archive-btn"
                        data-id="<?= (int)$property['id']; ?>"
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
          <i class="fas fa-building empty-state-icon"></i>
          <h4>No Properties Found</h4>
          <p>Get started by adding your first property</p>
          <button type="button" class="btn btn-primary-custom show-add-form">
            <i class="fas fa-plus me-2"></i> Add Property
          </button>
        </div>
      <?php endif; ?>
    </div>

    <!-- Plant Tab -->
    <div class="tab-pane fade tab-plant" id="plant" role="tabpanel">
      <?php if ($plant_count > 0): ?>
        <div class="table-responsive">
          <table class="table table-custom" id="plantTable">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th>Fund Cluster</th>
                <th>Property No.</th>
                <th>Article</th>
                <th class="custom-desc">Description</th>
                <th class="text-center">Unit Cost</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Total Value</th>
                <th class="text-center">Date Acquired</th>
                <th class="text-center actions-column">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($properties_by_type['plant'] as $index => $property): ?>
                <?php
                $total_value = $property['unit_cost'] * $property['qty'];
                $date_acquired = !empty($property['date_acquired'])
                  ? date('M d, Y', strtotime($property['date_acquired']))
                  : '-';
                ?>
                <tr>
                  <td class="text-center">
                    <span class="badge badge-custom badge-primary"><?= $index + 1 ?></span>
                  </td>
                  <td><?= remove_junk($property['fund_cluster']); ?></td>
                  <td><strong><?= remove_junk($property['property_no']); ?></strong></td>
                  <td><?= remove_junk($property['article']); ?><br>
                    <small class="text-muted"> <?= remove_junk($property['unit']); ?></small>
                  </td>
                  <td class="custom-desc" title="<?= htmlspecialchars(remove_junk($property['description'])); ?>">
                    <?php
                    $desc = remove_junk($property['description']);
                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                    ?>
                  </td>
                  <td class="text-center"><strong>₱<?= number_format($property['unit_cost'], 2); ?></strong></td>
                  <td class="text-center"><span class="badge badge-custom badge-primary"><?= remove_junk($property['qty']); ?></span></td>
                  <td class="text-center"><strong class="text-success">₱<?= number_format($total_value, 2); ?></strong></td>
                  <td class="text-center"><?= $date_acquired; ?></td>
                  <td class="text-center">
                    <div class="btn-group btn-group-custom">
                      <a href="edit_ppe.php?id=<?= (int)$property['id']; ?>"
                        class="btn btn-warning-custom" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="archive_property.php?id=<?= (int)$property['id']; ?>"
                        class="btn btn-danger-custom archive-btn"
                        data-id="<?= (int)$property['id']; ?>"
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
          <i class="fas fa-seedling empty-state-icon"></i>
          <h4>No Plant Items Found</h4>
          <p>Get started by adding your first plant item</p>
          <button type="button" class="btn btn-primary-custom show-add-form">
            <i class="fas fa-plus me-2"></i> Add Plant Item
          </button>
        </div>
      <?php endif; ?>
    </div>

    <!-- Equipment Tab -->
    <div class="tab-pane fade tab-equipment" id="equipment" role="tabpanel">
      <?php if ($equipment_count > 0): ?>
        <div class="table-responsive">
          <table class="table table-custom" id="equipmentTable">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th>Fund Cluster</th>
                <th>Property No.</th>
                <th>Article</th>
                <th class="custom-desc">Description</th>
                <th class="text-center">Unit Cost</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Total Value</th>
                <th class="text-center">Date Acquired</th>
                <th class="text-center actions-column">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($properties_by_type['equipment'] as $index => $property): ?>
                <?php
                $total_value = $property['unit_cost'] * $property['qty'];
                $date_acquired = !empty($property['date_acquired'])
                  ? date('M d, Y', strtotime($property['date_acquired']))
                  : '-';
                ?>
                <tr>
                  <td class="text-center">
                    <span class="badge badge-custom badge-primary"><?= $index + 1 ?></span>
                  </td>
                  <td><?= remove_junk($property['fund_cluster']); ?></td>
                  <td><strong><?= remove_junk($property['property_no']); ?></strong></td>
                  <td><?= remove_junk($property['article']); ?><br>
                    <small class="text-muted"> <?= remove_junk($property['unit']); ?></small>
                  </td>
                  <td class="custom-desc" title="<?= htmlspecialchars(remove_junk($property['description'])); ?>">
                    <?php
                    $desc = remove_junk($property['description']);
                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                    ?>
                  </td>
                  <td class="text-center"><strong>₱<?= number_format($property['unit_cost'], 2); ?></strong></td>
                  <td class="text-center"><span class="badge badge-custom badge-primary"><?= remove_junk($property['qty']); ?></span></td>
                  <td class="text-center"><strong class="text-success">₱<?= number_format($total_value, 2); ?></strong></td>
                  <td class="text-center"><?= $date_acquired; ?></td>
                  <td class="text-center">
                    <div class="btn-group btn-group-custom">
                      <a href="edit_ppe.php?id=<?= (int)$property['id']; ?>"
                        class="btn btn-warning-custom" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="archive_property.php?id=<?= (int)$property['id']; ?>"
                        class="btn btn-danger-custom archive-btn"
                        data-id="<?= (int)$property['id']; ?>"
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
          <button type="button" class="btn btn-primary-custom show-add-form">
            <i class="fas fa-plus me-2"></i> Add Equipment
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add Property Form (Hidden by default) -->
  <div class="add-property-form" id="addPropertyForm">
    <div class="add-form-header">
      <h5><i class="fas fa-plus-circle me-2"></i> Add New Property, Plant & Equipment</h5>
      <button type="button" id="cancelAddBtn" class="btn btn-light btn-sm">
        <i class="fas fa-times me-1"></i> Cancel
      </button>
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
              <select class="form-control" id="fund_cluster" name="fund_cluster" required>
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
              <label for="property_no" class="form-label fw-bold">Property No. <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="property_no" name="property_no" required>
              <div class="invalid-feedback">
                Please provide a property number.
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <div class="form-group">
              <label for="subcategory_id" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
              <select name="subcategory_id" id="subcategory_id" class="form-control" required>
                <option value=""> Select Category </option>
                <?php foreach ($all_subcategories as $sub): ?>
                  <option value="<?php echo (int)$sub['id']; ?>">
                    <?php echo remove_junk($sub['subcategory_name']); ?>
                  </option>
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

          <div class="col-md-4 mb-3">
            <div class="form-group">
              <label for="qty" class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
              <input type="number" min="1" class="form-control" id="qty" name="qty" required>
              <div class="invalid-feedback">
                Please provide a valid quantity.
              </div>
            </div>
          </div>
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

      <div class="form-section">
        <div class="d-flex justify-content-between">
          <button type="button" id="cancelFormBtn" class="btn btn-secondary">
            <i class="fas fa-times me-1"></i> Cancel
          </button>
          <button type="submit" name="add_property" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Save Property
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    // Initialize DataTables for each table
    var propertyTable = $('#propertyTable').DataTable({
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: true,
      autoWidth: false,
    });

    var plantTable = $('#plantTable').DataTable({
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: true,
      autoWidth: false,
    });

    var equipmentTable = $('#equipmentTable').DataTable({
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: true,
      autoWidth: false,
    });

    // Global search functionality
    $('#searchInput').on('keyup', function() {
      var searchTerm = this.value;
      
      // Search in active tab's table
      var activeTab = $('.nav-tabs .nav-link.active').attr('id');
      
      switch(activeTab) {
        case 'property-tab':
          propertyTable.search(searchTerm).draw();
          break;
        case 'plant-tab':
          plantTable.search(searchTerm).draw();
          break;
        case 'equipment-tab':
          equipmentTable.search(searchTerm).draw();
          break;
      }
    });

    // Show add form buttons
    $('.show-add-form').on('click', function() {
      $('#showAddFormBtn').click();
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

    // Tab change event - reset search
    $('#ppeTabs button').on('shown.bs.tab', function (e) {
      $('#searchInput').val('').trigger('keyup');
    });

    // Add Property Form functionality
    $('#showAddFormBtn').on('click', function() {
      $('#addPropertyForm').slideDown(300);
      $('html, body').animate({
        scrollTop: $('#addPropertyForm').offset().top - 20
      }, 300);
    });

    $('#cancelAddBtn, #cancelFormBtn').on('click', function() {
      $('#addPropertyForm').slideUp(300);
    });
  });
</script>