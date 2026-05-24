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
        "Location: ../../client/manageDigitalBusinessCard.php?status=error&message=Invalid request method"
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
| Retrieve & Normalize Form Data
|--------------------------------------------------------------------------
*/

$programme =
    trim(
        $_POST["programme"] ?? ""
    );

$employmentCategory =
    trim(
        $_POST["employmentCategory"] ?? ""
    );

$introVideoLink =
    trim(
        $_POST["introVideoLink"] ?? ""
    );

/*
|--------------------------------------------------------------------------
| Update Digital Business Card
|--------------------------------------------------------------------------
*/

$profileService =
    new SupervisorProfileService();

$result =
    $profileService->updateDigitalBusinessCard(

        $_SESSION["userID"],

        $programme,

        $employmentCategory,

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
    "Location: ../../client/manageDigitalBusinessCard.php?status={$status}&message={$message}"
);

exit();

?>