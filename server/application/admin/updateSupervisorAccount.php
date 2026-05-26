<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/SupervisorManagementService.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/supervisorsManagement.php?status=error&message=Invalid request method"
    );

    exit();
}

SessionManager::requireRole(
    "Administrator"
);

$supervisorManagementService =
    new SupervisorManagementService();

$result =
    $supervisorManagementService
    ->updateSupervisorAccount(
        $_SESSION["systemRole"],
        $_POST["supervisorID"] ?? "",
        $_POST["fullName"] ?? "",
        $_POST["universityEmail"] ?? "",
        $_POST["activeStatus"] ?? ""
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
    "Location: ../../../client/admin/supervisorsManagement.php?status={$status}&message={$message}"
);

exit();

?>




