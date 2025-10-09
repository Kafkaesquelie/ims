<?php
$page_title = 'All Requests';
require_once('includes/load.php');

// Check user permission
page_require_level(1);


// Mark request as completed
if (isset($_GET['issued'])) {
    $request_id = (int)$_GET['issued'];
    $sql = "UPDATE requests 
            SET status = 'Issued', date_issued = NOW() 
            WHERE id = '{$request_id}'";

    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Request marked as issued.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
    exit;
}


// Fetch all requests
$requests = find_all_req();

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

<style>
:root {
    --primary: #28a745;
    --primary-dark: #1e7e34;
    --primary-light: #34ce57;
    --secondary: #6c757d;
    --light: #f8f9fa;
    --dark: #343a40;
    --border-radius: 12px;
}

.card-container {
    max-width: 1400px;
    margin: 0 auto;
}

.card-custom {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 2rem;
}

.card-header-custom {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    padding: 1.25rem 1.5rem;
    border-bottom: none;
}

.card-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-title i {
    font-size: 1.1rem;
}

.table-custom {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin: 0;
}

.table-custom thead th {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 2px solid #dee2e6;
    font-weight: 700;
    color: var(--dark);
    padding: 1rem 0.75rem;
    border: none;
}

.table-custom tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-color: #f1f3f4;
}

.table-custom tbody tr {
    transition: all 0.2s ease;
}

.table-custom tbody tr:hover {
    background-color: #f8fdf9;
    transform: scale(1.002);
}

.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    text-decoration: none;
}



.btn-view:hover {
    background:blue;
    color: white;
    transform: translateY(-1px);
    text-decoration: none;
}


.profile-img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.8rem;
}

.badge-pending {
    background: rgba(255, 193, 7, 0.15);
    color: #856404;
}

.badge-approved {
    background: rgba(40, 167, 69, 0.15);
    color: var(--primary-dark);
}

.badge-rejected {
    background: rgba(220, 53, 69, 0.15);
    color: #721c24;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--secondary);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #dee2e6;
}

.empty-state h5 {
    margin-bottom: 0.5rem;
    color: var(--secondary);
}

.empty-state p {
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn-action {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .profile-img {
        width: 40px;
        height: 40px;
    }
}
</style>

<div class="card-container mt-4">
    <!-- Breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
             <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-custom">
            <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Inventory Items</li>
          </ol>
        </nav>
        </div>
    </div>

    <!-- Stock Requests Card -->
    <div class="card-custom" >
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-clipboard-list"></i> Stock Requests
            </h5>
            <div class="text-muted">
                <small>Total: <?php echo count($requests); ?> requests</small>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table id="reqTable" class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>RIS NO</th>
                                <th>Profile</th>
                                <th>Requested By</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Remarks</th>               
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo remove_junk($req['ris_no']); ?></td>
                                    <td>
                                        <img src="uploads/users/<?php echo !empty($req['image']) ? $req['image'] : 'no_image.png'; ?>" 
                                             alt="Profile" class="profile-img">
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong><?php echo remove_junk($req['req_by']); ?></strong>
                                            <small class="text-muted"><?php echo remove_junk($req['position']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo remove_junk($req['dep_name']); ?></td>
                                    
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date("M d, Y", strtotime($req['date'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($req['remarks']); ?>
                                        </small>
                                    </td>
                                 <td>
                                        <div class="btn-group" role="group">
                                            <?php if (strtolower($req['status']) == 'approved'): ?>
                                                <!-- Approved → Show Issue button -->
                                                <a href="?issued=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-warning issue-btn" 
                                                    title="Mark as Issued">
                                                    <i class="fa-solid fa-box-open"></i> Issue
                                                </a>

                                            <?php elseif (strtolower($req['status']) == 'issued'): ?>
                                                <!-- For Confirmation → Show loader text -->
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                                    <i class="fa-solid fa-spinner fa-spin"></i> For Confirmation
                                                </span>

                                            <?php else: ?>
                                                <!-- Default View + Archive -->
                                                <a href="r_view.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-view btn-primary" 
                                                    title="View Request">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="a_script.php?id=<?php echo (int)$req['id']; ?>" 
                                                    class="btn-action btn-danger btn-archive archive-btn" 
                                                    title="Archive">
                                                    <i class="fa-solid fa-file-zipper"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>



                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>No Requests Found</h5>
                    <p>There are currently no stock requests in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.issue-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const btn = this;

            Swal.fire({
                title: 'Issue Items?',
                text: "This will mark the request as 'For Confirmation' and notify the requester.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loader effect
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                    btn.disabled = true;

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Issued!',
                                    text: data.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa-solid fa-box-open"></i> Issue';
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'An unexpected error occurred.', 'error');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa-solid fa-box-open"></i> Issue';
                        });
                }
            });
        });
    });
});

</script>

<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Archive confirmation
    document.querySelectorAll('.archive-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const requestId = this.getAttribute('data-id');

            Swal.fire({
                title: 'Archive Request?',
                text: "This request will be moved to archives.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

// Complete confirmation
    document.querySelectorAll('.complete-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Mark as Completed?',
                text: "This request will be finalized and cannot be undone.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, complete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    
    // Initialize DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#reqTable').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            searching: false,
            autoWidth: false,
            responsive: true,
            
        });

   
    }
});
</script>
