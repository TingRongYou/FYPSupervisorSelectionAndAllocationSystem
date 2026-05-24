<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../business/SupervisorProfileService.php";

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
        "Location: ../../client/manageIntroVideo.php?status=error&message=Invalid request method"
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

$introVideoLink =
    trim(
        $_POST["introVideoLink"] ?? ""
    );

/*
|--------------------------------------------------------------------------
| Update Introductory Video
|--------------------------------------------------------------------------
*/

$profileService =
    new SupervisorProfileService();

$result =
    $profileService->updateIntroVideo(

        $_SESSION["userID"],

        $introVideoLink
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
    "Location: ../../client/manageIntroVideo.php?status={$status}&message={$message}"
);

exit();

?>