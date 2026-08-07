<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/AdminReportFacade.php";
require_once __DIR__ . "/../shared/accountLayout.php";
require_once __DIR__ . "/adminReportComponents.php";

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators may generate the allocation summary report.
*/

SessionManager::startSession();
SessionManager::requireRole("Administrator");

/*
|--------------------------------------------------------------------------
| Report Facade
|--------------------------------------------------------------------------
| Uses the facade pattern to keep page rendering separate from data assembly.
*/

$reportFacade = new AdminReportFacade();

/*
|--------------------------------------------------------------------------
| Report Filter
|--------------------------------------------------------------------------
| UC301 filters supervisor workload by programme when selected.
*/

$programme = trim($_GET["programme"] ?? "");
$rosterPage = max(1, (int) ($_GET["rosterPage"] ?? 1));
$recordsPerPage = 3;
$report = $reportFacade->getAllocationSummary($programme);
$supervisorTotal = count($report["supervisors"]);
$supervisorTotalPages = max(1,(int) ceil($supervisorTotal / $recordsPerPage));
$rosterPage =min($rosterPage, $supervisorTotalPages);
$supervisorOffset = ($rosterPage - 1) * $recordsPerPage;
$visibleSupervisors = array_slice($report["supervisors"], $supervisorOffset, $recordsPerPage);
$supervisorStart = $supervisorTotal === 0 ? 0 : $supervisorOffset + 1;
$supervisorEnd = min($supervisorTotal, $supervisorOffset + count($visibleSupervisors));

/*
|--------------------------------------------------------------------------
| Page Helpers
|--------------------------------------------------------------------------
*/

function selected($left, $right) {
    return (string) $left === (string) $right ? "selected" : "";
}

// Maps workload status values to CSS classes used by the roster item.
function capacityClass($status) {
    if ($status === "Full Capacity") {
        return "full";
    }

    if ($status === "High Usage") {
        return "high";
    }

    return "";
}

function allocationRosterPageUrl($page, $programme) {
    $query = ["rosterPage" => max(1, (int) $page)];

    if ($programme !== "") {
        $query["programme"] = $programme;
    }

    return "adminAllocationSummary.php?" . http_build_query($query);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Allocation Summary", "admin"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <!-- Page shell: shared topbar, report sidebar, and main report content. -->
    <div class="content-shell">
        <?php echo ssasPortalSidebar("report-allocation"); ?>

        <main class="main allocation-summary-main">
            <div class="report-shell">
                <section class="hero-card allocation-title-hero">
                    <div>
                        <span class="hero-kicker">Admin Reports</span>
                        <h1>Allocation Summary</h1>
                        <p>Monitor supervisor quota utilization, pending workload, and capacity risks for administrative review.</p>
                    </div>
                </section>

                <div class="report-toolbar">
                    <?php echo adminReportExportMenu("allocation", ["programme" => $programme]); ?>
                </div>

                <!-- UC301 summary metrics: utilization, capacity risk, pending work. -->
                <section class="capacity-grid">
                    <div class="summary-card primary">
                        <div class="progress-label">Slot Utilization</div>
                        <div class="summary-number"><?php echo ssasEscape($report["slotUtilization"]); ?>%</div>
                        <p class="note">Allocated Slots: <?php echo ssasEscape($report["allocatedTotal"]); ?><br>Total Capacity: <?php echo ssasEscape($report["totalCapacity"]); ?></p>
                        <div class="meter"><span style="width: <?php echo ssasEscape(min($report["slotUtilization"], 100)); ?>%;"></span></div>
                    </div>
                    <div class="summary-card">
                        <div class="progress-label">Supervisors at Capacity</div>
                        <div class="progress-value"><?php echo ssasEscape($report["atCapacity"]); ?></div>
                        <p class="note">Highlighted where current allocations reach 100% of quota.</p>
                    </div>
                    <div class="summary-card">
                        <div class="progress-label">Pending Requests</div>
                        <div class="progress-value"><?php echo ssasEscape($report["pendingRequests"]); ?></div>
                        <p class="note">Requests still waiting for supervisor decision.</p>
                    </div>
                </section>

                <!-- Supervisor capacity roster from allocation and quota records. -->
                <section class="table-card allocation-roster-card">
                    <div class="table-headline">
                        <div>
                            <h2>Supervisor Capacity Roster</h2>
                            <p>Real-time supervisor load from allocation and quota records.</p>
                        </div>
                        <form class="filter-form allocation-filter-form" method="GET" action="adminAllocationSummary.php">
                            <input type="hidden" name="rosterPage" value="1">
                            <div class="filter-field">
                                <label>Programme</label>
                                <select name="programme">
                                    <option value="">All Programmes</option>
                                    <?php foreach ($report["programmeOptions"] as $option): ?>
                                        <option value="<?php echo ssasEscape($option["programme"]); ?>" <?php echo selected($programme, $option["programme"]); ?>>
                                            <?php echo ssasEscape($option["programme"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="button" type="submit">Apply</button>
                        </form>
                    </div>

                    <?php if ($report["message"] !== ""): ?>
                        <div class="empty-message"><?php echo ssasEscape($report["message"]); ?></div>
                    <?php else: ?>
                        <div class="roster-list allocation-roster-list">
                            <?php foreach ($visibleSupervisors as $supervisor): ?>
                                <?php
                                    $statusClass = capacityClass($supervisor["capacityStatus"]);
                                    $fillRate = (float) $supervisor["fillRate"];
                                ?>
                                <article class="roster-item <?php echo ssasEscape($statusClass); ?>">
                                    <div class="person-cell">
                                        <div class="avatar">
                                            <?php if (!empty($supervisor["profilePhotoPath"])): ?>
                                                <img src="<?php echo ssasEscape($supervisor["profilePhotoPath"]); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo ssasEscape(ssasInitials($supervisor["fullName"])); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="name"><?php echo ssasEscape($supervisor["fullName"]); ?></p>
                                            <p class="meta"><?php echo ssasEscape($supervisor["programme"]); ?></p>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="load-label <?php echo ssasEscape($statusClass); ?>">
                                            <span><?php echo ssasEscape($supervisor["capacityStatus"]); ?></span>
                                            <span><?php echo ssasEscape($supervisor["currentTotal"]); ?> / <?php echo ssasEscape($supervisor["maxSuperviseesAllowed"]); ?></span>
                                        </div>
                                        <div class="meter">
                                            <span style="width: <?php echo ssasEscape(min($fillRate, 100)); ?>%; background: <?php echo $statusClass === "full" ? "#dc2626" : ($statusClass === "high" ? "#b45309" : "#0d5be8"); ?>;"></span>
                                        </div>
                                    </div>

                                    <div class="last-active">
                                        Last Active<br><?php echo ssasEscape(adminLastActiveLabel($supervisor["lastAllocationDate"])); ?>
                                    </div>

                                    <div class="roster-status-chip <?php echo ssasEscape($statusClass); ?>">
                                        <?php echo ssasEscape($supervisor["capacityStatus"]); ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="pagination-note">
                            <span>Showing <?php echo ssasEscape($supervisorStart); ?>-<?php echo ssasEscape($supervisorEnd); ?> of <?php echo ssasEscape($supervisorTotal); ?> supervisors</span>
                            <div class="table-pager" aria-label="Supervisor capacity roster pagination">
                                <?php if ($rosterPage > 1): ?>
                                    <a class="table-page-button" href="<?php echo ssasEscape(allocationRosterPageUrl($rosterPage - 1, $programme)); ?>" aria-label="Previous supervisor roster page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo ssasEscape($rosterPage); ?> of <?php echo ssasEscape($supervisorTotalPages); ?></span>

                                <?php if ($rosterPage < $supervisorTotalPages): ?>
                                    <a class="table-page-button" href="<?php echo ssasEscape(allocationRosterPageUrl($rosterPage + 1, $programme)); ?>" aria-label="Next supervisor roster page">&gt;</a>
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
