<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../../business/services/AccountService.php";

/*
|--------------------------------------------------------------------------
| Session Bootstrap
|--------------------------------------------------------------------------
| Start or resume session before accessing session data.
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| Only allow profile photo update through POST request.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/shared/profile.php?status=error&message=Invalid request method");
    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
| Ensure only logged-in users can update their profile photo.
*/

SessionManager::requireLogin();

/*
|--------------------------------------------------------------------------
| CSRF Token Validation
|--------------------------------------------------------------------------
| Verify that the request comes from the official profile form.
*/

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {

    header("Location: ../../../client/shared/profile.php?status=error&message=Invalid CSRF token");
    exit();
}

/*
|--------------------------------------------------------------------------
| Account Service Initialization
|--------------------------------------------------------------------------
| Create service object to handle profile photo update logic.
*/

$accountService = new AccountService();

/*
|--------------------------------------------------------------------------
| Profile Photo Update
|--------------------------------------------------------------------------
| Pass uploaded profile photo to AccountService for validation and saving.
*/

$result =
    $accountService
    ->updateProfilePhoto(
        $_SESSION["userID"],
        $_FILES["profilePhoto"] ?? []
    );

/*
|--------------------------------------------------------------------------
| Session Profile Photo Refresh
|--------------------------------------------------------------------------
| If upload succeeds, reload profile and update photo path in session.
*/

if ($result["success"]) {

    $profile = $accountService->getAccountProfile($_SESSION["userID"]);

    SessionManager::setProfilePhotoPath($profile["profilePhotoPath"] ?? "");
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
| Redirect user back to profile page with upload result message.
*/

header("Location: ../../../client/shared/profile.php?status={$status}&message={$message}");
exit();

?>
