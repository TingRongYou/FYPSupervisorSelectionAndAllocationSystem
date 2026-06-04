<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/EligibilityService.php";

/*
|--------------------------------------------------------------------------
| Session Bootstrap
|--------------------------------------------------------------------------
| Start the session before checking user role or processing data.
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| Ensure this batch process only runs through POST request.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid request method"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators are allowed to run the eligibility batch.
*/

SessionManager::requireRole(
    "Administrator"
);

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
| Blocks forged eligibility batch requests before statuses are changed.
*/

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Eligibility Batch Processing
|--------------------------------------------------------------------------
| Run eligibility rules and create accounts for eligible students.
*/

$eligibilityService =
    new EligibilityService();

$result =
    $eligibilityService
    ->runEligibilityBatch(
        $_SESSION["systemRole"]
    );

/*
|--------------------------------------------------------------------------
| Result Status Handling
|--------------------------------------------------------------------------
| Convert service result into success or error status.
*/

$status =
    $result["success"]
    ? "success"
    : "error";

$message =
    urlencode(
        $result["message"]
    );

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
| Redirect administrator back with result message.
*/

header(
    "Location: ../../../client/admin/studentEligibility.php?status={$status}&message={$message}"
);

exit();

?>
