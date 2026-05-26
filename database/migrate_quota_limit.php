<?php

require_once __DIR__ . "/../server/data/database/database.php";

$database = new Database();
$conn = $database->connect();

$statement = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'SUPERVISOR_PROFILE'
    AND COLUMN_NAME = 'assignedQuotaLimit'
");

$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);

if ((int) $result["total"] === 0) {
    $conn->exec("
        ALTER TABLE SUPERVISOR_PROFILE
        ADD COLUMN assignedQuotaLimit INT NULL AFTER quotaID
    ");
}

echo "quota limit migration ok" . PHP_EOL;

?>

