<?php
$page_title = 'USER MANUAL';
require_once('includes/load.php');

// Check user level and set manual type
$current_user = current_user();
$user_level = $current_user['user_level'];
$is_admin = ($user_level == 1);
$is_user = ($user_level == 3);

// Set manual type based on user level
$manual_type = $is_admin ? 'admin' : 'user';
?>
<?php include_once('layouts/header.php'); ?>

<style>
    :root {
        --primary-green: #1e7e34;
        --secondary-green: #28a745;
        --accent-green: #34ce57;
        --light-green: #d4edda;
        --dark-green: #155724;
        --light-bg: #f8fff9;
        --card-shadow: 0 10px 30px rgba(30, 126, 52, 0.15);
        --admin-accent: #ffc107;
        --admin-dark: #856404;
        --admin-light: #fff3cd;
    }
    
    body {
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 50%, #d4edda 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }
    
    .manual-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .manual-header {
        text-align: center;
        margin-bottom: 40px;
        padding: 40px 30px;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
        color: white;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }
    
    .manual-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .manual-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 15px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        position: relative;
    }
    
    .manual-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 800px;
        margin: 0 auto;
        position: relative;
    }
    
    .user-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 0.9rem;
        margin-top: 10px;
        border: 1px solid rgba(255,255,255,0.3);
        backdrop-filter: blur(10px);
    }
    
    .section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--card-shadow);
        border-left: 5px solid var(--secondary-green);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }
    
    .section:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(30, 126, 52, 0.2);
    }
    
    .section h2 {
        color: var(--dark-green);
        font-size: 1.8rem;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--light-green);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .section h2::before {
        content: '';
        display: inline-block;
        width: 10px;
        height: 35px;
        background: var(--secondary-green);
        border-radius: 5px;
    }
    
    .section p {
        margin-bottom: 15px;
        font-size: 1.05rem;
    }
    
    .section ul {
        padding-left: 20px;
        margin-bottom: 20px;
    }
    
    .section li {
        margin-bottom: 8px;
        position: relative;
        padding-left: 25px;
    }
    
    .section li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: var(--secondary-green);
        font-weight: bold;
    }
    
    .placeholder {
        width: 100%;
        border: 2px dashed #c3e6cb;
        padding: 40px 20px;
        text-align: center;
        color: var(--dark-green);
        margin: 25px 0;
        border-radius: 10px;
        background: var(--light-bg);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .placeholder::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--secondary-green), var(--primary-green));
    }
    
    .placeholder:hover {
        border-color: var(--secondary-green);
        background: #e8f5e9;
    }
    
    .placeholder i {
        font-size: 2rem;
        margin-bottom: 15px;
        display: block;
        color: var(--secondary-green);
    }
    
    .manual-nav {
        position: sticky;
        top: 20px;
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: var(--card-shadow);
        margin-bottom: 30px;
        border-left: 4px solid var(--secondary-green);
    }
    
    .manual-nav h3 {
        color: var(--dark-green);
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--light-green);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .manual-nav h3 i {
        color: var(--secondary-green);
    }
    
    .manual-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .manual-nav li {
        margin-bottom: 8px;
    }
    
    .manual-nav a {
        display: block;
        padding: 10px 15px;
        color: var(--dark-green);
        text-decoration: none;
        border-radius: 5px;
        transition: all 0.2s ease;
        position: relative;
        padding-left: 35px;
    }
    
    .manual-nav a::before {
        content: '▶';
        position: absolute;
        left: 15px;
        color: var(--secondary-green);
        transition: transform 0.2s ease;
    }
    
    .manual-nav a:hover, .manual-nav a.active {
        background: var(--light-green);
        color: var(--dark-green);
        transform: translateX(5px);
    }
    
    .manual-nav a:hover::before {
        transform: translateX(3px);
    }
    
    .manual-layout {
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }
    
    .nav-sidebar {
        flex: 0 0 250px;
        position: sticky;
        top: 20px;
    }
    
    .content-main {
        flex: 1;
    }
    
    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        background: var(--secondary-green);
        color: white;
        border-radius: 50%;
        font-weight: bold;
        margin-right: 10px;
    }
    
    .module-highlight {
        background: linear-gradient(135deg, var(--light-green), #c3e6cb);
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        border-left: 4px solid var(--secondary-green);
    }
    
    .module-highlight h4 {
        color: var(--dark-green);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .module-highlight h4 i {
        color: var(--secondary-green);
    }
    
    .admin-only {
        background: linear-gradient(135deg, var(--admin-light), #ffeaa7);
        border-left: 4px solid var(--admin-accent);
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
    }
    
    .admin-only h5 {
        color: var(--admin-dark);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .admin-feature {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        border-left: 4px solid #2196f3;
        padding: 12px;
        border-radius: 6px;
        margin: 10px 0;
    }
    
    .admin-feature h6 {
        color: #0d47a1;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
    }
    
    /* Remove Bootstrap default spacing that causes extra space */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    @media (max-width: 768px) {
        .manual-layout {
            flex-direction: column;
        }
        
        .nav-sidebar {
            flex: 1;
            position: static;
        }
        
        .manual-header h1 {
            font-size: 2rem;
        }
        
        .section {
            padding: 20px;
        }
        
        .manual-container {
            padding: 15px;
        }
    }
</style>

<div class="manual-container">
    <div class="manual-header">
        <h1>
            <i class="fas fa-book"></i> 
            <?php echo $is_admin ? 'WBIMS Admin Manual' : 'WBIMS User Manual'; ?>
        </h1>
        <p>
            <?php echo $is_admin 
                ? 'Complete administrator guide for managing the Web-Based Inventory Management System' 
                : 'Complete guide to navigating and using the Web-Based Inventory Management System'; ?>
        </p>
        <div class="user-badge">
            <i class="fas fa-user-shield"></i> 
            <?php echo $is_admin ? 'Administrator Access' : 'User Access'; ?>
        </div>
    </div>
    
    <div class="manual-layout">
        <div class="nav-sidebar">
            <div class="manual-nav">
                <h3><i class="fas fa-bookmark"></i> Contents</h3>
                <ul>
                    <?php if ($is_admin): ?>
                        <!-- Admin Manual Contents -->
                        <li><a href="#section1" class="active">1. Admin Introduction</a></li>
                        <li><a href="#section2">2. System Administration</a></li>
                        <li><a href="#section3">3. User Management</a></li>
                        <li><a href="#section4">4. Inventory Management</a></li>
                        <li><a href="#section5">5. Request Approval</a></li>
                        <li><a href="#section6">6. Stock Management</a></li>
                        <li><a href="#section7">7. Reports & Analytics</a></li>
                        <li><a href="#section8">8. System Settings</a></li>
                        <li><a href="#section9">9. Backup & Security</a></li>
                        <li><a href="#section10">10. Troubleshooting</a></li>
                    <?php else: ?>
                        <!-- User Manual Contents -->
                        <li><a href="#section1" class="active">1. Introduction</a></li>
                        <li><a href="#section2">2. System Overview</a></li>
                        <li><a href="#section3">3. Dashboard</a></li>
                        <li><a href="#section4">4. Viewing & Canceling Requests</a></li>
                        <li><a href="#section5">5. Submit Request Form</a></li>
                        <li><a href="#section6">6. Transactions Module</a></li>
                        <li><a href="#section7">7. ICS Menu</a></li>
                        <li><a href="#section8">8. PAR Menu</a></li>
                        <li><a href="#section9">9. Return Receipt Menu</a></li>
                        <li><a href="#section10">10. Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="content-main">
            <?php if ($is_admin): ?>
                <!-- ADMIN MANUAL CONTENT -->
                <div id="section1" class="section">
                    <h2><span class="step-number">1</span> Admin Introduction</h2>
                    <p>
                        Welcome to the WBIMS Administrator Manual. This guide provides comprehensive instructions 
                        for managing all aspects of the Web-Based Inventory Management System.
                    </p>
                    <div class="module-highlight">
                        <h4><i class="fas fa-shield-alt"></i> Administrator Privileges</h4>
                        <p>As an administrator, you have full access to system configuration, user management, and inventory controls.</p>
                    </div>
                    <div class="admin-only">
                        <h5><i class="fas fa-exclamation-triangle"></i> Administrative Access Required</h5>
                        <p>All features described in this manual require administrator-level permissions.</p>
                    </div>
                </div>

                <div id="section2" class="section">
                    <h2><span class="step-number">2</span> System Administration</h2>
                    <p>
                        Manage system-wide settings and configurations to ensure optimal performance and security.
                    </p>
                    <div class="admin-feature">
                        <h6><i class="fas fa-cog"></i> Key Administrative Functions</h6>
                        <ul>
                            <li>System configuration and global settings</li>
                            <li>Database maintenance and optimization</li>
                            <li>User role and permission management</li>
                            <li>System maintenance tasks and logs</li>
                            <li>Audit trail monitoring and reporting</li>
                        </ul>
                    </div>
                    <div class="placeholder">
                        <i class="fas fa-cogs"></i>
                        [Insert Screenshot: System Administration Panel]
                    </div>
                </div>

                <div id="section3" class="section">
                    <h2><span class="step-number">3</span> User Management</h2>
                    <p>Create, modify, and manage user accounts and their permissions throughout the system.</p>
                    <div class="admin-feature">
                        <h6><i class="fas fa-users-cog"></i> User Management Features</h6>
                        <ul>
                            <li>Add new users and assign appropriate roles</li>
                            <li>Modify user permissions and access levels</li>
                            <li>Reset user passwords and security settings</li>
                            <li>Deactivate or archive inactive user accounts</li>
                            <li>Monitor user activity and access patterns</li>
                        </ul>
                    </div>
                    <div class="placeholder">
                        <i class="fas fa-users-cog"></i>
                        [Insert Screenshot: User Management Interface]
                    </div>
                </div>

                <div id="section4" class="section">
                    <h2><span class="step-number">4</span> Inventory Management</h2>
                    <p>Comprehensive control over all inventory items, categories, and stock levels.</p>
                    <div class="admin-feature">
                        <h6><i class="fas fa-boxes"></i> Inventory Control Features</h6>
                        <ul>
                            <li>Add, edit, and remove inventory items</li>
                            <li>Manage item categories and classifications</li>
                            <li>Set reorder points and stock alerts</li>
                            <li>Track item usage and consumption patterns</li>
                            <li>Manage item archiving and disposal</li>
                        </ul>
                    </div>
                    <div class="placeholder">
                        <i class="fas fa-boxes"></i>
                        [Insert Screenshot: Inventory Management Dashboard]
                    </div>
                </div>

                <div id="section5" class="section">
                    <h2><span class="step-number">5</span> Request Approval</h2>
                    <p>Review, approve, or reject inventory requests from system users with detailed oversight.</p>
                    <div class="admin-feature">
                        <h6><i class="fas fa-clipboard-check"></i> Approval Workflow</h6>
                        <ul>
                            <li>Review pending requests with complete details</li>
                            <li>Approve or reject requests with comments</li>
                            <li>Monitor request history and patterns</li>
                            <li>Set approval thresholds and limits</li>
                            <li>Manage emergency request procedures</li>
                        </ul>
                    </div>
                    <div class="placeholder">
                        <i class="fas fa-clipboard-check"></i>
                        [Insert Screenshot: Request Approval Interface]
                    </div>
                </div>

                <div id="section6" class="section">
                    <h2><span class="step-number">6</span> Stock Management</h2>
                    <p>Monitor and manage inventory stock levels, reorder points, and automated stock alerts.</p>
                    <div class="admin-feature">
                        <h6><i class="fas fa-warehouse"></i> Stock Control</h6>
                        <ul>
                            <li>Real-time stock level monitoring</li>
                            <li>Automated reorder point calculations</li>
                            <li>Stock movement tracking and reporting</li>
                            <li>Low stock alerts and notifications</li>
                            <li>Stock adjustment and correction procedures</li>
                        </ul>
                    </div>
                    <div class="placeholder">
                        <i class="fas fa-warehouse"></i>
                        [Insert Screenshot: Stock Management Dashboard]
                    </div>
                </div>

                <div id="section7" class="section">
                    <h2><span class="step-number">7</span> Reports & Analytics</h2>
                    <p>Generate comprehensive reports and analyze system data for informed decision-making.</p>
                    <div class="placeholder">
                        <i class="fas fa-chart-bar"></i>
                        [Insert Screenshot: Reports & Analytics Dashboard]
                    </div>
                </div>

                <div id="section8" class="section">
                    <h2><span class="step-number">8</span> System Settings</h2>
                    <p>Configure system-wide settings, preferences, and operational parameters.</p>
                    <div class="placeholder">
                        <i class="fas fa-sliders-h"></i>
                        [Insert Screenshot: System Settings Panel]
                    </div>
                </div>

                <div id="section9" class="section">
                    <h2><span class="step-number">9</span> Backup & Security</h2>
                    <p>Manage system backups, security protocols, and data protection measures.</p>
                    <div class="placeholder">
                        <i class="fas fa-database"></i>
                        [Insert Screenshot: Backup & Security Settings]
                    </div>
                </div>

                <div id="section10" class="section">
                    <h2><span class="step-number">10</span> Troubleshooting</h2>
                    <p>Common issues, error resolution, and system maintenance procedures.</p>
                    <div class="placeholder">
                        <i class="fas fa-tools"></i>
                        [Insert Screenshot: Troubleshooting Guide]
                    </div>
                </div>

            <?php else: ?>
                <!-- USER MANUAL CONTENT -->
                <div id="section1" class="section">
                    <h2><span class="step-number">1</span> Introduction</h2>
                    <p>
                        This User Manual provides step-by-step instructions for navigating and using 
                        the Web-Based Inventory Management System (WBIMS). Screenshots will be inserted 
                        in the placeholders provided.
                    </p>
                    <div class="module-highlight">
                        <h4><i class="fas fa-info-circle"></i> Quick Tip</h4>
                        <p>Use the navigation menu on the left to quickly jump to different sections of this manual.</p>
                    </div>
                </div>

                <div id="section2" class="section">
                    <h2><span class="step-number">2</span> System Overview</h2>
                    <p>
                        WBIMS allows users to submit requests, monitor transactions, view issued items, 
                        process returns, and manage inventory records.
                    </p>
                    <div class="module-highlight">
                        <h4><i class="fas fa-cogs"></i> System Features</h4>
                        <ul>
                            <li>Submit and track inventory requests</li>
                            <li>Monitor transaction history</li>
                            <li>View issued items and equipment</li>
                            <li>Process returns of ICS properties</li>
                            <li>Generate reports for inventory management</li>
                        </ul>
                    </div>
                </div>

                <div id="section3" class="section">
                    <h2><span class="step-number">3</span> Dashboard</h2>
                    <p>
                        The Dashboard displays important request statistics such as:
                    </p>
                    <ul>
                        <li>Total Requests</li>
                        <li>Pending Requests</li>
                        <li>Approved Requests</li>
                        <li>Completed Requests</li>
                    </ul>
                    <div class="placeholder">
                        <i class="fas fa-desktop"></i>
                        [Insert Screenshot: Dashboard Overview]
                    </div>
                </div>

                <div id="section4" class="section">
                    <h2><span class="step-number">4</span> Viewing and Canceling Requests</h2>
                    <p>Users can view the details of a request by clicking the View button.</p>
                    <div class="placeholder">
                        <i class="fas fa-eye"></i>
                        [Insert Screenshot: Request Details View]
                    </div>
                </div>

                <div id="section5" class="section">
                    <h2><span class="step-number">5</span> Submit Request Form</h2>
                    <p>
                        The Submit Request Form allows users to browse items in the inventory and submit a request.
                    </p>
                    <div class="placeholder">
                        <i class="fas fa-clipboard-list"></i>
                        [Insert Screenshot: Submit Request Form – Item List]
                    </div>
                </div>

                <div id="section6" class="section">
                    <h2><span class="step-number">6</span> Transactions Module</h2>
                    <p>
                        This page shows all request statuses, including:
                    </p>
                    <ul>
                        <li>Completed</li>
                        <li>Canceled</li>
                        <li>Approved</li>
                        <li>Pending</li>
                    </ul>
                    <div class="placeholder">
                        <i class="fas fa-exchange-alt"></i>
                        [Insert Screenshot: Transactions Page]
                    </div>
                </div>

                <div id="section7" class="section">
                    <h2><span class="step-number">7</span> ICS Menu</h2>
                    <p>
                        The ICS menu displays all issued semi-expendable items.
                    </p>
                    <div class="placeholder">
                        <i class="fas fa-boxes"></i>
                        [Insert Screenshot: ICS List]
                    </div>
                </div>

                <div id="section8" class="section">
                    <h2><span class="step-number">8</span> PAR Menu</h2>
                    <p>
                        The PAR menu displays issued equipment items.
                    </p>
                    <div class="placeholder">
                        <i class="fas fa-tools"></i>
                        [Insert Screenshot: PAR List]
                    </div>
                </div>

                <div id="section9" class="section">
                    <h2><span class="step-number">9</span> Return Receipt Menu</h2>
                    <p>
                        Shows the list of returned ICS properties.
                    </p>
                    <div class="placeholder">
                        <i class="fas fa-undo-alt"></i>
                        [Insert Screenshot: Return Receipt List]
                    </div>
                </div>

                <div id="section10" class="section">
                    <h2><span class="step-number">10</span> Logout</h2>
                    <p>The Logout button safely ends the user session.</p>
                    <div class="placeholder">
                        <i class="fas fa-sign-out-alt"></i>
                        [Insert Screenshot: Logout Button]
                    </div>
                    <div class="module-highlight">
                        <h4><i class="fas fa-shield-alt"></i> Security Note</h4>
                        <p>Always log out when you're finished using the system, especially on shared computers.</p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scrolling for navigation links
        document.querySelectorAll('.manual-nav a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                // Remove active class from all links
                document.querySelectorAll('.manual-nav a').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Add active class to clicked link
                this.classList.add('active');
                
                window.scrollTo({
                    top: targetElement.offsetTop - 20,
                    behavior: 'smooth'
                });
            });
        });
        
        // Highlight current section in navigation
        const sections = document.querySelectorAll('.section');
        const navLinks = document.querySelectorAll('.manual-nav a');
        
        window.addEventListener('scroll', function() {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (pageYOffset >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    });
</script>

<?php include_once('layouts/footer.php'); ?>