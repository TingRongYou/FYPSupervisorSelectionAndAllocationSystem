<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();
SessionManager::requireLogin();

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password | SSAS</title>
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
        .panel { background: #fff; border: 1px solid #c8d8e6; border-top: 0; padding: 24px 22px 34px; min-height: 520px; box-shadow: 0 8px 22px rgba(11,79,138,.06); }
        .note { border: 1px solid #c8d8e6; margin-bottom: 30px; border-radius: 8px; overflow: hidden; }
        .note-title { background: #f7f9fb; color: #2a74c9; padding: 11px 14px; border-bottom: 1px solid #c8d8e6; font-weight: 800; }
        .note-body { padding: 16px; line-height: 1.75; font-size: 14px; }
        .form-area { max-width: 620px; margin: 0 auto; }
        .field { display: grid; grid-template-columns: 170px 1fr; gap: 12px; align-items: center; margin-bottom: 16px; }
        .field label { text-align: right; color: #1d2b3a; }
        .field input { height: 40px; border: 1px solid #c7d2dd; border-radius: 7px; padding: 0 12px; font-size: 14px; }
        .strength { margin-left: 182px; width: 230px; border-radius: 6px; background: #ffc400; color: #1d2b3a; padding: 8px; text-align: center; font-size: 13px; font-weight: 800; }
        .actions { margin-top: 22px; background: #f4f7fa; padding: 20px; text-align: center; border-radius: 8px; }
        .button { border: 0; border-radius: 7px; min-width: 88px; height: 42px; margin: 0 6px; color: #fff; cursor: pointer; font-weight: 800; }
        .save { background: #0d5be8; }
        .reset { background: #a9b8c3; }
        .message { border-radius: 4px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        @media (max-width: 720px) { .field { grid-template-columns: 1fr; } .field label { text-align: left; } .strength { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="portal-shell">
        <?php echo ssasPortalSidebar("set-password"); ?>
        <main class="portal-main page">
            <div class="breadcrumb">Home &gt; My Account &gt; Set Password</div>
            <h1 class="title">Set Password <span style="font-size:16px;color:#6b7f91;">&gt; Change Password</span></h1>
            <nav class="tabs">
                <a class="tab" href="profile.php">Profile</a>
                <a class="tab active" href="setPassword.php">Set Password</a>
            </nav>
            <section class="panel">
                <?php echo ssasStatusMessage(); ?>
            <section class="note">
                <div class="note-title">Note</div>
                <div class="note-body">
                    <p>1) Passwords must contain:</p>
                    <ul>
                        <li>a minimum of 1 alphabet and</li>
                        <li>a minimum of 1 numeric character and</li>
                        <li>a minimum of 1 special character and</li>
                        <li>at least 8 characters in length.</li>
                    </ul>
                    <p>2) Change your password whenever it is deemed necessary or required.</p>
                    <p>3) You are responsible for keeping your password safe.</p>
                    <p>4) Do not share your password with anyone.</p>
                </div>
            </section>

            <form action="../../server/application/auth/updatePasswordProcess.php" method="POST" class="form-area">
                <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                <div class="field">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" placeholder="Current Password" required>
                </div>
                <div class="field">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" placeholder="New Password" required>
                </div>
                <div class="field">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                </div>
                <div class="strength">Password Strength Level</div>
                <div class="actions">
                    <button class="button save" type="submit">Save</button>
                    <button class="button reset" type="reset">Reset</button>
                </div>
            </form>
            </section>
        </main>
    </div>
</body>
</html>


