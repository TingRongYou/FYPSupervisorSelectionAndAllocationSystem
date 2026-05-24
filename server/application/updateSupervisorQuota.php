<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../business/QuotaManager.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../client/quotaManagement.php?status=error&message=Invalid request method"
    );

    exit();
}

SessionManager::requireRole(
    "Administrator"
);

$quotaManager =
    new QuotaManager();

$result =
    $quotaManager
    ->updateSupervisorQuota(
        $_SESSION["systemRole"],
        $_POST["supervisorID"] ?? "",
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
    "Location: ../../client/quotaManagement.php?status={$status}&message={$message}"
);

exit();

?>
