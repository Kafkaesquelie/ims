<?php
// Prevent accidental output before session starts
// ob_start();
// Enable error reporting (development/debug mode only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------
// DEFINE SEPARATOR ALIASES
// -----------------------------------------------------------------------
define("URL_SEPARATOR", '/');
define("DS", DIRECTORY_SEPARATOR);

// -----------------------------------------------------------------------
// DEFINE ROOT PATHS
// -----------------------------------------------------------------------
defined('SITE_ROOT') or define('SITE_ROOT', realpath(dirname(__FILE__)));
define("LIB_PATH_INC", SITE_ROOT . DS);

// Load configuration first
require_once(LIB_PATH_INC . 'config.php');

// Load functions next (includes make_date function)
require_once(LIB_PATH_INC . 'functions.php');

// Session should load after functions to avoid undefined calls
require_once(LIB_PATH_INC . 'session.php');

// Other required system files
require_once(LIB_PATH_INC . 'upload.php');
require_once(LIB_PATH_INC . 'database.php');
require_once(LIB_PATH_INC . 'sql.php');

// Clean accidental output to prevent header issues
// ob_end_clean();

?>
