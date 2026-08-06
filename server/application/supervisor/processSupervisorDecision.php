<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/RequestDecisionService.php";

/*
|--------------------------------------------------------------------------
| Session and Access Control
|--------------------------------------------------------------------------
| Start session and ensure only supervisors can process decisions.
*/

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

/*
|--------------------------------------------------------------------------
| Redirect Helper Function
|--------------------------------------------------------------------------
| Redirect back to decision page with request ID, status, and message.
*/

function redirectWithMessage($requestID, $status, $message) {
    header(
        "Location: ../../../client/supervisor/supervisorRequestDecision.php?requestID="
        . urlencode((string) $requestID) // encode
        . "&status="
        . urlencode($status)
        . "&message="
        . urlencode($message)
    );
    exit;
}
/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| Only allow supervisor decision submission through POST request.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/supervisor/supervisorIncomingRequests.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Form Data Retrieval
|--------------------------------------------------------------------------
| Retrieve request ID and CSRF token from submitted form.
*/

$requestID = (int) ($_POST["requestID"] ?? 0);
$csrfToken = $_POST["csrf_token"] ?? "";

/*
|--------------------------------------------------------------------------
| CSRF Token Validation
|--------------------------------------------------------------------------
| Ensure the request comes from the official decision form.
*/

if (empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    redirectWithMessage($requestID, "error", "Invalid session token. Please try again.");
}

/*
|--------------------------------------------------------------------------
| Decision Input Retrieval
|--------------------------------------------------------------------------
| Retrieve supervisor decision and comment from the form.
*/

$decisionStatus = trim($_POST["decisionStatus"] ?? "");
$supervisorComment = trim($_POST["supervisorComment"] ?? "");

/*
|--------------------------------------------------------------------------
| Allowed Decision Statuses
|--------------------------------------------------------------------------
| Only Accepted and Rejected decisions are allowed.
*/

$allowedStatuses = ["Accepted", "Rejected"];

/*
|--------------------------------------------------------------------------
| Decision Validation
|--------------------------------------------------------------------------
| Validate request ID and decision status before processing.
*/

if ($requestID <= 0 || !in_array($decisionStatus, $allowedStatuses, true)) {
    redirectWithMessage($requestID, "error", "Invalid decision request.");
}

/*
|--------------------------------------------------------------------------
| Request Decision Service Initialization
|--------------------------------------------------------------------------
| Create service object to process supervisor decision through State pattern.
*/

$requestDecisionService = new RequestDecisionService();

/*
|--------------------------------------------------------------------------
| Supervisor Decision Processing
|--------------------------------------------------------------------------
| Save supervisor decision, verify ownership, update request status,
| and apply related business rules.
*/

$result = $requestDecisionService->processDecision(
    $requestID,
    $_SESSION["userID"],
    $decisionStatus,
    $supervisorComment
);

/*
|--------------------------------------------------------------------------
| Failed Decision Handling
|--------------------------------------------------------------------------
| Redirect back with error message if processing fails.
*/

if (!$result["success"]) {
    redirectWithMessage($requestID, "error", $result["message"]);
}

/*
|--------------------------------------------------------------------------
| Success Redirect
|--------------------------------------------------------------------------
| Redirect back with success message after decision is processed.
*/

redirectWithMessage($requestID, "success", $result["message"]);

?>
