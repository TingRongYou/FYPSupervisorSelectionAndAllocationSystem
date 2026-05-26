<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/SupervisorDashboardService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication and RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Supervisor"
);

$dashboardService =
    new SupervisorDashboardService();

$dashboard =
    $dashboardService
    ->getDashboardData(
        $_SESSION["userID"]
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
        Supervisor Dashboard | SSAS
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
        }

        .crest {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: grid;
            place-items: center;
            background: #ffffff;
            color: #0b4f8a;
            font-weight: 800;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: right;
            font-size: 12px;
            line-height: 1.4;
        }

        .topbar-user strong {
            display: block;
            font-size: 14px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #ffffff;
            color: #0b4f8a;
            display: grid;
            place-items: center;
            font-weight: 800;
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
            justify-content: space-between;
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

        .alert {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            background: #ffd9d2;
            color: #7f2a1d;
            border-left: 4px solid #eb5b32;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert strong {
            color: #5b1d14;
        }

        .hero-card {
            background: #0d5be8;
            color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 12px 24px rgba(13, 91, 232, .22);
            margin-bottom: 28px;
        }

        .hero-card h1 {
            margin: 0 0 8px;
            font-size: 30px;
            font-weight: 700;
        }

        .hero-card p {
            margin: 0;
            color: #dbe9ff;
            line-height: 1.5;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin: 28px 0 24px;
        }

        .metric {
            background: rgba(255, 255, 255, .13);
            border-radius: 8px;
            padding: 16px;
        }

        .metric-label {
            color: #b9d2ff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 7px;
        }

        .metric-value {
            font-size: 26px;
            font-weight: 800;
        }

        .quota-line {
            display: inline-block;
            width: 42%;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .25);
            overflow: hidden;
            vertical-align: middle;
            margin-left: 8px;
        }

        .quota-line span {
            display: block;
            height: 100%;
            background: #ffffff;
            border-radius: inherit;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            border: 0;
            border-radius: 6px;
            padding: 0 18px;
            background: #ffffff;
            color: #0d5be8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            transition: transform .2s, box-shadow .2s;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .14);
        }

        .applications {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, .08);
            overflow: hidden;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 24px;
            border-bottom: 1px solid #e3edf6;
        }

        .section-header h2 {
            margin: 0;
            color: #0b3760;
            font-size: 20px;
        }

        .filter-chip {
            background: #eef2f6;
            color: #526a7f;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 16px 24px;
            border-bottom: 1px solid #e3edf6;
            text-align: left;
            font-size: 14px;
        }

        th {
            color: #526a7f;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .student-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #0b66d8;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 12px;
        }

        .student-name {
            color: #0b3760;
            font-weight: 800;
        }

        .muted {
            color: #6b7f91;
            font-size: 12px;
            margin-top: 3px;
        }

        .focus-tag {
            display: inline-flex;
            max-width: 220px;
            border-radius: 4px;
            padding: 5px 8px;
            background: #e8f2fb;
            color: #0b4f8a;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status {
            display: inline-flex;
            min-width: 92px;
            justify-content: center;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .status.pending {
            background: #fff2bf;
            color: #a96a00;
        }

        .status.accepted {
            background: #dff8e6;
            color: #14733e;
        }

        .status.rejected {
            background: #fdeaea;
            color: #a52d2d;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
            min-height: 42px;
            border-radius: 8px;
            background: #0d5be8;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }

        .empty-state {
            padding: 28px;
            color: #526a7f;
            text-align: center;
        }

        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 16px 24px;
            color: #6b7f91;
            font-size: 13px;
        }

        @media (max-width: 980px) {
            .content-shell {
                display: block;
            }

            .sidebar {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #dde8f2;
            }

            .metric-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .topbar {
                height: auto;
                align-items: flex-start;
                padding: 18px;
            }

            .main {
                padding: 22px;
            }

            .hero-card {
                padding: 22px;
            }

            .section-header,
            .table-footer {
                display: block;
            }

            .filter-chip {
                display: inline-flex;
                margin-top: 12px;
            }
        }

        .sidebar {
            width: 280px;
            flex: 0 0 280px;
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
            font-size: 15px;
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

        .nav-link,
        .nav-parent {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-start;
            color: #526a7f;
            text-decoration: none;
            padding: 11px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            background: #f1f5f9;
            border: 0;
            width: 100%;
            min-height: 38px;
            cursor: pointer;
            transition: background .2s, color .2s, transform .2s;
            white-space: nowrap;
        }

        .nav-link:hover,
        .nav-link.active,
        .nav-parent.active {
            background: #eaf3ff;
            color: #0b66d8;
            transform: translateX(2px);
        }

        .nav-text {
            flex: 1;
        }

        .nav-icon {
            display: none;
        }

        .nav-chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            color: #7d96b4;
            font-size: 13px;
            font-weight: 700;
        }

        .nav-link:hover .nav-chevron,
        .nav-link.active .nav-chevron,
        .nav-parent.active .nav-chevron {
            color: #0b66d8;
        }

        .subnav {
            margin: -3px 0 8px 13px;
            padding: 2px 0 2px 22px;
            border-left: 1px solid #cbd8e6;
        }

        .subnav a {
            position: relative;
            display: block;
            color: #6b7f91;
            text-decoration: none;
            font-size: 12px;
            padding: 6px 10px;
            line-height: 1.25;
            border-radius: 6px;
            white-space: nowrap;
        }

        .subnav a:before {
            content: "";
            position: absolute;
            left: -22px;
            top: 50%;
            width: 16px;
            height: 1px;
            background: #cbd8e6;
        }

        .subnav a:hover,
        .subnav a.active {
            background: #f1f7ff;
            color: #0d5be8;
            font-weight: 800;
        }

    </style>
</head>

<body>

    <?php echo supervisorTopbar(); ?>

    <div class="content-shell">

        <?php echo supervisorSidebar("dashboard"); ?>

        <main class="main">

            <?php if ($dashboard["deadlineAlert"]["show"]): ?>
                <section class="alert">
                    <div>
                        <strong>!</strong>
                        <?php echo e($dashboard["deadlineAlert"]["message"]); ?>
                    </div>
                    <span>x</span>
                </section>
            <?php endif; ?>

            <section class="hero-card">
                <h1>Welcome back, <?php echo e($_SESSION["fullName"]); ?>.</h1>
                <p>
                    You have <?php echo e($dashboard["pendingRequests"]); ?> new applications requiring your immediate attention.
                    <br>
                    Your current supervision load is healthy.
                </p>

                <div class="metric-grid">
                    <div class="metric">
                        <div class="metric-label">Incoming Requests</div>
                        <div class="metric-value"><?php echo e($dashboard["pendingRequests"]); ?></div>
                    </div>

                    <div class="metric">
                        <div class="metric-label">Active Supervisees</div>
                        <div class="metric-value">
                            <?php echo e($dashboard["activeSupervisees"]); ?>
                            <span style="font-size: 15px; color: #b9d2ff;">/ <?php echo e($dashboard["maxSuperviseesAllowed"]); ?></span>
                        </div>
                    </div>

                    <div class="metric">
                        <div class="metric-label">Quota Usage</div>
                        <div class="metric-value">
                            <?php echo e($dashboard["quotaUsage"]); ?>%
                            <span class="quota-line">
                                <span style="width: <?php echo e($dashboard["quotaUsage"]); ?>%;"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <a class="button" href="supervisorIncomingRequests.php">
                    View All Requests
                </a>
            </section>

            <section class="applications">
                <div class="section-header">
                    <h2>Recent Student Applications</h2>
                    <span class="filter-chip">Filter</span>
                </div>

                <?php if (empty($dashboard["recentApplications"])): ?>
                    <div class="empty-state">
                        No student applications have been submitted to you yet.
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Programme</th>
                                    <th>Research Focus</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard["recentApplications"] as $application): ?>
                                    <tr>
                                        <td>
                                            <div class="student-cell">
                                                <div class="student-avatar">
                                                    <?php echo e(supervisorInitials($application["fullName"])); ?>
                                                </div>
                                                <div>
                                                    <div class="student-name">
                                                        <?php echo e($application["fullName"]); ?>
                                                    </div>
                                                    <div class="muted">
                                                        <?php echo e($application["studentID"]); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo e($application["programme"]); ?></td>
                                        <td>
                                            <span class="focus-tag">
                                                <?php echo e($application["researchFocus"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status <?php echo e($application["statusClass"]); ?>">
                                                <?php echo e($application["decisionStatus"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a class="action-button" href="supervisorRequestDecision.php?requestID=<?php echo e($application["requestID"]); ?>">
                                                <?php echo e($application["actionText"]); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <span>
                            Showing <?php echo e(count($dashboard["recentApplications"])); ?> recent applications
                        </span>
                        <span>&lt; &gt;</span>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

</body>
</html>


