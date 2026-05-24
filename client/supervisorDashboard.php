<?php

require_once "../server/application/SessionManager.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireLogin();

/*
|--------------------------------------------------------------------------
| RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Supervisor"
);

/*
|--------------------------------------------------------------------------
| Escape Output Helper
|--------------------------------------------------------------------------
*/

function e($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
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
        Supervisor Dashboard | SSAS
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

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
                background .2s,
                transform .2s;
        }

        .nav-link:hover,
        .nav-link.active {

            background: #176fac;

            transform: translateX(2px);
        }

        /*
        |--------------------------------------------------------------------------
        | Main Content
        |--------------------------------------------------------------------------
        */

        .main {

            flex: 1;

            padding: 34px;
        }

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 16px;

            margin-bottom: 28px;
        }

        h1 {

            margin: 0 0 8px;

            color: #0b3760;

            font-size: 32px;
        }

        .welcome {

            margin: 0;

            color: #5c6f82;

            font-size: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard Cards
        |--------------------------------------------------------------------------
        */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 20px;
        }

        .card {

            background: #ffffff;

            border: 1px solid #d9e7f3;

            border-radius: 10px;

            padding: 24px;

            box-shadow:
                0 8px 22px
                rgba(11,79,138,.08);

            transition:
                transform .2s,
                box-shadow .2s;
        }

        .card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 14px 26px
                rgba(11,79,138,.14);
        }

        .card-title {

            margin: 0 0 12px;

            color: #0b3760;

            font-size: 20px;

            font-weight: 700;
        }

        .card-description {

            margin: 0 0 20px;

            color: #526a7f;

            line-height: 1.6;

            font-size: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 42px;

            padding: 0 18px;

            border-radius: 6px;

            background: #0b66ad;

            color: #ffffff;

            text-decoration: none;

            font-size: 14px;

            font-weight: 700;

            transition:
                background .2s,
                transform .2s,
                box-shadow .2s;
        }

        .button:hover {

            background: #084f88;

            transform: translateY(-1px);

            box-shadow:
                0 8px 18px
                rgba(11,102,173,.2);
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

            .dashboard-grid {

                grid-template-columns: 1fr;
            }

            .page-header {

                display: block;
            }
        }

    </style>
</head>

<body>

    <div class="layout">

        <!-- Sidebar -->

        <aside class="sidebar">

            <div class="brand">
                SSAS
            </div>

            <div class="subtitle">
                Supervisor Selection and Allocation System
            </div>

            <a
                class="nav-link active"
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

        <!-- Main Content -->

        <main class="main">

            <section class="page-header">

                <div>

                    <h1>
                        Supervisor Dashboard
                    </h1>

                    <p class="welcome">
                        Welcome back,
                        <?php echo e($_SESSION["fullName"]); ?>
                    </p>

                </div>

            </section>

            <!-- Dashboard Modules -->

            <section class="dashboard-grid">

                <!-- Digital Business Card -->

                <article class="card">

                    <h2 class="card-title">
                        Digital Business Card
                    </h2>

                    <p class="card-description">
                        Manage your supervisor profile,
                        programme details,
                        and professional information
                        visible to students.
                    </p>

                    <a
                        class="button"
                        href="manageDigitalBusinessCard.php"
                    >
                        Manage Profile
                    </a>

                </article>

                <!-- Expertise Tags -->

                <article class="card">

                    <h2 class="card-title">
                        Expertise & Tags
                    </h2>

                    <p class="card-description">
                        Configure your research interests
                        and expertise tags for intelligent
                        student-supervisor matching.
                    </p>

                    <a
                        class="button"
                        href="manageExpertiseTags.php"
                    >
                        Manage Tags
                    </a>

                </article>

                <!-- Introductory Video -->

                <article class="card">

                    <h2 class="card-title">
                        Introductory Video
                    </h2>

                    <p class="card-description">
                        Add or update a YouTube or Vimeo
                        introduction video for students
                        during supervisor discovery.
                    </p>

                    <a
                        class="button"
                        href="manageIntroVideo.php"
                    >
                        Manage Video
                    </a>

                </article>

                <!-- Past Projects -->

                <article class="card">

                    <h2 class="card-title">
                        Past Projects Showcase
                    </h2>

                    <p class="card-description">
                        Showcase previous FYP projects
                        supervised to help students
                        understand your supervision domain.
                    </p>

                    <a
                        class="button"
                        href="managePastProjects.php"
                    >
                        Manage Projects
                    </a>

                </article>

            </section>

        </main>

    </div>

</body>
</html>