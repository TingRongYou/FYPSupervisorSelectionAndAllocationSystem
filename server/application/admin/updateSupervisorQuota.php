<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/QuotaManager.php";

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
    header("Location: ../../../client/admin/quotaManagement.php?status=error&message=Invalid request method");

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
| CSRF Token Validation
|--------------------------------------------------------------------------
| Verify that the request comes from the official quota management form.
*/

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {

    header("Location: ../../../client/admin/quotaManagement.php?status=error&message=Invalid CSRF token");

    exit();
}

/*
|--------------------------------------------------------------------------
| Quota Manager Initialization
|--------------------------------------------------------------------------
| Create quota manager object to handle quota update logic.
*/
$quotaManager = new QuotaManager();

/*
|--------------------------------------------------------------------------
| Quota Update Processing
|--------------------------------------------------------------------------
| Update multiple supervisor quotas if quotaRows exists;
| otherwise update a single supervisor quota.
*/

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

    /*
|--------------------------------------------------------------------------
| Result Status Handling
|--------------------------------------------------------------------------
| Convert service response into success or error status.
*/

$status = $result["success"] ? "success" : "error";

$message = urlencode($result["message"]);

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
| Redirect administrator back to quota management page with result message.
*/

header("Location: ../../../client/admin/quotaManagement.php?status={$status}&message={$message}");

exit();

?>
