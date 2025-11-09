<?php
$page_title = 'All Requests Logs';
require_once('includes/load.php');
if (!$session->isUserLoggedIn()) {
  header("Location: admin.php");
  exit();
}
page_require_level(1);

// Fetch all approved/rejected requests
$requests = find_all_req_logs();

// Fetch and group ICS transactions by ICS number with proper status calculation
$ics_grouped = find_all_ics_documents();
$ics_transactions = find_all_ics_transactions();
foreach ($ics_transactions as $ics) {
    $ics_no = $ics['ics_no'];
    if (!isset($ics_grouped[$ics_no])) {
        $ics_grouped[$ics_no] = [
            'ics_no' => $ics_no,
            'employee_name' => $ics['employee_name'],
            'department' => $ics['department'],
            'image' => $ics['image'],
            'transaction_date' => $ics['transaction_date'],
            'items' => [],
            'total_quantity' => 0,
            'status' => $ics['status']
        ];
    }
    $ics_grouped[$ics_no]['items'][] = [
        'item_name' => $ics['item_name'],
        'quantity' => $ics['quantity']
    ];
    $ics_grouped[$ics_no]['total_quantity'] += $ics['quantity'];
}

// Calculate document-level status for ICS
foreach ($ics_grouped as &$ics_doc) {
    $ics_doc['status'] = calculate_document_status($ics_doc['items'], $ics_doc['ics_no'], 'ics');
}

// Fetch and group PAR transactions by PAR number with proper status calculation
$par_grouped = find_all_par_documents();
$par_transactions = find_all_par_transactions();
foreach ($par_transactions as $par) {
    $par_no = $par['par_no'];
    if (!isset($par_grouped[$par_no])) {
        $par_grouped[$par_no] = [
            'par_no' => $par_no,
            'employee_name' => $par['employee_name'],
            'department' => $par['department'],
            'image' => $par['image'],
            'transaction_date' => $par['transaction_date'],
            'items' => [],
            'total_quantity' => 0,
            'status' => $par['status']
        ];
    }
    $par_grouped[$par_no]['items'][] = [
        'item_name' => $par['item_name'],
        'quantity' => $par['quantity']
    ];
    $par_grouped[$par_no]['total_quantity'] += $par['quantity'];
}

// Calculate document-level status for PAR
foreach ($par_grouped as &$par_doc) {
    $par_doc['status'] = calculate_document_status($par_doc['items'], $par_doc['par_no'], 'par');
}

/**
 * Calculate document status based on return status of all items
 */
function calculate_document_status($items, $doc_no, $doc_type) {
    global $db;
    
    // Count total items and returned items
    $total_items = count($items);
    $returned_items = 0;
    $partially_returned_items = 0;
    
    if ($doc_type === 'ics') {
        // For ICS documents - check return_items table
        $sql = "SELECT COUNT(DISTINCT t.item_id) as returned_count 
                FROM return_items ri 
                JOIN transactions t ON ri.transaction_id = t.id 
                WHERE t.ICS_No = '{$doc_no}'";
    } else {
        // For PAR documents - check return_items table
        $sql = "SELECT COUNT(DISTINCT t.properties_id) as returned_count 
                FROM return_items ri 
                JOIN transactions t ON ri.transaction_id = t.id 
                WHERE t.PAR_No = '{$doc_no}'";
    }
    
    $result = $db->query($sql);
    $returned_count = 0;
    if ($result && $db->num_rows($result) > 0) {
        $data = $db->fetch_assoc($result);
        $returned_count = $data['returned_count'];
    }
    
    // Determine status based on returned items count
    if ($returned_count == 0) {
        return 'Issued';
    } elseif ($returned_count > 0 && $returned_count < $total_items) {
        return 'Partially Returned';
    } else {
        return 'Returned';
    }
}

/**
 * Alternative function to calculate status based on quantity returned
 */
function calculate_document_status_by_quantity($doc_no, $doc_type) {
    global $db;
    
    if ($doc_type === 'ics') {
        $sql = "SELECT 
                    t.id,
                    t.quantity as issued_qty,
                    COALESCE(SUM(ri.qty), 0) as returned_qty
                FROM transactions t
                LEFT JOIN return_items ri ON t.id = ri.transaction_id
                WHERE t.ICS_No = '{$doc_no}'
                GROUP BY t.id";
    } else {
        $sql = "SELECT 
                    t.id,
                    t.quantity as issued_qty,
                    COALESCE(SUM(ri.qty), 0) as returned_qty
                FROM transactions t
                LEFT JOIN return_items ri ON t.id = ri.transaction_id
                WHERE t.PAR_No = '{$doc_no}'
                GROUP BY t.id";
    }
    
    $result = $db->query($sql);
    $total_items = 0;
    $fully_returned_items = 0;
    $partially_returned_items = 0;
    $not_returned_items = 0;
    
    if ($result && $db->num_rows($result) > 0) {
        while ($data = $db->fetch_assoc($result)) {
            $total_items++;
            $issued_qty = $data['issued_qty'];
            $returned_qty = $data['returned_qty'];
            
            if ($returned_qty >= $issued_qty) {
                $fully_returned_items++;
            } elseif ($returned_qty > 0) {
                $partially_returned_items++;
            } else {
                $not_returned_items++;
            }
        }
    }
    
    // Determine document status
    if ($fully_returned_items == $total_items) {
        return 'Returned';
    } elseif ($fully_returned_items > 0 || $partially_returned_items > 0) {
        return 'Partially Returned';
    } else {
        return 'Issued';
    }
}
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
                  <?php echo date("M d, Y ", strtotime($req['date_completed'])); ?>
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
                  <a href="ris_view.php?id=<?php echo (int)$req['id']; ?>"
                    class="btn btn-success btn-sm"
                    title="View Request">
                    <i class="fa fa-eye"></i>
                  </a>
                  <a href="print_ris.php?ris_no=<?php echo (int)($req['id']); ?>"
                    class="btn btn-primary btn-sm" title="Print RIS">
                    <i class="fa-solid fa-print"></i> 
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
            <th>Item/s</th>
            <th>Total Qty</th>
            <th>Date Issued</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ics_grouped as $ics): ?>
            <tr>
              <td style="color:success"><strong><?php echo remove_junk($ics['ics_no']); ?></strong></td>
              <td class="text-center">
                <img src="uploads/users/<?php echo remove_junk($ics['image']); ?>"
                  alt="Profile"
                  class="img-circle"
                  style="width:50px; height:50px; object-fit:cover;">
              </td>
              <td><strong><?php echo remove_junk($ics['employee_name']); ?></strong></td>
              <td><?php echo remove_junk($ics['department']); ?></td>
              <td>
                <div class="items-list">
                  <?php
                  $items_display = [];
                  foreach ($ics['items'] as $item) {
                    $items_display[] = $item['item_name'] . ' (' . $item['quantity'] . ')';
                  }
                  echo remove_junk(implode('<br>', $items_display));
                  ?>
                </div>
              </td>
              <td class="text-center">
                <span class="badge bg-primary"><?php echo $ics['total_quantity']; ?></span>
              </td>
              <td><?php echo date('M d, Y', strtotime($ics['transaction_date'])); ?></td>
              <td>
                <?php 
                $status = $ics['status'];
                if ($status == 'Returned'): ?>
                  <span class="badge bg-success">Returned</span>
                <?php elseif ($status == 'Partially Returned'): ?>
                  <span class="badge bg-warning">Partially Returned</span>
                <?php elseif ($status == 'Issued'): ?>
                  <span class="badge bg-info">Issued</span>
                <?php elseif ($status == 'Re-issued'): ?>
                  <span class="badge bg-primary">Re-issued</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo ucfirst($status); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <a href="view_logs.php?ics_no=<?php echo urlencode($ics['ics_no']); ?>" class="btn btn-success btn-sm" title="View">
                  <i class="fa fa-eye"></i>
                </a>
                <a href="ics_view.php?ics_no=<?php echo urlencode($ics['ics_no']); ?>"
                  class="btn btn-primary btn-sm" title="Print ICS">
                  <i class="fa-solid fa-print"></i>
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
            <th>Item/s</th>
            <th>Total Qty</th>
            <th>Date Issued</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($par_grouped as $par): ?>
            <tr>
              <td><strong><?php echo remove_junk($par['par_no']); ?></strong></td>
              <td class="text-center">
                <img src="uploads/users/<?php echo remove_junk($par['image']); ?>"
                  alt="Profile"
                  class="img-circle"
                  style="width:50px; height:50px; object-fit:cover;">
              </td>
              <td><strong><?php echo remove_junk($par['employee_name']); ?></strong></td>
              <td><?php echo remove_junk($par['department']); ?></td>
              <td>
                <div class="items-list">
                  <?php
                  $items_display = [];
                  foreach ($par['items'] as $par_item) {
                    $items_display[] = $par_item['item_name'] . ' (' . $par_item['quantity'] . ')';
                  }
                  echo remove_junk(implode('<br>', $items_display));
                  ?>
                </div>
              </td>
              <td class="text-center">
                <span class="badge bg-primary"><?php echo $par['total_quantity']; ?></span>
              </td>
              <td><?php echo date('M d, Y', strtotime($par['transaction_date'])); ?></td>
              <td>
                <?php 
                $status = $par['status'];
                if ($status == 'Returned'): ?>
                  <span class="badge bg-success">Returned</span>
                <?php elseif ($status == 'Partially Returned'): ?>
                  <span class="badge bg-warning">Partially Returned</span>
                <?php elseif ($status == 'Issued'): ?>
                  <span class="badge bg-info">Issued</span>
                <?php elseif ($status == 'Re-issued'): ?>
                  <span class="badge bg-primary">Re-issued</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo ucfirst($status); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <a href="view_logs.php?par_no=<?php echo urlencode($par['par_no']); ?>" class="btn btn-success btn-word btn-sm" title="View PAR"> <i class="fa fa-eye"></i></a>
                <a href="par_view.php?par_no=<?php echo urlencode($par['par_no']); ?>"
                  class="btn btn-primary btn-sm" title=" Print PAR">
                  <i class="fa-solid fa-print"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Rest of your JavaScript code remains the same -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.archive-btn').forEach(function(button) {
      button.addEventListener('click', function(e) {
        e.preventDefault();
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
            Swal.fire({
              title: 'Archiving...',
              text: 'Please wait while we archive the request.',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });
            window.location.href = url;
          }
        });
      });
    });

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
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    }

    if (urlParams.get('archive') === 'failed') {
      Swal.fire({
        title: 'Error!',
        text: 'Failed to archive the request. Please try again.',
        icon: 'error',
        confirmButtonColor: '#dc3545'
      });
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

  .items-list {
    max-height: 100px;
    overflow-y: auto;
    font-size: 0.9rem;
  }
  
  .badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
  }
  
  .badge.bg-success {
    background-color: #28a745 !important;
    color: white !important;
  }
  
  .badge.bg-info {
    background-color: #17a2b8 !important;
    color: white !important;
  }
  
  .badge.bg-primary {
    background-color: #007bff !important;
    color: white !important;
  }
  .table th {
        background: #005113ff;
        color: white;
        font-weight: 600;
        border: none;
        padding: 1rem;
        text-align: center;
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
       "deferRender": true,        
      "processing": true,
      "serverSide": false,
      "order": [
        [5, "desc"]
      ],
      "columnDefs": [{
          "width": "14%",
          "targets": 0
        },
        {
          "width": "10%",
          "targets": 1
        },
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
      "autoWidth": false
    });

    $('#icsTable').DataTable({
      "pageLength": 5,
      "lengthMenu": [5, 10, 25, 50, 100],
      "deferRender": true,        
      "processing": true,
      "serverSide": false,
      "order": [
        [6, "desc"]
      ],
    });

    $('#parTable').DataTable({
      "pageLength": 5,
      "lengthMenu": [5, 10, 25, 50, 100],
       "deferRender": true,        
      "processing": true,
      "serverSide": false,
      "order": [
        [6, "desc"]
      ],
    });
  });
</script>