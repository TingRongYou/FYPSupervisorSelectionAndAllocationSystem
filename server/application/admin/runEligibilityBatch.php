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

$eligibilityService =
    new EligibilityService();

$result =
    $eligibilityService
    ->runEligibilityBatch(
        $_SESSION["systemRole"]
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




