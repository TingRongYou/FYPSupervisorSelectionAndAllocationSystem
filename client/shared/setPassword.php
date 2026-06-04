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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password | SSAS</title>
    <style>

        /*
        |--------------------------------------------------------------------------
        | Shared Layout Styles
        |--------------------------------------------------------------------------
        */
        <?php echo ssasAccountStyles(); ?>
        <?php echo ssasPortalShellStyles(); ?>

        /*
        |--------------------------------------------------------------------------
        | Base Reset Styles
        |--------------------------------------------------------------------------
        */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f6f8fb; color: #1d2b3a; }
        .page { padding: 0; }

        /*
        |--------------------------------------------------------------------------
        | Breadcrumb Navigation
        |--------------------------------------------------------------------------
        */
        .breadcrumb { color: #6b7f91; font-size: 13px; margin-bottom: 24px; }

        /*
        |--------------------------------------------------------------------------
        | Page Title
        |--------------------------------------------------------------------------
        */
        .title { color: #2a74c9; font-size: 28px; font-weight: 300; margin: 0 0 18px; }

        /*
        |--------------------------------------------------------------------------
        | Tab Navigation
        |--------------------------------------------------------------------------
        */
        .tabs { display: flex; border-bottom: 1px solid #c8d8e6; margin-bottom: 0; }
        .tab {
            border: 1px solid #c8d8e6; border-bottom: 0;
            padding: 10px 14px; background: #fff;
            color: #6b7f91; text-decoration: none; font-size: 14px;
        }
        .tab.active { color: #2a74c9; box-shadow: inset 0 3px 0 #2a74c9; }

        /*
        |--------------------------------------------------------------------------
        | Main Panel
        |--------------------------------------------------------------------------
        */
        .panel {
            background: #fff; border: 1px solid #c8d8e6; border-top: 0;
            padding: 24px 22px 34px; min-height: 520px;
            box-shadow: 0 8px 22px rgba(11,79,138,.06);
        }

        /*
        |--------------------------------------------------------------------------
        | Note / Requirements Box
        |--------------------------------------------------------------------------
        */
        .note { border: 1px solid #c8d8e6; margin-bottom: 30px; border-radius: 8px; overflow: hidden; }
        .note-title { background: #f7f9fb; color: #2a74c9; padding: 11px 14px; border-bottom: 1px solid #c8d8e6; font-weight: 800; }
        .note-body  { padding: 16px; line-height: 1.75; font-size: 14px; }

        /*
        |--------------------------------------------------------------------------
        | Form Layout
        |--------------------------------------------------------------------------
        */
        .form-area { max-width: 620px; margin: 0 auto; }

        /* Each row: label on left, input on right */
        .field { display: grid; grid-template-columns: 170px 1fr; gap: 12px; align-items: center; margin-bottom: 16px; }
        .field label { text-align: right; color: #1d2b3a; font-size: 14px; }

        /*
        |--------------------------------------------------------------------------
        | Password Input Wrapper
        |--------------------------------------------------------------------------
        */
        .pw-wrap { position: relative; display: flex; align-items: center; }
        .pw-wrap input {
            width: 100%; height: 40px;
            border: 1px solid #c7d2dd; border-radius: 7px;
            padding: 0 38px 0 12px; font-size: 14px; outline: none;
        }
        .pw-wrap input:focus { border-color: #2a74c9; box-shadow: 0 0 0 2px rgba(42,116,201,.12); }

        /*
        |--------------------------------------------------------------------------
        | Eye Toggle Button
        |--------------------------------------------------------------------------
        */
        .eye-btn {
            position: absolute; right: 10px;
            background: none; border: none;
            padding: 0; cursor: pointer;
            color: #8a9caf; display: flex; align-items: center;
            min-width: unset; height: unset;
        }
        .eye-btn:hover { color: #2a74c9; }
        .eye-btn svg { width: 17px; height: 17px; }

        /*
        |--------------------------------------------------------------------------
        | Password Strength Bar
        |--------------------------------------------------------------------------
        */
        .strength-wrap { margin-left: 182px; margin-bottom: 18px; }

        /* The coloured bar */
        .strength-bar {
            height: 6px; border-radius: 999px;
            background: #e4eaf0; overflow: hidden;
            margin-bottom: 6px; width: 260px;
        }
        .strength-bar-fill {
            height: 100%; border-radius: inherit;
            width: 0%; transition: width .3s, background .3s;
        }

        /* Text label beneath the bar */
        .strength-label { font-size: 12px; font-weight: 800; color: #8a9caf; }

        /*
        |--------------------------------------------------------------------------
        | Password Requirement Checklist
        |--------------------------------------------------------------------------
        */
        .req-list { margin: 10px 0 0; padding: 0; list-style: none; display: grid; gap: 4px; }
        .req-list li {
            display: flex; align-items: center; gap: 7px;
            font-size: 12px; color: #8a9caf;
            transition: color .2s;
        }
        /* Checkmark circle */
        .req-list li::before {
            content: "";
            width: 14px; height: 14px; border-radius: 50%;
            border: 2px solid #c7d2dd;
            flex-shrink: 0;
            transition: border-color .2s, background .2s;
        }
        /* Met requirement turns green */
        .req-list li.met { color: #177345; }
        .req-list li.met::before { border-color: #22c55e; background: #22c55e; }

        /*
        |--------------------------------------------------------------------------
        | Form Action Buttons
        |--------------------------------------------------------------------------
        */
        .actions { margin-top: 22px; background: #f4f7fa; padding: 20px; text-align: center; border-radius: 8px; }
        .button { border: 0; border-radius: 7px; min-width: 88px; height: 42px; margin: 0 6px; color: #fff; cursor: pointer; font-weight: 800; font-size: 14px; }
        .save  { background: #0d5be8; }
        .save:hover  { background: #0947c2; }
        .reset { background: #a9b8c3; }
        .reset:hover { background: #8a9caf; }

        /*
        |--------------------------------------------------------------------------
        | Status Message Styles
        |--------------------------------------------------------------------------
        */
        .message { border-radius: 4px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; font-size: 13px; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error   { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }

        /*
        |--------------------------------------------------------------------------
        | Responsive Design
        |--------------------------------------------------------------------------
        */
        @media (max-width: 720px) {
            .field { grid-template-columns: 1fr; }
            .field label { text-align: left; }
            .strength-wrap { margin-left: 0; }
            .strength-bar { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Shared topbar -->
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="portal-shell">
        <?php echo ssasPortalSidebar("set-password"); ?>

        <main class="portal-main page">

            <!-- Breadcrumb navigation -->
            <div class="breadcrumb">Home &gt; My Account &gt; Set Password</div>

            <!-- Page heading -->
            <h1 class="title">
                Set Password
                <span style="font-size:16px; color:#6b7f91;">&gt; Change Password</span>
            </h1>

            <!-- Tab bar: Profile | Set Password -->
            <nav class="tabs">
                <a class="tab"        href="profile.php">Profile</a>
                <a class="tab active" href="setPassword.php">Set Password</a>
            </nav>

            <section class="panel">

                <!-- Flash message from redirect (success / error) -->
                <?php echo ssasStatusMessage(); ?>

                <!-- Password requirements notice -->
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

                <!-- Change password form -->
                <form
                    action="../../server/application/auth/updatePasswordProcess.php"
                    method="POST"
                    class="form-area"
                    id="pwForm">

                    <!-- CSRF token (hidden) — prevents cross-site request forgery -->
                    <input type="hidden" name="csrf_token"
                        value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">

                    <!-- Current password field -->
                    <div class="field">
                        <label for="currentPassword">Current Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="currentPassword"
                                name="currentPassword"
                                placeholder="Current password" required>
                            <!-- Eye button toggles visibility -->
                            <button type="button" class="eye-btn"
                                    data-target="currentPassword"
                                    aria-label="Show password">
                                <?php echo eyeIcon(); ?>
                            </button>
                        </div>
                    </div>

                    <!-- New password field — strength meter reads from this -->
                    <div class="field">
                        <label for="newPassword">New Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="newPassword"
                                name="newPassword"
                                placeholder="New password" required>
                            <button type="button" class="eye-btn"
                                    data-target="newPassword"
                                    aria-label="Show password">
                                <?php echo eyeIcon(); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm password field -->
                    <div class="field">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="confirmPassword"
                                name="confirmPassword"
                                placeholder="Confirm password" required>
                            <button type="button" class="eye-btn"
                                    data-target="confirmPassword"
                                    aria-label="Show password">
                                <?php echo eyeIcon(); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Password strength section (updates live as user types) -->
                    <div class="strength-wrap">

                        <!-- Coloured progress bar -->
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthFill"></div>
                        </div>

                        <!-- Text label: Weak / Fair / Good / Strong -->
                        <div class="strength-label" id="strengthLabel">Password Strength</div>

                        <!-- Individual requirement checklist -->
                        <ul class="req-list" id="reqList">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-alpha">At least 1 letter (a–z / A–Z)</li>
                            <li id="req-num">At least 1 number (0–9)</li>
                            <li id="req-special">At least 1 special character (!@#$…)</li>
                            <li id="req-match">Passwords match</li>
                        </ul>
                    </div>

                    <!-- Form action buttons -->
                    <div class="actions">
                        <button class="button save"  type="submit">Save</button>
                        <button class="button reset" type="reset"
                                id="resetBtn">Reset</button>
                    </div>

                </form>
            </section>
        </main>
    </div>

    <script>

        /*
        |--------------------------------------------------------------------------
        | Password Visibility Toggle
        |--------------------------------------------------------------------------
        | Allows users to show or hide password fields.
        */
        document.querySelectorAll(".eye-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const input = document.getElementById(btn.dataset.target);
                const isHidden = input.type === "password";

                // Toggle input type
                input.type = isHidden ? "text" : "password";

                // Swap SVG icons inside the button
                const icons = btn.querySelectorAll("svg");
                icons.forEach(function (svg) {
                    svg.style.display = svg.style.display === "none" ? "block" : "none";
                });

                btn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Password Strength Meter
        |--------------------------------------------------------------------------
        | Evaluates password strength in real time.
        */
        const newPwInput     = document.getElementById("newPassword");
        const confirmPwInput = document.getElementById("confirmPassword");
        const strengthFill   = document.getElementById("strengthFill");
        const strengthLabel  = document.getElementById("strengthLabel");

        // Requirement list items
        const reqLength  = document.getElementById("req-length");
        const reqAlpha   = document.getElementById("req-alpha");
        const reqNum     = document.getElementById("req-num");
        const reqSpecial = document.getElementById("req-special");
        const reqMatch   = document.getElementById("req-match");

        // Strength levels: [label, bar colour, bar width %]
        const levels = [
            ["Too short",  "#e33434", "10%"],   // score 0
            ["Weak",       "#e33434", "25%"],   // score 1
            ["Fair",       "#f59e0b", "50%"],   // score 2
            ["Good",       "#3b82f6", "75%"],   // score 3
            ["Strong",     "#22c55e", "100%"],  // score 4
        ];

        function evaluatePassword() {
            const pw      = newPwInput.value;
            const confirm = confirmPwInput.value;

            // Individual requirement checks
            const hasLength  = pw.length >= 8;
            const hasAlpha   = /[a-zA-Z]/.test(pw);
            const hasNum     = /[0-9]/.test(pw);
            const hasSpecial = /[^a-zA-Z0-9]/.test(pw);
            const doesMatch  = pw !== "" && pw === confirm;

            // Update requirement checkmarks
            reqLength .classList.toggle("met", hasLength);
            reqAlpha  .classList.toggle("met", hasAlpha);
            reqNum    .classList.toggle("met", hasNum);
            reqSpecial.classList.toggle("met", hasSpecial);
            reqMatch  .classList.toggle("met", doesMatch);

            // Score = number of core requirements met (0–4)
            const score = [hasLength, hasAlpha, hasNum, hasSpecial]
                .filter(Boolean).length;

            // If nothing typed yet, show neutral state
            if (pw.length === 0) {
                strengthFill.style.width      = "0%";
                strengthFill.style.background = "#e4eaf0";
                strengthLabel.textContent     = "Password Strength";
                strengthLabel.style.color     = "#8a9caf";
                return;
            }

            // Apply colour + width + label from levels table
            const [label, colour, width] = levels[score];
            strengthFill.style.width      = width;
            strengthFill.style.background = colour;
            strengthLabel.textContent     = label;
            strengthLabel.style.color     = colour;
        }

        // Run on every keystroke in either password field
        newPwInput    .addEventListener("input", evaluatePassword);
        confirmPwInput.addEventListener("input", evaluatePassword);

        /*
        |--------------------------------------------------------------------------
        | Form Validation
        |--------------------------------------------------------------------------
        | Prevent form submission if requirements fail.
        */
        document.getElementById("pwForm").addEventListener("submit", function (e) {
            const pw      = newPwInput.value;
            const confirm = confirmPwInput.value;

            const errors = [];

            if (pw.length < 8)               errors.push("Password must be at least 8 characters.");
            if (!/[a-zA-Z]/.test(pw))        errors.push("Password must contain at least 1 letter.");
            if (!/[0-9]/.test(pw))           errors.push("Password must contain at least 1 number.");
            if (!/[^a-zA-Z0-9]/.test(pw))   errors.push("Password must contain at least 1 special character.");
            if (pw !== confirm)              errors.push("Passwords do not match.");

            if (errors.length > 0) {
                e.preventDefault();     // Stop form submission

                // Show first error as a simple alert
                alert(errors.join("\n"));
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Reset Button
        |--------------------------------------------------------------------------
        | Reset password strength meter when form resets.
        */
        document.getElementById("resetBtn").addEventListener("click", function () {
            // Brief timeout lets the browser clear inputs first
            setTimeout(evaluatePassword, 10);
        });
    </script>
</body>
</html>

<?php
/*
|--------------------------------------------------------------------------
| Helper Function: eyeIcon()
|--------------------------------------------------------------------------
| Returns SVG eye icons for password visibility toggle.
*/

function eyeIcon(): string {
    return '
        <!-- Eye icon — shown when password is hidden -->
        <svg style="display:block;" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <!-- Eye-off icon — shown when password is visible -->
        <svg style="display:none;" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8
                    a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8
                    a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        </svg>
    ';
}
?>
