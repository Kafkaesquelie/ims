<?php
require_once('includes/load.php'); // This already starts the session

$session->msg('s', 'You have successfully logged out.');

$session->logout(); // This destroys the session fully

header("Location: login.php");
exit();
