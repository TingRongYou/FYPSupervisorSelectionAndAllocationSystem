<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/TagManagementService.php";
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
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("expertise-tags"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>

            <section class="hero-card hero">
                <div>
                    <h1>Expertise & Interests</h1>
                    <p>Define your research domains and manage tags to optimize student matching.</p>
                </div>
                <div class="hero-stat">
                    <div>
                        <div class="metric-label" style="color: #b9d2ff;">Tag Utilization</div>
                        <div class="stat-value"><?php echo e($tagCount); ?>/10</div>
                    </div>
                    <div>
                        <div class="metric-label" style="color: #b9d2ff;">Strength</div>
                        <div class="strength-value"><?php echo e($strength); ?> &gt;</div>
                    </div>
                </div>
            </section>

            <form class="tags-layout" action="../../server/application/supervisor/updateSupervisorTags.php" method="POST" id="tagForm">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">

                <section class="card tag-card">
                    <div class="card-heading">
                        <h2 class="card-title">Selected Interests</h2>
                        <span class="active-badge" id="activeTagCount"><?php echo e($tagCount); ?> tags active</span>
                    </div>
                    <div class="selected-box" id="selectedTagsBox">
                        <?php if (empty($selectedTags)): ?>
                            <span class="selected-pill">No expertise selected</span>
                        <?php else: ?>
                            <?php foreach ($selectedTags as $tag): ?>
                                <button class="selected-pill selected-tag" type="button" data-tag-id="<?php echo e((int) $tag["tagID"]); ?>"><?php echo e($tag["tagName"]); ?></button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <label style="color: #526a7f; font-weight: 800; font-size: 14px;">Select Your Research Interests (Max 10)</label>
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
                                    <input type="checkbox" name="tagIDs[]" value="<?php echo e($tagID); ?>" data-tag-name="<?php echo e($tag["tagName"]); ?>" <?php echo in_array($tagID, $selectedTagIDs, true) ? "checked" : ""; ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="side-stack">
                    <section class="card insight-card">
                        <div class="metric-label" style="color: #8a9caf;">Expertise Strength</div>
                        <div class="score"><?php echo e($strength); ?></div>
                        <div class="visibility">Visibility Score</div>
                        <p style="color:#6b7f91; line-height:1.6; margin-top:10px;">Your tags currently cover <?php echo e($tagCount * 10); ?>% of relevant research queries in the department.</p>
                        <div class="bars strength-<?php echo e(strtolower($strength)); ?>">
                            <span style="height:26px;"></span><span></span><span></span><span></span>
                        </div>
                    </section>
                    
                    <section class="card insight-card">
                        <div class="metric-label" style="color: #8a9caf;">Trending In Research</div>
                        <div class="visibility" style="margin: 3px 0 10px;">Based On Current Student Demand</div>
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
</body>
</html>