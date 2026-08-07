<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/TemporalPhaseEngine.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$timeline = TemporalPhaseEngine::getInstance()->getPhasePayload();

function phaseClass($status) {
    $status = strtolower((string) $status);

    if ($status === "active") {
        return "active";
    }

    if ($status === "completed") {
        return "completed";
    }

    return "upcoming";
}

function formatDateTimeText($value) {
    if (!$value) {
        return "-";
    }

    return date("d M Y, h:i A", strtotime($value));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Timeline & Milestones", "student"); 
    ?>
    <script>
        window.ssasTimelineData = <?php echo json_encode($timeline); ?>;
    </script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo ssasPortalSidebar("timeline"); ?>
        <main class="main">
            <div class="page-shell">
                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Academic Timeline</p>
                        <h1>Timeline & Milestones</h1>
                        <p class="subtitle">Monitor the active academic phase and proposal submission lock using the central SSAS server clock.</p>
                    </div>
                    <span class="server-pill" id="serverClock">
                        Server Time: <?php echo ssasEscape($timeline["serverTime"]); ?>
                    </span>
                </section>

                <section class="hero">
                    <div class="hero-grid">
                        <div>
                            <p class="phase-label" id="phaseStatus"><?php echo ssasEscape($timeline["phaseStatus"]); ?></p>
                            <h2 class="phase-title" id="phaseTitle"><?php echo ssasEscape($timeline["activePhaseName"]); ?></h2>
                            <p class="phase-message" id="phaseMessage"><?php echo ssasEscape($timeline["message"]); ?></p>
                        </div>
                        <div class="countdown-box">
                            <p class="countdown-label">Remaining Time</p>
                            <div class="countdown-value" id="countdownValue"><?php echo ssasEscape($timeline["remainingText"]); ?></div>
                            <div class="countdown-caption" id="countdownCaption">
                                <?php echo ssasEscape($timeline["endTimestamp"] ? "Ends " . formatDateTimeText($timeline["endTimestamp"]) : "No active deadline"); ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="status-grid">
                    <article class="info-card">
                        <p class="info-label">Submission Access</p>
                        <p class="info-value" id="submissionAccess">
                            <?php echo $timeline["canSubmitProposal"] ? "Unlocked" : "Locked"; ?>
                        </p>
                    </article>
                    <article class="info-card">
                        <p class="info-label">Current Phase Start</p>
                        <p class="info-value" id="phaseStart"><?php echo ssasEscape(formatDateTimeText($timeline["startTimestamp"])); ?></p>
                    </article>
                    <article class="info-card">
                        <p class="info-label">Current Phase End</p>
                        <p class="info-value" id="phaseEnd"><?php echo ssasEscape(formatDateTimeText($timeline["endTimestamp"])); ?></p>
                    </article>
                </section>

                <section class="phase-panel">
                    <div class="panel-heading">
                        <h2>Academic Phase Timeline</h2>
                        <span class="updated" id="updatedAt">Updated from server clock</span>
                    </div>

                    <div id="phaseRows">
                        <?php if (empty($timeline["phases"])): ?>
                            <p class="empty-state">No academic phases are configured yet. Please contact the administrator.</p>
                        <?php else: ?>
                            <?php foreach ($timeline["phases"] as $phase): ?>
                                <div class="phase-row">
                                    <div class="phase-name"><?php echo ssasEscape($phase["phaseName"]); ?></div>
                                    <div class="date-text">Start: <?php echo ssasEscape(formatDateTimeText($phase["startTimestamp"])); ?></div>
                                    <div class="date-text">End: <?php echo ssasEscape(formatDateTimeText($phase["endTimestamp"])); ?></div>
                                    <span class="phase-badge <?php echo ssasEscape(phaseClass($phase["status"])); ?>"><?php echo ssasEscape($phase["status"]); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
