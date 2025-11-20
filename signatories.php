<?php
$page_title = 'Signatories';
require_once('includes/load.php');
page_require_level(1);

// ✅ Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name     = remove_junk($db->escape($_POST['name']));
    $position = remove_junk($db->escape($_POST['position']));
    $agency   = remove_junk($db->escape($_POST['agency']));

    $query  = "INSERT INTO signatories (name, position, agency) 
               VALUES ('{$name}','{$position}','{$agency}')";
    if ($db->query($query)) {
        $session->msg("s","New signatory added successfully.");
    } else {
        $session->msg("d","Failed to add signatory.");
    }
    redirect('signatories.php');
}

// ✅ Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id       = (int)$_POST['id'];
    $name     = remove_junk($db->escape($_POST['name']));
    $position = remove_junk($db->escape($_POST['position']));
    $agency   = remove_junk($db->escape($_POST['agency']));

    $query = "UPDATE signatories 
              SET name='{$name}', position='{$position}', agency='{$agency}'
              WHERE id={$id}";
    if ($db->query($query)) {
        $session->msg("s","Signatory updated successfully.");
    } else {
        $session->msg("d","Update failed.");
    }
    redirect('signatories.php');
}

// ✅ Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $query = "DELETE FROM signatories WHERE id={$id} LIMIT 1";
    if ($db->query($query)) {
        $session->msg("s","Signatory deleted.");
    } else {
        $session->msg("d","Failed to delete signatory.");
    }
    redirect('signatories.php');
}

// ✅ Fetch signatories
$signatories = find_by_sql("SELECT * FROM signatories ORDER BY id DESC");

include_once('layouts/header.php');?>

<?php include_once('layouts/header.php'); ?>
<?php if ($msg = $session->msg()): 
    $type = key($msg);
    $text = $msg[$type];
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
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

.page-header {
  background: white;
  border-top: 5px solid var(--primary);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.page-title {
  font-family: 'Times New Roman', serif;
  font-weight: 700;
  margin: 0;
  color: var(--dark);
}

.signatory-card {
  border: none;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.signatory-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.signatory-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 1rem 1.5rem;
  position: relative;
}

.signatory-header::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: rgba(255,255,255,0.3);
}

.signatory-name {
  font-size: 1.3rem;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.signatory-name i {
  font-size: 1.1rem;
}

.signatory-body {
  padding: 1.5rem;
}

.signatory-detail {
  display: flex;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.signatory-detail:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.detail-icon {
  color: var(--primary);
  font-size: 1.1rem;
  margin-right: 0.75rem;
  margin-top: 0.2rem;
  min-width: 20px;
}

.detail-content {
  flex: 1;
}

.detail-label {
  font-weight: 600;
  color: var(--dark);
  font-size: 0.9rem;
  margin-bottom: 0.2rem;
}

.detail-value {
  color: var(--secondary);
  font-size: 0.95rem;
}

.signatory-actions {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  display: flex;
  gap: 0.5rem;
}

.btn-edit {
  background: var(--warning);
  color: var(--dark);
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-edit:hover {
  background: #e0a800;
  transform: translateY(-1px);
  color: var(--dark);
}

.btn-delete {
  background: var(--danger);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.3s ease;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-delete:hover {
  background: #c82333;
  transform: translateY(-1px);
  color: white;
}

.btn-add {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  border-radius: 50px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  color: white;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--secondary);
}

.empty-state i {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state h4 {
  color: var(--secondary);
  margin-bottom: 0.5rem;
}

.modal-header-custom {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border-bottom: none;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-title-custom {
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-control:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn-submit {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-submit:hover {
  background: linear-gradient(135deg, var(--primary-dark), #155724);
  color: white;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .page-title {
    font-size: 1.5rem;
  }
  
  .signatory-card {
    margin-bottom: 1rem;
  }
  
  .signatory-actions {
    flex-direction: column;
  }
  
  .btn-edit, .btn-delete {
    width: 100%;
  }
}
</style>

<div class="container-fluid">
  <!-- Page Header -->
  <div class="page-header">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
      <h4 class="page-title">Signatories Management</h4>
      <button class="btn btn-add mt-3 mt-md-0" data-toggle="modal" data-target="#addSignatoryModal">
        <i class="fas fa-plus"></i> Add New Signatory
      </button>
    </div>
  </div>

  <div class="container">
    <!-- Signatories Grid -->
    <div class="row">
      <?php if(count($signatories) > 0): ?>
        <?php foreach($signatories as $sig): ?>
          <div class="col-lg-4 col-md-6">
            <div class="signatory-card">
              <div class="signatory-header">
                <h3 class="signatory-name">
                  <i class="fas fa-user-circle"></i>
                  <?= remove_junk($sig['name']); ?>
                </h3>
              </div>
              
              <div class="signatory-body">
                <div class="signatory-detail">
                  <div class="detail-icon">
                    <i class="fas fa-briefcase"></i>
                  </div>
                  <div class="detail-content">
                    <div class="detail-label">Position</div>
                    <div class="detail-value"><?= remove_junk($sig['position']); ?></div>
                  </div>
                </div>
                
                <div class="signatory-detail">
                  <div class="detail-icon">
                    <i class="fas fa-building"></i>
                  </div>
                  <div class="detail-content">
                    <div class="detail-label">Agency/Department</div>
                    <div class="detail-value"><?= remove_junk($sig['agency']); ?></div>
                  </div>
                </div>
                
                <?php if(!empty($sig['contact_info'])): ?>
                <div class="signatory-detail">
                  <div class="detail-icon">
                    <i class="fas fa-phone"></i>
                  </div>
                  <div class="detail-content">
                    <div class="detail-label">Contact Information</div>
                    <div class="detail-value"><?= remove_junk($sig['contact_info']); ?></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
              
              <div class="signatory-actions">
                <button class="btn btn-edit" 
                        data-toggle="modal" 
                        data-target="#editSignatoryModal<?= $sig['id']; ?>">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-delete archive-btn" 
                        data-id="<?= $sig['id']; ?>"
                        data-name="<?= htmlspecialchars(remove_junk($sig['name'])); ?>">
                  <i class="fas fa-archive"></i> Archive
                </button>
              </div>
            </div>
          </div>

          <!-- Edit Modal -->
          <div class="modal fade" id="editSignatoryModal<?= $sig['id']; ?>" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                  <div class="modal-header modal-header-custom">
                    <h5 class="modal-title modal-title-custom">
                      <i class="fas fa-edit"></i> Edit Signatory
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body p-4">
                    <input type="hidden" name="id" value="<?= $sig['id']; ?>">
                    <div class="form-group">
                      <label class="font-weight-bold">Full Name</label>
                      <input type="text" name="name" class="form-control" 
                             value="<?= remove_junk($sig['name']); ?>" required>
                    </div>
                    <div class="form-group">
                      <label class="font-weight-bold">Position</label>
                      <input type="text" name="position" class="form-control" 
                             value="<?= remove_junk($sig['position']); ?>" required>
                    </div>
                    <div class="form-group">
                      <label class="font-weight-bold">Agency/Department</label>
                      <input type="text" name="agency" class="form-control" 
                             value="<?= remove_junk($sig['agency']); ?>" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-submit">Update Signatory</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="empty-state">
            <i class="fas fa-users"></i>
            <h4>No Signatories Found</h4>
            <p>Get started by adding your first signatory to the system.</p>
            <button class="btn btn-add mt-3" data-toggle="modal" data-target="#addSignatoryModal">
              <i class="fas fa-plus"></i> Add First Signatory
            </button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Signatory Modal -->
<div class="modal fade" id="addSignatoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="POST" action="">
         <input type="hidden" name="action" value="add">
        <div class="modal-header modal-header-custom">
          <h5 class="modal-title modal-title-custom">
            <i class="fas fa-user-plus"></i> Add New Signatory
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group">
            <label class="font-weight-bold">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Position</label>
            <input type="text" name="position" class="form-control" placeholder="Enter position title" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">Agency/Department</label>
            <input type="text" name="agency" class="form-control" placeholder="Enter agency or department" required>
          </div>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-submit">Add Signatory</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// SweetAlert for archive confirmation
document.addEventListener('DOMContentLoaded', function() {
  // Archive button functionality
  document.querySelectorAll('.archive-btn').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      
      const id = this.getAttribute('data-id');
      const name = this.getAttribute('data-name');
      
      Swal.fire({
        title: 'Archive Signatory?',
        html: `You are about to archive <strong>"${name}"</strong>.<br>This action can be undone later if needed.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive it!',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: () => {
          return new Promise((resolve) => {
            // Simulate API call or redirect
            setTimeout(() => {
              window.location.href = `a_script.php?id=${id}`;
              resolve();
            }, 1000);
          });
        },
        allowOutsideClick: () => !Swal.isLoading()
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Archived!',
            text: 'The signatory has been archived.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          });
        }
      });
    });
  });

  // Animation for cards
  const cards = document.querySelectorAll('.signatory-card');
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
    card.classList.add('animate__animated', 'animate__fadeInUp');
  });
});

</script>

<?php include_once('layouts/footer.php'); ?>