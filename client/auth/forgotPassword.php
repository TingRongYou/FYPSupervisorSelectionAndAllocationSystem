<?php

/*
|--------------------------------------------------------------------------
| Variable Initialization
|--------------------------------------------------------------------------
| Store message text and status for user feedback.
*/
$message = "";
$status  = "";
$resetLink = "";

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
| Include database file and establish PDO connection.
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
| Form Submission Handling
|--------------------------------------------------------------------------
| Process forgot password form when submitted using POST method.
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get email input from the form and remove extra spaces
    $email = trim($_POST["email"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Email Validation
    |--------------------------------------------------------------------------
    | Validate whether email input is empty or invalid.
    */

    // Check if email field is empty
    if ($email === "") {

        $status = "error";
        $message = "Please enter your email.";

    // Validate email format
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $status = "error";
        $message = "Please enter a valid email address.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Security Message Handling
        |--------------------------------------------------------------------------
        | Always show same message to prevent email enumeration attacks.
        */

        $status = "success";

        $message =
            "If that email is registered, a password reset link will appear below.";

            /*
        |--------------------------------------------------------------------------
        | User Lookup
        |--------------------------------------------------------------------------
        | Search for matching university email in database.
        */

        // Prepare SQL query to find user by university email
        $stmt = $pdo->prepare(
            "SELECT userID FROM USER WHERE universityEmail = ? LIMIT 1"
        );

        // Execute query using entered email
        $stmt->execute([$email]);

        // Fetch user data as associative array
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        /*
        |--------------------------------------------------------------------------
        | Reset Token Generation
        |--------------------------------------------------------------------------
        | Generate reset token and expiry if user exists.
        */

        // Check whether user exists
        if ($user) {

            // Generate secure random reset token
            $token = bin2hex(random_bytes(32));

            // Set token expiry time to 1 hour later.
            $expiresAt = date("Y-m-d H:i:s", time() + 3600);

            /*
            |--------------------------------------------------------------------------
            | Database Token Update
            |--------------------------------------------------------------------------
            | Save reset token and expiry into database.
            */

            // Prepare SQL query to update reset token and expiry
            $stmt = $pdo->prepare("
                UPDATE USER
                SET resetToken = ?, resetExpires = ?
                WHERE universityEmail = ?
            ");

            // Execute update query
            $stmt->execute([
                $token,
                $expiresAt,
                $email
            ]);

            /*
            |--------------------------------------------------------------------------
            | Reset Link Generation
            |--------------------------------------------------------------------------
            | Build full password reset URL dynamically.
            */

            // Detect whether website uses HTTPS
            $scheme =
                (isset($_SERVER["HTTPS"])
                && $_SERVER["HTTPS"] === "on")
                ? "https"
                : "http";

            // Get current website host/domain
            $host = $_SERVER["HTTP_HOST"];

            // Get current directory path
            $dir =
                rtrim(
                    dirname($_SERVER["PHP_SELF"]),
                    "/\\"
                );

            if ($stmt->rowCount() > 0) {
                // Build full password reset link only after the token is saved.
                $resetLink =
                    $scheme . "://"
                    . $host
                    . $dir
                    . "/changePassword.php?token="
                    . urlencode($token);

                $message = "Use the local reset link below to continue.";
            }

            /*
            |--------------------------------------------------------------------------
            | Local Reset Link Fallback
            |--------------------------------------------------------------------------
            | XAMPP does not provide SMTP by default, so the reset link is shown
            | directly instead of attempting to send an email.
            */
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SSAS</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/auth.js" defer></script>
</head>
<body>
    <main class="page">
        <img class="brand-image" src="../assets/img/tarumt_logo_with_name.png" alt="TAR UMT Logo">
        <h1 class="subtitle">Forgot Password</h1>

        <section class="card">
            <div class="card-body">
                <h2>Reset Your Password</h2>

                <?php if ($message !== ""): ?>
                    <div class="message <?php echo e($status); ?>">
                        <?php echo e($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($resetLink !== ""): ?>
                    <div class="local-reset">
                        <strong>Local reset link</strong>
                        SMTP is disabled in this local setup. Use this link to reset the account password. The link expires in 1 hour.
                        <a href="<?php echo e($resetLink); ?>">Open Password Reset Page</a>
                    </div>
                <?php endif; ?>

                <?php if ($resetLink === ""): ?>
                    <form method="POST" data-email-validation>
                        <div class="field">
                            <input type="email" name="email" placeholder="Enter your university email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="button-row">
                            <button type="submit">Send Reset Link</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <a class="back" href="login.html">Back to Login</a>
        </section>
    </main>
</body>
</html>
