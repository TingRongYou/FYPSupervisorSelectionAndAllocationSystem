<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorProfileService.php";
require_once __DIR__ . "/../../server/business/services/AllocationWindowService.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$supervisorID = trim($_GET["supervisorID"] ?? "");
$profileService = new SupervisorProfileService();
$profile = $supervisorID !== "" ? $profileService->getPublicProfessionalProfile($supervisorID) : null;
$allocationWindowService = new AllocationWindowService();
$allocationWindow = $allocationWindowService->getWindow();

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function initials($name) {

    $parts = preg_split("/\s+/", trim((string) $name));
    $first = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "", 0, 1));

    return $first . $second;
}

function videoEmbedUrl($url) {

    if (preg_match("/youtu\.be\/([^?&]+)/", $url, $matches)) {

        return "https://www.youtube.com/embed/" . $matches[1];
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

    $fileName =
        basename((string) $path);

    return "../../storage/past_projects/" . rawurlencode($fileName);
}

function projectImageUrl($path) {

    $fileName =
        basename((string) $path);

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
$videoLink = !empty($profile["hasIntroVideo"])
    ? ($profile["introVideoLink"] ?? "")
    : "";
$videoDescription = $profile["introVideoDescription"] ?? "";
$isUploadedVideo = preg_match("/\/storage\/intro_videos\/.+\.(mp4|webm)$/i", $videoLink) === 1;
$embedUrl = $videoLink !== "" && !$isUploadedVideo ? videoEmbedUrl($videoLink) : "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Profile | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("discovery"); ?>
        <main class="main">
            <div class="profile-shell">
                <?php if (!$profile): ?>
                    <section class="empty">Supervisor profile was not found.</section>
                <?php else: ?>
                    <section class="profile-hero">
                        <div>
                            <h1 class="profile-name"><?php echo e($profile["fullName"]); ?></h1>
                            <div class="photo-frame">
                                <div class="portrait">
                                    <?php if (!empty($profile["profilePhotoPath"])): ?>
                                        <img src="<?php echo e($profile["profilePhotoPath"]); ?>" alt="">
                                    <?php else: ?>
                                        <div class="initials"><?php echo e(initials($profile["fullName"])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="status-stack">
                            <div class="status-pill <?php echo $isOnline ? "" : "offline"; ?>"><?php echo e($profile["status"]); ?></div>
                            <div class="quota-pill <?php echo $quotaStatus === "Available" ? "" : "full"; ?>"><?php echo e($quotaLabel); ?> - <?php echo e($quotaNumbers); ?></div>
                            <div class="availability-note"><?php echo e($profile["message"] ?? ""); ?></div>
                        </div>
                    </section>

                    <section class="info-card">
                        <div class="role"><?php echo e($profile["employmentCategory"]); ?></div>
                        <div class="programme">Programme: <?php echo e($profile["programme"]); ?></div>
                        <div class="bio"><?php echo e($bio); ?></div>
                        <div class="detail-list">
                            <div class="detail-item"><span class="detail-icon">@</span><?php echo e($profile["universityEmail"]); ?></div>
                            <div class="detail-item"><span class="detail-icon">T</span><?php echo e($activeTime); ?></div>
                        </div>
                        <div class="verified">
                            <div class="verified-icons"><span>m</span><span>i</span><span>&lt;</span></div>
                            Verified Academic
                        </div>
                    </section>

                    <?php if (!empty($expertiseTags)): ?>
                        <section class="profile-section">
                            <h2 class="section-title">Expertise & Tags</h2>
                            <div class="tag-list">
                                <?php foreach ($expertiseTags as $tagName): ?>
                                    <span class="tag-pill"><?php echo e($tagName); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($videoLink)): ?>
                        <section class="profile-section">
                            <h2 class="section-title">Introductory Video</h2>
                            <div class="video-frame">
                                <?php if ($isUploadedVideo): ?>
                                    <video controls src="<?php echo e($videoLink); ?>"></video>
                                <?php elseif ($embedUrl !== ""): ?>
                                    <iframe src="<?php echo e($embedUrl); ?>" title="Introductory video" allowfullscreen></iframe>
                                <?php endif; ?>
                            </div>
                            <?php if (trim($videoDescription) !== ""): ?>
                                <p class="video-description"><?php echo e($videoDescription); ?></p>
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
                                                <img src="<?php echo e(projectImageUrl($project["projectImagePath"])); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo e($project["projectTitle"]); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="project-year"><?php echo e($project["completionYear"]); ?> Academic Year</div>
                                        <h3 class="project-name"><?php echo e($project["projectTitle"]); ?></h3>
                                        <p class="project-desc"><?php echo e($project["projectDescription"] ?: "No project description has been added yet."); ?></p>
                                        <div class="project-alumni">Completed by <?php echo e($project["alumniName"]); ?></div>
                                        <?php if (!empty($project["projectPDFPath"])): ?>
                                            <a class="project-pdf" href="<?php echo e(projectPdfUrl($project["projectPDFPath"])); ?>" target="_blank" rel="noopener">View Project PDF</a>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <div class="actions">
                        <?php if ($canApply && $allocationWindow["canStudentsSubmit"]): ?>
                            <a class="button" href="submitProposalForm.php?supervisorID=<?php echo urlencode($profile["userID"]); ?>">Submit Proposal</a>
                        <?php elseif ($canApply): ?>
                            <span class="button disabled"><?php echo e($allocationWindow["status"] === "closed" ? "Selection Closed" : "Selection Not Open"); ?></span>
                        <?php else: ?>
                            <span class="button disabled"><?php echo e($profile["buttonLabel"] ?? "Applications Closed"); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>