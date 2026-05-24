<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/SupervisorProfileService.php";

/*
|--------------------------------------------------------------------------
| Session + RBAC
|--------------------------------------------------------------------------
*/

SessionManager::startSession();

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

$profile = $profileService->getDigitalBusinessCard(
    $_SESSION["userID"]
);

/*
|--------------------------------------------------------------------------
| Output Escaping
|--------------------------------------------------------------------------
*/

function e($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Status Messages
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
| Convert Video URL To Embed URL
|--------------------------------------------------------------------------
*/

function getEmbedURL($url) {

    if (empty($url)) {

        return "";
    }

    /*
    |--------------------------------------------------------------------------
    | YouTube
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/',
            $url,
            $matches
        )
    ) {

        return
            "https://www.youtube.com/embed/" .
            $matches[1];
    }

    /*
    |--------------------------------------------------------------------------
    | Vimeo
    |--------------------------------------------------------------------------
    */

    if (
        preg_match(
            '/vimeo\.com\/([0-9]+)/',
            $url,
            $matches
        )
    ) {

        return
            "https://player.vimeo.com/video/" .
            $matches[1];
    }

    return "";
}

$embedURL = getEmbedURL(
    $profile["introVideoLink"] ?? ""
);

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
        Manage Introductory Video | SSAS
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

        /*
        |--------------------------------------------------------------------------
        | Layout
        |--------------------------------------------------------------------------
        */

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
            margin-bottom: 28px;
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
        | Grid
        |--------------------------------------------------------------------------
        */

        .grid {

            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Card
        |--------------------------------------------------------------------------
        */

        .card {

            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 8px;
            padding: 22px;

            box-shadow:
                0 8px 22px rgba(11,79,138,.08);
        }

        .card h2 {

            margin: 0 0 18px;
            color: #0b3760;
            font-size: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Form
        |--------------------------------------------------------------------------
        */

        .field {

            margin-bottom: 16px;
        }

        label {

            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #35546d;
            margin-bottom: 7px;
        }

        input {

            width: 100%;
            height: 42px;

            border: 1px solid #c6d8e8;
            border-radius: 6px;

            padding: 0 12px;

            font-size: 14px;
            background: #ffffff;
        }

        input:focus {

            outline: 2px solid #9dccf1;
            border-color: #2179b8;
        }

        /*
        |--------------------------------------------------------------------------
        | Button
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
                0 8px 18px rgba(11,102,173,.2);
        }

        /*
        |--------------------------------------------------------------------------
        | Preview
        |--------------------------------------------------------------------------
        */

        .preview {

            background: #e8f2fb;
            border-radius: 8px;
            padding: 18px;

            color: #35546d;
            line-height: 1.6;
        }

        .video-link {

            color: #0b66ad;
            overflow-wrap: anywhere;
        }

        iframe {

            width: 100%;
            height: 320px;

            border: 0;
            border-radius: 8px;

            margin-top: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | Info
        |--------------------------------------------------------------------------
        */

        .info {

            background: #eef6fc;
            border-left: 4px solid #0b66ad;

            padding: 14px 16px;
            border-radius: 6px;

            color: #35546d;
            line-height: 1.6;

            margin-bottom: 18px;
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

            .grid {

                grid-template-columns: 1fr;
            }

            iframe {

                height: 240px;
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
            Supervisor Professional Profile
        </div>

        <a
            class="nav-link"
            href="supervisorDashboard.php"
        >
            Dashboard
        </a>

        <a
            class="nav-link"
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
            class="nav-link active"
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
            Manage Introductory Video
        </h1>

        <p class="hint">
            Add a professional introductory video
            to introduce your supervision style,
            research interests, and expectations.
        </p>

        <?php echo statusMessage(); ?>

        <div class="info">

            Only
            <strong>YouTube</strong>
            and
            <strong>Vimeo</strong>
            links are supported.

            Students will view this video
            during supervisor discovery.

        </div>

        <div class="grid">

            <!-- FORM -->
            <form
                class="card"
                action="../server/application/updateIntroVideo.php"
                method="POST"
                id="videoForm"
            >

                <h2>
                    Video URL
                </h2>

                <!-- CSRF -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo e($_SESSION["csrf_token"]); ?>"
                >

                <div class="field">

                    <label for="introVideoLink">
                        YouTube or Vimeo URL
                    </label>

                    <input
                        type="url"
                        id="introVideoLink"
                        name="introVideoLink"

                        placeholder="https://youtube.com/watch?v=..."

                        value="<?php echo e(
                            $profile["introVideoLink"] ?? ""
                        ); ?>"

                        pattern="https?:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+"

                        required
                    >

                </div>

                <button
                    class="button"
                    type="submit"
                >
                    Save Introductory Video
                </button>

            </form>

            <!-- PREVIEW -->
            <section class="card">

                <h2>
                    Current Video Preview
                </h2>

                <?php if (!empty(
                    $profile["introVideoLink"]
                )): ?>

                    <div class="preview">

                        Current Video URL:

                        <br><br>

                        <a
                            class="video-link"

                            href="<?php echo e(
                                $profile["introVideoLink"]
                            ); ?>"

                            target="_blank"

                            rel="noopener noreferrer"
                        >

                            <?php echo e(
                                $profile["introVideoLink"]
                            ); ?>

                        </a>

                        <?php if (!empty($embedURL)): ?>

                            <iframe
                                src="<?php echo e($embedURL); ?>"
                                allowfullscreen
                            ></iframe>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div class="preview">

                        No introductory video
                        has been configured yet.

                        <br><br>

                        Add a short video to help
                        students understand your
                        supervision approach.

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<!-- FRONTEND VALIDATION -->
<script>

document
.getElementById("videoForm")
.addEventListener("submit", function(event) {

    const url =
        document
        .getElementById("introVideoLink")
        .value
        .trim();

    const pattern =
        /^https?:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+$/i;

    if (!pattern.test(url)) {

        event.preventDefault();

        alert(
            "Please enter a valid YouTube or Vimeo URL."
        );
    }
});

</script>

</body>
</html>