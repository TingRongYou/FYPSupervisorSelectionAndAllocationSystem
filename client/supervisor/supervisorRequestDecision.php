<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$requestID = (int) ($_GET["requestID"] ?? 0);
$sidebarActivePage = ($_GET["source"] ?? "") === "supervision"
    ? "supervision"
    : "decision-action";
$requestDAO = new RequestDAO();
$request = $requestID > 0
    ? $requestDAO->getApplicationRequestForSupervisor($requestID, $_SESSION["userID"])
    : null;
$isExistingSupervisionProposal =
    $request && !empty($request["allocationID"]);

$matchedTags =
    $request && !empty($request["matchedTagNames"])
        ? explode("||", $request["matchedTagNames"])
        : [];

function formatDateText($date) {

    if (!$date) {

        return "-";
    }

    return date("M d, Y", strtotime($date));
}

function statusClass($status) {

    $normalized = strtolower(trim($status));

    if ($normalized === "accepted") {

        return "accepted";
    }

    if ($normalized === "rejected") {

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
    <title>Proposal Review | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar($sidebarActivePage); ?>
        <main class="main">
            <div class="breadcrumb">Requests & Decisions > Incoming Requests > Proposal Review</div>
            <?php echo statusMessage(); ?>

            <?php if (!$request): ?>
                <section class="card empty-state">The selected request was not found or is not assigned to you.</section>
            <?php else: ?>
                <div class="review-shell">
                    <section class="card proposal-card">
                        <h1 style="margin: 0 0 8px; color: #172033; font-size: 30px; line-height: 1.15; max-width: 760px;"><?php echo e($request["projectTitle"]); ?></h1>
                        <p class="student-line"><?php echo e($request["fullName"]); ?> of <?php echo e($request["programme"]); ?></p>

                        <section class="student-profile">
                            <h2 class="profile-heading">Student Profile</h2>
                            <div class="profile-bio-block">
                                <span class="profile-label">Personal Bio</span>
                                <p class="profile-bio"><?php echo e(trim((string) $request["personalBio"]) !== "" ? trim((string) $request["personalBio"]) : "No personal bio has been provided."); ?></p>
                            </div>

                            <div class="profile-grid">
                                <div class="profile-field">
                                    <span class="profile-label">CGPA</span>
                                    <span class="profile-value"><?php echo e(number_format((float) $request["cgpa"], 4)); ?></span>
                                </div>
                                <div class="profile-field">
                                    <span class="profile-label">Email</span>
                                    <span class="profile-value">
                                        <a href="mailto:<?php echo e($request["universityEmail"]); ?>"><?php echo e($request["universityEmail"]); ?></a>
                                    </span>
                                </div>
                                <div class="profile-field">
                                    <span class="profile-label">Phone Number</span>
                                    <span class="profile-value <?php echo trim((string) $request["contactNumber"]) === "" ? "empty-value" : ""; ?>">
                                        <?php echo e(trim((string) $request["contactNumber"]) !== "" ? $request["contactNumber"] : "Not provided"); ?>
                                    </span>
                                </div>
                                <div class="profile-field">
                                    <span class="profile-label">LinkedIn</span>
                                    <span class="profile-value">
                                        <?php if (trim((string) $request["linkedInURL"]) !== ""): ?>
                                            <a href="<?php echo e($request["linkedInURL"]); ?>" target="_blank" rel="noopener noreferrer">View LinkedIn Profile</a>
                                        <?php else: ?>
                                            <span class="empty-value">Not provided</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="profile-field">
                                    <span class="profile-label">GitHub</span>
                                    <span class="profile-value">
                                        <?php if (trim((string) $request["githubURL"]) !== ""): ?>
                                            <a href="<?php echo e($request["githubURL"]); ?>" target="_blank" rel="noopener noreferrer">View GitHub Profile</a>
                                        <?php else: ?>
                                            <span class="empty-value">Not provided</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="profile-field tags-field">
                                    <span class="profile-label">Matched Interest Tags</span>
                                    <?php if (!empty($matchedTags)): ?>
                                        <div class="matched-tags">
                                            <?php foreach ($matchedTags as $tagName): ?>
                                                <span class="matched-tag"><?php echo e($tagName); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="profile-value empty-value">No shared tags</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>

                        <?php $proposalViewerUrl = "../../server/application/supervisor/viewProposal.php?requestID=" . urlencode($request["requestID"]); ?>
                        <div class="pdf-viewer">
                            <div class="pdf-toolbar">
                                <div class="pdf-title"><span>PDF</span> Proposal Document</div>
                                <a class="pdf-action" href="<?php echo e($proposalViewerUrl); ?>" target="_blank">Open in New Tab</a>
                            </div>
                            <iframe class="pdf-frame" src="<?php echo e($proposalViewerUrl); ?>" title="Proposal Document"></iframe>
                        </div>
                    </section>

                    <aside class="side-stack">
                        <section class="card meta-card">
                            <h2>Proposal Summary</h2>
                            <div class="meta-panel">
                                <div class="meta-row"><span>Student ID</span><strong><?php echo e($request["studentID"]); ?></strong></div>
                                <div class="meta-row"><span>Submitted</span><strong><?php echo e(formatDateText($request["applicationDate"])); ?></strong></div>
                                <div class="meta-row"><span>Expires</span><strong><?php echo e(formatDateText($request["ttlExpirationTimestamp"])); ?></strong></div>
                                <div class="meta-row">
                                    <span><?php echo $isExistingSupervisionProposal ? "Proposal Status" : "Status"; ?></span>
                                    <span class="status <?php echo e(statusClass($request["decisionStatus"])); ?>">
                                        <?php echo e($request["decisionStatus"]); ?>
                                    </span>
                                </div>
                            </div>
                        </section>

                        <?php if ($request["decisionStatus"] === "Pending"): ?>
                            <form class="card decision-card" action="../../server/application/supervisor/processSupervisorDecision.php" method="POST">
                                <h2>Decision Comment</h2>
                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                                <input type="hidden" name="requestID" value="<?php echo e($request["requestID"]); ?>">
                                <textarea name="supervisorComment" required placeholder="Provide detailed feedback for the student..."><?php echo e($request["supervisorComment"] ?? ""); ?></textarea>
                                <div class="decision-actions">
                                    <button class="decision-button accept" type="submit" name="decisionStatus" value="Accepted">
                                        <?php echo $isExistingSupervisionProposal ? "Approve Proposal" : "Accept"; ?>
                                    </button>
                                    <button class="decision-button reject" type="submit" name="decisionStatus" value="Rejected">
                                        <?php echo $isExistingSupervisionProposal ? "Reject Proposal" : "Reject"; ?>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <section class="card decision-card">
                                <h2>Decision Recorded</h2>
                                <?php $commentText = trim((string) ($request["supervisorComment"] ?? "")); ?>
                                <div class="readonly-decision">
                                    <strong>This request has already been <?php echo e(strtolower($request["decisionStatus"])); ?>.</strong>
                                    No further decision action is available for this proposal. If it was rejected, the student must submit a new proposal before you can provide another decision.
                                    <div class="readonly-comment-wrap">
                                        <div class="readonly-comment-label">Supervisor Comment</div>
                                        <div class="readonly-comment <?php echo e($commentText !== "" ? "" : "empty"); ?>"><?php echo e($commentText !== "" ? $commentText : "No supervisor comment was provided."); ?></div>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>

                        <section class="card note-card">
                            <h2>Reminder</h2>
                            <?php if ($isExistingSupervisionProposal): ?>
                                This student remains in your supervisees list after the proposal decision because the supervision was created by auto-allocation.
                            <?php else: ?>
                                Final decisions are synchronised with the student profile, and the student will receive confirmation on their portal.
                            <?php endif; ?>
                        </section>
                    </aside>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
