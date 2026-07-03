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
| Programme, batch, and status.
*/

$filters = [
    "programme" => trim($_GET["programme"] ?? ""),
    "batch" => trim($_GET["batch"] ?? ""),
    "status" => strtolower(trim($_GET["status"] ?? ""))
];

$rosterPage =
    max(
        1,
        (int) ($_GET["rosterPage"] ?? 1)
    );

$recordsPerPage =
    3;

if (!in_array($filters["status"], ["assigned", "unassigned", ""], true)) {

    $filters["status"] = "";
}

$report =
    $reportFacade->getCohortOverview($filters);

$studentTotal =
    count($report["students"]);

$studentTotalPages =
    max(
        1,
        (int) ceil($studentTotal / $recordsPerPage)
    );

$rosterPage =
    min(
        $rosterPage,
        $studentTotalPages
    );

$studentOffset =
    ($rosterPage - 1) *
    $recordsPerPage;

$visibleStudents =
    array_slice(
        $report["students"],
        $studentOffset,
        $recordsPerPage
    );

$studentStart =
    $studentTotal === 0
        ? 0
        : $studentOffset + 1;

$studentEnd =
    min(
        $studentTotal,
        $studentOffset + count($visibleStudents)
    );

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

function rosterPageUrl($page, $filters) {

    $query = [
        "rosterPage" => max(1, (int) $page)
    ];

    foreach (["programme", "batch", "status"] as $key) {

        if (($filters[$key] ?? "") !== "") {

            $query[$key] = $filters[$key];
        }
    }

    return "adminCohortOverview.php?" . http_build_query($query);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cohort Overview | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/admin.css"); ?>">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="content-shell">
        <?php echo adminReportSidebar("cohort"); ?>

        <main class="main cohort-overview-main">
            <div class="report-shell">
                <section class="hero-card cohort-title-hero">
                    <div>
                        <span class="hero-kicker">Admin Reports</span>
                        <h1>Cohort Overview</h1>
                        <p>Manage students and faculty allocations by programme, batch, and allocation status.</p>
                    </div>
                </section>

                <div class="report-toolbar">
                    <?php echo adminReportExportMenu("cohort", $filters); ?>
                </div>

                <section class="filter-card">
                    <form class="filter-form" method="GET" action="adminCohortOverview.php">
                        <input type="hidden" name="rosterPage" value="1">
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
                    <div class="cohort-card active-cohort-card">
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
                                    <?php foreach ($visibleStudents as $student): ?>
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
                            <span>Showing <?php echo e($studentStart); ?>-<?php echo e($studentEnd); ?> of <?php echo e($studentTotal); ?> results</span>
                            <div class="table-pager" aria-label="Student roster pagination">
                                <?php if ($rosterPage > 1): ?>
                                    <a class="table-page-button" href="<?php echo e(rosterPageUrl($rosterPage - 1, $filters)); ?>" aria-label="Previous student roster page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo e($rosterPage); ?> of <?php echo e($studentTotalPages); ?></span>

                                <?php if ($rosterPage < $studentTotalPages): ?>
                                    <a class="table-page-button" href="<?php echo e(rosterPageUrl($rosterPage + 1, $filters)); ?>" aria-label="Next student roster page">&gt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&gt;</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
