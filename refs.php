<?php
$page_title = 'References';
require_once('includes/load.php');
page_require_level(1);

// Handle Add Fund Cluster
if (isset($_POST['add_cluster'])) {
    $name = remove_junk($db->escape($_POST['cluster_name']));
    $description = remove_junk($db->escape($_POST['description']));
    
    // Check for duplicate
    $existing = find_by_sql("SELECT id FROM fund_clusters WHERE name = '{$name}'");
    if (count($existing) > 0) {
        $session->msg("d", "Fund Cluster '{$name}' already exists.");
        redirect('refs.php');
    }
    
    $db->query("INSERT INTO fund_clusters (name, description) VALUES ('{$name}', '{$description}')");
    $session->msg("s", "Fund Cluster added successfully.");
    redirect('refs.php');
}

// Handle Edit Fund Cluster
if (isset($_POST['edit_cluster'])) {
    $id = (int)$_POST['id'];
    $name = remove_junk($db->escape($_POST['cluster_name']));
    $description = remove_junk($db->escape($_POST['description']));
    
    // Check for duplicate (excluding current record)
    $existing = find_by_sql("SELECT id FROM fund_clusters WHERE name = '{$name}' AND id != '{$id}'");
    if (count($existing) > 0) {
        $session->msg("d", "Fund Cluster '{$name}' already exists.");
        redirect('refs.php');
    }
    
    $db->query("UPDATE fund_clusters SET name='{$name}', description='{$description}', updated_at=NOW() WHERE id='{$id}'");
    $session->msg("s", "Fund Cluster updated successfully.");
    redirect('refs.php');
}

// Handle Add Division
if (isset($_POST['add_division'])) {
    $name = remove_junk($db->escape($_POST['division_name']));
    
    // Check for duplicate
    $existing = find_by_sql("SELECT id FROM divisions WHERE division_name = '{$name}'");
    if (count($existing) > 0) {
        $session->msg("d", "Division '{$name}' already exists.");
        redirect('refs.php');
    }
    
    $db->query("INSERT INTO divisions (division_name) VALUES ('{$name}')");
    $session->msg("s", "Division added successfully.");
    redirect('refs.php');
}

// Handle Edit Division
if (isset($_POST['edit_division'])) {
    $id = (int)$_POST['id'];
    $name = remove_junk($db->escape($_POST['division_name']));
    
    // Check for duplicate (excluding current record)
    $existing = find_by_sql("SELECT id FROM divisions WHERE division_name = '{$name}' AND id != '{$id}'");
    if (count($existing) > 0) {
        $session->msg("d", "Division '{$name}' already exists.");
        redirect('refs.php');
    }
    
    $db->query("UPDATE divisions SET division_name='{$name}', updated_at=NOW() WHERE id='{$id}'");
    $session->msg("s", "Division updated successfully.");
    redirect('refs.php');
}

// Handle Add Office under Division
if (isset($_POST['add_office'])) {
    $division_id = (int)$_POST['division_id'];
    $name = remove_junk($db->escape($_POST['office_name']));
    
    // Check for duplicate office name in the same division
    $existing = find_by_sql("SELECT id FROM offices WHERE office_name = '{$name}' AND division_id = '{$division_id}'");
    if (count($existing) > 0) {
        $session->msg("d", "Office '{$name}' already exists in this division.");
        redirect('refs.php');
    }
    
    $db->query("INSERT INTO offices (division_id, office_name) VALUES ('{$division_id}', '{$name}')");
    $session->msg("s", "Office added successfully.");
    redirect('refs.php');
}

// Handle Edit Office
if (isset($_POST['edit_office'])) {
    $id = (int)$_POST['id'];
    $division_id = (int)$_POST['division_id'];
    $name = remove_junk($db->escape($_POST['office_name']));
    
    // Check for duplicate (excluding current record)
    $existing = find_by_sql("SELECT id FROM offices WHERE office_name = '{$name}' AND division_id = '{$division_id}' AND id != '{$id}'");
    if (count($existing) > 0) {
        $session->msg("d", "Office '{$name}' already exists in this division.");
        redirect('refs.php');
    }
    
    $db->query("UPDATE offices SET division_id='{$division_id}', office_name='{$name}', updated_at=NOW() WHERE id='{$id}'");
    $session->msg("s", "Office updated successfully.");
    redirect('refs.php');
}


// Handle Multiple Division Additions
if (isset($_POST['division_names']) && is_array($_POST['division_names'])) {
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($_POST['division_names'] as $division_name) {
        $name = remove_junk($db->escape(trim($division_name)));
        
        if (!empty($name)) {
            // Check for duplicate
            $existing = find_by_sql("SELECT id FROM divisions WHERE division_name = '{$name}'");
            if (count($existing) === 0) {
                $db->query("INSERT INTO divisions (division_name) VALUES ('{$name}')");
                $successCount++;
            } else {
                $errorCount++;
            }
        }
    }
    
    if ($successCount > 0) {
        $session->msg("s", "{$successCount} division(s) added successfully.");
    }
    if ($errorCount > 0) {
        $session->msg("d", "{$errorCount} division(s) were duplicates and not added.");
    }
    
    if ($successCount > 0 || $errorCount > 0) {
        redirect('refs.php');
    }
}

// Handle Multiple Office Additions
if (isset($_POST['division_ids']) && isset($_POST['office_names']) && 
    is_array($_POST['division_ids']) && is_array($_POST['office_names'])) {
    
    $successCount = 0;
    $errorCount = 0;
    
    for ($i = 0; $i < count($_POST['division_ids']); $i++) {
        $division_id = (int)$_POST['division_ids'][$i];
        $office_name = remove_junk($db->escape(trim($_POST['office_names'][$i])));
        
        if (!empty($division_id) && !empty($office_name)) {
            // Check for duplicate office name in the same division
            $existing = find_by_sql("SELECT id FROM offices WHERE office_name = '{$office_name}' AND division_id = '{$division_id}'");
            if (count($existing) === 0) {
                $db->query("INSERT INTO offices (division_id, office_name) VALUES ('{$division_id}', '{$office_name}')");
                $successCount++;
            } else {
                $errorCount++;
            }
        }
    }
    
    if ($successCount > 0) {
        $session->msg("s", "{$successCount} office(s) added successfully.");
    }
    if ($errorCount > 0) {
        $session->msg("d", "{$errorCount} office(s) were duplicates and not added.");
    }
    
    if ($successCount > 0 || $errorCount > 0) {
        redirect('refs.php');
    }
}

// Fetch Data
$clusters = find_all('fund_clusters');
$divisions = find_all('divisions');
$offices = find_by_sql("
    SELECT o.*, d.id AS division_id, d.division_name 
    FROM offices o 
    JOIN divisions d ON o.division_id = d.id 
    ORDER BY d.division_name, o.office_name
");

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
    --info: #17a2b8;
    --warning: #ffc107;
    --danger: #dc3545;
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

.card-header-custom.info {
    background: linear-gradient(135deg, var(--info), #138496);
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

.btn-custom-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.6rem 1.25rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.btn-custom-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(40, 167, 69, 0.4);
    color: white;
}

.btn-custom-secondary {
    background: var(--secondary);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 0.6rem 1.25rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.btn-custom-secondary:hover {
    background: #5a6268;
    color: white;
    transform: translateY(-1px);
}

.btn-action {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
}

.btn-edit {
    background: var(--warning);
    color: var(--dark);
    border: none;
}

.btn-edit:hover {
    background: #e0a800;
    color: var(--dark);
    transform: scale(1.05);
}

.btn-archive {
    background: var(--danger);
    color: white;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.85rem;
}

.btn-archive:hover {
    background: #c82333;
    color: white;
    transform: scale(1.05);
    text-decoration: none;
}

.table-custom {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table-custom thead th {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 2px solid #dee2e6;
    font-weight: 700;
    color: var(--dark);
    padding: 1rem 0.75rem;
}

.table-custom tbody td {
    padding: 0.9rem 0.75rem;
    vertical-align: middle;
    border-color: #f1f3f4;
}

.table-custom tbody tr:hover {
    background-color: #f8fdf9;
}

.badge-custom {
    padding: 0.5rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.8rem;
}

.badge-primary {
    background: rgba(40, 167, 69, 0.15);
    color: var(--primary-dark);
}

.badge-info {
    background: rgba(23, 162, 184, 0.15);
    color: #138496;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-header.info {
    background: linear-gradient(135deg, var(--info), #138496);
}

.modal-title {
    font-weight: 600;
}

.stats-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border-top: 4px solid var(--primary);
}

.stats-card.info {
    border-top: 4px solid var(--info);
}

.stats-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stats-card.info .stats-value {
    color: var(--info);
}

.stats-label {
    color: var(--secondary);
    font-size: 0.95rem;
    font-weight: 600;
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

.description-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
    line-height: 1.4;
}

/* Fix for button alignment in groups */
.btn-group {
    display: inline-flex;
    gap: 0.5rem;
}

/* Ensure modals are properly positioned */
.modal {
    z-index: 1060;
}

/* Make sure buttons are clickable */
button, .btn {
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .card-title {
        font-size: 1.1rem;
    }
    
    .btn-custom-primary, .btn-custom-secondary {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .stats-card {
        padding: 1rem;
    }
    
    .stats-value {
        font-size: 2rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<div class="card-container mt-4">
    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-value"><?php echo count($clusters); ?></div>
                <div class="stats-label">Fund Clusters</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-value"><?php echo count($divisions); ?></div>
                <div class="stats-label">Divisions</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card info">
                <div class="stats-value"><?php echo count($offices); ?></div>
                <div class="stats-label">Offices</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Fund Cluster Card -->
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="card-title">
                        <i class="fas fa-database"></i> Fund Clusters
                    </h5>
                    <button class="btn btn-custom-primary float-right" data-bs-toggle="modal" data-bs-target="#addClusterModal">
                        <i class="fas fa-plus"></i> Add Cluster
                    </button>
                </div>
                <div class="card-body">
                    <?php if(count($clusters) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th width="25%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($clusters as $i=>$c): ?>
                                        <tr>
                                            <td><span class="badge badge-custom badge-primary"><?= $i+1 ?></span></td>
                                            <td class="fw-semibold"><?= remove_junk($c['name']) ?></td>
                                            <td>
                                                <?php if(!empty($c['description'])): ?>
                                                    <div class="description-text"><?= remove_junk($c['description']) ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-action btn-edit" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editClusterModal<?= $c['id'] ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <a href="a_script.php?id=<?= $o['id'] ?>&type=fund_clusters" 
                                                       class="btn-archive"
                                                       title ="Archive">
                                                        <i class="fa-solid fa-file-zipper"></i> 
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <h5>No Fund Clusters</h5>
                            <p>Get started by adding your first fund cluster</p>
                            <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#addClusterModal">
                                <i class="fas fa-plus"></i> Add First Cluster
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Divisions & Offices Card -->
        <div class="col-md-6">
            <div class="card-custom">
                <div class="card-header card-header-custom info d-flex justify-content-between align-items-center">
                    <h5 class="card-title">
                        <i class="fas fa-building"></i> Divisions & Offices
                    </h5>
                    <div>
                        <button class="btn btn-custom-primary me-2" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
                            <i class="fas fa-plus"></i> Add Division
                        </button>
                        <button class="btn btn-custom-secondary" data-bs-toggle="modal" data-bs-target="#addOfficeModal">
                            <i class="fas fa-plus"></i> Add Office
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(count($divisions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>Division</th>
                                        <th>Office</th>
                                        <th width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $current_division = '';
                                    foreach($offices as $o): 
                                        if ($current_division != $o['division_name']):
                                            $current_division = $o['division_name'];
                                    ?>
                                        <tr class="table-active">
                                        <td colspan="3" class="fw-bold text-dark"><strong>
                                            <i class="fas fa-sitemap me-2"></i> <?= remove_junk($o['division_name']) ?></strong>
                                            <div class="btn-group float-right ms-3" role="group">
                                                <button class="btn btn-sm btn-action btn-edit" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editDivisionModal<?= $o['division_id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                               <a href="a_script.php?id=<?= $o['id'] ?>&type=divisions" 
                                                class="btn-archive btn-sm"
                                                 title="Archive">
                                                 <i class="fa-solid fa-file-zipper"></i> 
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endif; 
                                    ?>
                                        <tr>
                                            <td></td>
                                            <td><?= remove_junk($o['office_name']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-action btn-edit" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editOfficeModal<?= $o['id'] ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>  Edit
                                                    </button>
                                                   <a href="a_script.php?id=<?= $o['id'] ?>&type=offices" 
                                                       class="btn-archive btn-sm"
                                                       title="Archive">
                                                        <i class="fa-solid fa-file-zipper"></i> Archive
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-building"></i>
                            <h5>No Divisions or Offices</h5>
                            <p>Start by adding your first division and office</p>
                            <div class="mt-3">
                                <button class="btn btn-custom-primary me-2" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
                                    <i class="fas fa-plus"></i> Add Division
                                </button>
                                <button class="btn btn-custom-secondary" data-bs-toggle="modal" data-bs-target="#addOfficeModal">
                                    <i class="fas fa-plus"></i> Add Office
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Cluster Modal -->
<div class="modal fade" id="addClusterModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="add_cluster" value="1">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Fund Cluster</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cluster Name *</label>
                        <input type="text" name="cluster_name" class="form-control" placeholder="Enter Fund Cluster Name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" placeholder="Enter description (optional)" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Save Cluster</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Division Modal -->
<div class="modal fade" id="addDivisionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="add_division" value="1">
            <div class="modal-content">
                <div class="modal-header info">
                    <h5 class="modal-title">Add Division</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Division Name *</label>
                        <input type="text" name="division_name" class="form-control" placeholder="Enter Division Name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Save Division</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Office Modal -->
<div class="modal fade" id="addOfficeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="add_office" value="1">
            <div class="modal-content">
                <div class="modal-header info">
                    <h5 class="modal-title">Add Office</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Division *</label>
                        <select name="division_id" class="form-select" required>
                            <option value="">Select Division </option>
                            <?php foreach($divisions as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= $d['division_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Office Name *</label>
                        <input type="text" name="office_name" class="form-control" placeholder="Enter Office Name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Save Office</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modals -->
<?php foreach($clusters as $c): ?>
<div class="modal fade" id="editClusterModal<?= $c['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="edit_cluster" value="1">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Fund Cluster</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cluster Name *</label>
                        <input type="text" name="cluster_name" class="form-control" value="<?= remove_junk($c['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" placeholder="Enter description (optional)" rows="3"><?= remove_junk($c['description']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Update Cluster</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php foreach($divisions as $d): ?>
<div class="modal fade" id="editDivisionModal<?= $d['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="id" value="<?= $d['id'] ?>">
            <input type="hidden" name="edit_division" value="1">
            <div class="modal-content">
                <div class="modal-header info">
                    <h5 class="modal-title">Edit Division</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Division Name *</label>
                        <input type="text" name="division_name" class="form-control" value="<?= $d['division_name'] ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php foreach($offices as $o): ?>
<div class="modal fade" id="editOfficeModal<?= $o['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="id" value="<?= $o['id'] ?>">
            <input type="hidden" name="edit_office" value="1">
            <div class="modal-content">
                <div class="modal-header info">
                    <h5 class="modal-title">Edit Office</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Division *</label>
                        <select name="division_id" class="form-select" required>
                            <option value=""> Select Division </option>
                            <?php foreach($divisions as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $d['id'] == $o['division_id'] ? 'selected' : '' ?>>
                                    <?= $d['division_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Office Name *</label>
                        <input type="text" name="office_name" class="form-control" value="<?= $o['office_name'] ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include_once('layouts/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select all archive buttons
    const archiveButtons = document.querySelectorAll('.btn-archive');
    
    archiveButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // stop default link action

            const url = this.getAttribute('href'); // get archive link

            Swal.fire({
                title: 'Are you sure?',
                text: "This office will be archived.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, archive it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect if confirmed
                    window.location.href = url;
                }
            });
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded - checking Bootstrap functionality');
    

    
    // Check if Bootstrap is loaded properly
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JavaScript is not loaded!');
    } else {
        console.log('Bootstrap is loaded successfully');
    }

    // =============================================
    // DYNAMIC FIELDS FUNCTIONALITY - FIXED VERSION
    // =============================================

    // Remove existing form elements to prevent conflicts
    function cleanupExistingForms() {
        // Remove single field forms for divisions and offices
        const divisionModal = document.getElementById('addDivisionModal');
        const officeModal = document.getElementById('addOfficeModal');
        
        if (divisionModal) {
            const singleInput = divisionModal.querySelector('input[name="division_name"]');
            if (singleInput) {
                singleInput.remove();
            }
        }
        
        if (officeModal) {
            const singleDivisionSelect = officeModal.querySelector('select[name="division_id"]');
            const singleOfficeInput = officeModal.querySelector('input[name="office_name"]');
            if (singleDivisionSelect) singleDivisionSelect.remove();
            if (singleOfficeInput) singleOfficeInput.remove();
        }
    }

    // Initialize dynamic forms after cleanup
    function initializeDynamicForms() {
        cleanupExistingForms();
        
        // Dynamic fields for Divisions
        const divisionModal = document.getElementById('addDivisionModal');
        if (divisionModal) {
            const divisionForm = divisionModal.querySelector('form');
            // Remove the old hidden input
            const oldHidden = divisionForm.querySelector('input[name="add_division"]');
            if (oldHidden) oldHidden.remove();
            
            const divisionFieldsContainer = createDynamicFieldsContainer(divisionForm, 'division');
            
            // Add initial field
            addDivisionField(divisionFieldsContainer);
            
            // Add "Add More" button
            const addMoreBtn = createAddMoreButton('Add Another Division', () => {
                addDivisionField(divisionFieldsContainer);
            });
            divisionFieldsContainer.appendChild(addMoreBtn);
        }

        // Dynamic fields for Offices
        const officeModal = document.getElementById('addOfficeModal');
        if (officeModal) {
            const officeForm = officeModal.querySelector('form');
            // Remove the old hidden input
            const oldHidden = officeForm.querySelector('input[name="add_office"]');
            if (oldHidden) oldHidden.remove();
            
            const officeFieldsContainer = createDynamicFieldsContainer(officeForm, 'office');
            
            // Add initial field
            addOfficeField(officeFieldsContainer);
            
            // Add "Add More" button
            const addMoreBtn = createAddMoreButton('Add Another Office', () => {
                addOfficeField(officeFieldsContainer);
            });
            officeFieldsContainer.appendChild(addMoreBtn);
        }
    }

    // Function to create dynamic fields container
    function createDynamicFieldsContainer(form, type) {
        const existingBody = form.querySelector('.modal-body');
        // Clear existing content except for modal title structure
        const existingElements = existingBody.querySelectorAll('div:not(.modal-header)');
        existingElements.forEach(el => {
            if (!el.classList.contains('modal-header')) {
                el.remove();
            }
        });
        
        const container = document.createElement('div');
        container.className = 'dynamic-fields-container';
        existingBody.appendChild(container);
        return container;
    }

    // Function to create "Add More" button
    function createAddMoreButton(text, onClick) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-primary btn-sm mt-2 add-more-btn';
        button.innerHTML = `<i class="fas fa-plus me-1"></i> ${text}`;
        button.addEventListener('click', onClick);
        return button;
    }

    // Function to add division field
    function addDivisionField(container) {
        const fieldCount = container.querySelectorAll('.division-field-group').length;
        const fieldGroup = document.createElement('div');
        fieldGroup.className = 'division-field-group mb-3 position-relative';
        fieldGroup.innerHTML = `
            <div class="row align-items-center">
                <div class="col-10">
                    <label class="form-label fw-semibold ${fieldCount === 0 ? '' : 'text-muted'}">
                        Division Name ${fieldCount > 0 ? `#${fieldCount + 1}` : ''} *
                    </label>
                    <input type="text" name="division_names[]" class="form-control" 
                           placeholder="Enter Division Name" ${fieldCount === 0 ? 'required' : ''}>
                </div>
                <div class="col-2">
                    ${fieldCount > 0 ? `
                    <button type="button" class="btn btn-danger btn-sm remove-field-btn" 
                            style="margin-top: 1.75rem;" title="Remove this division">
                        <i class="fas fa-times"></i>
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
        
        container.appendChild(fieldGroup);
        
        // Add remove functionality
        const removeBtn = fieldGroup.querySelector('.remove-field-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fieldGroup.remove();
                updateFieldLabels(container, '.division-field-group', 'Division Name');
            });
        }
    }

    // Function to add office field
    function addOfficeField(container) {
        const fieldCount = container.querySelectorAll('.office-field-group').length;
        const fieldGroup = document.createElement('div');
        fieldGroup.className = 'office-field-group mb-3 position-relative';
        fieldGroup.innerHTML = `
            <div class="row align-items-start">
                <div class="col-5">
                    <label class="form-label fw-semibold ${fieldCount === 0 ? '' : 'text-muted'}">
                        Division ${fieldCount > 0 ? `#${fieldCount + 1}` : ''} *
                    </label>
                    <select name="division_ids[]" class="form-select" ${fieldCount === 0 ? 'required' : ''}>
                        <option value="">-- Select Division --</option>
                        <?php foreach($divisions as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['division_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5">
                    <label class="form-label fw-semibold ${fieldCount === 0 ? '' : 'text-muted'}">
                        Office Name ${fieldCount > 0 ? `#${fieldCount + 1}` : ''} *
                    </label>
                    <input type="text" name="office_names[]" class="form-control" 
                           placeholder="Enter Office Name" ${fieldCount === 0 ? 'required' : ''}>
                </div>
                <div class="col-2">
                    ${fieldCount > 0 ? `
                    <button type="button" class="btn btn-danger btn-sm remove-field-btn" 
                            style="margin-top: 1.75rem;" title="Remove this office">
                        <i class="fas fa-times"></i>
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
        
        container.appendChild(fieldGroup);
        
        // Add remove functionality
        const removeBtn = fieldGroup.querySelector('.remove-field-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fieldGroup.remove();
                updateFieldLabels(container, '.office-field-group', 'Office Name');
            });
        }
    }

    // Function to update field labels after removal
    function updateFieldLabels(container, selector, baseLabel) {
        const fieldGroups = container.querySelectorAll(selector);
        fieldGroups.forEach((group, index) => {
            const label = group.querySelector('.form-label');
            const input = group.querySelector('input, select');
            
            if (index === 0) {
                label.innerHTML = `${baseLabel} *`;
                label.classList.remove('text-muted');
                if (input) input.required = true;
            } else {
                label.innerHTML = `${baseLabel} #${index + 1}`;
                label.classList.add('text-muted');
                if (input) input.required = false;
            }
        });
    }

    // =============================================
    // FORM SUBMISSION HANDLING FOR DYNAMIC FIELDS
    // =============================================

    // Handle division form submission with multiple fields
    const divisionForm = document.querySelector('#addDivisionModal form');
    if (divisionForm) {
        divisionForm.addEventListener('submit', function(e) {
            const divisionFields = this.querySelectorAll('input[name="division_names[]"]');
            let hasValidFields = false;
            
            divisionFields.forEach(field => {
                if (field.value.trim() !== '') {
                    hasValidFields = true;
                }
            });
            
            if (!hasValidFields) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please enter at least one division name.',
                    confirmButtonText: 'OK'
                });
                return;
            }
        });
    }

    // Handle office form submission with multiple fields
    const officeForm = document.querySelector('#addOfficeModal form');
    if (officeForm) {
        officeForm.addEventListener('submit', function(e) {
            const divisionSelects = this.querySelectorAll('select[name="division_ids[]"]');
            const officeInputs = this.querySelectorAll('input[name="office_names[]"]');
            let hasValidFields = false;
            
            for (let i = 0; i < divisionSelects.length; i++) {
                if (divisionSelects[i].value && officeInputs[i].value.trim() !== '') {
                    hasValidFields = true;
                    break;
                }
            }
            
            if (!hasValidFields) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Please enter at least one valid office with both division and office name.',
                    confirmButtonText: 'OK'
                });
                return;
            }
        });
    }

    // =============================================
    // MODAL RESET FUNCTIONALITY
    // =============================================

    // Reset dynamic fields when modal is closed
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const dynamicContainers = this.querySelectorAll('.dynamic-fields-container');
            dynamicContainers.forEach(container => {
                // Remove all but the first field
                const fieldGroups = container.querySelectorAll('.division-field-group, .office-field-group');
                fieldGroups.forEach((group, index) => {
                    if (index > 0) {
                        group.remove();
                    } else {
                        // Reset the first field
                        const inputs = group.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            if (input.type !== 'hidden') {
                                input.value = '';
                            }
                        });
                    }
                });
            });
        });
    });

    // =============================================
    // STYLES FOR DYNAMIC FIELDS
    // =============================================
    const style = document.createElement('style');
    style.textContent = `
        .dynamic-fields-container {
            margin-top: 1rem;
            padding-top: 1rem;
        }
        
        .division-field-group, .office-field-group {
            padding: 0.75rem;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .division-field-group:hover, .office-field-group:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        
        .remove-field-btn {
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .remove-field-btn:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .add-more-btn {
            transition: all 0.3s ease;
        }
        
        .add-more-btn:hover {
            transform: translateY(-1px);
        }
    `;
    document.head.appendChild(style);

    // Initialize the dynamic forms
    initializeDynamicForms();
});
</script>