<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/SupervisorManagementService.php";

/*
|--------------------------------------------------------------------------
| Session Bootstrap
|--------------------------------------------------------------------------
| Start session before processing supervisor account updates.
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| Ensure account updates are only submitted through POST request.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/supervisorsManagement.php?status=error&message=Invalid request method"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators are allowed to update supervisor accounts.
*/

SessionManager::requireRole(
    "Administrator"
);

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
| Blocks forged classification updates before business rules run.
*/

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {

    header(
        "Location: ../../../client/admin/supervisorsManagement.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Supervisor Role Classification
|--------------------------------------------------------------------------
| Assign employment category and quota classification
| for selected supervisor account.
*/

$supervisorManagementService =
    new SupervisorManagementService();

$result =
    $supervisorManagementService
    ->classifySupervisorRole(
        $_SESSION["systemRole"],
        $_POST["supervisorID"] ?? "",
        $_POST["employmentCategory"] ?? "",
        $_POST["quotaID"] ?? ""
    );

/*
|--------------------------------------------------------------------------
| Result Status Handling
|--------------------------------------------------------------------------
| Convert service response into success or error status.
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
| Redirect administrator back with classification result message.
*/

header(
    "Location: ../../../client/admin/supervisorsManagement.php?status={$status}&message={$message}"
);

exit();

?>
