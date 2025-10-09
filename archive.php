<?php
$page_title = 'All Archives';
require_once('includes/load.php');
// Check permission
page_require_level(1);

purge();

// Fetch archive records
$archives = find_all('archive');

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
  --warning: #ffc107;
  --danger: #dc3545;
  --light: #f8f9fa;
  --dark: #343a40;
  --border-radius: 12px;
  --shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card-header-custom {
  background: white;
  border-top: 5px solid var(--primary);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.page-title {
  font-family: 'Times New Roman', serif;
  font-weight: 700;
  margin: 0;
  color: var(--dark);
}

.btn-restore {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-restore:hover {
  background: linear-gradient(135deg, var(--primary-dark), #155724);
  color: white;
  transform: translateY(-1px);
}

.table-container {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.table-custom {
  width: 100%;
  margin-bottom: 0;
  border-collapse: collapse;
}

/* .table-custom thead {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
} */

.table-custom th {
  border: none;
  font-weight: 600;
  padding: 1rem;
  text-align: center;
}

.table-custom td {
  padding: 1rem;
  vertical-align: middle;
  text-align: center;
}

.archive-badge {
  background: var(--secondary);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.archive-badge.items { background: #007bff; }
.archive-badge.users { background: #6f42c1; }
.archive-badge.categories { background: #e83e8c; }
.archive-badge.logs { background: #fd7e14; }
.archive-badge.Refernce { background: #009b24ff; }


.empty-state i {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state h4 {
  color: var(--secondary);
  margin-bottom: 0.5rem;
}

.breadcrumb-custom {
  background: transparent;
  padding: 0;
  margin: 0;
}

.breadcrumb-custom .breadcrumb-item a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
}

.breadcrumb-custom .breadcrumb-item.active {
  color: var(--secondary);
}

@media (max-width: 768px) {
  .card-header-custom {
    padding: 1rem;
  }
  
  .table-custom th,
  .table-custom td {
    padding: 0.75rem 0.5rem;
    font-size: 0.9rem;
  }
  
  .btn-restore {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
}

/* Ensure table width matches header */
.table-container .dataTables_wrapper {
  width: 100%;
}

.table-container .dataTables_scroll {
  width: 100%;
}
</style>

<div class="container-fluid">
  <!-- Page Header -->
  <!-- <div class="card-header-custom">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <h4 class="page-title">Archives Management</h4>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom">
          <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
          <li class="breadcrumb-item active" aria-current="page">Archives</li>
        </ol>
      </nav>
    </div>
  </div> -->

    <!-- Archives Table -->
    <div class="table-container">
      <div class="card-header bg-light" style="border-top: 5px solid var(--primary); border-radius: var(--border-radius) var(--border-radius) 0 0;">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="fas fa-archive me-2 text-success"></i>
            Archived Records
          </h5>
          <span class="badge bg-secondary">
            <?php echo count($archives); ?> Records
          </span>
        </div>
      </div>
      
      <div class="p-1">
        <table id="archiveTable" class="table table-custom">
          <thead>
            <tr>
              <th width="8%">ID</th>
              <th width="15%">Classification</th>
              <th>Description</th>
              <th width="20%">Archived At</th>                  
              <th width="12%">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($archives) > 0): ?>
              <?php foreach ($archives as $archive): ?>
                <?php
                $data = json_decode($archive['data'], true);
                $field1 = 'N/A';

                // Choose what to show depending on classification
                if ($archive['classification'] === 'items') {
                    $field1 = $data['name'] ?? 'N/A';
                } elseif ($archive['classification'] === 'users') {
                    $field1 = $data['username'] ?? 'N/A';
                } elseif ($archive['classification'] === 'categories') {
                    $field1 = $data['name'] ?? 'N/A';
                } elseif ($archive['classification'] === 'logs') {
                    $field1 = $data['name'] ?? 'N/A';
                }elseif ($archive['classification'] === 'offices') {
                    $field1 = $data['office_name'] ?? 'N/A';
                } elseif ($archive['classification'] === 'divisions') {
                    $field1 = $data['division_name'] ?? 'N/A';
                } elseif ($archive['classification'] === 'fund_clusters') {
                    $field1 = $data['fund_cluster'] ?? 'N/A';
                }
                ?>
                <tr>
                  <td class="fw-bold text-primary"># <?= $archive['id']; ?></td>
                  <td>
                    <span class="archive-badge <?= $archive['classification']; ?>">
                      <?= ucfirst($archive['classification']); ?>
                    </span>
                  </td>
                  <td class="text-start">
                   <div class="d-flex align-items-center">
                      <?php if ($archive['classification'] === 'users'): ?>
                        <i class="fas fa-user me-2 text-muted"></i> 
                      <?php elseif ($archive['classification'] === 'items'): ?>
                        <i class="fas fa-box me-2 text-muted"></i>
                      <?php elseif ($archive['classification'] === 'categories'): ?>
                        <i class="fas fa-folder me-2 text-muted"></i>
                      <?php elseif ($archive['classification'] === 'logs'): ?>
                        <i class="fas fa-history me-2 text-muted"></i>
                      <?php elseif ($archive['classification'] === 'offices'): ?>
                        <i class="fas fa-bookmark me-2 text-muted"></i> 
                      <?php elseif ($archive['classification'] === 'divisions'): ?>
                        <i class="fas fa-bookmark me-2 text-muted"></i> 
                      <?php elseif ($archive['classification'] === 'fund_clusters'): ?>
                        <i class="fas fa-bookmark me-2 text-muted"></i> 
                      <?php endif; ?>
                      <span><?= $field1; ?></span>
                    </div>
                  </td>
                  <td>
                    <div class="text-muted">
                      <i class="fas fa-calendar me-1"></i>
                       <?= date('M j, Y', strtotime($archive['archived_at'])); ?>
                    </div>
                    <div class="text-muted smaller">
                      <i class="fas fa-clock me-1"></i>
                       <?= date('g:i A', strtotime($archive['archived_at'])); ?>
                    </div>
                  </td>
                  <td>
                    <a href="r_script.php?id=<?= $archive['id']; ?>" class="btn btn-restore restore-btn" title="Restore Record">
                      <i class="fas fa-undo"></i>
                      Restore
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
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
  // Restore confirmation
  document.querySelectorAll('.restore-btn').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault(); // stop normal link action
      const url = this.getAttribute('href');

      Swal.fire({
        title: 'Restore Record?',
        text: "This record will be restored to its original location.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, restore it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
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

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
  // DataTable initialization
  $('#archiveTable').DataTable({
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50, 100],
    "order": [[3, "asc"]], // Sort by Archived At date
    "searching": true,
    "autoWidth": false,
    "responsive": true,
    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    "language": {
      "emptyTable": '<div class="text-center text-muted py-4">' +
                    '<i class="fas fa-archive fa-3x mb-3"></i><br>' +
                    'No archived records found.' +
                    '</div>',
      "search": "_INPUT_",
      "searchPlaceholder": "Search archives...",
      "lengthMenu": "Show _MENU_ entries",
      "info": "Showing _START_ to _END_ of _TOTAL_ entries",
      "infoEmpty": "Showing 0 to 0 of 0 entries",
      "infoFiltered": "(filtered from _MAX_ total entries)",
      "paginate": {
        "first": "First",
        "last": "Last",
        "next": "Next",
        "previous": "Previous"
      }
    },
    "columnDefs": [
      { "orderable": false, "targets": [4] } // Actions column
    ],
    "drawCallback": function(settings) {
      // Update badge count after filtering
      const api = this.api();
      const filteredCount = api.rows({ search: 'applied' }).count();
      $('.badge.bg-secondary').text(filteredCount + ' Records');
    },
    "initComplete": function(settings, json) {
      // Ensure table width matches container
      $('.dataTables_scroll').css('width', '100%');
      $('#archiveTable').css('width', '100%');
    }
  });

  // Update the badge initially
  const table = $('#archiveTable').DataTable();
  $('.badge.bg-secondary').text(table.rows({ search: 'applied' }).count() + ' Records');
</script>