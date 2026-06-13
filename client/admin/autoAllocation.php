<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/AllocationEngine.php";
require_once "../../server/business/services/AllocationWindowService.php";
require_once "../../server/business/services/AdminReportFacade.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication and RBAC Validation
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

$summary =
    $allocationEngine
    ->getAllocationDashboard();

$autoAllocationLogs =
    $allocationEngine
    ->getRecentAutoAllocationLogs(5);

$allocationWindowService =
    new AllocationWindowService();

$allocationWindow =
    $allocationWindowService
    ->getWindow();

$initialAllocationDate =
    !empty($allocationWindow["initialAllocationDate"])
        ? date("d M Y, h:i A", strtotime($allocationWindow["initialAllocationDate"]))
        : "Not configured";

$finalAllocationDate =
    !empty($allocationWindow["finalAllocationDate"])
        ? date("d M Y, h:i A", strtotime($allocationWindow["finalAllocationDate"]))
        : "Not configured";

$adminReportFacade =
    new AdminReportFacade();

$unassignedOverview =
    $adminReportFacade
    ->getCohortOverview([
        "status" => "unassigned"
    ]);

$unassignedStudents =
    array_slice(
        $unassignedOverview["students"] ?? [],
        0,
        8
    );

$hasUnassignedStudents =
    ((int) ($summary["unassignedStudents"] ?? 0)) > 0;

$allocationStatusText =
    $allocationWindow["statusText"] ?? "";

if (
    ($allocationWindow["status"] ?? "") === "closed" &&
    !$hasUnassignedStudents
) {

    $allocationStatusText =
        "Final allocation date has passed. All eligible students have been allocated; no students are pending auto-allocation.";
}

$unassignedDescription =
    $hasUnassignedStudents
        ? "Eligible students without an allocation record. After the final allocation date, these students are pending auto-allocation."
        : "All eligible students currently have allocation records. No students are waiting for auto-allocation.";

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

function engineReadinessLabel($allocationWindow) {

    return $allocationWindow["canRunAutoAllocation"]
        ? "Ready for administrator trigger."
        : "Locked until final allocation date is reached.";
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
            font-size: 14px;
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

        .button:disabled {
            background: #c9d4e2;
            color: #66788c;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .button:disabled:hover {
            box-shadow: none;
            transform: none;
        }

        .button.secondary {
            background: #0d5be8;
            color: #ffffff;
        }

        .window-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .window-box {
            background: rgba(255, 255, 255, .13);
            border-radius: 8px;
            padding: 12px 14px;
        }

        .window-label {
            color: #b9d2ff;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .window-value {
            margin-top: 4px;
            color: #ffffff;
            font-weight: 800;
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
            font-size: 15px;
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
            font-size: 15px;
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

        .table-card {
            margin-bottom: 24px;
            overflow: hidden;
        }

        .table-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .table-head h2 {
            margin: 0 0 6px;
        }

        .table-scroll {
            overflow-x: auto;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        .log-table {
            min-width: 980px;
        }

        .student-table th,
        .student-table td {
            padding: 13px 14px;
            border-top: 1px solid #eef3f8;
            text-align: left;
            font-size: 14px;
        }

        .student-table th {
            color: #7c8da0;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .8px;
            background: #fbfdff;
        }

        .status-pill {
            display: inline-flex;
            width: max-content;
            align-items: center;
            min-height: 24px;
            border-radius: 999px;
            padding: 0 10px;
            background: #eaf3ff;
            color: #0d5be8;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .empty-state {
            border: 1px dashed #aac7df;
            border-radius: 8px;
            padding: 18px;
            color: #526a7f;
            background: #f8fbff;
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
            <a class="nav-link" href="adminSupervisorReviews.php">Supervisor Reviews Audit</a>
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

            <section class="hero-grid">
                <article class="hero-card">
                    <h1>Final Allocation Engine</h1>
                    <p>
                        <?php echo $hasUnassignedStudents
                            ? "After the final allocation date, administrators can run the engine to assign eligible unassigned students."
                            : "All eligible students are currently allocated. The engine is ready only if new unassigned students appear after the final allocation date."; ?>
                    </p>

                    <div class="window-grid">
                        <div class="window-box">
                            <div class="window-label">Initial Allocation Date</div>
                            <div class="window-value"><?php echo e($initialAllocationDate); ?></div>
                        </div>
                        <div class="window-box">
                            <div class="window-label">Final Allocation Date</div>
                            <div class="window-value"><?php echo e($finalAllocationDate); ?></div>
                        </div>
                    </div>

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

                    <form action="../../server/application/admin/runAutoAllocation.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <button class="button" type="submit" <?php echo $allocationWindow["canRunAutoAllocation"] ? "" : "disabled"; ?>>
                            Run Auto Allocation
                        </button>
                    </form>
                    <p style="color:#dbe9ff; margin-top:12px;">
                        <?php echo e($allocationStatusText); ?>
                    </p>
                    <p style="color:#dbe9ff; margin-top:6px;">
                        <?php echo e(engineReadinessLabel($allocationWindow)); ?>
                    </p>
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

            <section class="panel table-card">
                <div class="table-head">
                    <div>
                        <h2>Unassigned Students</h2>
                        <p style="margin:0;color:#526a7f;font-size:14px;line-height:1.5;">
                            <?php echo e($unassignedDescription); ?>
                        </p>
                    </div>
                    <a class="button" href="adminCohortOverview.php?status=unassigned">
                        View All
                    </a>
                </div>

                <?php if (empty($unassignedStudents)): ?>
                    <div class="empty-state">There are currently no unassigned eligible students.</div>
                <?php else: ?>
                    <div class="table-scroll">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Student ID</th>
                                    <th>Programme</th>
                                    <th>Batch</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unassignedStudents as $student): ?>
                                    <tr>
                                        <td><?php echo e($student["fullName"]); ?></td>
                                        <td><?php echo e($student["studentID"]); ?></td>
                                        <td><?php echo e($student["programme"]); ?></td>
                                        <td><?php echo e($student["intakeBatch"]); ?></td>
                                        <td>
                                            <span class="status-pill">
                                                <?php echo $allocationWindow["status"] === "closed" ? "Pending Auto-Allocation" : "Unassigned"; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="terminal">
                <div><strong>[system]</strong> Initializing allocation engine...</div>
                <div><strong>[deadline]</strong> Final allocation date: <span class="warn"><?php echo e($finalAllocationDate); ?></span></div>
                <div><strong>[trigger]</strong> <?php echo e(engineReadinessLabel($allocationWindow)); ?></div>
                <div><strong>[check]</strong> Eligible students: <span class="ok"><?php echo e($summary["eligibleStudents"]); ?></span></div>
                <div><strong>[check]</strong> Current allocated students: <span class="ok"><?php echo e($summary["allocatedStudents"]); ?></span></div>
                <div><strong>[queue]</strong> Unassigned eligible students: <span class="warn"><?php echo e($summary["unassignedStudents"]); ?></span></div>
                <div><strong>[rule]</strong> Capacity lock enabled. Supervisors cannot exceed quota.</div>
                <div><strong>[strategy]</strong> System Auto-Match strategy ready.</div>
            </section>

            <section class="panel table-card" style="margin-top:24px;">
                <div class="table-head">
                    <div>
                        <h2>Auto-Allocation Log</h2>
                        <p style="margin:0;color:#526a7f;font-size:14px;line-height:1.5;">
                            Records every administrator-triggered allocation run and its final result message.
                        </p>
                    </div>
                </div>

                <?php if (empty($autoAllocationLogs)): ?>
                    <div class="empty-state">No auto-allocation run has been recorded yet.</div>
                <?php else: ?>
                    <div class="table-scroll">
                        <table class="student-table log-table">
                            <thead>
                                <tr>
                                    <th>Run ID</th>
                                    <th>Triggered At</th>
                                    <th>Admin</th>
                                    <th>Eligible</th>
                                    <th>Matched</th>
                                    <th>Unassigned</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($autoAllocationLogs as $log): ?>
                                    <tr>
                                        <td>#<?php echo e($log["logID"]); ?></td>
                                        <td><?php echo e(date("d M Y, h:i A", strtotime($log["triggeredAt"]))); ?></td>
                                        <td><?php echo e($log["triggeredByAdminName"] ?? $log["triggeredByAdminID"] ?? "System"); ?></td>
                                        <td><?php echo e($log["eligibleCount"]); ?></td>
                                        <td><?php echo e($log["matchedCount"]); ?></td>
                                        <td><?php echo e($log["unassignedCount"]); ?></td>
                                        <td>
                                            <span class="status-pill">
                                                <?php echo e(str_replace("_", " ", $log["logStatus"])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e($log["resultMessage"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

</body>
</html>
