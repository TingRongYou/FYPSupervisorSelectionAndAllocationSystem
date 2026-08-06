<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/StudentReviewService.php";
require_once __DIR__ . "/supervisorLayout.php";
require_once __DIR__ . "/supervisorReportComponents.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

$reviewService = new StudentReviewService();

$reviews = $reviewService->getSanitizedReviewsForSupervisor($_SESSION["userID"]);

function stars($rating) {
    $rating = max(1, min(5, (int) $rating));

    return str_repeat("*", $rating) . str_repeat("-", 5 - $rating);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reviews | SSAS</title>
    <!-- Standardized Asset Links -->
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/supervisor.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("student-reviews"); ?>
        <main class="main">
            <div class="report-shell">
                <header class="report-head report-hero">
                    <div>
                        <div class="eyebrow">Student Feedback</div>
                        <h1>Student Reviews</h1>
                        <p>Review feedback submitted by your allocated students. Anonymous submissions are masked as required by the review privacy design.</p>
                    </div>
                </header>

                <?php if (empty($reviews)): ?>
                    <section class="empty-message">No student reviews have been submitted for your supervision records yet.</section>
                <?php else: ?>
                    <section class="review-summary">
                        <div>
                            <span>Total Reviews</span>
                            <strong><?php echo e(count($reviews)); ?></strong>
                        </div>
                        <div>
                            <span>Privacy</span>
                            <strong>Protected Feedback</strong>
                        </div>
                    </section>
                    <section class="review-grid">
                        <?php foreach ($reviews as $review): ?>
                            <article class="report-card review-card">
                                <div class="review-top">
                                    <div class="review-person">
                                        <span class="student-chip"><?php echo e(reportInitials($review["authorName"])); ?></span>
                                        <div>
                                            <p class="review-author"><?php echo e($review["authorName"]); ?></p>
                                            <p class="review-id">
                                                <?php echo $review["authorID"] !== "" ? "Student ID: " . e($review["authorID"]) : "Identity hidden by anonymous mode"; ?>
                                            </p>
                                        </div>
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
