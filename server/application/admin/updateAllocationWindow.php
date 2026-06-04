<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/AllocationWindowService.php";

SessionManager::startSession();
SessionManager::requireRole("Administrator");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../../client/admin/adminDashboard.php?status=error&message=Invalid request method");
    exit();
}

if (
    empty($_POST["csrf_token"]) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {

    header("Location: ../../../client/admin/adminDashboard.php?status=error&message=Invalid CSRF token");
    exit();
}

$allocationWindowService =
    new AllocationWindowService();

$result =
    $allocationWindowService
    ->updateWindow(
        $_POST["initialAllocationDate"] ?? "",
        $_POST["finalAllocationDate"] ?? "",
        $_POST["reviewStartDate"] ?? "",
        $_POST["reviewEndDate"] ?? ""
    );

$status =
    $result["success"] ? "success" : "error";

$message =
    urlencode($result["message"]);

header("Location: ../../../client/admin/adminDashboard.php?status={$status}&message={$message}");
exit();

?>
