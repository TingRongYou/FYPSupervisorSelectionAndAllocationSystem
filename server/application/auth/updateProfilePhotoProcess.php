<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../../business/services/AccountService.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../../client/shared/profile.php?status=error&message=Invalid request method");
    exit();
}

SessionManager::requireLogin();

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {

    header("Location: ../../../client/shared/profile.php?status=error&message=Invalid CSRF token");
    exit();
}

$accountService = new AccountService();

$result =
    $accountService
    ->updateProfilePhoto(
        $_SESSION["userID"],
        $_FILES["profilePhoto"] ?? []
    );

if ($result["success"]) {

    $profile =
        $accountService
        ->getAccountProfile(
            $_SESSION["userID"]
        );

    SessionManager::setProfilePhotoPath(
        $profile["profilePhotoPath"] ?? ""
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

header("Location: ../../../client/shared/profile.php?status={$status}&message={$message}");
exit();

?>




