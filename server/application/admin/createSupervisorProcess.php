<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/UserManagementService.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Validation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/admin/createSupervisorForm.php?status=error&message=Invalid request method");
    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication Validation
|--------------------------------------------------------------------------
*/

if (!SessionManager::isLoggedIn()) {
    header("Location: ../../../client/auth/login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole("Administrator");

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
| Blocks forged supervisor account creation requests.
*/

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {
    header("Location: ../../../client/admin/supervisorsManagement.php?status=error&message=Invalid CSRF token");
    exit();
}

/*
|--------------------------------------------------------------------------
| Create Supervisor Account
|--------------------------------------------------------------------------
*/

$userManagementService = new UserManagementService();

$result =
    $userManagementService->createSupervisorAccount(
        $_SESSION["systemRole"],

        $_POST["supervisorID"] ?? "",

        $_POST["fullName"] ?? "",

        $_POST["universityEmail"] ?? "",

        $_POST["password"] ?? "",

        $_POST["programme"] ?? "",

        $_POST["employmentCategory"] ?? "",

        $_POST["quotaID"] ?? ""
    );

/*
|--------------------------------------------------------------------------
| Redirect Response
|--------------------------------------------------------------------------
*/

$status = $result["success"] ? "success" : "error";

$message = urlencode($result["message"]);

$returnTo = $_POST["returnTo"] ?? "";

$redirectPage = $returnTo === "supervisorsManagement" ? "admin/supervisorsManagement.php" : "admin/createSupervisorForm.php";

$sourceQuery = $returnTo === "supervisorsManagement" && !$result["success"] ? "&source=createSupervisor" : "";

/*
|--------------------------------------------------------------------------
| Success Redirect
|--------------------------------------------------------------------------
*/

if ($result["success"]) {
    header("Location: ../../../client/{$redirectPage}?status={$status}&message={$message}");

    exit();
}

/*
|--------------------------------------------------------------------------
| Failure Redirect
|--------------------------------------------------------------------------
*/

header("Location: ../../../client/{$redirectPage}?status={$status}&message={$message}{$sourceQuery}");

exit();

?>
