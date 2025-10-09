<?php
$page_title = 'All Users';
require_once('includes/load.php');
page_require_level(2);

// Fetch all users
$all_users = find_all_user();

// Fetch departments and roles
$departments = find_all('departments');
$roles = find_all('user_groups');

// Handle Add User form submission
if(isset($_POST['add_user'])){
    $name = $db->escape($_POST['name']);
    $username = $db->escape($_POST['username']);
    $password = $db->escape($_POST['password']);
    $dep_id = (int)$db->escape($_POST['dep_id']); // matches your form
    $pos = $db->escape($_POST['position']);
    $role_id = (int)$db->escape($_POST['role_id']);
    $status = isset($_POST['status']) ? 1 : 0;

    // Check duplicate username
    $check_sql = "SELECT id FROM users WHERE username='{$username}' LIMIT 1";
    $check_result = $db->query($check_sql);
    if($db->num_rows($check_result) > 0){
        $session->msg('d','Username already exists.');
    } else {
        // Get the group_level corresponding to the selected role_id
        $group = find_by_id('user_groups', $role_id); // make sure this function exists
        if($group){
            $user_level = (int)$group['group_level'];
            $password_hash = sha1($password);

            $sql = "INSERT INTO users (name, username, password, department, position, user_level, status) VALUES ";
            $sql .= "('{$name}', '{$username}', '{$password_hash}', '{$dep_id}', '{$pos}', '{$user_level}', '{$status}')";

            if($db->query($sql)){
                $session->msg('s','User added successfully.');
                redirect('users.php', false);
            } else {
                $session->msg('d','Failed to add user.');
            }
        } else {
            $session->msg('d','Invalid role selected.');
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
    
    /* Modal styling */
    .modal-header {
        background: linear-gradient(135deg, #006205, #28a745);
        color: white;
        border-bottom: none;
    }
    
    .modal-header .btn-close {
        filter: invert(1);
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
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
    
    .modal-footer {
        border-top: 1px solid #e9ecef;
        padding: 1.5rem;
    }
</style>

<!--begin::Row--> 
<div class="row mb-3"> 
    <div class="col-sm-6"> 
        <!-- <h3 class="mb-0">Manage Users</h3>  -->
    </div> 
    <div class="col-sm-6"> 
        <ol class="breadcrumb float-sm-right"> 
            <li class="breadcrumb-item"><a href="admin.php">Home</a></li> 
            <li class="breadcrumb-item active" aria-current="page"> Manage User</li> 
        </ol> 
    </div> 
</div>

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

    <!-- Add New User Button - Now triggers modal -->
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="fa-solid fa-user-plus ml-2"></i> Add New User
    </button>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">
          <i class="fa-solid fa-user-plus me-2"></i>Add New User
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="" class="user-form">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" name="username" id="username" class="form-control" required>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="position" class="form-label">Position/Office</label>
              <input type="text" name="position" id="position" class="form-control" required>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="dep_id" class="form-label">Department</label>
              <select name="dep_id" id="dep_id" class="form-select" required>
                <option value="">Select Department</option>
                <?php foreach($departments as $dep): ?>
                  <option value="<?php echo (int)$dep['id']; ?>"><?php echo remove_junk($dep['department']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="role_id" class="form-label">Role</label>
              <select name="role_id" id="role_id" class="form-select" required>
                <option value="">Select Role</option>
                <?php foreach($roles as $role): ?>
                  <option value="<?php echo (int)$role['id']; ?>"><?php echo remove_junk($role['group_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-12 mb-3">
              <div class="form-check">
                <input type="checkbox" class="form-check-input status-checkbox" name="status" id="status">
                <label class="form-check-label" for="status">Active User</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="reset" class="btn btn-outline-secondary">Clear</button>
          <button type="submit" name="add_user" class="btn btn-success">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Users Table -->
<div class="card justify-content-center" id="usersTableWrapper" style="border-top: 5px solid #28a745; border-radius: 10px;">
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
                    <th>Department</th>               
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Last Edited</th>
                    <th>User Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach($all_users as $a_user): ?>
    <tr>
        <td class="text-center">
            <img src="uploads/users/<?php echo !empty($a_user['image']) ? $a_user['image'] : 'default.jpg'; ?>" 
                 class="img-thumbnail" style="width:60px;height:60px;border-radius:50%">
        </td>
        <td>
            <strong><?php echo remove_junk(ucwords($a_user['name'])); ?></strong><br>
            <small class="text-muted"><?php echo remove_junk(ucwords($a_user['position'])); ?></small>
        </td>
        <td><?php echo remove_junk(ucwords($a_user['username']))?></td>
        <td><?php echo remove_junk(ucwords($a_user['dep_name']))?></td>
        <td class="text-center">
        <?php echo $a_user['status']==1 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-danger">Inactive</span>'; ?>
    </td>

        <td><?php echo $a_user['last_login']?></td>
        <td><?php echo $a_user['last_edited']?></td>
        <td class="text-center"><?php echo remove_junk(ucwords($a_user['group_name']))?></td>
        <td class="text-center">
            <div class="btn-group">
                <a href="edit_user.php?id=<?php echo (int)$a_user['id'];?>" class="btn btn-warning btn-md" title="Edit">
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
    <?php endforeach;?>
</tbody>

            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.archive-btn').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault(); // stop normal link action
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
          // Redirect only if confirmed
          window.location.href = url;
        }
      });
    });
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
    $(document).ready(function () {
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
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const table = document.getElementById("usersTable");
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