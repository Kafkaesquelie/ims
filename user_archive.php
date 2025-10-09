<?php
$page_title = 'Request Archive';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user();
$user_id = (int)$current_user['id'];

// ✅ Fetch archived requests for the logged-in user
$archived_requests = find_by_sql("
    SELECT r.id, r.date, r.status,
           i.name AS item_name, 
           ri.qty, 
           (ri.qty * i.unit_cost) AS total_cost
    FROM requests r
    LEFT JOIN request_items ri ON r.id = ri.req_id
    LEFT JOIN items i ON ri.item_id = i.id
    WHERE r.requested_by = '{$user_id}'
      AND r.status = 'archived'
    ORDER BY r.date DESC
");

?>
<?php include_once('layouts/header.php'); ?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <?php echo display_msg($msg); ?>
      <div class="card shadow">
        <div class="card-header">
          <h3 class="card-title">  <i class=" nav-icon fa-solid fa-box-archive"></i> Archived Requests</h3>
        </div>
        <div class="card-body">
          <?php if (!empty($archived_requests)): ?>
            <table id="datatable" class="table table-bordered table-striped table-hover">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Item</th>
                  <th>Quantity</th>
                  <th>Total Cost</th>
                  <th>Status</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($archived_requests as $req): ?>
                <tr>
                  <td><?php echo remove_junk($req['id']); ?></td>
                  <td><?php echo remove_junk($req['date']); ?></td>
                  <td><?php echo remove_junk($req['item_name']); ?></td>
                  <td><?php echo remove_junk($req['qty']); ?></td>
                  <td><?php echo number_format($req['total_cost'], 2); ?></td>
                  <td><span class="badge bg-secondary"><?php echo ucfirst($req['status']); ?></span></td>
                  <td class="text-center">
                    <a href="restore_request.php?id=<?php echo (int)$req['id']; ?>" 
                       class="btn btn-sm btn-success" 
                       onclick="return confirm('Restore this request?');">
                       <i class="fas fa-undo"></i> Restore
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="text-center p-5">
              <i class="fas fa-archive fa-3x text-muted mb-3"></i>
              <h5 class="mb-2">No Archived Requests</h5>
              <p class="text-muted">You don’t have any archived requests yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ DataTable Script -->
<script>
$(document).ready(function() {
  $('#datatable').DataTable({
    "pageLength": 5,
    "lengthMenu": [5, 10, 25, 50, 100],
    "order": [[1, "desc"]],
    "autoWidth": false,
    "responsive": true,
    "dom": '<"top"f>rt<"bottom"lp><"clear">'
  });
});
</script>

<?php include_once('layouts/footer.php'); ?>
