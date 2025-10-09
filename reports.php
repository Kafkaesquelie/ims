<?php
  $page_title = 'Reports Page';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   page_require_level(1);
   
?>
<?php include_once('layouts/header.php'); ?>  

 <!--begin::Row-->
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h3 class="mb-0"> Inventory Reports</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Inventory Reports
                                </li>
                            </ol>
                        </div>
                    </div>
                    <!--end::Row--> 
<?php include_once('layouts/footer.php'); ?>
