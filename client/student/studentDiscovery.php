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
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("discovery"); ?>
        <main class="main">
            <div class="discovery-shell">
                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Supervisor Matching</p>
                        <h1>Discovery</h1>
                        <p class="subtitle">Find and connect with world-class academic supervisors. Match your research interests with TAR UMT's leading experts and innovators.</p>
                    </div>
                </section>

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
                        <label for="searchName">Search</label>
                        <input type="text" id="searchName" name="searchName" value="<?php echo e($searchName); ?>" placeholder="Search by supervisor name">
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
                            <article class="card discovery-card">
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
