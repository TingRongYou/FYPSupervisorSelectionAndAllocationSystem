<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../data/dao/RequestDAO.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

$requestID =
    (int) ($_GET["requestID"] ?? 0);

if ($requestID <= 0) {

    http_response_code(404);
    exit("Proposal was not found.");
}

$requestDAO =
    new RequestDAO();

$request =
    $requestDAO
    ->getApplicationRequestForSupervisor(
        $requestID,
        $_SESSION["userID"]
    );

if (!$request) {

    http_response_code(403);
    exit("You are not authorized to view this proposal.");
}

$proposalPath =
    (string) ($request["proposalPDFPath"] ?? "");

$proposalPath =
    str_replace("\\", "/", trim($proposalPath));

$proposalUrlPath =
    parse_url($proposalPath, PHP_URL_PATH);

if ($proposalUrlPath === null || $proposalUrlPath === false) {

    $proposalUrlPath =
        $proposalPath;
}

$proposalFileName =
    basename(rawurldecode($proposalUrlPath));

if (preg_match("/^[A-Za-z0-9_-]+\.pdf$/", $proposalFileName) !== 1) {

    http_response_code(404);
    exit("Proposal file path is invalid.");
}

$storageRoot =
    realpath(
        __DIR__ . "/../../../storage/proposals"
    );

if ($storageRoot === false) {

    http_response_code(404);
    exit("Proposal file was not found.");
}

$proposalFile =
    realpath(
        $storageRoot . DIRECTORY_SEPARATOR . $proposalFileName
    );

if (
    $proposalFile === false ||
    strpos($proposalFile, $storageRoot) !== 0 ||
    !is_file($proposalFile)
) {

    http_response_code(404);
    exit("Proposal file was not found.");
}

header("Content-Type: application/pdf");
header("Content-Length: " . filesize($proposalFile));
header("Content-Disposition: inline; filename=\"proposal-" . $requestID . ".pdf\"");
header("X-Content-Type-Options: nosniff");

readfile($proposalFile);
exit;

?>
