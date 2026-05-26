<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/QuotaManager.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/quotaManagement.php?status=error&message=Invalid request method"
    );

    exit();
}

SessionManager::requireRole(
    "Administrator"
);

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {

    header(
        "Location: ../../../client/admin/quotaManagement.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

$quotaManager =
    new QuotaManager();

if (isset($_POST["quotaRows"])) {

    $result =
        $quotaManager
        ->updateSupervisorQuotas(
            $_SESSION["systemRole"],
            $_POST["quotaRows"]
        );

} else {

    $result =
        $quotaManager
        ->updateSupervisorQuota(
            $_SESSION["systemRole"],
            $_POST["supervisorID"] ?? "",
            $_POST["quotaID"] ?? "",
            $_POST["assignedQuotaLimit"] ?? ""
        );
}

$status =
    $result["success"]
    ? "success"
    : "error";

$message =
    urlencode(
        $result["message"]
    );

header(
    "Location: ../../../client/admin/quotaManagement.php?status={$status}&message={$message}"
);

exit();

?>




