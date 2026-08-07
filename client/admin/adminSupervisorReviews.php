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
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Supervisor Reviews Audit", "admin"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo ssasPortalSidebar("reviews"); ?>
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
                            <strong><?php echo ssasEscape($totalReviews); ?></strong>
                        </div>
                        <div>
                            <span>Average Rating</span>
                            <strong><?php echo ssasEscape($averageRating); ?>/5.0</strong>
                        </div>
                        <div>
                            <span>Visible Records</span>
                            <strong><?php echo ssasEscape($visibleReviews); ?></strong>
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
                        <strong><?php echo ssasEscape($anonymousReviews); ?></strong>
                    </article>
                </section>

                <section class="audit-card">
                    <div class="table-headline">
                        <div>
                            <h2>Submitted Reviews</h2>
                            <p>Showing <?php echo ssasEscape($totalReviews); ?> audit record(s)</p>
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
                                            <td>#<?php echo ssasEscape($review["reviewID"]); ?></td>
                                            <td>
                                                <strong><?php echo ssasEscape($review["trueStudentName"]); ?></strong>
                                                <div class="meta"><?php echo ssasEscape($review["trueStudentID"]); ?></div>
                                            </td>
                                            <td>
                                                <strong><?php echo ssasEscape($review["supervisorName"]); ?></strong>
                                                <div class="meta"><?php echo ssasEscape($review["supervisorID"]); ?></div>
                                            </td>
                                            <td><span class="stars"><?php echo ssasEscape(reviewStars($review["starRating"])); ?></span></td>
                                            <td>
                                                <?php if (!empty($review["isAnonymous"])): ?>
                                                    <span class="identity-pill anonymous">Anonymous to Supervisor</span>
                                                <?php else: ?>
                                                    <span class="identity-pill visible">Visible to Supervisor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="feedback"><?php echo ssasEscape(trim((string) $review["textFeedback"]) !== "" ? $review["textFeedback"] : "No written feedback."); ?></td>
                                            <td>
                                                <?php echo ssasEscape(auditDate($review["allocationDate"])); ?>
                                                <div class="meta"><?php echo ssasEscape($review["allocationMethod"]); ?></div>
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
