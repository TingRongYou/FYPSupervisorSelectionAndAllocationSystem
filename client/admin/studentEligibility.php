<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/EligibilityService.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Administrator");

$eligibilityService = new EligibilityService();

$students           = $eligibilityService->getEligibilityDashboard([]);
$summary            = $eligibilityService->getEligibilitySummary();
$currentPage        = max(1, (int) ($_GET["page"] ?? 1));
$rowsPerPage        = 10;

$totalStudents      = (int) ($summary["totalStudents"]      ?? 0);
$eligibleStudents   = (int) ($summary["eligibleStudents"]   ?? 0);
$ineligibleStudents = (int) ($summary["ineligibleStudents"] ?? max(0, $totalStudents - $eligibleStudents));
$eligibleRate       = (int) ($summary["eligibleRate"]       ?? 0);
$totalPages         = max(1, (int) ceil(count($students) / $rowsPerPage));
$currentPage        = min($currentPage, $totalPages);
$visibleStudents    = array_slice($students, ($currentPage - 1) * $rowsPerPage, $rowsPerPage);
$firstVisibleEntry  = empty($visibleStudents) ? 0 : (($currentPage - 1) * $rowsPerPage) + 1;
$lastVisibleEntry   = empty($visibleStudents) ? 0 : $firstVisibleEntry + count($visibleStudents) - 1;

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

function pageUrl($page) {
    return "studentEligibility.php?page=" . max(1, (int) $page);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Eligibility | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>

        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #10263d; }

        .content-shell { display: flex; min-height: calc(100vh - 52px); }

        /* â”€â”€ Sidebar â”€â”€ */
        .sidebar { width: 220px; flex: 0 0 220px; background: #fff; border-right: 1px solid #dce8f3; padding: 16px 10px; }
        .role-card { display: flex; gap: 10px; align-items: center; padding: 6px 9px 14px; margin-bottom: 8px; }
        .role-icon { width: 34px; height: 34px; border-radius: 8px; background: #0d5be8; color: #fff; display: grid; place-items: center; font-size: 14px; font-weight: 900; flex-shrink: 0; }
        .role-title    { margin: 0; color: #10263d; font-weight: 900; font-size: 14px; }
        .role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        .nav-link { display: flex; align-items: center; gap: 10px; color: #526a7f; text-decoration: none; padding: 9px 12px; border-radius: 7px; margin-bottom: 3px; font-size: 13px; font-weight: 600; }
        .nav-link:hover, .nav-link.active { background: #eaf3ff; color: #0d5be8; }
        .nav-link.active { font-weight: 800; }
        .nav-icon    { font-size: 16px; width: 18px; text-align: center; flex-shrink: 0; }
        .nav-chevron { margin-left: auto; font-size: 11px; }

        .sidebar { width: 280px; flex: 0 0 280px; border-right: 1px solid #dde8f2; padding: 26px 18px; }
        .role-card { gap: 12px; padding: 12px; border-radius: 8px; background: #eef6fc; margin-bottom: 20px; }
        .role-icon { width: 36px; height: 36px; border-radius: 8px; font-size: 15px; }
        .role-title { font-size: 14px; }
        .role-subtitle { font-size: 12px; text-transform: none; letter-spacing: 0; }
        .nav-link { gap: 10px; padding: 12px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; font-weight: 400; transition: background .2s, color .2s, transform .2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { color: #0b66d8; transform: translateX(2px); }
        .nav-icon, .nav-chevron { display: none; }

        /* â”€â”€ Main â”€â”€ */
        .main { flex: 1; padding: 26px 28px 70px; min-width: 0; overflow-x: hidden; }

        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 700; font-size: 14px; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error   { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }

        /* â”€â”€ Page grid â”€â”€ */
        .page-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 240px;
            gap: 22px;
            align-items: stretch;
        }

        /* â”€â”€ Left stack â”€â”€ */
        .left-stack { display: grid; gap: 22px; min-width: 0; align-content: start; }

        /* â”€â”€ Hero â”€â”€ */
        .hero {
            background: linear-gradient(135deg, #1565e8 0%, #0d48c0 100%);
            color: #fff; border-radius: 14px; padding: 28px 30px;
            display: grid; grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px; align-items: start;
        }
        .hero h1 { margin: 0 0 6px; font-size: 26px; font-weight: 700; }
        .hero p  { margin: 0; color: #c8deff; font-size: 14px; line-height: 1.5; }
        .hero-actions { display: flex; align-items: flex-start; }
        .hero-metrics { grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 18px; }
        .metric { min-width: 130px; background: rgba(255,255,255,.15); border-radius: 10px; padding: 14px 18px; }
        .metric-label { color: #a8c8ff; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; }
        .metric-value { margin-top: 6px; font-size: 28px; font-weight: 900; }

        /* â”€â”€ Buttons â”€â”€ */
        .btn { border: 0; height: 38px; border-radius: 8px; padding: 0 18px; font-weight: 800; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn-light     { background: #fff; color: #0d5be8; }
        .btn-secondary { background: #eef3f8; color: #2f4053; }
        .btn-primary   { background: #0d5be8; color: #fff; }
        .btn-upload.ready { background: #0d5be8; color: #fff; }

        /* â”€â”€ Panels â”€â”€ */
        .panel { background: #fff; border: 1px solid #d9e7f3; border-radius: 14px; overflow: hidden; }

        /* Criteria panel */
        .criteria-panel { padding: 22px; }
        .panel-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .panel-title h2 { margin: 0; color: #10263d; font-size: 15px; font-weight: 900; }
        .title-mark { color: #0d5be8; margin-right: 6px; }
        .edit-link  { color: #0d5be8; text-decoration: none; font-size: 13px; font-weight: 800; }

        .criteria-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .criteria-card { background: #f3f7fb; border-radius: 10px; padding: 16px 14px; display: flex; gap: 12px; align-items: center; }
        .criteria-icon { width: 38px; height: 38px; border-radius: 50%; background: #ddeaff; display: grid; place-items: center; color: #0d5be8; font-size: 16px; font-weight: 900; flex-shrink: 0; }
        .criteria-label { margin: 0; color: #8a9caf; text-transform: uppercase; letter-spacing: .8px; font-size: 11px; font-weight: 800; }
        .criteria-value { margin: 4px 0 0; color: #10263d; font-size: 14px; font-weight: 800; }

        /* Upload strip */
        .upload-strip { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; align-items: center; margin-top: 18px; padding-top: 16px; border-top: 1px solid #edf2f7; }
        .upload-strip p { margin: 0; color: #6b7f91; font-size: 13px; line-height: 1.6; }
        .upload-control { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
        .file-label { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border: 1px solid #d0dcea; border-radius: 7px; background: #f6f8fb; color: #526a7f; font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .file-label:hover { border-color: #0d5be8; color: #0d5be8; }
        input[type="file"] { display: none; }
        .file-name { font-size: 12px; color: #8a9caf; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* Results table */
        .results-title { padding: 18px 22px 14px; border-bottom: 1px solid #edf2f7; }
        .results-title h2 { margin: 0; color: #10263d; font-size: 15px; font-weight: 900; }

        .table-head,
        .student-row {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1.4fr 0.8fr;
            align-items: center;
            gap: 14px;
        }
        .table-head {
            padding: 11px 22px;
            background: #f8fafd;
            border-bottom: 1px solid #edf2f7;
            color: #8a9caf;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .student-row { padding: 14px 22px; border-bottom: 1px solid #edf2f7; min-height: 66px; }
        .student-row:last-of-type { border-bottom: none; }
        .student-row:hover { background: #fafcff; }

        .student-cell { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .student-avatar { width: 34px; height: 34px; border-radius: 50%; background: #e6edf5; color: #526a7f; display: grid; place-items: center; font-size: 11px; font-weight: 900; flex-shrink: 0; }
        .student-name { margin: 0; color: #10263d; font-size: 13px; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .muted        { margin: 2px 0 0; color: #8a9caf; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cell-text    { color: #526a7f; font-size: 12px; line-height: 1.4; }

        .badge { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 5px 13px; font-size: 11px; font-weight: 800; }
        .badge.eligible   { background: #dcfce7; color: #118549; }
        .badge.ineligible { background: #fee2e2; color: #c02d2d; }

        .empty { padding: 36px 22px; text-align: center; color: #8a9caf; font-size: 14px; }

        .results-footer { display: flex; justify-content: space-between; align-items: center; padding: 14px 22px; color: #8a9caf; font-size: 12px; border-top: 1px solid #edf2f7; }
        .pager { display: flex; gap: 5px; align-items: center; }
        .page-pill { width: 28px; height: 28px; border: 1px solid #dce8f3; border-radius: 6px; display: grid; place-items: center; color: #6b7f91; background: #fff; font-size: 12px; font-weight: 800; cursor: pointer; text-decoration: none; }
        .page-pill.active { background: #0d5be8; color: #fff; border-color: #0d5be8; }

        /* â”€â”€ Status card â”€â”€ */
        .status-card {
            background: #fff;
            border: 1px solid #d9e7f3;
            border-radius: 14px;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
        }
        .status-card h2 { margin: 0 0 4px; color: #10263d; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: .8px; }

        /* SVG ring */
        .ring-wrap  { position: relative; width: 130px; height: 130px; margin: 20px auto 24px; }
        .ring-svg   { width: 130px; height: 130px; transform: rotate(-90deg); }
        .ring-bg    { fill: none; stroke: #e8f0fb; stroke-width: 11; }
        .ring-fill  { fill: none; stroke: #0d5be8; stroke-width: 11; stroke-linecap: round; }
        .ring-label { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .ring-label strong { color: #0d5be8; font-size: 26px; font-weight: 900; line-height: 1; }
        .ring-label span   { color: #6b7f91; font-size: 10px; text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; font-weight: 800; }

        /* Summary bars */
        .summary-bars { display: grid; gap: 16px; }
        .bar-row  { display: grid; gap: 7px; }
        .bar-info { display: flex; justify-content: space-between; align-items: center; }
        .bar-label { display: flex; align-items: center; gap: 7px; color: #10263d; font-size: 12px; font-weight: 700; }
        .bar-count { color: #526a7f; font-size: 12px; font-weight: 700; }
        .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
        .dot.blue { background: #0d5be8; }
        .dot.red  { background: #e33434; }
        .bar-track { height: 6px; background: #edf2f7; border-radius: 999px; overflow: hidden; }
        .bar-fill  { display: block; height: 100%; background: #0d5be8; border-radius: 999px; }
        .bar-fill.red { background: #e33434; }

        /* Spacer pushes insight to bottom */
        .status-spacer { flex: 1; }

        /* Insight */
        .insight {
            margin-top: auto;
            background: #f7fbff;
            border: 1px solid #e4eef8;
            border-radius: 10px;
            padding: 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .insight-icon { width: 26px; height: 26px; border-radius: 6px; background: #ddeaff; color: #0d5be8; display: grid; place-items: center; font-size: 13px; font-weight: 900; flex-shrink: 0; }
        .insight strong { display: block; font-size: 12px; color: #10263d; margin-bottom: 5px; font-weight: 800; }
        .insight p { margin: 0; color: #8a9caf; font-size: 11px; line-height: 1.55; }

        /* Responsive */
        @media (max-width: 1080px) {
            .page-grid { grid-template-columns: 1fr; align-items: start; }
            .status-card { flex-direction: row; flex-wrap: wrap; gap: 20px; }
            .status-card h2 { width: 100%; }
            .ring-wrap { margin: 0; }
            .insight { margin-top: 0; width: 100%; }
            .status-spacer { display: none; }
        }
        @media (max-width: 820px) {
            .content-shell { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid #dce8f3; }
            .main { padding: 16px 14px 60px; }
            .hero { grid-template-columns: 1fr; }
            .hero-actions { justify-content: flex-start; }
            .criteria-grid { grid-template-columns: 1fr; }
            .upload-strip { grid-template-columns: 1fr; }
            .upload-control { flex-wrap: wrap; }
            .table-head { display: none; }
            .student-row { grid-template-columns: 1fr 1fr; gap: 8px; }
        }
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
            <a class="nav-link active" href="studentEligibility.php">Students Eligibility</a>
            <a class="nav-link" href="quotaManagement.php">Quota Management</a>
            <a class="nav-link" href="autoAllocation.php">Allocations</a>
            <a class="nav-link" href="#">Reports</a>
            <a class="nav-link" href="../../server/application/auth/logout.php">Logout</a>
        </aside>

        <main class="main">
            <?php echo statusMessage(); ?>

            <div class="page-grid">

                <!-- Left column -->
                <div class="left-stack">

                    <!-- Hero -->
                    <article class="hero">
                        <div>
                            <h1>Student Eligibility Management</h1>
                            <p>Verify and manage student status against university criteria.</p>
                        </div>
                        <div class="hero-actions">
                            <form action="../../server/application/admin/runEligibilityBatch.php" method="POST">
                                <button class="btn btn-light" type="submit">
                                    â–¶ Run Eligibility Batch
                                </button>
                            </form>
                        </div>
                        <div class="hero-metrics">
                            <div class="metric">
                                <div class="metric-label">Total Checked</div>
                                <div class="metric-value"><?php echo e(number_format($totalStudents)); ?></div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Eligible Students</div>
                                <div class="metric-value"><?php echo e(number_format($eligibleStudents)); ?></div>
                            </div>
                        </div>
                    </article>

                    <!-- Active Criteria -->
                    <section class="panel criteria-panel">
                        <div class="panel-title">
                            <h2><span class="title-mark">â‰¡</span> Active Criteria</h2>
                            <a class="edit-link" href="#">Edit Rules</a>
                        </div>

                        <div class="criteria-grid">
                            <div class="criteria-card">
                                <div class="criteria-icon">C</div>
                                <div>
                                    <p class="criteria-label">Minimum CGPA</p>
                                    <p class="criteria-value">Min 2.0</p>
                                </div>
                            </div>
                            <div class="criteria-card">
                                <div class="criteria-icon">S</div>
                                <div>
                                    <p class="criteria-label">Current Semester</p>
                                    <p class="criteria-value">Y2S3</p>
                                </div>
                            </div>
                            <div class="criteria-card">
                                <div class="criteria-icon">F</div>
                                <div>
                                    <p class="criteria-label">Academic Status</p>
                                    <p class="criteria-value">Not equal to EF</p>
                                </div>
                            </div>
                        </div>

                        <form class="upload-strip"
                              action="../../server/application/admin/uploadStudentEligibilityCSV.php"
                              method="POST"
                              enctype="multipart/form-data">
                            <p>Upload a CSV containing studentID, universityEmail, malaysiaICNumber, fullName, programme, cgpa, currentSem, and enrollmentStatus before running the eligibility batch.</p>
                            <div class="upload-control">
                                <label class="file-label" for="studentCSV">
                                    ðŸ“Ž Choose File
                                </label>
                                <span class="file-name" id="fileName">No file chosen</span>
                                <input type="file" id="studentCSV" name="studentCSV" accept=".csv,text/csv" required>
                                <button class="btn btn-secondary btn-upload" id="uploadButton" type="submit">Upload CSV</button>
                            </div>
                        </form>
                    </section>

                    <!-- Batch Results -->
                    <section class="panel">
                        <div class="results-title">
                            <h2>Batch Processing Results</h2>
                        </div>

                        <div class="table-head">
                            <div>Student Name</div>
                            <div>ID</div>
                            <div>Programme</div>
                            <div>Status</div>
                        </div>

                        <?php if (empty($visibleStudents)): ?>
                            <div class="empty">
                                No Record: No Student Record Found
                            </div>
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
                                            <span class="badge eligible"
                                                  title="<?php echo e($student["eligibilityReason"]); ?>">
                                                Eligible
                                            </span>
                                        <?php else: ?>
                                            <span class="badge ineligible"
                                                  title="<?php echo e($student["eligibilityReason"]); ?>">
                                                Ineligible
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="results-footer">
                            <span>
                                Showing <?php echo e($firstVisibleEntry); ?>-<?php echo e($lastVisibleEntry); ?> of
                                <?php echo e(number_format($totalStudents)); ?> entries
                            </span>
                            <nav class="pager" aria-label="Pagination">
                                <a class="page-pill" href="<?php echo e(pageUrl(max(1, $currentPage - 1))); ?>">&lt;</a>
                                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                                    <a class="page-pill <?php echo $page === $currentPage ? "active" : ""; ?>"
                                       href="<?php echo e(pageUrl($page)); ?>">
                                        <?php echo e($page); ?>
                                    </a>
                                <?php endfor; ?>
                                <a class="page-pill" href="<?php echo e(pageUrl(min($totalPages, $currentPage + 1))); ?>">&gt;</a>
                            </nav>
                        </div>
                    </section>
                </div>

                <!-- Right column: Status card -->
                <aside class="status-card">
                    <h2>Status Summary</h2>

                    <?php
                        $r             = 50;
                        $circumference = round(2 * M_PI * $r, 2);
                        $offset        = round($circumference * (1 - $eligibleRate / 100), 2);
                    ?>
                    <div class="ring-wrap">
                        <svg class="ring-svg" viewBox="0 0 120 120">
                            <circle class="ring-bg"   cx="60" cy="60" r="<?php echo $r; ?>"/>
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
                                <span class="bar-label">
                                    <span class="dot blue"></span> Eligible Students
                                </span>
                                <span class="bar-count"><?php echo e(number_format($eligibleStudents)); ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="bar-fill" style="width: <?php echo e($eligibleRate); ?>%;"></span>
                            </div>
                        </div>
                        <div class="bar-row">
                            <div class="bar-info">
                                <span class="bar-label">
                                    <span class="dot red"></span> Ineligible Students
                                </span>
                                <span class="bar-count"><?php echo e(number_format($ineligibleStudents)); ?></span>
                            </div>
                            <div class="bar-track">
                                <span class="bar-fill red" style="width: <?php echo e(max(0, 100 - $eligibleRate)); ?>%;"></span>
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

    <script>
        document.getElementById("studentCSV").addEventListener("change", function () {
            const label = document.getElementById("fileName");
            const uploadButton = document.getElementById("uploadButton");
            label.textContent = this.files.length ? this.files[0].name : "No file chosen";
            uploadButton.classList.toggle("ready", this.files.length > 0);
        });
    </script>
</body>
</html>


