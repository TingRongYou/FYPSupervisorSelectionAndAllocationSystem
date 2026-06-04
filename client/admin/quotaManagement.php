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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quota Management | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        /* Global page reset */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #1d2b3a; }
        .content-shell { display: flex; min-height: calc(100vh - 52px); }
        .sidebar { width: 212px; flex: 0 0 212px; background: #fff; border-right: 1px solid #dce8f3; padding: 16px 10px; }
        .role-card { display: flex; gap: 10px; align-items: center; padding: 6px 9px 14px; margin-bottom: 8px; }
        .role-icon { width: 30px; height: 30px; border-radius: 7px; background: #0d5be8; color: #fff; display: grid; place-items: center; font-size: 12px; font-weight: 900; }
        .role-title { margin: 0; color: #10263d; font-weight: 900; font-size: 14px; }
        .role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 10px; }
        .nav-link { display: flex; align-items: center; gap: 8px; color: #526a7f; text-decoration: none; padding: 9px 10px; border-radius: 6px; margin-bottom: 5px; font-size: 12px; background: #f1f5f9; min-height: 32px; }
        .nav-link:hover, .nav-link.active { background: #eaf3ff; color: #0d5be8; }
        .nav-icon { width: 16px; height: 16px; border-radius: 4px; border: 1px solid #c7d9ee; background: #fff; position: relative; }
        .nav-icon:before { content: ""; position: absolute; inset: 4px; border: 1px solid #7d96b4; border-radius: 2px; }
        .nav-text { flex: 1; }
        .nav-chevron { color: #315e8c; font-weight: 900; }
        .sidebar { width: 280px; flex: 0 0 280px; border-right: 1px solid #dde8f2; padding: 26px 18px; }
        .role-card { gap: 12px; padding: 12px; border-radius: 8px; background: #eef6fc; margin-bottom: 20px; }
        .role-icon { width: 36px; height: 36px; border-radius: 8px; font-size: 15px; }
        .role-title { font-size: 14px; }
        .role-subtitle { font-size: 12px; }
        .nav-link { gap: 10px; padding: 12px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; font-weight: 400; background: transparent; min-height: 0; transition: background .2s, color .2s, transform .2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { color: #0b66d8; transform: translateX(2px); }
        .nav-icon, .nav-chevron { display: none; }
        .sidebar .role-card { min-height: 62px; }
        .sidebar .role-icon { width: 38px; height: 38px; font-size: 15px; font-weight: 800; }
        .sidebar .role-title { font-size: 14px; font-weight: 800; }
        .sidebar .role-subtitle { font-size: 12px; font-weight: 400; text-transform: none; letter-spacing: 0; }
        .sidebar .nav-link,
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active { min-height: 40px; padding: 12px 14px; margin-bottom: 8px; border-radius: 8px; font-size: 14px; font-weight: 600; line-height: 1.2; white-space: nowrap; }
        /* Main content area */
        .main { flex: 1; padding: 26px 28px 92px; max-width: 100%; }
        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        /* Hero quota summary */
        .hero { background: #2268f2; border-radius: 14px; color: #fff; min-height: 172px; padding: 28px 30px; display: grid; grid-template-columns: 1fr 250px; gap: 26px; align-items: center; box-shadow: 0 14px 28px rgba(13,91,232,.22); margin-bottom: 22px; }
        .hero h1 { margin: 0 0 12px; font-size: 28px; font-weight: 500; }
        .hero p { margin: 0; color: #dbe9ff; line-height: 1.55; max-width: 530px; }
        .hero-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .metric { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.13); border-radius: 10px; padding: 15px; }
        .metric.wide { grid-column: span 2; }
        .metric-label { color: #bad3ff; font-size: 10px; text-transform: uppercase; letter-spacing: 1.1px; font-weight: 800; }
        .metric-value { margin-top: 6px; font-size: 24px; font-weight: 900; }
        /* Directory and side summary grid */
        .quota-grid { display: grid; grid-template-columns: minmax(0, 1fr) 230px; gap: 22px; align-items: start; }
        .panel, .status-card { background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; box-shadow: 0 10px 24px rgba(11,79,138,.08); }
        .panel { overflow: hidden; }
        /* Directory filter header */
        .directory-header { display: flex; gap: 14px; align-items: center; justify-content: space-between; padding: 22px 22px 16px; }
        .directory-title h2, .status-card h2 { margin: 0; color: #10263d; font-size: 20px; }
        .filter-form { display: flex; gap: 10px; align-items: center; }
        .search-wrap { position: relative; }
        .search-wrap:before { content: ""; position: absolute; left: 12px; top: 50%; width: 10px; height: 10px; border: 2px solid #8ca1b6; border-radius: 50%; transform: translateY(-50%); }
        .search-wrap:after { content: ""; position: absolute; left: 22px; top: 25px; width: 6px; height: 2px; background: #8ca1b6; transform: rotate(45deg); }
        input, select { height: 38px; border: 1px solid #dbe6f0; border-radius: 7px; background: #f6f8fb; color: #1d2b3a; padding: 0 12px; font-size: 14px; }
        .search-wrap input { width: 210px; padding-left: 34px; }
        .filter-form select { width: 170px; }
        .button { border: 0; min-height: 38px; border-radius: 7px; padding: 0 16px; background: #0d5be8; color: #fff; font-weight: 800; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .button.secondary { background: #e9edf2; color: #2f4053; }
        /* Supervisor quota table */
        .table-head, .quota-row { display: grid; grid-template-columns: 1.25fr .82fr .62fr .88fr; gap: 16px; align-items: center; }
        .table-head { padding: 13px 22px; background: #fbfdff; color: #8a9caf; font-size: 11px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7; }
        .quota-row { padding: 17px 22px; border-bottom: 1px solid #edf2f7; min-height: 78px; }
        .quota-row:last-child { border-bottom: 0; }
        .supervisor-cell { display: flex; gap: 12px; align-items: center; min-width: 0; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #26384c; color: #fff; display: grid; place-items: center; font-size: 11px; font-weight: 900; flex: 0 0 auto; }
        .avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block; }
        .name { margin: 0; color: #10263d; font-weight: 900; font-size: 15px; }
        .meta { margin: 3px 0 0; color: #6b7f91; font-size: 12px; line-height: 1.35; }
        .programme { color: #526a7f; font-size: 14px; line-height: 1.4; font-weight: 700; }
        .quota-input { width: 66px; height: 38px; text-align: center; color: #0d5be8; font-weight: 900; background: #fff; font-size: 15px; }
        .quota-hint { margin-top: 7px; color: #7a8da0; font-size: 12px; line-height: 1.3; }
        .quota-input.valid-field { border-color: #a9dfbf; background: #f2fbf6; color: #177345; }
        .quota-input.invalid-field { border-color: #f0b8b8; background: #fff5f5; color: #b42318; }
        .badge { display: inline-flex; min-width: 76px; align-items: center; justify-content: center; border-radius: 999px; padding: 8px 12px; font-size: 12px; font-weight: 900; line-height: 1.1; text-align: center; }
        .badge.valid { background: #dcfce7; color: #118549; }
        .badge.full { background: #fff4d6; color: #876000; }
        .badge.over { background: #fee2e2; color: #b42318; }
        .empty { padding: 28px; color: #526a7f; text-align: center; }
        .showing { padding: 13px 22px 18px; color: #6b7f91; font-size: 13px; }
        /* Status summary panel */
        .status-card { padding: 22px; }
        .status-card h2 { display: flex; align-items: center; gap: 8px; }
        .status-icon { width: 14px; height: 14px; border: 1px solid #0d5be8; border-radius: 3px; display: inline-block; position: relative; }
        .status-icon:before { content: ""; position: absolute; left: 3px; right: 3px; bottom: 3px; height: 6px; border-left: 2px solid #0d5be8; border-right: 2px solid #0d5be8; }
        .summary-list { display: grid; gap: 18px; margin-top: 20px; }
        .summary-item { border-left: 2px solid #0d5be8; padding-left: 12px; }
        .summary-item.danger { border-left-color: #e33434; }
        .summary-label { color: #8a9caf; text-transform: uppercase; letter-spacing: 1px; font-size: 11px; font-weight: 900; }
        .summary-value { color: #10263d; font-size: 30px; margin-top: 5px; }
        .summary-value.danger { color: #d12f2f; font-weight: 900; }
        .summary-note { color: #6b7f91; font-size: 13px; margin-left: 4px; }
        .danger-line { height: 4px; border-radius: 999px; background: #ffe0e0; margin-top: 8px; overflow: hidden; }
        .danger-line span { display: block; height: 100%; background: #d12f2f; width: <?php echo e($overCapacityRate); ?>%; }
        /* Sticky save bar */
        .save-bar { position: fixed; right: 26px; bottom: 24px; z-index: 20; display: none; align-items: center; gap: 18px; background: rgba(255,255,255,.96); border: 1px solid #d9e7f3; border-radius: 14px; box-shadow: 0 14px 34px rgba(37,55,82,.22); padding: 12px 14px; }
        .save-bar.show { display: flex; }
        .save-summary { display: flex; gap: 10px; align-items: center; min-width: 190px; }
        .save-icon { width: 30px; height: 30px; border-radius: 50%; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-weight: 900; }
        .save-title { margin: 0; color: #10263d; font-weight: 900; font-size: 14px; }
        .save-subtitle { margin: 2px 0 0; color: #7a8da0; font-size: 12px; }
        @media (max-width: 1100px) { .hero, .quota-grid { grid-template-columns: 1fr; } .status-card { order: -1; } }
        @media (max-width: 880px) { .content-shell { display: block; } .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #dce8f3; } .main { padding: 20px 18px 100px; } .directory-header, .filter-form { display: grid; grid-template-columns: 1fr; align-items: stretch; } .search-wrap input, .filter-form select { width: 100%; } .table-head { display: none; } .quota-row { grid-template-columns: 1fr; gap: 10px; } .hero-metrics { grid-template-columns: 1fr; } .metric.wide { grid-column: span 1; } .save-bar { left: 16px; right: 16px; } }
    </style>
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
            <a class="nav-link" href="adminCohortOverview.php">Reports</a>
        </aside>

        <main class="main">
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
                        </div>
                        <form class="filter-form" method="GET" action="quotaManagement.php">
                            <div class="search-wrap">
                                <input type="text" name="searchName" value="<?php echo e($searchName); ?>" placeholder="Filter by name or programme...">
                            </div>
                            <select name="programme">
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
                            <div>Programme</div>
                            <div>Editable Quota</div>
                            <div>Validation Status</div>
                        </div>

                        <?php if (empty($supervisors)): ?>
                            <div class="empty">No supervisors match the selected criteria.</div>
                        <?php else: ?>
                            <?php foreach ($supervisors as $supervisor): ?>
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
                                            <p class="meta">
                                                <?php echo e($supervisorID); ?> - <?php echo e($supervisor["employmentCategory"]); ?><br>
                                                Type limit <?php echo e($tierMax); ?>, active <?php echo e($currentLoad); ?>
                                            </p>
                                        </div>
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
                            <div class="showing">Showing <?php echo e($totalSupervisors); ?> of <?php echo e($totalSupervisors); ?> supervisors</div>
                        <?php endif; ?>
                    </form>
                </section>

                <aside class="status-card">
                    <h2><span class="status-icon"></span>Status Summary</h2>
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
                            <div class="danger-line"><span></span></div>
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

    <script>
        const quotaInputs = Array.from(document.querySelectorAll(".quota-input"));
        const saveBar = document.getElementById("saveBar");
        const modifiedCount = document.getElementById("modifiedCount");
        const discardButton = document.getElementById("discardButton");
        const saveButton = document.getElementById("saveButton");

        function validateInput(input) {
            const rawValue = input.value.trim();
            const value = Number(rawValue);
            const original = Number(input.dataset.original);
            const tierMax = Number(input.dataset.tierMax);
            const currentLoad = Number(input.dataset.currentLoad);
            const row = input.closest(".quota-row");
            const badge = row.querySelector("[data-status-badge]");
            const changedFlag = row.querySelector(".changed-flag");
            const changed = value !== original;
            const invalid = rawValue === "" || !Number.isInteger(value) || value < currentLoad || value > tierMax;

            changedFlag.value = changed ? "1" : "0";
            input.classList.toggle("valid-field", changed && !invalid);
            input.classList.toggle("invalid-field", invalid);

            if (invalid) {
                badge.textContent = "Over-Capacity";
                badge.className = "badge over";
            } else {
                badge.textContent = "Valid";
                badge.className = "badge valid";
            }

            return { changed, invalid };
        }

        function refreshSaveBar() {
            let changedTotal = 0;
            let invalidTotal = 0;

            quotaInputs.forEach(function(input) {
                const result = validateInput(input);

                if (result.changed) {
                    changedTotal++;
                }

                if (result.invalid) {
                    invalidTotal++;
                }
            });

            modifiedCount.textContent = changedTotal;
            saveBar.classList.toggle("show", changedTotal > 0);
            saveButton.disabled = invalidTotal > 0;
            saveButton.style.opacity = invalidTotal > 0 ? ".55" : "1";
            saveButton.style.cursor = invalidTotal > 0 ? "not-allowed" : "pointer";
        }

        quotaInputs.forEach(function(input) {
            input.addEventListener("input", refreshSaveBar);
        });

        discardButton.addEventListener("click", function() {
            quotaInputs.forEach(function(input) {
                input.value = input.dataset.original;
            });

            refreshSaveBar();
        });

        document.getElementById("quotaForm").addEventListener("submit", function(event) {
            refreshSaveBar();

            if (saveButton.disabled) {
                event.preventDefault();
                alert("Quota invalid: the supervisor quota limit is empty, exceeds the supervisor type limit, or is below the current student count.");
                return;
            }

            if (!confirm("Confirm quota limit update?")) {
                event.preventDefault();
            }
        });

        refreshSaveBar();
    </script>
</body>
</html>
