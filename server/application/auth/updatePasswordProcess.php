<?php

require_once __DIR__ . "/SessionManager.php";
require_once __DIR__ . "/../../business/services/AccountService.php";

/*
|--------------------------------------------------------------------------
| Session Bootstrap
|--------------------------------------------------------------------------
| Start or resume session so session data can be accessed.
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| Only allow password update through POST request.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/shared/setPassword.php" . "?status=error&message=" . urlencode("Invalid request method."));
    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
| Ensure only logged-in users can change their password.
*/

SessionManager::requireLogin();

/*
|--------------------------------------------------------------------------
| CSRF Token Validation
|--------------------------------------------------------------------------
| Verify that the submitted form token matches the session token.
*/

if (
    empty($_POST["csrf_token"])          ||
    empty($_SESSION["csrf_token"])       ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {
    header("Location: ../../../client/shared/setPassword.php" . "?status=error&message=" . urlencode("Security token mismatch. Please try again."));
    exit();
}

/*
|--------------------------------------------------------------------------
| Input Collection
|--------------------------------------------------------------------------
| Retrieve password fields from the submitted form.
*/

$currentPassword = trim($_POST["currentPassword"] ?? "");
$newPassword     = trim($_POST["newPassword"]     ?? "");
$confirmPassword = trim($_POST["confirmPassword"] ?? "");

/*
|--------------------------------------------------------------------------
| Basic Input Validation
|--------------------------------------------------------------------------
| Ensure all password fields are filled in.
=== check that the password has equal value and data type
*/

if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
    header("Location: ../../../client/shared/setPassword.php" . "?status=error&message=" . urlencode("All fields are required."));
    exit();
}

/*
|--------------------------------------------------------------------------
| Password Change Processing
|--------------------------------------------------------------------------
| Delegate password verification, validation, hashing, and update
| to the AccountService business logic.
*/

$accountService = new AccountService();

$result = $accountService->changePassword(
    $_SESSION["userID"],
    $currentPassword,
    $newPassword,
    $confirmPassword
);

/*
|--------------------------------------------------------------------------
| CSRF Token Rotation
|--------------------------------------------------------------------------
| Generate a new token after processing to prevent token reuse.
*/

$_SESSION["csrf_token"] = bin2hex(random_bytes(32));

/*
|--------------------------------------------------------------------------
| Result Status Handling
|--------------------------------------------------------------------------
| Convert service result into success or error status.
*/

$status  = $result["success"] ? "success" : "error";
$message = urlencode($result["message"]);

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
| Redirect user back to set password page with result message.
*/

header("Location: ../../../client/shared/setPassword.php"
    . "?status={$status}&message={$message}");
exit();
