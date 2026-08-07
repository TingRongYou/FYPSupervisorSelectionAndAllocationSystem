<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/QuotaManager.php";
require_once __DIR__ . "/../shared/accountLayout.php";

// Administrator Access Control
// Ensures only administrators can edit supervisor quota limits.
SessionManager::startSession();
SessionManager::requireRole("Administrator");

// CSRF Token
// Protects quota update forms from cross-site request forgery.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Quota Service
// Loads quota, allocation, programme, and status details for the directory.
$quotaManager = new QuotaManager();

// Filter State
// Keeps search and programme filters selected after applying the directory filter.
$searchName = trim($_GET["searchName"] ?? "");
$selectedProgramme = trim($_GET["programme"] ?? "");
$quotaPage = max(1, (int) ($_GET["quotaPage"] ?? 1));
$recordsPerPage = 3;

$supervisors = $quotaManager->getQuotaDashboard($_GET);
$programmeOptions = $quotaManager->getProgrammeOptions();

// Summary Metric Builder
// Aggregates supervisor quota rows for the hero and status summary panels.
$totalCapacity = 0;
$totalAllocated = 0;
$overCapacityCount = 0;
$validCount = 0;

foreach ($supervisors as $supervisor) {
    $totalCapacity += (int) $supervisor["assignedQuotaLimit"];
    $totalAllocated += (int) $supervisor["currentSupervisees"];

    if ($supervisor["quotaStatus"] === "Over-Capacity") {
        $overCapacityCount++;
    }

    if ($supervisor["quotaStatus"] === "Valid") {
        $validCount++;
    }
}

$totalSupervisors = count($supervisors);
$utilizationRate = $totalCapacity > 0 ? round(($totalAllocated / $totalCapacity) * 100) : 0;
$averageQuota = $totalSupervisors > 0 ? round($totalCapacity / $totalSupervisors, 1) : 0;
$complianceRate = $totalSupervisors > 0 ? round(($validCount / $totalSupervisors) * 100) : 0;
$overCapacityRate = $totalSupervisors > 0 ? round(($overCapacityCount / $totalSupervisors) * 100, 1) : 0;
$quotaTotalPages = max(1, (int) ceil($totalSupervisors / $recordsPerPage));
$quotaPage = min($quotaPage, $quotaTotalPages);
$quotaOffset = ($quotaPage - 1) * $recordsPerPage;
$visibleSupervisors = array_slice($supervisors, $quotaOffset, $recordsPerPage);
$quotaStart = $totalSupervisors === 0 ? 0 : $quotaOffset + 1;
$quotaEnd = min($quotaOffset + count($visibleSupervisors), $totalSupervisors);

// HTML Escape Helper
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Select Option Helper
function selected($left, $right) {
    return (string) $left === (string) $right ? "selected" : "";
}

// Status Message Helper
function statusMessage() {
    if (!isset($_GET["status"], $_GET["message"])) {
        return "";
    }

    $class = $_GET["status"] === "success" ? "success" : "error";

    return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
}

// Status Badge Helper
function statusClass($status) {
    if ($status === "Valid") {
        return "valid";
    }

    return "over";
}

function quotaPageUrl($page, $searchName, $selectedProgramme) {

    $query = ["quotaPage" => max(1, (int) $page)];

    if ($searchName !== "") {
        $query["searchName"] = $searchName;
    }

    if ($selectedProgramme !== "") {
        $query["programme"] = $selectedProgramme;
    }

    return "quotaManagement.php?" . http_build_query($query);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Quota Management", "admin"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="content-shell">
        <aside class="sidebar">
            <div class="role-card">
                <div class="role-icon">A</div>
                <div>
                    <p class="role-title">SSAS Admin</p>
                    <p class="role-subtitle">Management Portal</p>
                </div>
            </div>

            <a class="nav-link" href="adminDashboard.php">Dashboard</a>
            <a class="nav-link" href="supervisorsManagement.php">Supervisors Management</a>
            <a class="nav-link" href="studentEligibility.php">Students Eligibility</a>
            <a class="nav-link active" href="quotaManagement.php">Quota Management</a>
            <a class="nav-link" href="autoAllocation.php">Allocations</a>
            <a class="nav-link" href="adminSupervisorReviews.php">Supervisor Reviews Audit</a>
            <button class="nav-link has-submenu" type="button" aria-expanded="false" aria-controls="admin-report-tree" onclick="toggleAdminReports(this)">
                <span>Reports</span>
                <span class="submenu-caret" aria-hidden="true">v</span>
            </button>
            <div class="report-tree" id="admin-report-tree">
                <a class="report-child" href="adminCohortOverview.php">Cohort Overview</a>
                <a class="report-child" href="adminAllocationSummary.php">Allocation Summary</a>
            </div>
        </aside>

        <main class="main quota-management-main">
            <?php echo statusMessage(); ?>

            <section class="hero">
                <div>
                    <h1>Supervisor Quota Management</h1>
                    <p>Oversee and adjust supervisory capacities. Ensure workload balance while maintaining academic supervision standards.</p>
                </div>
                <div class="hero-metrics">
                    <div class="metric wide">
                        <div class="metric-label">Total Capacity</div>
                        <div class="metric-value"><?php echo e($totalCapacity); ?></div>
                    </div>
                    <div class="metric">
                            <div class="metric-label">Allocation</div>
                            <div class="metric-value"><?php echo e($totalAllocated); ?></div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Compliance</div>
                        <div class="metric-value"><?php echo e($complianceRate); ?>%</div>
                    </div>
                </div>
            </section>

            <div class="quota-grid">
                <section class="panel">
                    <div class="directory-header">
                        <div class="directory-title">
                            <h2>Supervisor Directory</h2>
                            <p>Review current quota limits and adjust capacity by supervisor.</p>
                        </div>
                        <form class="filter-form quota-filter-form" method="GET" action="quotaManagement.php">
                            <input type="hidden" name="quotaPage" value="1">
                            <div class="search-wrap">
                                <label class="sr-only" for="quota-search">Search supervisor</label>
                                <input id="quota-search" type="text" name="searchName" value="<?php echo e($searchName); ?>" placeholder="Filter by name or programme...">
                            </div>
                            <label class="sr-only" for="quota-programme">Programme</label>
                            <select id="quota-programme" name="programme">
                                <option value="">All Programmes</option>
                                <?php foreach ($programmeOptions as $programme): ?>
                                    <option value="<?php echo e($programme["programme"]); ?>" <?php echo selected($selectedProgramme, $programme["programme"]); ?>>
                                        <?php echo e($programme["programme"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button" type="submit">Apply</button>
                            <a class="button secondary" href="quotaManagement.php">Reset</a>
                        </form>
                    </div>

                    <form id="quotaForm" action="../../server/application/admin/updateSupervisorQuota.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <div class="table-head">
                            <div>Supervisor Name</div>
                            <div>Supervisor Details</div>
                            <div>Programme</div>
                            <div>Editable Quota</div>
                            <div>Validation Status</div>
                        </div>

                        <?php if (empty($supervisors)): ?>
                            <div class="empty">No supervisors match the selected criteria.</div>
                        <?php else: ?>
                            <?php foreach ($visibleSupervisors as $supervisor): ?>
                                <?php
                                    $supervisorID = $supervisor["userID"];
                                    $assignedQuota = (int) $supervisor["assignedQuotaLimit"];
                                    $tierMax = (int) $supervisor["classificationQuotaLimit"];
                                    $currentLoad = (int) $supervisor["currentSupervisees"];
                                    $avatarPath = $supervisor["profilePhotoPath"] ?? "";
                                ?>
                                <article class="quota-row" data-row="<?php echo e($supervisorID); ?>">
                                    <div class="supervisor-cell">
                                        <div class="avatar">
                                            <?php if ($avatarPath !== ""): ?>
                                                <img src="<?php echo e($avatarPath); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo e(substr($supervisor["fullName"], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="name"><?php echo e($supervisor["fullName"]); ?></p>
                                        </div>
                                    </div>

                                    <div class="quota-detail-cell">
                                        <p><?php echo e($supervisorID); ?> - <?php echo e($supervisor["employmentCategory"]); ?></p>
                                        <p>Type limit <?php echo e($tierMax); ?>, active <?php echo e($currentLoad); ?></p>
                                    </div>

                                    <div class="programme"><?php echo e($supervisor["programme"]); ?></div>

                                    <div>
                                        <input type="hidden" name="quotaRows[<?php echo e($supervisorID); ?>][quotaID]" value="<?php echo e($supervisor["quotaID"]); ?>">
                                        <input class="quota-input" type="number" min="0" step="1" name="quotaRows[<?php echo e($supervisorID); ?>][assignedQuotaLimit]" value="<?php echo e($assignedQuota); ?>" data-original="<?php echo e($assignedQuota); ?>" data-tier-max="<?php echo e($tierMax); ?>" data-current-load="<?php echo e($currentLoad); ?>">
                                        <div class="quota-hint">Max <?php echo e($tierMax); ?></div>
                                        <input class="changed-flag" type="hidden" name="quotaRows[<?php echo e($supervisorID); ?>][changed]" value="0">
                                    </div>

                                    <div>
                                        <span class="badge <?php echo e(statusClass($supervisor["quotaStatus"])); ?>" data-status-badge>
                                            <?php echo e($supervisor["quotaStatus"]); ?>
                                        </span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <div class="showing pagination-note">
                                <span>Showing <?php echo e($quotaStart); ?>-<?php echo e($quotaEnd); ?> of <?php echo e($totalSupervisors); ?> supervisors</span>
                                <div class="table-pager" aria-label="Supervisor quota directory pagination">
                                    <?php if ($quotaPage > 1): ?>
                                        <a class="table-page-button" href="<?php echo e(quotaPageUrl($quotaPage - 1, $searchName, $selectedProgramme)); ?>" aria-label="Previous quota directory page">&lt;</a>
                                    <?php else: ?>
                                        <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                    <?php endif; ?>

                                    <span class="table-page-count">Page <?php echo e($quotaPage); ?> of <?php echo e($quotaTotalPages); ?></span>

                                    <?php if ($quotaPage < $quotaTotalPages): ?>
                                        <a class="table-page-button" href="<?php echo e(quotaPageUrl($quotaPage + 1, $searchName, $selectedProgramme)); ?>" aria-label="Next quota directory page">&gt;</a>
                                    <?php else: ?>
                                        <span class="table-page-button disabled" aria-hidden="true">&gt;</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </section>

                <aside class="status-card quota-summary-card">
                    <h2>Status Summary</h2>
                    <div class="summary-list">
                        <div class="summary-item">
                            <div class="summary-label">Total Supervisors</div>
                            <div class="summary-value"><?php echo e($totalSupervisors); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Average Quota Usage</div>
                            <div class="summary-value"><?php echo e($averageQuota); ?></div>
                        </div>
                        <div class="summary-item danger">
                            <div class="summary-label">Over Capacity Supervisors</div>
                            <div class="summary-value danger">
                                <?php echo e($overCapacityCount); ?>
                                <span class="summary-note">(<?php echo e($overCapacityRate); ?>%)</span>
                            </div>
                            <div class="danger-line"><span style="width: <?php echo e($overCapacityRate); ?>%;"></span></div>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <div class="save-bar" id="saveBar">
        <div class="save-summary">
            <div class="save-icon">*</div>
            <div>
                <p class="save-title"><span id="modifiedCount">0</span> modified field(s)</p>
                <p class="save-subtitle">Unsaved changes will be lost</p>
            </div>
        </div>
        <button class="button secondary" type="button" id="discardButton">Discard</button>
        <button class="button" type="submit" form="quotaForm" id="saveButton">Save Quotas</button>
    </div>
</body>
</html>
