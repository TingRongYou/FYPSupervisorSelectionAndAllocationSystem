<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorDiscoveryService.php";
require_once __DIR__ . "/../../server/business/services/TemporalPhaseEngine.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireLogin();
SessionManager::requireRole("Student");

$studentID   = $_SESSION["userID"];
$studentName = $_SESSION["fullName"] ?? "Student";

$discoveryService = new SupervisorDiscoveryService();
$requestDAO       = new RequestDAO();
$requestDAO->expireTimedOutRequestsByStudent($studentID);

$supervisors     = $discoveryService->getRecommendedMatches($studentID);
$hasSavedInterestTags = $discoveryService->hasSavedInterestTags($studentID);
$discoveryList   = array_slice($discoveryService->discoverSupervisors([]), 0, 3);
$requests        = $requestDAO->getRecentApplicationsByStudent($studentID, 2);
$proposalRequests = array_values(
    array_filter(
        $requestDAO->getApplicationsByStudent($studentID),
        function($request) {
            return $request["decisionStatus"] === "Proposal Requested";
        }
    )
);
$pendingRequests = $requestDAO->countPendingRequestsByStudent($studentID);
$allocation      = $requestDAO->getAllocationByStudent($studentID);
$timeline        = TemporalPhaseEngine::getInstance()->getPhasePayload();

$allocationStatus = $allocation ? "Allocated" : ($pendingRequests > 0 ? "Pending" : "Not Started");
$phaseEnd         = !empty($timeline["activePhase"])
    ? ($timeline["endTimestamp"] ?? "")
    : ($timeline["startTimestamp"] ?? "");
$phaseLabel       = !empty($timeline["activePhase"])
    ? ($timeline["activePhaseName"] ?? "No Active Phase")
    : (!empty($timeline["nextPhase"]["phaseName"]) ? "Next: " . $timeline["nextPhase"]["phaseName"] : "No Active Phase");
$serverEpoch      = $timeline["serverEpoch"] ?? time();

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
function initials($name) {
    $parts  = preg_split("/\s+/", trim((string) $name));
    $first  = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "",  0, 1));
    return $first . $second;
}
function requestClass($status) {
    $n = strtolower(trim((string) $status));
    if ($n === "approved" || $n === "accepted") return "approved";
    if ($n === "rejected" || $n === "rejected-timeout") return "rejected";
    if ($n === "withdrawn") return "withdrawn";
    if ($n === "proposal requested") return "pending";
    return "pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }

        .main { flex: 1; padding: 28px 32px 50px; min-width: 0; overflow-x: hidden; }
        .dashboard-shell { max-width: 100%; }

        /* Page header */
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; }
        h1 { margin: 0; color: #172033; font-size: 28px; font-weight: 700; }
        .search-button {
            height: 38px; padding: 0 16px;
            border: 1px solid #dbe6f0; border-radius: 7px;
            background: #fff; color: #0b3760;
            text-decoration: none; font-size: 15px; font-weight: 800;
            display: inline-flex; align-items: center; gap: 7px;
        }
        .search-button svg { width: 14px; height: 14px; }
        .proposal-notice { background: #fff7ed; border: 1px solid #fed7aa; border-left: 4px solid #f97316; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; color: #7c2d12; display: flex; justify-content: space-between; align-items: center; gap: 14px; }
        .proposal-notice strong { display: block; color: #7c2d12; margin-bottom: 3px; }
        .proposal-notice a { min-height: 34px; border-radius: 7px; background: #003f8f; color: #fff; display: inline-flex; align-items: center; justify-content: center; padding: 0 12px; text-decoration: none; font-size: 13px; font-weight: 900; white-space: nowrap; }

        .dashboard-top {
            display: grid;
            grid-template-columns: minmax(360px, 540px) minmax(0, 1fr);
            gap: 20px;
            align-items: stretch;
            margin-bottom: 22px;
        }
        .recommendation-column, .status-column { min-width: 0; }

        /* Recommended toolbar */
        .section-toolbar { margin-bottom: 16px; }
        .selector {
            display: inline-flex; align-items: center; gap: 12px;
            height: 40px; padding: 0 18px; border-radius: 7px;
            background: #003f8f; color: #fff;
            font-size: 15px; font-weight: 800; text-decoration: none;
        }
        .muted-title { color: #5d7085; font-size: 14px; margin: 0 0 18px; font-weight: 600; }

        /* Supervisor cards */
        .supervisor-grid { display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 0; }

        .supervisor-card {
            background: #fff; border: 1px solid #d9e7f3;
            border-radius: 12px; padding: 20px;
            min-height: 226px;
            display: flex; flex-direction: column;
            box-shadow: 0 4px 14px rgba(11,79,138,.06);
        }
        .supervisor-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }

        /* Avatar */
        .avatar {
            width: 56px; height: 56px; border-radius: 10px;
            background: #e9f1fa; color: #0b3760;
            display: grid; place-items: center;
            font-size: 16px; font-weight: 900;
            position: relative; overflow: hidden; flex-shrink: 0;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .avatar::after {
            content: ""; position: absolute;
            right: 3px; bottom: 3px;
            width: 10px; height: 10px;
            border-radius: 50%; border: 2px solid #fff;
            background: #22c55e;
        }
        .avatar.offline::after { background: #94a3b8; }

        /* Status + quota */
        .top-right { text-align: right; }
        .status-pill {
            display: inline-block; padding: 4px 9px;
            border-radius: 999px; font-size: 12px; font-weight: 900;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px;
        }
        .status-pill.online    { background: #dcfce7; color: #118549; }
        .status-pill.offline   { background: #e2e8f0; color: #64748b; }
        .status-pill.available { background: #dcfce7; color: #118549; }
        .status-pill.full      { background: #fee2e2; color: #c02d2d; }
        .quota { color: #9aacc0; font-size: 14px; text-transform: uppercase; letter-spacing: .7px; }
        .quota strong { display: block; color: #172033; font-size: 20px; font-weight: 900; letter-spacing: 0; line-height: 1.1; }

        .supervisor-name { margin: 0 0 5px; color: #172033; font-size: 16px; font-weight: 700; }
        .specialty { color: #5d7085; font-size: 14px; line-height: 1.45; min-height: 32px; }

        .tag-list { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 16px; }
        .tag { padding: 5px 9px; border-radius: 4px; background: #eef3f8; color: #526a7f; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }

        .btn-apply {
            width: 100%; height: 38px; border-radius: 7px;
            background: #003f8f; color: #fff;
            border: none; font-size: 14px; font-weight: 800;
            text-decoration: none; display: flex;
            align-items: center; justify-content: center;
            margin-top: auto; cursor: pointer;
        }
        .btn-apply:hover { background: #002d6b; }
        .btn-apply.disabled {
            background: #eef3f8; color: #a4b3c4;
            pointer-events: none; border: 1px solid #dce8f3;
        }

        /* Stats row */
        .stats-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; height: 100%; margin-bottom: 0; }

        .stat-card {
            background: #fff; border: 1px solid #d9e7f3;
            border-radius: 12px; padding: 20px;
            min-height: 104px;
            box-shadow: 0 4px 14px rgba(11,79,138,.06);
        }
        .stat-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: #eef3f8; display: grid; place-items: center;
        }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-label { color: #9aacc0; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .stat-value { margin-top: 14px; color: #172033; font-size: 32px; font-weight: 900; line-height: 1; }
        .stat-caption { color: #5d7085; font-size: 14px; margin-top: 5px; }

        /* Timer card */
        .timer-card {
            background: #111a2b; color: #fff;
            border-radius: 12px; padding: 20px;
            grid-column: 1 / -1;
            min-height: 104px;
            position: relative; overflow: hidden;
        }
        .timer-card::before {
            content: ""; position: absolute;
            right: -20px; top: -20px;
            width: 120px; height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,.04);
        }
        .timer-phase { font-size: 14px; font-weight: 800; color: #b8c6dc; margin: 0 0 8px; text-transform: uppercase; letter-spacing: .6px; }
        .timer-value { font-size: 36px; font-weight: 900; letter-spacing: 2px; line-height: 1; margin-bottom: 10px; }
        .timer-date { color: #4a9be8; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: .8px; }

        /* Dashboard lower */
        .dashboard-lower { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(240px, .6fr); gap: 22px; }

        .panel-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .panel-heading h2 { margin: 0; color: #172033; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .panel-heading h2 svg { width: 18px; height: 18px; color: #0b3760; }
        .panel-heading a { color: #003f8f; font-size: 14px; font-weight: 800; text-decoration: none; }

        /* Request panel */
        .request-panel {
            background: #fff; border: 1px solid #d9e7f3;
            border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 14px rgba(11,79,138,.06);
        }
        .request-item {
            padding: 16px 18px;
            border-bottom: 1px solid #edf2f7;
            display: grid;
            grid-template-columns: 58px 1fr auto;
            gap: 14px; align-items: start;
        }
        .request-item:last-child { border-bottom: none; }

        .request-avatar {
            width: 54px; height: 54px; border-radius: 50%;
            background: #e9f1fa; display: grid;
            place-items: center; color: #0b3760;
            font-weight: 900; font-size: 15px; overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #cfe0f1, 0 4px 10px rgba(11,79,138,.12);
        }
        .request-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .request-name { margin: 0 0 3px; color: #172033; font-weight: 800; font-size: 15px; }
        .request-expertise { color: #6b7f91; font-size: 14px; margin-bottom: 8px; }

        .request-meta-row { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
        .request-meta-item { display: flex; align-items: center; gap: 4px; color: #8a9caf; font-size: 13px; font-weight: 700; }
        .request-meta-item svg { width: 11px; height: 11px; }

        .request-right { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .request-status {
            padding: 4px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 900; text-transform: uppercase;
            white-space: nowrap;
        }
        .request-status.pending  { background: #eaf3ff; color: #0d5be8; }
        .request-status.rejected { background: #fee2e2; color: #c02d2d; }
        .request-status.approved { background: #dcfce7; color: #118549; }
        .request-status.withdrawn { background: #e2e8f0; color: #64748b; }
        .details-link { color: #003f8f; font-size: 14px; font-weight: 800; text-decoration: none; }

        .empty-state { padding: 28px 18px; color: #6b7f91; font-size: 14px; }

        /* Discovery panel */
        .discovery-panel {
            background: #fff; border: 1px solid #d9e7f3;
            border-radius: 12px; padding: 18px;
            box-shadow: 0 4px 14px rgba(11,79,138,.06);
        }
        .disc-section-label {
            color: #9aacc0; font-size: 14px; font-weight: 900;
            text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 12px; display: block;
        }

        .mini-item {
            display: grid; grid-template-columns: 48px 1fr auto;
            gap: 12px; align-items: center;
            padding: 12px 0; border-bottom: 1px solid #edf2f7;
            text-decoration: none; color: inherit;
        }
        .mini-item:last-of-type { border-bottom: none; }
        .mini-item:hover { background: #fafcff; margin: 0 -4px; padding: 10px 4px; border-radius: 6px; }

        .mini-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: #eef3f8; color: #0b3760;
            display: grid; place-items: center;
            font-size: 14px; font-weight: 900;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #cfe0f1, 0 3px 8px rgba(11,79,138,.12);
        }
        .mini-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mini-name { color: #172033; font-size: 14px; font-weight: 800; display: block; margin-bottom: 2px; }
        .mini-status { font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 4px; }
        .mini-status::before { content: "â—"; font-size: 8px; }
        .mini-status.online    { color: #118549; }
        .mini-status.offline   { color: #64748b; }
        .mini-status.available { color: #118549; }
        .mini-status.full      { color: #c02d2d; }
        .mini-chevron { color: #c4d0dc; font-size: 16px; font-weight: 300; }
        .mini-status { gap: 6px; }
        .mini-status::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            flex: 0 0 6px;
        }
        .mini-chevron {
            color: #8aa0b5;
            font-size: 0;
            font-weight: 900;
            letter-spacing: .4px;
        }
        .mini-chevron::after {
            content: "View";
            font-size: 13px;
        }

        .explore-btn {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 38px; margin-top: 14px;
            border-radius: 7px; border: 1px solid #dce8f3;
            background: #f6f8fb; color: #0b3760;
            font-size: 14px; font-weight: 800; text-decoration: none;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .explore-btn:hover { background: #eef3f8; }

        /* Responsive */
        @media (max-width: 1100px) {
            .dashboard-top { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .timer-card { grid-column: auto; }
        }
        @media (max-width: 820px) {
            .supervisor-grid, .stats-row, .dashboard-lower { grid-template-columns: 1fr; }
            .timer-card { grid-column: auto; }
            .main { padding: 16px 14px 50px; }
        }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("dashboard"); ?>
        <main class="main">
            <div class="dashboard-shell">

                <!-- Page header -->
                <section class="page-header">
                    <h1>Student Dashboard</h1>
                    <a class="search-button" href="studentDiscovery.php">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        Search Supervisor
                    </a>
                </section>

                <?php if (!empty($proposalRequests)): ?>
                    <?php $firstProposalRequest = $proposalRequests[0]; ?>
                    <section class="proposal-notice">
                        <div>
                            <strong>Proposal Requested</strong>
                            <?php echo e($firstProposalRequest["supervisorName"]); ?> has requested your project proposal after allocation.
                        </div>
                        <a href="submitProposalForm.php?supervisorID=<?php echo urlencode($firstProposalRequest["supervisorID"]); ?>&requestID=<?php echo e($firstProposalRequest["requestID"]); ?>">
                            Submit Proposal
                        </a>
                    </section>
                <?php endif; ?>

                <section class="dashboard-top">
                    <div class="recommendation-column">
                        <div class="section-toolbar">
                            <a class="selector" href="studentDiscovery.php">
                                Recommended Supervisors
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </a>
                        </div>

                        <p class="muted-title">Top Match For You:</p>

                        <section class="supervisor-grid">
                            <?php if (empty($supervisors)): ?>
                                <article class="supervisor-card">
                                    <p class="empty-state">
                                        <?php echo $hasSavedInterestTags
                                            ? "No recommended supervisors currently match your saved interests with Available slot status. Browse Discovery to review all supervisors."
                                            : "Update your Research Interests in your Profile to unlock personalised supervisor recommendations."; ?>
                                    </p>
                                </article>
                            <?php else: ?>
                                <?php foreach ($supervisors as $supervisor): ?>
                                    <?php
                                        $isOffline   = $supervisor["status"] === "Offline";
                                        $statusClass = $supervisor["statusClass"] ?? ($isOffline ? "offline" : "online");
                                        $canApply    = (bool) ($supervisor["canApply"] ?? false);
                                        $availabilityLabel = $supervisor["quotaStatus"] ?? "Full";
                                        $quotaParts  = explode("/", $supervisor["quotaText"]);
                                        $quotaUsed   = trim($quotaParts[0] ?? "0");
                                        $quotaMax    = preg_replace("/[^0-9]/", "", $quotaParts[1] ?? (string) $supervisor["maxSlots"]);
                                    ?>
                                    <article class="supervisor-card">
                                        <div class="supervisor-top">
                                            <div class="avatar <?php echo $isOffline ? "offline" : ""; ?>">
                                                <?php if (!empty($supervisor["profilePhotoPath"])): ?>
                                                    <img src="<?php echo e($supervisor["profilePhotoPath"]); ?>" alt="">
                                                <?php else: ?>
                                                    <?php echo e(initials($supervisor["fullName"])); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="top-right">
                                                <span class="status-pill <?php echo e($statusClass); ?>"><?php echo e($supervisor["status"]); ?></span>
                                                <div class="quota">
                                                    <?php echo e($availabilityLabel); ?>
                                                    <strong><?php echo e($quotaUsed); ?> / <?php echo e($quotaMax); ?></strong>
                                                </div>
                                            </div>
                                        </div>

                                        <h2 class="supervisor-name"><?php echo e($supervisor["fullName"]); ?></h2>
                                        <div class="specialty">
                                            Specialization: <?php echo e($supervisor["programme"]); ?>, <?php echo e($supervisor["employmentCategory"]); ?>
                                        </div>

                                        <div class="tag-list">
                                            <span class="tag"><?php echo e($supervisor["programme"]); ?></span>
                                            <span class="tag"><?php echo e($supervisor["employmentCategory"]); ?></span>
                                        </div>

                                        <?php if (!$canApply): ?>
                                            <span class="btn-apply disabled"><?php echo e($supervisor["buttonLabel"] ?? "Application Closed"); ?></span>
                                        <?php else: ?>
                                            <a class="btn-apply"
                                                href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($supervisor["userID"]); ?>">
                                                Apply for Supervision
                                            </a>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </section>
                    </div>

                    <div class="status-column">
                        <section class="stats-row">
                            <article class="stat-card">
                                <div class="stat-top">
                                    <div class="stat-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#0b3760" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                    </div>
                                    <span class="stat-label">Status</span>
                                </div>
                                <div class="stat-value"><?php echo e($allocationStatus); ?></div>
                                <div class="stat-caption">Allocation Status</div>
                            </article>

                            <article class="stat-card">
                                <div class="stat-top">
                                    <div class="stat-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#0b3760" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                    </div>
                                    <span class="stat-label">Real-Time</span>
                                </div>
                                <div class="stat-value"><?php echo e($pendingRequests); ?></div>
                                <div class="stat-caption">Active Requests</div>
                            </article>

                            <article class="timer-card">
                                <p class="timer-phase"><?php echo e($phaseLabel); ?></p>
                                <div class="timer-value" id="phaseTimer">--:--:--</div>
                                <div class="timer-date">
                                    <?php echo $phaseEnd !== "" ? e(strtoupper(date("d M Y", strtotime($phaseEnd)))) : "No active phase"; ?>
                                </div>
                            </article>
                        </section>
                    </div>
                </section>

                <!-- Lower section -->
                <section class="dashboard-lower">

                    <!-- Active requests -->
                    <div>
                        <div class="panel-heading">
                            <h2>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                Active Requests
                            </h2>
                            <a href="studentApplicationStatus.php">View All</a>
                        </div>

                        <div class="request-panel">
                            <?php if (empty($requests)): ?>
                                <p class="empty-state">No active requests yet. Start by applying to a supervisor with open slots.</p>
                            <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                    <?php $statusCls = requestClass($request["decisionStatus"]); ?>
                                    <article class="request-item">
                                        <div class="request-avatar">
                                            <?php if (!empty($request["supervisorPhotoPath"])): ?>
                                                <img src="<?php echo e($request["supervisorPhotoPath"]); ?>" alt="<?php echo e($request["supervisorName"]); ?>">
                                            <?php else: ?>
                                                <?php echo e(initials($request["supervisorName"])); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="request-name"><?php echo e($request["supervisorName"]); ?></p>
                                            <p class="request-expertise">
                                                Expertise: <?php echo e($request["projectTitle"] ?? "â€”"); ?>
                                            </p>
                                            <div class="request-meta-row">
                                                <span class="request-meta-item">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                                        <line x1="8"  y1="2" x2="8"  y2="6"/>
                                                        <line x1="3"  y1="10" x2="21" y2="10"/>
                                                    </svg>
                                                    <?php echo e(date("d M Y", strtotime($request["applicationDate"]))); ?>
                                                </span>
                                                <span class="request-meta-item">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                    </svg>
                                                    <?php echo e($request["projectTitle"] ?? "Proposal"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="request-right">
                                            <span class="request-status <?php echo e($statusCls); ?>">
                                                <?php echo e($request["decisionStatus"]); ?>
                                            </span>
                                            <?php if ($request["decisionStatus"] === "Proposal Requested"): ?>
                                                <a class="details-link" href="submitProposalForm.php?supervisorID=<?php echo urlencode($request["supervisorID"]); ?>&requestID=<?php echo e($request["requestID"]); ?>">Submit Proposal -></a>
                                            <?php else: ?>
                                                <a class="details-link" href="studentApplicationStatus.php">Details -></a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Discovery -->
                    <aside>
                        <div class="panel-heading">
                            <h2>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                </svg>
                                Discovery
                            </h2>
                        </div>

                        <div class="discovery-panel">
                            <span class="disc-section-label">All Supervisors</span>

                            <?php foreach ($discoveryList as $supervisor): ?>
                                <?php $miniStatusClass = $supervisor["statusClass"] ?? "offline"; ?>
                                <a class="mini-item"
                                    href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($supervisor["userID"]); ?>">
                                    <span class="mini-avatar">
                                        <?php if (!empty($supervisor["profilePhotoPath"])): ?>
                                            <img src="<?php echo e($supervisor["profilePhotoPath"]); ?>" alt="<?php echo e($supervisor["fullName"]); ?>">
                                        <?php else: ?>
                                            <?php echo e(initials($supervisor["fullName"])); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span>
                                        <span class="mini-name"><?php echo e($supervisor["fullName"]); ?></span>
                                        <span class="mini-status <?php echo e($miniStatusClass); ?>">
                                            <?php echo e($supervisor["status"]); ?>
                                        </span>
                                    </span>
                                    <span class="mini-chevron">â€º</span>
                                </a>
                            <?php endforeach; ?>

                            <a class="explore-btn" href="studentDiscovery.php">Explore All Faculty</a>
                        </div>
                    </aside>

                </section>
            </div>
        </main>
    </div>

    <script>
        const phaseEnd = "<?php echo e($phaseEnd); ?>";
        const serverOffset = (Number("<?php echo e($serverEpoch); ?>") * 1000) - Date.now();
        const timer    = document.getElementById("phaseTimer");

        function updateTimer() {
            if (!phaseEnd) { timer.textContent = "--:--:--"; return; }
            const remaining = new Date(phaseEnd.replace(" ", "T")).getTime() - (Date.now() + serverOffset);
            if (remaining <= 0) { timer.textContent = "00:00:00"; return; }
            const h = Math.floor(remaining / 3600000);
            const m = Math.floor((remaining % 3600000) / 60000);
            const s = Math.floor((remaining % 60000) / 1000);
            timer.textContent =
                String(h).padStart(2, "0") + ": " +
                String(m).padStart(2, "0") + ": " +
                String(s).padStart(2, "0");
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    </script>
</body>
</html>
