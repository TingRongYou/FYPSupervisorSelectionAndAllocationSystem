<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/PastProjectService.php";

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

$pastProjectService = new PastProjectService();

$projects = $pastProjectService
    ->getProjectsBySupervisor(
        $_SESSION["userID"]
    );

$editingProject = null;

/*
|--------------------------------------------------------------------------
| Edit Mode
|--------------------------------------------------------------------------
*/

if (isset($_GET["editProjectID"])) {

    $editingProject =
        $pastProjectService
        ->getProjectByID(
            $_GET["editProjectID"],
            $_SESSION["userID"]
        );
}

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
        Manage Past Projects | SSAS
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
            grid-template-columns: 0.85fr 1.15fr;
            gap: 20px;

            align-items: start;
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
        | Buttons
        |--------------------------------------------------------------------------
        */

        .button {

            min-height: 38px;

            border: 0;
            border-radius: 6px;

            padding: 0 14px;

            background: #0b66ad;
            color: #ffffff;

            font-size: 14px;
            font-weight: 700;

            cursor: pointer;
            text-decoration: none;

            display: inline-flex;
            align-items: center;
            justify-content: center;

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

        .button.secondary {

            background: #e8f2fb;
            color: #0b4f8a;
        }

        .button.danger {

            background: #c93838;
        }

        /*
        |--------------------------------------------------------------------------
        | Projects
        |--------------------------------------------------------------------------
        */

        .project-list {

            display: grid;
            gap: 14px;
        }

        .project {

            border: 1px solid #d9e7f3;
            border-radius: 8px;
            padding: 16px;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .project:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 18px rgba(11,79,138,.1);
        }

        .project h3 {

            margin: 0 0 8px;
            color: #0b3760;
        }

        .project-meta {

            margin: 0 0 12px;
            color: #526a7f;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        */

        .actions {

            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .inline-form {

            display: inline;
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
            line-height: 1.6;
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
            class="nav-link"
            href="manageIntroVideo.php"
        >
            Introductory Video
        </a>

        <a
            class="nav-link active"
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
            Manage Past Projects Showcase
        </h1>

        <p class="hint">

            Showcase completed FYP projects
            to help students understand
            your supervision domains.

        </p>

        <?php echo statusMessage(); ?>

        <div class="info">

            Past project showcases improve
            student understanding of your
            research expertise and supervision
            experience.

        </div>

        <div class="grid">

            <!-- FORM -->
            <form
                class="card"
                action="../server/application/managePastProjectProcess.php"
                method="POST"
                id="projectForm"
            >

                <h2>

                    <?php echo $editingProject
                        ? "Edit Past Project"
                        : "Add Past Project"; ?>

                </h2>

                <!-- CSRF -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo e($_SESSION["csrf_token"]); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="<?php echo $editingProject
                        ? "update"
                        : "add"; ?>"
                >

                <?php if ($editingProject): ?>

                    <input
                        type="hidden"
                        name="projectID"
                        value="<?php echo e(
                            $editingProject["projectID"]
                        ); ?>"
                    >

                <?php endif; ?>

                <div class="field">

                    <label for="projectTitle">
                        Project Title
                    </label>

                    <input
                        type="text"
                        id="projectTitle"
                        name="projectTitle"

                        maxlength="255"

                        value="<?php echo e(
                            $editingProject["projectTitle"] ?? ""
                        ); ?>"

                        required
                    >

                </div>

                <div class="field">

                    <label for="completionYear">
                        Completion Year
                    </label>

                    <input
                        type="number"
                        id="completionYear"
                        name="completionYear"

                        min="2000"

                        max="<?php echo e(
                            ((int) date("Y")) + 1
                        ); ?>"

                        value="<?php echo e(
                            $editingProject["completionYear"] ?? ""
                        ); ?>"

                        required
                    >

                </div>

                <div class="field">

                    <label for="alumniName">
                        Alumni Name
                    </label>

                    <input
                        type="text"
                        id="alumniName"
                        name="alumniName"

                        maxlength="100"

                        value="<?php echo e(
                            $editingProject["alumniName"] ?? ""
                        ); ?>"

                        required
                    >

                </div>

                <div class="actions">

                    <button
                        class="button"
                        type="submit"
                    >

                        <?php echo $editingProject
                            ? "Update Project"
                            : "Add Project"; ?>

                    </button>

                    <?php if ($editingProject): ?>

                        <a
                            class="button secondary"
                            href="managePastProjects.php"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </form>

            <!-- PROJECT LIST -->
            <section class="card">

                <h2>
                    Past Projects
                </h2>

                <?php if (empty($projects)): ?>

                    <div class="empty">

                        No past projects
                        have been added yet.

                        <br><br>

                        Add completed projects
                        to strengthen your
                        professional showcase.

                    </div>

                <?php else: ?>

                    <div class="project-list">

                        <?php foreach ($projects as $project): ?>

                            <article class="project">

                                <h3>
                                    <?php echo e(
                                        $project["projectTitle"]
                                    ); ?>
                                </h3>

                                <p class="project-meta">

                                    <?php echo e(
                                        $project["completionYear"]
                                    ); ?>

                                    · Alumni:

                                    <?php echo e(
                                        $project["alumniName"]
                                    ); ?>

                                </p>

                                <div class="actions">

                                    <!-- EDIT -->
                                    <a
                                        class="button secondary"

                                        href="managePastProjects.php?editProjectID=<?php echo e(
                                            $project["projectID"]
                                        ); ?>"
                                    >
                                        Edit
                                    </a>

                                    <!-- DELETE -->
                                    <form
                                        class="inline-form"

                                        action="../server/application/managePastProjectProcess.php"

                                        method="POST"
                                    >

                                        <!-- CSRF -->
                                        <input
                                            type="hidden"
                                            name="csrf_token"

                                            value="<?php echo e(
                                                $_SESSION["csrf_token"]
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="projectID"

                                            value="<?php echo e(
                                                $project["projectID"]
                                            ); ?>"
                                        >

                                        <button
                                            class="button danger"
                                            type="submit"

                                            onclick="return confirm('Delete this project?')"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<!-- FRONTEND VALIDATION -->
<script>

document
.getElementById("projectForm")
.addEventListener("submit", function(event) {

    const year =
        parseInt(
            document
            .getElementById("completionYear")
            .value
        );

    const currentYear =
        new Date().getFullYear() + 1;

    if (
        isNaN(year) ||
        year < 2000 ||
        year > currentYear
    ) {

        event.preventDefault();

        alert(
            "Please enter a valid completion year."
        );
    }
});

</script>

</body>
</html>