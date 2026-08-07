<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorReportFacade.php";
require_once __DIR__ . "/supervisorReportComponents.php";
require_once __DIR__ . "/../shared/accountLayout.php";

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
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Applicant Demographics", "supervisor"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo ssasPortalSidebar("report-demographics"); ?>
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
                                <option value="<?php echo ssasEscape($availableYear); ?>" <?php echo (string) $year === (string) $availableYear ? "selected" : ""; ?>>
                                    <?php echo ssasEscape($availableYear); ?>
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
                            <p class="panel-subtitle">Academic Year <?php echo ssasEscape(date("Y")); ?>/<?php echo ssasEscape(date("y") + 1); ?></p>
                        </div>
                        <span class="year-pill"><?php echo ssasEscape(reportYearLabel($year)); ?></span>
                    </div>

                    <?php if ($report["message"] !== ""): ?>
                        <div class="empty-message"><?php echo ssasEscape($report["message"]); ?></div>
                    <?php else: ?>
                        <div class="chart-layout">
                            <section class="donut-section" aria-label="Applicant total donut chart">
                                <div class="donut" style="background: <?php echo ssasEscape($donutBackground); ?>;">
                                    <div class="donut-center">
                                        <div class="donut-number">
                                            <?php echo ssasEscape(number_format($report["totalApplicants"])); ?>
                                        </div>
                                        <div class="donut-label">Total Applicants</div>
                                    </div>
                                </div>
                            </section>

                            <section class="legend-section" aria-label="Programme and percentage breakdown">
                                <div class="chart-legend">
                                    <?php foreach ($report["programmes"] as $index => $programme): ?>
                                        <div class="legend-row">
                                            <span class="legend-dot" style="background:<?php echo ssasEscape($palette[$index % count($palette)]); ?>;"></span>
                                            <div class="legend-text">
                                                <div class="legend-name"><?php echo ssasEscape($programme["programme"]); ?></div>
                                                <div class="legend-count"><?php echo ssasEscape(number_format($programme["count"])); ?> applicant(s)</div>
                                            </div>
                                            <div class="legend-pct-block">
                                                <span class="legend-pct"><?php echo ssasEscape($programme["percentage"]); ?>%</span>
                                                <span class="legend-pct-sub"><?php echo ssasEscape(number_format($programme["count"])); ?> Applicants</span>
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
                                    <?php echo ssasEscape($tag["tagName"]); ?>
                                    <span class="tag-chip-count"><?php echo ssasEscape($tag["interestedStudents"]); ?></span>
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
