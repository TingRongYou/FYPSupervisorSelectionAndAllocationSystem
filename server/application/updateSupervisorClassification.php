<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../business/SupervisorManagementService.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../client/supervisorsManagement.php?status=error&message=Invalid request method"
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
    ->classifySupervisorRole(
        $_SESSION["systemRole"],
        $_POST["supervisorID"] ?? "",
        $_POST["employmentCategory"] ?? "",
        $_POST["quotaID"] ?? ""
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
    "Location: ../../client/supervisorsManagement.php?status={$status}&message={$message}"
);

exit();

?>
