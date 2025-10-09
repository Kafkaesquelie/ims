<?php
  $page_title = 'Reports and Analytics';
  require_once('includes/load.php');
  if (!$session->isUserLoggedIn()) {
    header("Location: admin.php");
    exit();
  }
  page_require_level(1);


?>

<?php
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$selected_dept = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$where_dept = $selected_dept > 0 ? "AND d.id = {$selected_dept}" : "";
$total_requests_filtered = count_requests_by_month_dept($selected_month, $selected_dept);


// --- Requests per department ---
$dept_requests = find_by_sql("
    SELECT d.department,
       COUNT(ri.id) AS total_requests,
       SUM(ri.qty) AS total_quantity,
       SUM(ri.qty * i.unit_cost) AS total_value
FROM requests r
JOIN request_items ri ON r.id = ri.req_id
JOIN items i ON ri.item_id = i.id
JOIN (
    SELECT id, department FROM users
    UNION ALL
    SELECT id, office AS department FROM employees
) AS u_combined ON r.requested_by = u_combined.id
JOIN departments d ON u_combined.department = d.id
WHERE DATE_FORMAT(r.date, '%Y-%m') = '{$selected_month}'
  AND r.status = 'Approved'
  {$where_dept}
GROUP BY d.id

");

// Prepare arrays for Chart.js
$departments = [];
$requests = [];
$quantities = [];
$values = [];
$total_qty = $total_value = $total_requests = 0;

foreach ($dept_requests as $row) {
    $departments[] = $row['department'];
    $requests[] = (int)$row['total_requests'];
    $quantities[] = (int)$row['total_quantity'];
    $values[] = (float)$row['total_value'];

    $total_requests += (int)$row['total_requests'];
    $total_qty += (int)$row['total_quantity'];
    $total_value += (float)$row['total_value'];
}

// --- Stock distribution by category per department ---
$stock_dist = find_by_sql("
    SELECT c.name AS category, SUM(ri.qty) AS total_quantity
  FROM request_items ri
  JOIN items i ON ri.item_id = i.id
  JOIN categories c ON i.categorie_id = c.id
  JOIN requests r ON ri.req_id = r.id
  JOIN (
      SELECT id, department FROM users
      UNION ALL
      SELECT id, office AS department FROM employees
  ) AS u_combined ON r.requested_by = u_combined.id
  WHERE r.status = 'Approved'
    AND DATE_FORMAT(r.date, '%Y-%m') = '{$selected_month}'
    " . ($selected_dept > 0 ? " AND u_combined.department = {$selected_dept}" : "") . "
  GROUP BY c.id
");


$supply_dist = find_by_sql("
    SELECT 
        CASE 
            WHEN sep.unit_cost >= 50000 THEN 'Property'
            WHEN sep.unit_cost >= 5000 THEN 'High Value Semi-Expendable'
            ELSE 'Low Value Semi-Expendable'
        END AS value_category,
        SUM(si.qty) AS total_quantity
    FROM supply_requests sr
    JOIN supply_items si ON sr.id = si.request_id
    JOIN semi_exp_prop sep ON si.supply_id = sep.id  
    JOIN (
    SELECT id, department FROM users
    UNION ALL
    SELECT id, office AS department FROM employees
    ) AS u_combined ON sr.req_id = u_combined.id
    JOIN departments d ON u_combined.department = d.id
    WHERE sr.status = 'Approved'
      AND DATE_FORMAT(sr.request_date, '%Y-%m') = '{$selected_month}'
      " . ($selected_dept > 0 ? " AND d.id = {$selected_dept}" : "") . "
    GROUP BY value_category
");

 


// Restructure data: department => [cat1=>x, cat2=>y...]
$dist_data = [];
foreach ($stock_dist as $row) {
    $dist_data[$row['category']] = (int)$row['total_quantity'];
}


$categories = [];
foreach ($stock_dist as $row) {
    if (!in_array($row['category'], $categories)) {
        $categories[] = $row['category'];
    }
}

// --- Percentages ---
// --- Overall totals (all departments, all data in selected month) ---
// --- Overall totals (all departments, all approved requests in selected month) ---
$overall = find_by_sql("
    SELECT 
        COUNT(ri.id) AS total_requests,
        SUM(ri.qty) AS total_quantity,
        SUM(ri.qty * i.unit_cost) AS total_value
    FROM requests r
    JOIN request_items ri ON r.id = ri.req_id
    JOIN items i ON ri.item_id = i.id
    WHERE r.status = 'Approved'
      AND DATE_FORMAT(r.date, '%Y-%m') = '{$selected_month}'
");

$overall_requests = (int)$overall[0]['total_requests'];
$overall_qty      = (int)$overall[0]['total_quantity'];
$overall_value    = (float)$overall[0]['total_value'];

// --- Filtered totals (by department if selected) ---
$filtered_requests = $total_requests;
$filtered_qty      = $total_qty;
$filtered_value    = $total_value;

// --- Percentages ---
// If no department selected, percentages = 100%
if ($selected_dept > 0) {
    $req_percent   = $overall_requests > 0 ? round(($filtered_requests / $overall_requests) * 100, 2) : 0;
    $qty_percent   = $overall_qty > 0 ? round(($filtered_qty / $overall_qty) * 100, 2) : 0;
    $value_percent = $overall_value > 0 ? round(($filtered_value / $overall_value) * 100, 2) : 0;
} else {
    $req_percent = $qty_percent = $value_percent = 100;
}



 
// Count total users + employees overall
// Count total users + employees overall
$total_users_overall = find_by_sql("
    SELECT COUNT(*) AS total FROM (
        SELECT id FROM users
        UNION
        SELECT id FROM employees
    ) AS combined
")[0]['total'];

// Count users + employees filtered by selected department (0 = all)
if ($selected_dept > 0) {
    // Get department name from ID
    $dept_row = find_by_sql("SELECT department FROM departments WHERE id = {$selected_dept}");
    $dept_name = $dept_row ? $dept_row[0]['department'] : '';

    $dept_users = find_by_sql("
        SELECT COUNT(*) AS total FROM (
            SELECT id FROM users WHERE department = {$selected_dept}
            UNION
            SELECT id FROM employees WHERE office = '{$dept_name}'
        ) AS combined
    ");
    $total_users_filtered = (int)$dept_users[0]['total'];
} else {
    $total_users_filtered = $total_users_overall;
}

// Calculate percentage
$user_percent = $total_users_overall > 0 
    ? round(($total_users_filtered / $total_users_overall) * 100, 2) 
    : 0;

?>



<?php include_once('layouts/header.php'); ?>  




<div class="row">
          <div class="col-md-12">
            <div class="card chart-card">
              <div class="card-header">
                <h5 class="card-title"> Recap Report (per department)</h5>

              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                    <i class="fa-solid fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-lte-dismiss="card-remove">
                    <i class="fa-solid fa-times"></i>
                </button>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="row">
                  <div class="col-md-8">
                    <p class="text-center">
                     <form method="GET" class="form-inline mb-3">
                        <label for="month" class="mr-2"><b>Select Month:</b></label>
                        <input type="month" id="month" name="month" class="form-control mr-2"
                                value="<?php echo $selected_month; ?>">
                                <select name="department" class="form-control mr-2">
                                    <option value="">All Departments</option>
                                    <?php
                                    $all_depts = find_by_sql("SELECT * FROM departments");
                                    foreach ($all_depts as $d):
                                    ?>
                                        <option value="<?php echo $d['id']; ?>" 
                                        <?php echo (isset($_GET['department']) && $_GET['department'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo $d['department']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </select>

                        <button type="submit" class="btn btn-primary">View</button>
                        </form>

                    </p>
                       <div class="chart" style="position: relative; width: 100%; height: 400px;">
                        <canvas id="stockChart"></canvas>
                    </div>

                    <!-- /.chart-responsive -->
                  </div>
                  <!-- /.col -->
                <div class="col-md-4">
                        <p class="text-center">
                            <strong>STOCK DISTRIBUTION BY CATEGORY</strong>
                        </p>
                    <?php
                        $total_qty_all = array_sum($dist_data); // sum of all categories

                        foreach ($dist_data as $category => $qty) {
                            // Calculate percentage
                            $percent = $total_qty_all > 0 ? round(($qty / $total_qty_all) * 100) : 0;

                            echo "<div class='progress-group'>
                                    <span>{$category}</span>
                                    <span class='float-right'>{$qty}</span>
                                    <div class='progress progress-sm'>
                                        <div class='progress-bar bg-success' style='width: {$percent}%' role='progressbar' aria-valuenow='{$percent}' aria-valuemin='0' aria-valuemax='100'></div>
                                    </div>
                                </div>";
                        }
                        ?>

                        <!-- NEW: Supply Requests Classification -->
                <p class="text-center me-4"><strong>SUPPLY REQUESTS (by Value Category)</strong></p>
                <?php
                $total_supply_qty = 0;
                foreach ($supply_dist as $row) {
                    $total_supply_qty += (int)$row['total_quantity'];
                }
                foreach ($supply_dist as $row) {
                    $value_category = $row['value_category'];
                    $qty = (int)$row['total_quantity'];
                    $percent = $total_supply_qty > 0 ? round(($qty / $total_supply_qty) * 100) : 0;

                    // assign colors
                    $bar_class = "bg-primary";
                    if ($value_category == "Property") $bar_class = "bg-danger";
                    elseif ($value_category == "High Value Semi-Expendable") $bar_class = "bg-warning";
                    elseif ($value_category == "Low Value Semi-Expendable") $bar_class = "bg-info";

                    echo "<div class='progress-group'>
                            <span>{$value_category}</span>
                            <span class='float-right'>{$qty}</span>
                            <div class='progress progress-sm'>
                              <div class='progress-bar {$bar_class}' style='width: {$percent}%' 
                                  role='progressbar' aria-valuenow='{$percent}' aria-valuemin='0' aria-valuemax='100'></div>
                            </div>
                          </div>";
                }
                ?>

                        </div>
                  <!-- /.col -->
                </div>
                
                <!-- /.row -->
              </div>
              <!-- ./card-body -->
              <div class="card-footer text-center">
                <div class="row">
                  <div class="col-sm-3 col-6">
                    <div class="description-block border-right">
                      <span class="description-percentage text-warning"><i class="fas fa-caret-up"></i><?php echo $qty_percent; ?>%</span>
                      <h5 class="description-header" ><strong><?php echo $total_qty; ?></strong></h5>
                      <span class="description-text">TOTAL QTY OF ITEMS</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6">
                    <div class="description-block border-right">
                      <span class="description-percentage text-success"><i class="fas fa-caret-left"></i> <?php echo $value_percent; ?>%</span>
                      <h5 class="description-header"><strong>₱<?php echo number_format($total_value, 2); ?></strong></h5>
                      <span class="description-text">TOTAL INVENTORY VALUE</span>
                    </div>
                    <!-- /.description-block -->
                  </div>
                  <div class="col-sm-3 col-6">
                    <div class="description-block border-right">
                      <span class="description-percentage text-danger"><i class="fas fa-caret-down"></i> <?php echo $req_percent; ?>%</span>
                      <h5 class="description-header" ><strong><?php echo $total_requests_filtered; ?></strong></h5>
                      <span class="description-text">TOTAL REQUEST</span>
                    </div>
                    <!-- /.description-block -->
                    </div>
                  <!-- /.col -->
                  <div class="col-sm-3 col-6">
                    <div class="description-block">
                      <span class="description-percentage text-primary"><i class="fas fa-caret-up"></i> <?php echo $user_percent; ?>%</span>
                      <h5 class="description-header"><strong><?php echo $total_users_filtered; ?></strong></h5>
                      <span class="description-text">TOTAL USERS & EMPLOYEES</span>
                    </div>
                  </div>
                  <!-- /.col -->
                </div>
                <!-- /.row -->
              </div>
              <!-- /.card-footer -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->

        
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('stockChart').getContext('2d');
const stockChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($departments); ?>,
    datasets: [
      {
        label: 'Requests',
        data: <?php echo json_encode($requests); ?>,
        backgroundColor: 'rgba(255, 0, 0, 0.7)',
        yAxisID: 'y1',
      },
      {
        label: 'Total Quantity',
        data: <?php echo json_encode($quantities); ?>,
        backgroundColor: 'rgba(232, 166, 0, 1)',
        yAxisID: 'y1',
      },
      {
        label: 'Total Value',
        data: <?php echo json_encode($values); ?>,
        backgroundColor: 'rgba(0, 154, 8, 0.7)',
        yAxisID: 'y2',
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      title: {
        display: true,
        text: 'Requests per Department (<?php echo $selected_month; ?>)'
      }
    },
    scales: {
      y1: {
        type: 'linear',
        position: 'left',
        beginAtZero: true,
        title: { display: true, text: 'Requests / Quantity' }
      },
      y2: {
        type: 'linear',
        position: 'right',
        beginAtZero: true,
        title: { display: true, text: 'Total Value (₱)' },
        grid: { drawOnChartArea: false } // prevent grid overlap
      }
    }
  }
});

</script>



<?php include_once('layouts/footer.php'); ?>
