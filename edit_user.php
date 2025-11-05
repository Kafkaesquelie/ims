<?php
$page_title = 'Edit User Profile';
require_once('includes/load.php');
page_require_level(2); 

// Fetch user
if(isset($_GET['id'])){
    $user_id = (int)$_GET['id'];
    $edit_user = find_by_id('users', $user_id);
    if(!$edit_user){
        $session->msg('d', 'User not found.');
        redirect('users.php');
    }
} else {
    redirect('users.php');
}

// Fetch departments, divisions, and offices
$divisions = find_all('divisions');
$offices = find_all('offices');

// Update user image
if(isset($_POST['submit_image'])) {
    if(isset($_FILES['user_image'])){
        $photo = new Media();
        $user_id = (int)$_POST['user_id'];
        $photo->upload($_FILES['user_image']);
        if($photo->process_user($user_id)){
            $session->msg('s', 'Photo has been uploaded.');
        } else {
            $session->msg('d', 'Failed to upload photo.');
        }
        redirect('edit_user.php?id='.$user_id);
    }
}

// Update user info
if (isset($_POST['update_user'])) {
    $req_fields = array('name','username','position','division','office','role');
    validate_fields($req_fields);

    if (empty($errors)) {
        $id            = (int)$_POST['user_id'];
        $name          = remove_junk($db->escape($_POST['name']));
        $username      = remove_junk($db->escape($_POST['username']));
        $division_id   = (int)$db->escape($_POST['division']);
        $office_id     = (int)$db->escape($_POST['office']);
        $position   = remove_junk($db->escape($_POST['position']));
        $user_level    = (int)$db->escape($_POST['role']);
        $status        = isset($_POST['status']) ? 1 : 0;

        $has_change = (
            $name !== $edit_user['name'] ||
            $username !== $edit_user['username'] ||
            (int)$division_id !== (int)$edit_user['division'] ||
            (int)$office_id !== (int)$edit_user['office'] ||
            $position !== $edit_user['position'] || 
            (int)$user_level !== (int)$edit_user['user_level'] ||
            $status !== (int)$edit_user['status']
        );

        if (! $has_change) {
            $session->msg('i', 'No changes were made.');
            redirect('edit_user.php?id=' . $id);
        }

        // Update user
        $sql = "UPDATE users SET 
                    name = '{$name}', 
                    username = '{$username}', 
                    division = '{$division_id}', 
                    office = '{$office_id}', 
                    position = '{$position}', 
                    user_level = '{$user_level}',
                    status = '{$status}'
                WHERE id = '{$id}'";

        if ($db->query($sql)) {
            $session->msg('s', 'Account updated successfully.');
            redirect('users.php');
        } else {
            $session->msg('d', 'Update Account Failed.');
            redirect('edit_user.php?id=' . $id);
        }
    } else {
        $session->msg('d', 'Please fill out all required fields.');
        redirect('edit_user.php?id=' . $_POST['user_id']);
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
    
    .role-badge {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .role-admin {
        background-color: #dc3545;
        color: white;
    }
    
    .role-it {
        background-color: #fd7e14;
        color: white;
    }
    
    .role-user {
        background-color: #20c997;
        color: white;
    }
</style>

<div class="container-fluid mt-3">
  <div class="row d-flex justify-content-center">
    <div class="col-md-11">
      <div class="card">
        <div class="card-header text-center">
          <h3 class="text-success"><i class="fa-solid fa-user-pen me-2"></i>Edit User Profile</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <!-- LEFT COLUMN: Profile Section -->
            <div class="col-md-4">
              <div class="profile-section">
                <div class="profile-image-container">
                  <div class="profile-image-preview">
                    <img src="uploads/users/<?php echo !empty($edit_user['image']) ? $edit_user['image'] : 'no_image.jpg'; ?>" 
                         alt="User Image" 
                         style="width:100%;height:100%;object-fit:cover;">
                  </div>
                  <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">
                    <div class="profile-upload-btn">
                      <button type="button" class="btn btn-outline-success btn-block">
                        <i class="fa-solid fa-camera me-2"></i>Change Profile Image
                      </button>
                      <input type="file" name="user_image" accept="image/*" required>
                    </div>
                    <small class="text-muted mt-2 d-block">Recommended: 200x200px, JPG/PNG</small>
                    <button type="submit" name="submit_image" class="btn btn-primary mt-3 w-100">
                      <i class="fa-solid fa-upload me-2"></i>Upload Image
                    </button>
                  </form>
                </div>
                
                <div class="profile-info">
                  <h5><i class="fa-solid fa-info-circle me-2"></i>Current Status</h5>
                  <div id="statusPreview" class="status-indicator <?php echo $edit_user['status'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                    <i class="fa-solid fa-circle"></i>
                    <span><?php echo $edit_user['status'] == 1 ? 'Active' : 'Inactive'; ?></span>
                  </div>
                  
                  <?php
                  $role_class = '';
                  $role_text = '';
                  switch($edit_user['user_level']) {
                      case 1:
                          $role_class = 'role-admin';
                          $role_text = 'Admin';
                          break;
                      case 2:
                          $role_class = 'role-it';
                          $role_text = 'IT';
                          break;
                      case 3:
                          $role_class = 'role-user';
                          $role_text = 'User';
                          break;
                  }
                  ?>
                  <p><strong>Current Role:</strong><br>
                    <span class="role-badge <?php echo $role_class; ?>"><?php echo $role_text; ?></span>
                  </p>
                  
                  <p><strong>Last Login:</strong><br>
                    <?php echo !empty($edit_user['last_login']) ? date('M j, Y g:i A', strtotime($edit_user['last_login'])) : 'Never'; ?>
                  </p>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN: User Form -->
            <div class="col-md-8">
              <form method="post" action="" class="user-form">
                <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">

                <!-- Personal Information Section -->
                <div class="form-section">
                  <div class="form-section-title">
                    <i class="fa-solid fa-user me-2"></i>Personal Information
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Full Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="name" 
                             value="<?php echo remove_junk($edit_user['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Username <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="username" 
                             value="<?php echo remove_junk($edit_user['username']); ?>" required>
                    </div>
                  </div>
                </div>

                <!-- Division & Office Section -->
                <div class="form-section">
                  <div class="form-section-title">
                    <i class="fa-solid fa-building me-2"></i>Division & Office
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Division <span class="text-danger">*</span></label><br>
                      <select class="form-select w-100" name="division" id="divisionSelect" required>
                        <option value="">Select Division</option>
                        <?php foreach($divisions as $div): ?>
                          <option value="<?php echo (int)$div['id']; ?>" 
                            <?php if($edit_user['division'] == $div['id']) echo 'selected'; ?>>
                            <?php echo remove_junk($div['division_name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Office <span class="text-danger">*</span></label><br>
                      <select class="form-select w-100" name="office" id="officeSelect" required>
                        <option value="">Select Office</option>
                        <?php foreach($offices as $off): ?>
                          <option value="<?php echo (int)$off['id']; ?>" 
                            <?php if($edit_user['office'] == $off['id']) echo 'selected'; ?>>
                            <?php echo remove_junk($off['office_name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Role & Designation Section -->
                <div class="form-section">
                  <div class="form-section-title">
                    <i class="fa-solid fa-user-shield me-2"></i>Role & Designation
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Designation <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="position" 
                             value="<?php echo remove_junk($edit_user['position']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">User Role <span class="text-danger">*</span></label><br>
                      <select class="form-select w-100" name="role" required>
                        <option value="1" <?php if($edit_user['user_level'] == '1') echo 'selected'; ?>>Admin</option>
                        <option value="2" <?php if($edit_user['user_level'] == '2') echo 'selected'; ?>>IT</option>
                        <option value="3" <?php if($edit_user['user_level'] == '3') echo 'selected'; ?>>User</option>
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
                      <label class="form-label">Account Status</label>
                      <div class="form-check mt-2">
                        <input class="form-check-input status-checkbox" type="checkbox" name="status" id="status"
                          value="1" <?php if($edit_user['status'] == 1) echo 'checked'; ?>>
                        <label class="form-check-label" for="status">Active User</label>
                      </div>
                      <small class="text-muted">Check this box to activate the user account</small>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-section">
                  <div class="action-buttons">
                    <button type="submit" name="update_user" class="btn btn-success btn-lg">
                      <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                    </button>
                    <a href="users.php" class="btn btn-secondary btn-lg">
                      <i class="fa-solid fa-arrow-left me-2"></i>Back to Users
                    </a>
                    <button type="reset" class="btn btn-outline-secondary btn-lg">
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
document.addEventListener("DOMContentLoaded", function() {
    // Status checkbox functionality
    const statusCheckbox = document.getElementById('status');
    const statusPreview = document.getElementById('statusPreview');

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

    // Office dropdown based on division selection
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
                    
                    // Try to preserve the originally selected office if it belongs to the new division
                    const originalOfficeId = "<?php echo $edit_user['office']; ?>";
                    if (originalOfficeId) {
                        officeSelect.value = originalOfficeId;
                    }
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