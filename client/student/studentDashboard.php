<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorDiscoveryService.php";
require_once __DIR__ . "/../../server/business/services/TemporalPhaseEngine.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/../../server/data/dao/TagDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireLogin();
SessionManager::requireRole("Student");

$studentID   = $_SESSION["userID"];
$studentName = $_SESSION["fullName"] ?? "Student";

$discoveryService = new SupervisorDiscoveryService();
$requestDAO       = new RequestDAO();
$requestDAO->expireTimedOutRequestsByStudent($studentID);

$tagDAO = new TagDAO();
$studentTagIDs = $tagDAO->getStudentTagIDs($studentID) ?: [];

$supervisors     = $discoveryService->getRecommendedMatches($studentID); // Search for recommended supervisors based on student's saved interest tags
$hasSavedInterestTags = $discoveryService->hasSavedInterestTags($studentID); // Check if student has any interest tags
$discoveryList   = array_slice($discoveryService->discoverSupervisors([]), 0, 3); // Get 3 supervisor
$requests        = $requestDAO->getRecentApplicationsByStudent($studentID, 2); // Get user 2 of the most recent applications
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
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    
    <script>
        window.ssasDashboardConfig = {
            phaseEnd: "<?php echo e($phaseEnd); ?>",
            serverEpoch: "<?php echo e($serverEpoch); ?>"
        };
    </script>
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("dashboard"); ?>
        <main class="main">
            <div class="dashboard-shell">

                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Student Workspace</p>
                        <h1>Student Dashboard</h1>
                        <p class="subtitle">Track supervisor recommendations, active requests, and your review timeline in one place.</p>
                    </div>
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

                <!-- Display the recommended supervisors as dropdown box -->
                <section class="dashboard-top-section recommendation-column">
                    <div class="section-toolbar">
                        <button class="selector" id="toggleRecommendations">
                            Recommended Supervisors
                            <svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                    </div>

                    <div id="recommendationsContent" class="collapsible-content">
                        <div class="collapsible-inner"> 
                            
                            <p class="muted-title">Top Match For You:</p>

                            <section class="supervisor-grid">
                                <?php if (empty($supervisors)): ?>
                                    <article class="supervisor-card empty-grid-card">
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
                                            
                                            // NEW LOGIC: Filter ONLY the tags that match the student's interests
                                            $matchedTags = [];
                                            if (!empty($supervisor["tagIDs"]) && !empty($supervisor["tagNames"])) {
                                                foreach ($supervisor["tagIDs"] as $index => $tagID) {
                                                    // If the supervisor's tag is in the student's list, keep the name
                                                    if (in_array($tagID, $studentTagIDs)) {
                                                        $matchedTags[] = $supervisor["tagNames"][$index];
                                                    }
                                                }
                                            }
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

                                            <?php $matchCount = count($matchedTags); ?>

                                                <div class="name-row">
                                                    <h2 class="supervisor-name"><?php echo e($supervisor["fullName"]); ?></h2>
                                                    <?php if ($matchCount > 0): ?>
                                                        <span class="match-score"><?php echo $matchCount; ?> Match<?php echo $matchCount === 1 ? '' : 'es'; ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="specialty">
                                                    Specialization: <?php echo e($supervisor["programme"]); ?>, <?php echo e($supervisor["employmentCategory"]); ?>
                                                </div>

                                                <div class="tag-list">
                                                    <span class="tag structural"><?php echo e($supervisor["programme"]); ?></span>
                                                    <span class="tag structural"><?php echo e($supervisor["employmentCategory"]); ?></span>
                                                    
                                                    <?php foreach ($matchedTags as $tagName): ?>
                                                        <span class="tag"><?php echo e($tagName); ?></span>
                                                    <?php endforeach; ?>
                                                </div>

                                            <?php if (!$canApply): ?>
                                                <span class="btn-apply disabled"><?php echo e($supervisor["buttonLabel"] ?? "Application Closed"); ?></span>
                                            <?php else: ?>
                                                <a class="btn-apply" href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($supervisor["userID"]); ?>">
                                                    Apply for Supervision
                                                </a>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </section>
                            
                        </div>
                    </div>
                </section>

                <!-- Status card (Allocation Status, Active Requests, Phases) -->
                <section class="dashboard-middle-section">
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
                </section>

                <section class="dashboard-lower">

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
                                                Expertise: <?php echo e($request["projectTitle"] ?? "—"); ?>
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
                                <a class="mini-item" href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($supervisor["userID"]); ?>">
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
                                    <span class="mini-chevron">›</span>
                                </a>
                            <?php endforeach; ?>
                            <a class="explore-btn" href="studentDiscovery.php">Explore All Faculty</a>
                        </div>
                    </aside>

                </section>
            </div>
        </main>
    </div>
</body>
</html>
