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
        "Location: ../../../client/supervisor/manageIntroVideo.php?status=error&message=Invalid request method"
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
        "Location: ../../../client/supervisor/manageIntroVideo.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Retrieve Form Data
|--------------------------------------------------------------------------
*/

$introVideoLink =
    trim(
        $_POST["introVideoLink"] ?? ""
    );

$introVideoDescription =
    trim(
        $_POST["introVideoDescription"] ?? ""
    );

$contentSource =
    trim(
        $_POST["contentSource"] ?? "external"
    );

$existingIntroVideoLink =
    trim(
        $_POST["existingIntroVideoLink"] ?? ""
    );

/*
|--------------------------------------------------------------------------
| Update Introductory Video
|--------------------------------------------------------------------------
*/

$profileService =
    new SupervisorProfileService();

if (isset($_POST["removeIntroVideo"]) && $_POST["removeIntroVideo"] === "1") {

    $result =
        $profileService
        ->removeIntroVideo(
            $_SESSION["userID"]
        );

} elseif ($contentSource === "upload") {

    $uploadedFile =
        $_FILES["introVideoFile"] ?? [];

    if (
        isset($uploadedFile["error"]) &&
        (int) $uploadedFile["error"] === UPLOAD_ERR_NO_FILE &&
        preg_match(
            "/^\.\.\/storage\/intro_videos\/[A-Za-z0-9_-]+\.(mp4|webm)$/i",
            $existingIntroVideoLink
        ) === 1
    ) {

        $result =
            $profileService->updateIntroVideo(

                $_SESSION["userID"],

                $existingIntroVideoLink,

                $introVideoDescription
            );

    } else {

        $result =
            $profileService->updateIntroVideoFromUpload(

                $_SESSION["userID"],

                $uploadedFile,

                $introVideoDescription
            );
    }

} else {

    $result =
        $profileService->updateIntroVideo(

            $_SESSION["userID"],

            $introVideoLink,

            $introVideoDescription
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
    "Location: ../../../client/supervisor/manageIntroVideo.php?status={$status}&message={$message}"
);

exit();

?>




