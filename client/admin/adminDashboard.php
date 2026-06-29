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
    $adminReportFacade
    ->getAllocatedStudentProgrammeDistribution();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/admin.css"); ?>">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <div class="app-shell">
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
                <a class="nav-link active" href="adminDashboard.php">Dashboard</a>
                <a class="nav-link" href="supervisorsManagement.php">Supervisors Management</a>
                <a class="nav-link" href="studentEligibility.php">Students Eligibility</a>
                <a class="nav-link" href="quotaManagement.php">Quota Management</a>
                <a class="nav-link" href="autoAllocation.php">Allocations</a>
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

            <main class="main admin-dashboard-main">
                <?php echo statusMessage(); ?>

                <section class="alerts">
                    <?php if ($supervisorsAtCapacity > 0): ?>
                    <article class="alert danger">
                        <strong>!</strong>
                        <div>
                            <strong>Capacity Overload</strong>
                            <?php echo e($supervisorsAtCapacity); ?> supervisor(s) have reached full quota.
                        </div>
                    </article>
                    <?php endif; ?>

                    <article class="alert <?php echo e($allocationWindowAlertClass); ?>">
                        <?php if ($allocationWindowAlertClass !== "success"): ?>
                            <strong>!</strong>
                        <?php endif; ?>
                        <div>
                            <strong>Allocation Window</strong>
                            <?php echo e($allocationWindowNotice); ?>
                        </div>
                    </article>
                </section>

                <section class="dashboard-grid">
                    <article class="overview-card">
                        <h1>System Overview</h1>
                        <p>Real-time supervision and allocation metrics from live SSAS records.</p>
                        <div class="metrics">
                            <div>
                                <div class="metric-label">Total Students</div>
                                <div class="metric-value"><?php echo e(number_format($totalStudents)); ?></div>
                            </div>
                            <div>
                                <div class="metric-label">Assigned</div>
                                <div class="metric-value"><?php echo e(number_format($assignedStudents)); ?> (<?php echo e($allocationRate); ?>%)</div>
                            </div>
                            <div>
                                <div class="metric-label">Pending Requests</div>
                                <div class="metric-value"><?php echo e(number_format($pendingRequests)); ?></div>
                            </div>
                            <div>
                                <div class="metric-label">Unassigned</div>
                                <div class="metric-value"><?php echo e(number_format($pendingStudents)); ?></div>
                            </div>
                        </div>
                        <a class="button primary dashboard-cta" href="autoAllocation.php">Manage Allocations</a>
                    </article>

                    <article class="panel efficiency">
                        <h2>Allocation Efficiency</h2>
                        <div class="ring">
                            <div class="ring-value">
                                <?php echo e($allocationRate); ?>%
                                <span>Allocated</span>
                            </div>
                        </div>
                        <div class="efficiency-details">
                            <div>
                                Pending Requests
                                <strong><?php echo e(number_format($pendingRequests)); ?></strong>
                            </div>
                            <div>
                                Capacity Used
                                <strong><?php echo e($totalCapacity > 0 ? round(($allocatedTotal / $totalCapacity) * 100, 1) : 0); ?>%</strong>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="panel distribution programme-distribution">
                    <h2>Programme Allocation Distribution</h2>
                    <p class="panel-subtitle">Allocated students grouped by student programme.</p>
                    
                    <?php if (empty($programmeDistribution)): ?>
                        <p class="panel-subtitle">No allocated student programme records are available.</p>
                    <?php else: ?>
                        <?php foreach ($programmeDistribution as $distribution): ?>
                            <?php
                                $programme = trim((string) ($distribution["programme"] ?? "Unspecified"));
                                $allocated = max(0, (int) ($distribution["allocated"] ?? 0));
                                $percentage = $assignedStudents > 0 ? round(($allocated / $assignedStudents) * 100, 1) : 0;
                            ?>
                            <div class="bar-row">
                                <div class="bar-header">
                                    <span><?php echo e($programme); ?></span>
                                    <span><?php echo e($allocated); ?> student(s) (<?php echo e($percentage); ?>%)</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo e(min(100, $percentage)); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <section class="panel timeline-panel">
                    <div class="timeline-head">
                        <div>
                            <span class="status-pill timeline-status"><?php echo e(str_replace("_", " ", $allocationWindow["status"])); ?></span>
                            <h2>Allocation & Review Timeline</h2>
                            <p class="panel-subtitle">Students can submit supervisor requests from the initial allocation date until the final allocation date. The review period must start after the final allocation date.</p>
                        </div>
                    </div>
                    
                    <form class="window-form timeline-form" id="timelineForm" action="../../server/application/admin/updateAllocationWindow.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <div class="timeline-group">
                            <div class="timeline-group-title">
                                <span>Allocation Dates</span>
                                <small>Student request window</small>
                            </div>
                            <div class="timeline-group-grid">
                                <div class="timeline-field">
                                    <label for="initialAllocationDate">Initial Allocation Date</label>
                                    <input type="datetime-local" id="initialAllocationDate" name="initialAllocationDate" value="<?php echo e(dateTimeInputValue($allocationWindow["initialAllocationDate"] ?? "")); ?>" required>
                                </div>
                                <div class="timeline-field">
                                    <label for="finalAllocationDate">Final Allocation Date</label>
                                    <input type="datetime-local" id="finalAllocationDate" name="finalAllocationDate" value="<?php echo e(dateTimeInputValue($allocationWindow["finalAllocationDate"] ?? "")); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="timeline-group">
                            <div class="timeline-group-title">
                                <span>Review Dates</span>
                                <small>Supervisor decision period</small>
                            </div>
                            <div class="timeline-group-grid">
                                <div class="timeline-field">
                                    <label for="reviewStartDate">Review Period Start</label>
                                    <input type="datetime-local" id="reviewStartDate" name="reviewStartDate" value="<?php echo e(dateTimeInputValue($reviewPeriod["startTimestamp"] ?? "")); ?>" required>
                                </div>
                                <div class="timeline-field">
                                    <label for="reviewEndDate">Review Period End</label>
                                    <input type="datetime-local" id="reviewEndDate" name="reviewEndDate" value="<?php echo e(dateTimeInputValue($reviewPeriod["endTimestamp"] ?? "")); ?>" required>
                                </div>
                            </div>
                        </div>
                        <button class="button primary" type="submit">Save Timeline Dates</button>
                    </form>
                </section>

                <section class="quick-actions">
                    <article class="action-card">
                        <h3>Supervisor Accounts</h3>
                        <p>Create supervisor accounts and maintain supervisor access records.</p>
                        <a class="button primary" href="supervisorsManagement.php">Manage Supervisors</a>
                    </article>
                    <article class="action-card">
                        <h3>Quota Management</h3>
                        <p>Monitor quota tiers and supervisor capacity before allocation.</p>
                        <a class="button primary" href="quotaManagement.php">Manage Quotas</a>
                    </article>
                    <article class="action-card">
                        <h3>Reports</h3>
                        <p>Review allocation progress, pending cases, and operational status.</p>
                        <a class="button primary" href="adminCohortOverview.php">View Reports</a>
                    </article>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
