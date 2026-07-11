<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/StudentReviewService.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$reviewService = new StudentReviewService();
$payload = $reviewService->getReviewPagePayload($_SESSION["userID"]);
$context = $payload["context"];
$statistics = $payload["statistics"] ?? null;

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function initials($name) {

    $parts = preg_split("/\s+/", trim((string) $name));
    $first = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "", 0, 1));

    return $first . $second;
}

function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class = $_GET["status"] === "success" ? "success" : "error";

    return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Review | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("review"); ?>
        <main class="main">
            <div class="review-shell">
                <?php echo statusMessage(); ?>
                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Student Supervision Assessment System</p>
                        <h1>Supervisor Performance Review</h1>
                        <p class="subtitle">Your feedback helps maintain academic excellence. Please provide an honest assessment of your supervisor's guidance during this semester.</p>
                    </div>
                </section>

                <?php if (!$context): ?>
                    <section class="empty-card">
                        <strong>No Completed Allocation Found</strong>
                        You can submit a supervisor review only after you have an accepted allocation record.
                    </section>
                <?php else: ?>
                    <section class="review-layout">
                        <aside class="supervisor-card review-profile-card">
                            <div class="review-profile-top">
                                <div class="avatar review-avatar">
                                    <?php if (!empty($context["profilePhotoPath"])): ?>
                                        <img src="<?php echo e($context["profilePhotoPath"]); ?>" alt="Supervisor photo">
                                    <?php else: ?>
                                        <?php echo e(initials($context["supervisorName"])); ?>
                                    <?php endif; ?>
                                </div>
                                <span class="review-profile-kicker">Assigned Supervisor</span>
                                <h2 class="supervisor-name"><?php echo e($context["supervisorName"]); ?></h2>
                            </div>

                            <div class="review-profile-meta">
                                <span><?php echo e($context["employmentCategory"]); ?></span>
                                <strong><?php echo e($context["programme"]); ?></strong>
                            </div>

                            <div class="score-card">
                                <span class="score-label">Current Average</span>
                                <strong>
                                    <?php echo e(number_format((float) ($statistics["averageRating"] ?? 0), 1)); ?>
                                    <span>/ 5.0</span>
                                </strong>
                                <div class="score-stars" aria-hidden="true">
                                    <?php echo ssasRenderStars($statistics["averageRating"] ?? 0); ?>
                                </div>
                                <small><?php echo e((int) ($statistics["reviewCount"] ?? 0)); ?> submitted review(s)</small>
                            </div>
                        </aside>

                        <div>
                            <section class="review-card">
                                <?php if (!empty($context["reviewID"])): ?>
                                    <div class="completed-review">
                                        <span class="badge">Completed</span>
                                        <span>You have already submitted an evaluation for your supervisor this semester.</span>
                                        <span>Previous rating: <?php echo e((int) $context["starRating"]); ?> / 5</span>
                                        <?php if (trim((string) $context["textFeedback"]) !== ""): ?>
                                            <span><?php echo e($context["textFeedback"]); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (!$payload["isReviewPeriod"]): ?>
                                    <div class="completed-review">
                                        <span class="badge" style="background:#e2e8f0;color:#64748b;">Closed</span>
                                        <strong>Review Period Not Active</strong>
                                        <span>Reviews can only be submitted during the Review Period.</span>
                                        <span>Current phase: <?php echo e($payload["phase"]["phaseName"] ?? "No active phase"); ?></span>
                                    </div>
                                <?php else: ?>
                                    <form action="../../server/application/student/submitSupervisorReview.php" method="POST" id="reviewForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                                        <input type="hidden" name="allocationID" value="<?php echo e($context["allocationID"]); ?>">

                                        <label>Overall Experience</label>
                                        <div>
                                            <div class="stars" aria-label="Star rating">
                                                <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                                    <input type="radio" id="rating<?php echo $rating; ?>" name="starRating" value="<?php echo $rating; ?>">
                                                    <label for="rating<?php echo $rating; ?>">★</label>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="rating-text" id="ratingText">0 / 5.0</span>
                                        </div>

                                        <label for="textFeedback">Detailed Feedback</label>
                                        <textarea id="textFeedback" name="textFeedback" maxlength="1000" placeholder="Share strengths, responsiveness, guidance, and expertise."></textarea>
                                        <div class="counter" id="feedbackCounter">0 / 1000 characters</div>

                                        <div class="toggle-row">
                                            <div>
                                                <strong>Submit anonymously</strong>
                                                <span>Your identity will be hidden from the supervisor-facing view.</span>
                                            </div>
                                            <label class="switch" for="isAnonymous">
                                                <input type="checkbox" id="isAnonymous" name="isAnonymous" value="1">
                                                <span class="slider"></span>
                                            </label>
                                        </div>

                                        <div class="form-actions">
                                            <button class="button secondary" type="reset">Reset</button>
                                            <button class="button" type="submit">Submit Review</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </section>

                            <section class="privacy-card">
                                <h2>Privacy Notice</h2>
                                <p>Reviews are stored with your true student ID for administrative traceability. If anonymous mode is enabled, supervisor-facing displays show “Anonymous Student” instead of your identity.</p>
                            </section>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
