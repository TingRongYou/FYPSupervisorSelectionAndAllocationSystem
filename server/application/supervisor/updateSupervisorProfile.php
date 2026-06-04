<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/SupervisorProfileService.php";

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
        "Location: ../../../client/supervisor/manageDigitalBusinessCard.php?status=error&message=Invalid request method"
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
| CSRF Validation
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {

    header(
        "Location: ../../../client/supervisor/manageDigitalBusinessCard.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

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

$activeTime =
    trim(
        $_POST["activeTime"] ?? ""
    );

$introVideoLink =
    trim(
        $_POST["introVideoLink"] ?? ""
    );

$supervisorBio =
    trim(
        $_POST["supervisorBio"] ?? ""
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

        $activeTime,

        $introVideoLink,

        $supervisorBio,

        $_FILES["profilePhoto"] ?? null
    );

if ($result["success"]) {

    $profile =
        $profileService
        ->getDigitalBusinessCard(
            $_SESSION["userID"]
        );

    SessionManager::setProfilePhotoPath(
        $profile["profilePhotoPath"] ?? ""
    );
}

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
    "Location: ../../../client/supervisor/manageDigitalBusinessCard.php?status={$status}&message={$message}"
);

exit();

?>
