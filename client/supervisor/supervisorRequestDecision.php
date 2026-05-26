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
        .breadcrumb { color: #9aacc0; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 900; margin-bottom: 18px; }
        .proposal-card { padding: 34px; }
        .proposal-icon { width: 54px; height: 54px; border-radius: 14px; background: #eff6ff; color: #0d5be8; display: grid; place-items: center; font-size: 24px; font-weight: 900; margin-bottom: 20px; }
        h1 { margin: 0 0 8px; color: #172033; font-size: 30px; line-height: 1.15; max-width: 720px; }
        .student-line { margin: 0 0 30px; color: #526a7f; font-weight: 700; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin: 28px 0; }
        .summary-grid h3 { margin: 0 0 10px; color: #7c8da0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .summary-grid p { margin: 0; color: #526a7f; line-height: 1.6; }
        .pdf-preview { margin-top: 30px; min-height: 260px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #d9e7f3; display: grid; place-items: center; text-align: center; padding: 28px; }
        .pdf-preview strong { display: block; color: #003f8f; font-size: 18px; margin-bottom: 8px; }
        .pdf-preview a { color: #0d5be8; font-weight: 900; text-decoration: none; }
        .side-stack { display: grid; gap: 18px; }
        .meta-card, .decision-card, .note-card { padding: 22px; }
        .meta-row { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 14px; color: #526a7f; font-size: 13px; }
        .meta-row strong { color: #172033; }
        .status { display: inline-flex; min-width: 82px; justify-content: center; padding: 7px 13px; border-radius: 999px; font-size: 11px; font-weight: 900; }
        .status.pending { background: #fff0bf; color: #9a6500; }
        .status.accepted { background: #dcfce7; color: #166534; }
        .status.rejected { background: #fee2e2; color: #991b1b; }
        .decision-card h2, .note-card h2 { margin: 0 0 16px; color: #7c8da0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .decision-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
        .decision-button { min-height: 76px; border-radius: 12px; border: 0; font-weight: 900; cursor: pointer; }
        .accept { background: #e5f6ed; color: #177345; }
        .reject { background: #fdeaea; color: #a52d2d; }
        .readonly-decision { border-radius: 12px; padding: 16px; background: #f8fbff; border: 1px solid #d9e7f3; color: #526a7f; line-height: 1.5; }
        .readonly-decision strong { display: block; color: #172033; margin-bottom: 8px; }
        .readonly-comment { margin-top: 14px; padding-top: 14px; border-top: 1px solid #e5edf5; white-space: pre-wrap; }
        .note-card { background: #f8fbff; color: #526a7f; line-height: 1.5; }
        textarea { min-height: 126px; }
        .empty-state { padding: 32px; color: #6b7f91; }
        @media (max-width: 980px) { .review-shell { grid-template-columns: 1fr; } .summary-grid { grid-template-columns: 1fr; } }
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
                                <p>Review the project title, attached document, submission date, and any required comments before making your decision.</p>
                            </div>
                        </div>

                        <div class="pdf-preview">
                            <div>
                                <strong>Proposal Document</strong>
                                <a href="<?php echo e($request["proposalPDFPath"]); ?>" target="_blank">Open uploaded PDF</a>
                            </div>
                        </div>
                    </section>

                    <aside class="side-stack">
                        <section class="card meta-card">
                            <div class="meta-row"><span>File Management</span><strong><?php echo e($request["studentID"]); ?></strong></div>
                            <div class="meta-row"><span>Submitted</span><strong><?php echo e(formatDateText($request["applicationDate"])); ?></strong></div>
                            <div class="meta-row"><span>Expires</span><strong><?php echo e(formatDateText($request["ttlExpirationTimestamp"])); ?></strong></div>
                            <div class="meta-row"><span>Status</span><span class="status <?php echo e(statusClass($request["decisionStatus"])); ?>"><?php echo e($request["decisionStatus"]); ?></span></div>
                        </section>

                        <?php if ($request["decisionStatus"] === "Pending"): ?>
                            <form class="card decision-card" action="../../server/application/supervisor/processSupervisorDecision.php" method="POST">
                                <h2>Decision Comment</h2>
                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                                <input type="hidden" name="requestID" value="<?php echo e($request["requestID"]); ?>">
                                <textarea name="supervisorComment" placeholder="Provide detailed feedback for the student..."><?php echo e($request["supervisorComment"] ?? ""); ?></textarea>
                                <div class="decision-actions">
                                    <button class="decision-button accept" type="submit" name="decisionStatus" value="Accepted">Accept</button>
                                    <button class="decision-button reject" type="submit" name="decisionStatus" value="Rejected">Reject</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <section class="card decision-card">
                                <h2>Decision Recorded</h2>
                                <div class="readonly-decision">
                                    <strong>This request has already been <?php echo e(strtolower($request["decisionStatus"])); ?>.</strong>
                                    No further decision action is available for this proposal.
                                    <div class="readonly-comment">
                                        <?php echo e($request["supervisorComment"] !== "" ? $request["supervisorComment"] : "No supervisor comment was provided."); ?>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>

                        <section class="card note-card">
                            <h2>Reminder</h2>
                            Final decisions are synchronised with the student profile, and the student will receive confirmation on their portal.
                        </section>
                    </aside>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>


