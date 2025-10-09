<?php
$page_title = 'Printable Reports on Physical Count';
require_once('includes/load.php');
page_require_level(1);

// ensure current user info is always defined
$current_user = current_user(); // your app's helper
$current_user_name = isset($current_user['name']) ? $current_user['name'] : '';
$current_user_position = isset($current_user['position']) ? $current_user['position'] : '';

// ✅ SEMI-EXPENDABLES
$sql_semi = "
  SELECT 
    sep.*, 
    sc.semicategory_name
  FROM semi_exp_prop sep
  JOIN transactions t ON sep.id = t.item_id
  LEFT JOIN semicategories sc ON sep.semicategory_id = sc.id
  WHERE t.transaction_type = 'issue'
    AND t.ICS_No IS NOT NULL
";

if (!empty($semicategory_filter)) {
  $sql_semi .= " AND sep.semicategory_id = '" . $db->escape($semicategory_filter) . "'";
}
if (!empty($smpdate_filter)) {
  $sql_semi .= " AND DATE(sep.date_added) <= '" . $db->escape($smpdate_filter) . "'";
}
if (!empty($smpfund_cluster_filter)) {
  $sql_semi .= " AND sep.fund_cluster = '" . $db->escape($smpfund_cluster_filter) . "'";
}
if (!empty($value_type_filter)) {
  if ($value_type_filter == 'low') {
    $sql_semi .= " AND sep.unit_cost < 5000";
  } elseif ($value_type_filter == 'high') {
    $sql_semi .= " AND sep.unit_cost >= 5000 AND sep.unit_cost < 50000";
  }
}

$sql_semi .= " ORDER BY sep.inv_item_no ASC";



// ✅ PROPERTIES
$sql_props = "
  SELECT 
    p.*, 
    s.subcategory_name
  FROM properties p
  JOIN transactions t ON p.id = t.item_id
  LEFT JOIN subcategories s ON p.subcategory_id = s.id
  WHERE t.transaction_type = 'issue'
    AND t.PAR_No IS NOT NULL
";

if (!empty($subcategory_filter)) {
  $sql_props .= " AND p.subcategory_id = '" . $db->escape($subcategory_filter) . "'";
}
if (!empty($smpdate_filter)) {
  $sql_props .= " AND DATE(p.date_acquired) <= '" . $db->escape($smpdate_filter) . "'";
}
if (!empty($smpfund_cluster_filter)) {
  $sql_props .= " AND p.fund_cluster = '" . $db->escape($smpfund_cluster_filter) . "'";
}
if (!empty($value_type_filter)) {
  if ($value_type_filter == 'low') {
    $sql_props .= " AND p.unit_cost < 5000";
  } elseif ($value_type_filter == 'high') {
    $sql_props .= " AND p.unit_cost >= 5000 AND p.unit_cost < 50000";
  }
}

$sql_props .= " ORDER BY p.property_no ASC";



// ✅ REGULAR ITEMS
$sql = "
  SELECT 
    i.*, 
    c.name AS category_name
  FROM items i
  JOIN request_items ri ON ri.item_id = i.id
  JOIN requests r ON r.id = ri.req_id
  LEFT JOIN categories c ON i.categorie_id = c.id
";

if (!empty($category_filter)) {
  $sql .= " AND i.categorie_id = '" . $db->escape($category_filter) . "'";
}
if (!empty($date_filter)) {
  $sql .= " AND DATE(i.date_added) <= '" . $db->escape($date_filter) . "'";
}
if (!empty($fund_cluster_filter)) {
  $sql .= " AND i.fund_cluster = '" . $db->escape($fund_cluster_filter) . "'";
}

$sql .= " ORDER BY i.id ASC";



// ✅ Fetch data
$props = find_by_sql($sql_props);
$semi_items = find_by_sql($sql_semi);
$items = find_by_sql($sql);
?>

<?php include_once('layouts/header.php'); ?>

<style>
  :root {
    --primary: #28a745;
    --primary-dark: #1e7e34;
    --primary-light: #34ce57;
    --secondary: #6c757d;
    --light: #f8f9fa;
    --dark: #343a40;
    --border-radius: 10px;
  }

  .card-container {
    max-width: 1300px;
    margin: 0 auto;
  }

  .card-header-custom {
    border-top: 5px solid green;
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    padding: 1rem 1.5rem;
  }

  .registry-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.3rem;
    font-weight: 700;
    text-align: center;
    margin: 0;
    line-height: 1.3;
  }

  .tabs-container {
    background: white;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .nav-tabs-custom {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 2px solid #e9ecef;
    padding: 0;
    margin: 0;
  }

  .nav-tab-item {
    flex: 1;
    min-width: 200px;
    text-align: center;
  }

  .nav-tab-link {
    display: block;
    padding: 1rem 1.5rem;
    background-color: #ffffffff;
    color: var(--secondary);
    text-decoration: none;
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 600;
    position: relative;
    overflow: hidden;
  }

  .nav-tab-link:hover {
    background-color: #e9ecef;
    color: var(--primary-dark);
  }

  .nav-tab-link.active {
    background-color: #ccffd9ff;
    color: var(--primary);
    border-bottom: 3px solid var(--primary);
  }

  .nav-tab-link.active:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--primary);
  }

  .tab-icon {
    margin-right: 8px;
    font-size: 1.1rem;
  }

  .tab-content {
    padding: 2rem;
    background: white;
    min-height: 300px;
  }

  .tab-pane {
    display: none;
    animation: fadeIn 0.5s ease;
  }

  .tab-pane.active {
    display: block;
  }

  .tab-description {
    color: var(--secondary);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    line-height: 1.6;
    text-align: center;
  }

  .btn-tab-action {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .btn-tab-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
    color: white;
  }

  .action-container {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
  }

  .stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .stat-card {
    background: #f8f9fa;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-align: center;
    border-left: 4px solid var(--primary);
    transition: transform 0.3s ease;
  }

  .stat-card:hover {
    transform: translateY(-5px);
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
  }

  /* RPCI Form Styles */
  .rpci-form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 2rem;
  }

  .rpci-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .rpci-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .rpci-subtitle {
    font-style: italic;
    color: var(--secondary);
  }

  .form-section {
    margin-bottom: 2rem;
  }

  .form-section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--primary-dark);
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
  }

  .form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -0.5rem;
  }

  .form-group {
    flex: 1;
    min-width: 200px;
    padding: 0 0.5rem;
    margin-bottom: 1rem;
  }

  .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark);
  }

  .form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .form-control:focus {
    border-color: var(--primary);
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
  }

  .table-responsive {
    overflow-x: auto;
  }

  .rpci-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
  }

  .rpci-table th,
  .rpci-table td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: left;
  }

  .rpci-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  .signature-section {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-top: 2rem;
  }

  .signature-box {
    flex: 1;
    min-width: 200px;
    margin: 0 0.5rem 1.5rem;
    text-align: center;
  }

  .signature-line {
    border-top: 1px solid #000;
    margin-top: 5px;
    padding-top: 0.5rem;
  }

  .btn-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
  }

  .btn-secondary {
    background: var(--secondary);
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-secondary:hover {
    background: #5a6268;
    color: white;
  }

  .underline {
    border-bottom: 1px solid #000;
    display: inline-block;
    min-width: 150px;
    text-align: center;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  @media (max-width: 768px) {
    .nav-tabs-custom {
      flex-direction: column;
    }

    .nav-tab-item {
      min-width: 100%;
    }

    .registry-title {
      font-size: 1.1rem;
    }

    .tab-content {
      padding: 1.5rem;
    }

    .card-header-custom {
      padding: 0.8rem 1rem;
    }

    .signature-section {
      flex-direction: column;
    }

    .signature-box {
      margin-bottom: 2rem;
    }
  }

  /* RPCSP Form Styles */
  .rpcsp-form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 2rem;
  }

  .rpcsp-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .rpcsp-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .rpcsp-subtitle {
    font-style: italic;
    color: var(--secondary);
  }

  .rpcsp-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
  }

  .rpcsp-table th,
  .rpcsp-table td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: center;
  }

  .rpcsp-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  /* RPCSPPE Form Styles */
  .rpcppe-form-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-top: 2rem;
  }

  .rpcppe-header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
  }

  .rpcppe-title {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .rpcppe-subtitle {
    font-style: italic;
    color: var(--secondary);
    margin-bottom: 0.5rem;
  }

  .rpcppe-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
  }

  .rpcppe-table th,
  .rpcppe-table td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: center;
    vertical-align: middle;
  }

  .rpcppe-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }

  /* Multi-signature styles */
  .multi-signature-section {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-top: 2rem;
  }

  .multi-signature-column {
    flex: 1;
    min-width: 200px;
    margin: 0 0.5rem 1.5rem;
  }

  .multi-signature-box {
    text-align: center;
    margin-bottom: 1.5rem;
  }

  .multi-signature-line {
    border-top: 1px solid #000;
    margin-top: 5px;
    padding-top: 0.5rem;
    min-height: 60px;
  }

  .multi-signature-caption {
    font-size: 0.8rem;
    margin-top: 3px;
    line-height: 1.2;
  }
</style>

<div class="card-container mt-3">
  <div class="card shadow-sm border-0">
    <div class="card-header-custom">
      <h3 class="registry-title">REPORTS ON PHYSICAL COUNT OF:</h3>
    </div>

    <div class="tabs-container">
      <ul class="nav-tabs-custom" id="registryTabs">
        <li class="nav-tab-item">
          <a href="#inventories" class="nav-tab-link active" data-tab="inventories">
            <i class="fas fa-boxes tab-icon"></i> Inventories
          </a>
        </li>
        <li class="nav-tab-item">
          <a href="#property" class="nav-tab-link" data-tab="property">
            <i class="fas fa-building tab-icon"></i> Property, Plant & Equipment
          </a>
        </li>
        <li class="nav-tab-item">
          <a href="#semi-expendable" class="nav-tab-link" data-tab="semi-expendable">
            <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property
          </a>
        </li>
      </ul>

      <div class="tab-content">
        <!-- Inventories Tab -->
        <div id="inventories" class="tab-pane active">

          <!-- RPCI Form Section -->
          <div class="rpci-form-container">
            <div class="rpci-header">
              <h2 class="rpci-title">REPORT ON THE PHYSICAL COUNT OF INVENTORIES</h2>
            </div>

            <form method="post" action="" id="filter-form">
              <!-- Category and Date Selection -->
              <div class="form-section">
                <h4 class="form-section-title">Filter</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control filter-input" name="categorie_id" id="categorie_id" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Categories</option>
                      <?php
                      $categories = find_by_sql("SELECT id, name FROM categories ORDER BY name ASC");
                      foreach ($categories as $cat) {
                        $selected = (isset($_POST['categorie_id']) && $_POST['categorie_id'] == $cat['id']) ? 'selected' : '';
                        echo "<option value=\"{$cat['id']}\" $selected>{$cat['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">As at</label>
                    <input type="date" class="form-control filter-input" name="date_added" id="date_added" value="<?php echo isset($_POST['date_added']) ? $_POST['date_added'] : ''; ?>" style="border: none; background-color: #f8f9fa;">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Fund Cluster</label>
                    <select class="form-control filter-input" name="fund_cluster" id="fund_cluster" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Fund Clusters</option>
                      <?php
                      $clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
                      foreach ($clusters as $cluster) {
                        $selected = (isset($_POST['fund_cluster']) && $_POST['fund_cluster'] == $cluster['name']) ? 'selected' : '';
                        echo "<option value=\"{$cluster['name']}\" $selected>{$cluster['name']}</option>";
                      }

                      ?>
                    </select>
                  </div>
                </div>
                <div style="margin-bottom: 15px; line-height: 1.8;">
                  <strong>For which</strong>
                  <span class="underline" style="min-width: 180px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
                  <span class="underline" style="min-width: 150px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
                  BSU-BOKOD CAMPUS is accountable, having assumed such accountability on
                  <input type="date" class="form-control filter-input" name="assumption_date" id="assumption_date" value="<?php echo $assumption_date; ?>" style="display:inline-block; width:auto; min-width:150px; border:none; border-bottom: 1px solid #000; background:transparent;">
                </div>
              </div>

              <!-- Inventory Table -->
              <div class="form-section">
                <h4 class="form-section-title">Inventory Items</h4>
                <div class="table-responsive">
                  <table class="rpci-table">
                    <thead>
                      <tr>
                        <th>Article</th>
                        <th>Description</th>
                        <th>Stock Number</th>
                        <th>Unit of Measure</th>
                        <th>Unit Value</th>
                        <th>Balance Per Card (Quantity)</th>
                        <th>On Hand Per Count (Quantity)</th>
                        <th>Shortage/Overage Quantity</th>
                        <th>Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($items as $item): ?>
                        <tr
                          data-category="<?php echo $item['categorie_id']; ?>"
                          data-date="<?php echo substr($item['date_added'], 0, 10); ?>"
                          data-fund-cluster="<?php echo $item['fund_cluster'] ?? ''; ?>">
                          <td><?php echo $item['id']; ?></td>
                          <td><?php echo $item['name']; ?></td>
                          <td><?php echo $item['stock_card']; ?></td>
                          <td><?php echo $item['UOM']; ?></td>
                          <td><?php echo $item['unit_cost']; ?></td>
                          <td><?php echo $item['quantity']; ?></td>
                          <td><?php echo $item['quantity']; ?></td>
                          <td><?php echo $item['quantity']; ?></td>
                          <td></td>
                        </tr>
                      <?php endforeach; ?>

                      <!-- Add empty rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                      <?php endfor; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Certifications Section -->
              <div class="form-section">
                <h4 class="form-section-title">Certifications</h4>
                <div class="signature-section">
                  <!-- Certified Correct by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Certified Correct by:</label>
                      <select class="form-control signature-select" name="certified_correct_by" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Inventory Committee Chair/Member</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['certified_correct_by']) && $_POST['certified_correct_by'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Inventory Committee Chair and Members</p>
                  </div>

                  <!-- Approved by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Approved by:</label>
                      <select class="form-control signature-select" name="approved_by" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Head of Agency/Entity</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['approved_by']) && $_POST['approved_by'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Head of Agency/Entity or Authorized Representative</p>
                  </div>

                  <!-- Verified by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Verified by:</label>
                      <select class="form-control signature-select" name="verified_by" style="height: 47px; font-size: 1rem;">
                        <option value="">Select COA Representative</option>
                        <?php
                        $coa_reps = find_by_sql("SELECT id, name FROM signatories WHERE position = 'COA Representative' ORDER BY name ASC");
                        foreach ($coa_reps as $rep) {
                          $selected = (isset($_POST['verified_by']) && $_POST['verified_by'] == $rep['id']) ? 'selected' : '';
                          echo "<option value=\"{$rep['id']}\" $selected>{$rep['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of COA Representative</p>
                  </div>
                </div>
              </div>

              <div class="btn-group">
                <button type="submit" name="add_inventory_item" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save Inventory Report
                </button>
                <button type="button" class="btn btn-secondary" id="print-report">
                  <i class="fas fa-print"></i> Print Report
                </button>
                <button type="reset" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Reset Form
                </button>
              </div>
            </form>
          </div>
        </div>





        <!-- Property, Plant & Equipment Tab -->
        <div id="property" class="tab-pane">
          <!-- RPCSPPE Form Section -->
          <div class="rpcppe-form-container">
            <div class="rpcppe-header">
              <h2 class="rpcppe-title">REPORT ON PHYSICAL COUNT OF PROPERTY, PLANT, AND EQUIPMENT (RPCSPPE)</h2>

            </div>

            <form method="post" action="" id="filter-form-ppe">
              <!-- Category and Date Selection -->
              <div class="form-section">
                <h4 class="form-section-title">Filter</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control filter-input" name="ppe_category_id" id="ppe_category_id" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Categories</option>
                      <?php
                      $ppe_categories = find_by_sql("SELECT id, subcategory_name FROM subcategories ORDER BY subcategory_name ASC");
                      foreach ($ppe_categories as $cat) {
                        $selected = (isset($_POST['subcategory_id']) && $_POST['subcategory_id'] == $cat['id']) ? 'selected' : '';
                        echo "<option value=\"{$cat['id']}\" $selected>{$cat['subcategory_name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">As at</label>
                    <input type="date" class="form-control filter-input" name="ppedate_added" id="ppedate_added" value="<?php echo isset($_POST['ppedate_added']) ? $_POST['ppedate_added'] : ''; ?>" style="border: none; background-color: #f8f9fa;">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Fund Cluster</label>
                    <select class="form-control filter-input" name="ppefund_cluster" id="ppefund_cluster" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Fund Clusters</option>
                      <?php
                      $ppefund_clusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
                      foreach ($ppefund_clusters as $cluster) {
                        $selected = (isset($_POST['fund_cluster']) && $_POST['fund_cluster'] == $cluster['name']) ? 'selected' : '';
                        echo "<option value=\"{$cluster['name']}\" $selected>{$cluster['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div style="margin-bottom: 15px; line-height: 1.8;">
                  <strong>For which:</strong>
                  <span class="underline" style="min-width: 180px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
                  <span class="underline" style="min-width: 150px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
                  is accountable, having assumed accountability on
                  <input type="date" class="form-control filter-input" name="assumption_date_ppe" id="assumption_date_ppe" value="<?php echo $assumption_date_ppe; ?>" style="display:inline-block; width:auto; min-width:150px; border:none; border-bottom: 1px solid #000; background:transparent;">
                </div>
              </div>

              <!-- Property, Plant & Equipment Table -->
              <div class="form-section">
                <h4 class="form-section-title">Property, Plant & Equipment Items</h4>
                <div class="table-responsive">
                  <table class="rpcppe-table">
                    <thead>
                      <tr>
                        <th>Date Acquired</th>
                        <th>Property Number</th>
                        <th>Unit</th>
                        <th>ARTICLE</th>
                        <th style="max-width:10%">Description</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                        <th>Quantity per Card</th>
                        <th>Quantity Per Physical Count</th>
                        <th>Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($props as $item): ?>
                        <tr
                          data-category-ppe="<?php echo $item['subcategory_id'] ?? ''; ?>"
                          data-date-ppe="<?php echo !empty($item['date_acquired']) ? substr($item['date_acquired'], 0, 10) : ''; ?>"
                          data-fund-cluster-ppe="<?php echo $item['fund_cluster'] ?? ''; ?>">

                          <td>
                            <?php
                            echo !empty($item['date_acquired'])
                              ? date('d-M-y', strtotime($item['date_acquired']))
                              : '-';
                            ?>
                          </td>
                          <td><?php echo $item['property_no']; ?></td>
                          <td><?php echo $item['unit']; ?></td>
                          <td><?php echo $item['article']; ?></td>
                          <td><?php echo $item['description']; ?></td>
                          <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                          <td>₱<?php echo number_format($item['unit_cost'] * $item['qty'], 2); ?></td>
                          <td>
                          <td>
                          <td><?php echo $item['remarks'] ?? ''; ?></td>
                        </tr>
                      <?php endforeach; ?>

                      <!-- Add empty rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                      <?php endfor; ?>

                      <!-- Total Row -->
                      <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL: </td>
                        <td>₱<?php
                              $total_amount = 0;
                              foreach ($semi_items as $item) {
                                $line_total = $item['unit_cost'] * $item['total_qty']; // or $item['total_qty']
                                $total_amount += $line_total;
                              }
                              echo number_format($total_amount, 2);
                              ?></td>

                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Multi-Signature Certifications Section -->
<div class="form-section">
    <h4 class="form-section-title">Certifications</h4>
    <div class="multi-signature-section">
        <!-- Left Column - Certified Correct (6 signatories) -->
        <div class="multi-signature-column">
            <div class="multi-signature-box">
                <div class="signature-field">
                    <label class="form-label">Certified Correct by:</label>
                </div>
                <div class="multi-signature-line"></div>
                <p class="multi-signature-caption">Signature over Printed Name of IC Chair and Members</p>
            </div>

            <!-- Certified Correct Signatory 1 -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="certified_correct_1_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Committee Member</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_1_ppe']) && $_POST['certified_correct_1_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>

            <!-- Certified Correct Signatory 2 -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="certified_correct_2_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Committee Member</option>
                        <?php
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_2_ppe']) && $_POST['certified_correct_2_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>

            <!-- Certified Correct Signatory 3 -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="certified_correct_3_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Committee Member</option>
                        <?php
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_3_ppe']) && $_POST['certified_correct_3_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>
        </div>

        <!-- Middle Column - Certified Correct (continued) -->
        <div class="multi-signature-column">
            <div class="multi-signature-box">
                <div class="signature-field">
                    <label class="form-label">Certified Correct by (cont.):</label>
                </div>
                <div class="multi-signature-line"></div>
                <p class="multi-signature-caption">Signature over Printed Name of IC Chair and Members</p>

            </div>

            <!-- Certified Correct Signatory 4 -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="certified_correct_4_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Committee Member</option>
                        <?php
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_4_ppe']) && $_POST['certified_correct_4_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>

            <!-- Certified Correct Signatory 5 -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="certified_correct_5_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Committee Vice Chair</option>
                        <?php
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_5_ppe']) && $_POST['certified_correct_5_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>

            <!-- Certified Correct Signatory 6 -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="certified_correct_6_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Committee Chair</option>
                        <?php
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['certified_correct_6_ppe']) && $_POST['certified_correct_6_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>
        </div>

        <!-- Right Column - Approved by and Verified by -->
        <div class="multi-signature-column">
            <!-- Approved by Section -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <label class="form-label">Approved by:</label>
                </div>
                <!-- <div class="multi-signature-line"></div> -->
                <p class="multi-signature-caption">Signature over Printed Name of Head of Agency/Entity</p>
            </div>

            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="approved_by_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select Head of Agency</option>
                        <?php
                        foreach ($signatories as $sign) {
                            $selected = (isset($_POST['approved_by_ppe']) && $_POST['approved_by_ppe'] == $sign['id']) ? 'selected' : '';
                            echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>

            <!-- Spacer between sections -->

            <!-- Verified by Section -->
            <div class="multi-signature-box">
                <div class="signature-field">
                    <label class="form-label">Verified by:</label>
                </div>
                <!-- <div class="multi-signature-line"></div> -->
                <p class="multi-signature-caption">Signature over Printed Name of COA Representative</p>
            </div>

            <div class="multi-signature-box">
                <div class="signature-field">
                    <select class="form-control signature-select" name="verified_by_ppe" style="height: 47px; font-size: 1rem; margin-bottom: 5px;">
                        <option value="">Select COA Representative</option>
                        <?php
                        $coa_reps = find_by_sql("SELECT id, name FROM signatories WHERE position = 'COA Representative' ORDER BY name ASC");
                        foreach ($coa_reps as $rep) {
                            $selected = (isset($_POST['verified_by_ppe']) && $_POST['verified_by_ppe'] == $rep['id']) ? 'selected' : '';
                            echo "<option value=\"{$rep['id']}\" $selected>{$rep['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="multi-signature-line"></div>
            </div>
        </div>
    </div>
</div>

              <div class="btn-group">
                <button type="submit" name="add_ppe_item" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save PPE Report
                </button>
                <button type="button" class="btn btn-secondary" id="print-report-ppe">
                  <i class="fas fa-print"></i> Print Report
                </button>
                <button type="reset" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Reset Form
                </button>
              </div>
            </form>
          </div>
        </div>


        <!-- Semi-Expendable Property Tab -->
        <div id="semi-expendable" class="tab-pane">
          <!-- RPCSP Form Section -->
          <div class="rpcsp-form-container">
            <div class="rpcsp-header">
              <h2 class="rpcsp-title">REPORT ON THE PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY (RPCSP)</h2>
            </div>

            <form method="post" action="" id="filter-form-semi-expendable">
              <!-- Category and Date Selection -->
              <div class="form-section">
                <h4 class="form-section-title">Filter</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Subcategory</label>
                    <select class="form-control filter-input" name="semicategory_id" id="semicategory_id" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Subcategories</option>
                      <?php
                      $subcategories = find_by_sql("SELECT id, semicategory_name FROM semicategories ORDER BY semicategory_name ASC");
                      foreach ($subcategories as $subcat) {
                        $selected = (isset($_POST['semicategory_id']) && $_POST['semicategory_id'] == $subcat['id']) ? 'selected' : '';
                        echo "<option value=\"{$subcat['id']}\" $selected>{$subcat['semicategory_name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">As at</label>
                    <input type="date" class="form-control filter-input" name="smpdate_added" id="smpdate_added" value="<?php echo isset($_POST['smpdate_added']) ? $_POST['smpdate_added'] : ''; ?>" style="border: none; background-color: #f8f9fa;">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Fund Cluster</label>
                    <select class="form-control filter-input" name="smpfund_cluster" id="smpfund_cluster" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Fund Clusters</option>
                      <?php
                      $smpclusters = find_by_sql("SELECT id, name FROM fund_clusters ORDER BY name ASC");
                      foreach ($smpclusters as $smpcluster) {
                        $selected = (isset($_POST['smpfund_cluster']) && $_POST['smpfund_cluster'] == $smpcluster['name']) ? 'selected' : '';
                        echo "<option value=\"{$smpcluster['name']}\" $selected>{$smpcluster['name']}</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Value Type</label>
                    <select class="form-control filter-input" name="value_type" id="value_type" style="height: 47px; font-size: 1rem; border: none; background-color: #f8f9fa;">
                      <option value="">All Values</option>
                      <option value="low" <?php echo (isset($_POST['value_type']) && $_POST['value_type'] == 'low') ? 'selected' : ''; ?>>Low Value (Below ₱5,000)</option>
                      <option value="high" <?php echo (isset($_POST['value_type']) && $_POST['value_type'] == 'high') ? 'selected' : ''; ?>>High Value (₱5,000 - ₱50,000)</option>
                    </select>
                  </div>
                </div>
                <div style="margin-bottom: 15px; line-height: 1.8;">
                  <strong>For which</strong>
                  <span class="underline" style="min-width: 180px; margin-left: 5px;"><?php echo $current_user_name; ?></span>,
                  <span class="underline" style="min-width: 150px; margin-left: 5px;"><?php echo $current_user_position; ?></span>,
                  BSU-BOKOD CAMPUS is accountable, having assumed such accountability on
                  <input type="date" class="form-control filter-input" name="assumption_date_semi" id="assumption_date_semi" value="<?php echo $assumption_date_semi; ?>" style="display:inline-block; width:auto; min-width:150px; border:none; border-bottom: 1px solid #000; background:transparent;">
                </div>
              </div>

              <!-- Semi-Expendable Property Table -->
              <div class="form-section">
                <h4 class="form-section-title">Semi-Expendable Property Items</h4>
                <div class="table-responsive">
                  <table class="rpcsp-table">
                    <thead>
                      <tr>
                        <th rowspan="2">ARTICLE</th>
                        <th rowspan="2">Description</th>
                        <th rowspan="2">Semi-expendable Property No.</th>
                        <th rowspan="2">Unit of Measure</th>
                        <th rowspan="2">Unit Value</th>
                        <th colspan="2">Balance per Card</th>
                        <th colspan="2">On Hand Per Count</th>
                        <th colspan="2">Shortage/Overage</th>
                        <th rowspan="2">Remarks</th>
                      </tr>
                      <tr>
                        <th>Qty</th>
                        <th>Value</th>
                        <th>Qty</th>
                        <th>Value</th>
                        <th>Qty</th>
                        <th>Value</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($semi_items as $item): ?>
                        <tr
                          data-category-smp="<?php echo $item['semicategory_id']; ?>"
                          data-date-smp="<?php echo substr($item['date_added'], 0, 10); ?>"
                          data-fund-cluster-smp="<?php echo $item['fund_cluster'] ?? ''; ?>"
                          data-value-type="<?php echo ($item['unit_cost'] < 5000) ? 'low' : (($item['unit_cost'] >= 5000 && $item['unit_cost'] < 50000) ? 'high' : ''); ?>">
                          <td><?php echo $item['id']; ?></td>
                          <td><?php echo $item['item_description']; ?></td>
                          <td><?php echo $item['inv_item_no']; ?></td>
                          <td><?php echo $item['unit']; ?></td>
                          <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                          <td><?php echo $item['total_qty']; ?></td>
                          <td>₱<?php echo number_format($item['total_qty'] * $item['unit_cost'], 2); ?></td>
                          <td><?php echo $item['qty_left']; ?></td>
                          <td>₱<?php echo number_format($item['total_qty'] * $item['unit_cost'], 2); ?></td>
                          <td>0</td>
                          <td>₱0.00</td>
                          <td></td>
                        </tr>
                      <?php endforeach; ?>

                      <!-- Add empty rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                          <td>&nbsp;</td>
                        </tr>
                      <?php endfor; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Certifications Section -->
              <div class="form-section">
                <h4 class="form-section-title">Certifications</h4>
                <div class="signature-section">
                  <!-- Certified Correct by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Certified Correct by:</label>
                      <select class="form-control signature-select" name="certified_correct_by_semi" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Inventory Committee Chair/Member</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['certified_correct_by_semi']) && $_POST['certified_correct_by_semi'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Inventory Committee Chair and Members</p>
                  </div>

                  <!-- Approved by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Approved by:</label>
                      <select class="form-control signature-select" name="approved_by_semi" style="height: 47px; font-size: 1rem;">
                        <option value="">Select Head of Agency/Entity</option>
                        <?php
                        $signatories = find_by_sql("SELECT id, name FROM signatories ORDER BY name ASC");
                        foreach ($signatories as $sign) {
                          $selected = (isset($_POST['approved_by_semi']) && $_POST['approved_by_semi'] == $sign['id']) ? 'selected' : '';
                          echo "<option value=\"{$sign['id']}\" $selected>{$sign['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of Head of Agency/Entity or Authorized Representative</p>
                  </div>

                  <!-- Witnessed by -->
                  <div class="signature-box">
                    <div class="signature-field">
                      <label class="form-label">Witnessed by:</label>
                      <select class="form-control signature-select" name="witnessed_by_semi" style="height: 47px; font-size: 1rem;">
                        <option value="">Select COA Representative</option>
                        <?php
                        $coa_reps = find_by_sql("SELECT id, name FROM signatories WHERE position = 'COA Representative' ORDER BY name ASC");
                        foreach ($coa_reps as $rep) {
                          $selected = (isset($_POST['witnessed_by_semi']) && $_POST['witnessed_by_semi'] == $rep['id']) ? 'selected' : '';
                          echo "<option value=\"{$rep['id']}\" $selected>{$rep['name']}</option>";
                        }
                        ?>
                      </select>
                    </div>
                    <div class="signature-line"></div>
                    <p class="signature-caption">Signature over Printed Name of COA Representative</p>
                  </div>
                </div>
              </div>

              <div class="btn-group">
                <button type="submit" name="add_semi_expendable_item" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save Semi-Expendable Report
                </button>
                <button type="button" class="btn btn-secondary" id="print-report-semi">
                  <i class="fas fa-print"></i> Print Report
                </button>
                <button type="reset" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Reset Form
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

    <!-- Hidden div for print preview -->
    <div id="print-preview" style="display:none;"></div>

    <?php include_once('layouts/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      // For Inventories tab
      document.getElementById('print-report').addEventListener('click', function() {
        const categorySelect = document.querySelector('select[name="categorie_id"]');
        const dateInput = document.querySelector('input[name="date_added"]');
        const fundClusterSelect = document.querySelector('select[name="fund_cluster"]');
        const assumptionDateInput = document.querySelector('input[name="assumption_date"]');
        const certifiedCorrect = document.querySelector('select[name="certified_correct_by"]');
        const approvedBy = document.querySelector('select[name="approved_by"]');
        const verifiedBy = document.querySelector('select[name="verified_by"]');

        // Validation
        if (!categorySelect.value) {
          Swal.fire('Missing Category', 'Please select a category before printing.', 'warning');
          return;
        }
        if (!dateInput.value) {
          Swal.fire('Missing Date', 'Please select a date before printing.', 'warning');
          return;
        }
        if (!fundClusterSelect.value) {
          Swal.fire('Missing Fund Cluster', 'Please select a fund cluster before printing.', 'warning');
          return;
        }
        if (!assumptionDateInput.value) {
          Swal.fire('Missing Assumption Date', 'Please select an assumption date before printing.', 'warning');
          return;
        }
        if (!certifiedCorrect.value || !approvedBy.value || !verifiedBy.value) {
          Swal.fire('Missing Certifications', 'Please select all required certifications before printing the report.', 'warning');
          return;
        }

        // Submit form to printable page
        const form = document.getElementById('filter-form');
        form.target = '_blank';
        form.action = 'rpci_print.php';
        form.submit();
      });

      // For Property, Plant & Equipment tab
      document.getElementById('print-report-ppe').addEventListener('click', function() {
        const categorySelect = document.querySelector('select[name="ppe_category_id"]');
        const dateInput = document.querySelector('input[name="ppedate_added"]');
        const fundClusterSelect = document.querySelector('select[name="ppefund_cluster"]');
        const assumptionDateInput = document.querySelector('input[name="assumption_date_ppe"]');

        // Validation
        if (!categorySelect.value) {
          Swal.fire('Missing Category', 'Please select a category before printing.', 'warning');
          return;
        }
        if (!dateInput.value) {
          Swal.fire('Missing Date', 'Please select a date before printing.', 'warning');
          return;
        }
        if (!fundClusterSelect.value) {
          Swal.fire('Missing Fund Cluster', 'Please select a fund cluster before printing.', 'warning');
          return;
        }
        if (!assumptionDateInput.value) {
          Swal.fire('Missing Assumption Date', 'Please select an assumption date before printing.', 'warning');
          return;
        }

        // Submit form to printable page
        const form = document.getElementById('filter-form-ppe');
        form.target = '_blank';
        form.action = 'rpcppe_print.php';
        form.submit();
      });

      // For Semi-Expendable Property tab
      document.getElementById('print-report-semi').addEventListener('click', function() {
        const subcategorySelect = document.querySelector('select[name="subcategory_id"]');
        const dateInput = document.querySelector('input[name="smpdate_added"]');
        const fundClusterSelect = document.querySelector('select[name="smpfund_cluster"]');
        const assumptionDateInput = document.querySelector('input[name="assumption_date_semi"]');
        const certifiedCorrect = document.querySelector('select[name="certified_correct_by_semi"]');
        const approvedBy = document.querySelector('select[name="approved_by_semi"]');
        const witnessedBy = document.querySelector('select[name="witnessed_by_semi"]');

        // Validation
        if (!subcategorySelect.value) {
          Swal.fire('Missing Subcategory', 'Please select a subcategory before printing.', 'warning');
          return;
        }
        if (!dateInput.value) {
          Swal.fire('Missing Date', 'Please select a date before printing.', 'warning');
          return;
        }
        if (!fundClusterSelect.value) {
          Swal.fire('Missing Fund Cluster', 'Please select a fund cluster before printing.', 'warning');
          return;
        }
        if (!assumptionDateInput.value) {
          Swal.fire('Missing Assumption Date', 'Please select an assumption date before printing.', 'warning');
          return;
        }
        if (!certifiedCorrect.value || !approvedBy.value || !witnessedBy.value) {
          Swal.fire('Missing Certifications', 'Please select all required certifications before printing the report.', 'warning');
          return;
        }

        // Submit form to printable page
        const form = document.getElementById('filter-form-semi-expendable');
        form.target = '_blank';
        form.action = 'rpcsp_print.php';
        form.submit();
      });

      // Tab switching functionality
      document.addEventListener('DOMContentLoaded', function() {
        const tabLinks = document.querySelectorAll('.nav-tab-link');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabLinks.forEach(link => {
          link.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all tabs and panes
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            const tabId = this.getAttribute('href');
            document.querySelector(tabId).classList.add('active');
          });
        });

        // Filter functionality for inventories tab
        const categorySelect = document.querySelector('select[name="categorie_id"]');
        const dateInput = document.querySelector('input[name="date_added"]');
        const fundClusterSelect = document.querySelector('select[name="fund_cluster"]');
        const tableRows = document.querySelectorAll('.rpci-table tbody tr');

        function filterTable() {
          const selectedCategory = categorySelect.value;
          const selectedDate = dateInput.value;
          const selectedFundCluster = fundClusterSelect.value;

          tableRows.forEach(row => {
            const rowCategory = row.getAttribute('data-category');
            const rowDate = row.getAttribute('data-date');
            const rowFundCluster = row.getAttribute('data-fund-cluster');
            let show = true;

            // Filter by category
            if (selectedCategory && selectedCategory !== rowCategory) {
              show = false;
            }

            // Filter by date - show items with date <= selected date
            if (selectedDate && rowDate > selectedDate) {
              show = false;
            }

            // Filter by fund cluster
            if (selectedFundCluster && selectedFundCluster !== rowFundCluster) {
              show = false;
            }

            row.style.display = show ? '' : 'none';
          });
        }

        // Add event listeners to all filter inputs
        if (categorySelect) categorySelect.addEventListener('change', filterTable);
        if (dateInput) dateInput.addEventListener('change', filterTable);
        if (fundClusterSelect) fundClusterSelect.addEventListener('change', filterTable);

        // Initial filter on page load
        if (tableRows.length > 0) filterTable();

        // Filter functionality for semi-expendable tab
        const subcategorySelectSemi = document.querySelector('select[name="subcategory_id"]');
        const dateInputSemi = document.querySelector('input[name="smpdate_added"]');
        const fundClusterSelectSemi = document.querySelector('select[name="smpfund_cluster"]');
        const valueTypeSelectSemi = document.querySelector('select[name="value_type"]');
        const tableRowsSemi = document.querySelectorAll('#semi-expendable .rpcsp-table tbody tr');

        function filterTableSemi() {
          const selectedSubcategory = subcategorySelectSemi.value;
          const selectedDate = dateInputSemi.value;
          const selectedFundCluster = fundClusterSelectSemi.value;
          const selectedValueType = valueTypeSelectSemi.value;

          tableRowsSemi.forEach(row => {
            const rowSubcategory = row.getAttribute('data-category-smp');
            const rowDate = row.getAttribute('data-date-smp');
            const rowFundCluster = row.getAttribute('data-fund-cluster-smp');
            const rowValueType = row.getAttribute('data-value-type');
            let show = true;

            // Filter by subcategory
            if (selectedSubcategory && selectedSubcategory !== rowSubcategory) {
              show = false;
            }

            // Filter by date - show items with date <= selected date
            if (selectedDate && rowDate > selectedDate) {
              show = false;
            }

            // Filter by fund cluster
            if (selectedFundCluster && selectedFundCluster !== rowFundCluster) {
              show = false;
            }

            // Filter by value type
            if (selectedValueType && selectedValueType !== rowValueType) {
              show = false;
            }

            row.style.display = show ? '' : 'none';
          });
        }

        // Add event listeners to all filter inputs
        if (subcategorySelectSemi) subcategorySelectSemi.addEventListener('change', filterTableSemi);
        if (dateInputSemi) dateInputSemi.addEventListener('change', filterTableSemi);
        if (fundClusterSelectSemi) fundClusterSelectSemi.addEventListener('change', filterTableSemi);
        if (valueTypeSelectSemi) valueTypeSelectSemi.addEventListener('change', filterTableSemi);

        // Initial filter on page load
        if (tableRowsSemi.length > 0) filterTableSemi();

        // Filter functionality for PPE tab
        const categorySelectPPE = document.querySelector('select[name="ppe_category_id"]');
        const dateInputPPE = document.querySelector('input[name="ppedate_added"]');
        const fundClusterSelectPPE = document.querySelector('select[name="ppefund_cluster"]');
        const tableRowsPPE = document.querySelectorAll('#property .rpcppe-table tbody tr:not(:last-child)');

        function filterTablePPE() {
          const selectedCategory = categorySelectPPE.value;
          const selectedDate = dateInputPPE.value;
          const selectedFundCluster = fundClusterSelectPPE.value;

          tableRowsPPE.forEach(row => {
            const rowCategory = row.getAttribute('data-category-ppe');
            const rowDate = row.getAttribute('data-date-ppe');
            const rowFundCluster = row.getAttribute('data-fund-cluster-ppe');
            let show = true;

            // Filter by category
            if (selectedCategory && selectedCategory !== rowCategory) {
              show = false;
            }

            // Filter by date - show items with date <= selected date
            if (selectedDate && rowDate > selectedDate) {
              show = false;
            }

            // Filter by fund cluster
            if (selectedFundCluster && selectedFundCluster !== rowFundCluster) {
              show = false;
            }

            row.style.display = show ? '' : 'none';
          });
        }

        // Add event listeners to all filter inputs
        if (categorySelectPPE) categorySelectPPE.addEventListener('change', filterTablePPE);
        if (dateInputPPE) dateInputPPE.addEventListener('change', filterTablePPE);
        if (fundClusterSelectPPE) fundClusterSelectPPE.addEventListener('change', filterTablePPE);

        // Initial filter on page load
        if (tableRowsPPE.length > 0) filterTablePPE();
      });
    </script>