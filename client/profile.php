<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/AccountService.php";
require_once __DIR__ . "/accountLayout.php";

SessionManager::startSession();
SessionManager::requireLogin();

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$accountService = new AccountService();
$profile = $accountService->getAccountProfile($_SESSION["userID"]);

if (!$profile) {

    header("Location: login.html?status=error&message=Account was not found");
    exit();
}

SessionManager::setProfilePhotoPath(
    $profile["profilePhotoPath"] ?? ""
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo ssasPortalShellStyles(); ?>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f6f8fb; color: #1d2b3a; }
        .page { padding: 0; }
        .breadcrumb { color: #6b7f91; font-size: 13px; margin-bottom: 24px; }
        .title { color: #2a74c9; font-size: 28px; font-weight: 300; margin: 0 0 18px; }
        .tabs { display: flex; border-bottom: 1px solid #c8d8e6; margin-bottom: 0; }
        .tab { border: 1px solid #c8d8e6; border-bottom: 0; padding: 10px 14px; background: #fff; color: #6b7f91; text-decoration: none; font-size: 14px; }
        .tab.active { color: #2a74c9; box-shadow: inset 0 3px 0 #2a74c9; }
        .panel { background: #fff; border: 1px solid #c8d8e6; border-top: 0; padding: 34px; min-height: 420px; }
        .profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 38px; align-items: start; }
        .identity { text-align: center; }
        .photo { width: 170px; height: 170px; border: 6px solid #e4e8ee; margin: 0 auto 16px; display: grid; place-items: center; background: #eaf3ff; color: #0d5be8; font-size: 42px; font-weight: 900; }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .photo-form { margin-top: 14px; display: grid; gap: 10px; }
        .photo-form input[type="file"] { position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px; }
        .photo-picker { display: grid; gap: 8px; border: 1px dashed #b8cfe8; background: #f7fbff; padding: 13px; cursor: pointer; color: #315e8c; transition: border-color .2s, background .2s; }
        .photo-picker:hover { border-color: #0d5be8; background: #eef6ff; }
        .photo-picker strong { display: inline-flex; justify-content: center; align-items: center; min-height: 34px; border-radius: 6px; background: #0d5be8; color: #fff; font-size: 13px; }
        .photo-picker span { color: #526a7f; font-size: 12px; word-break: break-word; }
        .photo-actions { display: flex; gap: 8px; justify-content: center; }
        .save-photo { border: 0; border-radius: 7px; background: #0d5be8; color: #fff; padding: 10px 16px; cursor: pointer; font-weight: 800; box-shadow: 0 6px 14px rgba(13,91,232,.16); }
        .photo-note { color: #6b7f91; font-size: 12px; line-height: 1.4; }
        .name { color: #3c82d7; font-size: 22px; font-weight: 800; margin-bottom: 14px; }
        .id-band { background: #448dca; color: #fff; padding: 12px; font-weight: 800; }
        .info-section h2 { color: #3c82d7; font-weight: 300; border-bottom: 1px solid #d8e4ef; padding-bottom: 9px; margin: 0 0 8px; }
        .info-row { display: grid; grid-template-columns: 190px 1fr; gap: 16px; border-bottom: 1px dotted #c8d8e6; padding: 11px 0; font-size: 14px; }
        .label { color: #54708b; text-align: right; }
        .value { color: #1d2b3a; }
        @media (max-width: 850px) { .profile-grid { grid-template-columns: 1fr; } .label { text-align: left; } .info-row { grid-template-columns: 1fr; gap: 4px; } }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="portal-shell">
        <?php echo ssasPortalSidebar("profile"); ?>
        <main class="portal-main page">
            <?php echo ssasStatusMessage(); ?>
        <div class="breadcrumb">Home &gt; My Account &gt; Profile</div>
        <h1 class="title">Profile <span style="font-size:16px;color:#6b7f91;">&gt; My SSAS Account Info</span></h1>
        <nav class="tabs">
            <a class="tab active" href="profile.php">Profile</a>
            <a class="tab" href="setPassword.php">Set Password</a>
        </nav>
        <section class="panel">
            <div class="profile-grid">
                <aside class="identity">
                    <div class="name"><?php echo ssasEscape($profile["fullName"]); ?></div>
                    <div class="photo" id="profilePhotoPreview">
                        <?php if (!empty($profile["profilePhotoPath"])): ?>
                            <img src="<?php echo ssasEscape($profile["profilePhotoPath"]); ?>" alt="Profile photo">
                        <?php else: ?>
                            <?php echo ssasEscape(ssasInitials($profile["fullName"])); ?>
                        <?php endif; ?>
                    </div>
                    <div class="id-band"><?php echo ssasEscape($profile["userID"]); ?></div>
                    <form class="photo-form" action="../server/application/updateProfilePhotoProcess.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                        <label class="photo-picker" for="profilePhotoInput">
                            <strong>Choose Profile Photo</strong>
                            <span id="profilePhotoName">No file selected</span>
                        </label>
                        <input type="file" id="profilePhotoInput" name="profilePhoto" accept="image/jpeg,image/png" required>
                        <div class="photo-note">JPG or PNG only. Maximum 5MB.</div>
                        <div class="photo-actions">
                            <button class="save-photo" type="submit">Save Photo</button>
                        </div>
                    </form>
                </aside>
                <section class="info-section">
                    <h2>SSAS Account Particulars</h2>
                    <div class="info-row"><div class="label">Full Name</div><div class="value"><?php echo ssasEscape($profile["fullName"]); ?></div></div>
                    <div class="info-row"><div class="label">User ID</div><div class="value"><?php echo ssasEscape($profile["userID"]); ?></div></div>
                    <div class="info-row"><div class="label">University Email</div><div class="value"><?php echo ssasEscape($profile["universityEmail"]); ?></div></div>
                    <div class="info-row"><div class="label">System Role</div><div class="value"><?php echo ssasEscape($profile["systemRole"]); ?></div></div>
                    <div class="info-row"><div class="label">Account Status</div><div class="value"><?php echo $profile["activeStatus"] ? "Active" : "Inactive"; ?></div></div>
                    <div class="info-row"><div class="label">Access Scope</div><div class="value">Role-based SSAS access only</div></div>
                </section>
            </div>
        </section>
        </main>
    </div>
    <script>
        const photoInput = document.getElementById("profilePhotoInput");
        const photoPreview = document.getElementById("profilePhotoPreview");

        if (photoInput) {
            photoInput.addEventListener("change", function() {
                const file = photoInput.files[0];

                if (!file) {
                    document.getElementById("profilePhotoName").textContent = "No file selected";
                    return;
                }

                if (!["image/jpeg", "image/png"].includes(file.type)) {
                    alert("Only JPG or PNG profile photos are allowed.");
                    photoInput.value = "";
                    document.getElementById("profilePhotoName").textContent = "No file selected";
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert("Profile photo cannot exceed 5MB.");
                    photoInput.value = "";
                    document.getElementById("profilePhotoName").textContent = "No file selected";
                    return;
                }

                document.getElementById("profilePhotoName").textContent = file.name;
                photoPreview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Profile photo preview">';
            });
        }
    </script>
</body>
</html>
