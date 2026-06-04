<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/StudentProfileFacade.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$profileFacade = new StudentProfileFacade();
$payload = $profileFacade->getProfilePayload($_SESSION["userID"]);

if (!$payload) {

    header("Location: ../auth/login.html?status=error&message=Student profile was not found");
    exit();
}

$profile = $payload["profile"];
$allTags = $payload["allTags"];
$selectedTagIDs = $payload["selectedTagIDs"];

SessionManager::setProfilePhotoPath(
    $profile["profilePhotoPath"] ?? ""
);

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function initials($name) {

    $parts = preg_split("/\s+/", trim((string) $name));
    $first = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "", 0, 1));

    return $first . $second;
}

function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class = $_GET["status"] === "success" ? "success" : "error";

    return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 30px 38px 56px; min-width: 0; }
        .profile-shell { max-width: 1280px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 22px; }
        .eyebrow { margin: 0 0 7px; color: #0b66d8; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        h1 { margin: 0; color: #172033; font-size: 30px; line-height: 1.1; }
        .subtitle { margin: 8px 0 0; color: #5d7085; font-size: 15px; line-height: 1.5; }
        .header-actions { display: flex; gap: 10px; }
        .button { min-height: 40px; border-radius: 7px; border: 0; background: #003f8f; color: #fff; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 900; font-size: 14px; cursor: pointer; }
        .button.secondary { background: #fff; color: #526a7f; border: 1px solid #d9e7f3; }
        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        .profile-grid { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 22px; align-items: start; }
        .side-panel, .content-panel { background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; box-shadow: 0 8px 22px rgba(11,79,138,.07); }
        .side-panel { padding: 22px; }
        .content-panel { padding: 24px; }
        .avatar-wrap { text-align: center; padding-bottom: 18px; border-bottom: 1px solid #edf2f7; margin-bottom: 18px; }
        .avatar { width: 118px; height: 118px; border-radius: 12px; background: #eaf3ff; color: #003f8f; display: grid; place-items: center; margin: 0 auto 14px; font-size: 30px; font-weight: 900; overflow: hidden; border: 1px solid #d9e7f3; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-upload { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; border-radius: 7px; background: #eaf3ff; color: #003f8f; padding: 0 14px; font-weight: 900; cursor: pointer; font-size: 13px; }
        .avatar-upload input { position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px; }
        .file-name { display: block; color: #6b7f91; font-size: 13px; margin-top: 8px; word-break: break-word; }
        .identity-name { margin: 0 0 4px; color: #172033; font-size: 18px; font-weight: 900; }
        .identity-id { margin: 0; color: #6b7f91; font-size: 14px; }
        .readonly-block { margin-top: 14px; }
        .readonly-title { color: #9aacc0; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; font-weight: 900; margin: 22px 0 10px; }
        .readonly-field { background: #f6f8fb; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .readonly-field span { display: block; color: #8a9caf; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .7px; margin-bottom: 4px; }
        .readonly-field strong { color: #172033; font-size: 14px; line-height: 1.35; word-break: break-word; }
        .form-section { border-bottom: 1px solid #edf2f7; padding-bottom: 24px; margin-bottom: 24px; }
        .form-section:last-child { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
        .section-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .section-title h2 { margin: 0; color: #172033; font-size: 17px; font-weight: 900; }
        .counter { color: #0d5be8; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        label { display: block; color: #7c8da0; text-transform: uppercase; letter-spacing: .8px; font-size: 12px; font-weight: 900; margin-bottom: 8px; }
        textarea, input[type="text"], input[type="url"] { width: 100%; border: 1px solid #dbe6f0; border-radius: 8px; background: #f8fafc; color: #172033; font-size: 14px; padding: 12px 13px; outline: none; }
        textarea { min-height: 118px; resize: vertical; line-height: 1.5; }
        textarea:focus, input:focus { border-color: #0d5be8; background: #fff; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field-note { color: #8a9caf; font-size: 12px; margin: 7px 0 0; }
        .tag-box { border: 1px solid #dbe6f0; border-radius: 12px; padding: 18px; background: #fbfdff; }
        .selected-tags { display: flex; flex-wrap: wrap; gap: 8px; min-height: 34px; margin-bottom: 18px; }
        .selected-tag { display: inline-flex; align-items: center; min-height: 28px; padding: 0 10px; border-radius: 999px; background: #eaf3ff; color: #0d5be8; font-size: 12px; font-weight: 900; }
        .tag-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .tag-option { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 42px; border: 1px solid #dbe6f0; border-radius: 8px; background: #fff; padding: 0 12px; color: #172033; font-size: 13px; font-weight: 800; cursor: pointer; }
        .tag-option input { width: 16px; height: 16px; accent-color: #0d5be8; }
        .tag-option.selected { border-color: #0d5be8; background: #f0f7ff; color: #003f8f; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
        @media (max-width: 1080px) {
            .profile-grid, .two-col { grid-template-columns: 1fr; }
        }
        @media (max-width: 720px) {
            .main { padding: 22px 16px 46px; }
            .page-header { display: block; }
            .header-actions { margin-top: 14px; }
            .tag-grid { grid-template-columns: 1fr; }
            .form-actions { display: grid; }
        }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("profile"); ?>
        <main class="main">
            <div class="profile-shell">
                <?php echo statusMessage(); ?>
                <section class="page-header">
                    <div>
                        <p class="eyebrow">Profile Management</p>
                        <h1>Student Profile</h1>
                        <p class="subtitle">Manage your personal information and academic recommendation signals.</p>
                    </div>
                    <div class="header-actions">
                        <button class="button secondary" type="reset" form="studentProfileForm">Cancel</button>
                        <button class="button" type="submit" form="studentProfileForm">Save Changes</button>
                    </div>
                </section>

                <form id="studentProfileForm" class="profile-grid" action="../../server/application/student/updateStudentProfile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                    <input type="hidden" name="MAX_FILE_SIZE" value="524288">

                    <aside class="side-panel">
                        <section class="avatar-wrap">
                            <div class="avatar" id="avatarPreview">
                                <?php if (!empty($profile["profilePhotoPath"])): ?>
                                    <img src="<?php echo e($profile["profilePhotoPath"]); ?>" alt="Profile photo">
                                <?php else: ?>
                                    <?php echo e(initials($profile["fullName"])); ?>
                                <?php endif; ?>
                            </div>
                            <p class="identity-name"><?php echo e($profile["fullName"]); ?></p>
                            <p class="identity-id"><?php echo e($profile["studentID"]); ?></p>
                            <label class="avatar-upload" for="avatarFile">
                                Choose Avatar
                                <input id="avatarFile" name="avatarFile" type="file" accept="image/jpeg,image/png">
                            </label>
                            <span class="file-name" id="avatarFileName">JPG or PNG, max 0.5MB.</span>
                        </section>

                        <section class="readonly-block">
                            <p class="readonly-title">Academic Record</p>
                            <div class="readonly-field"><span>Programme</span><strong><?php echo e($profile["programme"]); ?></strong></div>
                            <div class="readonly-field"><span>Intake Batch</span><strong><?php echo e($profile["intakeBatch"]); ?></strong></div>
                            <div class="readonly-field"><span>Current Semester</span><strong><?php echo e($profile["currentSem"]); ?></strong></div>
                            <div class="readonly-field"><span>Academic Status</span><strong><?php echo e($profile["academicStatus"]); ?></strong></div>
                            <div class="readonly-field"><span>CGPA</span><strong><?php echo e(number_format((float) $profile["cgpa"], 4)); ?></strong></div>
                            <div class="readonly-field"><span>Email</span><strong><?php echo e($profile["universityEmail"]); ?></strong></div>
                        </section>
                    </aside>

                    <section class="content-panel">
                        <section class="form-section">
                            <div class="section-title">
                                <h2>Personal Details</h2>
                                <span class="counter" id="bioCounter">0 / 500</span>
                            </div>
                            <label for="personalBio">Personal Bio</label>
                            <textarea id="personalBio" name="personalBio" maxlength="500" placeholder="Summarise your background, research interest, and project direction."><?php echo e($profile["personalBio"]); ?></textarea>
                            <p class="field-note">Visible to supervisors reviewing your profile.</p>

                            <div class="two-col" style="margin-top: 16px;">
                                <div>
                                    <label for="contactNumber">Mobile Number</label>
                                    <input id="contactNumber" name="contactNumber" type="text" maxlength="20" value="<?php echo e($profile["contactNumber"]); ?>" placeholder="e.g., 0123456789">
                                </div>
                                <div>
                                    <label>Eligibility</label>
                                    <div class="readonly-field" style="margin: 0;"><strong><?php echo $profile["eligibilityStatus"] ? "Eligible for FYP" : "Not Eligible"; ?></strong></div>
                                </div>
                            </div>
                        </section>

                        <section class="form-section">
                            <div class="section-title">
                                <h2>Selected Interests</h2>
                                <span class="counter" id="tagCounter"><?php echo count($selectedTagIDs); ?> / 5 active</span>
                            </div>
                            <div class="tag-box">
                                <div class="selected-tags" id="selectedTags"></div>
                                <div class="tag-grid">
                                    <?php foreach ($allTags as $tag): ?>
                                        <?php $checked = in_array((int) $tag["tagID"], $selectedTagIDs, true); ?>
                                        <label class="tag-option <?php echo $checked ? "selected" : ""; ?>">
                                            <span><?php echo e($tag["tagName"]); ?></span>
                                            <input type="checkbox" name="interestTags[]" value="<?php echo e($tag["tagID"]); ?>" data-name="<?php echo e($tag["tagName"]); ?>" <?php echo $checked ? "checked" : ""; ?>>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="field-note">Select one to five interests. These tags feed the recommendation engine.</p>
                        </section>

                        <section class="form-section">
                            <div class="section-title">
                                <h2>Portfolio Links</h2>
                            </div>
                            <div class="two-col">
                                <div>
                                    <label for="linkedInURL">LinkedIn URL</label>
                                    <input id="linkedInURL" name="linkedInURL" type="url" value="<?php echo e($profile["linkedInURL"]); ?>" placeholder="https://linkedin.com/in/username">
                                </div>
                                <div>
                                    <label for="githubURL">GitHub URL</label>
                                    <input id="githubURL" name="githubURL" type="url" value="<?php echo e($profile["githubURL"]); ?>" placeholder="https://github.com/username">
                                </div>
                            </div>
                            <div style="margin-top: 16px;">
                                <label for="portfolioURL">Portfolio URL</label>
                                <input id="portfolioURL" name="portfolioURL" type="url" value="<?php echo e($profile["portfolioURL"]); ?>" placeholder="https://yourportfolio.com">
                            </div>
                        </section>

                        <div class="form-actions">
                            <button class="button secondary" type="reset">Cancel</button>
                            <button class="button" type="submit">Save Changes</button>
                        </div>
                    </section>
                </form>
            </div>
        </main>
    </div>

    <script>
        const form = document.getElementById("studentProfileForm");
        const avatarFile = document.getElementById("avatarFile");
        const avatarPreview = document.getElementById("avatarPreview");
        const avatarFileName = document.getElementById("avatarFileName");
        const bio = document.getElementById("personalBio");
        const bioCounter = document.getElementById("bioCounter");
        const tagCounter = document.getElementById("tagCounter");
        const selectedTags = document.getElementById("selectedTags");
        const tagInputs = Array.from(document.querySelectorAll('input[name="interestTags[]"]'));
        const maxAvatarBytes = 512 * 1024;

        function updateBioCounter() {
            bioCounter.textContent = bio.value.length + " / 500";
        }

        function updateTags() {
            const selected = tagInputs.filter(input => input.checked);

            tagCounter.textContent = selected.length + " / 5 active";
            selectedTags.innerHTML = "";

            selected.forEach(function(input) {
                const tag = document.createElement("span");
                tag.className = "selected-tag";
                tag.textContent = input.dataset.name;
                selectedTags.appendChild(tag);
            });

            if (selected.length === 0) {
                const empty = document.createElement("span");
                empty.className = "selected-tag";
                empty.textContent = "No interests selected";
                selectedTags.appendChild(empty);
            }

            tagInputs.forEach(function(input) {
                input.closest(".tag-option").classList.toggle("selected", input.checked);
            });
        }

        tagInputs.forEach(function(input) {
            input.addEventListener("change", function() {
                const selected = tagInputs.filter(item => item.checked);

                if (selected.length > 5) {
                    input.checked = false;
                    alert("Err Max Tags - You can only select a maximum of 5 research interests. Please remove one to add another.");
                }

                updateTags();
            });
        });

        if (avatarFile) {
            avatarFile.addEventListener("change", function() {
                const file = avatarFile.files[0];

                if (!file) {
                    avatarFileName.textContent = "JPG or PNG, max 0.5MB.";
                    return;
                }

                if (!["image/jpeg", "image/png"].includes(file.type) || file.size > maxAvatarBytes) {
                    alert("Err Invalid File - Upload failed. Please ensure your profile picture is in JPG or PNG format and does not exceed 0.5MB.");
                    avatarFile.value = "";
                    avatarFileName.textContent = "JPG or PNG, max 0.5MB.";
                    return;
                }

                avatarFileName.textContent = file.name;
                avatarPreview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Avatar preview">';
            });
        }

        if (form) {
            form.addEventListener("submit", function(event) {
                const selected = tagInputs.filter(input => input.checked);

                if (bio.value.length > 500) {
                    event.preventDefault();
                    alert("Validation Error - Personal Bio cannot exceed 500 characters.");
                    return;
                }

                if (selected.length === 0 || selected.length > 5) {
                    event.preventDefault();
                    alert("Err Max Tags - You can only select a maximum of 5 research interests. Please remove one to add another.");
                    return;
                }
            });

            form.addEventListener("reset", function() {
                setTimeout(function() {
                    updateBioCounter();
                    updateTags();
                    avatarFileName.textContent = "JPG or PNG, max 0.5MB.";
                }, 0);
            });
        }

        updateBioCounter();
        updateTags();
        bio.addEventListener("input", updateBioCounter);
    </script>
</body>
</html>
