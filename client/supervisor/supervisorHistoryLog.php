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
$year =
    trim(
        $_GET["year"] ?? ""
    );

$semester =
    trim(
        $_GET["semester"] ?? ""
    );

// Report Facade
// Fetches history through the supervisor report facade instead of querying pages directly.
$reportFacade =
    new SupervisorReportFacade();

$history =
    $reportFacade
    ->getSupervisionHistory(
        $_SESSION["userID"],
        $year,
        $semester
    );

$year =
    $history["selectedYear"];

$semester =
    $history["selectedSemester"];

function historySemesterLabel(
    $currentSem
) {

    if (preg_match('/S\s*([0-9]+)/i', (string) $currentSem, $matches)) {

        return "Semester " . $matches[1];
    }

    if (preg_match('/([0-9])\s*$/', (string) $currentSem, $matches)) {

        return "Semester " . $matches[1];
    }

    return $currentSem !== "" ? $currentSem : "Not recorded";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervision History | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        <?php echo reportStyles(); ?>

        /* Summary cards */
        .summary-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(230px, .7fr); gap: 18px; margin-bottom: 28px; }
        .blue-stat { min-height: 166px; padding: 28px; border-radius: 13px; background: radial-gradient(circle at 82% 20%, rgba(255,255,255,.18), transparent 24%), #0d5be8; color: #fff; box-shadow: 0 16px 26px rgba(13,91,232,.24); }
        .blue-stat .label { color: #b9d2ff; font-size: 14px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; margin-top: 22px; }
        .blue-stat .value { font-size: 42px; font-weight: 900; margin-top: 5px; }
        .field-card { padding: 28px; display: flex; flex-direction: column; justify-content: center; }
        .field-card .label { color: #b4c0ce; font-size: 14px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        .field-card .value { margin-top: 14px; color: #172033; font-size: 18px; font-weight: 900; }
        /* Assignment log table */
        .log-card { overflow: hidden; }
        .log-head { display: flex; justify-content: space-between; align-items: center; padding: 22px 24px 10px; }
        @media (max-width: 900px) { .summary-grid { grid-template-columns: 1fr; } .log-head { display: block; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("report-history"); ?>
        <main class="main">
            <div class="report-shell">
                <!-- Page Header -->
                <section class="report-head">
                    <div>
                        <h1>Supervision History</h1>
                        <p>Access supervision assignment records organized by allocation year and student semester.</p>
                    </div>
                    <?php echo reportExportMenu("history", ["year" => $year, "semester" => $semester]); ?>
                </section>

                <!-- Supervision Summary -->
                <section class="summary-grid">
                    <div class="blue-stat">
                        <div class="label">Total Career Supervisions</div>
                        <div class="value"><?php echo e($history["careerTotal"]); ?></div>
                    </div>
                    <div class="report-card field-card">
                        <div class="label">Primary Field</div>
                        <div class="value"><?php echo e($history["primaryField"]); ?></div>
                        <p class="stat-note">Based on your supervisor expertise profile</p>
                    </div>
                </section>

                <!-- History Filters -->
                <form class="filter-row" method="GET" action="supervisorHistoryLog.php">
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
                    <button class="button" type="submit">Apply</button>
                </form>

                <!-- Assignment Log -->
                <section class="report-card log-card">
                    <div class="log-head">
                        <h2 class="panel-title">Assignment Log</h2>
                        <?php echo reportExportMenu("history", ["year" => $year, "semester" => $semester]); ?>
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
                                    <?php foreach ($history["records"] as $index => $record): ?>
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
                            <span>Showing <?php echo e(count($history["records"])); ?> historical record(s)</span>
                            <span>1</span>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    <?php echo reportExportScript(); ?>
</body>
</html>
