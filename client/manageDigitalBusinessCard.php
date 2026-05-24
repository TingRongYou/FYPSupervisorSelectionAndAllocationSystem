<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/SupervisorProfileService.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication + RBAC
|--------------------------------------------------------------------------
*/
if (!SessionManager::isLoggedIn()) {

    die("Access Denied");
}

SessionManager::requireRole("Supervisor");

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/
if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Service Layer
|--------------------------------------------------------------------------
*/
$profileService = new SupervisorProfileService();
$profile = $profileService->getDigitalBusinessCard($_SESSION["userID"]);

/*
|--------------------------------------------------------------------------
| Output Escaping
|--------------------------------------------------------------------------
*/
function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| Status Message
|--------------------------------------------------------------------------
*/
function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class = $_GET["status"] === "success"
        ? "success"
        : "error";

    return '
        <div class="message ' . $class . '">
            ' . e($_GET["message"]) . '
        </div>
    ';
}

/*
|--------------------------------------------------------------------------
| Safe Defaults
|--------------------------------------------------------------------------
*/
$availableSlots = $profile["availableSlots"] ?? 0;
$currentSupervisees = $profile["currentSupervisees"] ?? 0;
$maxSuperviseesAllowed = $profile["maxSuperviseesAllowed"] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Digital Business Card | SSAS
    </title>

    <style>

        * {

            box-sizing: border-box;
        }

        body {

            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8fc;
            color: #1d2b3a;
        }

        .layout {

            display: flex;
            min-height: 100vh;
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar
        |--------------------------------------------------------------------------
        */

        .sidebar {

            width: 260px;
            background: #0b4f8a;
            color: #ffffff;
            padding: 28px 22px;
        }

        .brand {

            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {

            color: #cfe5f8;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        .nav-link {

            display: block;
            color: #eaf5ff;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {

            background: #176fac;
            transform: translateX(2px);
        }

        /*
        |--------------------------------------------------------------------------
        | Main
        |--------------------------------------------------------------------------
        */

        .main {

            flex: 1;
            padding: 34px;
        }

        h1 {

            margin: 0 0 8px;
            color: #0b3760;
            font-size: 30px;
        }

        .hint {

            margin: 0 0 24px;
            color: #5c6f82;
        }

        /*
        |--------------------------------------------------------------------------
        | Cards
        |--------------------------------------------------------------------------
        */

        .grid {

            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 20px;
        }

        .card {

            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 8px;
            padding: 22px;
            box-shadow:
                0 8px 22px rgba(11, 79, 138, 0.08);
        }

        .card h2 {

            margin: 0 0 18px;
            color: #0b3760;
            font-size: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Forms
        |--------------------------------------------------------------------------
        */

        .field {

            margin-bottom: 16px;
        }

        label,
        .label {

            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #35546d;
            margin-bottom: 7px;
        }

        input,
        select {

            width: 100%;
            height: 42px;
            border: 1px solid #c6d8e8;
            border-radius: 6px;
            padding: 0 12px;
            font-size: 14px;
            background: #ffffff;
        }

        input[readonly] {

            background: #eef5fb;
            color: #526a7f;
        }

        input:focus,
        select:focus {

            outline: 2px solid #9dccf1;
            border-color: #2179b8;
        }

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .button {

            height: 42px;
            border: 0;
            border-radius: 6px;
            padding: 0 18px;
            background: #0b66ad;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .button:hover {

            background: #084f88;
            transform: translateY(-1px);

            box-shadow:
                0 8px 18px rgba(11, 102, 173, 0.2);
        }

        /*
        |--------------------------------------------------------------------------
        | Metrics
        |--------------------------------------------------------------------------
        */

        .metric {

            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .metric-item {

            background: #e8f2fb;
            border-radius: 8px;
            padding: 16px;
        }

        .metric-value {

            color: #0b4f8a;
            font-size: 24px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        */

        .message {

            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .message.success {

            background: #e5f6ed;
            color: #177345;
            border: 1px solid #a9dfbf;
        }

        .message.error {

            background: #fdeaea;
            color: #a52d2d;
            border: 1px solid #f0b8b8;
        }

        /*
        |--------------------------------------------------------------------------
        | Empty State
        |--------------------------------------------------------------------------
        */

        .empty {

            background: #ffffff;
            border: 1px dashed #aac7df;
            border-radius: 8px;
            padding: 28px;
            color: #526a7f;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .layout {

                display: block;
            }

            .sidebar {

                width: 100%;
            }

            .main {

                padding: 22px;
            }

            .grid,
            .metric {

                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            SSAS
        </div>

        <div class="subtitle">
            TAR UMT Supervisor Selection and Allocation System
        </div>

        <a
            class="nav-link"
            href="supervisorDashboard.php"
        >
            Dashboard
        </a>

        <a
            class="nav-link active"
            href="manageDigitalBusinessCard.php"
        >
            Digital Business Card
        </a>

        <a
            class="nav-link"
            href="manageExpertiseTags.php"
        >
            Expertise & Tags
        </a>

        <a
            class="nav-link"
            href="manageIntroVideo.php"
        >
            Introductory Video
        </a>

        <a
            class="nav-link"
            href="managePastProjects.php"
        >
            Past Projects
        </a>

        <a
            class="nav-link"
            href="../server/application/logout.php"
        >
            Logout
        </a>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <h1>
            Manage Digital Business Card
        </h1>

        <p class="hint">
            Maintain the profile information displayed
            to students during supervisor discovery.
        </p>

        <?php echo statusMessage(); ?>

        <?php if (!$profile): ?>

            <div class="empty">
                Supervisor profile was not found.
            </div>

        <?php else: ?>

            <div class="grid">

                <!-- PROFILE FORM -->
                <form
                    class="card"
                    action="../server/application/updateSupervisorProfile.php"
                    method="POST"
                >

                    <h2>
                        Profile Details
                    </h2>

                    <!-- CSRF -->
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo e($_SESSION["csrf_token"]); ?>"
                    >

                    <div class="field">

                        <label for="fullName">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="fullName"
                            value="<?php echo e($profile["fullName"]); ?>"
                            readonly
                        >
                    </div>

                    <div class="field">

                        <label for="universityEmail">
                            University Email
                        </label>

                        <input
                            type="email"
                            id="universityEmail"
                            value="<?php echo e($profile["universityEmail"]); ?>"
                            readonly
                        >
                    </div>

                    <div class="field">

                        <label for="programme">
                            Programme
                        </label>

                        <input
                            type="text"
                            id="programme"
                            name="programme"
                            value="<?php echo e($profile["programme"]); ?>"
                            required
                        >
                    </div>

                    <!-- IMPROVED -->
                    <div class="field">

                        <label for="employmentCategory">
                            Employment Category
                        </label>

                        <select
                            id="employmentCategory"
                            name="employmentCategory"
                            required
                        >

                            <option value="">
                                Select Category
                            </option>

                            <option
                                value="Full-Time"
                                <?php echo ($profile["employmentCategory"] === "Full-Time") ? "selected" : ""; ?>
                            >
                                Full-Time
                            </option>

                            <option
                                value="Part-Time"
                                <?php echo ($profile["employmentCategory"] === "Part-Time") ? "selected" : ""; ?>
                            >
                                Part-Time
                            </option>

                            <option
                                value="Admin"
                                <?php echo ($profile["employmentCategory"] === "Admin") ? "selected" : ""; ?>
                            >
                                Admin
                            </option>

                        </select>

                    </div>

                    <div class="field">

                        <label for="introVideoLink">
                            Introductory Video Link
                        </label>

                        <input
                            type="url"
                            id="introVideoLink"
                            name="introVideoLink"
                            placeholder="https://youtube.com/..."
                            value="<?php echo e($profile["introVideoLink"]); ?>"
                        >
                    </div>

                    <button
                        class="button"
                        type="submit"
                    >
                        Save Business Card
                    </button>

                </form>

                <!-- QUOTA -->
                <section class="card">

                    <h2>
                        Quota Summary
                    </h2>

                    <div class="field">

                        <span class="label">
                            Quota Tier
                        </span>

                        <p>
                            <?php echo e($profile["quotaTierName"] ?? "N/A"); ?>
                        </p>

                    </div>

                    <div class="metric">

                        <div class="metric-item">

                            <div class="metric-value">
                                <?php echo e($profile["quotaText"] ?? "0 / 0"); ?>
                            </div>

                            <div class="label">
                                Current Quota
                            </div>

                        </div>

                        <div class="metric-item">

                            <div class="metric-value">
                                <?php echo e($availableSlots); ?>
                            </div>

                            <div class="label">
                                Available Slots
                            </div>

                        </div>

                    </div>

                    <div class="field" style="margin-top: 18px;">

                        <span class="label">
                            Active Supervisees
                        </span>

                        <p>
                            <?php echo e($currentSupervisees); ?>
                            /
                            <?php echo e($maxSuperviseesAllowed); ?>
                        </p>

                    </div>

                </section>

            </div>

        <?php endif; ?>

    </main>

</div>

</body>
</html>