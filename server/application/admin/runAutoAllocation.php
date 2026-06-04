<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/AllocationEngine.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/autoAllocation.php?status=error&message=Invalid request method"
    );

    exit();
}

SessionManager::requireRole(
    "Administrator"
);

if (
    empty($_POST["csrf_token"]) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {

    header(
        "Location: ../../../client/admin/autoAllocation.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

$allocationEngine =
    new AllocationEngine();

$result =
    $allocationEngine
    ->executeAutoAllocation(
        $_SESSION["systemRole"],
        $_SESSION["userID"] ?? null
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
    "Location: ../../../client/admin/autoAllocation.php?status={$status}&message={$message}"
);

exit();

?>
