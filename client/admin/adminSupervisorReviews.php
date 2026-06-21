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
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/admin.js" defer></script>
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
                    Security design: anonymous mode masks student identity on supervisor/public interfaces, while this administrator-only page preserves true student ID for authorised audit and misuse investigation.
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