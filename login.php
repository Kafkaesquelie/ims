<?php
  ob_start();
  require_once('includes/load.php');
  if ($session->isUserLoggedIn(true)) { redirect('admin.php', false); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IMS | Login Page</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.3.0/css/all.min.css">

  <style>
    :root {
      --primary: #025621;
      --primary-light: #1e7e34;
      --secondary: #f3ff48;
      --white: #ffffff;
      --dark: #023b08;
      --light-bg: rgba(255, 255, 255, 0.15);
      --card-bg: rgba(255, 255, 255, 0.25);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, var(--dark), var(--primary), var(--secondary));
      background-size: 400% 400%;
      animation: gradient 15s ease infinite;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      margin: 0;
      overflow-x: hidden;
    }

    @keyframes gradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 450px;
      position: relative;
      overflow: hidden;
      transition: transform 0.3s ease;
    }

    .login-container:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .logo-container {
      text-align: center;
      margin-bottom: 25px;
    }

    .logo-container img {
      width: 100px;
      height: auto;
      transition: transform 0.5s ease;
    }

    .logo-container img:hover {
      transform: rotate(5deg) scale(1.05);
    }

    .login-container h1 {
      font-size: 2.2rem;
      margin-bottom: 10px;
      font-weight: 700;
      color: var(--dark);
      text-align: center;
    }

    .login-container p {
      color: #6c757d;
      margin-bottom: 30px;
      font-weight: 500;
      text-align: center;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .input-group {
      position: relative;
    }

    .form-control {
      height: 52px;
      border-radius: 10px;
      padding-left: 50px;
      font-size: 1rem;
      border: 2px solid #e9ecef;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }

    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(2, 86, 33, 0.15);
      background-color: #fff;
    }

    .input-group-text {
      position: absolute;
      left: 0;
      top: 0;
      height: 52px;
      width: 50px;
      background: transparent;
      border: none;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
    }

    .btn-login {
      height: 52px;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border: none;
      color: white;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-login:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: 0.5s;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(2, 86, 33, 0.4);
    }

    .btn-login:hover:before {
      left: 100%;
    }

    @keyframes typing {
      from { width: 0; }
      to { width: 100%; }
    }

    @keyframes blink {
      50% { border-color: transparent; }
    }

    .floating {
      animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
      0% { transform: translate(0, 0px); }
      50% { transform: translate(0, 10px); }
      100% { transform: translate(0, -0px); }
    }

    /* Custom Alert Styles */
    .custom-alert {
      border-radius: 10px;
      border: none;
      padding: 12px 15px;
      margin-bottom: 20px;
      animation: slideInDown 0.5s ease;
    }

    .alert-danger {
      background: linear-gradient(135deg, #f8d7da, #f1b0b7);
      color: #721c24;
      border-left: 4px solid #dc3545;
    }

    .alert-success {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      color: #155724;
      border-left: 4px solid #28a745;
    }

    .alert-warning {
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
      color: #856404;
      border-left: 4px solid #ffc107;
    }

    .alert-info {
      background: linear-gradient(135deg, #d1ecf1, #b8e2e8);
      color: #0c5460;
      border-left: 4px solid #17a2b8;
    }

    .alert-dismissible .btn-close {
      padding: 0.75rem;
    }

    @keyframes slideInDown {
      from {
        transform: translateY(-20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .forgot-password {
      text-align: center;
      margin-top: 15px;
    }

    .forgot-password a {
      color: var(--primary);
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s ease;
    }

    .forgot-password a:hover {
      color: var(--primary-light);
      text-decoration: underline;
    }

    @media (max-width: 576px) {
      .login-container {
        padding: 30px 20px;
      }
      
      .login-container h1 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="logo-container">
      <img src="uploads/other/bsulogo.png" alt="BSU Logo" >
    </div>

    <h1>Welcome Back</h1>
    <p id="typing-text" class="typing-animation"></p>
<!-- Message Display Area -->
<div id="message-container">
  <?php 
  // Display message as alert
  if(isset($msg) && !empty($msg)) {
    $msg_type = 'danger'; // default type
    $msg_text = '';
    
    // Handle different message formats
    if(is_array($msg)) {
      // If $msg is an array with type and text
      if(isset($msg['type'])) {
        $msg_type = $msg['type'];
      }
      if(isset($msg['text'])) {
        $msg_text = $msg['text'];
      } elseif(isset($msg['message'])) {
        $msg_text = $msg['message'];
      } else {
        // If it's a simple array, try to get the first element
        $msg_text = !empty($msg) ? reset($msg) : '';
      }
    } else {
      // If $msg is a string
      $msg_text = $msg;
    }
    
    // Only display if we have message text
    if(!empty($msg_text)) {
      echo '<div class="custom-alert alert-' . htmlspecialchars($msg_type) . ' alert-dismissible fade show" role="alert">';
      echo htmlspecialchars($msg_text);
      echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
      echo '</div>';
    }
  }
  ?>
</div>

    <form method="post" action="auth_v2.php" class="clearfix">
      <div class="form-group">
        <div class="input-group">
          <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
          <div class="input-group-text">
            <span class="fa-solid fa-user"></span>
          </div>
        </div>
      </div>
      
      <div class="form-group">
        <div class="input-group">
          <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
          <div class="input-group-text">
            <span class="fa-solid fa-lock"></span>
          </div>
        </div>
      </div>
      
      <div class="d-grid">
        <button type="submit" class="btn btn-login">Login</button>
      </div>
    </form>

    <!-- <div class="forgot-password">
      <a href="forgot-password.php">Forgot your password?</a>
    </div> -->
  </div>

  <!-- Typing animation -->
  <script>
    const phrases = [
      "Sign in to BSU-Bokod Inventory System",
      "Manage your supplies efficiently",
      "Login, Request, Acquire",
      "Keep track of your resources with ease"
    ];
    const typingText = document.getElementById("typing-text");
    let phraseIndex = 0, charIndex = 0;
    let isDeleting = false;
    
    function typePhrase() {
      const currentPhrase = phrases[phraseIndex];
      
      if (isDeleting) {
        // Deleting text
        typingText.textContent = currentPhrase.substring(0, charIndex - 1);
        charIndex--;
        
        if (charIndex === 0) {
          isDeleting = false;
          phraseIndex = (phraseIndex + 1) % phrases.length;
          setTimeout(typePhrase, 500);
        } else {
          setTimeout(typePhrase, 50);
        }
      } else {
        // Typing text
        typingText.textContent = currentPhrase.substring(0, charIndex + 1);
        charIndex++;
        
        if (charIndex === currentPhrase.length) {
          isDeleting = true;
          setTimeout(typePhrase, 1500);
        } else {
          setTimeout(typePhrase, 90);
        }
      }
    }
    
    // Start the typing animation
    typePhrase();

    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.custom-alert');
      alerts.forEach(function(alert) {
        setTimeout(function() {
          const bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
        }, 5000);
      });
    });
  </script>

  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>