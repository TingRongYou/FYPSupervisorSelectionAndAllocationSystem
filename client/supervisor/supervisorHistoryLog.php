<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorReportFacade.php";
require_once __DIR__ . "/supervisorLayout.php";
require_once __DIR__ . "/supervisorReportComponents.php";

// Supervisor Access Control
// Keeps history records limited to the signed-in supervisor.
SessionManager::startSession();
SessionManager::requireRole("Supervisor");

// Report Filters
// Reads the optional year and semester filters from the request.
$year = trim($_GET["year"] ?? "");
$semester = trim($_GET["semester"] ?? "");
$historyPage = max(1, (int) ($_GET["historyPage"] ?? 1));
$historyPerPage = 3;

// Report Facade
// Fetches history through the supervisor report facade instead of querying pages directly.
$reportFacade = new SupervisorReportFacade();

$history =
    $reportFacade
    ->getSupervisionHistory(
        $_SESSION["userID"],
        $year,
        $semester
    );

$year = $history["selectedYear"];
$semester = $history["selectedSemester"];
$historyTotal = (int) ($history["total"] ?? count($history["records"]));
$historyTotalPages = max(1, (int) ceil($historyTotal / $historyPerPage));
$historyPage = min($historyPage, $historyTotalPages);
$historyOffset = ($historyPage - 1) * $historyPerPage;
$visibleHistoryRecords =
    array_slice(
        $history["records"],
        $historyOffset,
        $historyPerPage
    );
$historyStart = $historyTotal === 0 ? 0 : $historyOffset + 1;

$historyEnd = min($historyTotal, $historyOffset + count($visibleHistoryRecords));

function historySemesterLabel($currentSem) {
    if (preg_match('/S\s*([0-9]+)/i', (string) $currentSem, $matches)) {
        return "Semester " . $matches[1];
    }

    if (preg_match('/([0-9])\s*$/', (string) $currentSem, $matches)) {
        return "Semester " . $matches[1];
    }

    return $currentSem !== "" ? $currentSem : "Not recorded";
}

function historyPageUrl($page, $year, $semester) {

    $query = ["historyPage" => max(1, (int) $page)];

    if ($year !== "") {
        $query["year"] = $year;
    }

    if ($semester !== "") {
        $query["semester"] = $semester;
    }

    return "supervisorHistoryLog.php?" . http_build_query($query);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervision History | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/supervisor.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("report-history"); ?>
        <main class="main">
            <div class="report-shell">
                <section class="report-head history-hero">
                    <div>
                        <div class="eyebrow">Supervisor Records</div>
                        <h1>Supervision History</h1>
                        <p>Access supervision assignment records organized by allocation year and student semester.</p>
                    </div>
                </section>

                <section class="summary-grid history-summary">
                    <div class="history-stat-card">
                        <div>
                            <div class="label">Total Career Supervisions</div>
                            <div class="value"><?php echo e($history["careerTotal"]); ?></div>
                        </div>
                        <span class="year-pill"><?php echo e(reportYearLabel($year)); ?></span>
                    </div>
                    <div class="report-card field-card">
                        <div class="label">Primary Field</div>
                        <div class="value"><?php echo e($history["primaryField"]); ?></div>
                        <p class="stat-note">Based on your supervisor expertise profile</p>
                    </div>
                </section>

                <div class="history-toolbar">
                    <form class="filter-row history-filter" method="GET" action="supervisorHistoryLog.php">
                        <input type="hidden" name="historyPage" value="1">
                        <select name="year" aria-label="Year">
                            <option value="">All Years</option>
                            <?php foreach ($history["years"] as $availableYear): ?>
                                <option value="<?php echo e($availableYear); ?>" <?php echo (string) $year === (string) $availableYear ? "selected" : ""; ?>>
                                    <?php echo e($availableYear); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="semester" aria-label="Semester">
                            <option value="" <?php echo $semester === "" ? "selected" : ""; ?>>All Semesters</option>
                            <option value="1" <?php echo $semester === "1" ? "selected" : ""; ?>>Semester 1</option>
                            <option value="2" <?php echo $semester === "2" ? "selected" : ""; ?>>Semester 2</option>
                            <option value="3" <?php echo $semester === "3" ? "selected" : ""; ?>>Semester 3</option>
                        </select>
                        <button class="btn-apply" type="submit">Apply</button>
                    </form>
                    <?php echo reportExportMenu("history", ["year" => $year, "semester" => $semester]); ?>
                </div>

                <section class="report-card log-card">
                    <div class="log-head">
                        <div>
                            <h2 class="panel-title">Assignment Log</h2>
                            <p class="panel-subtitle">Sorted by newest allocation first, then student name A-Z.</p>
                        </div>
                        <span class="year-pill"><?php echo e(reportSemesterLabel($semester)); ?></span>
                    </div>
                    <?php if ($history["message"] !== ""): ?>
                        <div style="padding: 0 24px 24px;">
                            <div class="empty-message"><?php echo e($history["message"]); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="table-scroll">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Student Name</th>
                                        <th>Project Title</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visibleHistoryRecords as $index => $record): ?>
                                        <tr>
                                            <td><strong><?php echo e($record["completionYear"]); ?></strong></td>
                                            <td><?php echo e(historySemesterLabel($record["currentSem"] ?? "")); ?></td>
                                            <td>
                                                <span class="student-chip" style="background: <?php echo e(reportPalette()[$index % count(reportPalette())]); ?>">
                                                    <?php echo e(reportInitials($record["alumniName"])); ?>
                                                </span>
                                                <?php echo e($record["alumniName"]); ?>
                                            </td>
                                            <td><?php echo e($record["projectTitle"]); ?></td>
                                            <td>
                                                <span class="status-pill <?php echo ($record["allocationMethod"] ?? "") === "System Auto-Match" ? "blue" : "green"; ?>">
                                                    <?php echo e($record["statusLabel"] ?? "Active"); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-note">
                            <span>Showing <?php echo e($historyStart); ?>-<?php echo e($historyEnd); ?> of <?php echo e($historyTotal); ?> historical record(s)</span>
                            <div class="table-pager" aria-label="Supervision history pagination">
                                <?php if ($historyPage > 1): ?>
                                    <a class="table-page-button" href="<?php echo e(historyPageUrl($historyPage - 1, $year, $semester)); ?>" aria-label="Previous history page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo e($historyPage); ?> of <?php echo e($historyTotalPages); ?></span>

                                <?php if ($historyPage < $historyTotalPages): ?>
                                    <a class="table-page-button" href="<?php echo e(historyPageUrl($historyPage + 1, $year, $semester)); ?>" aria-label="Next history page">&gt;</a>
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
    <?php echo reportExportScript(); ?>
</body>
</html>
