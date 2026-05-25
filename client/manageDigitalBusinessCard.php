<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/SupervisorProfileService.php";
require_once __DIR__ . "/../server/business/TagManagementService.php";
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

$completion = 45;
$defaultBio = "";

if ($profile) {

    $defaultBio =
        $profile["supervisorBio"]
        ??
        (
            "Specializing in "
            . $profile["programme"]
            . ", I guide students through applied research and final year project development at TAR UMT."
        );

    $filled = 0;
    $fields = ["programme", "employmentCategory", "introVideoLink", "supervisorBio"];

    foreach ($fields as $field) {

        if (!empty($profile[$field])) {

            $filled++;
        }
    }

    if (!empty($selectedTags)) {

        $filled++;
    }

    $completion = min(100, 45 + ($filled * 10));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Business Card | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .profile-grid { display: grid; grid-template-columns: 1.25fr .85fr; gap: 24px; align-items: start; }
        .form-card { padding: 26px; }
        .form-title { margin: 0 0 22px; color: #1d2b3a; font-size: 19px; display: flex; gap: 8px; align-items: center; }
        .basic-layout { display: grid; grid-template-columns: 130px 1fr; gap: 24px; }
        .photo-box { width: 116px; height: 116px; border-radius: 8px; border: 2px dashed #aab9ca; background: #e9eef5; display: grid; place-items: center; color: #41556b; font-size: 38px; position: relative; }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; }
        .photo-box span { position: absolute; right: -8px; bottom: -8px; width: 26px; height: 26px; border-radius: 6px; background: #0d5be8; color: #fff; display: grid; place-items: center; font-size: 13px; }
        .photo-upload { margin-top: 12px; }
        .photo-upload input { font-size: 12px; padding: 9px; }
        .photo-hint { margin: 7px 0 0; color: #71859a; font-size: 12px; line-height: 1.4; }
        .field-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .tag-pill { border-radius: 999px; padding: 7px 10px; background: #e9f1ff; color: #0d5be8; font-size: 12px; font-weight: 800; }
        .actions { display: flex; gap: 12px; margin-top: 24px; }
        .preview-card { overflow: hidden; }
        .preview-band { height: 110px; background: #0d5be8; }
        .preview-body { padding: 0 28px 24px; }
        .preview-avatar { width: 84px; height: 84px; border-radius: 12px; border: 4px solid #fff; background: #26384c; color: #fff; display: grid; place-items: center; font-size: 24px; font-weight: 900; margin-top: -42px; overflow: hidden; }
        .preview-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .preview-name { margin: 18px 0 4px; color: #1d2b3a; font-size: 23px; }
        .preview-role { color: #0d5be8; font-weight: 800; margin-bottom: 5px; }
        .preview-text { color: #526a7f; line-height: 1.6; font-size: 14px; }
        .insight { margin-top: 18px; padding: 18px; background: #eef6ff; border: 1px solid #d9e7f3; border-radius: 10px; color: #526a7f; font-size: 13px; line-height: 1.6; }
        .empty { padding: 24px; }
        @media (max-width: 1100px) { .profile-grid, .basic-layout, .two-col { grid-template-columns: 1fr; } }
    </style>
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
                    <form class="card form-card" action="../server/application/updateSupervisorProfile.php" method="POST" enctype="multipart/form-data">
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
                                    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                                    <input type="file" id="profilePhoto" name="profilePhoto" accept="image/jpeg,image/png">
                                    <p class="photo-hint">JPG or PNG only. Maximum 5MB.</p>
                                </div>
                            </div>
                            <div class="field-grid">
                                <div>
                                    <label>Full Name</label>
                                    <input type="text" value="<?php echo e($profile["fullName"]); ?>" readonly>
                                </div>
                                <div class="two-col">
                                    <div>
                                        <label>Employment Category</label>
                                        <select name="employmentCategory" required>
                                            <?php foreach (["Full-Time Lecturer", "Part-Time Lecturer", "Dean", "Deputy Dean", "Academic Director", "Programme Leader"] as $category): ?>
                                                <option value="<?php echo e($category); ?>" <?php echo $profile["employmentCategory"] === $category ? "selected" : ""; ?>>
                                                    <?php echo e($category); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Programme</label>
                                        <input type="text" name="programme" value="<?php echo e($profile["programme"]); ?>" required>
                                    </div>
                                </div>
                                <div>
                                    <label>Short Biography</label>
                                    <textarea name="supervisorBio" maxlength="500" required><?php echo e($defaultBio); ?></textarea>
                                </div>
                                <div class="two-col">
                                    <div>
                                        <label>Email Address</label>
                                        <input type="email" value="<?php echo e($profile["universityEmail"]); ?>" readonly>
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
                        <div class="actions">
                            <button class="button" type="submit">Save Card Changes</button>
                            <a class="button secondary" href="manageDigitalBusinessCard.php">Discard Changes</a>
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
                                <p class="preview-text"><?php echo e($defaultBio); ?></p>
                                <p class="preview-text">Current supervision load: <?php echo e($profile["quotaText"] ?? "0/0 supervisees"); ?>.</p>
                                <p class="preview-text"><?php echo e($profile["universityEmail"]); ?></p>
                                <a class="button secondary" href="#">Share Profile Link</a>
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
    <script>
        const profilePhoto = document.getElementById("profilePhoto");
        const photoPreview = document.getElementById("photoPreview");

        if (profilePhoto) {
            profilePhoto.addEventListener("change", function() {
                const file = profilePhoto.files[0];

                if (!file) {
                    return;
                }

                if (!["image/jpeg", "image/png"].includes(file.type)) {
                    alert("Only JPG or PNG profile photos are allowed.");
                    profilePhoto.value = "";
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert("Profile photo cannot exceed 5MB.");
                    profilePhoto.value = "";
                    return;
                }

                const previewUrl = URL.createObjectURL(file);
                photoPreview.innerHTML = '<img src="' + previewUrl + '" alt="Profile photo preview"><span>*</span>';
            });
        }
    </script>
</body>
</html>
