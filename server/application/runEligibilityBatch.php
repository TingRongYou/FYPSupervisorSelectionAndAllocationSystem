<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../business/EligibilityService.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../client/studentEligibility.php?status=error&message=Invalid request method"
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
    "Location: ../../client/studentEligibility.php?status={$status}&message={$message}"
);

exit();

?>
