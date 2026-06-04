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

    <!-- Character encoding -->
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <!-- Browser page title -->
    <title>Forgot Password - SSAS</title>

    <style>

        /* Apply box sizing to all elements */
        * {
            box-sizing: border-box;
        }

        /*
        |--------------------------------------------------------------------------
        | Body Styling
        |--------------------------------------------------------------------------
        | Configure full-page layout and background.
        */
        body {

            /* Make body take full screen height */
            min-height: 100vh;

            margin: 0;
            font-family: Arial, Helvetica, sans-serif;

            /* Set default text color */
            color: #25364a;

            /* Add overlay gradient and background image */
            background:
                linear-gradient(
                    rgba(207, 231, 246, .18),
                    rgba(207, 231, 246, .05)
                ),
                url("../assets/background.jpg");

            /* Make image cover full screen */
            background-size: cover;
            background-position: center;

            /* Prevent repeating image */
            background-repeat: no-repeat;
        }

        /*
        |--------------------------------------------------------------------------
        | Main Page Layout
        |--------------------------------------------------------------------------
        | Center page content vertically and horizontally.
        */
        .page {

            /* Full screen height */
            min-height: 100vh;

            /* Use flexbox layout */
            display: flex;

            /* Arrange items vertically */
            flex-direction: column;

            /* Center items horizontally */
            align-items: center;

            /* Inner spacing */
            padding: 18px 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Logo Styling
        |--------------------------------------------------------------------------
        */
        .brand-image {

            /* Logo width */
            width: 322px;

            /* Responsive maximum width */
            max-width: 78vw;

            /* Logo height */
            height: 78px;

            /* Keep image ratio */
            object-fit: contain;

            /* Bottom spacing */
            margin-bottom: 6px;
        }

        /*
        |--------------------------------------------------------------------------
        | Page Subtitle
        |--------------------------------------------------------------------------
        */
        .subtitle {

            /* Remove default margin except bottom */
            margin: 0 0 20px;

            /* Large text size */
            font-size: 35px;

            /* Thin font weight */
            font-weight: 300;

            /* Text color */
            color: #2b3745;

            /* Center align text */
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Card Container
        |--------------------------------------------------------------------------
        */

        .card {

            /* Card width */
            width: 374px;

            /* Responsive maximum width */
            max-width: 100%;

            /* Semi-transparent white background */
            background: rgba(255, 255, 255, .92);

            /* Border around card */
            border: 1px solid rgba(64, 91, 114, .28);

            /* Shadow effect */
            box-shadow: 0 8px 28px rgba(29, 54, 74, .26);
        }

        /*
        |--------------------------------------------------------------------------
        | Card Body
        |--------------------------------------------------------------------------
        */
        .card-body {

            /* Inner spacing */
            padding: 34px 36px 26px;
        }

        /*
        |--------------------------------------------------------------------------
        | Form Heading
        |--------------------------------------------------------------------------
        */
        h2 {

            /* Bottom spacing */
            margin: 0 0 22px;

            /* Space below text */
            padding-bottom: 9px;

            /* Bottom border line */
            border-bottom: 1px solid #c8dbef;

            /* Text color */
            color: #3c82d7;

            /* Font size */
            font-size: 20px;

            /* Thin font */
            font-weight: 300;
        }

        /*
        |--------------------------------------------------------------------------
        | Input Field Styling
        |--------------------------------------------------------------------------
        */
        input {

            /* Full width input */
            width: 100%;

            /* Input height */
            height: 34px;

            /* Border style */
            border: 1px solid #c6d2df;

            /* Background color */
            background: #eaf1fb;

            /* Inner spacing */
            padding: 0 10px;

            /* Text size */
            font-size: 14px;

            /* Remove default outline */
            outline: none;
        }

        /*
        |--------------------------------------------------------------------------
        | Input Focus Effect
        |--------------------------------------------------------------------------
        */
        input:focus {

            /* Border color when focused */
            border-color: #ff8a3d;

            /* Inner glow effect */
            box-shadow: 0 0 0 1px #ff8a3d inset;
        }

        /*
        |--------------------------------------------------------------------------
        | Button Row
        |--------------------------------------------------------------------------
        */
        .button-row {

            /* Use flexbox */
            display: flex;

            /* Push button to right */
            justify-content: flex-end;

            /* Top spacing */
            margin-top: 22px;
        }

        /*
        |--------------------------------------------------------------------------
        | Button Styling
        |--------------------------------------------------------------------------
        */
        button {

            /* Minimum button width */
            min-width: 130px;

            /* Button height */
            height: 38px;

            /* Remove border */
            border: 0;

            /* Button background color */
            background: #448dca;

            /* Text color */
            color: white;

            /* Font size */
            font-size: 14px;

            /* Show pointer cursor */
            cursor: pointer;
        }

        /*
        |--------------------------------------------------------------------------
        | Button Hover Effect
        |--------------------------------------------------------------------------
        */
        button:hover {

            /* Change color on hover */
            background: #287abc;
        }

        /*
        |--------------------------------------------------------------------------
        | Status Message Styling
        |--------------------------------------------------------------------------
        */
        .message {

            /* Bottom spacing */
            margin-bottom: 12px;

            /* Inner spacing */
            padding: 9px 10px;

            /* Font size */
            font-size: 13px;
        }

        /* Success message style */
        .success {
            background: #e5f6ed;
            color: #177345;
        }

        /* Error message style */
        .error {
            background: #fdeaea;
            color: #9a2626;
        }

        .local-reset {
            margin: 14px 0 4px;
            padding: 12px;
            border: 1px solid #b9d4f5;
            background: #eef6ff;
            color: #244467;
            font-size: 13px;
            line-height: 1.45;
        }

        .local-reset strong {
            display: block;
            margin-bottom: 6px;
            color: #0755b8;
        }

        .local-reset a {
            display: block;
            margin-top: 8px;
            padding: 10px 12px;
            background: #075fd8;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            word-break: break-word;
        }

        .local-reset a:hover {
            background: #004aa8;
        }

        /*
        |--------------------------------------------------------------------------
        | Back Link Styling
        |--------------------------------------------------------------------------
        */
        .back {

            /* Make link behave like block */
            display: block;

            /* Inner spacing */
            padding: 12px 14px;

            /* Background color */
            background: #4a94cf;

            /* Text color */
            color: #fff56d;

            /* Remove underline */
            text-decoration: none;

            /* Font size */
            font-size: 16px;
        }

    </style>
</head>

<body>

    <!-- Main page container -->
    <main class="page">

        <!-- TAR UMT logo -->
        <img class="brand-image"
            src="../assets/tarumt_logo.png"
            alt="TAR UMT Logo">

        <!-- Page title -->
        <h1 class="subtitle">Forgot Password</h1>

        <!-- Card container -->
        <section class="card">

            <div class="card-body">

                <!-- Card title -->
                <h2>Reset Your Password</h2>

                <!-- Display message if message exists -->
                <?php if ($message !== ""): ?>

                    <div class="message <?php echo htmlspecialchars($status); ?>">

                        <!-- Show message text safely -->
                        <?php echo htmlspecialchars($message); ?>

                    </div>

                <?php endif; ?>

                <?php if ($resetLink !== ""): ?>

                    <div class="local-reset">
                        <strong>Local reset link</strong>
                        SMTP is disabled in this local setup. Use this link to reset the account password. The link expires in 1 hour.

                        <a href="<?php echo htmlspecialchars($resetLink); ?>">
                            Open Password Reset Page
                        </a>
                    </div>

                <?php endif; ?>

                <!-- Show form if reset not successful -->
                <?php if ($resetLink === ""): ?>

                    <!-- Password reset form -->
                    <form method="POST">

                        <!-- Email input field -->
                        <input
                            type="email"
                            name="email"
                            placeholder="Enter your university email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >

                        <div class="button-row">

                            <!-- Submit button -->
                            <button type="submit">
                                Send Reset Link
                            </button>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

            <!-- Back to login link -->
            <a class="back" href="login.html">
                Back to Login
            </a>
        </section>

    </main>

</body>
</html>
