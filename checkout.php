<?php
$page_title = 'Request Form';
require_once('includes/load.php');
page_require_level(1);

// get logged-in user
$current_user = current_user();
$current_user_id = $current_user['id'] ?? null;
$current_user_name = $current_user['name'] ?? $current_user['username'] ?? '';

function get_users_table() {
    global $db;
    // fetch common user fields and build a display name
    $sql = "SELECT * FROM users ORDER BY name ASC";
    $result = $db->query($sql);
    $users = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // build a reasonable display name from whatever columns exist
            if (!empty($row['name'])) {
                $display = $row['name'];
            } elseif (!empty($row['first_name'])) {
                $display = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            } else {
                $display = $row['username'] ?? ('User ' . $row['id']);
            }
            $users[] = [
                'id' => $row['id'],
                'full_name' => $display,
                'position' => $row['position'] ?? '',
            ];
        }
    }
    return $users;
}

function get_requestors() {
    // merge users and employees into a single array with 'source' so front-end can tell them apart
    $requestors = [];
    $users = get_users_table();
    foreach ($users as $u) {
        $requestors[] = [
            'source' => 'users',
            'id' => $u['id'],
            'full_name' => $u['full_name'],
            'position' => $u['position'] ?? ''
        ];
    }
    $employees = get_employees();
    foreach ($employees as $e) {
        $requestors[] = [
            'source' => 'employees',
            'id' => $e['id'],
            'full_name' => $e['full_name'],
            'position' => $e['position'] ?? ''
        ];
    }
    return $requestors;
}

// Build list used in the select
$requestors = get_requestors();

// default selected requestor value (current logged user if present in users)
$default_selected = 'users_' . ($current_user_id ?? '0');

// ---------- Form submission handling ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $db;

    // parse selected requestor (format: source_id, e.g. "users_5" or "employees_3")
    $selected = $_POST['requestor'] ?? '';
    $parts = explode('_', $selected, 2);
    $source = $parts[0] ?? 'users';
    $rid = isset($parts[1]) ? (int)$parts[1] : 0;

    // fetch requestor row depending on source
    $requestor_row = null;
    if ($source === 'users') {
        $requestor_row = find_by_id('users', $rid);
    } else { // employees
        $requestor_row = find_by_id('employees', $rid);
    }

    // determine human-readable name & position for the requestor
    if ($requestor_row) {
        if ($source === 'users') {
            // users table might have name or username
            $requestor_name = $requestor_row['name'] ?? ($requestor_row['username'] ?? 'Unknown');
            $requestor_position = $requestor_row['position'] ?? '';
        } else {
            $requestor_name = trim(($requestor_row['first_name'] ?? '') . ' ' . ($requestor_row['middle_name'] ?? '') . ' ' . ($requestor_row['last_name'] ?? ''));
            $requestor_position = $requestor_row['position'] ?? '';
        }
    } else {
        $requestor_name = 'Unknown';
        $requestor_position = '';
    }

    // collect selected items and remarks
    $qtys = $_POST['qty'] ?? [];
    $remarks = remove_junk($db->escape($_POST['remarks'] ?? ''));

    // Filter out zero-qty values
    $qtys = array_filter($qtys, function($q) { return (int)$q > 0; });

    if (empty($qtys)) {
        $session->msg("d", "❌ No items selected.");
        redirect('checkout.php', false);
    }

    // Duplicate pending request check (keeps same logic as before)
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $check = $db->query("SELECT r.id 
                             FROM requests r 
                             JOIN request_items ri ON r.id = ri.req_id
                             WHERE r.requested_by = '{$rid}' 
                               AND ri.item_id = '{$item_id}' 
                               AND r.status = 'Pending' LIMIT 1");
        if ($db->num_rows($check) > 0) {
            $item = find_by_id('items', $item_id);
            $session->msg("d", "❌ You already have a pending request for item: {$item['name']}");
            redirect('checkout.php', false);
        }
    }

    $db->query("START TRANSACTION");

    // NOTE: requests table currently expects requested_by to be a user id.
    // If you want to store source info as well you need an extra column (e.g. requestor_source).
    // For now we store the numeric id in requested_by (this mirrors the earlier structure).
        $ris_prefix = date("Y") . '-' . date("m") . '-';
        $ris_no_input = remove_junk($db->escape($_POST['ris_no'] ?? '0000'));
        $ris_no = $ris_prefix . $ris_no_input;

        $query_request = "INSERT INTO requests (requested_by, date, status, ris_no)
                        VALUES ('{$rid}', NOW(), 'Pending', '{$ris_no}')";


   if (!$db->query($query_request)) {
    $db->query("ROLLBACK");
    $session->msg("d", "❌ Failed to create request: " . $db->error());
    redirect('checkout.php', false);
}


    $req_id = $db->insert_id();
    $all_ok = true;

    // Insert request items and update stock
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty = (int)$qty;
        $item = find_by_id('items', $item_id);
        if (!$item || $qty > (int)$item['quantity']) {
            $all_ok = false;
            $session->msg("d", "❌ Invalid quantity for item: {$item['name']}");
            break;
        }

        $query_item = "INSERT INTO request_items (req_id, item_id, qty, remarks) 
                       VALUES ('{$req_id}', '{$item_id}', '{$qty}', '{$db->escape($remarks)}')";
        if (!$db->query($query_item)) {
            $all_ok = false;
            break;
        }

        if (!$db->query("UPDATE items SET quantity = quantity - {$qty} WHERE id = '{$item_id}'")) {
            $all_ok = false;
            break;
        }
    }

    if ($all_ok) {
        $db->query("COMMIT");
        $session->msg("s", "✅ Request submitted successfully!");
    } else {
        $db->query("ROLLBACK");
        $session->msg("d", "❌ Failed to save requested items. Request cancelled.");
    }

    redirect('checkout.php', false);
}

function get_category_name($cat_id) {
    global $db;
    $id = (int)$cat_id;
    $result = $db->query("SELECT name FROM categories WHERE id = {$id} LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}

$all_items = find_all('items');

foreach ($all_items as &$item) {
    $item['cat_name'] = get_category_name($item['categorie_id']);
}

// Fetch all items AFTER handling submission
?>

<?php include_once('layouts/header.php'); ?>

<style>
    .search-box {
  position: relative;
  flex: 1;
  max-width: 300px;
}

.search-box input {
  padding-left: 2.5rem;
  border-radius: 25px;
  border: 1px solid #dee2e6;
}

.search-box .search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--secondary);
}
      .search-box {
    max-width: 100%;
  }
    .green-btn {
        background-color: #1e7e34;
        border-color: #28a745;
        color: white;
        border-radius: 5px;
    }
    .green-btn:hover {
        background-color: #004a04;
        border-color: #004a04;
        color: white;
    }
    .table-success {
        background-color: #e8f5e9 !important;
    }
    .form-control:focus {
        border-color: #006205;
        box-shadow: 0 0 0 0.2rem rgba(0, 98, 5, 0.25);
    }
    .border-success {
        border-color: #006205 !important;
    }
    .text-success {
        color: #006205 !important;
    }
    .modal-header.bg-success {
        background-color: #006205 !important;
    }
    .btn-success {
        background-color: #006205;
        border-color: #006205;
    }
    .btn-success:hover {
        background-color: #004a04;
        border-color: #004a04;
    }
    .card-header {
        background-color: #197707ff;
        border-bottom: 1px solid #dee2e6;
        font-weight: bold;
        color:white;
    }
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 20px;
    }
</style>

<!-- Header Card -->
<div class="card green-header" style="border-top: 5px solid #28a745; border-radius: 10px;">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-sm-9">                  
                <h5 class="mb-0"><i class="nav-icon fa-solid fa-pen-to-square"></i> Request Form</h5>
            </div>
            <div class=" d-flex justify-content-end">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control" placeholder="Search items..." id="searchInput">
                    </div>
            </div>
        </div>
    </div>
</div>

<form id="requestForm" method="post" action="">

<!-- Requestor Information Card -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-user me-2"></i> Requestor Information
    </div>
    <div class="card-body">
        <?php
        $year = date("Y");
        $month = date("m");
        $ris_prefix = $year . '-' . $month . '-';
        ?>

        <!-- Labels Row -->
        <div class="row mb-1 text-muted fw-bold">
            <div class="col-md-4">Requestor's Name:</div>
            <div class="col-md-4">Position / Office:</div>
            <div class="col-md-4">RIS No:</div>
        </div>

        <!-- Fields Row -->
        <div class="row mb-3 align-items-center">
            <!-- Requestor Dropdown -->
            <div class="col-md-4">
                <select name="requestor" id="requestorSelect" 
                        class="form-control text-success border-success" 
                        style="border-radius:5px;" 
                        required>
                    <option value=""> Select Requestor </option>
                    <?php foreach($requestors as $req): 
                        $val = $req['source'] . '_' . $req['id'];
                        $sel = ($val === $default_selected) ? 'selected' : '';
                    ?>
                        <option value="<?= $val; ?>" <?= $sel; ?>>
                            <?= htmlspecialchars($req['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Position -->
            <div class="col-md-4">
                <input type="text" id="positionField" 
                       class="form-control text-success border-success" 
                       value="" 
                       style="background:transparent;" readonly>
            </div>

            <!-- RIS No -->
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <span class="me-1 text-success fw-bold " style="margin-left:10px"><?= $ris_prefix; ?></span>
                    <input type="text" name="ris_no" id="risNoField" 
                           class="form-control text-success border-success"
                           placeholder="0000"
                           maxlength="4"
                            style="background: transparent; width: 50%; " required>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Items Card -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-boxes me-2"></i> Available Items
    </div>
    <div class="card-body">
        <div class="table-responsive mb-3">
        <?php if(!empty($all_items)): ?>
        <table class="table table-striped table-hover align-middle" id="itemsTable">
          <thead class="table-success">
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
              <td><?= htmlspecialchars($it['stock_card']); ?></td>       
              <td>
                <strong><?= htmlspecialchars($it['name']); ?></strong><br>
                <small class="text-muted"> <?= htmlspecialchars($it['cat_name']); ?></small>
            </td>

              <td class="text-center"><?= (int)$it['quantity']; ?></td>
              <td class="text-center">
                <input type="number" name="qty[<?= (int)$it['id']; ?>]" min="0" max="<?= (int)$it['quantity']; ?>" value="0" 
                       class="form-control text-center border-success" style="max-width:120px;" <?= $it['quantity']==0?'disabled':'' ?> 
                       title="Max: <?= (int)$it['quantity']; ?>">
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
    </div>
</div>

<!-- Remarks & Submit Card -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-comment me-2"></i> Remarks & Submission
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-9">
                <label class="fw-bold text-success">Remarks (Optional)</label>
                <textarea class="form-control border-success" name="remarks" rows="2" placeholder="Add remarks here..."></textarea>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn green-btn w-100" id="reviewBtn"><i class="fa-solid fa-clipboard-check"></i> Review Request</button>
            </div>
        </div>
    </div>
</div>

</form>

<!-- Modal (kept outside the form) -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Request Receipt</h5>
        <!-- keep the data-bs-dismiss attribute, JS fallback is also added below -->
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="receiptBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">Cancel</button>
        <button type="button" class="btn btn-success" id="finalSubmitBtn">Submit Request</button>
      </div>
    </div>
  </div>
</div>

<script>
// Pass PHP requestors to JS
const requestors = <?= json_encode($requestors); ?>;
const defaultSelected = "<?= $default_selected; ?>";

// Populate position field based on selected option
function updatePositionFieldFromSelect() {
    const sel = document.getElementById('requestorSelect').value;
    const [source, idStr] = sel.split('_');
    const id = parseInt(idStr || '0', 10);
    const found = requestors.find(r => r.source === source && parseInt(r.id,10) === id);
    document.getElementById('positionField').value = found ? (found.position || '') : '';
}

// set initial position after page load / default
document.addEventListener('DOMContentLoaded', function() {
    // if select has default, set position
    updatePositionFieldFromSelect();
});

// on change
document.getElementById('requestorSelect').addEventListener('change', updatePositionFieldFromSelect);

// search filter for the items table
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

// Create (single) Bootstrap Modal instance and use it for show/hide
let receiptModalEl = document.getElementById('receiptModal');
let receiptModal = null;
if (typeof bootstrap !== 'undefined' && receiptModalEl) {
    receiptModal = new bootstrap.Modal(receiptModalEl);
}

// Review button: build receipt HTML and show modal via the instance
document.getElementById('reviewBtn').addEventListener('click', function() {
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    let receiptHTML = '<p><strong>Requestor:</strong> ' + document.getElementById('requestorSelect').selectedOptions[0].text + 
                      ' (' + document.getElementById('positionField').value + ')</p>';
    receiptHTML += '<table class="table table-bordered"><thead><tr><th>Item Name</th><th>Qty</th></tr></thead><tbody>';
    let hasItem = false;

    rows.forEach(row => {
        const name = row.cells[1].innerText;
        const input = row.cells[3].querySelector('input');
        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            hasItem = true;
            receiptHTML += `<tr><td>${name}</td><td>${qty}</td></tr>`;
        }
    });

    receiptHTML += '</tbody></table>';
    if (!hasItem) {
        Swal.fire({icon:'warning', title:'No items selected', text:'Enter quantity greater than 0 for at least one item.'});
        return;
    }

    const remarks = document.querySelector('textarea[name="remarks"]').value.trim();
    if (remarks) receiptHTML += `<p><strong>Remarks:</strong> ${remarks}</p>`;

    document.getElementById('receiptBody').innerHTML = receiptHTML;

    // show modal safely
    if (receiptModal) {
        receiptModal.show();
    } else {
        // fallback if bootstrap not available
        document.getElementById('receiptModal').classList.add('show');
        document.getElementById('receiptModal').style.display = 'block';
    }
});

// Final submit -> submit the form
document.getElementById('finalSubmitBtn').addEventListener('click', function() {
    document.getElementById('requestForm').submit();
});

// fallback: ensure cancel and X buttons hide the modal (works even if data-bs-dismiss fails)
document.querySelectorAll('#receiptModal [data-bs-dismiss], #modalCancelBtn, .btn-close').forEach(btn => {
    btn.addEventListener('click', function() {
        if (receiptModal) {
            receiptModal.hide();
        } else {
            // fallback hide
            const m = document.getElementById('receiptModal');
            m.classList.remove('show');
            m.style.display = 'none';
        }
    });
});
</script>

<?php include_once('layouts/footer.php'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    var table = $('#itemsTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: false,
        autoWidth: true,
    });
    $('#searchInput').on('keyup', function() {
      table.search(this.value).draw();
    });

});
</script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const table = document.getElementById("itemsTable");
    const rows = table.getElementsByTagName("tr");

    searchInput.addEventListener("keyup", function () {
      const filter = this.value.toLowerCase();

      // Loop through table rows (skip header)
      for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName("td");
        let match = false;

        // Check every cell for a match
        for (let j = 0; j < cells.length; j++) {
          const cellText = cells[j].textContent || cells[j].innerText;
          if (cellText.toLowerCase().indexOf(filter) > -1) {
            match = true;
            break;
          }
        }

        // Show or hide the row based on match
        rows[i].style.display = match ? "" : "none";
      }
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('finalSubmitBtn').addEventListener('click', function () {
  Swal.fire({
    title: "Submit Request?",
    text: "Do you want to finalize and send this request?",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, submit it",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('requestForm').submit();
    }
  });
});

</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  // Select all quantity input fields
  document.querySelectorAll('input[name^="qty["]').forEach(input => {
    // Listen for any change or typing
    input.addEventListener('input', function() {
      const max = parseInt(this.max);
      const val = parseInt(this.value) || 0;

      // If user input exceeds available quantity
      if (val > max) {
        Swal.fire({
          icon: 'warning',
          title: 'Quantity Exceeded',
          text: `Only ${max} item(s) available in stock.`,
          confirmButtonColor: '#28a745',
        });

        // Reset to maximum allowed
        this.value = max;
      } else if (val < 0) {
        // prevent negative input
        this.value = 0;
      }
    });
  });
});
</script>
