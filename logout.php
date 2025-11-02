<?php
require_once('includes/load.php'); // include your class

$auth = new Session(); 

// Call logout
$auth->logout();
// Destroy session completely
session_destroy();
// Add a logout confirmation message
$auth->msg('s', 'You have successfully logged out.');
// Redirect to login page 
header("Location: index.php");
exit();
?>

