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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Allocation Engine | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/admin.js" defer></script>
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
                    <p>Pending request and allocation constraints are checked before commit.</p>
                </article>

                <article class="check-card">
                    <h3>Algorithm Load</h3>
                    <p>Strategy engine prioritizes programme compatibility and available capacity.</p>
                </article>

                <article class="check-card">
                    <h3>Ruleset</h3>
                    <p>Eligible students only, one allocation per student, no supervisor over-quota.</p>
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
                    <a class="button secondary" href="adminCohortOverview.php?status=unassigned">View All</a>
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