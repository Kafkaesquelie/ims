<?php
$page_title = 'View Request';
require_once('includes/load.php');
page_require_level(1);

// ✅ Handle inline RIS update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['ris_no'])) {
    $id = (int)$_POST['id'];
    $input_ris = remove_junk($db->escape($_POST['ris_no']));

    // Ensure format YYYY-MM-XXXX
    if (!preg_match('/^\d{4}-\d{2}-\d{4}$/', $input_ris)) {
        // Auto-prepend year-month if user just typed '0001' etc.
        $ris_no = date("Y-m") . '-' . str_pad($input_ris, 4, '0', STR_PAD_LEFT);
    } else {
        $ris_no = $input_ris;
    }

    $sql = "UPDATE requests SET ris_no = '{$ris_no}' WHERE id = '{$id}'";
    if ($db->query($sql)) {
        echo "success";
    } else {
        echo "error: " . $db->con->error;
    }
    exit;
}


$request_id = (int)$_GET['id'];

// Fetch request info
$request = find_by_id('requests', $request_id);
if (!$request) {
    $session->msg("d", "Request not found.");
    redirect('requests.php');
}

// Get requestor name
// Try users table first
$user = find_by_id('users', $request['requested_by']);
if ($user) {
    $requestor_name = $user['name'];
} else {
    // Check employees table
    $employee = find_by_id('employees', $request['requested_by']);
    if ($employee) {
        $first = remove_junk($employee['first_name']);
        $middle = remove_junk($employee['middle_name']);
        $last = remove_junk($employee['last_name']);
        // Concatenate all parts, ignore empty middle name
        $requestor_name = trim("$first $middle $last");
    } else {
        $requestor_name = 'Unknown';
    }
}

// Fetch requested items
$items = find_request_items($request_id); 

// Define number of copies
$copies = 3;

// Current logged-in user
$current_user = current_user();
$current_user_name = $current_user ? remove_junk($current_user['name']) : "System User";

// Generate RIS format if empty
$ris_no_display = !empty($request['ris_no'])
    ? $request['ris_no']
    : date("Y-m") . '-0000';

?>

<?php include_once('layouts/header.php'); ?>

<style>
@media print {
    /* Hide everything first */
    body * {
        visibility: hidden;
    }

    /* Show only the print area */
    .print-area, .print-area * {
        visibility: visible;
    }

    /* Hide floating buttons, header, footer */
    .fab-container,
    header,
    footer {
        display: none !important;
    }

    .print-area {
        position: static;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .form-container {
        page-break-inside: avoid;
        margin-bottom: 5px;
        padding: 5px 8px;
        font-size: 10px;
    }

    table {
        font-size: 10px;
    }

    h4 {
        font-size: 12px;
    }

    @page {
        size: legal portrait;
        margin: 0.3in; /* reduce margins to fit all copies */
    }
}
</style>


<div class="container-fluid my-4">
    <div class="row">
        <div class="col-md-12">
            <div class="print-area">             
                <div class="form-container border p-2 mb-2 shadow-sm 
                    <?php echo isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] ? 'bg-dark text-light' : 'bg-white text-dark'; ?>">

                    <h4 class="text-center mb-1">REQUISITION AND ISSUE SLIP</h4>

                    <div class="d-flex justify-content-between mb-1">
                        <div><strong>Entity Name:</strong> BENGUET STATE UNIVERSITY - BOKOD CAMPUS</div>
                        <div><strong>Fund Cluster:</strong> __________</div>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <div><strong>Responsibility Center Code:</strong> __________</div>
                      <div style="display:inline-flex; align-items:center; gap:3px;">
    <span><?= date("Y-m") ?>-</span>
    <input 
        type="text" 
        id="risSeqInput" 
        maxlength="4" 
        value="<?= substr($ris_no_display, -4) ?>" 
        onblur="updateRIS(<?= $request_id ?>, this.value)" 
        style="width:50px; border:none; border-bottom:1px solid #000; text-align:center; outline:none;"
    />
</div>



                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm text-center align-middle table-bordered">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Stock No.</th>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th colspan="2">Stock Available?</th>
                                    <th>Issue Qty</th>
                                    <th>Remarks</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>Yes</th>
                                    <th>No</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): 
                                    $stock_available = isset($item['stock']) ? ($item['qty'] <= $item['stock']) : true;
                                ?>
                                <tr>
                                    <td>0<?php echo (int)$item['stock_card']; ?></td>
                                    <td><?php echo remove_junk($item['UOM']); ?></td>
                                    <td><?php echo remove_junk($item['item_name']); ?></td>
                                    <td><?php echo (int)$item['qty']; ?></td>
                                    <td><?php echo $stock_available ? '✔' : ''; ?></td>
                                    <td><?php echo !$stock_available ? '✔' : ''; ?></td>
                                    <td><?php echo (int)$item['qty']; ?></td>
                                    <td><?php echo remove_junk($item['remarks']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php for ($i = 0; $i <3 ; $i++): ?>
                                <tr>
                                    <td>&nbsp;</td><td></td><td></td><td></td>
                                    <td></td><td></td><td></td><td></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row text-center mt-2">
                        <div class="col">
                            <p>Requested by:</p>
                            <hr>
                            <p><b><?php echo remove_junk($requestor_name); ?></b></p>
                        </div>
                        <div class="col">
                            <p>Approved by:</p>
                            <hr>
                            <p><b><?php echo $current_user_name; ?></b></p>
                        </div>
                        <div class="col">
                            <p>Issued by:</p>
                            <hr>
                            <p><b><?php echo $current_user_name; ?></b></p>
                        </div>
                        <div class="col">
                            <p>Received by:</p>
                            <hr>
                            <p>___________________</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


          <!-- Floating Action Buttons -->
<div class="fab-container">
    <a href="requests.php" class="btn btn-secondary fab-btn">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <?php if(strtolower($request['status']) !== 'approved'): ?>
    <a href="approve_req.php?id=<?php echo (int)$request['id']; ?>" 
       class="btn btn-success approve-btn fab-btn">
       <i class="fas fa-check"></i> Approve
    </a>
    <?php endif; ?>
      <button class="btn btn-success" style=" border-radius: 50px" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
</div>

<style>
/* Floating Action Buttons container */
.fab-container {
    position: fixed;
    bottom: 60px;
    right: 20px;
    display: flex;
    flex-direction: row;   /* now side by side */
    gap: 10px;
    z-index: 1050;
    flex-wrap: wrap;       /* if too narrow, buttons wrap */
}

/* Styling for each button */
.fab-btn {
    padding: 12px 20px;
    border-radius: 50px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    transition: transform 0.2s ease-in-out;
    white-space: nowrap; /* keep text from breaking */
}

.fab-btn:hover {
    transform: scale(1.05);
}
#risSeqInput {
    border: none;
    border-bottom: 1px solid #000;
    outline: none;
    width: 50px;
    text-align: center;
    background: transparent;
}
#risSeqInput:focus {
    border-bottom: 1px solid #007bff;
}

</style>


        </div>
    </div>
</div>


<script>
function updateRIS(requestId, newSeq) {
    // Ensure only digits and pad to 4 characters
    newSeq = newSeq.replace(/\D/g, '').padStart(4, '0');
    const yearMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
    const fullRIS = `${yearMonth}-${newSeq}`;

    fetch('r_view.php?id=' + requestId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${requestId}&ris_no=${encodeURIComponent(fullRIS)}`
    })
    .then(res => res.text())
    .then(data => console.log('RIS Updated:', data))
    .catch(err => console.error('Error updating RIS:', err));
}
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.approve-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            
            // Get current RIS number from the editable span
            const risSpan = document.querySelector('[contenteditable][onblur^="updateRIS"]');
            const risValue = risSpan ? risSpan.innerText.trim() : '';

            // Check if RIS number is complete (format: YYYY-MM-XXXX)
            const risPattern = /^\d{4}-\d{2}-\d{4}$/;

            if (!risPattern.test(risValue)) {
                Swal.fire({
                    title: 'Incomplete RIS Number',
                    text: 'Please complete the RIS number in the format YYYY-MM-XXXX before approving.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
                return; // stop approval
            }

            // Confirm approval
            Swal.fire({
                title: 'Are you sure?',
                text: "This request will be approved.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
});
</script>





<?php include_once('layouts/footer.php'); ?>  