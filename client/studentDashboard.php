<?php

require_once "../server/application/SessionManager.php";
require_once __DIR__ . "/accountLayout.php";

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
    "Student"
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
        Student Dashboard | SSAS
    </title>

    <style>
        <?php echo ssasAccountStyles(); ?>

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

            min-height: calc(100vh - 52px);
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

    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="layout">

        <!-- Sidebar -->

        <aside class="sidebar">

            <div class="brand">
                SSAS
            </div>

            <div class="subtitle">
                Student Supervisor Selection System
            </div>

            <a
                class="nav-link active"
                href="studentDashboard.php"
            >
                Dashboard
            </a>

            <a
                class="nav-link"
                href="studentDiscovery.php"
            >
                Supervisor Discovery
            </a>

            <a
                class="nav-link"
                href="studentProfile.php"
            >
                Student Profile
            </a>

            <a
                class="nav-link"
                href="studentApplicationStatus.php"
            >
                Application Status
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
                        Student Dashboard
                    </h1>

                    <p class="welcome">
                        Welcome back,
                        <?php echo e($_SESSION["fullName"]); ?>
                    </p>

                </div>

            </section>

            <!-- Dashboard Modules -->

            <section class="dashboard-grid">

                <!-- Discovery -->

                <article class="card">

                    <h2 class="card-title">
                        Supervisor Discovery
                    </h2>

                    <p class="card-description">
                        Browse supervisors,
                        search by programme,
                        and filter supervisors
                        based on availability.
                    </p>

                    <a
                        class="button"
                        href="studentDiscovery.php"
                    >
                        Discover Supervisors
                    </a>

                </article>

                <!-- Student Profile -->

                <article class="card">

                    <h2 class="card-title">
                        Student Profile
                    </h2>

                    <p class="card-description">
                        Update your student profile,
                        contact information,
                        research interests,
                        and portfolio links.
                    </p>

                    <a
                        class="button"
                        href="studentProfile.php"
                    >
                        Manage Profile
                    </a>

                </article>

                <!-- Application Status -->

                <article class="card">

                    <h2 class="card-title">
                        Application Status
                    </h2>

                    <p class="card-description">
                        Track proposal submissions,
                        pending applications,
                        and supervisor responses
                        in real-time.
                    </p>

                    <a
                        class="button"
                        href="studentApplicationStatus.php"
                    >
                        View Status
                    </a>

                </article>

                <!-- Recommendation -->

                <article class="card">

                    <h2 class="card-title">
                        Supervisor Recommendations
                    </h2>

                    <p class="card-description">
                        View AI-assisted supervisor
                        recommendations based on your
                        selected research interests.
                    </p>

                    <a
                        class="button"
                        href="studentDiscovery.php"
                    >
                        View Recommendations
                    </a>

                </article>

            </section>

        </main>

    </div>

</body>
</html>
