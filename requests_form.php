<?php
$page_title = 'Request Form';
require_once('includes/load.php');
page_require_level(3);

// Check login
if (!$session->isUserLoggedIn(true)) {
    redirect('index.php', false);
}

$current_user = current_user();
$user_id = (int)$current_user['id']; 
$user_name = $current_user['name']; 

$user_department_id = $current_user['department']; 
$dept = find_by_id('departments', $user_department_id);
$user_department = $dept ? $dept['department'] : 'Unknown';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    global $db;

    $qtys = $_POST['qty'] ?? [];
    $remarks = remove_junk($db->escape($_POST['remarks'] ?? ''));

    // Filter out items with zero quantity
    $qtys = array_filter($qtys, fn($q) => (int)$q > 0);

    if (empty($qtys)) {
        $session->msg("d", "❌ No items selected.");
        redirect('requests_form.php', false);
    }

    // Check for duplicate pending requests
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $check = $db->query("SELECT r.id 
                             FROM requests r 
                             JOIN request_items ri ON r.id = ri.req_id
                             WHERE r.requested_by = '{$user_id}' 
                               AND ri.item_id = '{$item_id}' 
                               AND r.status = 'Pending' LIMIT 1");
        if ($db->num_rows($check) > 0) {
            $item = find_by_id('items', $item_id);
            $session->msg("d", "❌ You already have a pending request for item: {$item['name']}");
            redirect('requests_form.php', false);
        }
    }

    // Start transaction
    $db->query("START TRANSACTION");

    // ✅ Insert the request header ONCE
    $query_request = "INSERT INTO requests (requested_by, date, status, remarks)
                      VALUES ('{$user_id}', NOW(), 'Pending', '{$remarks}')";
    if (!$db->query($query_request)) {
    $db->query("ROLLBACK");
    $session->msg("d", "❌ Failed to create request: " . $db->error());
    redirect('requests_form.php', false);
}

    $req_id = $db->insert_id();

    // --- Generate RIS No ---
    // --- Generate Partial RIS No (last 4 digits left blank for admin) ---
    $year = date("Y");
    $month = date("m");
    $ris_no = "{$year}-{$month}-"; // Admin fills last 4 digits later


    if ($result && $db->num_rows($result) > 0) {
        $last_ris = $db->fetch_assoc($result)['ris_no'];
        $last_seq = (int)substr($last_ris, -4);
        $new_seq = str_pad($last_seq + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $new_seq = "0001";
    }

    $ris_no = "{$year}-{$month}-";

    // Save RIS No
    if (!$db->query("UPDATE requests SET ris_no = '{$ris_no}' WHERE id = '{$req_id}'")) {
        $db->query("ROLLBACK");
        $session->msg("d", "❌ Failed to generate RIS No.");
        redirect('requests_form.php', false);
    }

    // ✅ Insert request items
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty = (int)$qty;

        $item = find_by_id('items', $item_id);
        if (!$item || $qty > (int)$item['quantity']) {
            $db->query("ROLLBACK");
            $session->msg("d", "❌ Invalid quantity for item: {$item['name']}");
            redirect('requests_form.php', false);
        }

        // Insert into request_items
        $query_item = "INSERT INTO request_items (req_id, item_id, qty)
                       VALUES ('{$req_id}', '{$item_id}', '{$qty}')";
        if (!$db->query($query_item)) {
    $db->query("ROLLBACK");
    $session->msg("d", "❌ Failed to add item: " . $db->error());
    redirect('checkout.php', false);
}

        // Update stock
        $new_qty = $item['quantity'] - $qty;
        if (!$db->query("UPDATE items SET quantity = '{$new_qty}' WHERE id = '{$item_id}'")) {
            $db->query("ROLLBACK");
            $session->msg("d", "❌ Failed to update stock for {$item['name']}");
            redirect('requests_form.php', false);
        }
    }

    $db->query("COMMIT");
    $session->msg("s", "✅ Request successfully created! RIS No: {$ris_no}");
    redirect('requests_form.php', false);
}

// Fetch items AFTER handling submission (so page reload still works)
$all_items = find_all('items'); 
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

<div class="row mb-2 align-items-center" style="border-top: 5px solid #006205; border-radius: 10px;">
    <div class="col-sm-9 mt-3">                  
        <h5 class="mb-0"><i class="nav-icon fa-solid fa-pen-to-square"></i> Request Form</h5>
    </div>
    <div class="col-sm-3 d-flex justify-content-end mt-3">
        <div class="input-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Search items...">
            <button class="btn btn-secondary" id="searchBtn" type="button"><i class="fas fa-search"></i></button>
        </div>
    </div>
</div>

  <div class="card-body">

    <form id="requestForm" method="post" action="">
      <div class="row mb-3">
        <div class="col-md-6">
          <label>Requestor's Name:</label>
          <input type="text" class="form-control-plaintext border-bottom text-success" value="<?= $user_name; ?>" readonly>
        </div>
        <div class="col-md-6">
          <label>Department:</label>
          <input type="text" class="form-control-plaintext border-bottom text-success" value="<?= $user_department; ?>" readonly>
        </div>
      </div>

      <label>Available Items</label>
      <div class="table-responsive mb-3">
        <?php if(!empty($all_items)): ?>
        <table class="table table-striped table-hover align-middle" id="itemsTable">
          <thead class="table-light">
            <tr>
              <th>Stock Card</th>
              <th>Item Name</th>
              <th class="text-center">Available Qty</th>
              <th class="text-center">Request Qty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($all_items as $it): ?>
            <tr <?= $it['quantity'] == 0 ? 'class="table-secondary"' : '' ?>>
              <td><?= $it['stock_card']; ?></td>         
              <td><?= $it['name']; ?></td>
              <td class="text-center"><?= $it['quantity']; ?></td>
              <td class="text-center">
                <input type="number" name="qty[<?= $it['id']; ?>]" min="0" max="<?= $it['quantity']; ?>" value="0" 
                       class="form-control text-center" style="max-width:120px;" <?= $it['quantity']==0?'disabled':'' ?> 
                       title="Max: <?= $it['quantity']; ?>">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="text-center p-4">
          <i class="fas fa-box-open fa-3x text-muted mb-2"></i>
          <h5>No items available</h5>
          <p class="text-muted mb-0">No items in the system to request.</p>
        </div>
        <?php endif; ?>
      </div>

      <div class="row mb-3">
        <div class="col-md-9">
          <label>Remarks (Optional)</label>
          <textarea class="form-control" name="remarks" rows="2" placeholder="Add remarks here..."></textarea>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-success w-100" id="reviewBtn">Review Request</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Request Receipt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="receiptBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="finalSubmitBtn">Submit Request</button>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<script>
// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

// Review before submit
// Review before submit
document.getElementById('reviewBtn').addEventListener('click', function() {
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    let receiptHTML = '<table class="table table-bordered"><thead><tr><th>Item Name</th><th>Qty</th></tr></thead><tbody>';
    let hasItem = false;

    rows.forEach(row => {
        const name = row.cells[1].innerText;  // Item Name column
        const input = row.cells[3].querySelector('input'); // Request Qty input
        if (!input) return; // skip rows with no input

        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            hasItem = true;
            receiptHTML += `<tr><td>${name}</td><td>${qty}</td></tr>`;
        }
    });

    receiptHTML += '</tbody></table>';
    if (!hasItem) {
        Swal.fire({
            icon: 'warning',
            title: 'No items selected',
            text: 'Enter quantity greater than 0 for at least one item.'
        });
        return;
    }

    const remarks = document.querySelector('textarea[name="remarks"]').value.trim();
    if (remarks) receiptHTML += `<p><strong>Remarks:</strong> ${remarks}</p>`;

    document.getElementById('receiptBody').innerHTML = receiptHTML;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
});


</script>

<script>
document.getElementById('finalSubmitBtn').addEventListener('click', function() {
    // Submit the form
    document.getElementById('requestForm').submit();
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // Watch all qty inputs
  document.querySelectorAll('input[name^="qty["]').forEach(input => {
    input.addEventListener('input', function() {
      const max = parseInt(this.getAttribute('max'));
      const val = parseInt(this.value) || 0;

      if (val > max) {
        Swal.fire({
          icon: 'warning',
          title: 'Quantity Exceeded',
          text: `Only ${max} item(s) are available in stock.`,
          confirmButtonColor: '#28a745',
        });
        this.value = max; // Reset to max
      } else if (val < 0) {
        this.value = 0; // Prevent negatives
      }
    });
  });
});
</script>


<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    var table = $('#itemsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
         searching: false ,
        autoWidth: true,
       
    });

});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
