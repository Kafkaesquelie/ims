<?php
$page_title = 'All Requests Logs';
require_once('includes/load.php');
if (!$session->isUserLoggedIn()) {
  header("Location: admin.php");
  exit();
}
page_require_level(1);

// Get selected school year and semester from URL parameters
$selected_sy = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : '';

// Initialize date range variables
$start_date = '';
$end_date = '';

// Fetch all school years and semesters
$school_years = find_by_sql("SELECT DISTINCT school_year FROM school_years ORDER BY school_year DESC");
$semesters = find_by_sql("SELECT DISTINCT semester FROM school_years ORDER BY 
                         CASE 
                           WHEN semester = '1st' THEN 1
                           WHEN semester = '2nd' THEN 2
                           WHEN semester = 'Summer' THEN 3
                         END");

// Build WHERE clause for date filtering based on school year and semester
$date_where = "";
if (!empty($selected_sy) && !empty($selected_semester)) {
    // Get the date range for the selected school year and semester
    $date_range = find_by_sql("SELECT start_date, end_date FROM school_years 
                              WHERE school_year = '{$selected_sy}' 
                              AND semester = '{$selected_semester}' 
                              LIMIT 1");
    
    if (!empty($date_range)) {
        $start_date = $date_range[0]['start_date'];
        $end_date = $date_range[0]['end_date'];
        $date_where = "WHERE r.date_completed BETWEEN '{$start_date}' AND '{$end_date}'";
    }
}

// Fetch all approved/rejected requests with date filter - FIXED: Pass the date_where parameter
$requests = find_all_req_logs($date_where);

// Fetch and group ICS transactions by ICS number with proper status calculation
$ics_transactions = find_all_ics_transactions();
$ics_grouped = [];
foreach ($ics_transactions as $ics) {
    // Apply date filter for ICS transactions
    if (!empty($selected_sy) && !empty($selected_semester) && !empty($start_date) && !empty($end_date)) {
        $transaction_date = $ics['transaction_date'];
        if ($transaction_date < $start_date || $transaction_date > $end_date) {
            continue;
        }
    }
    
    $ics_no = $ics['ics_no'];
    if (!isset($ics_grouped[$ics_no])) {
        $ics_grouped[$ics_no] = [
            'ics_no' => $ics_no,
            'employee_name' => $ics['employee_name'],
            'position' => $ics['position'],
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
$par_transactions = find_all_par_transactions();
$par_grouped = [];
foreach ($par_transactions as $par) {
    // Apply date filter for PAR transactions
    if (!empty($selected_sy) && !empty($selected_semester) && !empty($start_date) && !empty($end_date)) {
        $transaction_date = $par['transaction_date'];
        if ($transaction_date < $start_date || $transaction_date > $end_date) {
            continue;
        }
    }
    
    $par_no = $par['par_no'];
    if (!isset($par_grouped[$par_no])) {
        $par_grouped[$par_no] = [
            'par_no' => $par_no,
            'employee_name' => $par['employee_name'],
            'position' => $par['position'],
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
        'quantity' => $par['quantity'],
        
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

/**
 * FIXED: Modified function to accept date filter parameter
 */
function find_all_req_logs($date_where = "") {
    global $db;
    
    $base_sql = "
        SELECT 
            r.id, 
            r.date, 
            r.status,
            r.ris_no,
            r.date_completed,
            COALESCE(ou.office_name, eo.office_name) AS office_name,
            COALESCE(u.id, e.id) AS requestor_id,
            COALESCE(u.name, CONCAT(e.first_name, ' ', e.last_name)) AS req_name,
            COALESCE(u.image, e.image, 'default.png') AS prof_pic,
            COALESCE(u.position, e.position) AS req_position
        FROM requests r
        LEFT JOIN users u ON r.requested_by = u.id
        LEFT JOIN employees e ON r.requested_by = e.id
        LEFT JOIN offices ou ON u.office = ou.id   -- user's office
        LEFT JOIN offices eo ON e.office = eo.id   -- employee's office
        WHERE r.status IN ('Completed','Archived','Issued','Canceled','Declined')
    ";
    
    // Add date filter if provided
    if (!empty($date_where)) {
        // Remove "WHERE" from the date_where and add appropriate conjunction
        $date_condition = str_replace("WHERE", "AND", $date_where);
        $sql = $base_sql . " " . $date_condition;
    } else {
        $sql = $base_sql;
    }
    
    $sql .= " ORDER BY r.date_completed DESC";
    
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="row mb-3">
    <div class="col-sm-6">
      <h5 class="mb-0"> <i class="nav-icon fas fa-chart-bar"></i> Manage Transactions</h5>
    </div>
  </div>

  <!-- School Year and Semester Filter -->
  <div class="card mb-4">
    <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
      <h3 class="card-title"><i class="nav-icon fas fa-filter"></i> Filter by School Year & Semester</h3>
    </div>
    <div class="card-body">
      <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label for="school_year" class="form-label fw-bold">School Year</label><br>
          <select class="form-select rounded-pill w-100 p-2" id="school_year" name="school_year">
            <option value="">All School Years</option>
            <?php foreach ($school_years as $sy): ?>
              <option value="<?php echo $sy['school_year']; ?>" 
                <?php echo ($selected_sy == $sy['school_year']) ? 'selected' : ''; ?>>
                <?php echo $sy['school_year']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="semester" class="form-label fw-bold">Semester</label><br>
          <select class="form-select rounded-pill w-100 p-2" id="semester" name="semester">
            <option value="">All Semesters</option>
            <?php foreach ($semesters as $sem): ?>
              <option value="<?php echo $sem['semester']; ?>" 
                <?php echo ($selected_semester == $sem['semester']) ? 'selected' : ''; ?>>
                <?php echo $sem['semester']; ?> Semester
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <div class="d-flex gap-2">
            <?php if (!empty($selected_sy) || !empty($selected_semester)): ?>
              <a href="logs.php" class="btn btn-outline-danger rounded-pill">
                <i class="fas fa-times"></i> Clear Filters
              </a>
            <?php endif; ?>
            <button type="submit" class="btn btn-success rounded-pill">
              <i class="fas fa-filter"></i> Apply Filters
            </button>
          </div>
        </div>
      </form>
      
      <!-- Active Filter Display -->
      <?php if (!empty($selected_sy) || !empty($selected_semester)): ?>
        <div class="mt-3 p-3 bg-light rounded">
          <h6 class="mb-2"><i class="fas fa-info-circle"></i> Active Filters:</h6>
          <?php if (!empty($selected_sy)): ?>
            <span class="badge bg-primary me-2 rounded-pill">School Year: <?php echo $selected_sy; ?></span>
          <?php endif; ?>
          <?php if (!empty($selected_semester)): ?>
            <span class="badge bg-info rounded-pill">Semester: <?php echo $selected_semester; ?></span>
          <?php endif; ?>
          <?php if (!empty($start_date) && !empty($end_date)): ?>
            <span class="badge bg-secondary ms-2 rounded-pill">
              Date Range: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
            </span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Logs Table -->
  <div class="card">
    <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
      <h3 class="card-title"> 
        <i class="nav-icon fas fa-box-open"></i> Stock Requests
        <?php if (!empty($selected_sy)): ?>
          <small class="text-muted">(Filtered: <?php echo $selected_sy; ?><?php echo !empty($selected_semester) ? ' - ' . $selected_semester . ' Semester' : ''; ?>)</small>
        <?php endif; ?>
      </h3>
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
            <?php if (count($requests) > 0): ?>
              <?php foreach ($requests as $req): ?>
                <tr>
                  <td class="text-success"> <strong>
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
                    <?php elseif ($req['status'] == 'Canceled'): ?>
                      <span class="badge bg-primary"><?php echo ucfirst($req['status']); ?></span>
                    <?php else: ?>
                      <span class="badge bg-danger"><?php echo ucfirst($req['status']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <a href="ris_view.php?id=<?php echo (int)$req['id']; ?>"
                      class="btn btn-success btn-sm rounded-pill"
                      title="View Request">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="print_ris.php?ris_no=<?php echo (int)($req['id']); ?>"
                      class="btn btn-primary btn-sm rounded-pill" title="Print RIS">
                      <i class="fa-solid fa-print"></i> 
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
          
            <?php endif; ?> 
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 🟦 ICS Transactions Table -->
  <div class="card">
    <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
      <h3 class="card-title">
        <i class="nav-icon fas fa-file-invoice"></i> ICS Transactions
        <?php if (!empty($selected_sy)): ?>
          <small class="text-muted">(Filtered: <?php echo $selected_sy; ?><?php echo !empty($selected_semester) ? ' - ' . $selected_semester . ' Semester' : ''; ?>)</small>
        <?php endif; ?>
      </h3>
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
            <?php if (count($ics_grouped) > 0): ?>
              <?php foreach ($ics_grouped as $ics): ?>
                <tr>
                  <td class="text-success"><strong><?php echo remove_junk($ics['ics_no']); ?></strong></td>
                  <td class="text-center">
                    <img src="uploads/users/<?php echo remove_junk($ics['image']); ?>"
                      alt="Profile"
                      class="img-circle"
                      style="width:50px; height:50px; object-fit:cover;">
                  </td>
                  <td><strong><?php echo remove_junk($ics['employee_name']); ?></strong><br>
                 <small><?php echo remove_junk($ics['position']); ?></small></td>
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
                    <span class="badge bg-primary rounded-pill"><?php echo $ics['total_quantity']; ?></span>
                  </td>
                  <td><?php echo date('M d, Y', strtotime($ics['transaction_date'])); ?></td>
                  <td>
                    <?php 
                    $status = $ics['status'];
                    if ($status == 'Returned'): ?>
                      <span class="badge bg-success rounded-pill">Returned</span>
                    <?php elseif ($status == 'Partially Returned'): ?>
                      <span class="badge bg-warning rounded-pill">Partially Returned</span>
                    <?php elseif ($status == 'Issued'): ?>
                      <span class="badge bg-info rounded-pill">Issued</span>
                    <?php elseif ($status == 'Re-issued'): ?>
                      <span class="badge bg-primary rounded-pill">Re-issued</span>
                    <?php else: ?>
                      <span class="badge bg-secondary rounded-pill"><?php echo ucfirst($status); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <a href="view_logs.php?ics_no=<?php echo urlencode($ics['ics_no']); ?>" class="btn btn-success btn-sm rounded-pill" title="View">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="ics_view.php?ics_no=<?php echo urlencode($ics['ics_no']); ?>"
                      class="btn btn-primary btn-sm rounded-pill" title="Print ICS">
                      <i class="fa-solid fa-print"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 🟩 PAR Transactions Table -->
  <div class="card mb-4">
    <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
      <h3 class="card-title">
        <i class="nav-icon fas fa-file-contract"></i> PAR Transactions
        <?php if (!empty($selected_sy)): ?>
          <small class="text-muted">(Filtered: <?php echo $selected_sy; ?><?php echo !empty($selected_semester) ? ' - ' . $selected_semester . ' Semester' : ''; ?>)</small>
        <?php endif; ?>
      </h3>
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
            <?php if (count($par_grouped) > 0): ?>
              <?php foreach ($par_grouped as $par): ?>
                <tr>
                  <td class="text-success"><strong><?php echo remove_junk($par['par_no']); ?></strong></td>
                  <td class="text-center">
                    <img src="uploads/users/<?php echo remove_junk($par['image']); ?>"
                      alt="Profile"
                      class="img-circle"
                      style="width:50px; height:50px; object-fit:cover;">
                  </td>
                  <td><strong><?php echo remove_junk($par['employee_name']); ?></strong><br>
                 <small><?php echo remove_junk($par['position']); ?></small></td>
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
                    <span class="badge bg-primary rounded-pill"><?php echo $par['total_quantity']; ?></span>
                  </td>
                  <td><?php echo date('M d, Y', strtotime($par['transaction_date'])); ?></td>
                  <td>
                    <?php 
                    $status = $par['status'];
                    if ($status == 'Returned'): ?>
                      <span class="badge bg-success rounded-pill">Returned</span>
                    <?php elseif ($status == 'Partially Returned'): ?>
                      <span class="badge bg-warning rounded-pill">Partially Returned</span>
                    <?php elseif ($status == 'Issued'): ?>
                      <span class="badge bg-info rounded-pill">Issued</span>
                    <?php elseif ($status == 'Re-issued'): ?>
                      <span class="badge bg-primary rounded-pill">Re-issued</span>
                    <?php else: ?>
                      <span class="badge bg-secondary rounded-pill"><?php echo ucfirst($status); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <a href="view_logs.php?par_no=<?php echo urlencode($par['par_no']); ?>" class="btn btn-success btn-word btn-sm rounded-pill" title="View PAR"> <i class="fa fa-eye"></i></a>
                    <a href="par_view.php?par_no=<?php echo urlencode($par['par_no']); ?>"
                      class="btn btn-primary btn-sm rounded-pill" title=" Print PAR">
                      <i class="fa-solid fa-print"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

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
  
  .filter-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-left: 5px solid #28a745;
  }
  
  /* Rounded borders for form elements and buttons */
  .form-select.rounded-pill,
  .btn.rounded-pill,
  .badge.rounded-pill {
    border-radius: 50rem !important;
  }
</style>

<?php include_once('layouts/footer.php'); ?>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
  $(document).ready(function() {
    // Initialize DataTables
    initializeDataTables();
    
    function initializeDataTables() {
      // Destroy existing instances if they exist
      if ($.fn.DataTable.isDataTable('#datatable')) {
        $('#datatable').DataTable().destroy();
      }
      if ($.fn.DataTable.isDataTable('#icsTable')) {
        $('#icsTable').DataTable().destroy();
      }
      if ($.fn.DataTable.isDataTable('#parTable')) {
        $('#parTable').DataTable().destroy();
      }
      
      // Initialize main datatable
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
        "autoWidth": false,
        "language": {
          "emptyTable": "No transactions found for the selected filters"
        }
      });

      // Initialize ICS table
      $('#icsTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [5, 10, 25, 50, 100],
        "deferRender": true,        
        "processing": true,
        "serverSide": false,
        "order": [
          [6, "desc"]
        ],
        "language": {
          "emptyTable": "No ICS transactions found for the selected filters"
        }
      });

      // Initialize PAR table
      $('#parTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [5, 10, 25, 50, 100],
        "deferRender": true,        
        "processing": true,
        "serverSide": false,
        "order": [
          [6, "desc"]
        ],
        "language": {
          "emptyTable": "No PAR transactions found for the selected filters"
        }
      });
    }
    
    // Reinitialize DataTables when form is submitted (page reload)
    $('form').on('submit', function() {
      setTimeout(function() {
        initializeDataTables();
      }, 100);
    });
  });
</script>