<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorProfileService.php";
require_once __DIR__ . "/../../server/business/services/TagManagementService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$profileService = new SupervisorProfileService();
$tagService = new TagManagementService();

$profile = $profileService->getDigitalBusinessCard($_SESSION["userID"]);
$selectedTagIDs = $tagService->getSupervisorTagIDs($_SESSION["userID"]);
$allTags = $tagService->getAllTags();
$selectedTags = [];

foreach ($allTags as $tag) {

    if (in_array((int) $tag["tagID"], $selectedTagIDs, true)) {

        $selectedTags[] = $tag["tagName"];
    }
}

$completion = 0;
$defaultBio = "";
$activeTime = "";
$clientBasePath = rtrim(dirname($_SERVER["SCRIPT_NAME"], 2), "/\\");
$shareProfileUrl =
    (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        ? "https://"
        : "http://"
    )
    . ($_SERVER["HTTP_HOST"] ?? "localhost")
    . $clientBasePath
    . "/student/studentSupervisorProfile.php?supervisorID="
    . rawurlencode($_SESSION["userID"]);

if ($profile) {

    $defaultBio =
        $profile["supervisorBio"]
        ??
        (
            "Specializing in "
            . $profile["programme"]
            . ", I guide students through applied research and final year project development at TAR UMT."
        );

    $activeTime =
        $profile["activeTime"]
        ?? "Consultation by appointment";

    $completedItems = 0;
    $completionItems = [
        !empty($profile["profilePhotoPath"]),
        !empty($profile["fullName"]),
        !empty($profile["universityEmail"]),
        !empty($profile["programme"]),
        !empty($profile["employmentCategory"]),
        !empty($activeTime),
        !empty($profile["supervisorBio"]),
        !empty($selectedTags)
    ];

    foreach ($completionItems as $itemCompleted) {

        if ($itemCompleted) {

            $completedItems++;
        }
    }

    $completion =
        (int) round(
            ($completedItems / count($completionItems)) * 100
        );
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Business Card | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("business-card"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>

            <section class="hero">
                <div>
                    <h1>Digital Business Card</h1>
                    <p>Manage your academic profile and how it appears to students across the university portal.</p>
                </div>
                <div class="hero-stat">
                    <div class="stat-label">Profile Completion</div>
                    <div class="stat-value"><?php echo e($completion); ?>%</div>
                    <div class="progress"><span style="width: <?php echo e($completion); ?>%;"></span></div>
                </div>
            </section>

            <?php if (!$profile): ?>
                <section class="card empty">Supervisor profile was not found.</section>
            <?php else: ?>
                <section class="profile-grid">
                    <form class="card form-card" action="../../server/application/supervisor/updateSupervisorProfile.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <h2 class="form-title">Basic Information</h2>
                        <div class="basic-layout">
                            <div>
                                <div class="photo-box" id="photoPreview">
                                    <?php if (!empty($profile["profilePhotoPath"])): ?>
                                        <img src="<?php echo e($profile["profilePhotoPath"]); ?>" alt="Profile photo">
                                    <?php else: ?>
                                        <?php echo e(supervisorInitials($profile["fullName"])); ?>
                                    <?php endif; ?>
                                    <span>*</span>
                                </div>
                                <div class="photo-upload">
                                    <label>Profile Photo</label>
                                    <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
                                    <div class="photo-upload-control" id="photoUploadControl">
                                        <label class="photo-upload-button" for="profilePhoto">Choose Photo</label>
                                        <span class="photo-upload-name" id="photoUploadName">No photo selected</span>
                                        <input type="file" id="profilePhoto" name="profilePhoto" accept="image/jpeg,image/png">
                                    </div>
                                    <p class="photo-hint">JPG or PNG only. Maximum 2MB.</p>
                                </div>
                            </div>
                            <div class="field-grid">
                                <div>
                                    <label>Full Name</label>
                                    <input class="locked-field" type="text" value="<?php echo e($profile["fullName"]); ?>" readonly tabindex="-1">
                                </div>
                                <div class="two-col">
                                    <div>
                                        <label>Faculty Position</label>
                                        <input class="locked-field" type="text" name="employmentCategory" value="<?php echo e($profile["employmentCategory"]); ?>" readonly tabindex="-1">
                                    </div>
                                    <div>
                                        <label>Programme</label>
                                        <input class="locked-field" type="text" name="programme" value="<?php echo e($profile["programme"]); ?>" readonly tabindex="-1">
                                    </div>
                                </div>
                                <div>
                                    <label>Active Time</label>
                                    <input type="text" name="activeTime" maxlength="100" value="<?php echo e($activeTime); ?>" placeholder="e.g. Monday 2:00 PM - 4:00 PM" required>
                                </div>
                                <div>
                                    <label>Short Biography</label>
                                    <textarea name="supervisorBio" maxlength="500" required><?php echo e($defaultBio); ?></textarea>
                                </div>
                                <div class="two-col">
                                    <div>
                                        <label>Email Address</label>
                                        <input class="locked-field" type="email" value="<?php echo e($profile["universityEmail"]); ?>" readonly tabindex="-1">
                                    </div>
                                    <div>
                                        <label>Introductory Video Link</label>
                                        <input type="text" name="introVideoLink" value="<?php echo e($profile["introVideoLink"]); ?>" placeholder="https://youtube.com/...">
                                    </div>
                                </div>
                                <div>
                                    <label>Expertise Tags</label>
                                    <div class="tag-list">
                                        <?php if (empty($selectedTags)): ?>
                                            <span class="tag-pill">No tags selected</span>
                                        <?php else: ?>
                                            <?php foreach ($selectedTags as $tagName): ?>
                                                <span class="tag-pill"><?php echo e($tagName); ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="actions card-actions-panel">
                            <button class="button save-card-button" type="submit">Save Card Changes</button>
                            <a class="button secondary discard-card-button" href="manageDigitalBusinessCard.php">Discard Changes</a>
                        </div>
                    </form>

                    <aside>
                        <section class="card preview-card">
                            <div class="preview-band"></div>
                            <div class="preview-body">
                                <div class="preview-avatar">
                                    <?php if (!empty($profile["profilePhotoPath"])): ?>
                                        <img src="<?php echo e($profile["profilePhotoPath"]); ?>" alt="Profile photo">
                                    <?php else: ?>
                                        <?php echo e(supervisorInitials($profile["fullName"])); ?>
                                    <?php endif; ?>
                                </div>
                                <h2 class="preview-name"><?php echo e($profile["fullName"]); ?></h2>
                                <div class="preview-role"><?php echo e($profile["employmentCategory"]); ?></div>
                                <div class="preview-text">Programme: <?php echo e($profile["programme"]); ?></div>
                                <div class="preview-text">Active Time: <?php echo e($activeTime); ?></div>
                                <p class="preview-text"><?php echo e($defaultBio); ?></p>
                                <p class="preview-text">Current supervision load: <?php echo e($profile["quotaText"] ?? "0/0 supervisees"); ?>.</p>
                                <p class="preview-text"><?php echo e($profile["universityEmail"]); ?></p>
                                <a class="button secondary share-profile-link" href="<?php echo e($shareProfileUrl); ?>" data-share-url="<?php echo e($shareProfileUrl); ?>" target="_blank" rel="noopener noreferrer">Share Profile Link</a>
                            </div>
                        </section>
                        <section class="insight">
                            <strong>Design Insight</strong><br>
                            This card adapts automatically to university branding. Contact details remain visible only to authorized users according to SSAS security rules.
                        </section>
                    </aside>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
