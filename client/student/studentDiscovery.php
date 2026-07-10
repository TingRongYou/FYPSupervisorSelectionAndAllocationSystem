<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorDiscoveryService.php";
require_once __DIR__ . "/../../server/data/dao/TagDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$tagDAO = new TagDAO();
$studentTagIDs = $tagDAO->getStudentTagIDs($_SESSION["userID"]) ?: [];

$discoveryService = new SupervisorDiscoveryService();
$searchName = trim($_GET["searchName"] ?? "");
$selectedProgramme = trim($_GET["programme"] ?? "");
$selectedAvailability = trim($_GET["availability"] ?? "");
$selectedInterestTagID = trim($_GET["interestTagID"] ?? "");

$programmes = $discoveryService->getProgrammes(); // Get supervisor programme
$researchTags = $discoveryService->getResearchTags(); // Get supervisor research tags
$supervisors = $discoveryService->discoverSupervisors($_GET); // Get all supervisors based on the search and filter criteria
$recommendedMatches = $discoveryService->getRecommendedMatches($_SESSION["userID"]); // Get recommended supervisors
$hasSavedInterestTags = $discoveryService->hasSavedInterestTags($_SESSION["userID"]); // Check if student has saved interest tags

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

    return (string) $left === (string) $right ? "selected" : ""; // $left is the current tag selected, $right is the new tag selected
    // If they match, return "selected", else return empty string
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

                <section class="recommendation-panel recommendation-column">
                    <div class="recommendation-head">
                        <h2>Top Matches For You</h2>
                        <p class="recommendation-note">Based on your saved research interests and supervisors marked Available.</p>
                    </div>

                    <section class="supervisor-grid">
                        <?php if (empty($recommendedMatches)): ?>
                            <article class="supervisor-card empty-grid-card">
                                <p class="empty-state">
                                    <?php echo $hasSavedInterestTags
                                        ? "No recommended supervisors currently match your saved interests with Available slot status. You can still browse all supervisors below."
                                        : "Update your Research Interests in your Profile to unlock personalised supervisor recommendations."; ?>
                                </p>
                            </article>
                        <?php else: ?>
                            <?php foreach ($recommendedMatches as $match): ?>
                                <?php
                                    $isOffline   = $match["status"] === "Offline";
                                    $statusClass = $match["statusClass"] ?? ($isOffline ? "offline" : "online");
                                    $canApply    = (bool) ($match["canApply"] ?? false);
                                    $availabilityLabel = $match["quotaStatus"] ?? "Full";
                                    $quotaParts  = explode("/", $match["quotaText"]);
                                    $quotaUsed   = trim($quotaParts[0] ?? "0");
                                    $quotaMax    = preg_replace("/[^0-9]/", "", $quotaParts[1] ?? (string) $match["maxSlots"]);
                                    
                                    // Filter ONLY the tags that match the student's interests
                                    $matchedTags = [];
                                    if (!empty($match["tagIDs"]) && !empty($match["tagNames"])) {
                                        foreach ($match["tagIDs"] as $index => $tagID) {
                                            if (in_array($tagID, $studentTagIDs)) {
                                                $matchedTags[] = $match["tagNames"][$index];
                                            }
                                        }
                                    }
                                    $matchCount = count($matchedTags);
                                ?>
                                <article class="supervisor-card">
                                    <div class="supervisor-top">
                                        <div class="avatar <?php echo $isOffline ? "offline" : ""; ?>">
                                            <?php if (!empty($match["profilePhotoPath"])): ?>
                                                <img src="<?php echo e($match["profilePhotoPath"]); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo e(initials($match["fullName"])); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="top-right">
                                            <span class="status-pill <?php echo e($statusClass); ?>"><?php echo e($match["status"]); ?></span>
                                            <div class="quota">
                                                <?php echo e($availabilityLabel); ?>
                                                <strong><?php echo e($quotaUsed); ?> / <?php echo e($quotaMax); ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="name-row">
                                        <h2 class="supervisor-name"><?php echo e($match["fullName"]); ?></h2>
                                        <?php if ($matchCount > 0): ?>
                                            <span class="match-score"><?php echo $matchCount; ?> Match<?php echo $matchCount === 1 ? '' : 'es'; ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="specialty">
                                        Specialization: <?php echo e($match["programme"]); ?>, <?php echo e($match["employmentCategory"]); ?>
                                    </div>

                                    <div class="tag-list">
                                        <span class="tag structural"><?php echo e($match["programme"]); ?></span>
                                        <span class="tag structural"><?php echo e($match["employmentCategory"]); ?></span>
                                        
                                        <?php foreach ($matchedTags as $tagName): ?>
                                            <span class="tag"><?php echo e($tagName); ?></span>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (!$canApply): ?>
                                        <span class="btn-apply disabled"><?php echo e($match["buttonLabel"] ?? "Application Closed"); ?></span>
                                    <?php else: ?>
                                        <a class="btn-apply" href="studentSupervisorProfile.php?supervisorID=<?php echo urlencode($match["userID"]); ?>">
                                            Apply for Supervision
                                        </a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
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
                            <input type="hidden" id="availabilityInput" name="availability" value="<?php echo e($selectedAvailability); ?>">
                            <div class="availability-tabs">
                            <button class="tab availability-tab <?php echo $selectedAvailability === "" ? "active" : ""; ?>" type="button" data-value="">All</button>
                            <button class="tab availability-tab <?php echo $selectedAvailability === "Available" ? "active" : ""; ?>" type="button" data-value="Available">Available</button>
                            <button class="tab availability-tab <?php echo $selectedAvailability === "Full" ? "active" : ""; ?>" type="button" data-value="Full">Full</button>
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
                                            <span class="mini-dot"><?php echo e(substr($supervisor["programme"], 0, 3)); ?></span>
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
