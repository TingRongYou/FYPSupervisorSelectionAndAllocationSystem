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
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 54px 60px; }
        .profile-shell { width: 100%; max-width: 1120px; margin: 0 auto; }
        .profile-hero { display: grid; grid-template-columns: 360px 1fr; gap: 70px; align-items: end; margin-bottom: 38px; }
        .profile-name { margin: 0 0 18px; color: #003f8f; font-size: 34px; line-height: 1.1; }
        .photo-frame { width: 360px; height: 250px; border: 1px solid #cfdbea; background: #fff; box-shadow: 0 16px 34px rgba(11,79,138,.08); padding: 14px; display: grid; place-items: center; }
        .portrait { width: 100%; height: 100%; background: linear-gradient(135deg, #5c1748, #0e7f79); display: grid; place-items: center; overflow: hidden; }
        .portrait img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .initials { width: 150px; height: 150px; border-radius: 16px; background: rgba(255,255,255,.9); color: #0b3760; display: grid; place-items: center; font-size: 48px; font-weight: 900; }
        .status-stack { display: grid; gap: 16px; width: 240px; margin-bottom: 28px; }
        .status-pill, .quota-pill { min-height: 46px; border-radius: 8px; background: #178f56; color: #fff; display: grid; place-items: center; font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }
        .status-pill.offline { background: #64748b; }
        .quota-pill.full { background: #c02d2d; }
        .availability-note { color: #526a7f; line-height: 1.5; font-size: 14px; }
        .info-card { width: 860px; max-width: 100%; background: #fff; border: 1px solid #e3edf6; border-radius: 16px; box-shadow: 0 16px 34px rgba(11,79,138,.08); padding: 36px; }
        .role { color: #0d5be8; font-size: 17px; font-weight: 900; margin-bottom: 4px; }
        .programme { color: #9aacc0; font-size: 16px; font-weight: 800; margin-bottom: 22px; }
        .bio { color: #526a7f; line-height: 1.55; max-width: 650px; margin-bottom: 26px; font-size: 16px; }
        .detail-list { display: grid; gap: 15px; color: #526a7f; font-size: 15px; }
        .detail-item { display: flex; align-items: center; gap: 10px; }
        .detail-icon { width: 22px; height: 22px; border-radius: 5px; background: #eef3f8; color: #0d5be8; display: grid; place-items: center; font-size: 11px; font-weight: 900; }
        .profile-section { width: 860px; max-width: 100%; margin-top: 22px; background: #fff; border: 1px solid #e3edf6; border-radius: 12px; box-shadow: 0 16px 34px rgba(11,79,138,.08); padding: 28px; }
        .section-title { margin: 0 0 16px; color: #172033; font-size: 18px; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .tag-pill { border-radius: 999px; background: #eaf3ff; color: #0d5be8; padding: 8px 12px; font-size: 13px; font-weight: 900; }
        .video-frame { width: 100%; aspect-ratio: 16 / 9; border-radius: 8px; overflow: hidden; background: #111827; }
        .video-frame iframe, .video-frame video { width: 100%; height: 100%; border: 0; display: block; }
        .video-description { margin: 14px 0 0; color: #526a7f; line-height: 1.6; }
        .project-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .project-item { border: 1px solid #edf2f7; border-radius: 8px; padding: 16px; background: #fbfdff; }
        .project-cover { width: 100%; aspect-ratio: 16 / 9; border-radius: 8px; overflow: hidden; background: linear-gradient(135deg, #08233d, #0d5be8); margin-bottom: 14px; display: grid; place-items: center; color: #fff; font-weight: 900; text-align: center; padding: 18px; }
        .project-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .project-year { color: #0d5be8; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .project-name { margin: 6px 0; color: #172033; font-size: 15px; line-height: 1.35; }
        .project-desc { color: #526a7f; font-size: 14px; line-height: 1.55; margin: 8px 0 12px; }
        .project-alumni { color: #526a7f; font-size: 13px; }
        .project-pdf { display: inline-flex; align-items: center; justify-content: center; min-height: 32px; padding: 0 11px; margin-top: 12px; border-radius: 6px; background: #eaf3ff; color: #0d5be8; text-decoration: none; font-size: 13px; font-weight: 900; }
        .verified { margin-top: 24px; display: flex; align-items: center; gap: 10px; color: #9aacc0; letter-spacing: 1.4px; text-transform: uppercase; font-size: 13px; font-weight: 900; }
        .verified-icons { display: flex; gap: 4px; }
        .verified-icons span { width: 18px; height: 18px; border-radius: 50%; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-size: 9px; }
        .actions { margin-top: 18px; }
        .button { min-height: 42px; border: 0; border-radius: 7px; padding: 0 22px; background: #003f8f; color: #fff; text-decoration: none; font-size: 12px; font-weight: 900; text-transform: uppercase; display: inline-flex; align-items: center; gap: 10px; }
        .button.disabled { background: #e9eef5; color: #9aacc0; pointer-events: none; }
        .empty { background: #fff; border: 1px dashed #aac7df; border-radius: 8px; padding: 28px; color: #526a7f; }
        @media (max-width: 900px) {
            .main { padding: 28px 22px; }
            .profile-shell { max-width: none; }
            .profile-hero, .project-list { grid-template-columns: 1fr; gap: 24px; }
            .portrait, .status-stack { width: 100%; }
        }
    </style>
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
                            <span class="verified-icons"><span>m</span><span>i</span><span>&lt;</span></span>
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
