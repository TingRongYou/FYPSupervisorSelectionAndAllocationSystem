<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../data/dao/RequestDAO.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

function redirectWithMessage($requestID, $status, $message) {

    header(
        "Location: ../../../client/supervisor/supervisorRequestDecision.php?requestID="
        . urlencode((string) $requestID)
        . "&status="
        . urlencode($status)
        . "&message="
        . urlencode($message)
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../../client/supervisor/supervisorIncomingRequests.php");
    exit;
}

$requestID = (int) ($_POST["requestID"] ?? 0);
$csrfToken = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrfToken)
) {

    redirectWithMessage($requestID, "error", "Invalid session token. Please try again.");
}

$decisionStatus = trim($_POST["decisionStatus"] ?? "");
$supervisorComment = trim($_POST["supervisorComment"] ?? "");
$allowedStatuses = [
    "Accepted",
    "Rejected"
];

if ($requestID <= 0 || !in_array($decisionStatus, $allowedStatuses, true)) {

    redirectWithMessage($requestID, "error", "Invalid decision request.");
}

$requestDAO = new RequestDAO();
$result = $requestDAO->processSupervisorDecision(
    $requestID,
    $_SESSION["userID"],
    $decisionStatus,
    $supervisorComment
);

if (!$result["success"]) {

    redirectWithMessage($requestID, "error", $result["message"]);
}

redirectWithMessage(
    $requestID,
    "success",
    $result["message"]
);

?>




