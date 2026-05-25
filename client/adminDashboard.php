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
    "Administrator"
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
        Administrator Dashboard | SSAS
    </title>

    <style>
        <?php echo ssasAccountStyles(); ?>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8fc;
            color: #1d2b3a;
        }

        .app-shell {
            min-height: 100vh;
        }

        .topbar {
            height: 64px;
            background: #0b95c5;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            box-shadow: 0 4px 14px rgba(11, 79, 138, .16);
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .crest {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: grid;
            place-items: center;
            background: #ffffff;
            color: #0b4f8a;
            font-size: 15px;
            font-weight: 800;
        }

        .topbar-user {
            text-align: right;
            font-size: 13px;
            line-height: 1.4;
        }

        .topbar-user strong {
            display: block;
            font-size: 14px;
        }

        .content-shell {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #dde8f2;
            padding: 26px 18px;
        }

        .role-card {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            background: #eef6fc;
            margin-bottom: 20px;
        }

        .role-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #0b66d8;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .role-title {
            margin: 0;
            color: #0b3760;
            font-weight: 700;
            font-size: 15px;
        }

        .role-subtitle {
            margin: 2px 0 0;
            color: #6b7f91;
            font-size: 12px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #526a7f;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            transition: background .2s, color .2s, transform .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #eaf3ff;
            color: #0b66d8;
            transform: translateX(2px);
        }

        .main {
            flex: 1;
            padding: 28px 34px 40px;
        }

        .alerts {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-radius: 8px;
            padding: 16px;
            font-size: 13px;
        }

        .alert strong {
            display: block;
            margin-bottom: 4px;
        }

        .alert.danger {
            background: #ffd9d9;
            color: #9e1d1d;
            border-left: 4px solid #e33434;
        }

        .alert.warning {
            background: #fff4d6;
            color: #876000;
            border-left: 4px solid #f0a400;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.8fr .85fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .overview-card {
            background: #0d5be8;
            color: #ffffff;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 12px 24px rgba(13, 91, 232, .22);
        }

        .overview-card h1 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
        }

        .overview-card p {
            margin: 0;
            color: #dbe9ff;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin: 32px 0 26px;
        }

        .metric-label {
            color: #b9d2ff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 26px;
            font-weight: 800;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border-radius: 6px;
            background: #ffffff;
            color: #0d5be8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: transform .2s, box-shadow .2s;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .14);
        }

        .button.primary {
            background: #0b66ad;
            color: #ffffff;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, .08);
        }

        .panel h2 {
            margin: 0 0 8px;
            color: #0b3760;
            font-size: 20px;
        }

        .panel-subtitle {
            margin: 0 0 20px;
            color: #6b7f91;
            font-size: 13px;
        }

        .efficiency {
            display: grid;
            place-items: center;
            min-height: 100%;
        }

        .ring {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 10px solid #0d5be8;
            display: grid;
            place-items: center;
            margin: 10px auto 22px;
        }

        .ring-value {
            color: #0d5be8;
            font-size: 32px;
            font-weight: 800;
            text-align: center;
            line-height: 1;
        }

        .ring-value span {
            display: block;
            color: #6b7f91;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
            text-transform: uppercase;
        }

        .efficiency-details {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            color: #526a7f;
            width: 100%;
            font-size: 13px;
        }

        .efficiency-details strong {
            display: block;
            color: #0b3760;
            font-size: 18px;
            margin-top: 4px;
        }

        .distribution {
            margin-top: 10px;
        }

        .bar-row {
            margin-bottom: 22px;
        }

        .bar-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            color: #1d2b3a;
            font-size: 14px;
            font-weight: 700;
        }

        .bar-track {
            height: 10px;
            border-radius: 999px;
            background: #edf2f7;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: inherit;
            background: #2f6fed;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .action-card {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, .08);
            transition: transform .2s, box-shadow .2s;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 26px rgba(11, 79, 138, .14);
        }

        .action-card h3 {
            margin: 0 0 10px;
            color: #0b3760;
            font-size: 18px;
        }

        .action-card p {
            margin: 0 0 18px;
            color: #526a7f;
            line-height: 1.6;
            font-size: 14px;
        }

        @media (max-width: 1050px) {
            .dashboard-grid,
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 820px) {
            .topbar {
                height: auto;
                align-items: flex-start;
                gap: 12px;
                padding: 18px;
            }

            .content-shell {
                display: block;
            }

            .sidebar {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #dde8f2;
            }

            .main {
                padding: 22px;
            }

            .alerts,
            .metrics {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body>

    <div class="app-shell">

        <?php echo ssasTopbar("TAR UMT SSAS"); ?>

        <div class="content-shell">

            <aside class="sidebar">

                <div class="role-card">
                    <div class="role-icon">
                        A
                    </div>
                    <div>
                        <p class="role-title">
                            SSAS Admin
                        </p>
                        <p class="role-subtitle">
                            Management Portal
                        </p>
                    </div>
                </div>

                <a class="nav-link active" href="adminDashboard.php">
                    Dashboard
                </a>

                <a class="nav-link" href="supervisorsManagement.php">
                    Supervisors Management
                </a>

                <a class="nav-link" href="studentEligibility.php">
                    Students Eligibility
                </a>

                <a class="nav-link" href="quotaManagement.php">
                    Quota Management
                </a>

                <a class="nav-link" href="autoAllocation.php">
                    Allocations
                </a>

                <a class="nav-link" href="#">
                    Reports
                </a>

                <a class="nav-link" href="../server/application/logout.php">
                    Logout
                </a>

            </aside>

            <main class="main">

                <section class="alerts">

                    <article class="alert danger">
                        <strong>
                            !
                        </strong>
                        <div>
                            <strong>
                                Capacity Overload
                            </strong>
                            CS Faculty has exceeded available supervisor slots.
                        </div>
                    </article>

                    <article class="alert warning">
                        <strong>
                            !
                        </strong>
                        <div>
                            <strong>
                                Deadline Approaching
                            </strong>
                            Main allocation cycle closes in 48 hours.
                        </div>
                    </article>

                </section>

                <section class="dashboard-grid">

                    <article class="overview-card">

                        <h1>
                            System Overview
                        </h1>

                        <p>
                            Real-time supervision and allocation metrics for Computer Science.
                        </p>

                        <div class="metrics">

                            <div>
                                <div class="metric-label">
                                    Total Students
                                </div>
                                <div class="metric-value">
                                    4,820
                                </div>
                            </div>

                            <div>
                                <div class="metric-label">
                                    Assigned
                                </div>
                                <div class="metric-value">
                                    4,156 (86%)
                                </div>
                            </div>

                            <div>
                                <div class="metric-label">
                                    Pending
                                </div>
                                <div class="metric-value">
                                    664 (14%)
                                </div>
                            </div>

                        </div>

                        <a class="button" href="autoAllocation.php">
                            Manage Allocations
                        </a>

                    </article>

                    <article class="panel efficiency">

                        <h2>
                            Allocation Efficiency
                        </h2>

                        <div class="ring">
                            <div class="ring-value">
                                80%
                                <span>
                                    Efficiency
                                </span>
                            </div>
                        </div>

                        <div class="efficiency-details">
                            <div>
                                Avg. Response
                                <strong>
                                    2.4h
                                </strong>
                            </div>
                            <div>
                                Success Rate
                                <strong>
                                    94%
                                </strong>
                            </div>
                        </div>

                    </article>

                </section>

                <section class="panel distribution">

                    <h2>
                        Supervisor Expertise Distribution
                    </h2>

                    <p class="panel-subtitle">
                        Active workload distribution by specialized CS domain.
                    </p>

                    <div class="bar-row">
                        <div class="bar-header">
                            <span>
                                Artificial Intelligence / Machine Learning
                            </span>
                            <span>
                                94%
                            </span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: 94%;"></div>
                        </div>
                    </div>

                    <div class="bar-row">
                        <div class="bar-header">
                            <span>
                                Software Engineering
                            </span>
                            <span>
                                82%
                            </span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: 82%;"></div>
                        </div>
                    </div>

                    <div class="bar-row">
                        <div class="bar-header">
                            <span>
                                Data Science
                            </span>
                            <span>
                                76%
                            </span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: 76%;"></div>
                        </div>
                    </div>

                    <div class="bar-row">
                        <div class="bar-header">
                            <span>
                                Cybersecurity
                            </span>
                            <span>
                                88%
                            </span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: 88%;"></div>
                        </div>
                    </div>

                </section>

                <section class="quick-actions">

                    <article class="action-card">
                        <h3>
                            Supervisor Accounts
                        </h3>
                        <p>
                            Create supervisor accounts and maintain supervisor access records.
                        </p>
                        <a class="button primary" href="supervisorsManagement.php">
                            Manage Supervisors
                        </a>
                    </article>

                    <article class="action-card">
                        <h3>
                            Quota Management
                        </h3>
                        <p>
                            Monitor quota tiers and supervisor capacity before allocation.
                        </p>
                        <a class="button primary" href="quotaManagement.php">
                            Manage Quotas
                        </a>
                    </article>

                    <article class="action-card">
                        <h3>
                            Reports
                        </h3>
                        <p>
                            Review allocation progress, pending cases, and operational status.
                        </p>
                        <a class="button primary" href="#">
                            View Reports
                        </a>
                    </article>

                </section>

            </main>

        </div>

    </div>

</body>
</html>
