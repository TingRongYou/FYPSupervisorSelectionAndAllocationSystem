<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$studentID = $_SESSION["userID"];
$requestDAO = new RequestDAO();
$requestDAO->expireTimedOutRequestsByStudent($studentID); // Automatically reject after 72 hours
$applications = $requestDAO->getApplicationsByStudent($studentID); // Get student application details

// Translate database input to function output (CSS)
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

    return "pending"; // Default as pending
}

// Label text
function statusLabel($status) {
    if ($status === "Rejected-Timeout") {
        return "Rejected Timeout";
    }

    if ($status === "Proposal Requested") {
        return "Proposal Requested";
    }

    return $status;
}

// E.g., 10 Jul 2026 02:30 PM
function formatDateText($value) {
    if (!$value) {
        return "-";
    }

    return date("d M Y, h:i A", strtotime($value));
}

function countdownText($expiresAt) {
    if (!$expiresAt) { // If empty, false or NULL
        return "-";
    }

    $remaining = strtotime($expiresAt) - time(); // Calculate differences between expiry date and current time

    if ($remaining <= 0) {
        return "Expired";
    }

    $hours = floor($remaining / 3600); // Round to nearest whole hour
    $minutes = floor(($remaining % 3600) / 60); // Round to nearest minutes

    // Ensure output is like 02h 05m
    return str_pad((string) $hours, 2, "0", STR_PAD_LEFT) . 
        "h " .
        str_pad((string) $minutes, 2, "0", STR_PAD_LEFT) .
        "m";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Application Status", "student"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo ssasPortalSidebar("application-status"); ?>
        <main class="main">
            <div class="status-shell">
                <?php echo ssasStatusMessage(); ?>
                <section class="page-header student-hero">
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

                <?php if (empty($applications)): ?> <!-- If no applications -->
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
                                    <p class="project-title"><?php echo ssasEscape($application["projectTitle"]); ?></p>
                                    <p class="project-sub">Request ID: <?php echo ssasEscape($application["requestID"]); ?></p>
                                </div>
                                <div>
                                    <p class="project-title"><?php echo ssasEscape($application["supervisorName"]); ?></p>
                                    <p class="project-sub"><?php echo ssasEscape($application["employmentCategory"]); ?>, <?php echo ssasEscape($application["programme"]); ?></p>
                                </div>
                                <span class="muted"><?php echo ssasEscape(formatDateText($application["applicationDate"])); ?></span>
                                <span><span class="badge <?php echo ssasEscape($class); ?>"><?php echo ssasEscape(statusLabel($application["decisionStatus"])); ?></span></span>
                                <div>
                                    <?php if ($isPending): ?>
                                        <span class="countdown" data-expiry="<?php echo ssasEscape($expiryMs); ?>">
                                            <?php echo ssasEscape(countdownText($application["ttlExpirationTimestamp"])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="comment"><?php echo ssasEscape($application["supervisorComment"] ?: "No supervisor comment recorded."); ?></span>
                                    <?php endif; ?>
                                </div>
                                    <!-- Dynamic action button based on proposal status-->
                                <div>
                                    <div class="action-stack">
                                    <?php if (trim((string) $application["proposalPDFPath"]) !== ""): ?> <!-- If there is a proposal path, then view PDF-->
                                        <a class="row-action secondary" href="studentProposalDetails.php?requestID=<?php echo ssasEscape($application["requestID"]); ?>">View Details</a>
                                    <?php endif; ?>
                                    <?php if ($isProposalRequested || $canResubmitRejectedProposal): ?> <!-- Submit or resubmit button-->
                                        <a class="row-action" href="submitProposalForm.php?supervisorID=<?php echo urlencode($application["supervisorID"]); ?>&requestID=<?php echo ssasEscape($application["requestID"]); ?>">
                                            <?php echo $canResubmitRejectedProposal ? "Resubmit Proposal" : "Submit Proposal"; ?>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Default state-->
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
