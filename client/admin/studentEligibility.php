<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/EligibilityService.php";
require_once __DIR__ . "/../shared/accountLayout.php";

// Administrator Access Control
SessionManager::startSession();
SessionManager::requireRole("Administrator");

$csrfToken = SessionManager::getCsrfToken();

$eligibilityService = new EligibilityService();

$students           = $eligibilityService->getEligibilityDashboard([]);
$summary            = $eligibilityService->getEligibilitySummary();
$rules              = $eligibilityService->getEligibilityRules();

// Eligibility filter: all | eligible | ineligible
$filterStatus = trim($_GET["filterStatus"] ?? "all");
if (!in_array($filterStatus, ["all", "eligible", "ineligible"])) {
    $filterStatus = "all";
}

// Apply filter before pagination
$filteredStudents = $students;
if ($filterStatus === "eligible") {
    $filteredStudents = array_values(array_filter($students, fn($s) => (bool) $s["eligibilityStatus"]));
} elseif ($filterStatus === "ineligible") {
    $filteredStudents = array_values(array_filter($students, fn($s) => !(bool) $s["eligibilityStatus"]));
}

$currentPage        = max(1, (int) ($_GET["page"] ?? 1));
$rowsPerPage        = 10;

$totalStudents      = (int) ($summary["totalStudents"]      ?? 0);
$eligibleStudents   = (int) ($summary["eligibleStudents"]   ?? 0);
$ineligibleStudents = (int) ($summary["ineligibleStudents"] ?? max(0, $totalStudents - $eligibleStudents));
$eligibleRate       = (int) ($summary["eligibleRate"]       ?? 0);

$totalFiltered      = count($filteredStudents);
$totalPages         = max(1, (int) ceil($totalFiltered / $rowsPerPage));
$currentPage        = min($currentPage, $totalPages);
$visibleStudents    = array_slice($filteredStudents, ($currentPage - 1) * $rowsPerPage, $rowsPerPage);
$firstVisibleEntry  = empty($visibleStudents) ? 0 : (($currentPage - 1) * $rowsPerPage) + 1;
$lastVisibleEntry   = empty($visibleStudents) ? 0 : $firstVisibleEntry + count($visibleStudents) - 1;
$uploadedFileName   =
    trim($_GET["uploadedFile"] ?? ($_SESSION["eligibility_csv_file_name"] ?? ""));

$hasUploadedEligibilityCSV =
    !empty($_SESSION["eligibility_csv_uploaded"]);

$semesterOptions = [
    "Y1S1",
    "Y1S2",
    "Y1S3",
    "Y2S1",
    "Y2S2",
    "Y2S3",
    "Y3S1",
    "Y3S2",
    "Y3S3"
];

$academicStatusOptions = [
    "EF" => "EF",
    "EN" => "EN",
    "EP" => "EP"
];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function statusMessage() {
    if (!isset($_GET["status"], $_GET["message"])) return "";
    $class = $_GET["status"] === "success" ? "success" : "error";
    return '<div class="message ' . $class . '">' . e($_GET["message"]) . '</div>';
}

function studentInitials($name) {
    $parts  = preg_split("/\s+/", trim((string) $name));
    $first  = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "",  0, 1));
    return $first . $second;
}

function selected($a, $b) {
    return (string) $a === (string) $b ? "selected" : "";
}

function pageUrl($page, $filterStatus = "all") {
    $q = ["page" => max(1, (int) $page)];
    if ($filterStatus !== "all") {
        $q["filterStatus"] = $filterStatus;
    }
    return "studentEligibility.php?" . http_build_query($q);
}

function filterUrl($status) {
    if ($status === "all") return "studentEligibility.php";
    return "studentEligibility.php?filterStatus=" . urlencode($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Eligibility | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/admin.css"); ?>">
    <script src="../assets/js/admin.js" defer></script>
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
            <a class="nav-link active" href="studentEligibility.php">Students Eligibility</a>
            <a class="nav-link" href="quotaManagement.php">Quota Management</a>
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

        <main class="main eligibility-main">
            <?php echo statusMessage(); ?>

            <div class="page-grid">
                <div class="left-stack">
                    <article class="hero">
                        <div>
                            <h1>Student Eligibility Management</h1>
                            <p>Verify and manage student status against university criteria.</p>
                        </div>
                        <div class="hero-actions">
                            <form action="../../server/application/admin/runEligibilityBatch.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <button class="btn btn-light" type="submit" <?php echo $hasUploadedEligibilityCSV ? "" : "disabled"; ?>>
                                    Run Eligibility Batch
                                </button>
                            </form>
                        </div>
                        <div class="hero-metrics">
                            <div class="metric">
                                <div class="metric-label">Total Checked</div>
                                <div class="metric-value"><?php echo e(number_format($totalStudents)); ?></div>
                            </div>
                            <div class="metric eligible-metric">
                                <div class="metric-label">Eligible Students</div>
                                <div class="metric-value"><?php echo e(number_format($eligibleStudents)); ?></div>
                            </div>
                        </div>
                    </article>

                    <section class="panel criteria-panel eligibility-criteria-panel">
                        <div class="panel-title">
                            <h2><span class="title-mark"></span> Active Criteria</h2>
                            <button class="edit-link" id="editRulesButton" type="button">Edit Rules</button>
                        </div>

                        <div class="criteria-grid">
                            <div class="criteria-card">
                                <div class="criteria-icon">C</div>
                                <div>
                                    <p class="criteria-label">Minimum CGPA</p>
                                    <p class="criteria-value">Greater than <?php echo e(number_format((float) $rules["minimumCGPA"], 2)); ?></p>
                                </div>
                            </div>
                            <div class="criteria-card">
                                <div class="criteria-icon">S</div>
                                <div>
                                    <p class="criteria-label">Current Semester</p>
                                    <p class="criteria-value">Next sem <?php echo e($rules["requiredNextSemester"]); ?></p>
                                </div>
                            </div>
                            <div class="criteria-card">
                                <div class="criteria-icon">F</div>
                                <div>
                                    <p class="criteria-label">Academic Status</p>
                                    <p class="criteria-value">Not equal to <?php echo e($rules["blockedAcademicStatus"]); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="upload-strip">
                            <div class="upload-guidance">
                                <strong>CSV required columns</strong>
                                <div class="csv-field-list" aria-label="Required CSV columns">
                                    <span>studentID</span>
                                    <span>universityEmail</span>
                                    <span>malaysiaICNumber</span>
                                    <span>fullName</span>
                                    <span>programme</span>
                                    <span>cgpa</span>
                                    <span>currentSem</span>
                                    <span>academicStatus</span>
                                </div>
                            </div>
                            <form class="upload-control" action="../../server/application/admin/uploadStudentEligibilityCSV.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <span class="file-name" id="fileName"><?php echo e($uploadedFileName !== "" ? $uploadedFileName : "No file uploaded"); ?></span>
                                <input type="file" id="studentCSV" name="studentCSV" accept=".csv,text/csv" required>
                                <button class="btn btn-secondary btn-upload" id="uploadButton" type="button">Upload CSV</button>
                            </form>
                        </div>

                        <div class="rules-editor" id="rulesEditor">
                            <form class="rules-form" action="../../server/application/admin/updateEligibilityRules.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <div>
                                    <label for="minimumCGPA">Minimum CGPA</label>
                                    <input type="number" id="minimumCGPA" name="minimumCGPA" min="0" max="4" step="0.01" value="<?php echo e(number_format((float) $rules["minimumCGPA"], 2)); ?>" required>
                                </div>
                                <div>
                                    <label for="requiredNextSemester">Required Next Semester</label>
                                    <select id="requiredNextSemester" name="requiredNextSemester" required>
                                        <?php foreach ($semesterOptions as $semesterOption): ?>
                                            <option value="<?php echo e($semesterOption); ?>" <?php echo selected($rules["requiredNextSemester"], $semesterOption); ?>>
                                                <?php echo e($semesterOption); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="blockedAcademicStatus">Blocked Status</label>
                                    <select id="blockedAcademicStatus" name="blockedAcademicStatus" required>
                                        <?php foreach ($academicStatusOptions as $statusValue => $statusLabel): ?>
                                            <option value="<?php echo e($statusValue); ?>" <?php echo selected($rules["blockedAcademicStatus"], $statusValue); ?>>
                                                <?php echo e($statusLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="rules-actions">
                                    <button class="btn btn-primary" type="submit">Save Rules</button>
                                    <button class="btn btn-secondary" id="cancelRulesButton" type="button">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="panel eligibility-results-panel">
                        <div class="results-header">
                            <h2>Batch Processing Results</h2>
                            <div class="filter-group" role="group" aria-label="Filter by eligibility status">
                                <a href="<?php echo e(filterUrl('all')); ?>" class="filter-pill <?php echo $filterStatus === 'all' ? 'active' : ''; ?>">
                                    All <span class="pill-count"><?php echo e(count($students)); ?></span>
                                </a>
                                <a href="<?php echo e(filterUrl('eligible')); ?>" class="filter-pill eligible-pill <?php echo $filterStatus === 'eligible' ? 'active' : ''; ?>">
                                    Eligible <span class="pill-count"><?php echo e($eligibleStudents); ?></span>
                                </a>
                                <a href="<?php echo e(filterUrl('ineligible')); ?>" class="filter-pill ineligible-pill <?php echo $filterStatus === 'ineligible' ? 'active' : ''; ?>">
                                    Ineligible <span class="pill-count"><?php echo e($ineligibleStudents); ?></span>
                                </a>
                            </div>
                        </div>

                        <div class="table-head">
                            <div>Student Name</div>
                            <div>ID</div>
                            <div>Programme</div>
                            <div>Status</div>
                        </div>

                        <?php if (empty($visibleStudents)): ?>
                            <div class="empty">No students found<?php echo $filterStatus !== 'all' ? ' for the selected filter' : ''; ?>.</div>
                        <?php else: ?>
                            <?php foreach ($visibleStudents as $student): ?>
                                <article class="student-row">
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            <?php echo e(studentInitials($student["fullName"])); ?>
                                        </div>
                                        <div style="min-width:0;">
                                            <p class="student-name"><?php echo e($student["fullName"]); ?></p>
                                            <p class="muted"><?php echo e($student["universityEmail"]); ?></p>
                                        </div>
                                    </div>
                                    <div class="cell-text"><?php echo e($student["userID"]); ?></div>
                                    <div class="cell-text"><?php echo e($student["programme"]); ?></div>
                                    <div>
                                        <?php if ((bool) $student["eligibilityStatus"]): ?>
                                            <span class="badge eligible" title="<?php echo e($student["eligibilityReason"]); ?>">Eligible</span>
                                        <?php else: ?>
                                            <span class="badge ineligible" title="<?php echo e($student["eligibilityReason"]); ?>">Ineligible</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="results-footer">
                            <span>
                                Showing <?php echo e($firstVisibleEntry); ?>–<?php echo e($lastVisibleEntry); ?> of <?php echo e(number_format($totalFiltered)); ?> entries
                                <?php if ($filterStatus !== 'all'): ?>
                                    &nbsp;<span style="color:#0d5be8; font-weight:700;">(filtered)</span>
                                <?php endif; ?>
                            </span>
                            <nav class="pager" aria-label="Pagination">
                                <a class="page-pill" href="<?php echo e(pageUrl(max(1, $currentPage - 1), $filterStatus)); ?>">&lt;</a>
                                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                                    <a class="page-pill <?php echo $page === $currentPage ? 'active' : ''; ?>" href="<?php echo e(pageUrl($page, $filterStatus)); ?>">
                                        <?php echo e($page); ?>
                                    </a>
                                <?php endfor; ?>
                                <a class="page-pill" href="<?php echo e(pageUrl(min($totalPages, $currentPage + 1), $filterStatus)); ?>">&gt;</a>
                            </nav>
                        </div>
                    </section>
                </div>

                <aside class="status-card">
                    <h2>Status Summary</h2>
                    <p class="status-subtitle">Current imported eligibility record balance.</p>

                    <?php
                        $r = 50;
                        $circumference = round(2 * M_PI * $r, 2);
                        $offset = round($circumference * (1 - $eligibleRate / 100), 2);
                        $eligibleArc = round($circumference * ($eligibleRate / 100), 2);
                        $ineligibleArc = max(0, round($circumference - $eligibleArc, 2));
                    ?>
                    <div class="ring-wrap">
                        <svg class="ring-svg" viewBox="0 0 120 120">
                            <circle class="ring-bg" cx="60" cy="60" r="<?php echo $r; ?>"/>
                            <circle class="ring-fill red" cx="60" cy="60" r="<?php echo $r; ?>"
                                stroke-dasharray="<?php echo $ineligibleArc; ?> <?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo -$eligibleArc; ?>"/>
                            <circle class="ring-fill" cx="60" cy="60" r="<?php echo $r; ?>"
                                stroke-dasharray="<?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo $offset; ?>"/>
                        </svg>
                        <div class="ring-label">
                            <strong><?php echo e($eligibleRate); ?>%</strong>
                            <span>Eligibility Rate</span>
                        </div>
                    </div>

                    <div class="summary-bars">
                        <div class="bar-row">
                            <div class="bar-info">
                                <span class="bar-label"><span class="dot blue"></span> Eligible Students</span>
                                <span class="bar-count eligible-count"><?php echo e(number_format($eligibleStudents)); ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="summary-fill eligible" style="width: <?php echo e($eligibleRate); ?>%;"></span>
                            </div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-info">
                                <span class="bar-label"><span class="dot red"></span> Ineligible Students</span>
                                <span class="bar-count"><?php echo e(number_format($ineligibleStudents)); ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="summary-fill ineligible" style="width: <?php echo e(max(0, 100 - $eligibleRate)); ?>%;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="status-spacer"></div>

                    <div class="insight">
                        <div class="insight-icon">i</div>
                        <div>
                            <strong>Insight</strong>
                            <p>The eligibility list reflects the latest uploaded academic CSV and UC101 batch validation rules.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</body>
</html>
