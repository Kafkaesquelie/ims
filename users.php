<?php
$page_title = 'All Users';
require_once('includes/load.php');
page_require_level(2);

// ✅ Handle AJAX request for offices
if (isset($_GET['action']) && $_GET['action'] === 'get_offices' && isset($_GET['division_id'])) {
  $division_id = (int)$_GET['division_id'];
  $sql = "SELECT id, office_name FROM offices WHERE division_id = '{$division_id}' ORDER BY office_name ASC";
  $result = $db->query($sql);

  $offices = [];
  while ($row = $db->fetch_assoc($result)) {
    $offices[] = $row;
  }

  echo json_encode($offices);
  exit; // important to stop further output
}

// Fetch all users
$all_users = find_all_user();

// Fetch departments and roles
$roles = find_all('user_groups');
// Fetch divisions and offices
$divisions = find_all('divisions');
$offices = find_all('offices');

// Handle Add User form submission
if (isset($_POST['add_user'])) {
  $name = $db->escape($_POST['name']);
  $username = $db->escape($_POST['username']);
  $password = $db->escape($_POST['password']);
  $pos = $db->escape($_POST['position']);
  $role_id = (int)$db->escape($_POST['role_id']);
  $status = isset($_POST['status']) ? 1 : 0;

  // Check duplicate username
  $check_sql = "SELECT id FROM users WHERE username='{$username}' LIMIT 1";
  $check_result = $db->query($check_sql);
  if ($db->num_rows($check_result) > 0) {
    $session->msg('d', 'Username already exists.');
  } else {
    // Get the group_level corresponding to the selected role_id
    $group = find_by_id('user_groups', $role_id); // make sure this function exists
    if ($group) {
      $user_level = (int)$group['group_level'];
      $password_hash = sha1($password);

      $sql = "INSERT INTO users (name, username, password,  division, office, position, user_level, status)
        VALUES ('{$name}', '{$username}', '{$password_hash}', '{$db->escape($_POST['division'])}', '{$db->escape($_POST['office'])}', '{$pos}', '{$user_level}', '{$status}')";


      if ($db->query($sql)) {
        $session->msg('s', 'User added successfully.');
        redirect('users.php', false);
      } else {
        $session->msg('d', 'Failed to add user.');
      }
    } else {
      $session->msg('d', 'Invalid role selected.');
    }
  }
}
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

<!-- Add Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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

  /* Profile section styling */
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

  .profile-image-preview.has-image {
    background: #fff;
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

  .table th {
    background: #005113ff;
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
    text-align: center;
  }

  /* Action buttons */
  .action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
  }

  /* Form container styling */
  #addUserForm {
    border-top: 5px solid #006205;
    border-radius: 10px;
    background: white;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
  }

  /* Card styling for consistency */
  .card {
    border-top: 5px solid #28a745;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
  }
</style>

<!-- Search and Add User Section -->
<div class="row mb-3 align-items-center">
  <div class="col-md-6">
  </div>

  <div class="col-md-6 d-flex flex-column flex-md-row justify-content-md-end justify-content-center gap-3">
    <!-- Search Box -->
    <div class="search-box flex-grow-1 mr-2" style="max-width: 300px;">
      <i class="fas fa-search search-icon"></i>
      <input type="text" class="form-control" placeholder="Search users" id="searchInput">
    </div>

    <!-- Add New User Button -->
    <button class="btn btn-success" id="showAddUserForm">
      <i class="fa-solid fa-user-plus ml-2"></i> Add New User
    </button>


  </div>
</div>

<!-- Users Table (Visible by default) -->
<div class="card" id="usersTableWrapper">
  <div class="card-header">
    <h3 class="card-title"><i class="fa-solid fa-users-gear"></i> User Records</h3>
  </div>
  <div class="card-body p-3">
    <div class="table-responsive">
      <table class="table table-hover text-nowrap" id="usersTable">
        <thead>
          <tr>
            <th>Profile</th>
            <th>Name</th>
            <th>Username</th>
            <th>Office</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Last Edited</th>
            <th>User Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_users as $a_user): ?>
            <tr>
              <td class="text-center">
                <img src="uploads/users/<?php echo !empty($a_user['image']) ? $a_user['image'] : 'default.jpg'; ?>"
                  class="img-thumbnail" style="width:60px;height:60px;border-radius:50%">
              </td>
              <td>
                <strong><?php echo remove_junk(ucwords($a_user['name'])); ?></strong><br>
                <small class="text-muted"><?php echo remove_junk(ucwords($a_user['position'])); ?></small>
              </td>
              <td><?php echo remove_junk(ucwords($a_user['username'])) ?></td>
              <td><?php echo remove_junk(ucwords($a_user['office_name'])) ?></td>
              <td class="text-center">
                <?php echo $a_user['status'] == 1
                  ? '<span class="badge bg-success">Active</span>'
                  : '<span class="badge bg-danger">Inactive</span>'; ?>
              </td>

              <td><?php echo $a_user['last_login'] ?></td>
              <td><?php echo $a_user['last_edited'] ?></td>
              <td class="text-center"><?php echo remove_junk(ucwords($a_user['group_name'])) ?></td>
              <td class="text-center">
                <div class="btn-group">
                  <a href="edit_user.php?id=<?php echo (int)$a_user['id']; ?>" class="btn btn-warning btn-md" title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </a>
                  <a href="a_script.php?id=<?php echo (int)$a_user['id']; ?>"
                    class="btn btn-danger btn-md archive-btn"
                    data-id="<?php echo (int)$a_user['id']; ?>"
                    title="Archive">
                    <span><i class="fa-solid fa-file-zipper"></i></span>
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

<!-- Add User Form (Hidden by default) -->
<div id="addUserForm" class="card" style="display:none;">
  <div class="card-header">
    <h3 class="card-title text-success"><i class="fa-solid fa-user-plus"></i> Add New User</h3>
  </div>
  <form method="POST" action="" class="user-form" enctype="multipart/form-data">
    <div class="row no-gutters">
      <!-- Profile Section - Left Side -->
      <div class="col-md-4">
        <div class="profile-section">
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
          </div>

          <div class="profile-info">
            <h5><i class="fa-solid fa-info-circle me-2"></i>Profile Information</h5>
            <p><strong>Status:</strong> <span id="statusPreview">Inactive</span></p>
            <p><strong>Role:</strong> <span id="rolePreview">Not selected</span></p>
            <p><strong>Division:</strong> <span id="divisionPreview">Not selected</span></p>
            <p><strong>Office:</strong> <span id="officePreview">Not selected</span></p>
          </div>
        </div>
      </div>

      <!-- Form Fields - Right Side -->
      <div class="col-md-8">
        <div class="form-content p-4">
          <!-- Personal Information Section -->
          <div class="form-section">
            <div class="form-section-title">
              <i class="fa-solid fa-user me-2"></i>Personal Information
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" id="username" class="form-control" required>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" id="password" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                <input type="text" name="position" id="designation" class="form-control" required>
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
                <label for="division" class="form-label">Division <span class="text-danger">*</span></label>
                <select name="division" id="division" class="form-select" required>
                  <option value="">Select Division</option>
                  <?php foreach ($divisions as $div): ?>
                    <option value="<?php echo (int)$div['id']; ?>"><?php echo remove_junk($div['division_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="office" class="form-label">Office <span class="text-danger">*</span></label>
                <select name="office" id="office" class="form-select" required>
                  <option value="">Select Office</option>
                  <?php foreach ($offices as $off): ?>
                    <option value="<?php echo (int)$off['id']; ?>"><?php echo remove_junk($off['office_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Role & Status Section -->
          <div class="form-section">
            <div class="form-section-title">
              <i class="fa-solid fa-user-shield me-2"></i>Role & Status
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role_id" id="role_id" class="form-select" required>
                  <option value="">Select Role</option>
                  <?php foreach ($roles as $role): ?>
                    <option value="<?php echo (int)$role['id']; ?>"><?php echo remove_junk($role['group_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Status</label>
                <div class="form-check mt-2">
                  <input type="checkbox" class="form-check-input status-checkbox" name="status" id="status" value="1">
                  <label class="form-check-label" for="status">Active User</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="form-section">
            <div class="action-buttons">
              <button type="submit" name="add_user" class="btn btn-success btn-lg">
                <i class="fa-solid fa-floppy-disk me-2"></i>Save User
              </button>
              <button type="reset" class="btn btn-outline-secondary btn-lg">
                <i class="fa-solid fa-eraser me-2"></i>Clear Form
              </button>
              <!-- Back to Users Button (hidden by default) -->
              <button class="btn btn-secondary" id="backToUsers" style="display: none;">
                <i class="fa-solid fa-arrow-left ml-2"></i> Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Archive user confirmation
    document.querySelectorAll('.archive-btn').forEach(function(button) {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const catId = this.dataset.id;
        const url = this.getAttribute('href');

        Swal.fire({
          title: 'Are you sure?',
          text: "This user will be archived.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Archive'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = url;
          }
        });
      });
    });

    // Get DOM elements
    const showAddUserFormBtn = document.getElementById('showAddUserForm');
    const backToUsersBtn = document.getElementById('backToUsers');
    const usersTableWrapper = document.getElementById('usersTableWrapper');
    const addUserForm = document.getElementById('addUserForm');

    // Show add user form when button is clicked
    showAddUserFormBtn.addEventListener('click', function() {
      usersTableWrapper.style.display = 'none';
      addUserForm.style.display = 'block';
      showAddUserFormBtn.style.display = 'none';
      backToUsersBtn.style.display = 'inline-block';
    });

    // Back to users table
    backToUsersBtn.addEventListener('click', function() {
      usersTableWrapper.style.display = 'block';
      addUserForm.style.display = 'none';
      showAddUserFormBtn.style.display = 'inline-block';
      backToUsersBtn.style.display = 'none';
      // Reset form
      document.querySelector('form').reset();
      resetImagePreview();
      resetPreviews();
    });

    // Image preview functionality
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');

    imageInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'profile-image-preview has-image';
          img.alt = 'Profile Preview';
          img.style.objectFit = 'cover';
          img.style.width = '100%';
          img.style.height = '100%';
          imagePreview.innerHTML = '';
          imagePreview.appendChild(img);
        }
        reader.readAsDataURL(file);
      } else {
        resetImagePreview();
      }
    });

    function resetImagePreview() {
      imagePreview.innerHTML = `
      <div class="no-image-placeholder">
        <i class="fa-solid fa-user"></i>
        <span>No Image Selected</span>
      </div>
    `;
      imagePreview.className = 'profile-image-preview';
    }

    function resetPreviews() {
      document.getElementById('statusPreview').textContent = 'Inactive';
      document.getElementById('rolePreview').textContent = 'Not selected';
      document.getElementById('divisionPreview').textContent = 'Not selected';
      document.getElementById('officePreview').textContent = 'Not selected';
    }

    // Real-time preview updates
    const statusCheckbox = document.getElementById('status');
    const roleSelect = document.getElementById('role_id');
    const divisionSelect = document.getElementById('division');
    const officeSelect = document.getElementById('office');

    statusCheckbox.addEventListener('change', function() {
      document.getElementById('statusPreview').textContent = this.checked ? 'Active' : 'Inactive';
    });

    roleSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      document.getElementById('rolePreview').textContent = selectedOption.text || 'Not selected';
    });

    divisionSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      document.getElementById('divisionPreview').textContent = selectedOption.text || 'Not selected';
    });

    officeSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      document.getElementById('officePreview').textContent = selectedOption.text || 'Not selected';
    });

    // Office dropdown based on division selection
    divisionSelect.addEventListener('change', function() {
      const divisionId = this.value;
      officeSelect.innerHTML = '<option value="">Loading...</option>';

      if (divisionId) {
        fetch(`users.php?action=get_offices&division_id=${divisionId}`)
          .then(response => response.json())
          .then(data => {
            officeSelect.innerHTML = '<option value="">Select Office</option>';
            data.forEach(office => {
              const option = document.createElement('option');
              option.value = office.id;
              option.textContent = office.office_name;
              officeSelect.appendChild(option);
            });
            // Update preview
            document.getElementById('officePreview').textContent = 'Not selected';
          })
          .catch(() => {
            officeSelect.innerHTML = '<option value="">Error loading offices</option>';
          });
      } else {
        officeSelect.innerHTML = '<option value="">Select Office</option>';
        document.getElementById('officePreview').textContent = 'Not selected';
      }
    });
  });
</script>

<?php include_once('layouts/footer.php'); ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    var table = $('#usersTable').DataTable({
      pageLength: 5,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: false,
      autoWidth: false,
      fixedColumns: true
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    });
  });
</script>