<?php
$page_title = 'All Categories';
require_once('includes/load.php');
page_require_level(1);


// Get current active tab from session or default to 'stock-categories'
$active_tab = isset($_SESSION['active_cat_tab']) ? $_SESSION['active_cat_tab'] : 'stock-categories';

// Update active tab when a tab is clicked (via AJAX or form submission)
if (isset($_POST['active_tab'])) {
    $_SESSION['active_cat_tab'] = $_POST['active_tab'];
    $active_tab = $_SESSION['active_cat_tab'];
} elseif (isset($_GET['tab'])) {
    $_SESSION['active_cat_tab'] = $_GET['tab'];
    $active_tab = $_SESSION['active_cat_tab'];
}

$edit_mode = false;
$edit_cat = null;

// If editing (load category into form)
if (isset($_GET['edit'])) {
  $cat_id = (int)$_GET['edit'];
  $edit_cat = find_by_id('categories', $cat_id);
  if ($edit_cat) {
    $edit_mode = true;
  } else {
    $session->msg("d", "Category not found");
    redirect('cat.php');
  }
}

// Add new category
if (isset($_POST['add_cat'])) {
  $req_field = array('categorie-name');
  validate_fields($req_field);

  $cat_name   = remove_junk($db->escape($_POST['categorie-name']));
  // Object code is not required for on-hand stock categories

  if (empty($errors)) {
      if (is_duplicate_category($cat_name)) {
          $session->msg("d", "Category name '{$cat_name}' already exists.");
      } else {
          $sql = "INSERT INTO categories (name) VALUES ('{$cat_name}')";
          if ($db->query($sql)) {
              $session->msg("s", "Successfully Added New Category");
          } else {
              $session->msg("d", "Sorry Failed to insert.");
          }
      }
  } else {
      $session->msg("d", $errors);
  }
  redirect('cat.php', false);
}

// Update category
if (isset($_POST['update_cat'])) {
  $req_field = array('categorie-name', 'cat_id');
  validate_fields($req_field);

  $cat_name   = remove_junk($db->escape($_POST['categorie-name']));
  $cat_id     = (int)$_POST['cat_id'];

  if (empty($errors)) {
      if (is_duplicate_category($cat_name, $cat_id)) {
          $session->msg("d", "Category name '{$cat_name}' already exists.");
      } else {
          $sql = "UPDATE categories 
                  SET name='{$cat_name}' 
                  WHERE id='{$cat_id}'";
          $result = $db->query($sql);
          if ($result && $db->affected_rows() === 1) {
              $session->msg("s", "Category updated successfully");
          } else {
              $session->msg("d", "Update failed or no changes made");
          }
      }
  } else {
      $session->msg("d", $errors);
  }
  redirect('cat.php', false);
}

// Update Account Title and Subcategories
if (isset($_POST['update_account_title'])) {
  $account_title_id = (int)$_POST['account_title_id'];
  $category_name = remove_junk($db->escape($_POST['category_name']));
  
  // Update main category
  $sql = "UPDATE account_title SET category_name='{$category_name}' WHERE id='{$account_title_id}'";
  if ($db->query($sql)) {
      
      // Update existing subcategories and add new ones
      if (isset($_POST['subcategories'])) {
          foreach ($_POST['subcategories'] as $subcat) {
              $subcat_id = isset($subcat['id']) ? (int)$subcat['id'] : 0;
              $subcat_name = remove_junk($db->escape($subcat['name']));
              $uacs_code = remove_junk($db->escape($subcat['uacs']));
              
              if ($subcat_id > 0) {
                  // Update existing subcategory
                  $sql = "UPDATE subcategories 
                          SET subcategory_name='{$subcat_name}', uacs_code='{$uacs_code}' 
                          WHERE id='{$subcat_id}' AND account_title_id='{$account_title_id}'";
              } else {
                  // Insert new subcategory
                  $sql = "INSERT INTO subcategories (account_title_id, subcategory_name, uacs_code) 
                          VALUES ('{$account_title_id}', '{$subcat_name}', '{$uacs_code}')";
              }
              $db->query($sql);
          }
      }
      
      $session->msg("s", "Successfully Updated Category and Subcategories");
  } else {
      $session->msg("d", "Failed to update category");
  }
  redirect('cat.php', false);
}

// Fetch account title and subcategories for editing
$edit_account_title = null;
$edit_subcategories = array();
if (isset($_GET['edit_account'])) {
  $account_id = (int)$_GET['edit_account'];
  
  // Get main category
  $edit_account_title = find_by_id('account_title', $account_id);
  if ($edit_account_title) {
      // Get subcategories
      $edit_subcategories = find_by_sql("
          SELECT * FROM subcategories 
          WHERE account_title_id = '{$account_id}' 
          ORDER BY id ASC
      ");
  }
}



// Handle Account Title and Subcategories submission
if (isset($_POST['save_account_title'])) {
  $category_name = remove_junk($db->escape($_POST['category_name']));
  
  // Insert main category
  $sql = "INSERT INTO account_title (category_name) VALUES ('{$category_name}')";
  if ($db->query($sql)) {
      $account_title_id = $db->insert_id();
      
      // Insert subcategories
      if (isset($_POST['subcategories'])) {
          foreach ($_POST['subcategories'] as $subcat) {
              $subcat_name = remove_junk($db->escape($subcat['name']));
              $uacs_code = remove_junk($db->escape($subcat['uacs']));
              
              $sql = "INSERT INTO subcategories (account_title_id, subcategory_name, uacs_code) 
                      VALUES ('{$account_title_id}', '{$subcat_name}', '{$uacs_code}')";
              $db->query($sql);
          }
      }
      
      $session->msg("s", "Successfully Added Category and Subcategories");
  } else {
      $session->msg("d", "Failed to add category");
  }
  redirect('cat.php', false);
}

// Helper: check duplicates
function is_duplicate_category($cat_name, $exclude_id = null) {
  global $db;
  $cat_name = remove_junk($db->escape($cat_name));
  $sql = "SELECT id FROM categories WHERE name = '{$cat_name}'";
  if ($exclude_id) {
      $exclude_id = (int)$exclude_id;
      $sql .= " AND id != '{$exclude_id}'";
  }
  $result = $db->query($sql);
  return ($db->num_rows($result) > 0);
}

// Fetch all account titles for dropdown
$account_titles = find_by_sql("SELECT * FROM account_title ORDER BY category_name ASC");

// Fetch subcategories with their parent category
$semi_categories = find_by_sql("
  SELECT s.id, s.subcategory_name, s.uacs_code, a.category_name, a.id as account_title_id
  FROM subcategories s
  JOIN account_title a ON s.account_title_id = a.id
  ORDER BY a.category_name, s.subcategory_name
");


// Handle Semi-Expendable Categories
if (isset($_POST['add_semi_category'])) {
    $req_field = array('semicategory_name', 'uacs');
    validate_fields($req_field);

    $semi_cat_name = remove_junk($db->escape($_POST['semicategory_name']));
    $semi_cat_code = (int)($_POST['uacs'] ?? 0);

    if (empty($errors)) {
        if (is_duplicate_semi_category($semi_cat_name)) {
            $session->msg("d", "Semi-expendable category name '{$semi_cat_name}' already exists.");
        } else {
            $sql = "INSERT INTO semicategories (semicategory_name, uacs) 
                    VALUES ('{$semi_cat_name}', '{$semi_cat_code}')";
            if ($db->query($sql)) {
                $session->msg("s", "Successfully Added New Semi-Expendable Category");
            } else {
                $session->msg("d", "Sorry, Failed to insert.");
            }
        }
    } else {
        $session->msg("d", $errors);
    }
    redirect('cat.php', false);
}

// Update semi-expendable category
if (isset($_POST['update_semi_category'])) {
    $req_field = array('semicategory_name', 'uacs', 'semi_cat_id');
    validate_fields($req_field);

    $semi_cat_name = remove_junk($db->escape($_POST['semicategory_name']));
    $semi_cat_code = (int)($_POST['uacs'] ?? 0);
    $semi_cat_id   = (int)$_POST['semi_cat_id'];

    if (empty($errors)) {
        if (is_duplicate_semi_category($semi_cat_name, $semi_cat_id)) {
            $session->msg("d", "Semi-expendable category name '{$semi_cat_name}' already exists.");
        } else {
            $sql = "UPDATE semicategories 
                    SET semicategory_name = '{$semi_cat_name}', uacs = '{$semi_cat_code}'
                    WHERE id = '{$semi_cat_id}'";
            $result = $db->query($sql);
            if ($result && $db->affected_rows() === 1) {
                $session->msg("s", "Semi-expendable category updated successfully");
            } else {
                $session->msg("d", "Update failed or no changes made");
            }
        }
    } else {
        $session->msg("d", $errors);
    }
    redirect('cat.php', false);
}

// Fetch semi-expendable category for editing
$edit_semi_mode = false;
$edit_semi_cat = null;
if (isset($_GET['edit_semi'])) {
    $semi_cat_id = (int)$_GET['edit_semi'];
    $edit_semi_cat = find_by_id('semicategories', $semi_cat_id);
    if ($edit_semi_cat) {
        $edit_semi_mode = true;
    } else {
        $session->msg("d", "Semi-expendable category not found");
        redirect('cat.php');
    }
}

// Helper: check duplicates for semi-expendable categories
function is_duplicate_semi_category($cat_name, $exclude_id = null) {
    global $db;
    $cat_name = remove_junk($db->escape($cat_name));
    $sql = "SELECT id FROM semicategories WHERE semicategory_name = '{$cat_name}'";
    if ($exclude_id) {
        $exclude_id = (int)$exclude_id;
        $sql .= " AND id != '{$exclude_id}'";
    }
    $result = $db->query($sql);
    return ($db->num_rows($result) > 0);
}

// Fetch all semi-expendable categories
$semi_expendable_categories = find_all('semicategories');

$all_categories = find_all('categories');
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

.form-control:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.table-custom {
  border-radius: var(--border-radius);
  overflow: hidden;
  box-shadow: var(--shadow);
}

.table-custom thead {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.table-custom th {
  border: none;
  font-weight: 600;
  padding: 1rem;
}

.table-custom td {
  padding: 1rem;
  vertical-align: middle;
}

.subcategory-row {
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  transition: background-color 0.3s ease;
}

.subcategory-row:hover {
  background-color: #f8f9fa;
}

.toggle-btn {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  border-radius: 50px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.toggle-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  color: white;
}

.form-section {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

/* Tabs Styling */
.nav-tabs-custom {
  display: flex;
  flex-wrap: wrap;
  border-bottom: 2px solid #e9ecef;
  padding: 0;
  margin: 0 0 2rem 0;
}

.nav-tab-item {
  flex: 1;
  min-width: 200px;
  text-align: center;
}

.nav-tab-link {
  display: block;
  padding: 1rem 1.5rem;
  background-color: #f8f9fa;
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
  background-color: white;
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
  padding: 0;
  background: white;
}

.tab-pane {
  display: none;
  animation: fadeIn 0.5s ease;
}

.tab-pane.active {
  display: block;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-header-custom {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border-bottom: none;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-title-custom {
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .card-header-custom {
    padding: 1rem;
  }
  
  .btn-group-custom {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .btn-group-custom .btn {
    width: 100%;
  }
  
  .nav-tabs-custom {
    flex-direction: column;
  }
  
  .nav-tab-item {
    min-width: 100%;
  }
}

/* Modal Header Styles */
.modal-header-custom {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border-bottom: none;
  border-radius: 12px 12px 0 0;
  padding: 1.5rem 2rem;
  position: relative;
}

.modal-header-custom::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 2rem;
  right: 2rem;
  height: 1px;
  background: rgba(255,255,255,0.2);
}

.modal-header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.modal-icon {
  width: 50px;
  height: 50px;
  background: rgba(255,255,255,0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.modal-title-section {
  flex: 1;
}

.modal-title-custom {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0;
  color: white;
}

.modal-subtitle {
  font-size: 0.9rem;
  opacity: 0.9;
  margin: 0.25rem 0 0 0;
}

/* Modal Body Styles */
.modal-body {
  padding: 0 !important;
}

.form-section-main,
.form-section-subcategories {
  padding: 2rem;
}

.form-section-main {
  border-bottom: 1px solid #e9ecef;
  background: #fafafa;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.section-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
}

.section-title {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.section-title h6 {
  margin: 0;
  font-weight: 600;
  color: var(--dark);
  font-size: 1.1rem;
}

.section-badge {
  background: var(--primary-light);
  color: var(--primary-dark);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.form-group-main {
  margin-bottom: 0;
}

.form-label {
  font-weight: 600;
  color: var(--dark);
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
}

.form-control-lg {
  padding: 0.75rem 1rem;
  font-size: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.form-control-lg:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
}

.form-hint {
  font-size: 0.8rem;
  color: var(--secondary);
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Subcategories Styles */
.form-section-subcategories {
  background: white;
}

.subcategory-row {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  transition: all 0.3s ease;
  position: relative;
}

.subcategory-row:hover {
  border-color: var(--primary-light);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.subcategory-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #dee2e6;
}

.subcategory-number {
  font-weight: 600;
  color: var(--primary);
  font-size: 0.9rem;
}

.remove-row {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 1px solid var(--danger);
  color: var(--danger);
  background: white;
}

.remove-row:not(:disabled):hover {
  background: var(--danger);
  color: white;
  transform: scale(1.05);
}

.remove-row:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Add More Section */
.add-more-section {
  text-align: center;
  padding: 1rem 0;
}

.btn-add-more {
  border: 2px dashed #dee2e6;
  background: white;
  color: var(--primary);
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-add-more:hover {
  border-color: var(--primary);
  background: var(--primary-light);
  color: var(--primary-dark);
  transform: translateY(-1px);
}

.add-more-hint {
  font-size: 0.8rem;
  color: var(--secondary);
  margin-top: 0.5rem;
}

/* Modal Footer Styles */
.modal-footer {
  border-top: 1px solid #e9ecef;
  padding: 1.5rem 2rem;
  background: #f8f9fa;
  border-radius: 0 0 12px 12px;
}

.footer-actions {
  display: flex;
  gap: 1rem;
  width: 100%;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  flex: 1;
}

.btn-save {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  flex: 2;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
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


.btn-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-header-content {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .modal-icon {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
  }
  
  .form-section-main,
  .form-section-subcategories {
    padding: 1.5rem;
  }
  
  .footer-actions {
    flex-direction: column;
  }
  
  .btn-cancel,
  .btn-save {
    flex: 1;
    width: 100%;
  }
  
  .section-header {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .section-title {
    flex-direction: column;
    gap: 0.5rem;
  }
}

/* Animation for new rows */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.subcategory-row.new-row {
  animation: slideIn 0.3s ease-out;
}
</style>
<div class="container-fluid">
  <!-- Page Header -->
  <div class="card-header-custom">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <h4 class="page-title">Categories Management</h4>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
          <li class="breadcrumb-item active" aria-current="page">Categories</li>
        </ol>
      </nav>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <div class="tabs-container">
    <ul class="nav-tabs-custom" id="categoriesTabs">
      <li class="nav-tab-item">
        <a href="#stock-categories" class="nav-tab-link <?php echo $active_tab === 'stock-categories' ? 'active' : ''; ?>" data-tab="stock-categories">
          <i class="fas fa-boxes tab-icon"></i> On-hand Stock Categories
        </a>
      </li>
      <li class="nav-tab-item">
        <a href="#semi-expendable" class="nav-tab-link <?php echo $active_tab === 'semi-expendable' ? 'active' : ''; ?>" data-tab="semi-expendable">
          <i class="fas fa-tools tab-icon"></i> Semi-Expendable Property
        </a>
      </li>
      <li class="nav-tab-item">
        <a href="#properties" class="nav-tab-link <?php echo $active_tab === 'properties' ? 'active' : ''; ?>" data-tab="properties">
          <i class="nav-icon fa-solid fa-building"></i> Property, Plant & Equipments
        </a>
      </li>
    </ul>
    
    <div class="tab-content">
      <!-- Stock Categories Tab -->
      <div id="stock-categories" class="tab-pane <?php echo $active_tab === 'stock-categories' ? 'active' : ''; ?>">
        <!-- Add/Edit Form -->
        <div class="form-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
              <i class="fas fa-boxes me-2 text-success"></i>
              Manage On-hand Stock Categories
            </h5>
          </div>
          
          <form method="post" action="cat.php" class="row g-3" id="stockCategoryForm">
            <input type="hidden" name="active_tab" value="stock-categories">
            <div class="col-12 col-md-8">
              <input type="text" class="form-control" 
                     name="categorie-name" 
                     placeholder="Category Name" 
                     value="<?php echo $edit_mode ? remove_junk($edit_cat['name']) : ''; ?>" 
                     required>
            </div>       
            <div class="col-12 col-md-4">
              <div class="d-grid gap-2 d-md-flex">
                <?php if ($edit_mode): ?>
                  <input type="hidden" name="cat_id" value="<?php echo (int)$edit_cat['id']; ?>">
                  <button type="submit" name="update_cat" class="btn btn-warning-custom me-2 flex-fill">
                    <i class="fas fa-save me-1"></i> Update
                  </button>
                  <a href="cat.php?tab=stock-categories" class="btn btn-secondary flex-fill">
                    <i class="fas fa-times me-1"></i> Cancel
                  </a>
                <?php else: ?>
                  <button type="submit" name="add_cat" class="btn btn-primary-custom flex-fill">
                    <i class="fas fa-plus me-1"></i> Add Category
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>

        <!-- Categories Table -->
        <div class="form-section">
          <table class="table table-custom">
            <thead class="text-center">
              <tr>
                <th width="10%">No.</th>
                <th>Category Name</th>
                <th width="20%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_categories as $i=>$cat): ?>
                <tr>
                   <td><span class="badge badge-custom badge-primary"><?= $i+1 ?></span></td>
                  <td><?php echo remove_junk(ucfirst($cat['name'])); ?></td>
                  <td class="text-center">
                    <div class="btn-group btn-group-custom">
                      <a href="cat.php?edit=<?php echo (int)$cat['id'];?>&tab=stock-categories" class="btn btn-warning-custom" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="a_script.php?id=<?= (int)$cat['id']; ?>&type=category&tab=stock-categories" 
                        class="btn btn-danger-custom archive-btn"
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
      </div>

      <!-- Semi-Expendable Property Tab -->
      <div id="semi-expendable" class="tab-pane <?php echo $active_tab === 'semi-expendable' ? 'active' : ''; ?>">
        <!-- Add/Edit Form -->
        <div class="form-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
              <i class="fas fa-tools me-2 text-success"></i>
              Manage Semi-Expendable Property Categories
            </h5>
          </div>
          
          <form method="post" action="cat.php" class="row g-3" id="semiCategoryForm">
            <input type="hidden" name="active_tab" value="semi-expendable">
            <div class="col-12 col-md-4">
              <input type="text" class="form-control" 
                     name="semicategory_name" 
                     placeholder="Category Name" 
                     value="<?php echo $edit_semi_mode ? remove_junk($edit_semi_cat['semicategory_name']) : ''; ?>" 
                     required>
            </div>
            <div class="col-12 col-md-4">
              <input type="number" class="form-control" 
                     name="uacs" 
                     placeholder="UACS Object Code" 
                     value="<?php echo $edit_semi_mode ? remove_junk($edit_semi_cat['uacs']) : ''; ?>">
            </div>
            <div class="col-12 col-md-4">
              <div class="d-grid gap-2 d-md-flex">
                <?php if ($edit_semi_mode): ?>
                  <input type="hidden" name="semi_cat_id" value="<?php echo (int)$edit_semi_cat['id']; ?>">
                  <button type="submit" name="update_semi_category" class="btn btn-warning-custom me-2 flex-fill">
                    <i class="fas fa-save me-1"></i> Update
                  </button>
                  <a href="cat.php?tab=semi-expendable" class="btn btn-secondary flex-fill">
                    <i class="fas fa-times me-1"></i> Cancel
                  </a>
                <?php else: ?>
                  <button type="submit" name="add_semi_category" class="btn btn-primary-custom flex-fill">
                    <i class="fas fa-plus me-1"></i> Add Category
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>

        <!-- Semi-Expendable Categories Table -->
        <div class="form-section">
          <table class="table table-custom" id="semiCategoriesTable">
            <thead class="text-center">
              <tr>
                <th width="10%">No.</th>
                <th>Category Name</th>
                <th>UACS Object Code</th>
                <th width="15%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($semi_expendable_categories as $i=>$cat): ?>
                <tr>
                  <td><span class="badge badge-custom badge-primary"><?= $i+1 ?></span></td>
                  <td><?php echo remove_junk(ucfirst($cat['semicategory_name'])); ?></td>
                  <td><?php echo remove_junk($cat['uacs'] ?: '-'); ?></td>
                  <td class="text-center">
                    <div class="btn-group btn-group-custom">
                      <a href="cat.php?edit_semi=<?php echo (int)$cat['id'];?>&tab=semi-expendable" class="btn btn-warning-custom" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                     <a href="a_script.php?id=<?= (int)$cat['id']; ?>&type=semicategory&tab=semi-expendable" 
                       class="btn btn-danger-custom archive-btn"
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
      </div>

      <!-- Property Tab -->
      <div id="properties" class="tab-pane <?php echo $active_tab === 'properties' ? 'active' : ''; ?>">
        <div class="form-section">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
              <i class="fas fa-tools me-2 text-success"></i>
             Property, Plant & Equipments Categories
            </h5>
            <button type="button" class="toggle-btn" data-toggle="modal" data-target="#addAccountTitleModal">
              <i class="fas fa-plus me-2"></i> Add Account Title
            </button>
          </div>

          <!-- Property Categories Table -->
          <table class="table table-custom" id="itemsTable">
            <thead class="text-center">
              <tr>
                <th>Category</th>
                <th>Subcategory</th>
                <th>UACS</th>
                <th width="15%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($semi_categories as $cat): ?>
                <tr>
                  <td><?php echo remove_junk($cat['category_name']); ?></td>
                  <td><?php echo remove_junk($cat['subcategory_name']); ?></td>
                  <td class="text-center"><?php echo remove_junk($cat['uacs_code']); ?></td>
                  <td class="text-center">
                    <div class="btn-group btn-group-custom">
                      <button type="button" class="btn btn-warning-custom" 
                              onclick="openEditModal(<?php echo (int)$cat['account_title_id']; ?>)" 
                              title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                     <a href="a_script.php?id=<?= (int)$cat['id']; ?>&type=subcategory&tab=properties" 
                     class="btn btn-danger-custom archive-btn"
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
      </div>
    </div>
  </div>
</div>

<!-- Add Account Title Modal -->
<div class="modal fade" id="addAccountTitleModal" tabindex="-1" role="dialog" aria-labelledby="addAccountTitleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header modal-header-custom">
        <div class="modal-header-content">
          <div class="modal-icon">
            <i class="fas fa-layer-group"></i>
          </div>
          <div class="modal-title-section">
            <h5 class="modal-title modal-title-custom" id="addAccountTitleModalLabel">
              Create New Category
            </h5>
            <p class="modal-subtitle">Add main category and its subcategories</p>
          </div>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-0">
        <form method="post" action="cat.php" id="categoryForm">
          <input type="hidden" name="save_account_title" value="1">
          <input type="hidden" name="active_tab" value="properties">
          
          <!-- Main Category Section -->
          <div class="form-section-main">
            <div class="section-header">
              <div class="section-icon">
                <i class="fas fa-folder"></i>
              </div>
              <div class="section-title">
                <h6>Main Category</h6>
                <span class="section-badge">Required</span>
              </div>
            </div>
            <div class="form-group-main">
              <label class="form-label">
                <i class="fas fa-tag me-2"></i>Category Name
              </label>
              <input type="text" class="form-control form-control-lg" 
                     name="category_name" 
                     placeholder="Enter category name (e.g. Semi-Expendable Property)" 
                     required>
              <div class="form-hint">
                <i class="fas fa-info-circle me-1"></i>
                This will be the main category that groups related subcategories
              </div>
            </div>
          </div>

          <!-- Subcategories Section -->
          <div class="form-section-subcategories">
            <div class="section-header">
              <div class="section-icon">
                <i class="fas fa-list-ul"></i>
              </div>
              <div class="section-title">
                <h6>Subcategories</h6>
                <span class="section-badge">Add at least one</span>
              </div>
            </div>

            <div id="subcategory-wrapper">
              <!-- Default first row -->
              <div class="subcategory-row">
                <div class="subcategory-header">
                  <span class="subcategory-number">#1</span>
                  <button type="button" class="btn btn-sm remove-row" disabled>
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <div class="row g-3">
                  <div class="col-md-7">
                    <div class="form-group">
                      <label class="form-label">Subcategory Name</label>
                      <input type="text" class="form-control" 
                             name="subcategories[0][name]" 
                             placeholder="Enter subcategory name" 
                             required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <div class="form-group">
                      <label class="form-label">UACS Code</label>
                      <input type="text" class="form-control" 
                             name="subcategories[0][uacs]" 
                             placeholder="Enter UACS code" 
                             required>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Add More Button -->
            <div class="add-more-section">
              <button type="button" class="btn btn-outline-primary btn-add-more" id="addRow">
                <i class="fas fa-plus-circle me-2"></i>
                Add Another Subcategory
              </button>
              <div class="add-more-hint">
                You can add multiple subcategories under this main category
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <div class="footer-actions">
          <button type="button" class="btn btn-secondary btn-cancel" data-dismiss="modal">
            <i class="fas fa-times me-2"></i> Cancel
          </button>
          <button type="submit" form="categoryForm" class="btn btn-primary-custom btn-save">
            <i class="fas fa-save me-2"></i> Save Category
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Account Title Modal -->
<div class="modal fade" id="editAccountTitleModal" tabindex="-1" role="dialog" aria-labelledby="editAccountTitleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header modal-header-custom">
        <div class="modal-header-content">
          <div class="modal-icon">
            <i class="fas fa-edit"></i>
          </div>
          <div class="modal-title-section">
            <h5 class="modal-title modal-title-custom" id="editAccountTitleModalLabel">
              Edit Category
            </h5>
            <p class="modal-subtitle">Update main category and its subcategories</p>
          </div>
        </div>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-0">
        <form method="post" action="cat.php" id="editCategoryForm">
          <input type="hidden" name="update_account_title" value="1">
          <input type="hidden" name="account_title_id" id="edit_account_title_id" value="<?php echo $edit_account_title ? (int)$edit_account_title['id'] : ''; ?>">
          <input type="hidden" name="active_tab" value="properties">
          
          <!-- Main Category Section -->
          <div class="form-section-main">
            <div class="section-header">
              <div class="section-icon">
                <i class="fas fa-folder"></i>
              </div>
              <div class="section-title">
                <h6>Main Category</h6>
                <span class="section-badge">Required</span>
              </div>
            </div>
            <div class="form-group-main">
              <label class="form-label">
                <i class="fas fa-tag me-2"></i>Category Name
              </label>
              <input type="text" class="form-control form-control-lg" 
                     name="category_name" 
                     id="edit_category_name"
                     placeholder="Enter category name (e.g. Semi-Expendable Property)" 
                     value="<?php echo $edit_account_title ? remove_junk($edit_account_title['category_name']) : ''; ?>"
                     required>
              <div class="form-hint">
                <i class="fas fa-info-circle me-1"></i>
                This will be the main category that groups related subcategories
              </div>
            </div>
          </div>

          <!-- Subcategories Section -->
          <div class="form-section-subcategories">
            <div class="section-header">
              <div class="section-icon">
                <i class="fas fa-list-ul"></i>
              </div>
              <div class="section-title">
                <h6>Subcategories</h6>
                <span class="section-badge">Add at least one</span>
              </div>
            </div>

            <div id="edit-subcategory-wrapper">
              <?php if ($edit_account_title && !empty($edit_subcategories)): ?>
                <?php foreach ($edit_subcategories as $index => $subcat): ?>
                  <div class="subcategory-row">
                    <div class="subcategory-header">
                      <span class="subcategory-number">#<?php echo $index + 1; ?></span>
                      <button type="button" class="btn btn-sm remove-row <?php echo count($edit_subcategories) > 1 ? 'btn-outline-danger' : ''; ?>" 
                              <?php echo count($edit_subcategories) === 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                    <input type="hidden" name="subcategories[<?php echo $index; ?>][id]" value="<?php echo (int)$subcat['id']; ?>">
                    <div class="row g-3">
                      <div class="col-md-7">
                        <div class="form-group">
                          <label class="form-label">Subcategory Name</label>
                          <input type="text" class="form-control" 
                                 name="subcategories[<?php echo $index; ?>][name]" 
                                 placeholder="Enter subcategory name" 
                                 value="<?php echo remove_junk($subcat['subcategory_name']); ?>"
                                 required>
                        </div>
                      </div>
                      <div class="col-md-5">
                        <div class="form-group">
                          <label class="form-label">UACS Code</label>
                          <input type="text" class="form-control" 
                                 name="subcategories[<?php echo $index; ?>][uacs]" 
                                 placeholder="Enter UACS code" 
                                 value="<?php echo remove_junk($subcat['uacs_code']); ?>"
                                 required>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Default first row if no subcategories -->
                <div class="subcategory-row">
                  <div class="subcategory-header">
                    <span class="subcategory-number">#1</span>
                    <button type="button" class="btn btn-sm remove-row" disabled>
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <input type="hidden" name="subcategories[0][id]" value="0">
                  <div class="row g-3">
                    <div class="col-md-7">
                      <div class="form-group">
                        <label class="form-label">Subcategory Name</label>
                        <input type="text" class="form-control" 
                               name="subcategories[0][name]" 
                               placeholder="Enter subcategory name" 
                               required>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="form-group">
                        <label class="form-label">UACS Code</label>
                        <input type="text" class="form-control" 
                               name="subcategories[0][uacs]" 
                               placeholder="Enter UACS code" 
                               required>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Add More Button -->
            <div class="add-more-section">
              <button type="button" class="btn btn-outline-primary btn-add-more" id="editAddRow">
                <i class="fas fa-plus-circle me-2"></i>
                Add Another Subcategory
              </button>
              <div class="add-more-hint">
                You can add multiple subcategories under this main category
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <div class="footer-actions">
          <button type="button" class="btn btn-secondary btn-cancel" data-dismiss="modal">
            <i class="fas fa-times me-2"></i> Cancel
          </button>
          <button type="submit" form="editCategoryForm" class="btn btn-primary-custom btn-save">
            <i class="fas fa-save me-2"></i> Update Category
          </button>
        </div>
      </div>
    </div>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
  // Store current active tab in session storage for persistence
  const currentTab = '<?php echo $active_tab; ?>';
  sessionStorage.setItem('active_cat_tab', currentTab);

  // Tab switching functionality
  const tabLinks = document.querySelectorAll('.nav-tab-link');
  const tabPanes = document.querySelectorAll('.tab-pane');
  
  function switchTab(tabId) {
    // Remove active class from all tabs and panes
    tabLinks.forEach(link => link.classList.remove('active'));
    tabPanes.forEach(pane => pane.classList.remove('active'));
    
    // Add active class to current tab and pane
    const activeTab = document.querySelector(`[data-tab="${tabId}"]`);
    const activePane = document.getElementById(tabId);
    
    if (activeTab && activePane) {
      activeTab.classList.add('active');
      activePane.classList.add('active');
      
      // Store in session storage
      sessionStorage.setItem('active_cat_tab', tabId);
      
      // Update all hidden active_tab fields in forms
      document.querySelectorAll('input[name="active_tab"]').forEach(input => {
        input.value = tabId;
      });
    }
  }
  
  // Add click event to tab links
  tabLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const tabId = this.getAttribute('data-tab');
      switchTab(tabId);
    });
  });
  
  // Restore active tab on page load from session storage
  const savedTab = sessionStorage.getItem('active_cat_tab');
  if (savedTab && savedTab !== currentTab) {
    switchTab(savedTab);
  }

  // Update all forms with current active tab
  document.querySelectorAll('form').forEach(form => {
    let activeTabInput = form.querySelector('input[name="active_tab"]');
    if (!activeTabInput) {
      activeTabInput = document.createElement('input');
      activeTabInput.type = 'hidden';
      activeTabInput.name = 'active_tab';
      form.appendChild(activeTabInput);
    }
    activeTabInput.value = currentTab;
  });

  // Your existing JavaScript for subcategory functionality remains the same
  let counter = 1;
  const wrapper = document.getElementById("subcategory-wrapper");
  const addBtn = document.getElementById("addRow");

  if (addBtn) {
    addBtn.addEventListener("click", function() {
      const newRow = document.createElement("div");
      newRow.classList.add("subcategory-row", "new-row");
      newRow.innerHTML = `
        <div class="subcategory-header">
          <span class="subcategory-number">#${counter + 1}</span>
          <button type="button" class="btn btn-sm remove-row">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="row g-3">
          <div class="col-md-7">
            <div class="form-group">
              <label class="form-label">Subcategory Name</label>
              <input type="text" class="form-control" 
                     name="subcategories[${counter}][name]" 
                     placeholder="Enter subcategory name" 
                     required>
            </div>
          </div>
          <div class="col-md-5">
            <div class="form-group">
              <label class="form-label">UACS Code</label>
              <input type="text" class="form-control" 
                     name="subcategories[${counter}][uacs]" 
                     placeholder="Enter UACS code" 
                     required>
            </div>
          </div>
        </div>
      `;
      wrapper.appendChild(newRow);
      
      if (counter === 1) {
        const firstRemoveBtn = wrapper.querySelector('.subcategory-row:first-child .remove-row');
        firstRemoveBtn.disabled = false;
        firstRemoveBtn.classList.add('btn-outline-danger');
      }
      
      counter++;
      
      setTimeout(() => {
        newRow.classList.remove('new-row');
      }, 300);

      newRow.querySelector('.remove-row').addEventListener('click', function() {
        removeSubcategoryRow(this);
      });
    });
  }

  function removeSubcategoryRow(button) {
    const rowToRemove = button.closest('.subcategory-row');
    rowToRemove.style.opacity = '0';
    rowToRemove.style.transform = 'translateX(-100%)';
    
    setTimeout(() => {
      rowToRemove.remove();
      
      const rows = wrapper.querySelectorAll('.subcategory-row');
      rows.forEach((row, index) => {
        const numberSpan = row.querySelector('.subcategory-number');
        numberSpan.textContent = `#${index + 1}`;
      });
      
      if (rows.length === 1) {
        const firstRemoveBtn = rows[0].querySelector('.remove-row');
        firstRemoveBtn.disabled = true;
        firstRemoveBtn.classList.remove('btn-outline-danger');
      }
      
      counter = rows.length;
    }, 300);
  }

  document.querySelectorAll('.remove-row').forEach(button => {
    button.addEventListener('click', function() {
      if (!this.disabled) {
        removeSubcategoryRow(this);
      }
    });
  });

  // Edit functionality for semi-expendable categories
  let editCounter = <?php echo $edit_account_title ? count($edit_subcategories) : 1; ?>;
  const editWrapper = document.getElementById("edit-subcategory-wrapper");
  const editAddBtn = document.getElementById("editAddRow");

  if (editAddBtn) {
    editAddBtn.addEventListener("click", function() {
      const newRow = document.createElement("div");
      newRow.classList.add("subcategory-row", "new-row");
      newRow.innerHTML = `
        <div class="subcategory-header">
          <span class="subcategory-number">#${editCounter + 1}</span>
          <button type="button" class="btn btn-sm remove-row btn-outline-danger">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <input type="hidden" name="subcategories[${editCounter}][id]" value="0">
        <div class="row g-3">
          <div class="col-md-7">
            <div class="form-group">
              <label class="form-label">Subcategory Name</label>
              <input type="text" class="form-control" 
                     name="subcategories[${editCounter}][name]" 
                     placeholder="Enter subcategory name" 
                     required>
            </div>
          </div>
          <div class="col-md-5">
            <div class="form-group">
              <label class="form-label">UACS Code</label>
              <input type="text" class="form-control" 
                     name="subcategories[${editCounter}][uacs]" 
                     placeholder="Enter UACS code" 
                     required>
            </div>
          </div>
        </div>
      `;
      editWrapper.appendChild(newRow);
      
      const firstRemoveBtn = editWrapper.querySelector('.subcategory-row:first-child .remove-row');
      if (firstRemoveBtn) {
        firstRemoveBtn.disabled = false;
        firstRemoveBtn.classList.add('btn-outline-danger');
      }
      
      editCounter++;
      
      setTimeout(() => {
        newRow.classList.remove('new-row');
      }, 300);

      newRow.querySelector('.remove-row').addEventListener('click', function() {
        removeEditSubcategoryRow(this);
      });
    });
  }

  function removeEditSubcategoryRow(button) {
    const rowToRemove = button.closest('.subcategory-row');
    rowToRemove.style.opacity = '0';
    rowToRemove.style.transform = 'translateX(-100%)';
    
    setTimeout(() => {
      rowToRemove.remove();
      
      const rows = editWrapper.querySelectorAll('.subcategory-row');
      rows.forEach((row, index) => {
        const numberSpan = row.querySelector('.subcategory-number');
        numberSpan.textContent = `#${index + 1}`;
      });
      
      if (rows.length === 1) {
        const firstRemoveBtn = rows[0].querySelector('.remove-row');
        firstRemoveBtn.disabled = true;
        firstRemoveBtn.classList.remove('btn-outline-danger');
      }
      
      editCounter = rows.length;
    }, 300);
  }

  if (editWrapper) {
    editWrapper.querySelectorAll('.remove-row').forEach(button => {
      button.addEventListener('click', function() {
        if (!this.disabled) {
          removeEditSubcategoryRow(this);
        }
      });
    });
  }

  // Archive confirmation
  document.querySelectorAll('.archive-btn').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      Swal.fire({
        title: 'Are you sure?',
        text: "This category will be archived.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive it!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  });

  // Auto-open edit modal if editing
  <?php if (isset($_GET['edit_account']) && $edit_account_title): ?>
    $(document).ready(function() {
      $('#editAccountTitleModal').modal('show');
    });
  <?php endif; ?>
});

function openEditModal(accountId) {
  // Add the current active tab to the URL
  const currentTab = sessionStorage.getItem('active_cat_tab') || 'properties';
  window.location.href = 'cat.php?edit_account=' + accountId + '&tab=' + currentTab;
}
</script>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
  // DataTable initialization
$(document).ready(function(){
  var table = $('#itemsTable').DataTable({
    pageLength: 5,
    lengthMenu: [5, 10, 25, 50],
    ordering: true,
    search: false,
    autoWidth: false,
    columnDefs: [
      { orderable: false, targets: [3] } // Actions column
    ]
  });
});
</script>

<script>
  // DataTable initialization
$(document).ready(function(){
  var table = $('#semiCategoriesTable').DataTable({
    pageLength: 5,
    lengthMenu: [5, 10, 25, 50],
    ordering: true,
    search: false,
    autoWidth: false,
    columnDefs: [
      { orderable: false, targets: [3] } // Actions column
    ]
  });
});
</script>