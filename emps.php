<?php
$page_title = 'Employees';
require_once('includes/load.php');
page_require_level(2);

// Handle AJAX request for offices by division
if (isset($_GET['action']) && $_GET['action'] === 'get_offices' && isset($_GET['division_id'])) {
    $division_id = (int)$_GET['division_id'];
    $sql = "SELECT id, office_name FROM offices WHERE division_id = '{$division_id}' ORDER BY office_name ASC";
    $result = $db->query($sql);
    $offices = [];
    while ($row = $db->fetch_assoc($result)) {
        $offices[] = $row;
    }
    echo json_encode($offices);
    exit;
}

// Fetch employees
$employees = find_by_sql("
    SELECT 
        e.*, 
        o.office_name, 
        d.division_name
    FROM employees e
    LEFT JOIN offices o ON e.office = o.id
    LEFT JOIN divisions d ON e.division = d.id
    ORDER BY e.id ASC
");

// Handle Add Employee form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $user_id     = $db->escape($_POST['user_id']);
    $first_name  = $db->escape($_POST['first_name']);
    $last_name   = $db->escape($_POST['last_name']);
    $middle_name = $db->escape($_POST['middle_name']);
    $position    = $db->escape($_POST['position']);
    $division    = $db->escape($_POST['division']);
    $office      = $db->escape($_POST['office']);
    $status      = isset($_POST['status']) ? 'Active' : 'Inactive'; // Checkbox value

    // Handle file upload
    $image_name = '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir  = "uploads/users/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_name  = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    $query  = "INSERT INTO employees 
    (user_id, first_name, last_name, middle_name, position, division, office, status, image, created_at, updated_at) 
    VALUES 
    ('{$user_id}', '{$first_name}', '{$last_name}', '{$middle_name}', '{$position}', '{$division}', '{$office}', '{$status}', '{$image_name}', NOW(), NOW())";

    if ($db->query($query)) {
        $session->msg("s","Employee added successfully.");
    } else {
        $session->msg("d","Failed to add employee.");
    }

    redirect('emps.php', false);
}
?>

<?php include_once('layouts/header.php'); 
$msg = $session->msg(); // get the flashed message

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

  .profile-image-container {
    text-align: center;
    padding: 20px;
    border-right: 2px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 10px 0 0 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
  }

  .profile-image-preview {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #28a745;
    margin-bottom: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #6c757d;
    text-align: center;
    padding: 20px;
  }

  .profile-image-preview.has-image {
    background: #fff;
  }

  .profile-upload-btn {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
    max-width: 200px;
  }

  .profile-upload-btn input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
  }

  .form-content {
    padding: 20px;
  }

  .form-section {
    margin-bottom: 25px;
  }

  .form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #28a745;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #28a745;
  }

  .no-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
  }

  .no-image-placeholder i {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 10px;
  }

  .no-image-placeholder span {
    font-size: 14px;
    color: #6c757d;
    text-align: center;
  }
  
  .table th {
    background: #005113ff;
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
    text-align: center;
  }

  /* Checkbox styling */
  .status-checkbox {
    transform: scale(1.2);
    margin-right: 0.5rem;
  }

  .form-check-label {
    font-weight: 500;
    color: #495057;
  }

  .status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
  }

  .status-active {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .status-inactive {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
</style>

<div class="container-fluid mt-3">

 <!-- Add Employee Button -->
<div class="mb-3 d-flex justify-content-end">
  <div class="search-box mr-2">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="form-control" placeholder="Search employees" id="searchInput">
        </div>
  <button class="btn btn-success" id="btnAddEmployee">
    <i class="fa-solid fa-user-plus"></i> Add Employee
  </button>
</div>

  <!-- Employees Table -->
  <div id="employeeTableWrapper" class="card" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <div class="card-header">
      <h3 class="card-title"><i class="nav-icon fa-solid fa-users"></i> Employee Records</h3>
    </div>
    <div class="card-body">
      <table id="employeeTable" class="table table-hover table-striped">
        <thead>
          <tr>
            <th>User_ID</th>
            <th>Profile</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Middle Name</th>
            <th>Designation</th>
            <th>Division</th>
            <th>Office</th>
            <th>Status</th>
            <th style="width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $emp): ?>
            <tr>
              <td><?php echo $emp['user_id']; ?></td>
              <td class="text-center">
                <?php if (!empty($emp['image']) && file_exists('uploads/users/' . $emp['image'])): ?>
                  <img src="uploads/users/<?php echo $emp['image']; ?>" 
                       class="img-thumbnail" style="width:60px;height:60px;border-radius:50%">
                <?php else: ?>
                  <div class="img-thumbnail d-inline-flex align-items-center justify-content-center" 
                       style="width:60px;height:60px;border-radius:50%; background:#f8f9fa; border:1px solid #dee2e6;">
                    <i class="fa-solid fa-user text-muted"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td><?php echo $emp['first_name']; ?></td>
              <td><?php echo $emp['last_name']; ?></td>
              <td><?php echo $emp['middle_name']; ?></td>
              <td><?php echo $emp['position']; ?></td>
              <td><?php echo $emp['division_name']; ?></td>
              <td><?php echo $emp['office_name']; ?></td>
              <td>
                <span class="badge bg-<?php echo $emp['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                  <?php echo $emp['status']; ?>
                </span>
              </td>
              <td>
                <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-md btn-warning"><i class="fa-solid fa-pen-to-square"></i></a>
                <a href="a_script.php?id=<?php echo $emp['id']; ?>" class="btn btn-md btn-danger" title="Archive"><i class="fa-solid fa-file-zipper"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- Add Employee Form -->
<div id="addEmployeeForm" class="card" style="display:none; border-top: 5px solid #006205; border-radius: 10px;">
  <div class="card-header">
    <h3 class="card-title text-success"><i class="fa-solid fa-user-plus"></i> Add New Employee</h3>
  </div>
  <form action="" method="POST" enctype="multipart/form-data">
    <div class="row no-gutters">
      <!-- Profile Image Section - Left Side -->
      <div class="col-md-3">
        <div class="profile-image-container">
          <div id="imagePreview" class="profile-image-preview">
            <div class="no-image-placeholder">
              <i class="fa-solid fa-user"></i>
              <span>No Image Selected</span>
            </div>
          </div>
          <div class="profile-upload-btn">
            <button type="button" class="btn btn-outline-success btn-block">
              <i class="fa-solid fa-camera"></i> Choose Profile Image
            </button>
            <input type="file" name="image" id="imageInput" accept="image/*">
          </div>
          <small class="text-muted mt-2 d-block">Recommended: 200x200px, JPG/PNG</small>
          
          <!-- Status Preview -->
          <div class="profile-info mt-3">
            <h5><i class="fa-solid fa-info-circle me-2"></i>Status Preview</h5>
            <div id="statusPreview" class="status-indicator status-inactive">
              <i class="fa-solid fa-circle"></i>
              <span>Inactive</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Fields Section - Right Side -->
      <div class="col-md-9">
        <div class="form-content">
          <!-- Personal Information Section -->
          <div class="form-section">
            <div class="form-section-title">
              <i class="fa-solid fa-user"></i> Personal Information
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control">
              </div>
            </div>
          </div>

          <!-- Employment Details Section -->
          <div class="form-section">
            <div class="form-section-title">
              <i class="fa-solid fa-briefcase"></i> Employment Details
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">User ID</label>
                <input type="number" name="user_id" class="form-control" placeholder="Enter user ID">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Designation <span class="text-danger">*</span></label>
                <input type="text" name="position" class="form-control" required placeholder="Enter designation">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Division <span class="text-danger">*</span></label>
                <select name="division" class="form-control" id="divisionSelect" required>
                  <option value="">Select Division</option>
                  <?php
                    $divisions = find_by_sql("SELECT id, division_name FROM divisions ORDER BY division_name ASC");
                    foreach ($divisions as $div):
                  ?>
                    <option value="<?php echo $div['id']; ?>"><?php echo $div['division_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Office <span class="text-danger">*</span></label>
                <select name="office" class="form-control" id="officeSelect" required>
                  <option value="">Select Office</option>
                  <?php
                    $offices = find_by_sql("SELECT id, office_name FROM offices ORDER BY office_name ASC");
                    foreach ($offices as $off):
                  ?>
                    <option value="<?php echo $off['id']; ?>"><?php echo $off['office_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Status Section -->
          <div class="form-section">
            <div class="form-section-title">
              <i class="fa-solid fa-circle-info"></i> Status Information
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Employment Status</label>
                <div class="form-check mt-2">
                  <input type="checkbox" class="form-check-input status-checkbox" name="status" id="status" value="1">
                  <label class="form-check-label" for="status">Active Employee</label>
                </div>
                <small class="text-muted">Check this box to set the employee as active</small>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="form-section">
            <div class="row">
              <div class="col-md-12">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fa-solid fa-floppy-disk"></i> Save Employee
                </button>
                <button type="button" id="btnCancel" class="btn btn-secondary btn-lg ml-3">
                  <i class="fa-solid fa-times"></i> Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

</div>
<?php include_once('layouts/footer.php'); ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const addBtn = document.getElementById('btnAddEmployee');
  const cancelBtn = document.getElementById('btnCancel');
  const addForm = document.getElementById('addEmployeeForm');
  const tableWrapper = document.getElementById('employeeTableWrapper');
  const imageInput = document.getElementById('imageInput');
  const imagePreview = document.getElementById('imagePreview');
  const statusCheckbox = document.getElementById('status');
  const statusPreview = document.getElementById('statusPreview');

  // Image preview functionality
  imageInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        // Create image element and replace the placeholder
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'profile-image-preview has-image';
        img.alt = 'Profile Preview';
        img.style.objectFit = 'cover';
        imagePreview.innerHTML = '';
        imagePreview.appendChild(img);
      }
      reader.readAsDataURL(file);
    } else {
      // Reset to placeholder if no file selected
      resetImagePreview();
    }
  });

  // Function to reset image preview to placeholder
  function resetImagePreview() {
    imagePreview.innerHTML = `
      <div class="no-image-placeholder">
        <i class="fa-solid fa-user"></i>
        <span>No Image Selected</span>
      </div>
    `;
    imagePreview.className = 'profile-image-preview';
  }

  // Status checkbox functionality
  statusCheckbox.addEventListener('change', function() {
    updateStatusPreview(this.checked);
  });

  function updateStatusPreview(isActive) {
    if (isActive) {
      statusPreview.className = 'status-indicator status-active';
      statusPreview.innerHTML = '<i class="fa-solid fa-circle"></i> <span>Active</span>';
    } else {
      statusPreview.className = 'status-indicator status-inactive';
      statusPreview.innerHTML = '<i class="fa-solid fa-circle"></i> <span>Inactive</span>';
    }
  }

  // Show/hide form
  addBtn.addEventListener('click', () => {
    addForm.style.display = 'block';
    tableWrapper.style.display = 'none';
    addBtn.style.display = 'none';
  });

  cancelBtn.addEventListener('click', () => {
    addForm.style.display = 'none';
    tableWrapper.style.display = 'block';
    addBtn.style.display = 'inline-block';
    // Reset form and image preview
    document.querySelector('form').reset();
    resetImagePreview();
    updateStatusPreview(false); // Reset to inactive
  });

  // Initialize status preview
  updateStatusPreview(false);
});
</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    $('#employeeTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: false,
        fixedColumns: true
    });
});
</script>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const table = document.getElementById("employeeTable");
    const rows = table.getElementsByTagName("tr");

    searchInput.addEventListener("keyup", function () {
      const filter = this.value.toLowerCase();

      for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName("td");
        let match = false;

        for (let j = 0; j < cells.length; j++) {
          const cellText = cells[j].textContent || cells[j].innerText;
          if (cellText.toLowerCase().indexOf(filter) > -1) {
            match = true;
            break;
          }
        }

        rows[i].style.display = match ? "" : "none";
      }
    });
  });
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const divisionSelect = document.getElementById("divisionSelect");
  const officeSelect = document.getElementById("officeSelect");

  divisionSelect.addEventListener("change", function() {
    const divisionId = this.value;
    officeSelect.innerHTML = '<option value="">Loading...</option>';

    if (divisionId) {
      fetch(`emps.php?action=get_offices&division_id=${divisionId}`)
        .then(response => response.json())
        .then(data => {
          officeSelect.innerHTML = '<option value="">Select Office</option>';
          data.forEach(office => {
            const option = document.createElement('option');
            option.value = office.id;
            option.textContent = office.office_name;
            officeSelect.appendChild(option);
          });
        })
        .catch(() => {
          officeSelect.innerHTML = '<option value="">Error loading offices</option>';
        });
    } else {
      officeSelect.innerHTML = '<option value="">Select Office</option>';
    }
  });
});
</script>