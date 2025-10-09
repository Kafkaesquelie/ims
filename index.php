<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to IMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <style>
    :root {
      --primary: #025621;
      --primary-light: #2c5035ff;
      --secondary: #f3ff48;
      --white: #ffffff;
      --dark: #023b08;
      --black: #000000;
      --light-bg: rgba(255, 255, 255, 0.15);
      --card-bg: rgba(255, 255, 255, 0.25);
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, var(--black), var(--dark), var(--primary));
      color: var(--white);
      overflow-x: hidden;
    }

    /* Enhanced Navbar Styles */
    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1030;
      backdrop-filter: blur(15px);
      padding: 0.8rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .navbar-brand {
      font-family:  "Michroma", sans-serif;;
      font-weight: 400;
      font-size: 1.5rem;
      color: var(--white) !important;
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }

    .navbar-brand:hover {
      color: var(--secondary) !important;
      transform: translateY(-2px);
    }

    .navbar-brand img {
      transition: transform 0.3s ease;
    }

    .navbar-brand:hover img {
      transform: scale(1.1);
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.85) !important;
      font-weight: 500;
      padding: 0.5rem 1.2rem !important;
      margin: 0 0.2rem;
      border-radius: 30px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .nav-link:before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: var(--secondary);
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }

    .nav-link:hover {
      color: var(--white) !important;
      background: rgba(255, 255, 255, 0.1);
    }

    .nav-link:hover:before {
      width: 70%;
    }

    .nav-link.active {
      color: var(--white) !important;
      background: rgba(255, 255, 255, 0.15);
    }

    .nav-link.active:before {
      width: 70%;
      background: var(--secondary);
    }

    .navbar-toggler {
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 0.4rem 0.6rem;
    }

    .navbar-toggler:focus {
      box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.25);
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .btn-get-started {
      background: linear-gradient(135deg, var(--primary-light), var(--primary));
      color: var(--white) !important;
      border: none;
      border-radius: 30px;
      padding: 0.6rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(2, 94, 33, 0.4);
      position: relative;
      overflow: hidden;
    }

    .btn-get-started:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: all 0.6s ease;
    }

    .btn-get-started:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(2, 94, 33, 0.6);
    }

    .btn-get-started:hover:before {
      left: 100%;
    }

    /* Section Styles */
    .section {
      min-height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 6rem 2rem 2rem;
    }

    .section-content {
      max-width: 1200px;
      width: 100%;
      text-align: center;
    }

    /* Home Section */
    .home-section {
      background: linear-gradient(135deg, var(--black), var(--dark), var(--primary));
    }

    .header-logos {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 2rem;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }

    .header-logos img {
      transition: transform 0.3s ease;
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    }

    .header-logos img:hover {
      transform: scale(1.05);
    }

    .bsu-logo {
      width: 120px;
      height: auto;
    }

    .landing-title {
      font-family: 'Poppins', sans-serif;
      font-size: 3.2rem;
      font-weight: 700;
      color: var(--white);
      margin-bottom: 0.5rem;
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .landing-subtitle {
      font-size: 1.3rem;
      color: var(--white);
      margin-bottom: 2.5rem;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.6;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .btn-login {
      background-color: var(--white);
      color: var(--primary);
      padding: 0.9rem 2.5rem;
      font-size: 1.2rem;
      font-weight: 600;
      border-radius: 50px;
      transition: all 0.3s ease;
      border: none;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
      z-index: 1;
    }

    .btn-login:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 0%;
      height: 100%;
      background: var(--primary-light);
      transition: all 0.3s ease;
      z-index: -1;
      border-radius: 50px;
    }

    .btn-login:hover {
      color: var(--white);
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .btn-login:hover:before {
      width: 100%;
    }

    /* Features Section */
    .features-section {
      background: linear-gradient(135deg, var(--dark), var(--primary), var(--primary-light));
    }

    .section-title {
      font-family: 'Poppins', sans-serif;
      font-size: 2.8rem;
      font-weight: 700;
      margin-bottom: 3rem;
      color: var(--white);
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      padding: 0 1rem;
    }

    .feature-box {
      padding: 2.5rem 2rem;
      background: var(--card-bg);
      border-radius: 16px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .feature-box:hover {
      transform: translateY(-10px);
      background: rgba(255, 255, 255, 0.3);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
    }

    .feature-icon {
      font-size: 3rem;
      color: var(--white);
      margin-bottom: 1.5rem;
      text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .feature-text {
      font-size: 1.2rem;
      font-weight: 600;
    }

    /* About Section */
    .about-section {
      background: linear-gradient(135deg, var(--primary), var(--dark), var(--black));
    }

    .about-content {
      text-align: left;
      max-width: 800px;
      margin: 0 auto;
    }

    .about-text {
      font-size: 1.1rem;
      line-height: 1.7;
      margin-bottom: 2rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 3rem;
    }

    .stat-box {
      padding: 2rem;
      background: var(--card-bg);
      border-radius: 12px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--secondary);
      margin-bottom: 0.5rem;
    }

    .stat-label {
      font-size: 1rem;
      font-weight: 500;
    }

    /* Contact Section */
    .contact-section {
      background: linear-gradient(135deg, var(--primary-light), var(--primary), var(--dark));
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }

    .contact-info {
      text-align: left;
    }

    .contact-item {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .contact-icon {
      font-size: 1.5rem;
      color: var(--secondary);
      margin-right: 1rem;
      width: 40px;
    }

    .contact-form {
      background: var(--card-bg);
      padding: 2rem;
      border-radius: 16px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .form-control {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: var(--white);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.15);
      border-color: var(--secondary);
      box-shadow: 0 0 0 0.2rem rgba(243, 255, 72, 0.25);
      color: var(--white);
    }

    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    /* Footer */
    footer {
      background: var(--black);
      padding: 2rem;
      text-align: center;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Animations */
    .pulse {
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    .floating {
      animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
      0% { transform: translate(0, 0px); }
      50% { transform: translate(0, 10px); }
      100% { transform: translate(0, -0px); }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .landing-title {
        font-size: 2.3rem;
      }

      .landing-subtitle {
        font-size: 1.1rem;
      }

      .section-title {
        font-size: 2.2rem;
      }

      .header-logos {
        flex-direction: column;
        gap: 1rem;
      }

      .features-grid {
        grid-template-columns: 1fr;
        gap: 1.2rem;
      }
      
      .navbar-collapse {
        background: rgba(0, 0, 0, 0.9);
        border-radius: 10px;
        padding: 1rem;
        margin-top: 10px;
        backdrop-filter: blur(15px);
      }
      
      .nav-link {
        margin: 0.2rem 0;
        text-align: center;
      }
      
      .btn-get-started {
        width: 100%;
        margin-top: 0.5rem;
      }

      .contact-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <!-- Enhanced Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <!-- Logo on the left -->
      <a class="navbar-brand" href="#home">
        <img src="uploads/other/imslogo.png" alt="BSU Logo" height="40" class="me-2">
        <span>MUSH</span>
      </a>

      <!-- Toggler for mobile -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navbar links in middle + Get Started button on right -->
      <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
        <!-- Middle links -->
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" href="#home">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#features">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#about">About</a>
          </li>
        
        </ul>

        <!-- Get Started button on right -->
        <div class="d-flex">
          <a href="login.php" class="btn btn-get-started">
            Get Started <i class="fas fa-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Home Section -->
  <section id="home" class="section home-section">
    <div class="section-content">
      <!-- Logos -->
      <div class="header-logos">
        <img src="uploads/other/bsulogo.png" alt="BSU Logo" class="bsu-logo floating">
      </div>

      <h1 class="landing-title">Inventory Management System</h1>
      <p class="landing-subtitle">
        A centralized solution to manage, monitor, and maintain supplies and equipment efficiently at BSU - Bokod Campus.
      </p>

      <a href="login.php" class="btn btn-login mt-3 pulse">
        <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
      </a>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="section features-section">
    <div class="section-content">
      <h2 class="section-title">System Features</h2>
      <div class="features-grid">
        <div class="feature-box">
          <i class="fas fa-boxes-stacked feature-icon"></i>
          <div class="feature-text">Real-time Inventory Tracking</div>
          <p class="mt-2">Monitor stock levels and item movements in real-time with our advanced tracking system.</p>
        </div>

        <div class="feature-box">
          <i class="fas fa-dolly feature-icon"></i>
          <div class="feature-text">Property & Equipment Management</div>
          <p class="mt-2">Efficiently manage university property and equipment with detailed records and maintenance schedules.</p>
        </div>

        <div class="feature-box">
          <i class="fas fa-paper-plane feature-icon"></i>
          <div class="feature-text">Request & Approval Workflow</div>
          <p class="mt-2">Streamlined request process with automated approval workflows for faster procurement.</p>
        </div>

        <div class="feature-box">
          <i class="fas fa-user-shield feature-icon"></i>
          <div class="feature-text">Role-Based Access Control</div>
          <p class="mt-2">Secure system with different access levels for administrators, staff, and faculty members.</p>
        </div>

        <div class="feature-box">
          <i class="fas fa-chart-line feature-icon"></i>
          <div class="feature-text">Analytics & Reporting</div>
          <p class="mt-2">Generate comprehensive reports and analytics for better decision-making and resource planning.</p>
        </div>

        <div class="feature-box">
          <i class="fas fa-bell feature-icon"></i>
          <div class="feature-text">Automated Notifications</div>
          <p class="mt-2">Receive automatic alerts for low stock, pending approvals, and important system updates.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="section about-section">
    <div class="section-content">
      <h2 class="section-title">About Our System</h2>
      <div class="about-content">
        <p class="about-text">
          The Inventory Management System (IMS) is a comprehensive solution developed specifically for Benguet State University - Bokod Campus to address the challenges of managing university supplies, equipment, and property assets.
        </p>
        <p class="about-text">
        With real-time tracking capabilities and automated workflows, we help reduce operational costs, prevent stockouts, and ensure optimal resource utilization across all departments.
        </p>
        <p class="about-text">
          Designed with input from university staff and administrators, the IMS combines powerful functionality with an intuitive interface, making inventory management more efficient and transparent for everyone involved.
        </p>
        
        <div class="stats-grid">
          <div class="stat-box">
            <div class="stat-number">500+</div>
            <div class="stat-label">Items Tracked</div>
          </div>
          <div class="stat-box">
            <div class="stat-number">24/7</div>
            <div class="stat-label">System Availability</div>
          </div>
          <div class="stat-box">
            <div class="stat-number">50+</div>
            <div class="stat-label">Active Users</div>
          </div>
          <div class="stat-box">
            <div class="stat-number">99%</div>
            <div class="stat-label">Satisfaction Rate</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  

  <!-- Footer -->
  <footer>
    <p>© <?php echo date("Y"); ?> Benguet State University - Bokod Campus | Supply and Property Management Office</p>
    <p class="mt-2">Inventory Management System v2.0</p>
  </footer>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Navbar active state management
    document.addEventListener('DOMContentLoaded', function() {
      const navLinks = document.querySelectorAll('.nav-link');
      
      // Set active nav link on click
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          navLinks.forEach(l => l.classList.remove('active'));
          this.classList.add('active');
        });
      });
      
      // Update active nav link on scroll
      window.addEventListener('scroll', function() {
        let current = '';
        const sections = document.querySelectorAll('section');
        
        sections.forEach(section => {
          const sectionTop = section.offsetTop;
          const sectionHeight = section.clientHeight;
          if (pageYOffset >= sectionTop - 100) {
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
</body>
</html>