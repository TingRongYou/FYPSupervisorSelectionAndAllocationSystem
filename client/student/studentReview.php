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
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 32px 40px 56px; min-width: 0; }
        .review-shell { max-width: 1180px; margin: 0 auto; }
        .eyebrow { margin: 0 0 7px; color: #0b66d8; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        h1 { margin: 0; color: #172033; font-size: 30px; line-height: 1.1; }
        .subtitle { margin: 8px 0 24px; color: #5d7085; font-size: 15px; line-height: 1.5; max-width: 760px; }
        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        .review-layout { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 24px; align-items: start; }
        .supervisor-card, .review-card, .privacy-card, .empty-card { background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; box-shadow: 0 8px 22px rgba(11,79,138,.07); }
        .supervisor-card { padding: 24px; text-align: center; }
        .avatar { width: 120px; height: 120px; border-radius: 12px; background: #eaf3ff; color: #003f8f; display: grid; place-items: center; margin: 0 auto 16px; font-size: 30px; font-weight: 900; overflow: hidden; border: 1px solid #d9e7f3; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .supervisor-name { margin: 0 0 6px; color: #172033; font-size: 18px; font-weight: 900; }
        .supervisor-meta { margin: 0; color: #6b7f91; font-size: 14px; line-height: 1.5; }
        .score-card { margin-top: 18px; padding: 14px; border-radius: 10px; background: #f8fbff; color: #526a7f; font-size: 13px; line-height: 1.45; }
        .score-card strong { display: block; color: #003f8f; font-size: 22px; margin-bottom: 4px; }
        .review-card { padding: 26px; }
        label { display: block; color: #7c8da0; text-transform: uppercase; letter-spacing: .8px; font-size: 12px; font-weight: 900; margin-bottom: 8px; }
        .stars { display: inline-flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; margin-bottom: 18px; }
        .stars input { position: absolute; opacity: 0; pointer-events: none; }
        .stars label { font-size: 32px; line-height: 1; color: #cbd5e1; cursor: pointer; margin: 0; letter-spacing: 0; text-transform: none; }
        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label { color: #f6b800; }
        .rating-text { color: #003f8f; font-size: 14px; font-weight: 900; margin-left: 10px; vertical-align: 8px; }
        textarea { width: 100%; min-height: 150px; resize: vertical; border: 1px solid #dbe6f0; border-radius: 8px; background: #f8fafc; color: #172033; font-size: 14px; padding: 13px; line-height: 1.5; outline: none; }
        textarea:focus { border-color: #0d5be8; background: #fff; }
        .counter { color: #8a9caf; font-size: 12px; font-weight: 800; text-align: right; margin-top: 8px; }
        .toggle-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-top: 18px; padding: 14px; border: 1px solid #dbe6f0; border-radius: 10px; background: #f8fbff; }
        .toggle-row strong { display: block; color: #172033; font-size: 14px; margin-bottom: 3px; }
        .toggle-row span { color: #6b7f91; font-size: 13px; line-height: 1.4; }
        .switch { position: relative; width: 48px; height: 26px; flex: 0 0 auto; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; inset: 0; cursor: pointer; background: #cbd5e1; border-radius: 999px; transition: .2s; }
        .slider:before { content: ""; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; border-radius: 50%; background: #fff; transition: .2s; box-shadow: 0 2px 6px rgba(0,0,0,.18); }
        .switch input:checked + .slider { background: #0d5be8; }
        .switch input:checked + .slider:before { transform: translateX(22px); }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
        .button { min-height: 40px; border-radius: 7px; border: 0; background: #003f8f; color: #fff; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 900; font-size: 14px; cursor: pointer; }
        .button.secondary { background: #fff; color: #526a7f; border: 1px solid #d9e7f3; }
        .button.disabled { background: #eef3f8; color: #94a3b8; cursor: not-allowed; box-shadow: none; }
        .privacy-card { margin-top: 18px; padding: 18px; background: #f8fbff; }
        .privacy-card h2 { margin: 0 0 8px; color: #003f8f; font-size: 16px; }
        .privacy-card p { margin: 0; color: #526a7f; font-size: 13px; line-height: 1.55; }
        .empty-card { padding: 30px; color: #526a7f; line-height: 1.55; }
        .empty-card strong { display: block; color: #172033; font-size: 18px; margin-bottom: 8px; }
        .completed-review { display: grid; gap: 12px; color: #526a7f; font-size: 14px; line-height: 1.5; }
        .badge { display: inline-flex; width: max-content; align-items: center; justify-content: center; min-height: 26px; padding: 0 10px; border-radius: 999px; background: #dcfce7; color: #118549; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        @media (max-width: 960px) { .review-layout { grid-template-columns: 1fr; } }
        @media (max-width: 640px) { .main { padding: 24px 16px 46px; } .form-actions { display: grid; } }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("review"); ?>
        <main class="main">
            <div class="review-shell">
                <?php echo statusMessage(); ?>
                <p class="eyebrow">Student Supervision Assessment System</p>
                <h1>Supervisor Performance Review</h1>
                <p class="subtitle">Your feedback helps maintain academic excellence. Please provide an honest assessment of your supervisor's guidance during this semester.</p>

                <?php if (!$context): ?>
                    <section class="empty-card">
                        <strong>No Completed Allocation Found</strong>
                        You can submit a supervisor review only after you have an accepted allocation record.
                    </section>
                <?php else: ?>
                    <section class="review-layout">
                        <aside class="supervisor-card">
                            <div class="avatar">
                                <?php if (!empty($context["profilePhotoPath"])): ?>
                                    <img src="<?php echo e($context["profilePhotoPath"]); ?>" alt="Supervisor photo">
                                <?php else: ?>
                                    <?php echo e(initials($context["supervisorName"])); ?>
                                <?php endif; ?>
                            </div>
                            <h2 class="supervisor-name"><?php echo e($context["supervisorName"]); ?></h2>
                            <p class="supervisor-meta"><?php echo e($context["employmentCategory"]); ?><br><?php echo e($context["programme"]); ?></p>
                            <div class="score-card">
                                <strong><?php echo e(number_format((float) ($statistics["averageRating"] ?? 0), 1)); ?> / 5.0</strong>
                                <?php echo e((int) ($statistics["reviewCount"] ?? 0)); ?> submitted review(s)
                            </div>
                        </aside>

                        <div>
                            <section class="review-card">
                                <?php if (!empty($context["reviewID"])): ?>
                                    <div class="completed-review">
                                        <span class="badge">Completed</span>
                                        <strong>M2: Msg Already Reviewed</strong>
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

    <script>
        const feedback = document.getElementById("textFeedback");
        const feedbackCounter = document.getElementById("feedbackCounter");
        const ratingInputs = Array.from(document.querySelectorAll('input[name="starRating"]'));
        const ratingText = document.getElementById("ratingText");
        const reviewForm = document.getElementById("reviewForm");

        function updateFeedbackCounter() {
            if (!feedback || !feedbackCounter) {
                return;
            }

            feedbackCounter.textContent = feedback.value.length + " / 1000 characters";
        }

        function updateRatingText() {
            const selected = ratingInputs.find(input => input.checked);

            if (ratingText) {
                ratingText.textContent = (selected ? selected.value : "0") + " / 5.0";
            }
        }

        ratingInputs.forEach(function(input) {
            input.addEventListener("change", updateRatingText);
        });

        if (feedback) {
            feedback.addEventListener("input", updateFeedbackCounter);
        }

        if (reviewForm) {
            reviewForm.addEventListener("submit", function(event) {
                const selected = ratingInputs.find(input => input.checked);

                if (!selected) {
                    event.preventDefault();
                    alert("Err Missing Rating - Submission failed. You must select a star rating (1-5) to submit a review. Text feedback is optional.");
                    return;
                }

                if (feedback.value.length > 1000) {
                    event.preventDefault();
                    alert("Submission failed. Feedback cannot exceed 1000 characters.");
                }
            });

            reviewForm.addEventListener("reset", function() {
                setTimeout(function() {
                    updateRatingText();
                    updateFeedbackCounter();
                }, 0);
            });
        }

        updateRatingText();
        updateFeedbackCounter();
    </script>
</body>
</html>
