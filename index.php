<?php

require_once "server/data/database.php";

$db = new Database();
$conn = $db->connect();

echo "<h1>SSAS Database Connected Successfully</h1>";

?>