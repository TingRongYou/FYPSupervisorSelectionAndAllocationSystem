<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/StudentReviewService.php";
require_once __DIR__ . "/supervisorLayout.php";
require_once __DIR__ . "/supervisorReportComponents.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

$reviewService =
    new StudentReviewService();

$reviews =
    $reviewService->getSanitizedReviewsForSupervisor($_SESSION["userID"]);

function stars($rating) {

    $rating =
        max(1, min(5, (int) $rating));

    return str_repeat("*", $rating) . str_repeat("-", 5 - $rating);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reviews | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        <?php echo reportStyles(); ?>
        .review-grid { display: grid; gap: 16px; }
        .review-card { padding: 20px; }
        .review-top { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; margin-bottom: 12px; }
        .review-author { margin: 0; color: #172033; font-size: 16px; font-weight: 900; }
        .review-id { margin: 4px 0 0; color: #7c8da0; font-size: 13px; font-weight: 800; }
        .stars { color: #0d5be8; font-size: 18px; font-weight: 900; letter-spacing: 2px; white-space: nowrap; }
        .review-text { margin: 0; color: #526a7f; line-height: 1.6; }
        .privacy-note { margin-top: 14px; color: #7c8da0; font-size: 13px; font-weight: 800; }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("student-reviews"); ?>
        <main class="main">
            <div class="report-shell">
                <header class="report-head">
                    <div>
                        <div class="eyebrow">Student Feedback</div>
                        <h1>Student Reviews</h1>
                        <p>Review feedback submitted by your allocated students. Anonymous submissions are masked as required by the review privacy design.</p>
                    </div>
                </header>

                <?php if (empty($reviews)): ?>
                    <section class="empty-message">No student reviews have been submitted for your supervision records yet.</section>
                <?php else: ?>
                    <section class="review-grid">
                        <?php foreach ($reviews as $review): ?>
                            <article class="report-card review-card">
                                <div class="review-top">
                                    <div>
                                        <p class="review-author"><?php echo e($review["authorName"]); ?></p>
                                        <p class="review-id">
                                            <?php echo $review["authorID"] !== "" ? "Student ID: " . e($review["authorID"]) : "Identity hidden by anonymous mode"; ?>
                                        </p>
                                    </div>
                                    <div class="stars" aria-label="<?php echo e((int) $review["starRating"]); ?> out of 5">
                                        <?php echo e(stars($review["starRating"])); ?>
                                    </div>
                                </div>
                                <p class="review-text">
                                    <?php echo e(trim((string) $review["textFeedback"]) !== "" ? $review["textFeedback"] : "No written feedback was provided."); ?>
                                </p>
                                <?php if (!empty($review["isAnonymous"])): ?>
                                    <div class="privacy-note">Anonymous review: student identity is visible only to authorised administrators for audit purposes.</div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
