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

if (!function_exists("e")) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

$database = new Database();
$pdo = $database->connect();

/*
|--------------------------------------------------------------------------
| Reset Token Retrieval
|--------------------------------------------------------------------------
| Retrieve token from URL parameter (GET) or form submission (POST).
*/
$token =
    trim(
        $_GET["token"]
        ?? $_POST["token"]
        ?? ""
    );

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
    if (
        $user
        && !empty($user["resetExpires"])
        && strtotime($user["resetExpires"]) >= time()
    ) {

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
    $message =
        "This password reset link is invalid or has expired.";

/*
|--------------------------------------------------------------------------
| Password Reset Processing
|--------------------------------------------------------------------------
| Process password reset form submission.
*/
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get new password input
    $newPassword =
        $_POST["newPassword"] ?? "";

    // Get confirm password input
    $confirmPassword =
        $_POST["confirmPassword"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Form Validation
    |--------------------------------------------------------------------------
    | Validate password inputs before updating database.
    */
    if (
        $newPassword === ""
        || $confirmPassword === ""
    ) {

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

    // Validate minimum password length
    } elseif (strlen($newPassword) < 8) {

        // Set error status
        $status = "error";

        // Error message
        $message =
            "Password must be at least 8 characters.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Password Update
        |--------------------------------------------------------------------------
        | Securely hash password and update database record.
        */

        // Hash password securely
        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        // Prepare SQL query to update password
        $stmt = $pdo->prepare("
            UPDATE USER
            SET password     = ?,
                resetToken   = NULL,
                resetExpires = NULL
            WHERE universityEmail = ?
        ");

        // Execute update query
        $stmt->execute([
            $hashedPassword,
            $user["universityEmail"]
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect After Success
        |--------------------------------------------------------------------------
        | Redirect user back to login page after password reset.
        */

        header(
            "Location: login.html?message="
            . urlencode(
                "Password changed successfully. Please login."
            )
            . "&type=success"
        );

        // Stop script execution
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - SSAS</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <main class="page">
        <img class="brand-image" src="../assets/img/tarumt_logo_with_name.png" alt="TAR UMT Logo">
        <h1 class="subtitle">Change Password</h1>

        <section class="card">
            <div class="card-body">
                <h2>Create New Password</h2>

                <?php if ($message !== ""): ?>
                    <div class="message <?php echo e($status); ?>">
                        <?php echo e($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo e($token); ?>">
                        <input type="password" name="newPassword" placeholder="New password (min. 8 characters)" required>
                        <input type="password" name="confirmPassword" placeholder="Confirm new password" required>
                        <div class="button-row">
                            <button type="submit">Change Password</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php if (!$validToken): ?>
                <a class="back" href="login.html">Back to Login</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
