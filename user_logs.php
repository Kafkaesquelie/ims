<?php
$page_title = 'User Logs';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user(); 
$user_id = (int)$current_user['id'];

// Get all approved/rejected requests with items
$all_requests = find_all_user_req_logs();

// Filter only the current user's requests
$user_requests = array_filter($all_requests, function($req) use ($user_id) {
    return $req['requested_by'] == $user_id; 
});
?>

<?php include_once('layouts/header.php'); ?>
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
</style>
    <h5 class="mb-4"> <i class="nav-icon fas fa-file-invoice"></i> Manage Transactions</h5>

    <?php if(count($user_requests) > 0): ?>
     <div class="table-responsive">
    <table id="userReqTable" class="table table-striped table-hover nowrap" style="width:100%">
        <thead class="table-success">
            <tr>
                <th class="text-center">No.</th>
                <th class="text-center">Item</th>
                <th class="text-center">Date</th>            
                <th class="text-center"class="text-center">Quantity</th>
                <th class="text-center">Total Cost</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($user_requests as $row): ?>
                <tr>
                    <td class="text-center"><span class="badge badge-custom badge-primary fs-6"><?php echo count_id(); ?></td></span>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>                
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['qty']) ?></td>
                    <td class="text-center"><?= htmlspecialchars(number_format($row['total_cost'], 2)) ?></td> 
                    <td class="text-center">
                        <?php 
                            $status = strtolower($row['status']);
                            $badgeClass = 'secondary';
                            if ($status == 'completed') $badgeClass = 'success';
                            elseif ($status == 'rejected') $badgeClass = 'danger';
                            elseif ($status == 'pending') $badgeClass = 'warning';
                        ?>
                        <span class="badge bg-<?php echo $badgeClass; ?>">
                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>



    <?php else: ?>
    <div class="text-center p-5">
        <i class="fa-solid fa-clipboard-list text-muted fa-3x mb-3"></i>
        <h5 class="mb-2">No logs available</h5>
        <p class="text-muted">You don’t have any approved or rejected requests yet.</p>
    </div>
<?php endif; ?>




<?php include_once('layouts/footer.php'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    var table = $('#userReqTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        search: false,
        autoWidth: false,
    });

});

</script>

