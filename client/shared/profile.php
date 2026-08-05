<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/AccountService.php";
require_once __DIR__ . "/../shared/accountLayout.php";

/*
|--------------------------------------------------------------------------
| Session Authentication
|--------------------------------------------------------------------------
| Start session and ensure only logged-in users can access.
*/
SessionManager::startSession();
SessionManager::requireLogin();

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
| Generate CSRF token for secure form submission.
*/

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Account Profile Retrieval
|--------------------------------------------------------------------------
| Fetch logged-in user profile information from database.
*/

$accountService = new AccountService();
$profile = $accountService->getAccountProfile($_SESSION["userID"]);

/*
|--------------------------------------------------------------------------
| Profile Validation
|--------------------------------------------------------------------------
| Redirect user if account profile cannot be found.
*/

if (!$profile) {
    header("Location: ../auth/login.html?status=error&message=Account was not found");
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
    <link rel="stylesheet" href="../assets/css/shared.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/shared.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/shared.js" defer></script>
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
                        <form class="photo-form" action="../../server/application/auth/updateProfilePhotoProcess.php" method="POST" enctype="multipart/form-data">
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
                        <?php if (!empty($profile["profilePhotoPath"])): ?>
                            <form class="photo-remove-form" action="../../server/application/auth/removeProfilePhotoProcess.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                                <button class="remove-photo" type="submit">Remove Photo</button>
                            </form>
                        <?php endif; ?>
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
</body>
</html>
