<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../data/dao/RequestDAO.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/supervisor/supervisorMySupervisees.php?status=error&message=Invalid request method");
    exit();
}

if (
    empty($_SESSION["csrf_token"]) ||
    empty($_POST["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    header("Location: ../../../client/supervisor/supervisorMySupervisees.php?status=error&message=Invalid session token. Please try again.");
    exit();
}

$allocationID = (int) ($_POST["allocationID"] ?? 0);

if ($allocationID <= 0) {
    header("Location: ../../../client/supervisor/supervisorMySupervisees.php?status=error&message=Invalid allocation record.");
    exit();
}

$requestDAO = new RequestDAO();

$result = $requestDAO->requestProposalForAllocation($allocationID, $_SESSION["userID"]);

$status = $result["success"] ? "success" : "error";

$message = urlencode($result["message"]);

header("Location: ../../../client/supervisor/supervisorMySupervisees.php?status={$status}&message={$message}");
exit();

?>
