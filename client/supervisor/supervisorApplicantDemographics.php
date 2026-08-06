<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorReportFacade.php";
require_once __DIR__ . "/supervisorLayout.php";
require_once __DIR__ . "/supervisorReportComponents.php";

// Supervisor Access Control
// Ensures only supervisor accounts can view applicant demographic intelligence.
SessionManager::startSession();
SessionManager::requireRole("Supervisor");

// Year Filter
// Uses a four-digit year only; invalid input falls back to all years.
$year = trim($_GET["year"] ?? "");

if ($year !== "" && !preg_match("/^[0-9]{4}$/", $year)) {
    $year = "";
}

// Report Facade
// Applies the Facade design pattern from the supervisor report class diagram.
$reportFacade = new SupervisorReportFacade();
$report       = $reportFacade->getDemographicData($_SESSION["userID"], $year);
$year         = $report["selectedYear"];
$palette      = reportPalette();

/* ── Build conic-gradient segments for the donut ── */
// Donut Segment Builder
// Converts programme percentages into conic-gradient slices for the on-screen chart.
$segments = [];
$cursor   = 0;

foreach ($report["programmes"] as $index => $programme) {
    $degrees    = $programme["percentage"] * 3.6;
    $segments[] =
        $palette[$index % count($palette)]
        . " " . $cursor . "deg "
        . ($cursor + $degrees) . "deg";
    $cursor += $degrees;
}

$donutBackground = empty($segments) ? "#edf2f7" : "conic-gradient(" . implode(", ", $segments) . ")";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Demographics | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/supervisor.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("report-demographics"); ?>
        <main class="main">
            <div class="report-shell">

                <section class="report-head report-hero">
                    <div>
                        <div class="eyebrow">Supervisor Intelligence</div>
                        <h1>Applicant Demographics</h1>
                        <p>Displays a pie chart breaking down the academic background of students currently under your supervision.</p>
                    </div>
                </section>

                <div class="report-toolbar">
                    <form class="filter-row report-filter" method="GET" action="supervisorApplicantDemographics.php">
                        <select name="year" aria-label="Filter by year">
                            <option value="" <?php echo $year === "" ? "selected" : ""; ?>>All Years</option>
                            <?php foreach ($report["years"] as $availableYear): ?>
                                <option value="<?php echo e($availableYear); ?>" <?php echo (string) $year === (string) $availableYear ? "selected" : ""; ?>>
                                    <?php echo e($availableYear); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn-apply" type="submit">Apply</button>
                    </form>
                    <?php echo reportExportMenu("demographics", ["year" => $year]); ?>
                </div>

                <section class="demographic-card">
                    <div class="card-top">
                        <div>
                            <h2 class="panel-title">Programme Distribution</h2>
                            <p class="panel-subtitle">Academic Year <?php echo e(date("Y")); ?>/<?php echo e(date("y") + 1); ?></p>
                        </div>
                        <span class="year-pill"><?php echo e(reportYearLabel($year)); ?></span>
                    </div>

                    <?php if ($report["message"] !== ""): ?>
                        <div class="empty-message"><?php echo e($report["message"]); ?></div>
                    <?php else: ?>
                        <div class="chart-layout">
                            <section class="donut-section" aria-label="Applicant total donut chart">
                                <div class="donut" style="background: <?php echo e($donutBackground); ?>;">
                                    <div class="donut-center">
                                        <div class="donut-number">
                                            <?php echo e(number_format($report["totalApplicants"])); ?>
                                        </div>
                                        <div class="donut-label">Total Applicants</div>
                                    </div>
                                </div>
                            </section>

                            <section class="legend-section" aria-label="Programme and percentage breakdown">
                                <div class="chart-legend">
                                    <?php foreach ($report["programmes"] as $index => $programme): ?>
                                        <div class="legend-row">
                                            <span class="legend-dot" style="background:<?php echo e($palette[$index % count($palette)]); ?>;"></span>
                                            <div class="legend-text">
                                                <div class="legend-name"><?php echo e($programme["programme"]); ?></div>
                                                <div class="legend-count"><?php echo e(number_format($programme["count"])); ?> applicant(s)</div>
                                            </div>
                                            <div class="legend-pct-block">
                                                <span class="legend-pct"><?php echo e($programme["percentage"]); ?>%</span>
                                                <span class="legend-pct-sub"><?php echo e(number_format($programme["count"])); ?> Applicants</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($report["expertiseTags"])): ?>
                        <div class="tag-strip">
                            <?php foreach ($report["expertiseTags"] as $tag): ?>
                                <span class="tag-chip">
                                    <?php echo e($tag["tagName"]); ?>
                                    <span class="tag-chip-count"><?php echo e($tag["interestedStudents"]); ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    <?php echo reportExportScript(); ?>
</body>
</html>
