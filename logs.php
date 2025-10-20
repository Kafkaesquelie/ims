<?php
$page_title = 'All Requests Logs';
require_once('includes/load.php');
page_require_level(1);

// Fetch all approved/rejected requests
$requests = find_all_req_logs();
// Fetch supply & equipment transactions

$par_transactions = find_all_par_transactions();
$ics_transactions = find_all_ics_transactions();


?>

<?php include_once('layouts/header.php'); ?>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="row mb-3">
    <div class="col-sm-6">
      <h5 class="mb-0"> <i class="nav-icon fas fa-chart-bar"></i> Manage Transactions</h5>
    </div>
    <div class="col-sm-6">
      <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
        <li class="breadcrumb-item active"><a href="requests.php">Requests</a></li>
        <li class="breadcrumb-item active" aria-current="page">Transactions</li>
      </ol>
    </div>
  </div>



  <!-- Logs Table -->
  <div class="card">
    <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
      <h3 class="card-title"> <i class="nav-icon fas fa-box-open"></i> Stock Requests</h3>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="datatable" class="table table-striped table-hover">
          <thead>
            <tr>
              <th>RIS NO</th>
              <th>Profile</th>
              <th>Requested By</th>
              <th>Office</th>
              <th>Items</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $req): ?>
              <tr>
                <td> <strong>
                    <?php echo remove_junk($req['ris_no']); ?> </strong>
                </td>
                <td class="text-center">
                  <img src="uploads/users/<?php echo remove_junk($req['prof_pic']); ?>"
                    alt="Profile"
                    class="img-circle"
                    style="width:50px; height:50px; object-fit:cover;">
                </td>
                <td><strong><?php echo remove_junk($req['req_name']); ?></strong><br>
                  <small><?php echo remove_junk($req['req_position']); ?></small>
                </td>
                <td>
                  <?php echo remove_junk($req['office_name']); ?>
                </td>
                <td>
                  <?php echo remove_junk(get_request_items_list($req['id'])); ?>
                </td>
                <td class="text-center">
                  <?php echo date("M d, Y ", strtotime($req['date'])); ?>
                </td>
                <td class="text-center">
                  <?php if ($req['status'] == 'Completed'): ?>
                    <span class="badge bg-success"><?php echo ucfirst($req['status']); ?></span>
                  <?php elseif ($req['status'] == 'Archived'): ?>
                    <span class="badge bg-danger"><?php echo ucfirst($req['status']); ?></span>
                  <?php else: ?>
                    <span class="badge bg-primary"><?php echo ucfirst($req['status']); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="print_ris.php?id=<?php echo (int)$req['id']; ?>"
                    class="btn btn-success btn-sm"
                    title="View Request">
                    <i class="fa fa-eye"></i>
                  </a>
                  

                  <a href="a_script.php?id=<?php echo (int)$req['id']; ?>"
                    class="btn btn-danger btn-md archive-btn"
                    data-id="<?php echo (int)$req['id']; ?>"
                    data-ris="<?php echo remove_junk($req['ris_no']); ?>"
                    title="Archive">
                    <span><i class="fa-solid fa-file-zipper"></i></span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>



<!-- 🟦 ICS Transactions Table -->
<div class="card">
  <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <h3 class="card-title"><i class="nav-icon fas fa-file-invoice"></i> ICS Transactions</h3>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="icsTable" class="table table-striped table-hover">
        <thead>
          <tr>
            <th>ICS No</th>
            <th>Profile</th>
            <th>Employee</th>
            <th>Office</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Date Issued</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ics_transactions as $ics): ?>
            <tr>
              <td><strong><?php echo remove_junk($ics['ics_no']); ?></strong></td>
              </td>
              <td class="text-center">
                <img src="uploads/users/<?php echo remove_junk($ics['image']); ?>"
                  alt="Profile"
                  class="img-circle"
                  style="width:50px; height:50px; object-fit:cover;">
              </td>
              <td><strong><?php echo remove_junk($ics['employee_name']); ?></strong></td>
              <td><?php echo remove_junk($ics['department']); ?></td>
              <td><?php echo remove_junk($ics['item_name']); ?></td>
              <td><?php echo (int)$ics['quantity']; ?></td>
              <td><?php echo date('M d, Y', strtotime($ics['transaction_date'])); ?></td>
              <td>
                <?php if ($ics['status'] == 'Returned'): ?>
                  <span class="badge bg-warning">Returned</span>
                <?php elseif ($ics['status'] == 'Issued'): ?>
                  <span class="badge bg-info">Issued</span>
                <?php elseif ($ics['status'] == 'Re-issued'): ?>
                  <span class="badge bg-primary">Re-issued</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo ucfirst($ics['status']); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <a href="ics_view.php?id=<?php echo (int)$ics['id']; ?>"
                  class="btn btn-success btn-sm" title="View ICS">
                  <i class="fa fa-eye"></i>
                </a>

              </td>
            </tr>
          <?php endforeach; ?> 
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- 🟩 PAR Transactions Table -->
<div class="card mb-4">
  <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <h3 class="card-title"><i class="nav-icon fas fa-file-contract"></i> PAR Transactions</h3>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="parTable" class="table table-striped table-hover">
        <thead>
          <tr>
            <th>PAR No</th>
            <th>Profile</th>
            <th>Employee</th>
            <th>Office</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Date Issued</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($par_transactions as $par): ?>
            <tr>
              <td><strong><?php echo remove_junk($par['par_no']); ?></strong></td>
              </td>
              <td class="text-center">
                <img src="uploads/users/<?php echo remove_junk($par['image']); ?>"
                  alt="Profile"
                  class="img-circle"
                  style="width:50px; height:50px; object-fit:cover;">
              </td>
              <td><strong><?php echo remove_junk($par['employee_name']); ?></strong></td>
              <td><?php echo remove_junk($par['department']); ?></td>
              <td><?php echo remove_junk($par['item_name']); ?></td>
              <td><?php echo (int)$par['quantity']; ?></td>
              <td><?php echo date('M d, Y', strtotime($par['transaction_date'])); ?></td>
              <td>
                <?php if ($par['status'] == 'Returned'): ?>
                  <span class="badge bg-warning">Returned</span>
                <?php elseif ($par['status'] == 'Issued'): ?>
                  <span class="badge bg-info">Issued</span>
                <?php elseif ($par['status'] == 'Re-issued'): ?>
                  <span class="badge bg-primary">Re-issued</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo ucfirst($par['status']); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <a href="par_view.php?id=<?php echo (int)$par['id']; ?>"
                  class="btn btn-success btn-sm" title="View PAR">
                  <i class="fa fa-eye"></i>
                </a>
                 

              </td>
            </tr>
          <?php endforeach; ?>
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
        const risNo = this.dataset.ris;
        const url = this.getAttribute('href');

        Swal.fire({
          title: 'Archive Request?',
          html: `<strong>RIS No: ${risNo}</strong><br>Are you sure you want to archive this request?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes, archive it!',
          cancelButtonText: 'Cancel',
          reverseButtons: true,
          customClass: {
            title: 'swal2-title-custom',
            htmlContainer: 'swal2-html-custom'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            // Show loading message
            Swal.fire({
              title: 'Archiving...',
              text: 'Please wait while we archive the request.',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            // Redirect to archive script
            window.location.href = url;
          }
        });
      });
    });

    // Check if archive was successful from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('archive') === 'success') {
      Swal.fire({
        title: 'Success!',
        text: 'Request has been archived successfully.',
        icon: 'success',
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
      });
      
      // Clean URL
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    }

    // Check if archive failed
    if (urlParams.get('archive') === 'failed') {
      Swal.fire({
        title: 'Error!',
        text: 'Failed to archive the request. Please try again.',
        icon: 'error',
        confirmButtonColor: '#dc3545'
      });
      
      // Clean URL
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    }
  });
</script>

<style>
.swal2-title-custom {
  color: #dc3545 !important;
  font-weight: 600;
}
.swal2-html-custom {
  font-size: 16px;
}
</style>

<?php include_once('layouts/footer.php'); ?>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>


<script>
  $(document).ready(function() {
    $('#datatable').DataTable({
      "pageLength": 5,
      "lengthMenu": [5, 10, 25, 50, 100],
      "order": [
        [4, "desc"]
      ],

      // ✅ Fix column widths
      "columnDefs": [{
          "width": "14%",
          "targets": 0
        }, // #
        {
          "width": "10%",
          "targets": 1
        }, // Requested By       
        {
          "width": "15%",
          "targets": 2
        },
        {
          "width": "10%",
          "targets": 3
        },
        {
          "width": "18%",
          "targets": 4
        },
        {
          "width": "10%",
          "targets": 5
        },
        {
          "width": "10%",
          "targets": 6
        },
        {
          "width": "12%",
          "targets": 7
        }
      ],
      "autoWidth": false // Important: disables automatic resizing

    });
  });
</script>

<script>
  $(document).ready(function() {
    $('#transactionsTable').DataTable({
      "pageLength": 5,
      "lengthMenu": [5, 10, 25, 50, 100],
      "order": [
        [4, "desc"]
      ],



    });

  });
</script>

<script>
  $(document).ready(function() {
    $('#parTable').DataTable({
      "pageLength": 5,
      "lengthMenu": [5, 10, 25, 50, 100],
      "order": [
        [5, "desc"]
      ],
    });
    $('#icsTable').DataTable({
      "pageLength": 5,
      "lengthMenu": [5, 10, 25, 50, 100],
      "order": [
        [5, "desc"]
      ],
    });
  });
</script>