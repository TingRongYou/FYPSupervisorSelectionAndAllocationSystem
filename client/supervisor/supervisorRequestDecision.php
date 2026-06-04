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
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .review-shell { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 22px; align-items: start; }
        .breadcrumb { color: #9aacc0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; font-weight: 900; margin-bottom: 18px; }
        .proposal-card { padding: 28px; }
        .proposal-header { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 26px; }
        .proposal-icon { width: 50px; height: 50px; flex: 0 0 50px; border-radius: 12px; background: #eff6ff; color: #0d5be8; display: grid; place-items: center; font-size: 0; margin-bottom: 16px; }
        .proposal-icon::before { content: "PDF"; font-size: 12px; font-weight: 900; letter-spacing: .5px; }
        .proposal-icon svg { width: 25px; height: 25px; display: block; }
        h1 { margin: 0 0 8px; color: #172033; font-size: 30px; line-height: 1.15; max-width: 760px; }
        .student-line { margin: 0 0 26px; color: #526a7f; font-weight: 800; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin: 0 0 24px; }
        .summary-grid h3 { margin: 0 0 10px; color: #7c8da0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .summary-grid p { margin: 0; color: #526a7f; line-height: 1.6; }
        .pdf-viewer { border: 1px solid #d9e7f3; border-radius: 10px; overflow: hidden; background: #f8fbff; }
        .pdf-toolbar { min-height: 54px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px; border-bottom: 1px solid #d9e7f3; background: #fff; }
        .pdf-title { display: flex; align-items: center; gap: 10px; color: #172033; font-weight: 900; }
        .pdf-title span { width: 28px; height: 28px; border-radius: 8px; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-size: 13px; font-weight: 900; }
        .pdf-action { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; padding: 0 12px; border-radius: 7px; background: #0d5be8; color: #fff; font-size: 13px; font-weight: 900; text-decoration: none; white-space: nowrap; }
        .pdf-frame { display: block; width: 100%; height: 560px; border: 0; background: #eef6ff; }
        .side-stack { display: grid; gap: 18px; }
        .meta-card, .decision-card, .note-card { padding: 22px; }
        .meta-row { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 14px; color: #526a7f; font-size: 14px; }
        .meta-row strong { color: #172033; }
        .status { display: inline-flex; min-width: 82px; justify-content: center; padding: 7px 13px; border-radius: 999px; font-size: 13px; font-weight: 900; }
        .status.pending { background: #fff0bf; color: #9a6500; }
        .status.accepted { background: #dcfce7; color: #166534; }
        .status.rejected { background: #fee2e2; color: #991b1b; }
        .decision-card h2, .note-card h2 { margin: 0 0 16px; color: #7c8da0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .decision-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
        .decision-button { min-height: 76px; border-radius: 12px; border: 0; font-weight: 900; cursor: pointer; }
        .accept { background: #e5f6ed; color: #177345; }
        .reject { background: #fdeaea; color: #a52d2d; }
        .readonly-decision { border-radius: 12px; padding: 16px; background: #f8fbff; border: 1px solid #d9e7f3; color: #526a7f; line-height: 1.5; }
        .readonly-decision strong { display: block; color: #172033; margin-bottom: 8px; }
        .readonly-comment-wrap { margin-top: 16px; padding-top: 14px; border-top: 1px solid #e5edf5; }
        .readonly-comment-label { margin-bottom: 8px; color: #7c8da0; font-size: 12px; font-weight: 900; letter-spacing: .8px; text-transform: uppercase; }
        .readonly-comment { min-height: 46px; border-radius: 10px; padding: 12px 14px; background: #fff; border: 1px solid #d9e7f3; white-space: pre-wrap; color: #243b53; line-height: 1.5; }
        .readonly-comment.empty { color: #8aa0b5; font-style: italic; }
        .note-card { background: #f8fbff; color: #526a7f; line-height: 1.5; }
        textarea { min-height: 126px; }
        .empty-state { padding: 32px; color: #6b7f91; }
        @media (max-width: 980px) { .review-shell { grid-template-columns: 1fr; } .summary-grid { grid-template-columns: 1fr; } .pdf-frame { height: 420px; } }
        @media (max-width: 620px) { .proposal-card { padding: 20px; } .proposal-header, .pdf-toolbar { display: block; } .proposal-icon { margin-bottom: 14px; } .pdf-action { margin-top: 10px; width: 100%; } }
    </style>
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
                        <div class="proposal-icon">â–¡</div>
                        <h1><?php echo e($request["projectTitle"]); ?></h1>
                        <p class="student-line"><?php echo e($request["fullName"]); ?> of <?php echo e($request["programme"]); ?></p>

                        <div class="summary-grid">
                            <div>
                                <h3>Executive Summary</h3>
                                <p>This proposal has been submitted for your review. Open the uploaded PDF to evaluate the project scope, feasibility, and student preparedness.</p>
                            </div>
                            <div>
                                <h3>Key Objectives</h3>
                                <p>
                                    <?php if ($isExistingSupervisionProposal): ?>
                                        This student is already allocated to you. Your decision records whether the submitted proposal is acceptable; it does not remove the supervision allocation.
                                    <?php else: ?>
                                        Review the project title, attached document, submission date, and any required comments before making your decision.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

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
                            <div class="meta-row"><span>Student ID</span><strong><?php echo e($request["studentID"]); ?></strong></div>
                            <div class="meta-row"><span>Submitted</span><strong><?php echo e(formatDateText($request["applicationDate"])); ?></strong></div>
                            <div class="meta-row"><span>Expires</span><strong><?php echo e(formatDateText($request["ttlExpirationTimestamp"])); ?></strong></div>
                            <div class="meta-row">
                                <span><?php echo $isExistingSupervisionProposal ? "Proposal Status" : "Status"; ?></span>
                                <span class="status <?php echo e(statusClass($request["decisionStatus"])); ?>">
                                    <?php echo e($request["decisionStatus"]); ?>
                                </span>
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
                                <div class="readonly-decision">
                                    <strong>This request has already been <?php echo e(strtolower($request["decisionStatus"])); ?>.</strong>
                                    No further decision action is available for this proposal. If it was rejected, the student must submit a new proposal before you can provide another decision.
                                    <div class="readonly-comment-wrap">
                                        <div class="readonly-comment-label">Supervisor Comment</div>
                                        <div class="readonly-comment <?php echo e($request["supervisorComment"] !== "" ? "" : "empty"); ?>">
                                            <?php echo e($request["supervisorComment"] !== "" ? $request["supervisorComment"] : "No supervisor comment was provided."); ?>
                                        </div>
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
    <script>
        const decisionForm = document.querySelector(".decision-card");

        if (decisionForm && decisionForm.tagName === "FORM") {
            decisionForm.addEventListener("submit", function(event) {
                const comment = decisionForm.querySelector('textarea[name="supervisorComment"]').value.trim();

                if (comment === "") {
                    event.preventDefault();
                    alert("Comment Required - Please provide a reason for rejection to help the student improve their next application.");
                }
            });
        }
    </script>
</body>
</html>
