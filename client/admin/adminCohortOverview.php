<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/AdminReportFacade.php";
require_once __DIR__ . "/../shared/accountLayout.php";
require_once __DIR__ . "/adminReportComponents.php";

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators may generate the cohort overview report.
*/

SessionManager::startSession();
SessionManager::requireRole("Administrator");

/*
|--------------------------------------------------------------------------
| Report Facade
|--------------------------------------------------------------------------
| Uses the facade pattern to keep page rendering separate from data assembly.
*/

$reportFacade =
    new AdminReportFacade();

/*
|--------------------------------------------------------------------------
| Report Filters
|--------------------------------------------------------------------------
| Programme, specialization, batch, and status.
*/

$filters = [
    "programme" => trim($_GET["programme"] ?? ""),
    "specialization" => trim($_GET["specialization"] ?? ""),
    "batch" => trim($_GET["batch"] ?? ""),
    "status" => strtolower(trim($_GET["status"] ?? ""))
];

if (!in_array($filters["status"], ["assigned", "unassigned", ""], true)) {

    $filters["status"] = "";
}

$report =
    $reportFacade->getCohortOverview($filters);

/*
|--------------------------------------------------------------------------
| Page Helpers
|--------------------------------------------------------------------------
*/

function selected($left, $right) {

    return
        (string) $left === (string) $right ? "selected" : "";
}

// Shows friendly text for empty filters in the active cohort card.
function cohortLabel($value, $fallback) {

    return
        trim((string) $value) === "" ? $fallback : (string) $value;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cohort Overview | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="content-shell">
        <?php echo adminReportSidebar("cohort"); ?>

        <main class="main">
            <div class="report-shell">
                <header class="report-head">
                    <div>
                        <h1>Cohort Overview</h1>
                        <p>Manage students and faculty allocations by programme, specialization, batch, and allocation status.</p>
                    </div>
                    <?php echo adminReportExportMenu("cohort", $filters); ?>
                </header>

                <section class="filter-card">
                    <form class="filter-form" method="GET" action="adminCohortOverview.php">
                        <div class="filter-field">
                            <label>Programme</label>
                            <select name="programme">
                                <option value="">All Programmes</option>
                                <?php foreach ($report["programmeOptions"] as $option): ?>
                                    <option value="<?php echo e($option["programme"]); ?>" <?php echo selected($filters["programme"], $option["programme"]); ?>>
                                        <?php echo e($option["programme"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label>Specialization</label>
                            <select name="specialization">
                                <option value="">All Specializations</option>
                                <?php foreach ($report["specializationOptions"] as $option): ?>
                                    <option value="<?php echo e($option["specialization"]); ?>" <?php echo selected($filters["specialization"], $option["specialization"]); ?>>
                                        <?php echo e($option["specialization"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label>Batch</label>
                            <select name="batch">
                                <option value="">All Batches</option>
                                <?php foreach ($report["batchOptions"] as $option): ?>
                                    <option value="<?php echo e($option["intakeBatch"]); ?>" <?php echo selected($filters["batch"], $option["intakeBatch"]); ?>>
                                        <?php echo e($option["intakeBatch"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Students</option>
                                <option value="assigned" <?php echo selected($filters["status"], "assigned"); ?>>Assigned</option>
                                <option value="unassigned" <?php echo selected($filters["status"], "unassigned"); ?>>Unassigned</option>
                            </select>
                        </div>
                        <button class="button" type="submit">Apply</button>
                    </form>
                </section>

                <section class="hero-grid">
                    <div class="cohort-card">
                        <h2>Active Cohort</h2>
                        <p><?php echo e(cohortLabel($filters["batch"], "All Batches")); ?> - <?php echo e(cohortLabel($filters["programme"], "All Programmes")); ?></p>
                        <div class="metric-row">
                            <div>
                                <div class="metric-value"><?php echo e($report["totalStudents"]); ?></div>
                                <div class="metric-label">Filtered Students</div>
                            </div>
                            <div>
                                <div class="metric-value"><?php echo e($report["allocatedStudents"]); ?></div>
                                <div class="metric-label">Allocated</div>
                            </div>
                            <div>
                                <div class="metric-value text-danger-light"><?php echo e($report["unassignedStudents"]); ?></div>
                                <div class="metric-label">Unassigned</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-card">
                        <div class="progress-label">Allocation Progress</div>
                        <div class="progress-value"><?php echo e($report["allocationProgress"]); ?>%</div>
                        <div class="meter"><span style="width: <?php echo e(min($report["allocationProgress"], 100)); ?>%;"></span></div>
                        <p class="note">Filtered <?php echo e($report["totalStudents"]); ?> of <?php echo e($report["systemTotalStudents"]); ?> active student record(s) in real time.</p>
                    </div>
                </section>

                <section class="table-card">
                    <div class="table-headline">
                        <div>
                            <h2>Student Roster</h2>
                            <p>Showing student records for the selected cohort filters.</p>
                        </div>
                    </div>

                    <?php if ($report["message"] !== ""): ?>
                        <div class="empty-message"><?php echo e($report["message"]); ?></div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>ID Number</th>
                                        <th>Specialization</th>
                                        <th>Allocated Supervisor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report["students"] as $student): ?>
                                        <?php $assigned = !empty($student["allocationID"]); ?>
                                        <tr>
                                            <td>
                                                <div class="person-cell">
                                                    <div class="avatar">
                                                        <?php if (!empty($student["profilePhotoPath"])): ?>
                                                            <img src="<?php echo e($student["profilePhotoPath"]); ?>" alt="">
                                                        <?php else: ?>
                                                            <?php echo e(adminReportInitials($student["fullName"])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <p class="name"><?php echo e($student["fullName"]); ?></p>
                                                        <p class="meta"><?php echo e($student["programme"]); ?> | <?php echo e($student["currentSem"]); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo e($student["studentID"]); ?></td>
                                            <td><?php echo e($student["specializations"] ?: "No specialization"); ?></td>
                                            <td><?php echo e($assigned ? $student["supervisorName"] : "Not Assigned"); ?></td>
                                            <td>
                                                <span class="status-pill <?php echo $assigned ? "blue" : "red"; ?>">
                                                    <?php echo $assigned ? "Assigned" : "Unassigned"; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-note">
                            Showing 1-<?php echo e(count($report["students"])); ?> of <?php echo e(count($report["students"])); ?> results
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
