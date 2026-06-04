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

    <!-- Character encoding -->
    <meta charset="UTF-8">

    <!-- Responsive layout for mobile devices -->
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <!-- Browser page title -->
    <title>Change Password - SSAS</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | Global Reset Styles
        |--------------------------------------------------------------------------
        | Apply universal styling and box sizing.
        */
        * {
            box-sizing: border-box;
        }

        /*
        |--------------------------------------------------------------------------
        | Body Layout and Background
        |--------------------------------------------------------------------------
        | Configure full-page layout and background image.
        */
        body {

            /* Full screen height */
            min-height: 100vh;

            /* Remove default browser margin */
            margin: 0;

            /* Default font style */
            font-family: Arial, Helvetica, sans-serif;

            /* Default text color */
            color: #25364a;

            /* Background overlay and image */
            background:
                linear-gradient(
                    rgba(207, 231, 246, .18),
                    rgba(207, 231, 246, .05)
                ),
                url("../assets/background.jpg");

            /* Make image cover full page */
            background-size: cover;

            /* Center image */
            background-position: center;

            /* Prevent repeating image */
            background-repeat: no-repeat;
        }

        /*
        |--------------------------------------------------------------------------
        | Main Page Container
        |--------------------------------------------------------------------------
        | Center content vertically and horizontally.
        */
        .page {

            /* Full screen height */
            min-height: 100vh;

            /* Use flex layout */
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
        | Branding Section
        |--------------------------------------------------------------------------
        | Configure TAR UMT logo styling.
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
        | Style main page heading.
        */
        .subtitle {

            /* Margin settings */
            margin: 0 0 20px;

            /* Large font size */
            font-size: 35px;

            /* Thin font weight */
            font-weight: 300;

            /* Text color */
            color: #2b3745;

            /* Center text */
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Card Container
        |--------------------------------------------------------------------------
        | Style password reset card container.
        */
        .card {

            /* Card width */
            width: 374px;

            /* Responsive max width */
            max-width: 100%;

            /* Semi-transparent white background */
            background: rgba(255, 255, 255, .92);

            /* Border style */
            border: 1px solid rgba(64, 91, 114, .28);

            /* Shadow effect */
            box-shadow:
                0 8px 28px rgba(29, 54, 74, .26);
        }

        /*
        |--------------------------------------------------------------------------
        | Card Body
        |--------------------------------------------------------------------------
        | Configure spacing inside card.
        */
        .card-body {

            /* Inner spacing */
            padding: 34px 36px 26px;
        }

        /*
        |--------------------------------------------------------------------------
        | Section Title
        |--------------------------------------------------------------------------
        | Style form heading.
        */
        h2 {

            /* Bottom spacing */
            margin: 0 0 22px;

            /* Space below title */
            padding-bottom: 9px;

            /* Bottom border */
            border-bottom: 1px solid #c8dbef;

            /* Title color */
            color: #3c82d7;

            /* Font size */
            font-size: 20px;

            /* Thin font */
            font-weight: 300;
        }

        /*
        |--------------------------------------------------------------------------
        | Form Input Styling
        |--------------------------------------------------------------------------
        | Configure text field appearance.
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

            /* Font size */
            font-size: 14px;

            /* Remove default outline */
            outline: none;

            /* Bottom spacing */
            margin-bottom: 12px;
        }

        /*
        |--------------------------------------------------------------------------
        | Input Focus State
        |--------------------------------------------------------------------------
        | Highlight input field when focused.
        */
        input:focus {

            /* Border color on focus */
            border-color: #ff8a3d;

            /* Glow effect */
            box-shadow:
                0 0 0 1px #ff8a3d inset;
        }

        /*
        |--------------------------------------------------------------------------
        | Button Container
        |--------------------------------------------------------------------------
        | Align button to right side.
        */
        .button-row {

            /* Use flex layout */
            display: flex;

            /* Align button to right */
            justify-content: flex-end;

            /* Top spacing */
            margin-top: 12px;
        }

        /*
        |--------------------------------------------------------------------------
        | Button Styling
        |--------------------------------------------------------------------------
        | Configure submit button appearance.
        */
        button {

            /* Minimum width */
            min-width: 130px;

            /* Button height */
            height: 38px;

            /* Remove border */
            border: 0;

            /* Background color */
            background: #448dca;

            /* Text color */
            color: white;

            /* Font size */
            font-size: 14px;

            /* Pointer cursor */
            cursor: pointer;
        }

        /*
        |--------------------------------------------------------------------------
        | Button Hover Effect
        |--------------------------------------------------------------------------
        | Change button color when hovered.
        */
        button:hover {

            /* Hover background color */
            background: #287abc;
        }

        /*
        |--------------------------------------------------------------------------
        | Status Message Styling
        |--------------------------------------------------------------------------
        | Configure success and error messages.
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

        /*
        |--------------------------------------------------------------------------
        | Back Button Link
        |--------------------------------------------------------------------------
        | Style back-to-login navigation link.
        */
        .back {

            /* Display as block */
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

        <!-- Page heading -->
        <h1 class="subtitle">Change Password</h1>

        <!-- Card container -->
        <section class="card">

            <div class="card-body">

                <!-- Form title -->
                <h2>Create New Password</h2>

                <!-- Show message if exists -->
                <?php if ($message !== ""): ?>

                    <div class="message <?php echo htmlspecialchars($status); ?>">

                        <!-- Display message safely -->
                        <?php echo htmlspecialchars($message); ?>

                    </div>

                <?php endif; ?>

                <!-- Show form only if token is valid -->
                <?php if ($validToken): ?>

                    <!-- Password reset form -->
                    <form method="POST">

                        <!-- Hidden token input -->
                        <input
                            type="hidden"
                            name="token"
                            value="<?php echo htmlspecialchars($token); ?>"
                        >

                        <!-- New password input -->
                        <input
                            type="password"
                            name="newPassword"
                            placeholder="New password (min. 8 characters)"
                            required
                        >

                        <!-- Confirm password input -->
                        <input
                            type="password"
                            name="confirmPassword"
                            placeholder="Confirm new password"
                            required
                        >

                        <div class="button-row">

                            <!-- Submit button -->
                            <button type="submit">
                                Change Password
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
