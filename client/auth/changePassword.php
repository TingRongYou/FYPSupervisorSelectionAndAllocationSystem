<?php
/*
|--------------------------------------------------------------------------
| Variable Initialization
|--------------------------------------------------------------------------
| Initialize default variables used for messages and token validation.
*/
$message = "";
$status = "";
$validToken = false;

// store user record if token is valid
$user = null;

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
| Include database connection file and establish PDO connection.
*/

require_once "../../server/data/database/database.php";
require_once __DIR__ . "/../shared/accountLayout.php";

$database = new Database();
$pdo = $database->connect();

/*
|--------------------------------------------------------------------------
| Reset Token Retrieval
|--------------------------------------------------------------------------
| Retrieve token from URL parameter (GET) or form submission (POST).
*/
$token = trim($_GET["token"] ?? $_POST["token"] ?? "");

/*
|--------------------------------------------------------------------------
| Token Validation
|--------------------------------------------------------------------------
| Validate reset token and ensure token has not expired.
*/
if ($token !== "") {

    // Prepare SQL query to find matching token
    $stmt = $pdo->prepare("
        SELECT *
        FROM USER
        WHERE resetToken = ?
        LIMIT 1
    ");

    // Execute query using token value
    $stmt->execute([$token]);

    // Fetch user record
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if token exists and is still valid using the same PHP clock
    // used when the token was created.
    if ($user && !empty($user["resetExpires"]) && strtotime($user["resetExpires"]) >= time()) {
        // Mark token as valid
        $validToken = true;
    }
}

/*
|--------------------------------------------------------------------------
| Invalid Token Handling
|--------------------------------------------------------------------------
| Display error message if token is invalid or expired.
*/

if (!$validToken) {

    // Set error status
    $status = "error";

    // Error message
    $message = "This password reset link is invalid or has expired.";

/*
|--------------------------------------------------------------------------
| Password Reset Processing
|--------------------------------------------------------------------------
| Process password reset form submission.
*/
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get new password input
    $newPassword = $_POST["newPassword"] ?? "";

    // Get confirm password input
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Form Validation
    |--------------------------------------------------------------------------
    | Validate password inputs before updating database.
    */
    if ($newPassword === "" || $confirmPassword === "") {

        // Set error status
        $status = "error";

        // Error message
        $message = "Please fill in all fields.";

    // Check whether passwords match
    } elseif ($newPassword !== $confirmPassword) {

        // Set error status
        $status = "error";

        // Error message
        $message = "Passwords do not match.";

    // Validate minimum password length and complexity
    } elseif (
        strlen($newPassword) < 8 || 
        !preg_match('/[a-zA-Z]/', $newPassword) || 
        !preg_match('/[0-9]/', $newPassword) || 
        !preg_match('/[^a-zA-Z0-9]/', $newPassword)
    ) {

        // Set error status
        $status = "error";

        // Error message
        $message = "Password must be at least 8 characters long, and contain at least 1 letter, 1 number, and 1 special character.";
        
    } else {
        /*
        |--------------------------------------------------------------------------
        | Password Update
        |--------------------------------------------------------------------------
        | Securely hash password and update database record.
        */

        // Hash password securely
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Prepare SQL query to update password
        $stmt = $pdo->prepare("
            UPDATE USER
            SET password     = ?,
                resetToken   = NULL,
                resetExpires = NULL
            WHERE universityEmail = ?
        ");

        // Execute update query
        $stmt->execute([$hashedPassword, $user["universityEmail"]]);

        /*
        |--------------------------------------------------------------------------
        | Redirect After Success
        |--------------------------------------------------------------------------
        | Redirect user back to login page after password reset.
        */

        header("Location: login.php?message=" . urlencode("Password changed successfully. Please login.") . "&type=success");

        // Stop script execution
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Change Password", "auth"); 
    ?>
</head>
<body>
    <main class="page">
        <img class="brand-image" src="../assets/img/tarumt_logo_with_name.png" alt="TAR UMT Logo">
        <h1 class="subtitle">Change Password</h1>

        <section class="card">
            <div class="card-body">
                <h2>Create New Password</h2>

                <?php if ($message !== ""): ?>
                    <div class="message show <?php echo ssasEscape($status); ?>">
                        <?php echo ssasEscape($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo ssasEscape($token); ?>">
                        <div class="field">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                            <input type="password" name="newPassword" id="passwordInput" placeholder="New password (min. 8 characters)" required>
                            <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
                                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg id="eyeOffIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="field">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                            <input type="password" name="confirmPassword" id="passwordConfirmInput" placeholder="Confirm new password" required>
                            <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
                                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg id="eyeOffIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="button-row">
                            <button type="submit">Change Password</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <a class="back" href="login.php">Back to Login</a>
        </section>
    </main>
</body>
</html>
