<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/TemporalPhaseEngine.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$timeline =
    TemporalPhaseEngine::getInstance()->getPhasePayload();

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function phaseClass($status) {

    $status =
        strtolower((string) $status);

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
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 28px 32px 50px; min-width: 0; overflow-x: hidden; }
        .page-shell { max-width: 1180px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 22px; }
        h1 { margin: 0 0 6px; font-size: 28px; color: #172033; }
        .subtitle { margin: 0; color: #5d7085; font-size: 15px; line-height: 1.5; }
        .server-pill { padding: 8px 12px; border-radius: 999px; background: #eaf3ff; color: #0b66d8; font-size: 13px; font-weight: 900; white-space: nowrap; }

        .hero { background: #0b5ee8; color: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 14px 32px rgba(11,94,232,.18); margin-bottom: 18px; }
        .hero-grid { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(260px, .7fr); gap: 18px; align-items: stretch; }
        .phase-label { margin: 0 0 8px; font-size: 13px; letter-spacing: .8px; text-transform: uppercase; font-weight: 900; color: rgba(255,255,255,.74); }
        .phase-title { margin: 0; font-size: 30px; line-height: 1.1; }
        .phase-message { margin: 12px 0 0; font-size: 15px; color: rgba(255,255,255,.9); line-height: 1.5; }
        .countdown-box { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22); border-radius: 10px; padding: 18px; min-height: 136px; display: flex; flex-direction: column; justify-content: center; }
        .countdown-label { margin: 0 0 10px; color: rgba(255,255,255,.78); font-size: 13px; text-transform: uppercase; letter-spacing: .8px; font-weight: 900; }
        .countdown-value { font-size: 36px; line-height: 1; font-weight: 900; letter-spacing: .5px; }
        .countdown-caption { margin-top: 10px; color: rgba(255,255,255,.82); font-size: 14px; }

        .status-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .info-card { background: #fff; border: 1px solid #d9e7f3; border-radius: 10px; padding: 18px; box-shadow: 0 4px 14px rgba(11,79,138,.05); }
        .info-label { margin: 0 0 8px; color: #6f8398; font-size: 13px; text-transform: uppercase; letter-spacing: .8px; font-weight: 900; }
        .info-value { margin: 0; color: #172033; font-size: 17px; font-weight: 800; }

        .phase-panel { background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; padding: 18px; box-shadow: 0 4px 14px rgba(11,79,138,.05); }
        .panel-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .panel-heading h2 { margin: 0; font-size: 18px; color: #172033; }
        .updated { color: #6f8398; font-size: 13px; font-weight: 700; }
        .phase-row { display: grid; grid-template-columns: 170px 1fr 1fr 120px; gap: 14px; align-items: center; border-top: 1px solid #edf2f7; padding: 16px 0; }
        .phase-row:first-of-type { border-top: 0; }
        .phase-name { font-weight: 900; color: #172033; }
        .date-text { color: #526a7f; font-size: 14px; }
        .phase-badge { justify-self: start; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }
        .phase-badge.active { background: #dcfce7; color: #118549; }
        .phase-badge.completed { background: #e2e8f0; color: #64748b; }
        .phase-badge.upcoming { background: #eaf3ff; color: #0b66d8; }
        .empty-state { padding: 18px; color: #6f8398; background: #f8fbfe; border-radius: 8px; }

        @media (max-width: 900px) {
            .main { padding: 18px 14px 42px; }
            .page-header, .hero-grid { display: block; }
            .server-pill { display: inline-block; margin-top: 12px; }
            .countdown-box { margin-top: 16px; }
            .status-grid, .phase-row { grid-template-columns: 1fr; }
            .phase-badge { justify-self: start; }
        }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("timeline"); ?>
        <main class="main">
            <div class="page-shell">
                <section class="page-header">
                    <div>
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

    <script>
        let timelinePayload = <?php echo json_encode($timeline); ?>;
        let serverOffsetMs = (Number(timelinePayload.serverEpoch || 0) * 1000) - Date.now();

        function pad(value) {
            return String(value).padStart(2, "0");
        }

        function formatRemaining(seconds) {
            seconds = Math.max(0, Math.floor(seconds));
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${pad(days)}d ${pad(hours)}h ${pad(minutes)}m`;
        }

        function formatDateTime(value) {
            if (!value) return "-";
            const date = new Date(value.replace(" ", "T"));
            return date.toLocaleString("en-MY", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit"
            });
        }

        function currentServerNow() {
            return Date.now() + serverOffsetMs;
        }

        function renderCountdown() {
            const target = timelinePayload.activePhase
                ? timelinePayload.endTimestamp
                : (timelinePayload.startTimestamp || null);

            let remainingSeconds = 0;

            if (target) {
                remainingSeconds = Math.max(
                    0,
                    Math.floor((new Date(target.replace(" ", "T")).getTime() - currentServerNow()) / 1000)
                );
            }

            document.getElementById("countdownValue").textContent =
                formatRemaining(remainingSeconds);
        }

        function renderPayload(payload) {
            timelinePayload = payload;
            serverOffsetMs = (Number(payload.serverEpoch || 0) * 1000) - Date.now();

            document.getElementById("serverClock").textContent =
                "Server Time: " + payload.serverTime;
            document.getElementById("phaseStatus").textContent =
                payload.phaseStatus;
            document.getElementById("phaseTitle").textContent =
                payload.activePhaseName;
            document.getElementById("phaseMessage").textContent =
                payload.message;
            document.getElementById("submissionAccess").textContent =
                payload.canSubmitProposal ? "Unlocked" : "Locked";
            document.getElementById("phaseStart").textContent =
                formatDateTime(payload.startTimestamp);
            document.getElementById("phaseEnd").textContent =
                formatDateTime(payload.endTimestamp);
            document.getElementById("countdownCaption").textContent =
                payload.endTimestamp ? "Ends " + formatDateTime(payload.endTimestamp) : "No active deadline";

            renderCountdown();
        }

        async function refreshTimeline() {
            try {
                const response = await fetch("../../server/application/student/getTimelineStatus.php", {
                    credentials: "same-origin"
                });

                if (!response.ok) return;

                renderPayload(await response.json());
            } catch (error) {
                return;
            }
        }

        renderCountdown();
        setInterval(renderCountdown, 1000);
        setInterval(refreshTimeline, 60000);
    </script>
</body>
</html>
