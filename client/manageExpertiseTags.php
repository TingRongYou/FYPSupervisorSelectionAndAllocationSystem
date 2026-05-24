<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/TagManagementService.php";

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

$tagService = new TagManagementService();

$tags = $tagService->getAllTags();

$selectedTagIDs = $tagService->getSupervisorTagIDs(
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
        Manage Expertise & Tags | SSAS
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

        /*
        |--------------------------------------------------------------------------
        | Tag Grid
        |--------------------------------------------------------------------------
        */

        .tag-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 12px;

            margin: 18px 0;
        }

        .tag-option {

            border: 1px solid #c6d8e8;
            border-radius: 8px;
            padding: 12px;

            display: flex;
            gap: 10px;
            align-items: center;

            color: #243d54;

            transition:
                background 0.2s ease,
                border-color 0.2s ease;
        }

        .tag-option:hover {

            border-color: #0b66ad;
            background: #f2f8fd;
        }

        input[type="checkbox"] {

            width: 18px;
            height: 18px;
            accent-color: #0b66ad;
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
                0 8px 18px rgba(11,102,173,.2);
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

            border: 1px dashed #aac7df;
            border-radius: 8px;
            padding: 20px;
            color: #526a7f;
        }

        /*
        |--------------------------------------------------------------------------
        | Info Text
        |--------------------------------------------------------------------------
        */

        .info {

            background: #eef6fc;
            border-left: 4px solid #0b66ad;
            padding: 14px 16px;
            border-radius: 6px;
            color: #35546d;
            margin-bottom: 18px;
            line-height: 1.5;
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

            .tag-grid {

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
            class="nav-link active"
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
            Manage Expertise & Tags
        </h1>

        <p class="hint">
            Configure research expertise used
            for recommendation matching.
        </p>

        <?php echo statusMessage(); ?>

        <div class="info">

            Select between
            <strong>1</strong>
            and
            <strong>10</strong>
            research expertise tags.

            These tags are used by the
            Recommendation Engine to match
            students with supervisors.

        </div>

        <form
            class="card"
            action="../server/application/updateSupervisorTags.php"
            method="POST"
            id="tagForm"
        >

            <!-- CSRF -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION["csrf_token"]); ?>"
            >

            <?php if (empty($tags)): ?>

                <div class="empty">

                    No research tags are currently available.

                    Please ask the administrator
                    to configure research tags.

                </div>

            <?php else: ?>

                <div class="tag-grid">

                    <?php foreach ($tags as $tag): ?>

                        <?php
                            $tagID = (int) $tag["tagID"];
                        ?>

                        <label class="tag-option">

                            <input
                                type="checkbox"
                                name="tagIDs[]"
                                value="<?php echo e($tagID); ?>"

                                <?php echo in_array(
                                    $tagID,
                                    $selectedTagIDs,
                                    true
                                ) ? "checked" : ""; ?>
                            >

                            <span>
                                <?php echo e($tag["tagName"]); ?>
                            </span>

                        </label>

                    <?php endforeach; ?>

                </div>

                <button
                    class="button"
                    type="submit"
                >
                    Save Expertise Tags
                </button>

            <?php endif; ?>

        </form>

    </main>

</div>

<!-- VALIDATION -->
<script>

document
.getElementById("tagForm")
.addEventListener("submit", function(event) {

    const checkedTags =
        document.querySelectorAll(
            'input[name="tagIDs[]"]:checked'
        );

    if (
        checkedTags.length < 1 ||
        checkedTags.length > 10
    ) {

        event.preventDefault();

        alert(
            "Please select between 1 and 10 expertise tags."
        );
    }
});

</script>

</body>
</html>