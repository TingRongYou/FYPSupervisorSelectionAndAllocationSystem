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
        .photo-upload input[type="file"] { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .photo-upload-control { display: grid; grid-template-columns: auto 1fr; align-items: center; gap: 8px; min-height: 42px; border: 1px solid #d8e4ef; background: #f6f8fb; border-radius: 8px; padding: 5px; }
        .photo-upload-button { display: inline-flex; align-items: center; justify-content: center; min-height: 30px; padding: 0 10px; border-radius: 6px; background: #e9edf2; color: #1d2b3a; font-size: 12px; font-weight: 800; cursor: pointer; white-space: nowrap; transition: background .2s, color .2s; text-transform: none; letter-spacing: 0; margin: 0; }
        .photo-upload-name { min-width: 0; color: #526a7f; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .photo-upload-control.ready { border-color: #0d5be8; background: #eef5ff; }
        .photo-upload-control.ready .photo-upload-button { background: #0d5be8; color: #fff; }
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
        .share-profile-link { width: 100%; min-height: 38px; margin-top: 12px; border: 1px solid #dbe6f0; border-radius: 8px; background: #fff; color: #1d2b3a; box-shadow: none; font-size: 13px; }
        .share-profile-link:before { content: "<"; margin-right: 7px; color: #526a7f; font-weight: 900; transform: rotate(35deg); display: inline-block; }
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
                                    <input type="text" value="<?php echo e($profile["fullName"]); ?>" readonly>
                                </div>
                                <div class="two-col">
                                    <div>
                                        <label>Faculty Position</label>
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
                                <div class="preview-text">Active Time: <?php echo e($activeTime); ?></div>
                                <p class="preview-text"><?php echo e($defaultBio); ?></p>
                                <p class="preview-text">Current supervision load: <?php echo e($profile["quotaText"] ?? "0/0 supervisees"); ?>.</p>
                                <p class="preview-text"><?php echo e($profile["universityEmail"]); ?></p>
                                <a class="button secondary share-profile-link" href="#">Share Profile Link</a>
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
        const photoUploadControl = document.getElementById("photoUploadControl");
        const photoUploadName = document.getElementById("photoUploadName");

        if (profilePhoto) {
            profilePhoto.addEventListener("change", function() {
                const file = profilePhoto.files[0];

                if (!file) {
                    return;
                }

                if (!["image/jpeg", "image/png"].includes(file.type)) {
                    alert("Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB.");
                    profilePhoto.value = "";
                    photoUploadControl.classList.remove("ready");
                    photoUploadName.textContent = "No photo selected";
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    alert("Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB.");
                    profilePhoto.value = "";
                    photoUploadControl.classList.remove("ready");
                    photoUploadName.textContent = "No photo selected";
                    return;
                }

                const previewUrl = URL.createObjectURL(file);
                photoPreview.innerHTML = '<img src="' + previewUrl + '" alt="Profile photo preview"><span>*</span>';
                photoUploadControl.classList.add("ready");
                photoUploadName.textContent = file.name;
            });
        }

        const cardForm = document.querySelector(".form-card");
        const discardLink = document.querySelector(".actions .button.secondary");

        if (cardForm) {
            cardForm.addEventListener("submit", function(event) {
                if (!cardForm.checkValidity()) {
                    event.preventDefault();
                    alert("Validation Error - Please complete all required fields before saving your profile.");
                    return;
                }

                if (!confirm("Update your Digital Business Card now?")) {
                    event.preventDefault();
                }
            });
        }

        if (discardLink) {
            discardLink.addEventListener("click", function(event) {
                if (!confirm("Cancel changes and return to the previously saved profile data?")) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>


