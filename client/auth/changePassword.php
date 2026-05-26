<?php

$message    = "";
$status     = "";
$validToken = false;
$user       = null;

require_once "../../server/data/database/database.php";
$database = new Database();
$pdo      = $database->connect();

// Accept token from GET (initial link) or POST (form re-submission)
$token = trim($_GET["token"] ?? $_POST["token"] ?? "");

// Validate token first (for both GET and POST)
if ($token !== "") {

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE resetToken = ?
          AND resetExpires >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $validToken = true;
    }
}

if (!$validToken) {

    $status  = "error";
    $message = "This password reset link is invalid or has expired.";

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $newPassword     = $_POST["newPassword"]     ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    if ($newPassword === "" || $confirmPassword === "") {

        $status  = "error";
        $message = "Please fill in all fields.";

    } elseif ($newPassword !== $confirmPassword) {

        $status  = "error";
        $message = "Passwords do not match.";

    } elseif (strlen($newPassword) < 8) {

        $status  = "error";
        $message = "Password must be at least 8 characters.";

    } else {

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password     = ?,
                resetToken   = NULL,
                resetExpires = NULL
            WHERE email = ?
        ");
        $stmt->execute([$hashedPassword, $user["email"]]);

        header("Location: login.html?message=" . urlencode("Password changed successfully. Please login.") . "&type=success");
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

    <style>
        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #25364a;
            background:
                linear-gradient(rgba(207, 231, 246, .18), rgba(207, 231, 246, .05)),
                url("../assets/background.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 18px 20px;
        }

        .brand-image {
            width: 322px;
            max-width: 78vw;
            height: 78px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .subtitle {
            margin: 0 0 20px;
            font-size: 35px;
            font-weight: 300;
            color: #2b3745;
            text-align: center;
        }

        .card {
            width: 374px;
            max-width: 100%;
            background: rgba(255, 255, 255, .92);
            border: 1px solid rgba(64, 91, 114, .28);
            box-shadow: 0 8px 28px rgba(29, 54, 74, .26);
        }

        .card-body { padding: 34px 36px 26px; }

        h2 {
            margin: 0 0 22px;
            padding-bottom: 9px;
            border-bottom: 1px solid #c8dbef;
            color: #3c82d7;
            font-size: 20px;
            font-weight: 300;
        }

        input {
            width: 100%;
            height: 34px;
            border: 1px solid #c6d2df;
            background: #eaf1fb;
            padding: 0 10px;
            font-size: 14px;
            outline: none;
            margin-bottom: 12px;
        }

        input:focus {
            border-color: #ff8a3d;
            box-shadow: 0 0 0 1px #ff8a3d inset;
        }

        .button-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }

        button {
            min-width: 130px;
            height: 38px;
            border: 0;
            background: #448dca;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }

        button:hover { background: #287abc; }

        .message {
            margin-bottom: 12px;
            padding: 9px 10px;
            font-size: 13px;
        }

        .success { background: #e5f6ed; color: #177345; }
        .error   { background: #fdeaea; color: #9a2626; }

        .back {
            display: block;
            padding: 12px 14px;
            background: #4a94cf;
            color: #fff56d;
            text-decoration: none;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <main class="page">
        <img class="brand-image" src="../assets/tarumt_logo.png" alt="TAR UMT Logo">

        <h1 class="subtitle">Change Password</h1>

        <section class="card">
            <div class="card-body">
                <h2>Create New Password</h2>

                <?php if ($message !== ""): ?>
                    <div class="message <?php echo htmlspecialchars($status); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <form method="POST">
                        <input type="hidden" name="token"
                            value="<?php echo htmlspecialchars($token); ?>">

                        <input type="password" name="newPassword"
                            placeholder="New password (min. 8 characters)" required>

                    <input type="password" name="confirmPassword"
                        placeholder="Confirm new password" required>

                        <div class="button-row">
                            <button type="submit">Change Password</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <a class="back" href="login.html">Back to Login</a>
        </section>
    </main>
</body>
</html>


