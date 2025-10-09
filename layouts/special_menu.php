   <!--begin::Sidebar-->
   
        <aside class="app-sidebar custom-sidebar shadow" data-bs-theme="dark">
            
            <!--begin::Sidebar Brand-->
            <div class="sidebar-brand">
                <!--begin::Brand Link-->
                <a href="index.html" class="brand-link">
                    <!--begin::Brand Image-->
                    <img src="uploads/other/bsulogo.png" alt="BSULogo" class="brand-image opacity-100 shadow">
                <strong><span class="brand-text">BSU - BOKOD IMS</span></strong>
                    <!--end::Brand Text-->
                </a>
                <!--end::Brand Link-->
            </div>
            <!--end::Sidebar Brand-->
            <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper">
                <nav>
                    <!--begin::Sidebar Menu-->
                
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        <li class="nav-item menu-open">
                            <!-- <a href="javascript:;" class="nav-link active"> -->                            
                                <a href="super_admin.php" class="nav-link  " >
                                        <i class="nav-icon fa-solid fa-gauge-high" ></i>
                                        <p>Dashboard</p>
                                </a>
                            <!-- </a> -->
                        </li>                   
                          <li class="nav-item ">
                            <a href="users.php" class="nav-link">
                                <i class="nav-icon fa-solid fa-users"></i>
                                <p>
                                    Users                               
                                </p>
                            </a>
                            
                        </li>
                        <li class="nav-item ">
                            <a href="emps.php" class="nav-link">
                                <i class="nav-icon fa-solid fa-users"></i>
                                <p>
                                    Employees                                  
                                </p>
                            </a>
                            
                        </li>
                          <li class="nav-item">
                            <a href="it_archive.php" class="nav-link">
                                <i class="nav-icon fa-solid fa-box-archive"></i>
                                <p>
                                    Archive
                                </p> 
                            </a>
                        </li>     
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link" id="logout" >
                                <i class="nav-icon fa-solid fa-arrow-right-to-bracket"></i>
                                <p>
                                    Logout
                                    
                                </p>
                            </a>
                            
                        </li>

                    </ul>
                    <!--end::Sidebar Menu-->
                </nav>
            </div>
            <!--end::Sidebar Wrapper-->
        </aside>
        <!--end::Sidebar-->


<style>
  /* Default icon color */
.app-sidebar .nav-icon {
    color: rgba(255, 255, 255, 1) !important;
    font-size 18px;
    margin-bottom: 4px;
    transition: color 0.3s;
}

/* When the link is active */
.app-sidebar .nav-link.active .nav-icon {
    color: #00DF72 !important; /* change to any color you like */
}

.custom-sidebar {
  background-color: #19202aff !important; /* any HEX, RGB, or gradient */
  color: #fff; /* make text readable */
}

</style>
<script>
  // Get current page file name
  const currentPage = window.location.pathname.split("/").pop();

  // Select all sidebar links
  const links = document.querySelectorAll(".app-sidebar .nav-link");

  links.forEach(link => {
    const linkPage = link.getAttribute("href");

    if (linkPage === currentPage) {
      link.classList.add("active");
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('logout').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent default link click

    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of the system!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6', // Blue confirm
        cancelButtonColor: '#d33',     // Red cancel
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to logout page
            window.location.href = 'logout.php';
        }
    });
});
</script>
