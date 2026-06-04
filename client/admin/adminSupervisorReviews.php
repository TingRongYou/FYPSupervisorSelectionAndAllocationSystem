<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/StudentReviewService.php";
require_once __DIR__ . "/../shared/accountLayout.php";
require_once __DIR__ . "/adminReportComponents.php";

SessionManager::startSession();
SessionManager::requireRole("Administrator");

$reviewService =
    new StudentReviewService();

$supervisorID =
    trim($_GET["supervisorID"] ?? "");

$reviews =
    $reviewService->getReviewAuditRecords($supervisorID);

function reviewStars($rating) {

    $rating =
        max(1, min(5, (int) $rating));

    return str_repeat("*", $rating) . str_repeat("-", 5 - $rating);
}

function auditDate($value) {

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
    <title>Supervisor Reviews Audit | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo adminReportStyles(); ?>
        .audit-card { background: #fff; border: 1px solid #e1ebf5; border-radius: 12px; box-shadow: 0 12px 28px rgba(11,79,138,.06); overflow: hidden; }
        .audit-note { margin-bottom: 18px; padding: 14px 16px; border: 1px solid #d9e7f3; border-radius: 10px; background: #f8fbff; color: #526a7f; line-height: 1.5; }
        .stars { color: #0d5be8; font-weight: 900; letter-spacing: 2px; white-space: nowrap; }
        .feedback { max-width: 460px; color: #526a7f; line-height: 1.5; }
        .identity-pill { display: inline-flex; align-items: center; min-height: 24px; padding: 0 10px; border-radius: 999px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .identity-pill.anonymous { background: #fff1db; color: #a84600; }
        .identity-pill.visible { background: #dcfce7; color: #177345; }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo adminReportSidebar("reviews"); ?>
        <main class="main">
            <div class="report-shell">
                <header class="report-head">
                    <div>
                        <h1>Supervisor Reviews Audit</h1>
                        <p>Administrative audit view of submitted supervisor reviews. True student identity is retained here for traceability even when supervisor-facing pages show Anonymous Student.</p>
                    </div>
                </header>

                <div class="audit-note">
                    Security design: anonymous mode masks student identity on supervisor/public interfaces, while this administrator-only page preserves trueStudentID for authorised audit and misuse investigation.
                </div>

                <section class="audit-card">
                    <div class="table-scroll">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Review ID</th>
                                    <th>Student</th>
                                    <th>Supervisor</th>
                                    <th>Rating</th>
                                    <th>Visibility</th>
                                    <th>Feedback</th>
                                    <th>Allocation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reviews)): ?>
                                    <tr>
                                        <td colspan="7">No supervisor reviews have been submitted yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td>#<?php echo e($review["reviewID"]); ?></td>
                                            <td>
                                                <strong><?php echo e($review["trueStudentName"]); ?></strong>
                                                <div class="meta"><?php echo e($review["trueStudentID"]); ?></div>
                                            </td>
                                            <td>
                                                <strong><?php echo e($review["supervisorName"]); ?></strong>
                                                <div class="meta"><?php echo e($review["supervisorID"]); ?></div>
                                            </td>
                                            <td><span class="stars"><?php echo e(reviewStars($review["starRating"])); ?></span></td>
                                            <td>
                                                <?php if (!empty($review["isAnonymous"])): ?>
                                                    <span class="identity-pill anonymous">Anonymous to Supervisor</span>
                                                <?php else: ?>
                                                    <span class="identity-pill visible">Visible to Supervisor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="feedback"><?php echo e(trim((string) $review["textFeedback"]) !== "" ? $review["textFeedback"] : "No written feedback."); ?></td>
                                            <td>
                                                <?php echo e(auditDate($review["allocationDate"])); ?>
                                                <div class="meta"><?php echo e($review["allocationMethod"]); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
