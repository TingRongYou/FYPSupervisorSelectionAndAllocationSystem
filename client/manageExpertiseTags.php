<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/TagManagementService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$tagService = new TagManagementService();
$tags = $tagService->getAllTags();
$selectedTagIDs = $tagService->getSupervisorTagIDs($_SESSION["userID"]);
$selectedTags = [];

foreach ($tags as $tag) {

    if (in_array((int) $tag["tagID"], $selectedTagIDs, true)) {

        $selectedTags[] = $tag;
    }
}

$tagCount = count($selectedTagIDs);
$strength = $tagCount >= 7 ? "High" : ($tagCount >= 4 ? "Medium" : "Low");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expertise & Tags | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .tags-layout { display: grid; grid-template-columns: 1.35fr .65fr; gap: 24px; align-items: start; }
        .tag-card { padding: 26px; }
        .card-title { margin: 0 0 22px; color: #1d2b3a; font-size: 17px; text-transform: uppercase; letter-spacing: .5px; }
        .selected-box { border: 1px dashed #cfe0ef; border-radius: 12px; padding: 20px; min-height: 120px; display: flex; flex-wrap: wrap; gap: 10px; align-content: flex-start; margin-bottom: 24px; }
        .selected-pill { background: #fff; border: 1px solid #dbe6f0; color: #2f4053; border-radius: 8px; padding: 9px 12px; font-weight: 800; font-size: 12px; }
        .tag-options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .tag-option { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid #dbe6f0; border-radius: 12px; padding: 14px; background: #fff; cursor: pointer; }
        .tag-option input { width: 18px; height: 18px; accent-color: #0d5be8; }
        .tag-name { display: flex; align-items: center; gap: 10px; font-weight: 800; color: #2f4053; }
        .tag-code { width: 34px; height: 34px; border-radius: 9px; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-size: 12px; }
        .side-stack { display: grid; gap: 18px; }
        .insight-card { padding: 24px; }
        .score { font-size: 34px; color: #0d5be8; font-weight: 900; margin: 16px 0 4px; }
        .bars { display: flex; align-items: end; gap: 6px; height: 70px; margin-top: 18px; }
        .bars span { width: 10px; border-radius: 999px; background: #0d5be8; opacity: .35; }
        .bars span:nth-child(2) { height: 40px; }
        .bars span:nth-child(3) { height: 54px; opacity: .65; }
        .bars span:nth-child(4) { height: 66px; opacity: 1; }
        .trend-list { margin: 0; padding-left: 18px; color: #2f4053; line-height: 1.9; font-weight: 700; font-size: 13px; }
        .actions { display: grid; gap: 12px; }
        @media (max-width: 1100px) { .tags-layout, .tag-options { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("expertise-tags"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>

            <section class="hero">
                <div>
                    <h1>Expertise & Interests</h1>
                    <p>Define your research domains and manage tags to optimize student matching.</p>
                </div>
                <div class="hero-stat">
                    <div class="stat-label">Tag Utilization</div>
                    <div class="stat-value"><?php echo e($tagCount); ?>/10</div>
                    <div class="stat-label" style="margin-top: 10px;">Strength: <?php echo e($strength); ?></div>
                </div>
            </section>

            <form class="tags-layout" action="../server/application/updateSupervisorTags.php" method="POST" id="tagForm">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">

                <section class="card tag-card">
                    <h2 class="card-title">Selected Interests</h2>
                    <div class="selected-box">
                        <?php if (empty($selectedTags)): ?>
                            <span class="selected-pill">No expertise selected</span>
                        <?php else: ?>
                            <?php foreach ($selectedTags as $tag): ?>
                                <span class="selected-pill"><?php echo e($tag["tagName"]); ?> x</span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <label>Select Your Research Interests (Max 10)</label>
                    <?php if (empty($tags)): ?>
                        <div class="selected-box">No research tags are currently available.</div>
                    <?php else: ?>
                        <div class="tag-options">
                            <?php foreach ($tags as $tag): ?>
                                <?php $tagID = (int) $tag["tagID"]; ?>
                                <label class="tag-option">
                                    <span class="tag-name">
                                        <span class="tag-code"><?php echo e(strtoupper(substr($tag["tagName"], 0, 2))); ?></span>
                                        <?php echo e($tag["tagName"]); ?>
                                    </span>
                                    <input type="checkbox" name="tagIDs[]" value="<?php echo e($tagID); ?>" <?php echo in_array($tagID, $selectedTagIDs, true) ? "checked" : ""; ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="side-stack">
                    <section class="card insight-card">
                        <div class="stat-label">Expertise Strength</div>
                        <div class="score"><?php echo e($strength); ?></div>
                        <p style="color:#6b7f91; line-height:1.6;">Your tags currently cover <?php echo e($tagCount * 10); ?>% of the recommended profile depth.</p>
                        <div class="bars"><span style="height:26px;"></span><span></span><span></span><span></span></div>
                    </section>
                    <section class="card insight-card">
                        <div class="stat-label">Trending In Research</div>
                        <ul class="trend-list">
                            <li>Artificial Intelligence</li>
                            <li>Cybersecurity</li>
                            <li>Data Analytics</li>
                        </ul>
                    </section>
                    <section class="actions">
                        <button class="button" type="submit">Save All Changes</button>
                        <a class="button secondary" href="manageExpertiseTags.php">Discard Changes</a>
                    </section>
                </aside>
            </form>
        </main>
    </div>
    <script>
        document.getElementById("tagForm").addEventListener("submit", function(event) {
            const checkedTags = document.querySelectorAll('input[name="tagIDs[]"]:checked');
            if (checkedTags.length < 1 || checkedTags.length > 10) {
                event.preventDefault();
                alert("Please select between 1 and 10 expertise tags.");
            }
        });
    </script>
</body>
</html>
