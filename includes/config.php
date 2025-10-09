<?php
  // Use environment variables with fallback to default values
  define( 'DB_HOST', $_ENV['DB_HOST'] ?? 'localhost:3308' );        
  define( 'DB_USER', $_ENV['DB_USER'] ?? 'root' );             
  define( 'DB_PASS', $_ENV['DB_PASS'] ?? '' );            
  define( 'DB_NAME', $_ENV['DB_NAME'] ?? 'inv_system' );        

?>
