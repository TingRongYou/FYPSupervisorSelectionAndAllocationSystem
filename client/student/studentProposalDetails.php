<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$requestID = (int) ($_GET["requestID"] ?? 0);

$requestDAO = new RequestDAO();

$request = $requestID > 0 ? $requestDAO->getApplicationRequestForStudent($requestID, $_SESSION["userID"]) : null;

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function proposalStatusClass($status) {
    $status = strtolower(trim((string) $status));

    if ($status === "accepted") {
        return "accepted";
    }

    if (
        $status === "rejected" ||
        $status === "rejected-timeout"
    ) {
        return "rejected";
    }

    return "pending";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Details | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("application-status"); ?>
        <main class="main">
            <div class="details-shell">
                <div class="breadcrumb"><a href="studentApplicationStatus.php">Application Status</a> &gt; Proposal Details</div>

                <?php if (!$request): ?>
                    <section class="empty-state">The selected proposal was not found or does not belong to your account.</section>
                <?php else: ?>
                    <section class="details-card">
                        <header class="details-header">
                            <div>
                                <p class="eyebrow">Project Proposal</p>
                                <h1><?php echo e($request["projectTitle"]); ?></h1>
                            </div>
                        </header>

                        <section class="decision-panel">
                            <div class="decision-heading">
                                <h2 class="decision-label">Supervisor Decision</h2>
                                <span class="status <?php echo e(proposalStatusClass($request["decisionStatus"])); ?>">
                                    <?php echo e($request["decisionStatus"]); ?>
                                </span>
                            </div>
                            <span class="comment-label">Supervisor Comment</span>
                            <div class="comment"><?php echo e(trim((string) $request["supervisorComment"]) !== "" ? $request["supervisorComment"] : "No supervisor comment has been recorded."); ?></div>
                        </section>

                        <?php if (trim((string) $request["proposalPDFPath"]) !== ""): ?>
                            <?php $proposalUrl = "../../server/application/student/viewProposal.php?requestID=" . urlencode($request["requestID"]); ?>
                            <div class="pdf-viewer">
                                <div class="pdf-toolbar">
                                    <div class="pdf-title">Proposal Document</div>
                                    <a class="pdf-action" href="<?php echo e($proposalUrl); ?>" target="_blank">Open in New Tab</a>
                                </div>
                                <!-- iframe is used to display a webpage in a webpage -->
                                <iframe class="pdf-frame" src="<?php echo e($proposalUrl); ?>" title="Proposal Document"></iframe>
                            </div>
                        <?php else: ?>
                            <section class="empty-state">No proposal PDF has been submitted for this request.</section>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>