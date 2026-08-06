<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../data/dao/RequestDAO.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

function renderProposalError($title, $message, $statusCode = 404) {
    http_response_code($statusCode);
    header("Content-Type: text/html; charset=UTF-8");

    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <style>
        html, body {
            margin: 0;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: #eef6ff;
            color: #172033;
        }
        .proposal-empty {
            min-height: 520px;
            display: grid;
            place-items: center;
            padding: 32px;
        }
        .proposal-empty-card {
            width: min(520px, 100%);
            border: 1px solid #cfe0f2;
            border-radius: 10px;
            background: #ffffff;
            padding: 28px;
            text-align: center;
            box-shadow: 0 14px 34px rgba(34, 63, 94, .08);
        }
        .proposal-empty-icon {
            display: inline-grid;
            place-items: center;
            width: 48px;
            height: 48px;
            margin-bottom: 14px;
            border-radius: 12px;
            background: #eaf3ff;
            color: #0d5be8;
            font-size: 13px;
            font-weight: 900;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 20px;
            line-height: 1.25;
        }
        p {
            margin: 0;
            color: #526a7f;
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <main class=\"proposal-empty\">
        <section class=\"proposal-empty-card\">
            <div class=\"proposal-empty-icon\">PDF</div>
            <h1>" . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "</h1>
            <p>" . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . "</p>
        </section>
    </main>
</body>
</html>";

    exit();
}

$requestID = (int) ($_GET["requestID"] ?? 0);

if ($requestID <= 0) {
    renderProposalError("Proposal Not Found", "The selected proposal request could not be found.");
}

$requestDAO = new RequestDAO();

$request = $requestDAO->getApplicationRequestForStudent($requestID, $_SESSION["userID"]);

if (!$request) {
    http_response_code(403);
    renderProposalError("Access Restricted", "You are not authorized to view this proposal document.", 403);
}

$proposalPath = str_replace("\\", "/", trim((string) ($request["proposalPDFPath"] ?? "")));

$proposalUrlPath = parse_url($proposalPath, PHP_URL_PATH);

if ($proposalUrlPath === null || $proposalUrlPath === false) {
    $proposalUrlPath = $proposalPath;
}

$proposalFileName = basename(rawurldecode($proposalUrlPath));

if (preg_match("/^[A-Za-z0-9_-]+\.pdf$/", $proposalFileName) !== 1) {
    renderProposalError("Proposal File Path Is Invalid", "The saved proposal file reference is invalid. Upload the PDF again.");
}

$storageRoot =realpath(__DIR__ . "/../../../storage/proposals");

$proposalFile = $storageRoot !== false ? realpath($storageRoot . DIRECTORY_SEPARATOR . $proposalFileName) : false;

if (
    $storageRoot === false ||
    $proposalFile === false ||
    strpos($proposalFile, $storageRoot) !== 0 ||
    !is_file($proposalFile)
) {
    renderProposalError("Proposal File Missing", "A proposal record exists, but the PDF file is missing from storage. Upload the PDF again.");
}

header("Content-Type: application/pdf");
header("Content-Length: " . filesize($proposalFile));
header("Content-Disposition: inline; filename=\"proposal-" . $requestID . ".pdf\"");
header("X-Content-Type-Options: nosniff");

readfile($proposalFile);
exit;

?>
