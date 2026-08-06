<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/StudentReviewService.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/student/studentReview.php?status=error&message=Invalid request method");
    exit();
}

SessionManager::requireRole("Student");

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {

    header("Location: ../../../client/student/studentReview.php?status=error&message=Invalid CSRF token");
    exit();
}

$service = new StudentReviewService();
$result = $service->submitReview(
    $_SESSION["userID"],
    $_POST["allocationID"] ?? 0,
    $_POST["starRating"] ?? 0,
    $_POST["textFeedback"] ?? "",
    isset($_POST["isAnonymous"])
);

$status = $result["success"] ? "success" : "error";
$message = urlencode($result["message"]);

header("Location: ../../../client/student/studentReview.php?status={$status}&message={$message}");
exit();

?>
