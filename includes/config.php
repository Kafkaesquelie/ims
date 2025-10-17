<?php
  // Use environment variables with fallback to default values
  // define( 'DB_HOST', $_ENV['DB_HOST'] ?? 'localhost:3308' );        
  // define( 'DB_USER', $_ENV['DB_USER'] ?? 'root' );             
  // define( 'DB_PASS', $_ENV['DB_PASS'] ?? '' );            
  // define( 'DB_NAME', $_ENV['DB_NAME'] ?? 'inv_system' );        


 
// Get the full Render Postgres URL
$database_url = "postgresql://inventory_user:ynmFwXQUUSOo7kMhHYJXaA2juWcIJxAo@dpg-d3jmkoq4d50c73fb03og-a.singapore-postgres.render.com/inv_system_ps8n";

// // Parse the URL into components
$db = parse_url($database_url);

define('DB_HOST', $db['host']);           // dpg-d3jmkoq4d50c73fb03og-a.singapore-postgres.render.com
define('DB_PORT', $db['port'] ?? 5432);   // default port 5432
define('DB_USER', $db['user']);           // inventory_user
define('DB_PASS', $db['pass']);           // your password
define('DB_NAME', ltrim($db['path'], '/')); // inv_system_ps8n
?>


