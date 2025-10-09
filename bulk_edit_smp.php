<?php
$page_title = 'Edit Semi-Expandable Property';
require_once('includes/load.php');
page_require_level(1); // Only admins

// ✅ Collect multiple IDs
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    $session->msg("d", "No items selected for bulk edit.");
    redirect('smp.php');
}

$ids = explode(',', $_GET['ids']);
$ids = array_map('intval', $ids);
$id_list = implode(',', $ids);

// ✅ Fetch items
$query = "SELECT * FROM semi_exp_prop WHERE id IN ($id_list)";
$items = $db->query($query);
if (!$items || $items->num_rows == 0) {
    $session->msg("d", "No valid items found for bulk edit.");
    redirect('smp.php');
}

// ✅ Fetch categories
$semi_categories = $db->query("SELECT * FROM semicategories ORDER BY semicategory_name ASC");

// ✅ Handle bulk update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update'])) {
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $id => $data) {
            $fund_cluster     = remove_junk($db->escape($data['fund_cluster']));
            $property_no      = !empty($data['property_no']) ? "'" . $db->escape($data['property_no']) . "'" : "NULL";
            $inv_item_no      = !empty($data['inv_item_no']) ? "'" . $db->escape($data['inv_item_no']) . "'" : "NULL";
            $item_description = remove_junk($db->escape($data['item_description']));
            $unit             = remove_junk($db->escape($data['unit']));
            $unit_cost        = (float)$data['unit_cost'];
            $estimated_use    = remove_junk($db->escape($data['estimated_use']));
            $status           = remove_junk($db->escape($data['status']));
            $semicategory_id  = (int)$data['semicategory_id'];

            $query  = "UPDATE semi_exp_prop SET 
                fund_cluster='{$fund_cluster}',
                inv_item_no={$inv_item_no},
                property_no={$property_no},
                item_description='{$item_description}',
                unit='{$unit}',
                unit_cost='{$unit_cost}',
                estimated_use='{$estimated_use}',
                status='{$status}',
                semicategory_id='{$semicategory_id}',
                last_edited = NOW()
                WHERE id='{$id}'";
            $db->query($query);
        }
        $session->msg("s", "✅ Bulk update applied to selected items!");
    } else {
        $session->msg("d", "❌ No items submitted.");
    }
    redirect('smp.php', false);
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="card shadow-sm p-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <h4 class="mb-0 flex-grow-1">
      <i class="fas fa-pen-to-square"></i> Bulk Edit Semi-Expandable Properties
    </h4>
    <div class="text-muted small">
      Editing <?= count($ids); ?> item(s)
    </div>
  </div>

  <form method="POST" action="">
    <input type="hidden" name="bulk_update" value="1">

    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> 
      Edit the fields below. Each card represents one item.
    </div>

    <?php 
    $items->data_seek(0); 
    while ($row = $items->fetch_assoc()): ?>
      <div class="card shadow-sm mb-4" style="border-left: 5px solid #006205; border-radius: 10px;">
        <div class="card-body">
          <h6 class="text-success mb-3">
            <i class="fas fa-box"></i> <?= remove_junk($row['item_description']); ?> 
            <span class="badge bg-secondary"><?= $row['property_no']; ?></span>
          </h6>

          <input type="hidden" name="items[<?= $row['id']; ?>][id]" value="<?= $row['id']; ?>">

          <div class="row g-3 mb-2">
            <div class="col-md-4">
              <label class="form-label">Fund Cluster</label>
              <input type="text" class="form-control" name="items[<?= $row['id']; ?>][fund_cluster]" 
                value="<?= $row['fund_cluster']; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Inventory Item No.</label>
              <input type="text" class="form-control" name="items[<?= $row['id']; ?>][inv_item_no]" 
                value="<?= $row['inv_item_no']; ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Property No.</label>
              <input type="text" class="form-control" name="items[<?= $row['id']; ?>][property_no]" 
                value="<?= $row['property_no']; ?>">
            </div>
          </div>

          <div class="row g-3 mb-2">
            <div class="col-md-4">
              <label class="form-label">Semi-Expendable Category</label>
              <select class="form-select" name="items[<?= $row['id']; ?>][semicategory_id]" required>
                <option value="">Select Category</option>
                <?php 
                $semi_categories->data_seek(0); 
                while ($cat = $semi_categories->fetch_assoc()): ?>
                  <option value="<?= $cat['id']; ?>" <?= ($cat['id']==$row['semicategory_id'])?'selected':''; ?>>
                    <?= remove_junk($cat['semicategory_name']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Item Description</label>
              <input type="text" class="form-control" name="items[<?= $row['id']; ?>][item_description]" 
                value="<?= $row['item_description']; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit</label>
              <input type="text" class="form-control" name="items[<?= $row['id']; ?>][unit]" 
                value="<?= $row['unit']; ?>" required>
            </div>
          </div>

          <div class="row g-3 mb-2">
            <div class="col-md-4">
              <label class="form-label">Unit Cost</label>
              <input type="number" step="0.01" class="form-control" name="items[<?= $row['id']; ?>][unit_cost]" 
                value="<?= $row['unit_cost']; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Estimated Use</label>
              <input type="text" class="form-control" name="items[<?= $row['id']; ?>][estimated_use]" 
                value="<?= $row['estimated_use']; ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-select" name="items[<?= $row['id']; ?>][status]">
                <option value="available" <?= $row['status']=='available'?'selected':''; ?>>Available</option>
                <option value="issued" <?= $row['status']=='issued'?'selected':''; ?>>Issued</option>
                <option value="returned" <?= $row['status']=='returned'?'selected':''; ?>>Returned</option>
                <option value="lost" <?= $row['status']=='lost'?'selected':''; ?>>Lost</option>
                <option value="disposed" <?= $row['status']=='disposed'?'selected':''; ?>>Disposed</option>
                <option value="archived" <?= $row['status']=='archived'?'selected':''; ?>>Archived</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>

    <div class="text-end mt-3">
      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Apply Changes</button>
      <a href="smp.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
  </form>
</div>

<?php include_once('layouts/footer.php'); ?>
