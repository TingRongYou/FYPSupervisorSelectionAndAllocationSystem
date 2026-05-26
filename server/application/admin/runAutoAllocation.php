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

$allocationEngine =
    new AllocationEngine();

$result =
    $allocationEngine
    ->executeAutoAllocation(
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
    "Location: ../../../client/admin/autoAllocation.php?status={$status}&message={$message}"
);

exit();

?>




