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
$departments = find_all('departments');
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
    $req_fields = array('name','username','department','designation','role');
    validate_fields($req_fields);

    if (empty($errors)) {
        $id            = (int)$_POST['user_id'];
        $name          = remove_junk($db->escape($_POST['name']));
        $username      = remove_junk($db->escape($_POST['username']));
        $department_id = (int)$db->escape($_POST['department']);
        $division_id   = (int)$db->escape($_POST['division']);
        $office_id     = (int)$db->escape($_POST['office']);
        $designation   = remove_junk($db->escape($_POST['designation']));
        $user_level    = (int)$db->escape($_POST['role']);
        $status        = isset($_POST['status']) ? 1 : 0;

        $has_change = (
            $name !== $edit_user['name'] ||
            $username !== $edit_user['username'] ||
            (int)$department_id !== (int)$edit_user['department'] ||
            (int)$division_id !== (int)$edit_user['division'] ||
            (int)$office_id !== (int)$edit_user['office'] ||
            $designation !== $edit_user['position'] || // renamed field
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
                    department = '{$department_id}', 
                    division = '{$division_id}', 
                    office = '{$office_id}', 
                    position = '{$designation}', 
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
<div class="row d-flex justify-content-center">
  <div class="col-md-10">
    <div class="card shadow" style="border-top: 5px solid #006205; border-radius: 10px;">
      <div class="card-header text-center">
        <h3><i class="fa-solid fa-user-pen"></i> Edit Profile</h3>
      </div>
      <div class="card-body">
        <div class="row">
          
          <!-- LEFT COLUMN -->
          <div class="col-md-4 text-center border-end">
            <div class="mb-3">
              <img src="uploads/users/<?php echo !empty($edit_user['image']) ? $edit_user['image'] : 'no_image.jpg'; ?>" 
                   alt="User Image" 
                   class="img-thumbnail rounded-circle" 
                   style="width:160px;height:160px;object-fit:cover;">
            </div>

            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">
              <div class="form-group text-start mt-3">
                <label for="user_image">Change Profile</label>
                <input type="file" class="form-control" name="user_image" accept="image/*" required>
              </div>
              <button type="submit" name="submit_image" class="btn btn-primary mt-2 w-100">Upload Image</button>
            </form>
          </div>

          <!-- RIGHT COLUMN -->
          <div class="col-md-8">
            <form method="post" action="">
              <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">

              <div class="row">
                <div class="col-md-6">
                  <label for="name">Full Name</label>
                  <input type="text" class="form-control" name="name" value="<?php echo remove_junk($edit_user['name']); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="username">Username</label>
                  <input type="text" class="form-control" name="username" value="<?php echo remove_junk($edit_user['username']); ?>" required>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-md-4">
                  <label for="department">Department</label>
                  <select class="form-control" name="department" >
                    <option value="">Select Department</option>
                    <?php foreach($departments as $dept): ?>
                      <option value="<?php echo (int)$dept['id']; ?>" <?php if($edit_user['department'] == $dept['id']) echo 'selected'; ?>>
                        <?php echo remove_junk($dept['department']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="division">Division</label>
                  <select class="form-control" name="division" id="divisionSelect">
                    <option value="">Select Division</option>
                    <?php foreach($divisions as $div): ?>
                      <option value="<?php echo (int)$div['id']; ?>" <?php if($edit_user['division'] == $div['id']) echo 'selected'; ?>>
                        <?php echo remove_junk($div['division_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="office">Office</label>
                  <select class="form-control" name="office" id="officeSelect">
                    <option value="">Select Office</option>
                    <?php foreach($offices as $off): ?>
                      <option value="<?php echo (int)$off['id']; ?>" <?php if($edit_user['office'] == $off['id']) echo 'selected'; ?>>
                        <?php echo remove_junk($off['office_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-md-6">
                  <label for="designation">Designation</label>
                  <input type="text" class="form-control" name="designation" value="<?php echo remove_junk($edit_user['position']); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="role">User Role</label>
                  <select class="form-control" name="role" required>
                    <option value="1" <?php if($edit_user['user_level'] == '1') echo 'selected'; ?>>Admin</option>
                    <option value="2" <?php if($edit_user['user_level'] == '2') echo 'selected'; ?>>IT</option>
                    <option value="3" <?php if($edit_user['user_level'] == '3') echo 'selected'; ?>>User</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6 mt-3">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="status" id="status"
                    value="1" <?php if($edit_user['status'] == 1) echo 'checked'; ?>>
                  <label class="form-check-label" for="status">Active</label>
                </div>
              </div>

              <div class="form-group mt-4 d-flex justify-content-between">
                <button type="submit" name="update_user" class="btn btn-success" style="min-width:150px;">Save</button>
                <a href="users.php" class="btn btn-secondary" style="min-width:150px;">Back</a>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

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
                        option.value = office.office_name; // or office.id if storing ID
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

<?php include_once('layouts/footer.php'); ?>
