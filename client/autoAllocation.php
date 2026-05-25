<?php

require_once "../server/application/SessionManager.php";
require_once "../server/business/AllocationEngine.php";
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

$allocationEngine =
    new AllocationEngine();

$summary =
    $allocationEngine
    ->getAllocationDashboard();

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
        Auto-Allocation Engine | SSAS
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

        .hero-grid {
            display: grid;
            grid-template-columns: 1.6fr .8fr;
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
        }

        .hero-card p {
            margin: 0;
            color: #dbe9ff;
        }

        .timer-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 28px;
        }

        .timer-box {
            background: rgba(255, 255, 255, .13);
            border-radius: 8px;
            padding: 14px;
            text-align: center;
        }

        .timer-value {
            font-size: 30px;
            font-weight: 800;
        }

        .timer-label {
            color: #b9d2ff;
            font-size: 12px;
            text-transform: uppercase;
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
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .14);
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
            text-align: center;
            font-size: 13px;
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .check-card {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, .08);
        }

        .check-card h3 {
            margin: 0 0 10px;
            color: #0b3760;
            font-size: 18px;
        }

        .check-card p {
            margin: 0;
            color: #526a7f;
            line-height: 1.5;
            font-size: 14px;
        }

        .terminal {
            background: #101a2e;
            color: #d7e6ff;
            border-radius: 10px;
            padding: 18px;
            font-family: Consolas, monospace;
            font-size: 13px;
            line-height: 1.8;
            box-shadow: 0 12px 24px rgba(16, 26, 46, .2);
        }

        .terminal strong {
            color: #66d9ef;
        }

        .terminal .ok {
            color: #7ee787;
        }

        .terminal .warn {
            color: #ffd866;
        }

        @media (max-width: 1050px) {
            .hero-grid,
            .check-grid {
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

            .timer-grid {
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
            <a class="nav-link" href="quotaManagement.php">Quota Management</a>
            <a class="nav-link active" href="autoAllocation.php">Allocations</a>
            <a class="nav-link" href="#">Reports</a>
            <a class="nav-link" href="../server/application/logout.php">Logout</a>
        </aside>

        <main class="main">

            <?php echo statusMessage(); ?>

            <section class="hero-grid">
                <article class="hero-card">
                    <h1>Final Allocation Engine</h1>
                    <p>
                        System will automatically finalize all pending supervisor allocations.
                    </p>

                    <div class="timer-grid">
                        <div class="timer-box">
                            <div class="timer-value"><?php echo e($summary["eligibleStudents"]); ?></div>
                            <div class="timer-label">Eligible</div>
                        </div>
                        <div class="timer-box">
                            <div class="timer-value"><?php echo e($summary["unassignedStudents"]); ?></div>
                            <div class="timer-label">Unassigned</div>
                        </div>
                        <div class="timer-box">
                            <div class="timer-value"><?php echo e($summary["pendingRequests"]); ?></div>
                            <div class="timer-label">Pending</div>
                        </div>
                    </div>

                    <br>

                    <form action="../server/application/runAutoAllocation.php" method="POST">
                        <button class="button" type="submit">
                            Run Auto Allocation
                        </button>
                    </form>
                </article>

                <article class="panel">
                    <h2>Status Summary</h2>
                    <div class="status-ring">
                        <strong><?php echo e($summary["allocationRate"]); ?>%</strong>
                    </div>
                    <p class="status-caption">
                        Matching efficiency current value.
                    </p>
                </article>
            </section>

            <section class="check-grid">
                <article class="check-card">
                    <h3>System Conflicts</h3>
                    <p>
                        Pending request and allocation constraints are checked before commit.
                    </p>
                </article>

                <article class="check-card">
                    <h3>Algorithm Load</h3>
                    <p>
                        Strategy engine prioritizes programme compatibility and available capacity.
                    </p>
                </article>

                <article class="check-card">
                    <h3>Ruleset</h3>
                    <p>
                        Eligible students only, one allocation per student, no supervisor over-quota.
                    </p>
                </article>
            </section>

            <section class="terminal">
                <div><strong>[system]</strong> Initializing allocation engine...</div>
                <div><strong>[check]</strong> Eligible students: <span class="ok"><?php echo e($summary["eligibleStudents"]); ?></span></div>
                <div><strong>[check]</strong> Current allocated students: <span class="ok"><?php echo e($summary["allocatedStudents"]); ?></span></div>
                <div><strong>[queue]</strong> Unassigned eligible students: <span class="warn"><?php echo e($summary["unassignedStudents"]); ?></span></div>
                <div><strong>[rule]</strong> Capacity lock enabled. Supervisors cannot exceed quota.</div>
                <div><strong>[strategy]</strong> System Auto-Match strategy ready.</div>
            </section>

        </main>
    </div>

</body>
</html>
