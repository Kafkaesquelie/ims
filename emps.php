<?php
$page_title = 'Employees';
require_once('includes/load.php');
page_require_level(2);

//  Handle AJAX request for offices by division
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
    $designation = $db->escape($_POST['position']);
    $division    = $db->escape($_POST['division']);
    $office      = $db->escape($_POST['office']);
    $status      = $db->escape($_POST['status']);


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
('{$user_id}', '{$first_name}', '{$last_name}', '{$middle_name}', '{$designation}', '{$division}', '{$office}', '{$department}', '{$status}', '{$image_name}', NOW(), NOW())";


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
            <th>Status</th>
            
            <th style="width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $emp): ?>
            <tr>
              <td><?php echo $emp['user_id']; ?></td>
              <td class="text-center">
                <img src="uploads/users/<?php echo !empty($emp['image']) ? $emp['image'] : 'no_image.jpg'; ?>" 
                     class="img-thumbnail" style="width:60px;height:60px;border-radius:50%">
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
    <div class="row p-3">

      <div class="col-md-4 mb-3">
        <label>First Name</label>
        <input type="text" name="first_name" class="form-control" required>
      </div>
      <div class="col-md-4 mb-3">
        <label>Last Name</label>
        <input type="text" name="last_name" class="form-control" required>
      </div>
      <div class="col-md-4 mb-3">
        <label>Middle Name</label>
        <input type="text" name="middle_name" class="form-control">
      </div>

      <div class="col-md-3 mb-3">
        <label>User ID</label>
        <input type="number" name="user_id" class="form-control">
      </div>

      <div class="col-md-3 mb-3">
        <label>Designation</label>
        <input type="text" name="designation" class="form-control" required>
      </div>

      <!-- Division Dropdown -->
      <div class="col-md-3 mb-3">
        <label>Division</label>
        <select name="division" class="form-control"  id="divisionSelect">
          <option value="">Select Division</option>
          <?php
            $divisions = find_by_sql("SELECT id, division_name FROM divisions ORDER BY division_name ASC");
            foreach ($divisions as $div):
          ?>
            <option value="<?php echo $div['id']; ?>"><?php echo $div['division_name']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Office Dropdown -->
      <div class="col-md-3 mb-3">
        <label>Office</label>
        <select name="office" class="form-control"  id="officeSelect">
          <option value="">Select Office</option>
          <?php
            $offices = find_by_sql("SELECT id, office_name FROM offices ORDER BY office_name ASC");
            foreach ($offices as $off):
          ?>
            <option value="<?php echo $off['id']; ?>"><?php echo $off['office_name']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Department Dropdown -->
      <div class="col-md-4 mb-3">
        <label>Department</label>
        <select name="department" class="form-control" required>
          <option value="">Select Department</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?php echo $dept['dpt']; ?>"><?php echo $dept['dpt']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label>Status</label>
        <select name="status" class="form-control" required>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label>Image</label>
        <input type="file" name="image" class="form-control">
      </div>

      <div class="col-md-12 mt-3">
        <button type="submit" class="btn btn-success">Save Employee</button>
        <button type="button" id="btnCancel" class="btn btn-secondary ml-3">Cancel</button>
      </div>
    </div>
  </form>
</div>

</div>


<script>
document.addEventListener("DOMContentLoaded", function() {
  const addBtn = document.getElementById('btnAddEmployee');
  const cancelBtn = document.getElementById('btnCancel');
  const addForm = document.getElementById('addEmployeeForm');
  const tableWrapper = document.getElementById('employeeTableWrapper');

  addBtn.addEventListener('click', () => {
    addForm.style.display = 'block';
    tableWrapper.style.display = 'none';
    addBtn.style.display = 'none';
  });

  cancelBtn.addEventListener('click', () => {
    addForm.style.display = 'none';
    tableWrapper.style.display = 'block';
    addBtn.style.display = 'inline-block';
  });
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
            option.value = office.office_name; // you can also store office.id if you prefer
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
