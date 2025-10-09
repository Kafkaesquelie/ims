<?php
$page_title = 'Add Item';
require_once('includes/load.php');
   page_require_level(1);



if (isset($_POST['add_item'])) {
    if (empty($errors)) {
        $fund_cluster   = $db->escape($_POST['fund_cluster']);
        $stock_card   = $db->escape($_POST['stock_card']);     
        $name         = $db->escape($_POST['name']);
        $quantity     = (int) $db->escape($_POST['quantity']);
        $UOM          = $db->escape($_POST['UOM']);
        $unit_cost    = $db->escape($_POST['unit_cost']);
        $categorie_id = (int) $db->escape($_POST['categorie_id']);
        $desc         = $db->escape($_POST['description']);
        // $media_id     = !empty($_POST['media_id']) ? (int) $db->escape($_POST['media_id']) : 0;

        // Default: keep existing media_id
        // Default: no image
        $media_id = 0;

        // Handle image upload
        if (isset($_FILES['item_image']) && $_FILES['item_image']['name'] != "") {
            $file_name = basename($_FILES['item_image']['name']);
            $target_dir = "uploads/items/";
            $target_file = $target_dir . $file_name;
            $check = getimagesize($_FILES["item_image"]["tmp_name"]);

            if ($check !== false) {
                if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
                    $db->query("INSERT INTO media (file_name) VALUES ('{$file_name}')");
                    $media_id = $db->insert_id();
                } else {
                    $session->msg('d', 'Failed to upload image.');
                    redirect('add_item.php');
                }
            } else {
                $session->msg('d', 'File is not an image.');
                redirect('add_item.php');
            }
        } else {
            // ✅ If no file uploaded, use no_image.png
            $default_file = 'no_image.png';
            $db->query("INSERT INTO media (file_name) VALUES ('{$default_file}')");
            $media_id = $db->insert_id();
        }

        // Check for duplicate Name
        $check_name_sql = "SELECT id FROM items WHERE name = '{$name}' LIMIT 1";
        $check_name_result = $db->query($check_name_sql);

        if ($db->num_rows($check_name_result) > 0) {
            $_SESSION['form_data'] = $_POST;
            $session->msg('d', "Item Name already exists.");
            redirect('add_item.php', false);
        }   
        // Check for duplicate
        $check_sql = "SELECT id FROM items WHERE stock_card = '{$stock_card}' LIMIT 1";
        $check_result = $db->query($check_sql);

        if ($db->num_rows($check_result) > 0) {
            $_SESSION['form_data'] = $_POST;
            $session->msg('d', "Stock Card <b>{$stock_card}</b> already exists.");
            redirect('add_item.php', false);
        }

        // Insert
        $sql  = "INSERT INTO items (fund_cluster,stock_card, name, quantity,UOM, unit_cost, categorie_id, description, media_id,date_added) VALUES ";
        $sql .= "('{$fund_cluster}','{$stock_card}','{$name}','{$quantity}', '{$UOM}' ,'{$unit_cost}','{$categorie_id}','{$desc}','{$media_id}',NOW())";

        if ($db->query($sql)) {
            unset($_SESSION['form_data']);
            $session->msg('s', "Item added successfully.");
            redirect('items.php', false);
        } else {
            $_SESSION['form_data'] = $_POST;
            $session->msg('d', 'Failed to add item!');
            redirect('add_item.php', false);
        }
    } else {
        $_SESSION['form_data'] = $_POST;
        $session->msg("d", $errors);
        redirect('add_item.php', false);
    }
}

$categories = find_all('categories');
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);

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

<!--begin::Row-->
    <div class="row mb-3">
        <div class="col-sm-6">
            <!-- <h3 class="mb-0"> <i class="fa-solid fa-plus"></i> Add Item</h3> -->
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                   Manage Inventory
                </li>
                 <li class="breadcrumb-item active" aria-current="page">
                   Add Item
                </li>
            </ol>
        </div>
    </div>
    <!--end::Row-->

<div class="row mb-3">
    <div class="col-sm-6">
        <!-- <h3 class="mb-0">Add Item</h3> -->
    </div>
    <div class="col-sm-6 text-right">
        <a href="items.php" class="btn btn-secondary">← Back to Items List</a>
    </div>
</div>



<div class="card mb-3">
    <div class="card-header" style=" border-top: 5px solid #28a745; border-radius: 10px;">
        <h3 class="card-title"> <i class="fas fa-plus-circle me-1"></i> New Item Form</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="add_item.php" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label for="fund_cluster" class="form-label">Fund Cluster</label>
                    <input type="text" name="fund_cluster" id="fund_cluster" class="form-control" 
                        value="<?php echo isset($form_data['fund_cluster']) ? $form_data['fund_cluster'] : ''; ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="name" class="form-label">Item Name</label>
                    <input type="text" name="name" id="name" class="form-control" 
                        value="<?php echo isset($form_data['name']) ? $form_data['name'] : ''; ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="stock_card" class="form-label">Stock No.</label>
                    <input type="text" name="stock_card" id="stock_card" class="form-control" 
                        value="<?php echo isset($form_data['stock_card']) ? $form_data['stock_card'] : ''; ?>" placeholder="e.g. 010" min="0">
                </div>
                <!-- <div class="col-md-2 mb-3">
                    <label for="inv_item_no" class="form-label">Inventory Item No.</label>
                     <input type="text" name="inv_item_no" id="inv_item_no" class="form-control" 
                    value="<?php echo isset($form_data['inv_item_no']) ? $form_data['inv_item_no'] : ''; ?>" placeholder="e.g GAA-255-001/6">
                </div>
                <div class="col-md-2 mb-3">
                 <label for="property_no" class="form-label">Property No.</label>
                    <input type="text" name="property_no" id="property_no" class="form-control" 
                        value="<?php echo isset($form_data['property_no']) ? $form_data['property_no'] : ''; ?>" placeholder="e.g. H1-24-10-0094">
                </div> -->
                <div class="col-md-3 mb-3">
                    <label for="categorie_id" class="form-label">Category</label>
                    <select name="categorie_id" id="categorie_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" data-name="<?php echo remove_junk($cat['name']); ?>"
                                <?php echo (isset($form_data['categorie_id']) && $form_data['categorie_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo remove_junk($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>
                <div class="col-md-3 mb-3">
                    <label for="UOM" class="form-label">Unit of Measure</label>
                    <input type="text" name="UOM" id="UOM" class="form-control" 
                        value="<?php echo isset($form_data['UOM']) ? $form_data['UOM'] : ''; ?>" placeholder="e.g. rim,pc,pcs" required>
                </div>
                 <div class="col-md-3 mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" 
                        value="<?php echo isset($form_data['quantity']) ? $form_data['quantity'] : ''; ?>" min="0" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="unit_cost" class="form-label">Unit Cost</label>
                    <input type="number" step="0.01" name="unit_cost" id="unit_cost" class="form-control"  
                        value="<?php echo isset($form_data['unit_cost']) ? $form_data['unit_cost'] : ''; ?>" min="0" required>
                </div>
            </div>

            <div class="row">
               
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?php 
                        echo isset($form_data['description']) ? $form_data['description'] : ''; ?></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="item_image" class="form-label">Item Image</label>
                    <input type="file" name="item_image" id="item_image" class="form-control">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" name="add_item" class="btn btn-success w-100 me-8">Add Item</button>
                    <button type="reset" class="btn btn-secondary w-100 ml-3">Clear</button>
                </div>
            </div>
        </form>
    </div>
</div>



<?php include_once('layouts/footer.php'); ?>
