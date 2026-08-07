<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/TemporalPhaseEngine.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$timeline = TemporalPhaseEngine::getInstance()->getPhasePayload();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline & Milestones | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <script>
        window.ssasTimelineData = <?php echo json_encode($timeline); ?>;
    </script>
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("timeline"); ?>
        <main class="main">
            <div class="page-shell">
                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Academic Timeline</p>
                        <h1>Timeline & Milestones</h1>
                        <p class="subtitle">Monitor the active academic phase and proposal submission lock using the central SSAS server clock.</p>
                    </div>
                    <span class="server-pill" id="serverClock">
                        Server Time: <?php echo e($timeline["serverTime"]); ?>
                    </span>
                </section>

                <section class="hero">
                    <div class="hero-grid">
                        <div>
                            <p class="phase-label" id="phaseStatus"><?php echo e($timeline["phaseStatus"]); ?></p>
                            <h2 class="phase-title" id="phaseTitle"><?php echo e($timeline["activePhaseName"]); ?></h2>
                            <p class="phase-message" id="phaseMessage"><?php echo e($timeline["message"]); ?></p>
                        </div>
                        <div class="countdown-box">
                            <p class="countdown-label">Remaining Time</p>
                            <div class="countdown-value" id="countdownValue"><?php echo e($timeline["remainingText"]); ?></div>
                            <div class="countdown-caption" id="countdownCaption">
                                <?php echo e($timeline["endTimestamp"] ? "Ends " . formatDateTimeText($timeline["endTimestamp"]) : "No active deadline"); ?>
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
                        <p class="info-value" id="phaseStart"><?php echo e(formatDateTimeText($timeline["startTimestamp"])); ?></p>
                    </article>
                    <article class="info-card">
                        <p class="info-label">Current Phase End</p>
                        <p class="info-value" id="phaseEnd"><?php echo e(formatDateTimeText($timeline["endTimestamp"])); ?></p>
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
                                    <div class="phase-name"><?php echo e($phase["phaseName"]); ?></div>
                                    <div class="date-text">Start: <?php echo e(formatDateTimeText($phase["startTimestamp"])); ?></div>
                                    <div class="date-text">End: <?php echo e(formatDateTimeText($phase["endTimestamp"])); ?></div>
                                    <span class="phase-badge <?php echo e(phaseClass($phase["status"])); ?>"><?php echo e($phase["status"]); ?></span>
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
