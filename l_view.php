<?php
$page_title = 'View Request';
require_once('includes/load.php');
page_require_level(1);

$request_id = (int)$_GET['id'];

// Fetch request info
$request = find_by_id('requests', $request_id);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('requests.php');
}

// Get requestor name
$user = find_by_id('users', $request['requested_by']);
$requestor_name = $user ? $user['name'] : 'Unknown';

// Fetch requested items
$items = find_request_items($request_id);

// Current logged-in user (for approved by/issued by)
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";

// Dark mode flag
$is_dark = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'];
?>
<?php include_once('layouts/header.php'); ?>


<div class="container-fluid my-4">
  <div class="row">
    <div class="col-md-10 mx-auto">
      <div class="card shadow-sm border-light 
                  <?php echo $is_dark ? 'bg-dark text-light' : 'bg-white text-dark'; ?>">
        <div class="card-body">

          <!-- RIS Form Header -->
          <h4 class="text-center mb-3">REQUISITION AND ISSUE SLIP</h4>
          <div class="d-flex justify-content-between mb-2">
            <div><strong>Entity Name:</strong> BENGUET STATE UNIVERSITY - BOKOD CAMPUS</div>
            <div><strong>Fund Cluster:</strong>__________ </div>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <div><strong>Responsibility Center Code:</strong> __________</div>
            <div><strong>RIS No:</strong> <?php echo $request['ris_no']; ?></div>
          </div>

          <!-- Requested Items Table -->
          <div class="table-responsive">
            <table class="table table-sm text-center align-middle 
                          <?php echo $is_dark ? 'table-dark table-bordered' : 'table-bordered'; ?>">
              <thead class="<?php echo $is_dark ? 'table-secondary text-dark' : 'table-light'; ?>">
                <tr>
                  <th>Stock No.</th>
                  <th>Unit</th>
                  <th>Description</th>
                  <th>Quantity</th>
                  <th>Issue Qty</th>
                  <th>Remarks</th>
                </tr>
              
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td>0<?php echo (int)$item['stock_card']; ?></td>
                  <td><?php echo remove_junk($item['unit_name']); ?></td>
                  <td><?php echo remove_junk($item['item_name']); ?></td>
                  <td><?php echo (int)$item['qty']; ?></td>
                  <td><?php echo (int)$item['qty']; ?></td>
                  <td><?php echo remove_junk($item['remarks']); ?></td>
                </tr>
                <?php endforeach; ?>

                <!-- Add at least 3 blank rows -->
                <?php for ($i = 0; $i < 3; $i++): ?>
                <tr>
                  <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>

          <!-- Signatures / Status -->
          <div class="row text-center mt-4">
            <div class="col">
              <p>Requested by:</p>
              <hr class="border-secondary">
              <p><b><?php echo remove_junk($requestor_name); ?></b></p>
              <small>Signature / Printed Name</small>
            </div>
            <div class="col">
              <p>Approved by:</p>
              <hr class="border-secondary">
              <p><b><?php echo $current_user_name; ?></b></p>
              <small>Signature / Printed Name</small>
            </div>
            <div class="col">
              <p>Issued by:</p>
              <hr class="border-secondary">
              <p><b><?php echo $current_user_name; ?></b></p>
              <small>Signature / Printed Name</small>
            </div>
            <div class="col">
              <p>Received by:</p>
              <hr class="border-secondary">
              <p>___________________</p>
              <small>Signature / Printed Name</small>
            </div>
          </div>

          <!-- Status -->
          <div class="text-center mt-4">
            <span class="badge bg-success fs-5 px-4 py-2">APPROVED</span>
          </div>

          <!-- Back Button -->
          <div class="text-center mt-4">
            <a href="logs.php" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Back
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
