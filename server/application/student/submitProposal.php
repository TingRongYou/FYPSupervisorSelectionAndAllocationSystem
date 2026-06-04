<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/RequestWorkflowManager.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../../client/student/studentDiscovery.php?status=error&message=Invalid request method");
    exit();
}

SessionManager::requireRole("Student");

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {

    header("Location: ../../../client/student/studentDiscovery.php?status=error&message=Invalid CSRF token");
    exit();
}

$supervisorID = trim($_POST["supervisorID"] ?? "");
$projectTitle = trim($_POST["projectTitle"] ?? "");
$requestID = (int) ($_POST["requestID"] ?? 0);

$manager = new RequestWorkflowManager();
$result = $manager->submitProposal(
    $_SESSION["userID"],
    $supervisorID,
    $projectTitle,
    $_FILES["proposalPDF"] ?? [],
    $requestID
);

$status = $result["success"] ? "success" : "error";
$message = urlencode($result["message"]);

if ($result["success"]) {

    header("Location: ../../../client/student/studentApplicationStatus.php?status={$status}&message={$message}");
    exit();
}

$redirectUrl =
    "../../../client/student/submitProposalForm.php?supervisorID=" .
    urlencode($supervisorID) .
    ($requestID > 0 ? "&requestID=" . urlencode((string) $requestID) : "") .
    "&status={$status}&message={$message}";

header("Location: " . $redirectUrl);
exit();

?>
