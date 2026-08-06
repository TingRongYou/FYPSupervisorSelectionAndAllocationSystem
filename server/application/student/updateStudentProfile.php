<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/StudentProfileFacade.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/student/profile.php?status=error&message=Invalid request method");
    exit();
}

SessionManager::requireRole("Student");

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    header("Location: ../../../client/student/profile.php?status=error&message=Invalid CSRF token");
    exit();
}

$facade = new StudentProfileFacade();
$result = $facade->updateProfile(
    $_SESSION["userID"],
    $_POST,
    $_FILES["avatarFile"] ?? []
);

$status = $result["success"] ? "success" : "error";
$message = urlencode($result["message"]);

header("Location: ../../../client/student/profile.php?status={$status}&message={$message}");
exit();

?>
