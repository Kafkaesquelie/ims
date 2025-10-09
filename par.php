<?php
$page_title = ' PAR Files';
require_once('includes/load.php');
page_require_level(3);

$current_user = current_user();
$current_user_id = (int)$current_user['id'];

// Fetch PAR transactions (match either user_id or employee_id)
$par_files = find_by_sql("
    SELECT 
        t.PAR_No,
        s.item_description,
        t.quantity,
        s.unit,
        t.transaction_date AS date_acquired
    FROM transactions t
    JOIN semi_exp_prop s ON t.item_id = s.id
    WHERE t.employee_id = '{$current_user_id}'
      AND t.PAR_No IS NOT NULL
    ORDER BY t.transaction_date DESC
");



?>


<?php include_once('layouts/header.php'); ?>  

<div class="card">
  <div class="card-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <h5 class="card-title"><i class="nav-icon fas fa-handshake"></i> Property Acknowledgement Receipts</h5>
  </div>
  <div class="card-body">
    <table class="table table-striped" id="parTable">
      <thead>
        <tr>
          <th><b>PAR Number</b></th>
          <th><b>Item Description</b></th>
          <th><b>Quantity</b></th>
          <th><b>Unit</b></th>
          <th><b>Date Acquired</b></th>
          <th><b>Actions</b></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($par_files)): ?>
          <?php foreach ($par_files as $row): ?>
            <tr>
              <td><strong><?php echo remove_junk($row['PAR_No']); ?></strong></td>
              <td><?php echo remove_junk($row['item_description']); ?></td>
              <td><?php echo (int)$row['quantity']; ?></td>
              <td><?php echo remove_junk($row['unit']); ?></td>
              <td><?php echo date('Y-m-d', strtotime($row['date_acquired'])); ?></td>
              <td>
                <a href="par_view.php?par=<?php echo urlencode($row['PAR_No']); ?>" class="btn btn-sm btn-primary"> <i class="fa-solid fa-eye"></i> View</a>
                <a href="print_forms.php?par=<?php echo urlencode($row['PAR_No']); ?>" class="btn btn-sm btn-success" target="_blank" title="print"><i class="fa-solid fa-print"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
        
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>  

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    var table = $('#parTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        search: false,
        autoWidth: false,
    });

});

</script>