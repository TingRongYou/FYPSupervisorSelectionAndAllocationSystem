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

$donutBackground =
    empty($segments)
    ? "#edf2f7"
    : "conic-gradient(" . implode(", ", $segments) . ")";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Demographics | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        <?php echo reportStyles(); ?>

        /* ── Fill the full main area ── */
        .main { display: flex; flex-direction: column; }

        .report-shell {
            /* Remove max-width cap — fill all available space */
            width: 100%;
            max-width: 1500px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── Page header ── */
        .report-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 20px;
        }
        .eyebrow {
            font-size: 11px; font-weight: 900;
            color: #0d5be8; text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 5px;
        }
        .report-head h1 {
            margin: 0 0 7px;
            font-size: 32px; font-weight: 700; color: #172033;
        }
        .report-head p {
            margin: 0; color: #6b7f91; font-size: 14px; line-height: 1.5;
        }

        /* ── Filter row ── */
        .filter-row {
            display: flex; gap: 10px; align-items: center;
            margin-bottom: 20px;
        }
        .filter-row select {
            height: 40px; border: 1px solid #d4e2f0;
            border-radius: 8px; background: #fff;
            color: #1d2b3a; padding: 0 36px 0 14px;
            font-size: 13px; font-weight: 600;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7f91' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 11px center;
            cursor: pointer;
        }
        .filter-row select:focus { outline: none; border-color: #0d5be8; }
        .btn-apply {
            height: 40px; padding: 0 24px; border: none;
            border-radius: 8px; background: #172033;
            color: #fff; font-size: 13px; font-weight: 800; cursor: pointer;
        }
        .btn-apply:hover { background: #0d1829; }

        /* ── Main card — fills remaining vertical space ── */
        .demographic-card {
            background: #fff;
            border: 1px solid #d9e7f3;
            border-radius: 16px;
            padding: 36px 42px 40px;
            box-shadow: 0 6px 28px rgba(11,79,138,.08);
            flex: 1;                  /* stretch to fill .report-shell */
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Card header */
        .card-top {
            display: flex; justify-content: space-between;
            align-items: flex-start; gap: 16px; margin-bottom: 36px;
        }
        .panel-title    { margin: 0 0 5px; font-size: 22px; font-weight: 700; color: #172033; }
        .panel-subtitle { margin: 0; font-size: 14px; color: #8a9caf; }

        .year-pill {
            display: inline-flex; align-items: center; gap: 7px;
            border: 1px solid #d4e2f0; border-radius: 999px;
            background: #f6f9fc; color: #526a7f;
            font-size: 11px; font-weight: 900;
            padding: 7px 14px; text-transform: uppercase; letter-spacing: .6px;
            white-space: nowrap;
        }
        .year-pill::before {
            content: "";
            width: 7px; height: 7px; border-radius: 50%;
            background: #0d5be8; flex-shrink: 0;
        }

        /* ── Chart layout ──
        Left: donut (fixed width)
        Right: legend (fills remaining space)
        ── */
        .chart-layout {
            display: grid;
            grid-template-columns: minmax(300px, 1fr) minmax(360px, 1fr);
            gap: 0;
            align-items: stretch;
            flex: 1;                  /* let the chart area expand */
            background: #fff;
        }

        /* ── Donut chart — thinner ring ── */
        .donut-section {
            display: grid;
            place-items: center;
            min-height: 360px;
            padding: 36px 44px;
            background: linear-gradient(180deg, #fff, #fbfdff);
        }

        .legend-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 360px;
            padding: 42px 52px;
        }

        .donut {
            /* Larger overall diameter */
            width: 280px; height: 280px;
            border-radius: 50%;
            background: <?php echo e($donutBackground); ?>;
            position: relative;
            display: grid; place-items: center;
        }

        /* Hollow centre — larger hole = thinner ring */
        .donut::after {
            content: ""; position: absolute;
            /* hole diameter = overall - (2 × ring thickness)
               ring thickness ≈ 30px  →  hole = 220px          */
            width: 220px; height: 220px;
            border-radius: 50%; background: #fff;
        }

        .donut-center {
            position: relative; z-index: 1; text-align: center;
        }
        .donut-number {
            color: #172033; font-size: 38px; font-weight: 900; line-height: 1;
        }
        .donut-label {
            margin-top: 6px; color: #8a9caf;
            font-size: 10px; font-weight: 900;
            text-transform: uppercase; letter-spacing: .9px;
        }

        /* ── Legend ── */
        .chart-legend { display: grid; gap: 0; }

        .legend-row {
            display: grid;
            /* dot | name+count | percentage — all on ONE row */
            grid-template-columns: 12px minmax(120px, 1fr) auto;
            gap: 13px;
            justify-content: start;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f0f4f8;
        }
        .legend-row:last-child { border-bottom: none; }

        .legend-dot {
            width: 12px; height: 12px;
            border-radius: 50%; flex-shrink: 0;
        }

        /* Name and count stacked vertically in the middle column */
        .legend-text { min-width: 0; }
        .legend-name {
            font-size: 15px; font-weight: 700; color: #172033;
            margin-bottom: 3px; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .legend-count { font-size: 12px; color: #8a9caf; font-weight: 600; }

        /* Percentage aligned to the right, same row as name */
        .legend-pct-block { text-align: right; white-space: nowrap; }
        .legend-pct {
            font-size: 22px; font-weight: 900; color: #172033;
            display: block; line-height: 1;
        }
        .legend-pct-sub {
            font-size: 11px; color: #8a9caf;
            font-weight: 600; margin-top: 2px; display: block;
        }

        /* ── Expertise tag strip ── */
        .tag-strip {
            display: flex; gap: 8px; flex-wrap: wrap;
            margin-top: 28px; padding-top: 22px;
        }
        .tag-chip {
            display: inline-flex; align-items: center; gap: 6px;
            border-radius: 999px; background: #f1f5f9;
            color: #526a7f; padding: 7px 14px;
            font-size: 12px; font-weight: 700;
        }
        .tag-chip-count {
            background: #e2ecf7; color: #0d5be8;
            border-radius: 999px; padding: 2px 8px;
            font-size: 11px; font-weight: 900;
        }

        /* ── Empty state ── */
        .empty-message {
            text-align: center; padding: 64px 20px;
            color: #8a9caf; font-size: 15px;
            flex: 1; display: flex; align-items: center; justify-content: center;
        }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .chart-layout { grid-template-columns: 1fr; }
            .donut        { width: 220px; height: 220px; }
            .donut::after { width: 168px; height: 168px; }
        }
        @media (max-width: 640px) {
            .demographic-card { padding: 20px; }
            .report-head      { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("report-demographics"); ?>
        <main class="main">
            <div class="report-shell">

                <!-- ── Page header ── -->
                <section class="report-head">
                    <div>
                        <div class="eyebrow">Supervisor Intelligence</div>
                        <h1>Applicant Demographics</h1>
                        <p>Displays a pie chart breaking down the academic background of students currently under your supervision.</p>
                    </div>
                    <?php echo reportExportMenu("demographics", ["year" => $year]); ?>
                </section>

                <!-- Year filter -->
                <form class="filter-row" method="GET"
                    action="supervisorApplicantDemographics.php">
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

                <!-- ── Main chart card ── -->
                <section class="demographic-card">

                    <!-- Card header -->
                    <div class="card-top">
                        <div>
                            <h2 class="panel-title">Programme Distribution</h2>
                            <p class="panel-subtitle">
                                Academic Year <?php echo e(date("Y")); ?>/<?php echo e(date("y") + 1); ?>
                            </p>
                        </div>
                        <span class="year-pill">
                            <?php echo e(reportYearLabel($year)); ?>
                        </span>
                    </div>

                    <?php if ($report["message"] !== ""): ?>
                        <div class="empty-message"><?php echo e($report["message"]); ?></div>

                    <?php else: ?>
                        <div class="chart-layout">

                            <!-- Donut section -->
                            <section class="donut-section" aria-label="Applicant total donut chart">
                                <div class="donut">
                                    <div class="donut-center">
                                        <div class="donut-number">
                                            <?php echo e(number_format($report["totalApplicants"])); ?>
                                        </div>
                                        <div class="donut-label">Total Applicants</div>
                                    </div>
                                </div>
                            </section>

                            <!-- Programme percentage section -->
                            <section class="legend-section" aria-label="Programme and percentage breakdown">
                                <div class="chart-legend">
                                    <?php foreach ($report["programmes"] as $index => $programme): ?>
                                        <div class="legend-row">

                                            <!-- Colour dot -->
                                            <span class="legend-dot"
                                                style="background:<?php echo e($palette[$index % count($palette)]); ?>;"></span>

                                            <!-- Programme name + applicant count -->
                                            <div class="legend-text">
                                                <div class="legend-name">
                                                    <?php echo e($programme["programme"]); ?>
                                                </div>
                                                <div class="legend-count">
                                                    <?php echo e(number_format($programme["count"])); ?> applicant(s)
                                                </div>
                                            </div>

                                            <!-- Percentage block (right-aligned, same row) -->
                                            <div class="legend-pct-block">
                                                <span class="legend-pct">
                                                    <?php echo e($programme["percentage"]); ?>%
                                                </span>
                                                <span class="legend-pct-sub">
                                                    <?php echo e(number_format($programme["count"])); ?> Applicants
                                                </span>
                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                        </div>
                    <?php endif; ?>

                    <!-- Expertise tag strip -->
                    <?php if (!empty($report["expertiseTags"])): ?>
                        <div class="tag-strip">
                            <?php foreach ($report["expertiseTags"] as $tag): ?>
                                <span class="tag-chip">
                                    <?php echo e($tag["tagName"]); ?>
                                    <span class="tag-chip-count">
                                        <?php echo e($tag["interestedStudents"]); ?>
                                    </span>
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
