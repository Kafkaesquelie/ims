<?php
  $page_title = 'Admin Home Page';
    session_start();
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn()) {
    header("Location: admin.php");
    exit();
  }

  // Checkin What level user has permission to view this page
  page_require_level(1);

  $c_categorie  = count_by_id('categories');
  $c_item       = count_by_id('items');
  $c_req        = count_requests();
  $c_smp       = count_by_id('semi_exp_prop');
  $items_low    = find_lacking_items('10');
  $recent_items = find_recent_item_added('5');
  $items_req    = find_highest_requested_items('10');

  $low_count = $items_low->num_rows;
  $total_items_count = $c_item['total'];
?>

<?php include_once('layouts/header.php'); ?>  

<style>
:root {
    --primary-green: #1e7e34;
    --dark-green: #155724;
    --light-green: #28a745;
    --accent-green: #34ce57;
    --primary-yellow: #ffc107;
    --dark-yellow: #e0a800;
    --light-yellow: #ffda6a;
    --card-bg: #ffffff;
    --text-dark: #343a40;
    --text-light: #6c757d;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --hover-shadow: 0 8px 25px rgba(30, 126, 52, 0.15);
}

/* Dashboard Header */
.dashboard-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    border-left: 5px solid var(--primary-yellow);
}

.dashboard-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
}

.dashboard-header .subtitle {
    opacity: 0.9;
    font-size: 0.9rem;
}

/* Info Boxes - Green & Yellow Theme */
.info-box {
    background: var(--card-bg);
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    border: none;
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
    border-top: 4px solid transparent;
}

.info-box:hover {
    transform: translateY(-5px);
    box-shadow: var(--hover-shadow);
}

.info-box-icon {
    width: 80px;
    height: 80px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.info-box:hover .info-box-icon {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.info-box-content {
    flex: 1;
    text-align: right;
}

.info-box-number {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 0.2rem;
    color: var(--dark-green);
}

.info-box-text {
    color: var(--text-dark);
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Cards Styling */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    border-top: 3px solid var(--primary-green);
}

.card:hover {
    box-shadow: var(--hover-shadow);
}

.card-header {
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
    border-bottom: 2px solid #e8f5e9;
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-header h3 {
    margin: 0;
    font-weight: 700;
    color: var(--dark-green);
    font-size: 1.2rem;
}

/* Badges */
.badge {
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
}

.badge-custom {
    background: rgba(40, 167, 69, 0.15);
    color: var(--dark-green);
    border: 1px solid rgba(40, 167, 69, 0.3);
}

/* Tables */
.table {
    margin-bottom: 0;
}

.table th {
    background: #005013ff;
    color: white;
    font-weight: 600;
    border: none;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
    border-color: #f1f3f4;
}

.table-hover tbody tr:hover {
    background-color: rgba(40, 167, 69, 0.05);
}

/* Status Badges */
.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow)) !important;
    color: #000 !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, var(--light-green), var(--primary-green)) !important;
}

/* Empty States */
.text-center.p-4 {
    padding: 3rem !important;
}

.text-center.p-4 i {
    opacity: 0.5;
    margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 1rem;
        text-align: center;
    }
    
    .info-box {
        margin-bottom: 1rem;
    }
    
    .info-box-icon {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
    }
    
    .info-box-number {
        font-size: 1.8rem;
    }
}

/* Animation for cards */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card, .info-box {
    animation: fadeInUp 0.6s ease forwards;
}

/* Custom scrollbar for tables */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--primary-green);
    border-radius: 10px;
}

/* Image styling */
.img-avatar {
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.img-avatar:hover {
    border-color: var(--primary-green);
}

/* Card tools */
.card-tools .btn {
    border-radius: 8px;
    transition: all 0.3s ease;
    color: var(--primary-green);
}

.card-tools .btn:hover {
    background-color: rgba(40, 167, 69, 0.1);
}

/* Yellow accent elements */
.yellow-accent {
    color: var(--primary-yellow);
}

.green-accent {
    color: var(--primary-green);
}
</style>

<!-- Dashboard Header -->
<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5><i class="nav-icon fa-solid fa-gauge-high me-2 yellow-accent"></i> Dashboard Overview</h5>
            <div class="subtitle">Welcome back! Here's what's happening with your inventory today.</div>
        </div>
        <div class="text-end">
            <div class="text-white-50 small">Last Updated</div>
            <div class="fw-bold"><?php echo date('F j, Y g:i A'); ?></div>
        </div>
    </div>
</div>

<!-- Info Boxes -->
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="requests.php" style="text-decoration: none;">
            <div class="info-box d-flex align-items-center">
                <span class="info-box-icon" style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green));">
                    <i class="nav-icon fa-solid fa-pen-to-square"></i>
                </span>
                <div class="info-box-content">
                    <div class="info-box-number"><?php echo $c_req; ?></div>
                    <span class="info-box-text">Pending Requests</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="items.php" style="text-decoration: none;">
            <div class="info-box d-flex align-items-center">
                <span class="info-box-icon" style="background: linear-gradient(135deg, var(--light-green), var(--primary-green));">
                    <i class="nav-icon fa-solid fa-box-open"></i>
                </span>
                <div class="info-box-content">
                    <div class="info-box-number"><?php echo $c_item['total']; ?></div>
                    <span class="info-box-text">Inventory Items</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="smp.php" style="text-decoration: none;">
            <div class="info-box d-flex align-items-center">
                <span class="info-box-icon" style="background: linear-gradient(135deg, var(--primary-yellow), var(--dark-yellow));">
                    <i class="fa-solid fa-warehouse"></i>
                </span>
                <div class="info-box-content">
                    <div class="info-box-number"><?php echo $c_smp['total']; ?></div>
                    <span class="info-box-text">Semi-Expendable Properties</span>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
        <a href="cat.php" style="text-decoration: none;">
            <div class="info-box d-flex align-items-center">
                <span class="info-box-icon" style="background: linear-gradient(135deg, var(--accent-green), var(--light-green));">
                    <i class="fa-solid fa-list"></i>
                </span>
                <div class="info-box-content">
                    <div class="info-box-number"><?php echo $c_categorie['total']; ?></div>
                    <span class="info-box-text">Categories</span>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Tables Section -->
<div class="row">
    <!-- Need Restock -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-triangle-exclamation me-2 yellow-accent"></i>
                    Need Restock
                    <?php if ($low_count > 0): ?>
                        <span class="badge bg-danger ms-2">
                            <?php echo $low_count; ?> Items
                        </span>
                    <?php endif; ?>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if ($total_items_count == 0): ?>
                        <div class="text-center p-5">
                            <i class="fa-solid fa-box-open text-muted fa-4x mb-3"></i>
                            <h5 class="text-muted mb-2">No items in inventory</h5>
                            <p class="text-muted">Your inventory is currently empty.</p>
                        </div>
                    <?php elseif ($low_count == 0): ?>
                        <div class="text-center p-5">
                            <i class="fa-solid fa-circle-check text-success fa-4x mb-3"></i>
                            <h5 class="text-success mb-2">All products are well stocked</h5>
                            <p class="text-muted">No products are below minimum stock levels.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Stock No.</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($items = $items_low->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-custom">
                                                <?php echo (int)$items['stock_card']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo remove_junk(first_character($items['name'])); ?></div>
                                            <small class="text-muted"><?php echo $items['description']; ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold <?php echo (int)$items['quantity'] == 0 ? 'text-danger' : 'text-warning'; ?>">
                                                <?php echo (int)$items['quantity']; ?>
                                            </div>
                                            <small class="text-muted">Popularity: <?php echo (int)$items['total_req']; ?></small>
                                        </td>
                                        <td>
                                            <?php if ((int)$items['quantity'] == 0): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> Out of Stock
                                                </span>
                                            <?php elseif ((int)$items['quantity'] <= 10): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Low Stock
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recently Added Items -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-clock-rotate-left me-2 green-accent"></i>
                    Recently Added Items
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (!empty($recent_items)): ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_items as $items): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($items['media_id'] === '0'): ?>
                                                    <img class="img-avatar img-circle me-3"
                                                         src="uploads/items/no_image.png"
                                                         alt="" style="width:45px; height:45px;">
                                                <?php else: ?>
                                                    <img class="img-avatar img-circle me-3"
                                                         src="uploads/items/<?php echo $items['image']; ?>"
                                                         alt="" style="width:45px; height:45px;">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo remove_junk(first_character($items['name'])); ?></div>
                                                    <small class="text-success">
                                                        <?php echo remove_junk(first_character($items['categorie'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success"><?php echo (int)$items['quantity']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $items['date_added']; ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fa-solid fa-inbox text-muted fa-4x mb-3"></i>
                            <h5 class="text-muted mb-2">No recently added items</h5>
                            <p class="text-muted">New items will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>