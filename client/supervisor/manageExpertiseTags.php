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
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .hero { border-radius: 8px; padding: 30px 34px; }
        .hero-stat { min-width: 270px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: center; }
        .hero-stat .stat-value { font-size: 28px; }
        .hero-stat .strength-value { color: #fff; font-size: 20px; font-weight: 900; margin-top: 8px; }
        .tags-layout { display: grid; grid-template-columns: minmax(0, 1.32fr) minmax(300px, .68fr); gap: 22px; align-items: start; }
        .tag-card { padding: 24px; border-radius: 8px; }
        .card-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 22px; }
        .card-title { margin: 0; color: #1d2b3a; font-size: 13px; text-transform: uppercase; letter-spacing: .8px; display: flex; align-items: center; gap: 8px; }
        .card-title:before { content: ""; width: 12px; height: 8px; border: 2px solid #0d5be8; border-radius: 3px; display: inline-block; }
        .active-badge { padding: 5px 9px; border-radius: 999px; background: #eaf3ff; color: #0d5be8; font-size: 10px; font-weight: 900; text-transform: uppercase; }
        .selected-box { border: 1px dashed #cfe0ef; border-radius: 12px; padding: 20px; min-height: 120px; display: flex; flex-wrap: wrap; gap: 10px; align-content: flex-start; margin-bottom: 24px; background: #fbfdff; }
        .selected-pill { background: #fff; border: 1px solid #dbe6f0; color: #2f4053; border-radius: 8px; padding: 9px 12px; font-weight: 800; font-size: 12px; box-shadow: 0 1px 2px rgba(11,79,138,.04); }
        .selected-pill.selected-tag { cursor: pointer; }
        .selected-pill.selected-tag:after { content: "x"; margin-left: 8px; color: #0d5be8; }
        .tag-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 10px; }
        .tag-option { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid #dbe6f0; border-radius: 10px; padding: 13px 14px; background: #fff; cursor: pointer; transition: border .2s, box-shadow .2s, background .2s; }
        .tag-option:hover { border-color: #b9d4f3; box-shadow: 0 8px 18px rgba(11,79,138,.08); }
        .tag-option:has(input:checked) { border-color: #0d5be8; background: #f5f9ff; }
        .tag-option input { width: 18px; height: 18px; margin: 0; appearance: none; border: 1px solid #c7d9ee; border-radius: 50%; display: grid; place-items: center; background: #fff; cursor: pointer; }
        .tag-option input:before { content: "+"; color: #8fa5bc; font-size: 13px; font-weight: 900; line-height: 1; }
        .tag-option input:checked { border-color: #0d5be8; background: #0d5be8; }
        .tag-option input:checked:before { content: "x"; color: #fff; }
        .tag-name { display: flex; align-items: center; gap: 10px; font-weight: 800; color: #2f4053; font-size: 13px; min-width: 0; }
        .tag-code { width: 32px; height: 32px; border-radius: 9px; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-size: 11px; font-weight: 900; flex: 0 0 auto; }
        .tag-option:nth-child(3n+1) .tag-code { background: #eaf3ff; color: #0d5be8; }
        .tag-option:nth-child(3n+2) .tag-code { background: #f5edff; color: #7a4de2; }
        .tag-option:nth-child(3n+3) .tag-code { background: #e9fbf2; color: #15975d; }
        .side-stack { display: grid; gap: 18px; }
        .insight-card { padding: 24px; border-radius: 8px; position: relative; overflow: hidden; }
        .score { font-size: 36px; color: #0d5be8; font-weight: 900; margin: 14px 0 4px; }
        .visibility { color: #b4c5d8; font-size: 10px; font-weight: 900; letter-spacing: .8px; text-transform: uppercase; }
        .bars { display: flex; align-items: end; gap: 6px; height: 70px; margin-top: 18px; }
        .bars span { width: 10px; border-radius: 999px; background: #0d5be8; opacity: .35; }
        .bars span:nth-child(2) { height: 40px; }
        .bars span:nth-child(3) { height: 54px; opacity: .65; }
        .bars span:nth-child(4) { height: 66px; opacity: 1; }
        .bars.strength-low span:nth-child(n+2) { opacity: .18; }
        .bars.strength-medium span:nth-child(3) { opacity: .85; }
        .bars.strength-medium span:nth-child(4) { opacity: .22; }
        .trend-list { margin: 0; padding-left: 18px; color: #2f4053; line-height: 1.9; font-weight: 700; font-size: 13px; }
        .trend-list li::marker { color: #0d5be8; }
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
                    <div>
                        <div class="stat-label">Tag Utilization</div>
                        <div class="stat-value"><?php echo e($tagCount); ?>/10</div>
                    </div>
                    <div>
                        <div class="stat-label">Strength</div>
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
                                    <input type="checkbox" name="tagIDs[]" value="<?php echo e($tagID); ?>" data-tag-name="<?php echo e($tag["tagName"]); ?>" <?php echo in_array($tagID, $selectedTagIDs, true) ? "checked" : ""; ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="side-stack">
                    <section class="card insight-card">
                        <div class="stat-label">Expertise Strength</div>
                        <div class="score"><?php echo e($strength); ?></div>
                        <div class="visibility">Visibility Score</div>
                        <p style="color:#6b7f91; line-height:1.6;">Your tags currently cover <?php echo e($tagCount * 10); ?>% of relevant research queries in the department.</p>
                        <div class="bars strength-<?php echo e(strtolower($strength)); ?>"><span style="height:26px;"></span><span></span><span></span><span></span></div>
                    </section>
                    <section class="card insight-card">
                        <div class="stat-label">Trending In Research</div>
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
    <script>
        const selectedTagsBox = document.getElementById("selectedTagsBox");
        const activeTagCount = document.getElementById("activeTagCount");
        const tagCheckboxes = Array.from(document.querySelectorAll('input[name="tagIDs[]"]'));

        function renderSelectedTags() {
            const checkedTags = tagCheckboxes.filter(function(checkbox) {
                return checkbox.checked;
            });

            selectedTagsBox.innerHTML = "";

            if (checkedTags.length === 0) {
                selectedTagsBox.innerHTML = '<span class="selected-pill">No expertise selected</span>';
                activeTagCount.textContent = "0 tags active";
                return;
            }

            activeTagCount.textContent = checkedTags.length + " tags active";

            checkedTags.forEach(function(checkbox) {
                const pill = document.createElement("button");
                pill.className = "selected-pill selected-tag";
                pill.type = "button";
                pill.dataset.tagId = checkbox.value;
                pill.textContent = checkbox.dataset.tagName;
                selectedTagsBox.appendChild(pill);
            });
        }

        tagCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                const checkedTags = tagCheckboxes.filter(function(tagCheckbox) {
                    return tagCheckbox.checked;
                });

                if (checkedTags.length > 10) {
                    checkbox.checked = false;
                    alert("A maximum of 10 expertise tags can be selected.");
                }

                renderSelectedTags();
            });
        });

        selectedTagsBox.addEventListener("click", function(event) {
            const pill = event.target.closest(".selected-tag");

            if (!pill) {
                return;
            }

            const checkbox = document.querySelector('input[name="tagIDs[]"][value="' + pill.dataset.tagId + '"]');

            if (checkbox) {
                checkbox.checked = false;
                renderSelectedTags();
            }
        });

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


