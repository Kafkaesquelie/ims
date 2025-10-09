
  

  <!-- Sidebar -->
  <!-- <div class="sidebar"> -->
    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <!-- Always visible -->
        <li class="nav-item">
          <a href="admin.php" class="nav-link">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- Admin only -->
        <?php if ($user['user_level'] === '1'): ?>
          <li class="nav-item">
            <a href="items.php" class="nav-link">
              <i class="nav-icon fas fa-box-open"></i>
              <p>Inventory Items</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="smp.php" class="nav-link">
              <i class="nav-icon fas fa-boxes"></i>
              <p>Semi-Exp Property</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="locations.php" class="nav-link">
              <i class="nav-icon fas fa-map-marker-alt"></i>
              <p>Locations/Placement</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="cat.php" class="nav-link">
              <i class="nav-icon fas fa-tags"></i>
              <p>Categories</p>
            </a>
          </li>

          <li class="nav-header">UNIVERSITY FORMS</li>
          <li class="nav-item">
            <a href="checkout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Item Checkout</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="admin_sreq.php" class="nav-link">
              <i class="nav-icon fas fa-shopping-basket"></i>
              <p>Supply Request</p>
            </a>
          </li>

          <li class="nav-header">REPORTS</li>
          <li class="nav-item">
            <a href="requests.php" class="nav-link">
              <i class="nav-icon fas fa-pen-to-square"></i>
              <p>Requests</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="stock_card.php" class="nav-link">
              <i class="nav-icon fas fa-clipboard-list"></i>
              <p>Stock Card</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="rsmi.php" class="nav-link">
              <i class="nav-icon fas fa-file-invoice"></i>
              <p>RSMI</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="rspi.php" class="nav-link">
              <i class="nav-icon fas fa-file-invoice"></i>
              <p>RSPI</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="logs.php" class="nav-link">
              <i class="nav-icon fas fa-chart-bar"></i>
              <p>Transactions</p>
            </a>
          </li>

          <li class="nav-header">ADMINISTRATION</li>
          <li class="nav-item">
            <a href="reps.php" class="nav-link">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Reports & Analytics</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="archive.php" class="nav-link">
              <i class="nav-icon fas fa-archive"></i>
              <p>Archive</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- Common logout for all -->
        <li class="nav-item">
          <a href="logout.php" class="nav-link" id="logout">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
          </a>
        </li>

      </ul>
    </nav>
  <!-- </div> -->
  <!-- /.sidebar -->
