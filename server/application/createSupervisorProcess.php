<?php

require_once "SessionManager.php";
require_once "../business/UserManagementService.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Validation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../client/createSupervisorForm.php?status=error&message=Invalid request method");
    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication Validation
|--------------------------------------------------------------------------
*/

if (!SessionManager::isLoggedIn()) {

    header("Location: ../../client/login.html");
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
| Create Supervisor Account
|--------------------------------------------------------------------------
*/

$userManagementService =
    new UserManagementService();

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
| Success Redirect
|--------------------------------------------------------------------------
*/

if ($result["success"]) {

    header(
        "Location: ../../client/adminDashboard.php?status={$status}&message={$message}"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Failure Redirect
|--------------------------------------------------------------------------
*/

header(
    "Location: ../../client/createSupervisorForm.php?status={$status}&message={$message}"
);

exit();

?>