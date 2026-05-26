<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/EligibilityService.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid request method"
    );

    exit();
}

SessionManager::requireRole(
    "Administrator"
);

if (
    !isset($_FILES["studentCSV"])
    ||
    $_FILES["studentCSV"]["error"] !== UPLOAD_ERR_OK
) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Please upload a valid CSV file"
    );

    exit();
}

$fileName =
    $_FILES["studentCSV"]["name"];

$extension =
    strtolower(
        pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        )
    );

if ($extension !== "csv") {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Only CSV files are allowed"
    );

    exit();
}

if ((int) $_FILES["studentCSV"]["size"] > 5242880) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=CSV file cannot exceed 5MB"
    );

    exit();
}

$eligibilityService =
    new EligibilityService();

$result =
    $eligibilityService
    ->importStudentEligibilityCSV(
        $_SESSION["systemRole"],
        $_FILES["studentCSV"]["tmp_name"]
    );

$status =
    $result["success"]
    ? "success"
    : "error";

$message =
    urlencode(
        $result["message"]
    );

header(
    "Location: ../../../client/admin/studentEligibility.php?status={$status}&message={$message}"
);

exit();

?>




