<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/AllocationEngine.php";
require_once "../../server/business/services/AdminReportFacade.php";
require_once "../../server/business/services/AllocationWindowService.php";
require_once __DIR__ . "/../shared/accountLayout.php";

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

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));
}

$allocationEngine =
    new AllocationEngine();

$adminReportFacade =
    new AdminReportFacade();

$allocationWindowService =
    new AllocationWindowService();

$allocationSummary =
    $allocationEngine
    ->getAllocationDashboard();

$capacitySummary =
    $adminReportFacade
    ->getAllocationSummary();

$allocationWindow =
    $allocationWindowService
    ->getWindow();

$reviewPeriod =
    $allocationWindowService
    ->getReviewPeriod();

$totalStudents =
    (int) ($allocationSummary["eligibleStudents"] ?? 0);

$assignedStudents =
    (int) ($allocationSummary["allocatedStudents"] ?? 0);

$pendingStudents =
    (int) ($allocationSummary["unassignedStudents"] ?? 0);

$allocationRate =
    (float) ($allocationSummary["allocationRate"] ?? 0);

$pendingRequests =
    (int) ($allocationSummary["pendingRequests"] ?? 0);

$allocationWindowNotice =
    $allocationWindow["statusText"] ?? "";

$allocationWindowAlertClass =
    "warning";

if (
    ($allocationWindow["status"] ?? "") === "closed" &&
    $pendingStudents === 0
) {

    $allocationWindowNotice =
        "Final allocation date has passed. All eligible students have been allocated; no students are pending auto-allocation.";

    $allocationWindowAlertClass =
        "success";
}

$supervisorsAtCapacity =
    (int) ($capacitySummary["atCapacity"] ?? 0);

$totalCapacity =
    (int) ($capacitySummary["totalCapacity"] ?? 0);

$allocatedTotal =
    (int) ($capacitySummary["allocatedTotal"] ?? 0);

$programmeDistribution =
    [];

foreach ($capacitySummary["supervisors"] ?? [] as $supervisor) {

    $programme =
        trim((string) ($supervisor["programme"] ?? "Unspecified"));

    if (!isset($programmeDistribution[$programme])) {

        $programmeDistribution[$programme] = [
            "allocated" => 0,
            "capacity" => 0
        ];
    }

    $programmeDistribution[$programme]["allocated"] +=
        (int) ($supervisor["currentTotal"] ?? 0);

    $programmeDistribution[$programme]["capacity"] +=
        (int) ($supervisor["maxSuperviseesAllowed"] ?? 0);
}

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

function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class =
        $_GET["status"] === "success"
        ? "success"
        : "error";

    return
        "<div class=\"message {$class}\">"
        . e($_GET["message"])
        . "</div>";
}

function dateTimeInputValue($value) {

    if (empty($value)) {

        return "";
    }

    return date("Y-m-d\TH:i", strtotime($value));
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
            font-size: 15px;
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
            width: 280px;
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
            font-size: 14px;
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
            white-space: nowrap;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #eaf3ff;
            color: #0b66d8;
            transform: translateX(2px);
        }

        .sidebar .role-card { min-height: 62px; }
        .sidebar .role-icon { width: 38px; height: 38px; font-size: 15px; font-weight: 800; }
        .sidebar .role-title { font-size: 14px; font-weight: 800; }
        .sidebar .role-subtitle { font-size: 12px; font-weight: 400; text-transform: none; letter-spacing: 0; }
        .sidebar .nav-link,
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            min-height: 40px;
            padding: 12px 14px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .nav-link.has-submenu { width: 100%; border: 0; background: #f1f5f9; font-family: inherit; cursor: pointer; justify-content: space-between; text-align: left; }
        .sidebar .nav-link { background: #f1f5f9; color: #526a7f; font-weight: 600; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #eaf3ff; color: #0b66d8; }
        .submenu-caret { color: #7d96b4; font-size: 14px; font-weight: 900; line-height: 1; transition: color .2s, transform .2s; }
        .nav-link.has-submenu[aria-expanded="true"] .submenu-caret { color: #0b66d8; transform: rotate(180deg); }
        .report-tree { display: none; position: relative; margin: -4px 0 8px 16px; padding-left: 14px; border-left: 1px solid #c9d8e8; }
        .report-tree.open { display: block; }
        .report-tree:after { content: ""; position: absolute; left: -1px; right: 0; bottom: 0; height: 1px; background: #c9d8e8; }
        .report-child { position: relative; display: block; padding: 9px 10px; color: #526a7f; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 6px; }
        .report-child:before { content: ""; position: absolute; left: -14px; top: 50%; width: 14px; height: 1px; background: #c9d8e8; }
        .report-child:hover { color: #0d5be8; background: #f0f6ff; }

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
            font-size: 15px;
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

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .message {
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-weight: 800;
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin: 32px 0 26px;
        }

        .metric-label {
            color: #b9d2ff;
            font-size: 14px;
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
            font-size: 15px;
        }

        .window-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .window-form label {
            display: block;
            color: #6b7f91;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 6px;
        }

        .window-form input {
            width: 100%;
            height: 42px;
            border: 1px solid #dbe6f0;
            border-radius: 7px;
            padding: 0 12px;
            color: #1d2b3a;
        }

        .window-form button {
            grid-column: 1 / -1;
        }

        .status-pill {
            display: inline-flex;
            width: max-content;
            align-items: center;
            min-height: 26px;
            border-radius: 999px;
            padding: 0 12px;
            background: #eaf3ff;
            color: #0d5be8;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 12px;
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
            font-size: 13px;
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
            font-size: 15px;
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
            .metrics,
            .window-form {
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

                <a class="nav-link" href="adminSupervisorReviews.php">
                    Supervisor Reviews Audit
                </a>

                <button class="nav-link has-submenu" type="button" aria-expanded="false" aria-controls="admin-report-tree" onclick="toggleAdminReports(this)">
                    <span>Reports</span>
                    <span class="submenu-caret" aria-hidden="true">v</span>
                </button>
                <div class="report-tree" id="admin-report-tree">
                    <a class="report-child" href="adminCohortOverview.php">Cohort Overview</a>
                    <a class="report-child" href="adminAllocationSummary.php">Allocation Summary</a>
                </div>
                <script>
                    function toggleAdminReports(button) {
                        const reportTree = document.getElementById("admin-report-tree");
                        const isOpen = button.getAttribute("aria-expanded") === "true";
                        button.setAttribute("aria-expanded", isOpen ? "false" : "true");
                        reportTree.classList.toggle("open", !isOpen);
                    }
                </script>

            </aside>

            <main class="main">

                <?php echo statusMessage(); ?>

                <section class="alerts">

                    <?php if ($supervisorsAtCapacity > 0): ?>
                    <article class="alert danger">
                        <strong>
                            !
                        </strong>
                        <div>
                            <strong>
                                Capacity Overload
                            </strong>
                            <?php echo e($supervisorsAtCapacity); ?> supervisor(s) have reached full quota.
                        </div>
                    </article>
                    <?php endif; ?>

                    <article class="alert <?php echo e($allocationWindowAlertClass); ?>">
                        <?php if ($allocationWindowAlertClass !== "success"): ?>
                            <strong>
                                !
                            </strong>
                        <?php endif; ?>
                        <div>
                            <strong>
                                Allocation Window
                            </strong>
                            <?php echo e($allocationWindowNotice); ?>
                        </div>
                    </article>

                </section>

                <section class="dashboard-grid">

                    <article class="overview-card">

                        <h1>
                            System Overview
                        </h1>

                        <p>
                            Real-time supervision and allocation metrics from live SSAS records.
                        </p>

                        <div class="metrics">

                            <div>
                                <div class="metric-label">
                                    Total Students
                                </div>
                                <div class="metric-value">
                                    <?php echo e(number_format($totalStudents)); ?>
                                </div>
                            </div>

                            <div>
                                <div class="metric-label">
                                    Assigned
                                </div>
                                <div class="metric-value">
                                    <?php echo e(number_format($assignedStudents)); ?> (<?php echo e($allocationRate); ?>%)
                                </div>
                            </div>

                            <div>
                                <div class="metric-label">
                                    Pending Requests
                                </div>
                                <div class="metric-value">
                                    <?php echo e(number_format($pendingRequests)); ?>
                                </div>
                            </div>

                            <div>
                                <div class="metric-label">
                                    Unassigned
                                </div>
                                <div class="metric-value">
                                    <?php echo e(number_format($pendingStudents)); ?>
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
                                <?php echo e($allocationRate); ?>%
                                <span>
                                    Allocated
                                </span>
                            </div>
                        </div>

                        <div class="efficiency-details">
                            <div>
                                Pending Requests
                                <strong>
                                    <?php echo e(number_format($pendingRequests)); ?>
                                </strong>
                            </div>
                            <div>
                                Capacity Used
                                <strong>
                                    <?php echo e($totalCapacity > 0 ? round(($allocatedTotal / $totalCapacity) * 100, 1) : 0); ?>%
                                </strong>
                            </div>
                        </div>

                    </article>

                </section>

                <section class="panel distribution">

                    <h2>
                        Programme Capacity Distribution
                    </h2>

                    <p class="panel-subtitle">
                        Live workload distribution by supervisor programme.
                    </p>

                    <?php if (empty($programmeDistribution)): ?>
                        <p class="panel-subtitle">No supervisor capacity records are available.</p>
                    <?php else: ?>
                        <?php foreach ($programmeDistribution as $programme => $distribution): ?>
                            <?php
                                $capacity = max(0, (int) $distribution["capacity"]);
                                $allocated = max(0, (int) $distribution["allocated"]);
                                $percentage = $capacity > 0 ? round(($allocated / $capacity) * 100, 1) : 0;
                            ?>
                            <div class="bar-row">
                                <div class="bar-header">
                                    <span><?php echo e($programme); ?></span>
                                    <span><?php echo e($allocated); ?>/<?php echo e($capacity); ?> (<?php echo e($percentage); ?>%)</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo e(min(100, $percentage)); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </section>

                <section class="panel" style="margin-top:24px;">
                    <span class="status-pill"><?php echo e(str_replace("_", " ", $allocationWindow["status"])); ?></span>
                    <h2>Allocation & Review Timeline</h2>
                    <p class="panel-subtitle">
                        Students can submit supervisor requests from the initial allocation date until the final allocation date. The review period must start after the final allocation date.
                    </p>
                    <form class="window-form" id="timelineForm" action="../../server/application/admin/updateAllocationWindow.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <div>
                            <label for="initialAllocationDate">Initial Allocation Date</label>
                            <input type="datetime-local" id="initialAllocationDate" name="initialAllocationDate" value="<?php echo e(dateTimeInputValue($allocationWindow["initialAllocationDate"] ?? "")); ?>" required>
                        </div>
                        <div>
                            <label for="finalAllocationDate">Final Allocation Date</label>
                            <input type="datetime-local" id="finalAllocationDate" name="finalAllocationDate" value="<?php echo e(dateTimeInputValue($allocationWindow["finalAllocationDate"] ?? "")); ?>" required>
                        </div>
                        <div>
                            <label for="reviewStartDate">Review Period Start</label>
                            <input type="datetime-local" id="reviewStartDate" name="reviewStartDate" value="<?php echo e(dateTimeInputValue($reviewPeriod["startTimestamp"] ?? "")); ?>" required>
                        </div>
                        <div>
                            <label for="reviewEndDate">Review Period End</label>
                            <input type="datetime-local" id="reviewEndDate" name="reviewEndDate" value="<?php echo e(dateTimeInputValue($reviewPeriod["endTimestamp"] ?? "")); ?>" required>
                        </div>
                        <button class="button primary" type="submit">Save Timeline Dates</button>
                    </form>
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
                        <a class="button primary" href="adminCohortOverview.php">
                            View Reports
                        </a>
                    </article>

                </section>

            </main>

        </div>

    </div>

<script>
    const timelineForm = document.getElementById("timelineForm");
    const initialAllocationDate = document.getElementById("initialAllocationDate");
    const finalAllocationDate = document.getElementById("finalAllocationDate");
    const reviewStartDate = document.getElementById("reviewStartDate");
    const reviewEndDate = document.getElementById("reviewEndDate");

    function syncTimelineMinimums() {
        if (finalAllocationDate && initialAllocationDate.value) {
            finalAllocationDate.min = initialAllocationDate.value;
        }

        if (reviewStartDate && finalAllocationDate.value) {
            reviewStartDate.min = finalAllocationDate.value;
        }

        if (reviewEndDate && reviewStartDate.value) {
            reviewEndDate.min = reviewStartDate.value;
        }
    }

    [initialAllocationDate, finalAllocationDate, reviewStartDate].forEach(function(input) {
        if (input) {
            input.addEventListener("change", syncTimelineMinimums);
        }
    });

    if (timelineForm) {
        timelineForm.addEventListener("submit", function(event) {
            const initialTime = new Date(initialAllocationDate.value).getTime();
            const finalTime = new Date(finalAllocationDate.value).getTime();
            const reviewStartTime = new Date(reviewStartDate.value).getTime();
            const reviewEndTime = new Date(reviewEndDate.value).getTime();

            if (
                !(initialTime < finalTime) ||
                !(finalTime < reviewStartTime) ||
                !(reviewStartTime < reviewEndTime)
            ) {
                event.preventDefault();
                alert("Timeline Error - Dates must follow this order: Initial Allocation, Final Allocation, Review Period Start, Review Period End.");
            }
        });
    }

    syncTimelineMinimums();
</script>
</body>
</html>
