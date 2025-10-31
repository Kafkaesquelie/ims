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
$divisions = find_all('divisions'); // Add this table if not existing
$offices = find_all('offices');     // Add this table if not existing

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
    $req_fields = array('first_name','last_name','position','division','office','status');
    validate_fields($req_fields);

    if (empty($errors)) {
        $user_id  = remove_junk($db->escape($_POST['user_id']));
        $first_name  = remove_junk($db->escape($_POST['first_name']));
        $last_name   = remove_junk($db->escape($_POST['last_name']));
        $middle_name = remove_junk($db->escape($_POST['middle_name']));
        $designation = remove_junk($db->escape($_POST['position']));
        $division    = remove_junk($db->escape($_POST['division']));
        $office      = remove_junk($db->escape($_POST['office']));
        $status      = remove_junk($db->escape($_POST['status']));

        $sql = "UPDATE employees SET 
                    first_name='{$first_name}', 
                    last_name='{$last_name}', 
                    middle_name='{$middle_name}', 
                    position='{$designation}', 
                    division='{$division}', 
                    office='{$office}', 
                    status='{$status}', 
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
<div class="row d-flex justify-content-center">
  <div class="col-md-10">
    <div class="card shadow" style="border-top: 5px solid #006205; border-radius: 10px;">
      <div class="card-header text-center">
        <h3><i class="fa-solid fa-user-pen"></i> Edit Employee</h3>
      </div>
      <div class="card-body">
        <div class="row">

          <!-- LEFT COLUMN: Profile Image -->
          <div class="col-md-4 text-center border-end">
            <div class="mb-3">
              <img src="uploads/users/<?php echo !empty($edit_emp['image']) ? $edit_emp['image'] : 'no_image.jpg'; ?>" 
                   alt="Employee Image" 
                   class="img-thumbnail rounded-circle" 
                   style="width:160px;height:160px;object-fit:cover;">
            </div>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="emp_id" value="<?php echo (int)$edit_emp['id']; ?>">
              <div class="form-group text-start mt-3">
                <label for="emp_image">Change Profile</label>
                <input type="file" class="form-control" name="emp_image" accept="image/*" required>
              </div>
              <button type="submit" name="submit_image" class="btn btn-primary mt-2 w-100">Upload Image</button>
            </form>
          </div>

          <!-- RIGHT COLUMN: Employee Info -->
          <div class="col-md-8">
            <form method="post" action="">
              <input type="hidden" name="emp_id" value="<?php echo (int)$edit_emp['id']; ?>">

              <div class="row">
                <div class="col-md-4">
                  <label>First Name</label>
                  <input type="text" name="first_name" class="form-control" 
                         value="<?php echo remove_junk($edit_emp['first_name']); ?>" required>
                </div>
                <div class="col-md-4">
                  <label>Last Name</label>
                  <input type="text" name="last_name" class="form-control" 
                         value="<?php echo remove_junk($edit_emp['last_name']); ?>" required>
                </div>
                <div class="col-md-4">
                  <label>Middle Name</label>
                  <input type="text" name="middle_name" class="form-control" 
                         value="<?php echo remove_junk($edit_emp['middle_name']); ?>">
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-md-6">
                  <label>User ID</label>
                  <input type="number" name="user_id" class="form-control" 
                         value="<?php echo remove_junk($edit_emp['user_id']); ?>">
                </div>
                <div class="col-md-6">
                  <label>Designation</label>
                  <input type="text" name="position" class="form-control" 
                         value="<?php echo remove_junk($edit_emp['position']); ?>" required>
                </div>
           
              </div>

              <div class="row mt-3">
                <div class="col-md-6">
                  <label>Division</label>
                  <select name="division" class="form-control" id="divisionSelect" required>
                    <option value=""> Select Division </option>
                    <?php foreach ($divisions as $div): ?>
                      <option value="<?php echo $div['id']; ?>" 
                        <?php if ($edit_emp['division'] == $div['division_name']) echo 'selected'; ?>>
                        <?php echo $div['division_name']; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label>Office</label>
                  <select name="office" class="form-control" id="officeSelect" required>
                    <option value=""> Select Office </option>
                    <?php foreach ($offices as $off): ?>
                      <option value="<?php echo $off['id']; ?>" 
                        <?php if ($edit_emp['office'] == $off['office_name']) echo 'selected'; ?>>
                        <?php echo $off['office_name']; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-md-6">
                  <label>Status</label>
                  <select name="status" class="form-control" required>
                    <option value="Active" <?php if ($edit_emp['status'] == 'Active') echo 'selected'; ?>>Active</option>
                    <option value="Inactive" <?php if ($edit_emp['status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                  </select>
                </div>
              </div>

              <div class="form-group mt-4 d-flex justify-content-between">
                <button type="submit" name="update_employee" class="btn btn-success" style="min-width:150px;">Save</button>
                <a href="emps.php" class="btn btn-secondary" style="min-width:150px;">Back</a>
              </div>

            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('divisionSelect').addEventListener('change', function() {
    let divisionId = this.value;
    let officeSelect = document.getElementById('officeSelect');
    officeSelect.innerHTML = '<option>Loading...</option>';

    fetch(`emps.php?action=get_offices&division_id=${divisionId}`)
        .then(res => res.json())
        .then(data => {
            officeSelect.innerHTML = '<option value="">Select Office</option>';
            data.forEach(office => {
                let opt = document.createElement('option');
                opt.value = office.id;
                opt.textContent = office.office_name;
                officeSelect.appendChild(opt);
            });
        });
});

</script>
<?php include_once('layouts/footer.php'); ?> 
