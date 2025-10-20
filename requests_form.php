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

// ✅ Get helper functions
function get_unit_name($unit_id)
{
    global $db;
    $res = $db->query("SELECT name FROM units WHERE id = '{$unit_id}' LIMIT 1");
    return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res)['name'] : '';
}

// ✅ Get base unit name from base_units table
function get_base_unit_name($base_unit_id)
{
    global $db;
    $res = $db->query("SELECT name FROM base_units WHERE id = '{$base_unit_id}' LIMIT 1");
    return ($res && $db->num_rows($res) > 0) ? $db->fetch_assoc($res)['name'] : 'Unit';
}

function get_category_name($cat_id)
{
    global $db;
    $id = (int)$cat_id;
    $result = $db->query("SELECT name FROM categories WHERE id = {$id} LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}

function calculate_display_quantity($item)
{
    $quantity = (float)$item['quantity'];
    
    // If no conversion or conversion rate is 1, return simple quantity
    if ($item['conversion_rate'] <= 1 || $item['main_unit_name'] === $item['base_unit_name']) {
        return number_format($quantity, 2) . " " . $item['main_unit_name'];
    }

    // Calculate full main units and remaining base units
    $full_main_units = floor($quantity);
    $remaining_main_decimal = $quantity - $full_main_units;
    $remaining_base_units = $remaining_main_decimal * $item['conversion_rate'];

    // Format the display - ensure whole numbers for main units
    if ($full_main_units > 0 && $remaining_base_units > 0) {
        return $full_main_units . " " . $item['main_unit_name'] . " | " . 
               (int)$remaining_base_units . " " . $item['base_unit_name'];
    } elseif ($full_main_units > 0) {
        return $full_main_units . " " . $item['main_unit_name'];
    } else {
        return (int)$remaining_base_units . " " . $item['base_unit_name'];
    }
}

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

    // --- Generate Partial RIS No (last 4 digits left blank for admin) ---
    $year = date("Y");
    $month = date("m");
    $ris_no = "{$year}-{$month}-"; // Admin fills last 4 digits later

    // Save RIS No
    if (!$db->query("UPDATE requests SET ris_no = '{$ris_no}' WHERE id = '{$req_id}'")) {
        $db->query("ROLLBACK");
        $session->msg("d", "❌ Failed to generate RIS No.");
        redirect('requests_form.php', false);
    }

    // ✅ Insert request items
    foreach ($qtys as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty = (float)$qty;

        $item = find_by_id('items', $item_id);
        if (!$item) continue;

        // Get conversion data
        $conversion = find_by_sql("SELECT conversion_rate, from_unit_id, to_unit_id 
                                   FROM unit_conversions WHERE item_id = '{$item_id}' LIMIT 1");
        $conversion_rate = $conversion ? (float)$conversion[0]['conversion_rate'] : 1;
        $from_unit_id = $conversion ? $conversion[0]['from_unit_id'] : $item['unit_id'];
        $to_unit_id = $conversion ? $conversion[0]['to_unit_id'] : $item['unit_id'];

        $unit_name = get_unit_name($item['unit_id']);
        $base_unit_name = get_base_unit_name($item['base_unit_id']);

        // Determine requested unit type
        $requested_unit_type = $_POST['unit_type'][$item_id] ?? $unit_name;
        $is_requesting_base_unit = ($requested_unit_type === $base_unit_name);

        // Calculate quantity to deduct from inventory
        if ($is_requesting_base_unit && $conversion_rate > 1) {
            // Requesting pieces but stored in boxes: convert to boxes
            $qty_to_deduct = $qty / $conversion_rate;
        } else {
            // Same unit or no conversion needed
            $qty_to_deduct = $qty;
        }

        // Check stock availability
        if ($qty_to_deduct > $item['quantity']) {
            $db->query("ROLLBACK");
            
            // Determine which unit to display available stock
            if ($is_requesting_base_unit && $conversion_rate > 1) {
                $available_main = floor($item['quantity']);
                $remaining_decimal = $item['quantity'] - $available_main;
                $available_base = (int)($remaining_decimal * $conversion_rate);

                if ($available_main > 0 && $available_base > 0) {
                    $available_display = $available_main . " " . $unit_name . " | " . $available_base . " " . $base_unit_name;
                } elseif ($available_main > 0) {
                    $available_display = $available_main . " " . $unit_name;
                } else {
                    $available_display = $available_base . " " . $base_unit_name;
                }
            } else {
                $available_main = floor($item['quantity']);
                $remaining_decimal = $item['quantity'] - $available_main;
                $available_base = (int)($remaining_decimal * $conversion_rate);
                
                if ($available_main > 0 && $available_base > 0) {
                    $available_display = $available_main . " " . $unit_name . " | " . $available_base . " " . $base_unit_name;
                } elseif ($available_main > 0) {
                    $available_display = $available_main . " " . $unit_name;
                } else {
                    $available_display = $available_base . " " . $base_unit_name;
                }
            }

            $session->msg("d", "❌ Not enough stock for item: {$item['name']} (Requested {$qty} {$requested_unit_type}, Available {$available_display})");
            redirect('requests_form.php', false);
        }

        // Compute price
        $unit_cost = (float)$item['unit_cost'];
        $price = $unit_cost * $qty_to_deduct;

        // Insert into request_items
        $query_item = "INSERT INTO request_items (req_id, item_id, qty, unit, price, remarks) 
                       VALUES ('{$req_id}', '{$item_id}', '{$qty}', '{$requested_unit_type}', '{$price}', '{$remarks}')";
        if (!$db->query($query_item)) {
            $db->query("ROLLBACK");
            $session->msg("d", "❌ Failed to add item: " . $db->error());
            redirect('requests_form.php', false);
        }

        // Update stock
        $new_qty = $item['quantity'] - $qty_to_deduct;
        if (!$db->query("UPDATE items SET quantity = '{$new_qty}' WHERE id = '{$item_id}'")) {
            $db->query("ROLLBACK");
            $session->msg("d", "❌ Failed to update stock for {$item['name']}");
            redirect('requests_form.php', false);
        }

        // Update yearly stock
        $school_year = find_by_sql("SELECT id FROM school_years WHERE is_current = 1 LIMIT 1");
        $school_year_id = $school_year ? $school_year[0]['id'] : 0;
        $check_stock = $db->query("SELECT id FROM item_stocks_per_year 
                                   WHERE item_id = '{$item_id}' AND school_year_id = '{$school_year_id}' LIMIT 1");
        if ($db->num_rows($check_stock) > 0) {
            $db->query("UPDATE item_stocks_per_year 
                        SET stock = stock - {$qty_to_deduct}, updated_at = NOW()
                        WHERE item_id = '{$item_id}' AND school_year_id = '{$school_year_id}'");
        } else {
            $db->query("INSERT INTO item_stocks_per_year (item_id, school_year_id, stock, updated_at)
                        VALUES ('{$item_id}', '{$school_year_id}', 0, NOW())");
        }
    }

    $db->query("COMMIT");
    $session->msg("s", "✅ Request successfully created! RIS No: {$ris_no}");
    redirect('requests_form.php', false);
}

// Fetch items and process for display
$all_items = find_by_sql("SELECT * FROM items WHERE archived = 0");

// Process items for display
foreach ($all_items as &$item) {
    $item['cat_name'] = get_category_name($item['categorie_id']);

    // Get main unit from units table and base unit from base_units table
    $item['main_unit_name'] = get_unit_name($item['unit_id']);
    $item['base_unit_name'] = get_base_unit_name($item['base_unit_id']);

    // Get conversion data
    $conversion = find_by_sql("SELECT conversion_rate, from_unit_id, to_unit_id 
                              FROM unit_conversions WHERE item_id = '{$item['id']}' LIMIT 1");

    if ($conversion && count($conversion) > 0) {
        $item['conversion_rate'] = (float)$conversion[0]['conversion_rate'];
        $item['from_unit_id'] = $conversion[0]['from_unit_id'];
        $item['to_unit_id'] = $conversion[0]['to_unit_id'];

        // Use proper unit names from respective tables
        $item['main_unit_name'] = get_unit_name($item['from_unit_id']);
        $item['base_unit_name'] = get_base_unit_name($item['to_unit_id']);
    } else {
        $item['conversion_rate'] = 1;
        // Keep the original values from items table
        $item['main_unit_name'] = get_unit_name($item['unit_id']);
        $item['base_unit_name'] = get_base_unit_name($item['base_unit_id']);
    }

    // Calculate display quantity
    $item['display_quantity'] = calculate_display_quantity($item);
}
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
        color: white;
    }

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
</style>

<div class="row mb-2 align-items-center" style="border-top: 5px solid #006205; border-radius: 10px;">
    <div class="col-sm-9 mt-3">                  
        <h5 class="mb-0"><i class="nav-icon fa-solid fa-pen-to-square"></i> Request Form</h5>
    </div>
    <div class="col-sm-3 d-flex justify-content-end mt-3">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control" placeholder="Search items..." id="searchInput">
        </div>
    </div>
</div>

<div class="card">
  <div class="card-body">
    <form id="requestForm" method="post" action="">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="fw-bold text-success">Requestor's Name:</label>
          <input type="text" class="form-control-plaintext border-bottom text-success" value="<?= $user_name; ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="fw-bold text-success">Position / Office:</label>
          <input type="text" class="form-control-plaintext border-bottom text-success" value="<?= $current_user['position'] ?? ''; ?>" readonly>
        </div>
      </div>

      <label class="fw-bold text-success">Available Items</label>
      <div class="table-responsive mb-3">
        <?php if(!empty($all_items)): ?>
        <table class="table table-striped table-hover align-middle" id="itemsTable">
          <thead class="table-success">
            <tr>
              <th>Stock Card</th>
              <th>Item Name</th>
              <th class="text-center">Available Qty</th>
              <th class="text-center">Request Unit</th>
              <th class="text-center">Request Qty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($all_items as $it): ?>
            <tr <?= $it['quantity'] == 0 ? 'class="table-secondary"' : '' ?>>
              <td><?= htmlspecialchars($it['stock_card']); ?></td>         
              <td>
                <strong><?= htmlspecialchars($it['name']); ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($it['cat_name']); ?></small>
              </td>
              <td class="text-center">
                <strong><?= $it['display_quantity']; ?></strong>
              </td>
              <td class="text-center">
                <select name="unit_type[<?= (int)$it['id']; ?>]"
                        class="form-select form-select-sm p-2 w-100 unit-select"
                        style="width: 120px;"
                        data-itemid="<?= (int)$it['id']; ?>"
                        data-conversion="<?= $it['conversion_rate']; ?>"
                        data-mainunit="<?= htmlspecialchars($it['main_unit_name']); ?>"
                        data-baseunit="<?= htmlspecialchars($it['base_unit_name']); ?>">
                    <?php if ($it['conversion_rate'] > 1 && $it['main_unit_name'] !== $it['base_unit_name']): ?>
                        <option value="<?= $it['main_unit_name']; ?>"><?= $it['main_unit_name']; ?></option>
                        <option value="<?= $it['base_unit_name']; ?>"><?= $it['base_unit_name']; ?></option>
                    <?php else: ?>
                        <option value="<?= $it['main_unit_name']; ?>" selected><?= $it['main_unit_name']; ?></option>
                    <?php endif; ?>
                </select>
              </td>
              <td class="text-center">
                <input type="number" 
                       name="qty[<?= (int)$it['id']; ?>]" 
                       min="0" 
                       step="1"
                       value="0" 
                       class="form-control text-center border-success qty-input" 
                       style="max-width:120px;" 
                       <?= $it['quantity'] == 0 ? 'disabled' : '' ?>
                       data-itemid="<?= (int)$it['id']; ?>"
                       data-available="<?= (float)$it['quantity']; ?>"
                       data-conversion="<?= $it['conversion_rate']; ?>"
                       data-mainunit="<?= htmlspecialchars($it['main_unit_name']); ?>"
                       data-baseunit="<?= htmlspecialchars($it['base_unit_name']); ?>"
                       title="Available: <?= $it['display_quantity']; ?>">
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
          <label class="fw-bold text-success">Remarks (Optional)</label>
          <textarea class="form-control border-success" name="remarks" rows="2" placeholder="Add remarks here..."></textarea>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-success w-100" id="reviewBtn">
            <i class="fa-solid fa-clipboard-check"></i> Review Request
          </button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Set initial values and event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for unit selection changes
    document.querySelectorAll('.unit-select').forEach(select => {
        select.addEventListener('change', function() {
            const itemId = this.dataset.itemid;
            const conversion = parseFloat(this.dataset.conversion) || 1;
            const mainUnit = this.dataset.mainunit;
            const baseUnit = this.dataset.baseunit;
            const qtyInput = document.querySelector(`input[name="qty[${itemId}]"]`);
            const available = parseFloat(qtyInput.dataset.available) || 0;

            // Update max value based on selected unit
            if (this.value === baseUnit && conversion > 1) {
                // Requesting in base units (pieces) - max is available * conversion rate
                const availableMain = parseFloat(available) || 0;
                const fullMainUnits = Math.floor(availableMain);
                const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);
                
                let availableText = '';
                if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                    availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                } else if (fullMainUnits > 0) {
                    availableText = `${fullMainUnits} ${mainUnit}`;
                } else {
                    availableText = `${remainingBaseUnits} ${baseUnit}`;
                }
                
                qtyInput.max = Math.floor(availableMain * conversion);
                qtyInput.title = `Available: ${availableText}`;
            } else {
                // Requesting in main units (boxes) - max is available
                const availableMain = parseFloat(available) || 0;
                const fullMainUnits = Math.floor(availableMain);
                const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);
                
                let availableText = '';
                if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                    availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                } else if (fullMainUnits > 0) {
                    availableText = `${fullMainUnits} ${mainUnit}`;
                } else {
                    availableText = `${remainingBaseUnits} ${baseUnit}`;
                }
                
                qtyInput.max = availableMain;
                qtyInput.title = `Available: ${availableText}`;
            }
        });
    });

    // Trigger change event on page load to set initial max values
    document.querySelectorAll('.unit-select').forEach(select => {
        select.dispatchEvent(new Event('change'));
    });

    // Ensure whole numbers in quantity inputs
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('input', function() {
            // Remove any decimal values and ensure whole numbers
            const value = parseFloat(this.value) || 0;
            if (!Number.isInteger(value)) {
                this.value = Math.floor(value);
            }
            
            // Ensure value doesn't exceed max
            const max = parseFloat(this.max) || 0;
            if (value > max) {
                this.value = max;
            }
            
            // Ensure value is not negative
            if (value < 0) {
                this.value = 0;
            }
        });
        
        // Also handle blur event to clean up any invalid input
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value) || 0;
            if (!Number.isInteger(value) || value < 0) {
                this.value = Math.max(0, Math.floor(value));
            }
        });
    });
});

// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

// Review before submit
document.getElementById('reviewBtn').addEventListener('click', function() {
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    let receiptHTML = '<p><strong>Requestor:</strong> ' +
        document.querySelector('input[readonly]').value + '</p>';

    receiptHTML += '<table class="table table-bordered align-middle"><thead><tr><th>Item Name</th><th>Requested Qty</th><th>Unit</th><th>Available Stock</th></tr></thead><tbody>';
    let hasItem = false;

    rows.forEach(row => {
        const input = row.querySelector('input.qty-input');
        const qty = parseFloat(input.value) || 0;
        if (qty <= 0) return;

        const itemId = input.dataset.itemid;
        const itemName = row.cells[1].innerText.trim();
        const available = row.cells[2].innerText.trim();
        const unitSelect = row.querySelector('select[name^="unit_type"]');
        const selectedUnit = unitSelect.selectedOptions[0].text;

        hasItem = true;

        receiptHTML += `
            <tr>
                <td>${itemName}</td>
                <td>${qty}</td>
                <td>${selectedUnit}</td>
                <td>${available}</td>
            </tr>`;
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

// Final submit
document.getElementById('finalSubmitBtn').addEventListener('click', function() {
    document.getElementById('requestForm').submit();
});

// Quantity validation
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('input', function() {
            const max = parseFloat(this.max);
            let val = parseFloat(this.value) || 0;
            
            // Ensure whole numbers only
            if (!Number.isInteger(val)) {
                val = Math.floor(val);
                this.value = val;
            }
            
            const unitSelect = document.querySelector(`select[name="unit_type[${this.dataset.itemid}]"]`);
            const selectedUnit = unitSelect ? unitSelect.selectedOptions[0].text : '';
            const conversion = parseFloat(this.dataset.conversion) || 1;
            const mainUnit = this.dataset.mainunit;
            const baseUnit = this.dataset.baseunit;

            if (val > max) {
                let availableText = '';
                if (selectedUnit === baseUnit && conversion > 1) {
                    const availableMain = parseFloat(this.dataset.available) || 0;
                    const fullMainUnits = Math.floor(availableMain);
                    const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);
                    
                    if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                    } else if (fullMainUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit}`;
                    } else {
                        availableText = `${remainingBaseUnits} ${baseUnit}`;
                    }
                } else {
                    const availableMain = parseFloat(this.dataset.available) || 0;
                    const fullMainUnits = Math.floor(availableMain);
                    const remainingBaseUnits = Math.floor((availableMain - fullMainUnits) * conversion);
                    
                    if (fullMainUnits > 0 && remainingBaseUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit} | ${remainingBaseUnits} ${baseUnit}`;
                    } else if (fullMainUnits > 0) {
                        availableText = `${fullMainUnits} ${mainUnit}`;
                    } else {
                        availableText = `${remainingBaseUnits} ${baseUnit}`;
                    }
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Quantity Exceeded',
                    text: `Only ${availableText} available in stock.`,
                    confirmButtonColor: '#28a745',
                });

                this.value = Math.floor(max);
            } else if (val < 0) {
                this.value = 0;
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
        searching: false,
        autoWidth: true,
    });
});
</script>