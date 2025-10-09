<?php
$page_title = 'Bulk Edit Items';
require_once('includes/load.php');
page_require_level(1);

// Get IDs from query string
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    $session->msg('d', 'No items selected.');
    redirect('items.php');
}

$ids = array_map('intval', explode(',', $_GET['ids']));
$items = [];
foreach($ids as $id) {
    $item = find_by_id('items', $id);
    if($item) $items[] = $item;
}

$categories = find_all('categories');

// Handle bulk update
if(isset($_POST['update_bulk_items'])) {
    foreach($ids as $id) {
        $name         = remove_junk($db->escape($_POST["name_$id"]));
        $fund_cluster = remove_junk($db->escape($_POST["fund_cluster_$id"]));
        $stock_card   = remove_junk($db->escape($_POST["stock_card_$id"]));
        $categorie_id = (int)$_POST["categorie_id_$id"];
        $UOM          = remove_junk($db->escape($_POST["UOM_$id"]));
        $quantity     = (int)$_POST["quantity_$id"];
        $unit_cost    = (float)$_POST["unit_cost_$id"];

        // Keep existing media_id
        $media_id = $_POST["existing_media_$id"];

        // Handle image upload per item
        if(isset($_FILES["image_$id"]) && $_FILES["image_$id"]["name"] != "") {
            $file_name = basename($_FILES["image_$id"]["name"]);
            $target_dir = "uploads/items/";
            $target_file = $target_dir . $file_name;
            $check = getimagesize($_FILES["image_$id"]["tmp_name"]);
            if($check !== false) {
                if(move_uploaded_file($_FILES["image_$id"]["tmp_name"], $target_file)) {
                    $db->query("INSERT INTO media (file_name) VALUES ('{$file_name}')");
                    $media_id = $db->insert_id();
                }
            }
        }

        $sql = "UPDATE items SET 
                    name='{$name}',
                    fund_cluster='{$fund_cluster}',
                    stock_card='{$stock_card}',
                    categorie_id='{$categorie_id}',
                    UOM='{$UOM}',
                    quantity='{$quantity}',
                    unit_cost='{$unit_cost}',
                    media_id='{$media_id}'
                WHERE id='{$id}' LIMIT 1";
        $db->query($sql);
    }
    $session->msg('s', 'Selected items updated successfully.');
    redirect('items.php');
}
?>

<?php include_once('layouts/header.php'); ?>

<div  class="card shadow-sm p-5">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <h4 class="mb-0 flex-grow-1">
      <i class="fas fa-pen-to-square"></i> Bulk Edit Semi-Expandable Properties
    </h4>
    <div class="text-muted small">
      Editing <?= count($ids); ?> item(s)
    </div>
  </div>

    <form method="post" action="" enctype="multipart/form-data">
      <div class="row">

        <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> 
      Edit the fields below. Each card represents one item.
    </div>

<?php foreach($items as $item): ?>
        <div class="card shadow sm p-5" style="border-left: 5px solid #006205; border-radius: 10px;">
            <div class="card-header text-center">
                <strong>Item ID: <?= $item['id']; ?></strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- IMAGE -->
                    <div class="col-md-4 text-center border-end">
                        <label class="fw-bold">Current Image</label><br>
                        <?php if(!empty($item['media_id'])): 
                            $media = find_by_id('media', $item['media_id']); ?>
                            <img src="uploads/items/<?php echo $media['file_name']; ?>" 
                                 style="width:150px;height:150px;object-fit:cover;" 
                                 class="img-thumbnail rounded">
                        <?php else: ?>
                            <img src="uploads/items/default.jpg" 
                                 style="width:150px;height:150px;object-fit:cover;" 
                                 class="img-thumbnail rounded">
                        <?php endif; ?>
                        <div class="form-group mt-3">
                            <label>Change Image</label>
                            <input type="file" class="form-control" name="image_<?= $item['id']; ?>" accept="image/*">
                        </div>
                        <input type="hidden" name="existing_media_<?= $item['id']; ?>" value="<?= $item['media_id']; ?>">
                    </div>

                    <!-- FORM FIELDS -->
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label>Item Name</label>
                                <input type="text" class="form-control" name="name_<?= $item['id']; ?>" value="<?= remove_junk($item['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label>Fund Cluster</label>
                                <input type="text" class="form-control" name="fund_cluster_<?= $item['id']; ?>" value="<?= remove_junk($item['fund_cluster']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label>Stock No.</label>
                                <input type="text" class="form-control" name="stock_card_<?= $item['id']; ?>" value="<?= remove_junk($item['stock_card']); ?>">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label>Category</label>
                                <select class="form-control" name="categorie_id_<?= $item['id']; ?>" required>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= (int)$cat['id']; ?>" <?= $item['categorie_id']==$cat['id'] ? 'selected' : ''; ?>>
                                            <?= remove_junk($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label>Unit of Measure</label>
                                <input type="text" class="form-control" name="UOM_<?= $item['id']; ?>" value="<?= remove_junk($item['UOM']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Quantity</label>
                                <input type="number" class="form-control" name="quantity_<?= $item['id']; ?>" value="<?= remove_junk($item['quantity']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Unit Cost</label>
                                <input type="number" step="0.01" class="form-control" name="unit_cost_<?= $item['id']; ?>" value="<?= remove_junk($item['unit_cost']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach; ?>
</div>


        <div class="text-center my-3">
            <button type="submit" name="update_bulk_items" class="btn btn-success btn-lg">Save All Changes</button>
            <a href="items.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<?php include_once('layouts/footer.php'); ?>
