<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
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

function eyeIcon(): string {
    return '
        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <svg id="eyeOffIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        </svg>
    ';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/shared.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/shared.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="portal-shell">
        <?php echo ssasPortalSidebar("set-password"); ?>
        <main class="portal-main page">
            <div class="breadcrumb">Home &gt; My Account &gt; Set Password</div>
            <h1 class="title">Set Password <span style="font-size:16px; color:#6b7f91;">&gt; Change Password</span></h1>

            <nav class="tabs">
                <a class="tab" href="profile.php">Profile</a>
                <a class="tab active" href="setPassword.php">Set Password</a>
            </nav>

            <section class="panel">
                <?php echo ssasStatusMessage(); ?>
                <section class="note">
                    <div class="note-title">Note</div>
                    <div class="note-body">
                        <p>1) Passwords must contain a minimum of 1 alphabet, 1 numeric, 1 special character, and be at least 8 characters long.</p>
                        <p>2) Change your password whenever deemed necessary.</p>
                    </div>
                </section>

                <form action="../../server/application/auth/updatePasswordProcess.php" method="POST" class="form-area" id="pwForm">
                    <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                    
                    <div class="field">
                        <label for="currentPassword">Current Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="currentPassword" name="currentPassword" placeholder="Current password" required>
                            <button type="button" class="eye-btn" data-target="currentPassword" aria-label="Show password">
                                <?php echo eyeIcon(); ?>
                            </button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="newPassword">New Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="newPassword" name="newPassword" placeholder="New password" required>
                            <button type="button" class="eye-btn" data-target="newPassword" aria-label="Show password">
                                <?php echo eyeIcon(); ?>
                            </button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                            <button type="button" class="eye-btn" data-target="confirmPassword" aria-label="Show password">
                                <?php echo eyeIcon(); ?>
                            </button>
                        </div>
                    </div>

                    <div class="strength-wrap">
                        <div class="strength-bar"><div class="strength-bar-fill" id="strengthFill"></div></div>
                        <div class="strength-label" id="strengthLabel">Password Strength</div>
                        <ul class="req-list">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-alpha">At least 1 letter</li>
                            <li id="req-num">At least 1 number</li>
                            <li id="req-special">At least 1 special character</li>
                            <li id="req-match">Passwords match</li>
                        </ul>
                    </div>

                    <div class="actions">
                        <button class="button save" type="submit">Save</button>
                        <button class="button reset" type="reset" id="resetBtn">Reset</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
