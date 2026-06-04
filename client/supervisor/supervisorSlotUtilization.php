<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorReportFacade.php";
require_once __DIR__ . "/supervisorLayout.php";
require_once __DIR__ . "/supervisorReportComponents.php";

// Supervisor Access Control
// Restricts utilization metrics to the signed-in supervisor account.
SessionManager::startSession();
SessionManager::requireRole("Supervisor");

// Report Facade
// Coordinates quota, allocation, and weekly trend data through the facade layer.
$reportFacade =
    new SupervisorReportFacade();

$utilization =
    $reportFacade
    ->getUtilizationStats(
        $_SESSION["userID"]
    );

// Benchmark Widths
// Clamps percentage bars so visual progress never exceeds the track.
$fillWidth =
    min(
        100,
        max(0, $utilization["fillRate"])
    );

$departmentWidth =
    min(
        100,
        max(0, $utilization["departmentAverage"])
    );

$maxTrendValue =
    max(
        1,
        (float) ($utilization["maxTrendValue"] ?? 1)
    );

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Utilization | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        <?php echo reportStyles(); ?>

        /* Main utilization layout */
        .utilization-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 22px; }
        /* Live utilization summary */
        .utilization-hero { min-height: 190px; padding: 32px; position: relative; overflow: hidden; }
        .utilization-hero:after { content: ""; position: absolute; right: -90px; bottom: -115px; width: 290px; height: 290px; border-radius: 50%; background: rgba(13,91,232,.05); }
        .live-label { color: #0d5be8; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: .9px; }
        .slot-value { margin-top: 10px; color: #172033; font-size: 43px; font-weight: 900; line-height: 1; }
        .slot-value span { color: #7c8da0; font-size: 17px; font-weight: 800; }
        .health-row { display: flex; align-items: center; gap: 10px; margin-top: 22px; }
        /* Weekly trend chart */
        .trend-card { margin-top: 24px; min-height: 310px; padding: 28px; }
        .trend-head { display: flex; justify-content: space-between; align-items: start; gap: 16px; }
        .trend-legend { display: flex; align-items: center; gap: 18px; color: #8a9caf; font-size: 14px; font-weight: 900; text-transform: uppercase; }
        .legend-key { display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
        .legend-swatch { width: 13px; height: 13px; border-radius: 3px; display: inline-block; }
        .legend-swatch.personal { background: #0d5be8; }
        .legend-swatch.department { background: #cbd5e1; }
        .bars { height: 190px; display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; align-items: end; margin-top: 28px; border-bottom: 1px solid #edf2f7; }
        .bar-stack { display: grid; grid-template-columns: auto auto; gap: 7px; align-items: end; justify-content: center; height: 100%; }
        .bar { width: 12px; min-height: 4px; border-radius: 999px 999px 0 0; }
        .bar.personal { background: #0d5be8; }
        .bar.department { background: #cbd5e1; }
        .bar-labels { display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; margin-top: 10px; color: #9aacc0; font-size: 12px; font-weight: 900; text-align: center; }
        /* Benchmark and health panels */
        .benchmark-card, .health-card { padding: 26px; }
        .benchmark-card { margin-bottom: 24px; }
        .benchmark-row { margin-top: 22px; }
        .benchmark-label { color: #526a7f; font-size: 14px; font-weight: 800; display: flex; justify-content: space-between; gap: 10px; }
        .track { height: 8px; border-radius: 999px; background: #e8eef5; overflow: hidden; margin-top: 8px; }
        .track span { display: block; height: 100%; background: #0d5be8; border-radius: inherit; }
        .track.gray span { background: #94a3b8; }
        .health-list { display: grid; gap: 16px; margin-top: 20px; }
        .health-item { border-left: 3px solid #0d5be8; padding-left: 12px; }
        .health-item.green { border-left-color: #22c55e; }
        .health-item.orange { border-left-color: #f59e0b; }
        .health-title { color: #172033; font-size: 14px; font-weight: 900; }
        .health-copy { margin-top: 4px; color: #7c8da0; font-size: 14px; line-height: 1.45; }
        @media (max-width: 980px) { .utilization-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("report-utilization"); ?>
        <main class="main">
            <div class="report-shell">
                <!-- Page Header -->
                <section class="report-head">
                    <div>
                        <h1>Slot Utilization</h1>
                        <p>Monitor current supervision capacity, quota occupancy, and workload trends.</p>
                    </div>
                    <?php echo reportExportMenu("utilization"); ?>
                </section>

                <!-- Utilization Dashboard -->
                <section class="utilization-layout">
                    <div>
                        <!-- Live Utilization Card -->
                        <section class="report-card utilization-hero">
                            <div class="live-label">Live Utilization</div>
                            <div class="slot-value">
                                <?php echo e($utilization["currentSlots"]); ?>/<?php echo e($utilization["quota"]); ?>
                                <span>Slots Filled</span>
                            </div>
                            <div class="health-row">
                                <span class="status-pill <?php echo $utilization["isFull"] ? "gray" : "green"; ?>">
                                    <?php echo $utilization["isFull"] ? "Quota Filled" : "Optimal Utilization"; ?>
                                </span>
                                <span class="stat-note"><?php echo e($utilization["availableSlots"]); ?> slot(s) remain available.</span>
                            </div>
                        </section>

                        <!-- Weekly Trend Card -->
                        <section class="report-card trend-card">
                            <div class="trend-head">
                                <div>
                                    <h2 class="panel-title">Weekly Slot Trends</h2>
                                    <p class="panel-subtitle">Comparison of activity vs availability</p>
                                </div>
                                <div class="trend-legend" aria-label="Chart legend">
                                    <span class="legend-key"><span class="legend-swatch department"></span>Dept Avg</span>
                                    <span class="legend-key"><span class="legend-swatch personal"></span>Personal</span>
                                </div>
                            </div>
                            <div class="bars">
                                <?php foreach ($utilization["weeklyTrend"] as $day): ?>
                                    <?php
                                        $personalHeight = (float) $day["personal"] > 0
                                            ? max(6, min(160, ((float) $day["personal"] / $maxTrendValue) * 160))
                                            : 0;
                                        $departmentHeight = (float) $day["departmentAverage"] > 0
                                            ? max(6, min(160, ((float) $day["departmentAverage"] / $maxTrendValue) * 160))
                                            : 0;
                                    ?>
                                    <div class="bar-stack">
                                        <span class="bar department"
                                              title="Department average: <?php echo e($day["departmentAverage"]); ?>"
                                              style="height: <?php echo e($departmentHeight); ?>px;"></span>
                                        <span class="bar personal"
                                              title="Personal: <?php echo e($day["personal"]); ?>"
                                              style="height: <?php echo e($personalHeight); ?>px;"></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="bar-labels">
                                <?php foreach ($utilization["weeklyTrend"] as $day): ?>
                                    <span><?php echo e($day["label"]); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                    <aside>
                        <!-- Benchmarking Card -->
                        <section class="report-card benchmark-card">
                            <h2 class="panel-title">Benchmarking</h2>
                            <div class="benchmark-row">
                                <div class="benchmark-label">
                                    <span>Your Fill Speed</span>
                                    <strong><?php echo e($utilization["fillRate"]); ?>%</strong>
                                </div>
                                <div class="track"><span style="width: <?php echo e($fillWidth); ?>%;"></span></div>
                            </div>
                            <div class="benchmark-row">
                                <div class="benchmark-label">
                                    <span>Department Avg</span>
                                    <strong><?php echo e($utilization["departmentAverage"]); ?>%</strong>
                                </div>
                                <div class="track gray"><span style="width: <?php echo e($departmentWidth); ?>%;"></span></div>
                            </div>
                            <p class="stat-note" style="margin-top: 18px;">The department average is anonymized and aggregated.</p>
                        </section>

                        <!-- Allocation Health Card -->
                        <section class="report-card health-card">
                            <h2 class="panel-title">Allocation Health</h2>
                            <div class="health-list">
                                <div class="health-item green">
                                    <div class="health-title">Slot Efficiency</div>
                                    <div class="health-copy">Slot utilization is currently <?php echo e($utilization["fillRate"]); ?>% compared to your quota.</div>
                                </div>
                                <div class="health-item">
                                    <div class="health-title">Unused Capacity</div>
                                    <div class="health-copy"><?php echo e($utilization["availableSlots"]); ?> slot(s) remain before the quota is full.</div>
                                </div>
                                <div class="health-item orange">
                                    <div class="health-title">Available Capacity</div>
                                    <div class="health-copy"><?php echo e($utilization["message"] === "" ? "Quota is fully filled." : $utilization["message"]); ?></div>
                                </div>
                            </div>
                        </section>
                    </aside>
                </section>
            </div>
        </main>
    </div>
    <?php echo reportExportScript(); ?>
</body>
</html>
