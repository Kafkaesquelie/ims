<?php
require_once('includes/load.php');
page_require_level(1); // admin only

// Database credentials from Clever Cloud
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

$mysqli = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Get all tables
$tables = [];
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

$backupSQL = "-- WBIMS Database Backup\n";
$backupSQL .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    // Drop table
    $backupSQL .= "DROP TABLE IF EXISTS `$table`;\n";

    // Table structure
    $res = $mysqli->query("SHOW CREATE TABLE `$table`");
    $row = $res->fetch_assoc();
    $backupSQL .= $row['Create Table'] . ";\n\n";

    // Table data
    $res = $mysqli->query("SELECT * FROM `$table`");
    while ($data = $res->fetch_assoc()) {
        $columns = array_keys($data);
        $values  = array_map(function($value) use ($mysqli) {
            return "'" . $mysqli->real_escape_string($value) . "'";
        }, array_values($data));

        $backupSQL .= "INSERT INTO `$table` (`"
                    . implode("`, `", $columns)
                    . "`) VALUES ("
                    . implode(", ", $values)
                    . ");\n";
    }

    $backupSQL .= "\n\n";
}

// Generate file
$filename = "WBIMS_Backup_" . date('Y-m-d_His') . ".sql";

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $backupSQL;
exit;
?>
