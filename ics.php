<?php
$page_title = 'ICS Files';
require_once('includes/load.php');
page_require_level(3);

// Get current user
$current_user = current_user();
$user_id = (int)$current_user['id'];

// Fetch transactions with ICS_No for current user
$transactions = find_by_sql("
    SELECT 
        t.ICS_No,
        s.item_description,
        t.quantity,
        s.unit,
        s.estimated_use
    FROM transactions t
    LEFT JOIN semi_exp_prop s ON t.item_id = s.id
    WHERE t.employee_id = '{$user_id}' 
      AND t.ICS_No IS NOT NULL
    ORDER BY t.transaction_date DESC
");
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header" style=" border-top: 5px solid #28a745; border-radius: 10px;">
        <h3 class="card-title"> <i class=" nav-icon fa-solid fa-box-archive"></i> Inventory Custodian Slips</h3>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="icsTable">
          <thead>
            <tr>
              <th>ICS Number</th>
              <th>Description</th>
              <th>Quantity</th>
              <th>Unit</th>
              <th>Estimated Useful Life</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if($transactions): ?>
              <?php foreach($transactions as $trans): ?>
                <tr>
                  <td><?php echo remove_junk($trans['ICS_No']); ?></td>
                  <td><?php echo remove_junk($trans['description']); ?></td>
                  <td><?php echo remove_junk($trans['quantity']); ?></td>
                  <td><?php echo remove_junk($trans['unit']); ?></td>
                  <td><?php echo remove_junk($trans['useful_life']); ?></td>
                  <td>
                  <a href="ics_view.php?par=<?php echo urlencode($row['ICS_No']); ?>" class="btn btn-sm btn-primary"> <i class="fa-solid fa-eye"></i> View</a>
                  <a href="print_forms.php?par=<?php echo urlencode($row['ICS_No']); ?>" class="btn btn-sm btn-success" target="_blank" title="print"><i class="fa-solid fa-print"></i></a>
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

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    var table = $('#icsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        search: false,
        autoWidth: false,
    });

});

</script>