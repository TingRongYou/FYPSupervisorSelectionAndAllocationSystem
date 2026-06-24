<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$studentID = $_SESSION["userID"];
$requestDAO = new RequestDAO();
$requestDAO->expireTimedOutRequestsByStudent($studentID);
$applications = $requestDAO->getApplicationsByStudent($studentID);

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function statusClass($status) {

    $normalized = strtolower(trim((string) $status));

    if ($normalized === "accepted") {

        return "accepted";
    }

    if ($normalized === "rejected" || $normalized === "rejected-timeout") {

        return "rejected";
    }

    if ($normalized === "withdrawn") {

        return "withdrawn";
    }

    if ($normalized === "auto-allocated") {

        return "auto-allocated";
    }

    if ($normalized === "proposal requested") {

        return "requested";
    }

    return "pending";
}

function statusLabel($status) {

    if ($status === "Rejected-Timeout") {

        return "Rejected Timeout";
    }

    if ($status === "Proposal Requested") {

        return "Proposal Requested";
    }

    return $status;
}

function formatDateText($value) {

    if (!$value) {

        return "-";
    }

    return date("d M Y, h:i A", strtotime($value));
}

function countdownText($expiresAt) {

    if (!$expiresAt) {

        return "-";
    }

    $remaining = strtotime($expiresAt) - time();

    if ($remaining <= 0) {

        return "Expired";
    }

    $hours = floor($remaining / 3600);
    $minutes = floor(($remaining % 3600) / 60);

    return str_pad((string) $hours, 2, "0", STR_PAD_LEFT) .
        "h " .
        str_pad((string) $minutes, 2, "0", STR_PAD_LEFT) .
        "m";
}

function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class = $_GET["status"] === "success" ? "success" : "error";

    return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("application-status"); ?>
        <main class="main">
            <div class="status-shell">
                <?php echo statusMessage(); ?>
                <section class="page-header">
                    <div>
                        <p class="eyebrow">Request & Proposal</p>
                        <h1>Application Status</h1>
                        <p class="subtitle">Monitor submitted project proposals, supervisor decisions, and active response countdowns.</p>
                    </div>
                    <a class="browse-btn" href="studentDiscovery.php">Browse Supervisors</a>
                </section>

                <?php
                    $pendingCount = 0;
                    $acceptedCount = 0;
                    $rejectedCount = 0;
                    $withdrawnCount = 0;

                    foreach ($applications as $application) {
                        $status = $application["decisionStatus"];
                        if ($status === "Pending" || $status === "Proposal Requested") {
                            $pendingCount++;
                        } elseif ($status === "Accepted") {
                            $acceptedCount++;
                        } elseif ($status === "Withdrawn") {
                            $withdrawnCount++;
                        } else {
                            $rejectedCount++;
                        }
                    }
                ?>

                <section class="summary-grid">
                    <article class="summary-card"><span>Total Proposals</span><strong><?php echo count($applications); ?></strong></article>
                    <article class="summary-card"><span>Pending</span><strong><?php echo $pendingCount; ?></strong></article>
                    <article class="summary-card"><span>Accepted</span><strong><?php echo $acceptedCount; ?></strong></article>
                    <article class="summary-card"><span>Closed</span><strong><?php echo $rejectedCount + $withdrawnCount; ?></strong></article>
                </section>

                <?php if (empty($applications)): ?>
                    <section class="empty-state">
                        <strong>My Empty Dashboard</strong>
                        You have not submitted any project proposals yet. Browse supervisors with open slots to start your application.
                        <br>
                        <a class="browse-btn" href="studentDiscovery.php">Browse Supervisors</a>
                    </section>
                <?php else: ?>
                    <section class="status-panel">
                        <div class="status-row header">
                            <span>Proposal</span>
                            <span>Supervisor</span>
                            <span>Submitted</span>
                            <span>Status</span>
                            <span>TTL / Comment</span>
                            <span>Action</span>
                        </div>

                        <?php foreach ($applications as $application): ?>
                            <?php
                                $class = statusClass($application["decisionStatus"]);
                                $isPending = $application["decisionStatus"] === "Pending";
                                $isProposalRequested = $application["decisionStatus"] === "Proposal Requested";
                                $canResubmitRejectedProposal =
                                    $application["decisionStatus"] === "Rejected" &&
                                    !empty($application["allocationID"]);
                                $expiryMs = $isPending && !empty($application["ttlExpirationTimestamp"])
                                    ? ((int) strtotime($application["ttlExpirationTimestamp"]) * 1000)
                                    : 0;
                            ?>
                            <article class="status-row">
                                <div>
                                    <p class="project-title"><?php echo e($application["projectTitle"]); ?></p>
                                    <p class="project-sub">Request ID: <?php echo e($application["requestID"]); ?></p>
                                </div>
                                <div>
                                    <p class="project-title"><?php echo e($application["supervisorName"]); ?></p>
                                    <p class="project-sub"><?php echo e($application["employmentCategory"]); ?>, <?php echo e($application["programme"]); ?></p>
                                </div>
                                <span class="muted"><?php echo e(formatDateText($application["applicationDate"])); ?></span>
                                <span><span class="badge <?php echo e($class); ?>"><?php echo e(statusLabel($application["decisionStatus"])); ?></span></span>
                                <div>
                                    <?php if ($isPending): ?>
                                        <span class="countdown" data-expiry="<?php echo e($expiryMs); ?>">
                                            <?php echo e(countdownText($application["ttlExpirationTimestamp"])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="comment"><?php echo e($application["supervisorComment"] ?: "No supervisor comment recorded."); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="action-stack">
                                    <?php if (trim((string) $application["proposalPDFPath"]) !== ""): ?>
                                        <a class="row-action secondary" href="studentProposalDetails.php?requestID=<?php echo e($application["requestID"]); ?>">View Details</a>
                                    <?php endif; ?>
                                    <?php if ($isProposalRequested || $canResubmitRejectedProposal): ?>
                                        <a class="row-action" href="submitProposalForm.php?supervisorID=<?php echo urlencode($application["supervisorID"]); ?>&requestID=<?php echo e($application["requestID"]); ?>">
                                            <?php echo $canResubmitRejectedProposal ? "Resubmit Proposal" : "Submit Proposal"; ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (
                                        trim((string) $application["proposalPDFPath"]) === "" &&
                                        !$isProposalRequested &&
                                        !$canResubmitRejectedProposal
                                    ): ?>
                                        <span class="muted">-</span>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>