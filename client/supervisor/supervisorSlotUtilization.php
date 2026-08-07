<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorReportFacade.php";
require_once __DIR__ . "/../shared/accountLayout.php";
require_once __DIR__ . "/supervisorReportComponents.php";

// Supervisor Access Control
// Restricts utilization metrics to the signed-in supervisor account.
SessionManager::startSession();
SessionManager::requireRole("Supervisor");

// Report Facade
// Coordinates quota, allocation, and weekly trend data through the facade layer.
$reportFacade = new SupervisorReportFacade();

$utilization = $reportFacade->getUtilizationStats($_SESSION["userID"]);

// Benchmark Widths
// Clamps percentage bars so visual progress never exceeds the track.
$fillWidth = min(100, max(0, $utilization["fillRate"]));
$departmentWidth =min(100, max(0, $utilization["departmentAverage"]));
$maxTrendValue =max(1, (float) ($utilization["maxTrendValue"] ?? 1));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Slot Utilization", "supervisor"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo ssasPortalSidebar("report-utilization"); ?>
        <main class="main">
            <div class="report-shell">
                <section class="report-head report-hero">
                    <div>
                        <div class="eyebrow">Supervisor Capacity</div>
                        <h1>Slot Utilization</h1>
                        <p>Monitor current supervision capacity, quota occupancy, and workload trends.</p>
                    </div>
                </section>

                <div class="report-toolbar utilization-toolbar">
                    <div class="toolbar-note">Current workload snapshot</div>
                    <?php echo reportExportMenu("utilization"); ?>
                </div>

                <section class="utilization-layout">
                    <div>
                        <section class="report-card utilization-hero">
                            <div class="live-label">Live Utilization</div>
                            <div class="slot-value">
                                <?php echo ssasEscape($utilization["currentSlots"]); ?>/<?php echo ssasEscape($utilization["quota"]); ?>
                                <span>Slots Filled</span>
                            </div>
                            <div class="health-row">
                                <span class="status-pill <?php echo ssasEscape($utilization["isFull"] ? "gray" : "green"); ?>">
                                    <?php echo ssasEscape($utilization["isFull"] ? "Quota Filled" : "Optimal Utilization"); ?>
                                </span>
                                <span class="stat-note"><?php echo ssasEscape($utilization["availableSlots"]); ?> slot(s) remain available.</span>
                            </div>
                        </section>

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
                                              title="Department average: <?php echo ssasEscape($day["departmentAverage"]); ?>"
                                              style="height: <?php echo ssasEscape($departmentHeight); ?>px;"></span>
                                        <span class="bar personal"
                                              title="Personal: <?php echo ssasEscape($day["personal"]); ?>"
                                              style="height: <?php echo ssasEscape($personalHeight); ?>px;"></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="bar-labels">
                                <?php foreach ($utilization["weeklyTrend"] as $day): ?>
                                    <span><?php echo ssasEscape($day["label"]); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                    <aside>
                        <section class="report-card benchmark-card">
                            <h2 class="panel-title">Benchmarking</h2>
                            <div class="benchmark-row">
                                <div class="benchmark-label">
                                    <span>Your Fill Speed</span>
                                    <strong><?php echo ssasEscape($utilization["fillRate"]); ?>%</strong>
                                </div>
                                <div class="track"><span style="width: <?php echo ssasEscape($fillWidth); ?>%;"></span></div>
                            </div>
                            <div class="benchmark-row">
                                <div class="benchmark-label">
                                    <span>Department Avg</span>
                                    <strong><?php echo ssasEscape($utilization["departmentAverage"]); ?>%</strong>
                                </div>
                                <div class="track gray"><span style="width: <?php echo ssasEscape($departmentWidth); ?>%;"></span></div>
                            </div>
                            <p class="stat-note" style="margin-top: 18px;">The department average is anonymized and aggregated.</p>
                        </section>

                        <section class="report-card health-card">
                            <h2 class="panel-title">Allocation Health</h2>
                            <div class="health-list">
                                <div class="health-item green">
                                    <div class="health-title">Slot Efficiency</div>
                                    <div class="health-copy">Slot utilization is currently <?php echo ssasEscape($utilization["fillRate"]); ?>% compared to your quota.</div>
                                </div>
                                <div class="health-item">
                                    <div class="health-title">Unused Capacity</div>
                                    <div class="health-copy"><?php echo ssasEscape($utilization["availableSlots"]); ?> slot(s) remain before the quota is full.</div>
                                </div>
                                <div class="health-item orange">
                                    <div class="health-title">Available Capacity</div>
                                    <div class="health-copy"><?php echo ssasEscape($utilization["message"] === "" ? "Quota is fully filled." : $utilization["message"]); ?></div>
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
