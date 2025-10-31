<?php
$page_title = 'View RIS Request';
require_once('includes/load.php');
page_require_level(1);

// Check if request ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $session->msg("d", "No request ID provided.");
    redirect('logs.php');
}

$request_id = (int)$_GET['id'];

// Fetch request info
$request = find_by_id('requests', $request_id);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('logs.php');
}

// Get requestor info with comprehensive joins across users, employees, offices, and divisions tables
$requestor_data = find_by_sql("
    SELECT 
        u.*,
        e.position,
        e.office as employee_office_id,
        e.division as employee_division_id,
        u.office as user_office_id,
        u.division as user_division_id,
        o.office_name,
        d.division_name,
        ou.office_name as user_office_name,
        du.division_name as user_division_name
    FROM users u
    LEFT JOIN employees e ON u.id = e.user_id
    LEFT JOIN offices o ON e.office = o.id
    LEFT JOIN divisions d ON e.division = d.id
    LEFT JOIN offices ou ON u.office = ou.id
    LEFT JOIN divisions du ON u.division = du.id
    WHERE u.id = '{$request['requested_by']}' 
    LIMIT 1
");
$requestor_data = !empty($requestor_data) ? $requestor_data[0] : null;

$requestor_name = $requestor_data ? $requestor_data['name'] : 'Unknown';
$requestor_position = $requestor_data ? ($requestor_data['position'] ?? '') : '';

// Get division and office - check multiple possible sources
$requestor_division = 'Not specified';
$requestor_office = 'Not specified';

if ($requestor_data) {
    // Priority 1: Check employee joins (office_name, division_name from employee joins)
    if (!empty($requestor_data['division_name'])) {
        $requestor_division = $requestor_data['division_name'];
    } elseif (!empty($requestor_data['user_division_name'])) {
        $requestor_division = $requestor_data['user_division_name'];
    }
    
    if (!empty($requestor_data['office_name'])) {
        $requestor_office = $requestor_data['office_name'];
    } elseif (!empty($requestor_data['user_office_name'])) {
        $requestor_office = $requestor_data['user_office_name'];
    }
    
    // Priority 2: If still not found, try direct ID lookups
    if ($requestor_division == 'Not specified') {
        if (!empty($requestor_data['employee_division_id'])) {
            $division_data = find_by_id('divisions', $requestor_data['employee_division_id']);
            if ($division_data && !empty($division_data['division_name'])) {
                $requestor_division = $division_data['division_name'];
            }
        } elseif (!empty($requestor_data['user_division_id'])) {
            $division_data = find_by_id('divisions', $requestor_data['user_division_id']);
            if ($division_data && !empty($division_data['division_name'])) {
                $requestor_division = $division_data['division_name'];
            }
        }
    }
    
    if ($requestor_office == 'Not specified') {
        if (!empty($requestor_data['employee_office_id'])) {
            $office_data = find_by_id('offices', $requestor_data['employee_office_id']);
            if ($office_data && !empty($office_data['office_name'])) {
                $requestor_office = $office_data['office_name'];
            }
        } elseif (!empty($requestor_data['user_office_id'])) {
            $office_data = find_by_id('offices', $requestor_data['user_office_id']);
            if ($office_data && !empty($office_data['office_name'])) {
                $requestor_office = $office_data['office_name'];
            }
        }
    }
}

// Fetch requested items with unit directly from request_items
$items = find_by_sql("
    SELECT 
        ri.item_id,
        ri.qty,
        ri.unit,
        ri.remarks,
        i.name as item_name,
        i.stock_card,
        i.quantity as current_stock,
        i.categorie_id,
        c.name AS category_name
    FROM request_items ri
    LEFT JOIN items i ON ri.item_id = i.id
    LEFT JOIN categories c ON i.categorie_id = c.id
    WHERE ri.req_id = '{$request_id}'
");

// Current logged-in user (for approved by/issued by)
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";
$current_user_position = isset($current_user['position']) ? remove_junk($current_user['position']) : "";

// Calculate totals
$total_items = count($items);
$total_quantity = 0;
foreach ($items as $item) {
    $total_quantity += (float)$item['qty'];
}

// Function to get category color
function getCategoryColor($category_name) {
    $colors = [
        'Common Supplies' => 'primary',
        'GSO Supplies' => 'warning',
        'Electrical Supplies' => 'success',
        'Janitorial Supplies' => 'danger',
        'Motorpool Supplies' => 'primary',
    ];
    
    $default_colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary', 'dark'];
    $category_key = ucfirst(strtolower(trim($category_name)));
    
    return $colors[$category_key] ?? $default_colors[array_rand($default_colors)];
}
?>

<?php include_once('layouts/header.php'); ?>

<!-- Debug information -->
<div class="container-fluid">
    <?php if (isset($_GET['debug'])): ?>
    <div class="alert alert-info">
        <h6>Debug Information:</h6>
        <p><strong>User ID:</strong> <?php echo $request['requested_by']; ?></p>
        <p><strong>Employee Office ID:</strong> <?php echo isset($requestor_data['employee_office_id']) ? $requestor_data['employee_office_id'] : 'Not set'; ?></p>
        <p><strong>Employee Division ID:</strong> <?php echo isset($requestor_data['employee_division_id']) ? $requestor_data['employee_division_id'] : 'Not set'; ?></p>
        <p><strong>User Office ID:</strong> <?php echo isset($requestor_data['user_office_id']) ? $requestor_data['user_office_id'] : 'Not set'; ?></p>
        <p><strong>User Division ID:</strong> <?php echo isset($requestor_data['user_division_id']) ? $requestor_data['user_division_id'] : 'Not set'; ?></p>
        <p><strong>All Requestor Fields:</strong> 
            <?php 
            if ($requestor_data) {
                foreach ($requestor_data as $key => $value) {
                    echo "<br><strong>{$key}:</strong> " . (is_array($value) ? print_r($value, true) : $value);
                }
            } else {
                echo "No requestor data found";
            }
            ?>
        </p>
    </div>
    <?php endif; ?>

<style>
     .btn-custom {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }
      .btn-back {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        color: white;
    }
</style>

    <!-- Status Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert 
                <?php 
                if ($request['status'] == 'Completed'): echo 'alert-success';
                elseif ($request['status'] == 'Archived'): echo 'alert-danger';
                else: echo 'alert-warning';
                endif; 
                ?> 
                d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="alert-heading mb-1">
                      RIS NO:   <?php echo isset($request['ris_no']) ? $request['ris_no'] : 'RIS Request'; ?>
                    </h5>
                    <p class="mb-0">
                        <strong>Status:</strong> 
                        <span class="badge 
                            <?php 
                            if ($request['status'] == 'Completed'): echo 'bg-success';
                            elseif ($request['status'] == 'Archived'): echo 'bg-danger';
                            else: echo 'bg-warning text-dark';
                            endif; 
                            ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </span>
                        • Requested on <?php echo date("F d, Y", strtotime($request['date'])); ?>
                    </p>
                </div>
                <div class="text-end">
                    <div class="h6 mb-1"><?php echo $total_items; ?> Items</div>
                    <div class="text-muted"><?php echo $total_quantity; ?> Total Units</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Requestor & Details -->
        <div class="col-lg-4">
            <!-- Requestor Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-user-circle me-2 text-primary"></i> Requestor Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <?php if ($requestor_data && !empty($requestor_data['prof_pic'])): ?>
                                <img src="uploads/users/<?php echo $requestor_data['prof_pic']; ?>" 
                                     alt="Profile" 
                                     class="rounded-circle"
                                     style="width:60px; height:60px; object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width:60px; height:60px;">
                                    <i class="fas fa-user fa-lg text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1"><?php echo remove_junk($requestor_name); ?></h6>
                            <p class="text-muted mb-0 small"><?php echo remove_junk($requestor_position); ?></p>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Division:</span>
                                <span class="fw-medium"><?php echo remove_junk($requestor_division); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Office:</span>
                                <span class="fw-medium"><?php echo remove_junk($requestor_office); ?></span>
                            </div>
                        </div>
                        <?php if ($requestor_data && !empty($requestor_data['contact'])): ?>
                        <div class="col-12">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Contact:</span>
                                <span class="fw-medium"><?php echo $requestor_data['contact']; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($requestor_data && !empty($requestor_data['email'])): ?>
                        <div class="col-12">
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Email:</span>
                                <span class="fw-medium"><?php echo $requestor_data['email']; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Items & Details -->
        <div class="col-lg-8">
            <!-- Items Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-boxes me-2 text-warning"></i> Requested Items
                    </h6>
                    <span class="badge bg-primary"><?php echo $total_items; ?> items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Item</th>
                                    <th>Category</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-4">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $index => $item): 
                                    $category_color = getCategoryColor($item['category_name']);
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div>
                                                <strong class="d-block"><?php echo remove_junk($item['item_name']); ?></strong>
                                                <small class="text-muted">Stock #: <?php echo (int)$item['stock_card']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $category_color; ?>">
                                                <?php echo remove_junk($item['category_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-primary"><?php echo (float)$item['qty']; ?></div>
                                            <small class="text-muted"><?php echo remove_junk($item['unit']); ?></small>
                                        </td>
                                       
                                        <td class="text-center">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                Available
                                            </span>
                                        </td>
                                        <td class="pe-4">
                                            <?php if (!empty($item['remarks'])): ?>
                                                <span class="text-muted small" data-bs-toggle="tooltip" title="<?php echo remove_junk($item['remarks']); ?>">
                                                    <i class="fas fa-comment me-1"></i>
                                                    <?php echo strlen($item['remarks']) > 30 ? substr($item['remarks'], 0, 30) . '...' : $item['remarks']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <a href="logs.php" class="btn btn-custom btn-back">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Rest of your CSS and JavaScript remains the same -->
<style>
.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.05);
    border-radius: 12px 12px 0 0 !important;
}

.table th {
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f8f9fa;
}

.progress {
    border-radius: 10px;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.alert {
    border: none;
    border-radius: 12px;
}

.shadow-sm {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.08) !important;
}
</style>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<?php include_once('layouts/footer.php'); ?>