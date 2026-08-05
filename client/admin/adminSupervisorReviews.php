<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/StudentReviewService.php";
require_once __DIR__ . "/../shared/accountLayout.php";
require_once __DIR__ . "/adminReportComponents.php";

SessionManager::startSession();
SessionManager::requireRole("Administrator");

$reviewService = new StudentReviewService();

$supervisorID = trim($_GET["supervisorID"] ?? "");
$reviews = $reviewService->getReviewAuditRecords($supervisorID);
$totalReviews = count($reviews);

$visibleReviews = 0;
$anonymousReviews = 0;
$ratingTotal = 0;

foreach ($reviews as $review) {
    $ratingTotal += (int) ($review["starRating"] ?? 0);

    if (!empty($review["isAnonymous"])) {
        $anonymousReviews++;
    } else {
        $visibleReviews++;
    }
}

$averageRating = $totalReviews > 0 ? number_format($ratingTotal / $totalReviews, 1) : "0.0";

function reviewStars($rating) {
    $rating = max(1, min(5, (int) $rating));

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
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/admin.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo adminReportSidebar("reviews"); ?>
        <main class="main admin-review-audit-main">
            <div class="report-shell">
                <section class="hero-card review-audit-hero">
                    <div>
                        <h1>Supervisor Reviews Audit</h1>
                        <p>Administrative audit view of submitted supervisor reviews. True student identity is retained here for traceability even when supervisor-facing pages show Anonymous Student.</p>
                    </div>

                    <div class="review-audit-metrics">
                        <div>
                            <span>Total Reviews</span>
                            <strong><?php echo e($totalReviews); ?></strong>
                        </div>
                        <div>
                            <span>Average Rating</span>
                            <strong><?php echo e($averageRating); ?>/5.0</strong>
                        </div>
                        <div>
                            <span>Visible Records</span>
                            <strong><?php echo e($visibleReviews); ?></strong>
                        </div>
                    </div>
                </section>

                <section class="review-audit-summary">
                    <article class="audit-insight-card">
                        <div>
                            <h2>Identity Protection</h2>
                            <p>Anonymous mode masks student identity on supervisor and public interfaces. This administrator-only page preserves true student ID for authorised audit and misuse investigation.</p>
                        </div>
                    </article>
                    <article class="audit-stat-card">
                        <span>Anonymous to Supervisor</span>
                        <strong><?php echo e($anonymousReviews); ?></strong>
                    </article>
                </section>

                <section class="audit-card">
                    <div class="table-headline">
                        <div>
                            <h2>Submitted Reviews</h2>
                            <p>Showing <?php echo e($totalReviews); ?> audit record(s)</p>
                        </div>
                    </div>
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
