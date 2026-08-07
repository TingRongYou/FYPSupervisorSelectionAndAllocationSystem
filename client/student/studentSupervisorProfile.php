<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorProfileService.php";
require_once __DIR__ . "/../../server/business/services/AllocationWindowService.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$studentID = $_SESSION["userID"] ?? "";
$requestDAO = new RequestDAO();
$studentAlreadyAllocated = $requestDAO -> studentHasAllocation($studentID); // Check allocation status
$supervisorID = trim($_GET["supervisorID"] ?? "");
$profileService = new SupervisorProfileService();
$profile = $supervisorID !== "" ? $profileService->getPublicProfessionalProfile($supervisorID) : null;
$allocationWindowService = new AllocationWindowService();
$allocationWindow = $allocationWindowService->getWindow(); // Retrieve active allocation time window

function videoEmbedUrl($url) {
    if (preg_match("/youtu\.be\/([^?&]+)/", $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1]; // return specific link to be placed in iframe
    }

    if (preg_match("/youtube\.com\/watch\?v=([^?&]+)/", $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }

    if (preg_match("/vimeo\.com\/([0-9]+)/", $url, $matches)) {
        return "https://player.vimeo.com/video/" . $matches[1];
    }

    return "";
}

function projectPdfUrl($path) {
    $fileName = basename((string) $path);

    return "../../storage/past_projects/" . rawurlencode($fileName);
}

function projectImageUrl($path) {
    $fileName = basename((string) $path);

    return "../../storage/past_project_images/" . rawurlencode($fileName);
}

$isOnline = $profile && $profile["status"] === "Online";
$canApply = $profile && (bool) ($profile["canApply"] ?? false);
$quotaStatus = $profile["quotaStatus"] ?? "Full";
$quotaText = $profile["quotaText"] ?? "0/0 supervisees";
$quotaNumbers = preg_replace("/[^0-9\/]/", "", $quotaText);
$quotaLabel = $quotaStatus === "Available" ? "Available" : "Full";
$activeTime = $profile["activeTime"] ?? "Consultation by appointment";
$bio = $profile["supervisorBio"] ?? "This supervisor has not added a biography yet.";
$expertiseTags = $profile["expertiseTags"] ?? [];
$pastProjects = $profile["pastProjects"] ?? [];
$videoLink = !empty($profile["hasIntroVideo"]) ? ($profile["introVideoLink"] ?? "") : "";
$videoDescription = $profile["introVideoDescription"] ?? "";
$isUploadedVideo = preg_match("/\/storage\/intro_videos\/.+\.(mp4|webm)$/i", $videoLink) === 1;
$embedUrl = $videoLink !== "" && !$isUploadedVideo ? videoEmbedUrl($videoLink) : "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Supervisor Profile", "student"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo ssasPortalSidebar("discovery"); ?>
        <main class="main">
            <div class="profile-shell">
                <?php if (!$profile): ?>
                    <section class="empty">Supervisor profile was not found.</section>
                <?php else: ?>

                    <div class="breadcrumb">
                        <a href="studentDiscovery.php">DISCOVERY</a> &gt; SUPERVISOR PROFILE
                    </div>

                    <section class="profile-hero">
                        <div class="hero-avatar-stack">
                            <div class="photo-frame <?php echo $isOnline ? "" : "offline"; ?>">
                                <div class="portrait">
                                    <?php if (!empty($profile["profilePhotoPath"])): ?>
                                        <img src="<?php echo ssasEscape($profile["profilePhotoPath"]); ?>" alt="">
                                    <?php else: ?>
                                        <div class="initials"><?php echo ssasEscape(ssasInitials($profile["fullName"])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="quota-pill <?php echo $quotaStatus === "Available" ? "" : "full"; ?>">
                                <?php echo ssasEscape($quotaLabel); ?> - <?php echo ssasEscape($quotaNumbers); ?>
                            </div>
                        </div>
                        
                        <div class="hero-info">
                            <h1 class="profile-name"><?php echo ssasEscape($profile["fullName"]); ?></h1>
                            <?php if (trim($profile["message"] ?? "") !== ""): ?>
                                <div class="availability-note"><?php echo ssasEscape($profile["message"]); ?></div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="info-card">
                        <div class="role"><?php echo ssasEscape($profile["employmentCategory"]); ?></div>
                        <div class="programme">Programme: <?php echo ssasEscape($profile["programme"]); ?></div>
                        <div class="bio"><?php echo ssasEscape($bio); ?></div>
                        <div class="detail-list">
                            <div class="detail-item"><span class="detail-icon">@</span><?php echo ssasEscape($profile["universityEmail"]); ?></div>
                            <div class="detail-item"><span class="detail-icon">T</span><?php echo ssasEscape($activeTime); ?></div>
                        </div>
                    </section>

                    <?php if (!empty($expertiseTags)): ?>
                        <section class="profile-section">
                            <h2 class="section-title">Expertise & Tags</h2>
                            <div class="tag-list">
                                <?php foreach ($expertiseTags as $tagName): ?>
                                    <span class="tag-pill"><?php echo ssasEscape($tagName); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($videoLink)): ?>
                        <section class="profile-section">
                            <h2 class="section-title">Introductory Video</h2>
                            <div class="video-frame">
                                <?php if ($isUploadedVideo): ?> <!-- Support both embedded video and uploaded video-->
                                    <video controls src="<?php echo ssasEscape($videoLink); ?>"></video>
                                <?php elseif ($embedUrl !== ""): ?>
                                    <iframe src="<?php echo ssasEscape($embedUrl); ?>" title="Introductory video" allowfullscreen></iframe>
                                <?php endif; ?>
                            </div>
                            <?php if (trim($videoDescription) !== ""): ?>
                                <p class="video-description"><?php echo ssasEscape($videoDescription); ?></p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($pastProjects)): ?>
                        <section class="profile-section">
                            <h2 class="section-title">Past Projects Showcase</h2>
                            <div class="project-list">
                                <?php foreach (array_slice($pastProjects, 0, 4) as $project): ?>
                                    <article class="project-item">
                                        <div class="project-cover">
                                            <?php if (!empty($project["projectImagePath"])): ?>
                                                <img src="<?php echo ssasEscape(projectImageUrl($project["projectImagePath"])); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo ssasEscape($project["projectTitle"]); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="project-year"><?php echo ssasEscape($project["completionYear"]); ?> Academic Year</div>
                                        <h3 class="project-name"><?php echo ssasEscape($project["projectTitle"]); ?></h3>
                                        <p class="project-desc"><?php echo ssasEscape($project["projectDescription"] ?: "No project description has been added yet."); ?></p>
                                        <div class="project-alumni">Completed by <?php echo ssasEscape($project["alumniName"]); ?></div>
                                        <?php if (!empty($project["projectPDFPath"])): ?>
                                            <a class="project-pdf" href="<?php echo ssasEscape(projectPdfUrl($project["projectPDFPath"])); ?>" target="_blank" rel="noopener">View Project PDF</a>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <div class="actions">
                        <?php if ($studentAlreadyAllocated): ?>
                            <span class="button disabled">Already Allocated</span>

                        <?php elseif ($canApply && $allocationWindow["canStudentsSubmit"]): ?>
                            <a class="button" href="submitProposalForm.php?supervisorID=<?php echo urlencode($profile["userID"]); ?>">Submit Proposal</a>

                        <?php elseif ($canApply): ?>
                            <span class="button disabled"><?php echo ssasEscape($allocationWindow["status"] === "closed" ? "Selection Closed" : "Selection Not Open"); ?></span>

                        <?php else: ?>
                            <span class="button disabled"><?php echo ssasEscape($profile["buttonLabel"] ?? "Applications Closed"); ?></span>
                            
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>