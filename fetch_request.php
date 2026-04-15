<?php
require_once('includes/load.php');

$requests = find_by_sql("SELECT * FROM requests ORDER BY id DESC");

foreach ($requests as $req) {
    echo "<tr>";
    echo "<td>{$req['id']}</td>";
    echo "<td>{$req['ris_no']}</td>";
    echo "<td>{$req['status']}</td>";
    echo "<td>{$req['created_at']}</td>";
    echo "</tr>";
}