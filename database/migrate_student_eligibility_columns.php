<?php

require_once __DIR__ . "/../server/data/database/database.php";

$database = new Database();
$conn = $database->connect();

$requiredColumns = [
    "currentSem" => "ALTER TABLE STUDENT_PROFILE ADD COLUMN currentSem VARCHAR(10) NOT NULL DEFAULT 'Y1S1' AFTER intakeBatch",
    "eligibilityStatus" => "ALTER TABLE STUDENT_PROFILE ADD COLUMN eligibilityStatus BOOLEAN NOT NULL DEFAULT FALSE AFTER portfolioURL"
];

foreach ($requiredColumns as $columnName => $alterSql) {

    $statement = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'STUDENT_PROFILE'
        AND COLUMN_NAME = :columnName
    ");

    $statement->bindParam(
        ":columnName",
        $columnName
    );

    $statement->execute();

    $result =
        $statement->fetch(
            PDO::FETCH_ASSOC
        );

    if ((int) $result["total"] === 0) {

        $conn->exec($alterSql);
    }
}

echo "student eligibility column migration ok" . PHP_EOL;

?>
