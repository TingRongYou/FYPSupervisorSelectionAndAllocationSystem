<?php

$message = "";
$status  = "";

require_once "../../server/data/database/database.php";
$database = new Database();
$pdo      = $database->connect();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {

        $status  = "error";
        $message = "Please enter your email.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $status  = "error";
        $message = "Please enter a valid email address.";

    } else {

        // Always show the same message to prevent email enumeration
        $status  = "success";
        $message = "If that email is registered, a password reset link has been sent.";

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            $token     = bin2hex(random_bytes(32));
            $expiresAt = date("Y-m-d H:i:s", strtotime("+30 minutes"));

            $stmt = $pdo->prepare("
                UPDATE users
                SET resetToken = ?, resetExpires = ?
                WHERE email = ?
            ");
            $stmt->execute([$token, $expiresAt, $email]);

            $scheme    = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") ? "https" : "http";
            $host      = $_SERVER["HTTP_HOST"];
            $dir       = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
            $resetLink = $scheme . "://" . $host . $dir . "/changePassword.php?token=" . urlencode($token);

            $subject = "SSAS Password Reset";
            $body    =
                "Dear user,\n\n"
                . "Click the link below to reset your password:\n\n"
                . $resetLink . "\n\n"
                . "This link expires in 30 minutes.\n\n"
                . "If you did not request a password reset, please ignore this email.\n\n"
                . "Regards,\nSSAS System";

            $headers = "From: noreply@ssas.local\r\nContent-Type: text/plain; charset=UTF-8";

            mail($email, $subject, $body, $headers);
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
        }

        input:focus {
            border-color: #ff8a3d;
            box-shadow: 0 0 0 1px #ff8a3d inset;
        }

        .button-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 22px;
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

        <h1 class="subtitle">Forgot Password</h1>

        <section class="card">
            <div class="card-body">
                <h2>Reset Your Password</h2>

                <?php if ($message !== ""): ?>
                    <div class="message <?php echo htmlspecialchars($status); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($status !== "success"): ?>
                    <form method="POST">
                        <input type="email" name="email"
                            placeholder="Enter your university email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required>
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


