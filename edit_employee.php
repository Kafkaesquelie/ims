<?php
$page_title = 'Edit Employee';
require_once('includes/load.php');
page_require_level(2); 

// ✅ Fetch employee
if (isset($_GET['id'])) {
    $emp_id = (int)$_GET['id'];
    $edit_emp = find_by_id('employees', $emp_id);
    if (!$edit_emp) {
        $session->msg('d', 'Employee not found.');
        redirect('emps.php');
    }
} else {
    redirect('emps.php');
}

// ✅ Fetch dropdown data
$departments = find_all('departments');
$divisions = find_all('divisions');
$offices = find_all('offices');

// ✅ Update profile image
if (isset($_POST['submit_image'])) {
    if (isset($_FILES['emp_image'])) {
        $target_dir  = "uploads/users/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_name  = time() . "_" . basename($_FILES["emp_image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["emp_image"]["tmp_name"], $target_file)) {
            $sql = "UPDATE employees SET image='{$image_name}' WHERE id='{$emp_id}'";
            if ($db->query($sql)) {
                $session->msg('s', 'Profile image updated successfully.');
            } else {
                $session->msg('d', 'Image update failed.');
            }
        } else {
            $session->msg('d', 'Image upload failed.');
        }
        redirect('edit_employee.php?id='.$emp_id);
    }
}

// ✅ Update employee info
if (isset($_POST['update_employee'])) {
    $req_fields = array('first_name','last_name','position','division','office');
    validate_fields($req_fields);

    if (empty($errors)) {
        $user_id  = !empty($_POST['user_id']) ? remove_junk($db->escape($_POST['user_id'])) : 'NULL'; // Set to NULL if empty
        $first_name  = remove_junk($db->escape($_POST['first_name']));
        $last_name   = remove_junk($db->escape($_POST['last_name']));
        $middle_name = remove_junk($db->escape($_POST['middle_name']));
        $designation = remove_junk($db->escape($_POST['position']));
        $division    = remove_junk($db->escape($_POST['division']));
        $office      = remove_junk($db->escape($_POST['office']));
        $status      = isset($_POST['status']) ? 'Active' : 'Inactive'; // Checkbox value

        // Build SQL query with proper NULL handling
        $sql = "UPDATE employees SET 
                    first_name='{$first_name}', 
                    last_name='{$last_name}', 
                    middle_name='{$middle_name}', 
                    position='{$designation}', 
                    division='{$division}', 
                    office='{$office}', 
                    status='{$status}', 
                    user_id={$user_id}, 
                    updated_at=NOW()
                WHERE id='{$emp_id}'";

        if ($db->query($sql)) {
            $session->msg('s', 'Employee updated successfully.');
            redirect('emps.php');
        } else {
            $session->msg('d', 'Failed to update employee.');
            redirect('edit_employee.php?id=' . $emp_id);
        }
    } else {
        $session->msg('d', 'Please fill out all required fields.');
        redirect('edit_employee.php?id=' . $emp_id);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<style>
    .profile-section {
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
        border-radius: 15px;
        padding: 2rem;
        border: 2px solid #e8f5e9;
        text-align: center;
        height: 100%;
    }
    
    .profile-image-container {
        margin-bottom: 2rem;
    }
    
    .profile-image-preview {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #28a745;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: #6c757d;
        position: relative;
        overflow: hidden;
    }
    
    .profile-upload-btn {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
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
    
    .profile-info {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px dashed #c3e6cb;
    }
    
    .profile-info h5 {
        color: #155724;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .profile-info p {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    /* Form styling */
    .user-form .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .user-form .form-control,
    .user-form .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .user-form .form-control:focus,
    .user-form .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    .status-checkbox {
        transform: scale(1.2);
        margin-right: 0.5rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #28a745;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #28a745;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
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
    
    .card {
        border-top: 5px solid #006205;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .clear-user-id {
        margin-top: 0.5rem;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid mt-3">
  <div class="row d-flex justify-content-center">
    <div class="col-md-11">
      <div class="card">
        <div class="card-header text-center">
          <h3 class="text-success"><i class="fa-solid fa-user-pen me-2"></i>Edit Employee</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <!-- LEFT COLUMN: Profile Section -->
            <div class="col-md-4">
              <div class="profile-section">
                <div class="profile-image-container">
                  <div class="profile-image-preview">
                    <img src="uploads/users/<?php echo !empty($edit_emp['image']) ? $edit_emp['image'] : 'no_image.jpg'; ?>" 
                         alt="Employee Image" 
                         style="width:100%;height:100%;object-fit:cover;">
                  </div>
                  <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="emp_id" value="<?php echo (int)$edit_emp['id']; ?>">
                    <div class="profile-upload-btn">
                      <button type="button" class="btn btn-outline-success btn-block">
                        <i class="fa-solid fa-camera me-2"></i>Change Profile Image
                      </button>
                      <input type="file" name="emp_image" accept="image/*" required>
                    </div>
                    <small class="text-muted mt-2 d-block">Recommended: 200x200px, JPG/PNG</small>
                    <button type="submit" name="submit_image" class="btn btn-primary mt-3 w-100">
                      <i class="fa-solid fa-upload me-2"></i>Upload Image
                    </button>
                  </form>
                </div>
                
                <div class="profile-info">
                  <h5><i class="fa-solid fa-info-circle me-2"></i>Current Status</h5>
                  <div id="statusPreview" class="status-indicator <?php echo $edit_emp['status'] === 'Active' ? 'status-active' : 'status-inactive'; ?>">
                    <i class="fa-solid fa-circle"></i>
                    <span><?php echo $edit_emp['status']; ?></span>
                  </div>
                  <p><strong>User ID:</strong> <?php echo !empty($edit_emp['user_id']) ? $edit_emp['user_id'] : 'Not set'; ?></p>
                  <p><strong>Last Updated:</strong><br><?php echo date('M j, Y g:i A', strtotime($edit_emp['updated_at'])); ?></p>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN: Employee Form -->
            <div class="col-md-8">
              <form method="post" action="" class="user-form">
                <input type="hidden" name="emp_id" value="<?php echo (int)$edit_emp['id']; ?>">

                <!-- Personal Information Section -->
                <div class="form-section">
                  <div class="form-section-title">
                    <i class="fa-solid fa-user me-2"></i>Personal Information
                  </div>
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">First Name <span class="text-danger">*</span></label>
                      <input type="text" name="first_name" class="form-control" 
                             value="<?php echo remove_junk($edit_emp['first_name']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label">Last Name <span class="text-danger">*</span></label>
                      <input type="text" name="last_name" class="form-control" 
                             value="<?php echo remove_junk($edit_emp['last_name']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                      <label class="form-label">Middle Name</label>
                      <input type="text" name="middle_name" class="form-control" 
                             value="<?php echo remove_junk($edit_emp['middle_name']); ?>">
                    </div>
                  </div>
                </div>

                <!-- Employment Details Section -->
                <div class="form-section">
                  <div class="form-section-title">
                    <i class="fa-solid fa-briefcase me-2"></i>Employment Details
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">User ID</label>
                      <input type="number" name="user_id" class="form-control" id="user_id_input"
                             value="<?php echo remove_junk($edit_emp['user_id']); ?>" placeholder="Enter user ID">
                      <div class="clear-user-id">
                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearUserId">
                          <i class="fa-solid fa-times me-1"></i> Clear User ID
                        </button>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Designation <span class="text-danger">*</span></label>
                      <input type="text" name="position" class="form-control" 
                             value="<?php echo remove_junk($edit_emp['position']); ?>" required placeholder="Enter designation">
                    </div>
                  </div>
                  
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Division <span class="text-danger">*</span></label><br>
                      <select name="division" class="form-select w-100" id="divisionSelect" required>
                        <option value="">Select Division</option>
                        <?php foreach ($divisions as $div): ?>
                          <option value="<?php echo $div['id']; ?>" 
                            <?php 
                            if ($edit_emp['division'] == $div['id'] || $edit_emp['division'] == $div['division_name']) {
                                echo 'selected';
                            }
                            ?>>
                            <?php echo $div['division_name']; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Office <span class="text-danger">*</span></label><br>
                      <select name="office" class="form-select w-100" id="officeSelect" required>
                        <option value="">Select Office</option>
                        <?php foreach ($offices as $off): ?>
                          <option value="<?php echo $off['id']; ?>" 
                            <?php 
                            if ($edit_emp['office'] == $off['id'] || $edit_emp['office'] == $off['office_name']) {
                                echo 'selected';
                            }
                            ?>>
                            <?php echo $off['office_name']; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Status Section -->
                <div class="form-section">
                  <div class="form-section-title">
                    <i class="fa-solid fa-circle-info me-2"></i>Status Information
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Employment Status</label>
                      <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input status-checkbox" name="status" id="status" value="1" 
                               <?php echo $edit_emp['status'] === 'Active' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Active Employee</label>
                      </div>
                      <small class="text-muted">Check this box to set the employee as active</small>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-section">
                  <div class="action-buttons">
                    <button type="submit" name="update_employee" class="btn btn-success btn-lg">
                      <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                    </button>
                    <a href="emps.php" class="btn btn-secondary btn-lg">
                      <i class="fa-solid fa-arrow-left me-2"></i>Back to Employees
                    </a>
                    <button type="reset" class="btn btn-outline-secondary btn-lg" id="resetForm">
                      <i class="fa-solid fa-eraser me-2"></i>Reset Form
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Status checkbox functionality
  const statusCheckbox = document.getElementById('status');
  const statusPreview = document.getElementById('statusPreview');
  const userIdInput = document.getElementById('user_id_input');
  const clearUserIdBtn = document.getElementById('clearUserId');
  const resetFormBtn = document.getElementById('resetForm');

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

  // Clear User ID functionality
  clearUserIdBtn.addEventListener('click', function() {
    userIdInput.value = '';
    userIdInput.focus();
  });

  // Reset form functionality
  resetFormBtn.addEventListener('click', function() {
    // Store the original user_id value for reset
    const originalUserId = "<?php echo remove_junk($edit_emp['user_id']); ?>";
    userIdInput.value = originalUserId;
    
    // Reset status preview to original state
    const originalStatus = "<?php echo $edit_emp['status']; ?>";
    const isActive = originalStatus === 'Active';
    statusCheckbox.checked = isActive;
    updateStatusPreview(isActive);
  });

  // Office dropdown based on division selection
  const divisionSelect = document.getElementById('divisionSelect');
  const officeSelect = document.getElementById('officeSelect');

  divisionSelect.addEventListener('change', function() {
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

  // Initialize status preview
  updateStatusPreview(statusCheckbox.checked);
});
</script>

<?php include_once('layouts/footer.php'); ?>