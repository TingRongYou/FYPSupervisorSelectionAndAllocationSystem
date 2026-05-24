<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../business/TagManagementService.php";

/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Validation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../client/manageExpertiseTags.php?status=error&message=Invalid request method"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireLogin();

/*
|--------------------------------------------------------------------------
| RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Supervisor"
);

/*
|--------------------------------------------------------------------------
| Retrieve Form Data
|--------------------------------------------------------------------------
*/

$tagIDs =
    $_POST["tagIDs"] ?? [];

/*
|--------------------------------------------------------------------------
| Update Supervisor Expertise Tags
|--------------------------------------------------------------------------
*/

$tagService =
    new TagManagementService();

$result =
    $tagService->updateSupervisorTags(

        $_SESSION["userID"],

        $tagIDs
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

header(
    "Location: ../../client/manageExpertiseTags.php?status={$status}&message={$message}"
);

exit();

?>