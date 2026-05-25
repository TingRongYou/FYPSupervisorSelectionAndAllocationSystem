<?php

require_once "../server/application/SessionManager.php";
require_once "../server/business/QuotaManager.php";
require_once __DIR__ . "/accountLayout.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication and RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Administrator"
);

$quotaManager =
    new QuotaManager();

$searchName =
    $_GET["searchName"] ?? "";

$selectedProgramme =
    $_GET["programme"] ?? "";

$supervisors =
    $quotaManager
    ->getQuotaDashboard(
        $_GET
    );

$quotaOptions =
    $quotaManager
    ->getQuotaOptions();

$programmeOptions =
    $quotaManager
    ->getProgrammeOptions();

$totalCapacity =
    0;

$totalAllocated =
    0;

$overloadedSupervisors =
    0;

foreach ($supervisors as $supervisor) {

    $totalCapacity +=
        (int) $supervisor["maxSuperviseesAllowed"];

    $totalAllocated +=
        (int) $supervisor["currentSupervisees"];

    if ($supervisor["quotaStatus"] === "Overloaded") {

        $overloadedSupervisors++;
    }
}

$utilizationRate =
    $totalCapacity > 0
    ? round(($totalAllocated / $totalCapacity) * 100)
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
        Quota Management | SSAS
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

        .panel,
        .status-card {
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

        .summary-list {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #526a7f;
            font-size: 14px;
        }

        .summary-item strong {
            color: #0b3760;
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
            grid-template-columns: 1.2fr 1fr auto auto;
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

        .quota-row {
            display: grid;
            grid-template-columns: 1.1fr .9fr .8fr .9fr auto;
            gap: 14px;
            align-items: center;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 16px;
            background: #ffffff;
            transition: transform .2s, box-shadow .2s;
        }

        .quota-row:hover {
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

        .bar-label {
            display: flex;
            justify-content: space-between;
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

        .badge.valid {
            background: #e5f6ed;
            color: #177345;
        }

        .badge.full {
            background: #fff4d6;
            color: #876000;
        }

        .badge.overloaded {
            background: #fdeaea;
            color: #a52d2d;
        }

        .quota-form {
            display: grid;
            grid-template-columns: 1fr auto;
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
            .quota-row {
                grid-template-columns: 1fr;
            }

            .quota-form {
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

    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

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
            <a class="nav-link" href="supervisorsManagement.php">Supervisors Management</a>
            <a class="nav-link" href="studentEligibility.php">Students Eligibility</a>
            <a class="nav-link active" href="quotaManagement.php">Quota Management</a>
            <a class="nav-link" href="autoAllocation.php">Allocations</a>
            <a class="nav-link" href="#">Reports</a>
            <a class="nav-link" href="../server/application/logout.php">Logout</a>
        </aside>

        <main class="main">

            <?php echo statusMessage(); ?>

            <section class="hero-grid">
                <article class="hero-card">
                    <h1>Supervisor Quota Management</h1>
                    <p>
                        Oversee and adjust supervisory capacities while maintaining academic supervision standards.
                    </p>

                    <div class="hero-metrics">
                        <div class="metric">
                            <div class="metric-label">Total Capacity</div>
                            <div class="metric-value"><?php echo e($totalCapacity); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Allocated</div>
                            <div class="metric-value"><?php echo e($totalAllocated); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Utilization</div>
                            <div class="metric-value"><?php echo e($utilizationRate); ?>%</div>
                        </div>
                    </div>
                </article>

                <article class="status-card">
                    <h2>Status Summary</h2>
                    <div class="summary-list">
                        <div class="summary-item">
                            <span>Total Supervisors</span>
                            <strong><?php echo e(count($supervisors)); ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Average Utilization</span>
                            <strong><?php echo e($utilizationRate); ?>%</strong>
                        </div>
                        <div class="summary-item">
                            <span>Overloaded Supervisors</span>
                            <strong><?php echo e($overloadedSupervisors); ?></strong>
                        </div>
                    </div>
                </article>
            </section>

            <section class="panel">
                <form class="filters" method="GET" action="quotaManagement.php">
                    <div>
                        <label for="searchName">Search Supervisor</label>
                        <input
                            type="text"
                            id="searchName"
                            name="searchName"
                            value="<?php echo e($searchName); ?>"
                            placeholder="Filter by name or staff ID"
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
                    <a class="button secondary" href="quotaManagement.php">Reset</a>
                </form>

                <h2>Supervisor Directory</h2>

                <?php if (empty($supervisors)): ?>
                    <div class="empty">
                        No supervisors match the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="directory">
                        <?php foreach ($supervisors as $supervisor): ?>
                            <?php
                                $statusClass =
                                    strtolower($supervisor["quotaStatus"]);
                            ?>

                            <article class="quota-row">
                                <div>
                                    <p class="name"><?php echo e($supervisor["fullName"]); ?></p>
                                    <p class="meta">
                                        <?php echo e($supervisor["userID"]); ?>
                                        ·
                                        <?php echo e($supervisor["employmentCategory"]); ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="meta">Programme</p>
                                    <p class="name"><?php echo e($supervisor["programme"]); ?></p>
                                </div>

                                <div>
                                    <span class="badge <?php echo e($statusClass); ?>">
                                        <?php echo e($supervisor["quotaStatus"]); ?>
                                    </span>
                                </div>

                                <div>
                                    <div class="bar-label">
                                        <span>
                                            <?php echo e($supervisor["currentSupervisees"]); ?>
                                            /
                                            <?php echo e($supervisor["maxSuperviseesAllowed"]); ?>
                                        </span>
                                        <span>
                                            <?php echo e($supervisor["loadPercentage"]); ?>%
                                        </span>
                                    </div>
                                    <div class="bar-track">
                                        <div
                                            class="bar-fill <?php echo $supervisor["quotaStatus"] !== "Valid" ? "full" : ""; ?>"
                                            style="width: <?php echo e($supervisor["loadPercentage"]); ?>%;"
                                        ></div>
                                    </div>
                                </div>

                                <form
                                    class="quota-form"
                                    action="../server/application/updateSupervisorQuota.php"
                                    method="POST"
                                >
                                    <input
                                        type="hidden"
                                        name="supervisorID"
                                        value="<?php echo e($supervisor["userID"]); ?>"
                                    >

                                    <div>
                                        <label for="quotaID-<?php echo e($supervisor["userID"]); ?>">
                                            Editable Quota
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
                                                    -
                                                    <?php echo e($quota["maxSuperviseesAllowed"]); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <button class="button" type="submit">
                                        Save Quota
                                    </button>
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
