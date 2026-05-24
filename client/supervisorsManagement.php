<?php

require_once "../server/application/SessionManager.php";
require_once "../server/business/SupervisorManagementService.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication and RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Administrator"
);

$supervisorManagementService =
    new SupervisorManagementService();

$searchName =
    $_GET["searchName"] ?? "";

$selectedProgramme =
    $_GET["programme"] ?? "";

$supervisors =
    $supervisorManagementService
    ->getSupervisorDirectory(
        $_GET
    );

$quotaOptions =
    $supervisorManagementService
    ->getQuotaOptions();

$programmeOptions =
    $supervisorManagementService
    ->getProgrammeOptions();

$totalSupervisors =
    count($supervisors);

$fullSupervisors =
    0;

$overallLoad =
    0;

foreach ($supervisors as $supervisor) {

    if ($supervisor["availabilityStatus"] === "Full") {

        $fullSupervisors++;
    }

    $overallLoad +=
        (int) $supervisor["loadPercentage"];
}

$averageLoad =
    $totalSupervisors > 0
    ? round($overallLoad / $totalSupervisors)
    : 0;

function e($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function selected($left, $right) {

    return (string) $left === (string) $right
        ? "selected"
        : "";
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
        Supervisor Management | SSAS
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

        .hero-grid {
            display: grid;
            grid-template-columns: 1.7fr .8fr;
            gap: 22px;
            margin-bottom: 24px;
        }

        .hero-card {
            background: #0d5be8;
            color: #ffffff;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 12px 24px rgba(13, 91, 232, .22);
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

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 28px;
        }

        .metric {
            background: rgba(255, 255, 255, .13);
            border-radius: 8px;
            padding: 14px;
        }

        .metric-label {
            color: #b9d2ff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 800;
        }

        .status-card,
        .panel {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, .08);
        }

        .status-card h2,
        .panel h2 {
            margin: 0 0 8px;
            color: #0b3760;
            font-size: 20px;
        }

        .status-ring {
            width: 138px;
            height: 138px;
            border-radius: 50%;
            border: 10px solid #0d5be8;
            display: grid;
            place-items: center;
            margin: 16px auto;
        }

        .status-ring strong {
            color: #0d5be8;
            font-size: 30px;
        }

        .status-caption {
            margin: 0;
            color: #6b7f91;
            font-size: 13px;
            text-align: center;
        }

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

        .filters {
            display: grid;
            grid-template-columns: 1.3fr 1fr auto auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #35546d;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            height: 40px;
            border: 1px solid #c6d8e8;
            border-radius: 6px;
            padding: 0 10px;
            background: #ffffff;
            color: #1d2b3a;
            font-size: 14px;
        }

        input:focus,
        select:focus {
            outline: 2px solid #9dccf1;
            border-color: #2179b8;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border: 0;
            border-radius: 6px;
            padding: 0 16px;
            background: #0b66ad;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s, box-shadow .2s;
        }

        .button:hover {
            background: #084f88;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(11, 102, 173, .2);
        }

        .button.secondary {
            background: #e8f2fb;
            color: #0b4f8a;
        }

        .directory {
            display: grid;
            gap: 14px;
        }

        .supervisor-row {
            display: grid;
            grid-template-columns: 1.1fr .9fr .8fr 1.2fr auto;
            gap: 14px;
            align-items: center;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 16px;
            background: #ffffff;
            transition: transform .2s, box-shadow .2s;
        }

        .supervisor-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(11, 79, 138, .1);
        }

        .name {
            margin: 0 0 4px;
            color: #0b3760;
            font-weight: 800;
        }

        .meta {
            margin: 0;
            color: #6b7f91;
            font-size: 13px;
        }

        .load-label {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: #35546d;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .bar-track {
            height: 8px;
            background: #edf2f7;
            border-radius: 999px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: inherit;
            background: #2f6fed;
        }

        .bar-fill.full {
            background: #d93c3c;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .badge.available {
            background: #e5f6ed;
            color: #177345;
        }

        .badge.full {
            background: #fdeaea;
            color: #a52d2d;
        }

        .classification-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        .empty {
            border: 1px dashed #aac7df;
            border-radius: 8px;
            padding: 24px;
            color: #526a7f;
            text-align: center;
        }

        @media (max-width: 1180px) {
            .hero-grid,
            .supervisor-row {
                grid-template-columns: 1fr;
            }

            .classification-form {
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

            .hero-metrics,
            .filters {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body>

    <header class="topbar">

        <div class="topbar-brand">
            <div class="crest">T</div>
            <span>TAR UMT SSAS</span>
        </div>

        <div class="topbar-user">
            <strong><?php echo e($_SESSION["fullName"]); ?></strong>
            Administrator
        </div>

    </header>

    <div class="content-shell">

        <aside class="sidebar">

            <div class="role-card">
                <div class="role-icon">A</div>
                <div>
                    <p class="role-title">SSAS Admin</p>
                    <p class="role-subtitle">Management Portal</p>
                </div>
            </div>

            <a class="nav-link" href="adminDashboard.php">Dashboard</a>
            <a class="nav-link active" href="supervisorsManagement.php">Supervisors Management</a>
            <a class="nav-link" href="#">Students Eligibility</a>
            <a class="nav-link" href="quotaManagement.php">Quota Management</a>
            <a class="nav-link" href="#">Allocations</a>
            <a class="nav-link" href="#">Reports</a>
            <a class="nav-link" href="../server/application/logout.php">Logout</a>

        </aside>

        <main class="main">

            <?php echo statusMessage(); ?>

            <section class="hero-grid">

                <article class="hero-card">
                    <h1>Supervisor Classification</h1>
                    <p>
                        Audit and manage academic classification levels for all supervisors.
                    </p>

                    <div class="hero-metrics">
                        <div class="metric">
                            <div class="metric-label">Total Active</div>
                            <div class="metric-value"><?php echo e($totalSupervisors); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Average Load</div>
                            <div class="metric-value"><?php echo e($averageLoad); ?>%</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Full Capacity</div>
                            <div class="metric-value"><?php echo e($fullSupervisors); ?></div>
                        </div>
                    </div>
                </article>

                <article class="status-card">
                    <h2>Status Summary</h2>
                    <div class="status-ring">
                        <strong><?php echo e($averageLoad); ?>%</strong>
                    </div>
                    <p class="status-caption">
                        Overall allocation load across filtered supervisors.
                    </p>
                </article>

            </section>

            <section class="panel">

                <form class="filters" method="GET" action="supervisorsManagement.php">
                    <div>
                        <label for="searchName">Search Staff</label>
                        <input
                            type="text"
                            id="searchName"
                            name="searchName"
                            value="<?php echo e($searchName); ?>"
                            placeholder="Search by name or staff ID"
                        >
                    </div>

                    <div>
                        <label for="programme">Programme</label>
                        <select id="programme" name="programme">
                            <option value="">All Programmes</option>
                            <?php foreach ($programmeOptions as $programme): ?>
                                <option
                                    value="<?php echo e($programme["programme"]); ?>"
                                    <?php echo selected($selectedProgramme, $programme["programme"]); ?>
                                >
                                    <?php echo e($programme["programme"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="button" type="submit">Apply Filters</button>
                    <a class="button secondary" href="supervisorsManagement.php">Reset</a>
                </form>

                <h2>Supervisor Directory</h2>

                <?php if (empty($supervisors)): ?>

                    <div class="empty">
                        No supervisors found for the selected criteria.
                    </div>

                <?php else: ?>

                    <div class="directory">

                        <?php foreach ($supervisors as $supervisor): ?>
                            <?php
                                $isFull =
                                    $supervisor["availabilityStatus"] === "Full";
                            ?>

                            <article class="supervisor-row">

                                <div>
                                    <p class="name"><?php echo e($supervisor["fullName"]); ?></p>
                                    <p class="meta">
                                        <?php echo e($supervisor["userID"]); ?>
                                        ·
                                        <?php echo e($supervisor["universityEmail"]); ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="meta">Programme</p>
                                    <p class="name"><?php echo e($supervisor["programme"]); ?></p>
                                </div>

                                <div>
                                    <span class="badge <?php echo $isFull ? "full" : "available"; ?>">
                                        <?php echo e($supervisor["availabilityStatus"]); ?>
                                    </span>
                                </div>

                                <div>
                                    <div class="load-label">
                                        <span>
                                            <?php echo e($supervisor["quotaText"]); ?>
                                        </span>
                                        <span>
                                            <?php echo e($supervisor["loadPercentage"]); ?>%
                                        </span>
                                    </div>
                                    <div class="bar-track">
                                        <div
                                            class="bar-fill <?php echo $isFull ? "full" : ""; ?>"
                                            style="width: <?php echo e($supervisor["loadPercentage"]); ?>%;"
                                        ></div>
                                    </div>
                                </div>

                                <form
                                    class="classification-form"
                                    action="../server/application/updateSupervisorClassification.php"
                                    method="POST"
                                >
                                    <input
                                        type="hidden"
                                        name="supervisorID"
                                        value="<?php echo e($supervisor["userID"]); ?>"
                                    >

                                    <div>
                                        <label for="employmentCategory-<?php echo e($supervisor["userID"]); ?>">
                                            Classification
                                        </label>
                                        <select
                                            id="employmentCategory-<?php echo e($supervisor["userID"]); ?>"
                                            name="employmentCategory"
                                            required
                                        >
                                            <option value="Full-Time Lecturer" <?php echo selected($supervisor["employmentCategory"], "Full-Time Lecturer"); ?>>
                                                Full-Time Lecturer
                                            </option>
                                            <option value="Part-Time Lecturer" <?php echo selected($supervisor["employmentCategory"], "Part-Time Lecturer"); ?>>
                                                Part-Time Lecturer
                                            </option>
                                            <option value="Dean" <?php echo selected($supervisor["employmentCategory"], "Dean"); ?>>
                                                Dean
                                            </option>
                                            <option value="Deputy Dean" <?php echo selected($supervisor["employmentCategory"], "Deputy Dean"); ?>>
                                                Deputy Dean
                                            </option>
                                            <option value="Academic Director" <?php echo selected($supervisor["employmentCategory"], "Academic Director"); ?>>
                                                Academic Director
                                            </option>
                                            <option value="Programme Leader" <?php echo selected($supervisor["employmentCategory"], "Programme Leader"); ?>>
                                                Programme Leader
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="quotaID-<?php echo e($supervisor["userID"]); ?>">
                                            Quota Tier
                                        </label>
                                        <select
                                            id="quotaID-<?php echo e($supervisor["userID"]); ?>"
                                            name="quotaID"
                                            required
                                        >
                                            <?php foreach ($quotaOptions as $quota): ?>
                                                <option
                                                    value="<?php echo e($quota["quotaID"]); ?>"
                                                    <?php echo selected($supervisor["quotaID"], $quota["quotaID"]); ?>
                                                >
                                                    <?php echo e($quota["quotaTierName"]); ?>
                                                    (<?php echo e($quota["maxSuperviseesAllowed"]); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <button class="button" type="submit">Save</button>

                                </form>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </main>

    </div>

</body>
</html>
