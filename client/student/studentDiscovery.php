<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorDiscoveryService.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$discoveryService = new SupervisorDiscoveryService();
$searchName = trim($_GET["searchName"] ?? "");
$selectedProgramme = trim($_GET["programme"] ?? "");
$selectedAvailability = trim($_GET["availability"] ?? "");
$selectedInterestTagID = trim($_GET["interestTagID"] ?? "");

$programmes = $discoveryService->getProgrammes();
$researchTags = $discoveryService->getResearchTags();
$supervisors = $discoveryService->discoverSupervisors($_GET);
$recommendedMatches = $discoveryService->getRecommendedMatches($_SESSION["userID"]);
$hasSavedInterestTags = $discoveryService->hasSavedInterestTags($_SESSION["userID"]);

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function initials($name) {

    $parts = preg_split("/\s+/", trim((string) $name));
    $first = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "", 0, 1));

    return $first . $second;
}

function selected($left, $right) {

    return (string) $left === (string) $right ? "selected" : "";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discovery | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 30px 42px 48px; max-width: 100%; }
        .discovery-shell { width: 100%; max-width: 1420px; margin: 0; }
        .page-title { margin: 0 0 8px; color: #172033; font-size: 30px; line-height: 1.1; }
        .page-subtitle { margin: 0 0 28px; color: #5d7085; max-width: 680px; line-height: 1.55; font-size: 14px; }
        .search-panel, .filter-panel { background: #eef3f8; border: 1px solid #e5edf5; border-radius: 8px; padding: 22px; margin-bottom: 24px; }
        .filter-panel { display: grid; grid-template-columns: 1fr 1fr 1.1fr auto; gap: 16px; align-items: end; }
        .recommendation-panel { margin-bottom: 26px; }
        .recommendation-head { display: grid; gap: 6px; margin-bottom: 12px; }
        .recommendation-head h2 { margin: 0; color: #172033; font-size: 20px; }
        .recommendation-note { margin: 0; color: #5d7085; font-size: 14px; max-width: 560px; line-height: 1.45; }
        .match-strip { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .match-card { display: flex; align-items: center; justify-content: space-between; gap: 14px; background: #fff; border: 1px solid #d9e7f3; border-radius: 8px; padding: 14px; text-decoration: none; color: inherit; box-shadow: 0 8px 22px rgba(11,79,138,.06); }
        .match-card:hover { border-color: #0d5be8; }
        .match-name { margin: 0 0 4px; color: #172033; font-size: 15px; font-weight: 900; }
        .match-meta { color: #526a7f; font-size: 13px; }
        .match-score { flex: 0 0 auto; border-radius: 999px; background: #eaf3ff; color: #0d5be8; padding: 6px 10px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        label { display: block; color: #7c8da0; text-transform: uppercase; letter-spacing: 1px; font-size: 14px; font-weight: 900; margin-bottom: 8px; }
        input, select { width: 100%; height: 42px; border: 1px solid #dbe6f0; border-radius: 5px; background: #fff; color: #172033; padding: 0 12px; font-size: 14px; }
        .availability-tabs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .tab { min-height: 42px; border: 0; border-radius: 5px; background: #fff; color: #526a7f; font-size: 14px; font-weight: 900; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px; }
        .tab.active { background: #003f8f; color: #fff; }
        .filter-actions { display: grid; gap: 10px; min-width: 130px; }
        .button { min-height: 42px; border: 0; border-radius: 5px; padding: 0 14px; background: #003f8f; color: #fff; text-decoration: none; font-size: 14px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .button.secondary { background: #fff; color: #526a7f; }
        .cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 28px; align-items: stretch; }
        .card { background: #fff; border: 1px solid #d9e7f3; border-radius: 8px; overflow: hidden; box-shadow: 0 8px 22px rgba(11,79,138,.07); }
        .card-visual { height: 210px; background: linear-gradient(135deg, #5c1748, #0e7f79); position: relative; display: grid; place-items: center; overflow: hidden; }
        .card:nth-child(2n) .card-visual { background: linear-gradient(135deg, #f8fbff, #e3edf8); }
        .card:nth-child(3n) .card-visual { background: linear-gradient(135deg, #128e88, #44b2a6); }
        .portrait { width: 122px; height: 122px; border-radius: 16px; background: rgba(255,255,255,.92); color: #0b3760; display: grid; place-items: center; font-size: 38px; font-weight: 900; border: 3px solid rgba(255,255,255,.7); overflow: hidden; }
        .portrait img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .badge-stack { position: absolute; top: 12px; left: 12px; display: grid; gap: 6px; }
        .status-badge, .quota-badge { width: max-content; border-radius: 999px; padding: 6px 10px; color: #fff; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .status-badge.online { background: #118549; }
        .status-badge.offline { background: #64748b; }
        .status-badge.available { background: #118549; }
        .status-badge.full { background: #c02d2d; }
        .quota-badge { background: #0d5be8; }
        .quota-badge.full { background: #c02d2d; }
        .card-body { padding: 22px; }
        .supervisor-name { margin: 0 0 6px; color: #172033; font-size: 20px; }
        .meta { color: #526a7f; font-size: 14px; line-height: 1.5; min-height: 40px; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 7px; margin: 18px 0 22px; min-height: 28px; }
        .tag { padding: 6px 8px; border-radius: 3px; background: #eef3f8; color: #526a7f; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .mini-avatars { display: flex; align-items: center; }
        .mini-dot { width: 26px; height: 26px; border-radius: 50%; background: #eef3f8; color: #172033; display: grid; place-items: center; font-size: 12px; font-weight: 900; margin-right: -6px; border: 2px solid #fff; }
        .apply-button.disabled { background: #e9eef5; color: #a4b3c4; pointer-events: none; }
        .empty-state { background: #fff; border: 1px dashed #aac7df; border-radius: 8px; padding: 28px; color: #526a7f; text-align: center; }
        @media (max-width: 1180px) { .cards, .match-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); } .filter-panel { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 900px) { .cards, .match-strip { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .main { padding: 22px; } .cards, .filter-panel { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("discovery"); ?>
        <main class="main">
            <div class="discovery-shell">
                <h1 class="page-title">Discovery</h1>
                <p class="page-subtitle">Find and connect with world-class academic supervisors. Match your research interests with TAR UMT's leading experts and innovators.</p>

                <section class="recommendation-panel">
                    <div class="recommendation-head">
                        <h2>Top Matches For You</h2>
                        <p class="recommendation-note">Based on your saved research interests and supervisors marked Available.</p>
                    </div>

                    <?php if (empty($recommendedMatches)): ?>
                        <div class="empty-state">
                            <?php echo $hasSavedInterestTags
                                ? "No recommended supervisors currently match your saved interests with Available slot status. You can still browse all supervisors below."
                                : "Update your Research Interests in your Profile to unlock personalised supervisor recommendations."; ?>
                        </div>
                    <?php else: ?>
                        <div class="match-strip">
                            <?php foreach ($recommendedMatches as $match): ?>
                                <a class="match-card" href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($match["userID"]); ?>">
                                    <div>
                                        <p class="match-name"><?php echo e($match["fullName"]); ?></p>
                                        <div class="match-meta"><?php echo e($match["programme"]); ?> - <?php echo e(implode(", ", array_slice($match["tagNames"], 0, 2))); ?></div>
                                    </div>
                                    <span class="match-score"><?php echo e($match["matchScore"]); ?> match</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <form method="GET" action="studentDiscovery.php">
                    <section class="search-panel">
                        <label for="searchName">Search Bar</label>
                        <input type="text" id="searchName" name="searchName" value="<?php echo e($searchName); ?>" placeholder="Name">
                    </section>

                    <section class="filter-panel">
                        <div>
                            <label for="programme">Programme</label>
                            <select id="programme" name="programme">
                                <option value="">All Programmes</option>
                                <?php foreach ($programmes as $programme): ?>
                                    <option value="<?php echo e($programme["programme"]); ?>" <?php echo selected($selectedProgramme, $programme["programme"]); ?>>
                                        <?php echo e($programme["programme"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="interestTagID">Research Interest</label>
                            <select id="interestTagID" name="interestTagID">
                                <option value="">Any Interest</option>
                                <?php foreach ($researchTags as $tag): ?>
                                    <option value="<?php echo e($tag["tagID"]); ?>" <?php echo selected($selectedInterestTagID, $tag["tagID"]); ?>>
                                        <?php echo e($tag["tagName"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Availability Status</label>
                            <div class="availability-tabs">
                                <button class="tab <?php echo $selectedAvailability === "" ? "active" : ""; ?>" type="submit" name="availability" value="">All</button>
                                <button class="tab <?php echo $selectedAvailability === "Available" ? "active" : ""; ?>" type="submit" name="availability" value="Available">Available</button>
                                <button class="tab <?php echo $selectedAvailability === "Full" ? "active" : ""; ?>" type="submit" name="availability" value="Full">Full</button>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button class="button" type="submit">Apply Filters</button>
                            <a class="button secondary" href="studentDiscovery.php">Reset Filters</a>
                        </div>
                    </section>
                </form>

                <?php if (empty($supervisors)): ?>
                    <div class="empty-state">No supervisors match your selected criteria. Please adjust your filters and try again.</div>
                <?php else: ?>
                    <section class="cards">
                        <?php foreach ($supervisors as $supervisor): ?>
                            <?php
                                $isOffline = $supervisor["status"] === "Offline";
                                $badgeClass = $supervisor["statusClass"] ?? ($isOffline ? "offline" : "online");
                                $quotaClass = $supervisor["quotaClass"] ?? "full";
                                $availabilityLabel = $supervisor["quotaStatus"] ?? "Full";
                                $used = (int) ($supervisor["activeStudents"] ?? 0);
                                $max = (int) ($supervisor["maxSlots"] ?? 0);
                            ?>
                            <article class="card">
                                <div class="card-visual">
                                    <div class="badge-stack">
                                        <span class="status-badge <?php echo e($badgeClass); ?>"><?php echo e($supervisor["status"]); ?></span>
                                        <span class="quota-badge <?php echo e($quotaClass); ?>"><?php echo e($availabilityLabel); ?>: <?php echo e($used); ?> / <?php echo e($max); ?></span>
                                    </div>
                                    <div class="portrait">
                                        <?php if (!empty($supervisor["profilePhotoPath"])): ?>
                                            <img src="<?php echo e($supervisor["profilePhotoPath"]); ?>" alt="">
                                        <?php else: ?>
                                            <?php echo e(initials($supervisor["fullName"])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h2 class="supervisor-name"><?php echo e($supervisor["fullName"]); ?></h2>
                                    <div class="meta"><?php echo e($supervisor["employmentCategory"]); ?>, <?php echo e($supervisor["programme"]); ?></div>
                                    <div class="tag-list">
                                        <span class="tag"><?php echo e($supervisor["programme"]); ?></span>
                                        <span class="tag"><?php echo e($supervisor["employmentCategory"]); ?></span>
                                        <?php foreach (array_slice($supervisor["tagNames"] ?? [], 0, 3) as $tagName): ?>
                                            <span class="tag"><?php echo e($tagName); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (!empty($supervisor["activeTime"])): ?>
                                            <span class="tag">Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="mini-avatars">
                                            <span class="mini-dot"><?php echo e(substr($supervisor["programme"], 0, 2)); ?></span>
                                            <span class="mini-dot">+<?php echo e(max(0, $max - $used)); ?></span>
                                        </div>
                                        <a class="button apply-button" href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($supervisor["userID"]); ?>">View Profile</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
