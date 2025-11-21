<?php
$user = current_user();
date_default_timezone_set('Asia/Manila'); // Set your timezone 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BSU - BOKOD | IMS</title>
    <link rel="icon" type="image/png" href="uploads/other/imslogo.png">
    <link rel="shortcut icon" type="image/png" href="uploads/other/imslogo.png">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE App -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        :root {
            --primary-green: #1e7e34;
            --dark-green: #155724;
            --light-green: #28a745;
            --accent-green: #34ce57;
            --sidebar-bg: #0a2e1a;
            --sidebar-hover: #1e7e34;
            --header-bg: #ffffff;
            --text-light: #f8f9fa;
            --text-dark: #343a40;
        }

        html,
        body {
            overflow-x: hidden;
            max-width: 100%;
        }

        .wrapper {
            overflow-x: hidden !important;
            max-width: 100vw;
        }

        /* Custom Header Styles */
        .custom-header {
            background: linear-gradient(135deg, var(--header-bg) 0%, #f8f9fa 100%);
            border-bottom: 3px solid var(--primary-green);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }

        .custom-header .navbar-nav .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .custom-header .navbar-nav .nav-link:hover {
            color: var(--primary-green);
            transform: translateY(-1px);
        }

        .custom-header .date-time {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(30, 126, 52, 0.3);
        }

        .user-menu {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            border-radius: 25px;
            padding: 0.25rem 0.75rem;
            margin-left: 1rem;
            transition: all 0.3s ease;
        }

        .user-menu:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.4);
        }

        .user-menu a {
            color: white !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-menu img {
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            width: 40px;
            height: 40px;
        }

        /* Custom Sidebar Styles */
        .custom-sidebar {
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0a1a0f 100%);
            border-right: 3px solid var(--primary-green);
        }

        .brand-link {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            border-bottom: 2px solid var(--accent-green);
            padding: 1rem 0.8rem;
        }

        .brand-link img {
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .brand-text {
            color: white !important;
            font-weight: 700;
            font-size: 1.2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .nav-sidebar>.nav-item {
            margin: 0.2rem 0.8rem;
        }

        .nav-sidebar .nav-link {
            color: var(--text-light);
            border-radius: 10px;
            margin: 0.2rem 0;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--primary-green), transparent);
            transition: width 0.3s ease;
            z-index: 1;
        }

        .nav-sidebar .nav-link:hover::before {
            width: 100%;
        }

        .nav-sidebar .nav-link:hover {
            color: white;
            transform: translateX(5px);
            background: rgba(30, 126, 52, 0.2);
        }

        .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.4);
            transform: translateX(5px);
        }

        .nav-sidebar .nav-link.active::before {
            display: none;
        }

        .nav-sidebar .nav-icon {
            width: 1.5rem;
            text-align: center;
            margin-right: 0.75rem;
            font-size: 1.1rem;
            z-index: 2;
            position: relative;
        }

        .nav-header {
            color: var(--accent-green) !important;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 1.5rem 0 0.5rem 1rem;
            padding: 0.5rem;
            border-left: 3px solid var(--accent-green);
            background: rgba(52, 206, 87, 0.1);
            border-radius: 0 5px 5px 0;
        }

        /* Preloader Styles */
        .preloader {
            background: linear-gradient(135deg, #ebfbeb 0%, #d4edda 100%);
        }

        /* Content Wrapper */
        .content-wrapper {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: calc(100vh - 56px);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .custom-header .navbar-nav .nav-link {
                padding: 0.5rem 0.25rem;
                font-size: 0.9rem;
            }
            .date-time {
                font-size: 0.7rem;
                padding: 0.4rem 0.8rem;
            }
            .user-menu {
                margin-left: 0.5rem;
                padding: 0.2rem 0.5rem;
            }
        }

        /* Animation for sidebar items */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .nav-sidebar>.nav-item {
            animation: slideIn 0.3s ease forwards;
        }

        .nav-sidebar>.nav-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .nav-sidebar>.nav-item:nth-child(2) {
            animation-delay: 0.15s;
        }

        .nav-sidebar>.nav-item:nth-child(3) {
            animation-delay: 0.2s;
        }

        .nav-sidebar>.nav-item:nth-child(4) {
            animation-delay: 0.25s;
        }

        .nav-sidebar>.nav-item:nth-child(5) {
            animation-delay: 0.3s;
        }

        .nav-sidebar>.nav-item:nth-child(6) {
            animation-delay: 0.35s;
        }

        .main-sidebar {
            overflow-x: hidden !important;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <?php if ($session->isUserLoggedIn(true)): ?>
        <div class="wrapper">

            <!-- Preloader -->
            <div class="preloader flex-column justify-content-center align-items-center">
                <img src="uploads/other/imslogo.png" alt="IMSLogo" height="250" width="250">
            </div>

            <!-- Custom Header -->
            <nav class="main-header navbar navbar-expand custom-header">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                            <i class="fas fa-bars"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="#" class="nav-link">
                            <strong>SUPPLY AND PROPERTY MANAGEMENT OFFICE</strong>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <div class="date-time">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date("F j, Y"); ?>
                        </div>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <div class="date-time">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date("g:i a"); ?>
                        </div>
                    </li>
                </ul>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item user-menu">
                        <a href="edit_prof.php" aria-expanded="false">
                            <img src="uploads/users/<?php echo $user['image']; ?>" alt="user-image" class="img-circle img-inline rounded-circle">
                            <span class="d-none d-md-inline"><?php echo remove_junk(ucfirst($user['name'])); ?></span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Custom Sidebar -->
            <aside class="main-sidebar sidebar-dark-primary elevation-4 custom-sidebar">
                <!-- Brand Logo -->
                <a href="admin.php" class="brand-link">
                    <img src="uploads/other/bsulogo.png" alt="BSU Logo" class="brand-image img-circle elevation-3">
                    <span class="brand-text">BSU - BOKOD IMS</span>
                </a>

                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                            <?php if ($user['user_level'] === '1'): ?>
                                <!-- Admin Menu -->
                                <li class="nav-item">
                                    <a href="admin.php" class="nav-link">
                                        <i class="nav-icon fas fa-tachometer-alt"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>

                                <li class="nav-header">REQUEST MANAGEMENT</li>
                                <li class="nav-item">
                                    <a href="requests.php" class="nav-link">
                                        <i class="nav-icon fas fa-pen-to-square"></i>
                                        <p>Requests</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="checkout.php" class="nav-link">
                                        <i class="nav-icon fas fa-sign-out-alt"></i>
                                        <p>Item Checkout</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="admin_ics_par.php" class="nav-link">
                                        <i class="nav-icon fas fa-shopping-basket"></i>
                                        <p>ICS - PAR ISSUANCE</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="issued_properties.php" class="nav-link">
                                        <i class="fa-solid fa-right-left"></i>
                                        <p> Return & Re-issue
                                        </p>
                                    </a>
                                </li>

                                <li class="nav-header">REPORTS & ANALYTICS</li>
                                <li class="nav-item">
                                    <a href="logs.php" class="nav-link">
                                        <i class="nav-icon fas fa-exchange-alt"></i>
                                        <p>Transactions</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="stock_card.php" class="nav-link">
                                        <i class="nav-icon fas fa-clipboard-list"></i>
                                        <p>Stock & Property Card</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="rspi.php" class="nav-link">
                                        <i class="nav-icon fas fa-file-invoice"></i>
                                        <p>Registry OF SPI's</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="rsmi.php" class="nav-link">
                                        <i class="nav-icon fas fa-file-invoice"></i>
                                        <p>RSMI / RSPI</p>
                                    </a>
                                </li>
                                <!-- <li class="nav-item">
                            <a href="rpc.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>RRC-INV-SP-PPE</p>
                            </a>
                        </li> -->


                                <li class="nav-header">INVENTORY MANAGEMENT</li>
                                <li class="nav-item">
                                    <a href="items.php" class="nav-link">
                                        <i class="nav-icon fas fa-box-open"></i>
                                        <p>Inventory Items</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="smp.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-screwdriver-wrench"></i>
                                        <p>Semi-Exp Properties</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="ppe.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-building"></i>
                                        <p> Equipments</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="cat.php" class="nav-link">
                                        <i class="nav-icon fas fa-tags"></i>
                                        <p>Categories</p>
                                    </a>
                                </li>


                                <li class="nav-header">ADMINISTRATION</li>
                                <!-- <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Reports & Analytics</p>
                            </a>
                        </li> -->
                                <li class="nav-item">
                                    <a href="users_employees.php" class="nav-link">
                                        <i class="nav-icon fas fa-chart-line"></i>
                                        <p>Employee Record</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="refs.php" class="nav-link">
                                        <i class="nav-icon fas fa-tags"></i>
                                        <p>References</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="archive.php" class="nav-link">
                                        <i class="nav-icon fas fa-archive"></i>
                                        <p>Archive</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="signatories.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-signature"></i>
                                        <p>Signatories</p>
                                    </a>
                                </li>

                            <?php elseif ($user['user_level'] === '2'): ?>
                                <!-- Super Admin Menu -->
                                <li class="nav-item">
                                    <a href="super_admin.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-gauge-high"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>
                                <li class="nav-header">USER MANAGEMENT</li>

                                <li class="nav-item">
                                    <a href="users.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-users-gear"></i>
                                        <p>Users</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="emps.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-users"></i>
                                        <p>Employees</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="archive.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-box-archive"></i>
                                        <p>Archive</p>
                                    </a>
                                </li>

                            <?php elseif ($user['user_level'] === '3'): ?>
                                <!-- User Menu -->
                                <li class="nav-item">
                                    <a href="home.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-gauge-high"></i>
                                        <p> Dashboard</p>
                                    </a>
                                </li>

                                <li class="nav-header">FORMS</li>
                                <li class="nav-item">
                                    <a href="requests_form.php" class="nav-link">
                                        <i class="nav-icon fa-solid fa-pen-to-square"></i>
                                        <p> Submit Requests</p>
                                    </a>
                                </li>

                                <li class="nav-header">DOCUMENTS</li>
                                <li class="nav-item">
                                   <a href="user_logs.php" class="nav-link">
                                        <i class="nav-icon fas fa-exchange-alt"></i>
                                        <p> Transactions</p>
                                    </a>
                                </li> 
                                <li class="nav-item">
                                    <a href="ics.php" class="nav-link">
                                       <i class="fa-solid fa-file-contract"></i>
                                        <p> Inventory Custodian Slip</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="par.php" class="nav-link">
                                        <i class="nav-icon fas fa-handshake"></i>
                                        <p> Property Acknowledgement Receipt</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="rrsp.php" class="nav-link">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <p> Return Receipt</p>
                                    </a>
                                </li>
                               
                            <?php endif; ?>

                            <li class="nav-header">ACCOUNT</li>
                        <li class="nav-item">
                            <a href="user_manual.php" class="nav-link">
                                <i class="fa-solid fa-book-bookmark"></i>
                                <p>User Manual</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fa-solid fa-gear"></i>
                                <p>Settings</p>
                            </a>
                        </li>
                            <li class="nav-item">
                                <a href="logout.php" class="nav-link" id="logout">
                                    <i class="nav-icon fas fa-sign-out-alt"></i>
                                    <p> Logout</p>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <!-- Your page content will go here -->

                    <?php endif; ?>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const logoutLink = document.getElementById("logout");

                            if (logoutLink) {
                                logoutLink.addEventListener("click", function(e) {
                                    e.preventDefault();

                                    Swal.fire({
                                        title: 'Are you sure?',
                                        text: "You will be logged out.",
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#3085d6',
                                        confirmButtonText: 'Yes, logout',
                                        cancelButtonText: 'Cancel',
                                        background: 'var(--header-bg)',
                                        color: 'var(--text-dark)'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = logoutLink.getAttribute("href");
                                        }
                                    });
                                });
                            }

                            const currentPage = window.location.pathname.split('/').pop();
                            const menuLinks = document.querySelectorAll('.nav-link[href]');

                            menuLinks.forEach(link => {
                                const linkHref = link.getAttribute('href');
                                if (linkHref === currentPage) {
                                    link.classList.add('active');
                                }
                            });
                        });
                    </script>