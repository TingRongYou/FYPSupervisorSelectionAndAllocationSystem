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
    header("Location: ../../../client/admin/supervisorsManagement.php?status=error&message=Invalid request method");

    exit();
}
/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators are allowed to update supervisor accounts.
*/

SessionManager::requireRole("Administrator");

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
| Blocks forged supervisor account updates before data is changed.
*/

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {
    header("Location: ../../../client/admin/supervisorsManagement.php?status=error&message=Invalid CSRF token");

    exit();
}

/*
|--------------------------------------------------------------------------
| Supervisor Account Update
|--------------------------------------------------------------------------
| Update supervisor information such as name, email,
| and account active status.
*/
$supervisorManagementService = new SupervisorManagementService();

/*
|--------------------------------------------------------------------------
| Result Status Handling
|--------------------------------------------------------------------------
| Convert service response into success or error status.
*/

$result =
    $supervisorManagementService
    ->updateSupervisorAccount(
        $_SESSION["systemRole"],
        $_POST["supervisorID"] ?? "",
        $_POST["fullName"] ?? "",
        $_POST["universityEmail"] ?? "",
        $_POST["activeStatus"] ?? ""
    );

$status = $result["success"] ? "success" : "error";

$message = urlencode($result["message"]);

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
| Redirect administrator back with update result message.
*/

header("Location: ../../../client/admin/supervisorsManagement.php?status={$status}&message={$message}");

exit();

?>
