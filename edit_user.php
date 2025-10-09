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

// Fetch departments
$departments = find_all('departments');

// Update user image
if(isset($_POST['submit_image'])) {
    if(isset($_FILES['user_image'])){
        $photo = new Media();
        $user_id = (int)$_POST['user_id'];
        $photo->upload($_FILES['user_image']);
        if($photo->process_user($user_id)){
            $session->msg('s', 'Photo has been uploaded.');
        } else{
             $session->msg('d', 'Failed to upload photo.');

        }
        redirect('edit_user.php?id='.$user_id);
    }
}
// Update user info
if (isset($_POST['update_user'])) {
    $req_fields = array('name','username','department','position','role');
    validate_fields($req_fields);

    if (empty($errors)) {
        $id            = (int)$_POST['user_id'];
        $name          = remove_junk($db->escape($_POST['name']));
        $username      = remove_junk($db->escape($_POST['username']));
        $department_id = (int)$_POST['department'];
        $position      = remove_junk($db->escape($_POST['position']));
        $user_level    = (int)$_POST['role'];
        $status = isset($_POST['status']) ? 1 : 0;


        // Detect changes manually
        $has_change = (
            $name !== $edit_user['name'] ||
            $username !== $edit_user['username'] ||
            (int)$department_id !== (int)$edit_user['department'] ||
            $position !== $edit_user['position'] ||
            (int)$user_level !== (int)(int)$edit_user['user_level']||
            $status !== (int)$edit_user['status']

        );

        if (! $has_change) {
            $session->msg('i', 'No changes were made.');
            redirect('edit_user.php?id=' . $id);
        }

        // Run update
        $sql = "UPDATE users SET 
                    name = '{$name}', 
                    username = '{$username}', 
                    department = '{$department_id}', 
                    position = '{$position}', 
                    user_level = '{$user_level}',
                    status = '{$status}'
                WHERE id = '{$id}'";

        if ($db->query($sql)) {
            // ✅ Don’t rely on affected_rows — we already checked changes
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

<?php include_once('layouts/header.php');
?>
<div class="row d-flex justify-content-center" >
  <div class="col-md-10">
    <div class="card shadow " style="border-top: 5px solid #006205; border-radius: 10px;">
      <div class="card-header text-center">
        <h3><i class="fa-solid fa-user-pen"></i> Edit Profile</h3>
      </div>
      <div class="card-body">
        <div class="row">
          
          <!-- LEFT COLUMN (Profile Image + Upload) -->
          <div class="col-md-4 text-center border-end">
            <!-- User Profile Image -->
            <div class="mb-3">
              <img src="uploads/users/<?php echo !empty($edit_user['image']) ? $edit_user['image'] : 'no_image.jpg'; ?>" 
                   alt="User Image" 
                   class="img-thumbnail rounded-circle" 
                   style="width:160px;height:160px;object-fit:cover;">
            </div>

            <!-- Image Upload Form -->
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">
              <div class="form-group text-start mt-3">
                  <label for="user_image">Change Profile</label>
                  <input type="file" class="form-control" name="user_image" accept="image/*" required>
              </div>
              <button type="submit" name="submit_image" class="btn btn-primary mt-2 w-100">Upload Image</button>
            </form>
          </div>

          <!-- RIGHT COLUMN (User Info Fields) -->
          <div class="col-md-8">
            <form method="post" action="">
              <input type="hidden" name="user_id" value="<?php echo (int)$edit_user['id']; ?>">

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group text-start">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" name="name" 
                           value="<?php echo remove_junk($edit_user['name']); ?>" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group text-start">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" name="username" 
                           value="<?php echo remove_junk($edit_user['username']); ?>" required>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mt-3">
                  <label for="department">Department</label>
                  <select class="form-control" name="department">
                      <option value="">Select Department</option>
                      <?php foreach($departments as $dept): ?>
                          <option value="<?php echo (int)$dept['id']; ?>"
                              <?php if($edit_user['department'] == $dept['id']) echo 'selected'; ?>>
                              <?php echo remove_junk($dept['department']); ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mt-3">
                  <label for="position">Position</label>
                  <input type="text" class="form-control" name="position" 
                         value="<?php echo remove_junk($edit_user['position']); ?>" required>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mt-3">
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
                  <label class="form-check-label" for="status">
                    Active
                  </label>
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



<?php include_once('layouts/footer.php'); ?>
