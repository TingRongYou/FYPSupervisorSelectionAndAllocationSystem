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
$rowsPerPage        = 6;

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

$hasUploadedFileName =
    $uploadedFileName !== "";

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
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Student Eligibility", "admin"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="content-shell">
        <?php echo ssasPortalSidebar("eligibility"); ?>

        <main class="main eligibility-main">
            <?php echo ssasStatusMessage(); ?>

            <div class="page-grid">
                <div class="left-stack">
                    <article class="hero">
                        <div>
                            <h1>Student Eligibility Management</h1>
                            <p>Verify and manage student status against university criteria.</p>
                        </div>
                        <div class="hero-actions">
                            <form action="../../server/application/admin/runEligibilityBatch.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
                                <button class="btn btn-light" type="submit" <?php echo $hasUploadedEligibilityCSV ? "" : "disabled"; ?>>
                                    Run Eligibility Batch
                                </button>
                            </form>
                        </div>
                        <div class="hero-metrics">
                            <div class="metric">
                                <div class="metric-label">Total Checked</div>
                                <div class="metric-value"><?php echo ssasEscape(number_format($totalStudents)); ?></div>
                            </div>
                            <div class="metric eligible-metric">
                                <div class="metric-label">Eligible Students</div>
                                <div class="metric-value"><?php echo ssasEscape(number_format($eligibleStudents)); ?></div>
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
                                    <p class="criteria-value">Greater than <?php echo ssasEscape(number_format((float) $rules["minimumCGPA"], 2)); ?></p>
                                </div>
                            </div>
                            <div class="criteria-card">
                                <div class="criteria-icon">S</div>
                                <div>
                                    <p class="criteria-label">Current Semester</p>
                                    <p class="criteria-value">Next sem <?php echo ssasEscape($rules["requiredNextSemester"]); ?></p>
                                </div>
                            </div>
                            <div class="criteria-card">
                                <div class="criteria-icon">F</div>
                                <div>
                                    <p class="criteria-label">Academic Status</p>
                                    <p class="criteria-value">Not equal to <?php echo ssasEscape($rules["blockedAcademicStatus"]); ?></p>
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
                            <div class="upload-actions">
                                <form class="upload-control" action="../../server/application/admin/uploadStudentEligibilityCSV.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
                                    <span class="file-name <?php echo $hasUploadedFileName ? "has-file" : ""; ?>" id="fileName">
                                        <span class="file-state"><?php echo $hasUploadedFileName ? "Uploaded CSV" : "CSV file"; ?></span>
                                        <span class="file-title"><?php echo ssasEscape($hasUploadedFileName ? $uploadedFileName : "No file uploaded"); ?></span>
                                    </span>
                                    <input type="file" id="studentCSV" name="studentCSV" accept=".csv,text/csv" required>
                                    <button class="btn btn-secondary btn-upload" id="uploadButton" type="button">Upload CSV</button>
                                </form>
                                <?php if ($hasUploadedFileName): ?>
                                    <form class="remove-upload-form" action="../../server/application/admin/removeStudentEligibilityCSV.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
                                        <button class="btn btn-danger-soft" type="submit">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="rules-editor" id="rulesEditor">
                            <form class="rules-form" action="../../server/application/admin/updateEligibilityRules.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
                                <div>
                                    <label for="minimumCGPA">Minimum CGPA</label>
                                    <input type="number" id="minimumCGPA" name="minimumCGPA" min="0" max="4" step="0.01" value="<?php echo ssasEscape(number_format((float) $rules["minimumCGPA"], 2)); ?>" required>
                                </div>
                                <div>
                                    <label for="requiredNextSemester">Required Next Semester</label>
                                    <select id="requiredNextSemester" name="requiredNextSemester" required>
                                        <?php foreach ($semesterOptions as $semesterOption): ?>
                                            <option value="<?php echo ssasEscape($semesterOption); ?>" <?php echo selected($rules["requiredNextSemester"], $semesterOption); ?>>
                                                <?php echo ssasEscape($semesterOption); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="blockedAcademicStatus">Blocked Status</label>
                                    <select id="blockedAcademicStatus" name="blockedAcademicStatus" required>
                                        <?php foreach ($academicStatusOptions as $statusValue => $statusLabel): ?>
                                            <option value="<?php echo ssasEscape($statusValue); ?>" <?php echo selected($rules["blockedAcademicStatus"], $statusValue); ?>>
                                                <?php echo ssasEscape($statusLabel); ?>
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
                                <a href="<?php echo ssasEscape(filterUrl('all')); ?>" class="filter-pill <?php echo $filterStatus === 'all' ? 'active' : ''; ?>">
                                    All <span class="pill-count"><?php echo ssasEscape(count($students)); ?></span>
                                </a>
                                <a href="<?php echo ssasEscape(filterUrl('eligible')); ?>" class="filter-pill eligible-pill <?php echo $filterStatus === 'eligible' ? 'active' : ''; ?>">
                                    Eligible <span class="pill-count"><?php echo ssasEscape($eligibleStudents); ?></span>
                                </a>
                                <a href="<?php echo ssasEscape(filterUrl('ineligible')); ?>" class="filter-pill ineligible-pill <?php echo $filterStatus === 'ineligible' ? 'active' : ''; ?>">
                                    Ineligible <span class="pill-count"><?php echo ssasEscape($ineligibleStudents); ?></span>
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
                                            <?php echo ssasEscape(ssasInitials($student["fullName"])); ?>
                                        </div>
                                        <div style="min-width:0;">
                                            <p class="student-name"><?php echo ssasEscape($student["fullName"]); ?></p>
                                            <p class="muted"><?php echo ssasEscape($student["universityEmail"]); ?></p>
                                        </div>
                                    </div>
                                    <div class="cell-text"><?php echo ssasEscape($student["userID"]); ?></div>
                                    <div class="cell-text"><?php echo ssasEscape($student["programme"]); ?></div>
                                    <div>
                                        <?php if ((bool) $student["eligibilityStatus"]): ?>
                                            <span class="badge eligible" title="<?php echo ssasEscape($student["eligibilityReason"]); ?>">Eligible</span>
                                        <?php else: ?>
                                            <span class="badge ineligible" title="<?php echo ssasEscape($student["eligibilityReason"]); ?>">Ineligible</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="results-footer">
                            <span>
                                Showing <?php echo ssasEscape($firstVisibleEntry); ?>–<?php echo ssasEscape($lastVisibleEntry); ?> of <?php echo ssasEscape(number_format($totalFiltered)); ?> entries
                                <?php if ($filterStatus !== 'all'): ?>
                                    &nbsp;<span style="color:#0d5be8; font-weight:700;">(filtered)</span>
                                <?php endif; ?>
                            </span>
                            <nav class="table-pager" aria-label="Student eligibility pagination">
                                <?php if ($currentPage > 1): ?>
                                    <a class="table-page-button" href="<?php echo ssasEscape(pageUrl($currentPage - 1, $filterStatus)); ?>" aria-label="Previous eligibility page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo ssasEscape($currentPage); ?> of <?php echo ssasEscape($totalPages); ?></span>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a class="table-page-button" href="<?php echo ssasEscape(pageUrl($currentPage + 1, $filterStatus)); ?>" aria-label="Next eligibility page">&gt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&gt;</span>
                                <?php endif; ?>
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
                            <strong><?php echo ssasEscape($eligibleRate); ?>%</strong>
                            <span>Eligibility Rate</span>
                        </div>
                    </div>

                    <div class="summary-bars">
                        <div class="bar-row">
                            <div class="bar-info">
                                <span class="bar-label"><span class="dot blue"></span> Eligible Students</span>
                                <span class="bar-count eligible-count"><?php echo ssasEscape(number_format($eligibleStudents)); ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="summary-fill eligible" style="width: <?php echo ssasEscape($eligibleRate); ?>%;"></span>
                            </div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-info">
                                <span class="bar-label"><span class="dot red"></span> Ineligible Students</span>
                                <span class="bar-count"><?php echo ssasEscape(number_format($ineligibleStudents)); ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="summary-fill ineligible" style="width: <?php echo ssasEscape(max(0, 100 - $eligibleRate)); ?>%;"></span>
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
