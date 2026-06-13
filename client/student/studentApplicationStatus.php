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
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 34px 40px 56px; min-width: 0; }
        .status-shell { max-width: 1280px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 22px; }
        .eyebrow { margin: 0 0 8px; color: #0b66d8; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        h1 { margin: 0; color: #172033; font-size: 30px; line-height: 1.1; }
        .subtitle { margin: 8px 0 0; color: #5d7085; font-size: 15px; line-height: 1.5; }
        .browse-btn { min-height: 40px; border-radius: 7px; background: #003f8f; color: #fff; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 900; font-size: 14px; white-space: nowrap; }
        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
        .summary-card { background: #fff; border: 1px solid #d9e7f3; border-radius: 10px; padding: 18px; box-shadow: 0 6px 16px rgba(11,79,138,.06); }
        .summary-card span { display: block; color: #7c8da0; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 8px; }
        .summary-card strong { color: #003f8f; font-size: 28px; line-height: 1; }
        .status-panel { background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; box-shadow: 0 8px 22px rgba(11,79,138,.07); overflow: hidden; }
        .status-row { display: grid; grid-template-columns: 1.2fr 1fr .75fr .75fr .9fr .7fr; gap: 16px; align-items: center; padding: 18px 20px; border-bottom: 1px solid #edf2f7; }
        .status-row.header { background: #f8fbff; color: #7c8da0; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: .8px; }
        .status-row:last-child { border-bottom: none; }
        .project-title { margin: 0 0 5px; color: #172033; font-size: 15px; font-weight: 900; line-height: 1.35; }
        .project-sub { margin: 0; color: #6b7f91; font-size: 14px; line-height: 1.4; }
        .muted { color: #6b7f91; font-size: 14px; }
        .badge { display: inline-flex; align-items: center; justify-content: center; min-height: 24px; padding: 0 10px; border-radius: 999px; font-size: 12px; font-weight: 900; text-transform: uppercase; white-space: nowrap; }
        .badge.pending { background: #fff4cc; color: #9a6400; }
        .badge.accepted { background: #dcfce7; color: #118549; }
        .badge.rejected { background: #fee2e2; color: #c02d2d; }
        .badge.withdrawn { background: #e2e8f0; color: #64748b; }
        .badge.auto-allocated { background: #eaf3ff; color: #0d5be8; }
        .badge.requested { background: #eaf3ff; color: #0d5be8; }
        .countdown { color: #003f8f; font-size: 15px; font-weight: 900; }
        .comment { color: #526a7f; font-size: 14px; line-height: 1.45; }
        .action-stack { display: flex; flex-wrap: wrap; gap: 7px; }
        .row-action { min-height: 34px; border-radius: 7px; background: #003f8f; color: #fff; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 13px; font-weight: 900; white-space: nowrap; }
        .row-action.secondary { background: #eaf3ff; color: #0d5be8; }
        .empty-state { background: #fff; border: 1px dashed #aac7df; border-radius: 12px; padding: 34px; color: #526a7f; font-size: 15px; line-height: 1.55; }
        .empty-state strong { display: block; color: #172033; font-size: 18px; margin-bottom: 8px; }
        .empty-state .browse-btn { margin-top: 16px; }
        @media (max-width: 1040px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .status-row { grid-template-columns: 1fr; gap: 8px; }
            .status-row.header { display: none; }
        }
        @media (max-width: 720px) {
            .main { padding: 24px 18px 46px; }
            .page-header { display: block; }
            .browse-btn { margin-top: 14px; width: 100%; }
            .summary-grid { grid-template-columns: 1fr; }
        }
    </style>
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

                        if ($status === "Pending") {
                            $pendingCount++;
                        } elseif ($status === "Proposal Requested") {
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

    <script>
        function formatRemaining(ms) {
            if (ms <= 0) {
                return "Expired";
            }

            const hours = Math.floor(ms / 3600000);
            const minutes = Math.floor((ms % 3600000) / 60000);
            const seconds = Math.floor((ms % 60000) / 1000);

            return String(hours).padStart(2, "0") + "h " +
                String(minutes).padStart(2, "0") + "m " +
                String(seconds).padStart(2, "0") + "s";
        }

        function tickCountdowns() {
            document.querySelectorAll("[data-expiry]").forEach(function(element) {
                const expiry = Number(element.dataset.expiry || 0);

                if (!expiry) {
                    return;
                }

                element.textContent = formatRemaining(expiry - Date.now());
            });
        }

        tickCountdowns();
        setInterval(tickCountdowns, 1000);
    </script>
</body>
</html>
