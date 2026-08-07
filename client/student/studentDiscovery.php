<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorDiscoveryService.php";
require_once __DIR__ . "/../../server/data/dao/TagDAO.php";
require_once __DIR__ . "/../shared/accountLayout.php";

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

function selected($left, $right) {
    return (string) $left === (string) $right ? "selected" : ""; // $left is the current tag selected, $right is the new tag selected
    // If they match, return "selected", else return empty string
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Discovery", "student"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo ssasPortalSidebar("discovery"); ?>
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
                                                <img src="<?php echo ssasEscape($match["profilePhotoPath"]); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo ssasEscape(ssasInitials($match["fullName"])); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="top-right">
                                            <span class="status-pill <?php echo ssasEscape($statusClass); ?>"><?php echo ssasEscape($match["status"]); ?></span>
                                            <div class="quota">
                                                <?php echo ssasEscape($availabilityLabel); ?>
                                                <strong><?php echo ssasEscape($quotaUsed); ?> / <?php echo ssasEscape($quotaMax); ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="name-row">
                                        <h2 class="supervisor-name"><?php echo ssasEscape($match["fullName"]); ?></h2>
                                        <?php if ($matchCount > 0): ?>
                                            <span class="match-score"><?php echo $matchCount; ?> Match<?php echo $matchCount === 1 ? '' : 'es'; ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="specialty">
                                        Specialization: <?php echo ssasEscape($match["programme"]); ?>, <?php echo ssasEscape($match["employmentCategory"]); ?>
                                    </div>

                                    <div class="tag-list">
                                        <span class="tag structural"><?php echo ssasEscape($match["programme"]); ?></span>
                                        <span class="tag structural"><?php echo ssasEscape($match["employmentCategory"]); ?></span>
                                        
                                        <?php foreach ($matchedTags as $tagName): ?>
                                            <span class="tag"><?php echo ssasEscape($tagName); ?></span>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (!$canApply): ?>
                                        <span class="btn-apply disabled"><?php echo ssasEscape($match["buttonLabel"] ?? "Application Closed"); ?></span>
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
                        <input type="text" id="searchName" name="searchName" value="<?php echo ssasEscape($searchName); ?>" placeholder="Search by supervisor name">
                    </section>

                    <section class="filter-panel">
                        <div>
                            <label for="programme">Programme</label>
                            <select id="programme" name="programme">
                                <option value="">All Programmes</option>
                                <?php foreach ($programmes as $programme): ?>
                                    <option value="<?php echo ssasEscape($programme["programme"]); ?>" <?php echo selected($selectedProgramme, $programme["programme"]); ?>>
                                        <?php echo ssasEscape($programme["programme"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="interestTagID">Research Interest</label>
                            <select id="interestTagID" name="interestTagID">
                                <option value="">Any Interest</option>
                                <?php foreach ($researchTags as $tag): ?>
                                    <option value="<?php echo ssasEscape($tag["tagID"]); ?>" <?php echo selected($selectedInterestTagID, $tag["tagID"]); ?>>
                                        <?php echo ssasEscape($tag["tagName"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Availability Status</label>
                            <input type="hidden" id="availabilityInput" name="availability" value="<?php echo ssasEscape($selectedAvailability); ?>">
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
                                        <span class="status-badge <?php echo ssasEscape($badgeClass); ?>"><?php echo ssasEscape($supervisor["status"]); ?></span>
                                        <span class="quota-badge <?php echo ssasEscape($quotaClass); ?>"><?php echo ssasEscape($availabilityLabel); ?>: <?php echo ssasEscape($used); ?> / <?php echo ssasEscape($max); ?></span>
                                    </div>
                                    <div class="portrait">
                                        <?php if (!empty($supervisor["profilePhotoPath"])): ?>
                                            <img src="<?php echo ssasEscape($supervisor["profilePhotoPath"]); ?>" alt="">
                                        <?php else: ?>
                                            <?php echo ssasEscape(ssasInitials($supervisor["fullName"])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h2 class="supervisor-name"><?php echo ssasEscape($supervisor["fullName"]); ?></h2>
                                    <div class="meta"><?php echo ssasEscape($supervisor["employmentCategory"]); ?>, <?php echo ssasEscape($supervisor["programme"]); ?></div>
                                    <div class="tag-list">
                                        <span class="tag structural"><?php echo ssasEscape($supervisor["programme"]); ?></span>
                                        <span class="tag structural"><?php echo ssasEscape($supervisor["employmentCategory"]); ?></span>
                                        <?php foreach (array_slice($supervisor["tagNames"] ?? [], 0, 3) as $tagName): ?>
                                            <span class="tag"><?php echo ssasEscape($tagName); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (!empty($supervisor["activeTime"])): ?>
                                            <span class="tag">Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="mini-avatars">
                                            <span class="mini-dot"><?php echo ssasEscape(substr($supervisor["programme"], 0, 3)); ?></span>
                                            <span class="mini-dot">+<?php echo ssasEscape(max(0, $max - $used)); ?></span>
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
