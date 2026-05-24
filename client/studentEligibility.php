<?php

require_once "../server/application/SessionManager.php";
require_once "../server/business/EligibilityService.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication and RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Administrator"
);

$eligibilityService =
    new EligibilityService();

$searchName =
    $_GET["searchName"] ?? "";

$selectedProgramme =
    $_GET["programme"] ?? "";

$selectedEligibilityStatus =
    $_GET["eligibilityStatus"] ?? "";

$students =
    $eligibilityService
    ->getEligibilityDashboard(
        $_GET
    );

$programmeOptions =
    $eligibilityService
    ->getProgrammeOptions();

$summary =
    $eligibilityService
    ->getEligibilitySummary();

function e($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function selected($left, $right) {

    return (string) $left === (string) $right
        ? "selected"
        : "";
}

function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class =
        $_GET["status"] === "success"
        ? "success"
        : "error";

    return
        "<div class=\"message {$class}\">"
        . e($_GET["message"])
        . "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Student Eligibility | SSAS
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8fc;
            color: #1d2b3a;
        }

        .topbar {
            height: 64px;
            background: #0b95c5;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            box-shadow: 0 4px 14px rgba(11, 79, 138, .16);
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .crest {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: grid;
            place-items: center;
            background: #ffffff;
            color: #0b4f8a;
            font-weight: 800;
        }

        .topbar-user {
            text-align: right;
            font-size: 13px;
            line-height: 1.4;
        }

        .topbar-user strong {
            display: block;
            font-size: 14px;
        }

        .content-shell {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #dde8f2;
            padding: 26px 18px;
        }

        .role-card {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            background: #eef6fc;
            margin-bottom: 20px;
        }

        .role-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #0b66d8;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .role-title {
            margin: 0;
            color: #0b3760;
            font-weight: 700;
            font-size: 15px;
        }

        .role-subtitle {
            margin: 2px 0 0;
            color: #6b7f91;
            font-size: 12px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: #526a7f;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            transition: background .2s, color .2s, transform .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #eaf3ff;
            color: #0b66d8;
            transform: translateX(2px);
        }

        .main {
            flex: 1;
            padding: 28px 34px 40px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.7fr .8fr;
            gap: 22px;
            margin-bottom: 24px;
        }

        .hero-card {
            background: #0d5be8;
            color: #ffffff;
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 12px 24px rgba(13, 91, 232, .22);
        }

        .hero-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
        }

        .hero-card h1 {
            margin: 0 0 8px;
            font-size: 30px;
            font-weight: 700;
        }

        .hero-card p {
            margin: 0;
            color: #dbe9ff;
            line-height: 1.5;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 28px;
        }

        .metric {
            background: rgba(255, 255, 255, .13);
            border-radius: 8px;
            padding: 14px;
        }

        .metric-label {
            color: #b9d2ff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 800;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border: 0;
            border-radius: 6px;
            padding: 0 16px;
            background: #0b66ad;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s, box-shadow .2s;
        }

        .button:hover {
            background: #084f88;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(11, 102, 173, .2);
        }

        .button.light {
            background: #ffffff;
            color: #0d5be8;
        }

        .button.secondary {
            background: #e8f2fb;
            color: #0b4f8a;
        }

        .panel,
        .status-card {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, .08);
        }

        .status-card h2,
        .panel h2 {
            margin: 0 0 8px;
            color: #0b3760;
            font-size: 20px;
        }

        .status-ring {
            width: 138px;
            height: 138px;
            border-radius: 50%;
            border: 10px solid #0d5be8;
            display: grid;
            place-items: center;
            margin: 16px auto;
        }

        .status-ring strong {
            color: #0d5be8;
            font-size: 30px;
        }

        .status-caption {
            margin: 0;
            color: #6b7f91;
            font-size: 13px;
            text-align: center;
        }

        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .criteria-card {
            background: #f4f8fc;
            border: 1px solid #d9e7f3;
            border-radius: 8px;
            padding: 14px;
        }

        .criteria-card strong {
            display: block;
            color: #0b3760;
            margin-bottom: 5px;
        }

        .criteria-card span {
            color: #526a7f;
            font-size: 13px;
        }

        .filters {
            display: grid;
            grid-template-columns: 1.2fr 1fr .9fr auto auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #35546d;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            height: 40px;
            border: 1px solid #c6d8e8;
            border-radius: 6px;
            padding: 0 10px;
            background: #ffffff;
            color: #1d2b3a;
            font-size: 14px;
        }

        input:focus,
        select:focus {
            outline: 2px solid #9dccf1;
            border-color: #2179b8;
        }

        .message {
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .message.success {
            background: #e5f6ed;
            color: #177345;
            border: 1px solid #a9dfbf;
        }

        .message.error {
            background: #fdeaea;
            color: #a52d2d;
            border: 1px solid #f0b8b8;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 840px;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e3edf6;
            text-align: left;
            font-size: 14px;
        }

        th {
            color: #526a7f;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .student-name {
            color: #0b3760;
            font-weight: 800;
        }

        .muted {
            color: #6b7f91;
            font-size: 13px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .badge.eligible {
            background: #e5f6ed;
            color: #177345;
        }

        .badge.ineligible {
            background: #fdeaea;
            color: #a52d2d;
        }

        .empty {
            border: 1px dashed #aac7df;
            border-radius: 8px;
            padding: 24px;
            color: #526a7f;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .hero-grid,
            .criteria-grid,
            .filters {
                grid-template-columns: 1fr;
            }

            .hero-header {
                display: block;
            }

            .hero-header form {
                margin-top: 18px;
            }
        }

        @media (max-width: 820px) {
            .topbar {
                height: auto;
                align-items: flex-start;
                gap: 12px;
                padding: 18px;
            }

            .content-shell {
                display: block;
            }

            .sidebar {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #dde8f2;
            }

            .main {
                padding: 22px;
            }

            .hero-metrics {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body>

    <header class="topbar">
        <div class="topbar-brand">
            <div class="crest">T</div>
            <span>TAR UMT SSAS</span>
        </div>
        <div class="topbar-user">
            <strong><?php echo e($_SESSION["fullName"]); ?></strong>
            Administrator
        </div>
    </header>

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
            <a class="nav-link" href="#">Allocations</a>
            <a class="nav-link" href="#">Reports</a>
            <a class="nav-link" href="../server/application/logout.php">Logout</a>
        </aside>

        <main class="main">

            <?php echo statusMessage(); ?>

            <section class="hero-grid">
                <article class="hero-card">
                    <div class="hero-header">
                        <div>
                            <h1>Student Eligibility Management</h1>
                            <p>
                                Verify and manage student status against university criteria.
                            </p>
                        </div>
                        <form action="../server/application/runEligibilityBatch.php" method="POST">
                            <button class="button light" type="submit">
                                Run Eligibility Batch
                            </button>
                        </form>
                    </div>

                    <div class="hero-metrics">
                        <div class="metric">
                            <div class="metric-label">Total Checked</div>
                            <div class="metric-value"><?php echo e($summary["totalStudents"]); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Eligible Students</div>
                            <div class="metric-value"><?php echo e($summary["eligibleStudents"]); ?></div>
                        </div>
                    </div>
                </article>

                <article class="status-card">
                    <h2>Status Summary</h2>
                    <div class="status-ring">
                        <strong><?php echo e($summary["eligibleRate"]); ?>%</strong>
                    </div>
                    <p class="status-caption">
                        Eligibility rate across all registered students.
                    </p>
                </article>
            </section>

            <section class="panel">
                <h2>Active Criteria</h2>
                <div class="criteria-grid">
                    <div class="criteria-card">
                        <strong>Minimum CGPA</strong>
                        <span>CGPA must be at least 2.0000.</span>
                    </div>
                    <div class="criteria-card">
                        <strong>Current Semester</strong>
                        <span>Student must be in Y2S3.</span>
                    </div>
                    <div class="criteria-card">
                        <strong>Academic Status</strong>
                        <span>Withdrawn / EP students are excluded.</span>
                    </div>
                </div>

                <form class="filters" method="GET" action="studentEligibility.php">
                    <div>
                        <label for="searchName">Search Student</label>
                        <input
                            type="text"
                            id="searchName"
                            name="searchName"
                            value="<?php echo e($searchName); ?>"
                            placeholder="Search by name or student ID"
                        >
                    </div>

                    <div>
                        <label for="programme">Programme</label>
                        <select id="programme" name="programme">
                            <option value="">All Programmes</option>
                            <?php foreach ($programmeOptions as $programme): ?>
                                <option
                                    value="<?php echo e($programme["programme"]); ?>"
                                    <?php echo selected($selectedProgramme, $programme["programme"]); ?>
                                >
                                    <?php echo e($programme["programme"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="eligibilityStatus">Eligibility</label>
                        <select id="eligibilityStatus" name="eligibilityStatus">
                            <option value="">All Status</option>
                            <option value="1" <?php echo selected($selectedEligibilityStatus, "1"); ?>>Eligible</option>
                            <option value="0" <?php echo selected($selectedEligibilityStatus, "0"); ?>>Ineligible</option>
                        </select>
                    </div>

                    <button class="button" type="submit">Apply Filters</button>
                    <a class="button secondary" href="studentEligibility.php">Reset</a>
                </form>

                <h2>Batch Processing Results</h2>

                <?php if (empty($students)): ?>
                    <div class="empty">
                        No student records match the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>ID</th>
                                    <th>Programme</th>
                                    <th>Current Sem</th>
                                    <th>CGPA</th>
                                    <th>Academic Status</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="student-name">
                                                <?php echo e($student["fullName"]); ?>
                                            </div>
                                            <div class="muted">
                                                <?php echo e($student["universityEmail"]); ?>
                                            </div>
                                        </td>
                                        <td><?php echo e($student["userID"]); ?></td>
                                        <td><?php echo e($student["programme"]); ?></td>
                                        <td><?php echo e($student["currentSem"]); ?></td>
                                        <td><?php echo e($student["cgpa"]); ?></td>
                                        <td><?php echo e($student["academicStatus"]); ?></td>
                                        <td>
                                            <?php if ($student["eligibilityStatus"]): ?>
                                                <span class="badge eligible">Eligible</span>
                                            <?php else: ?>
                                                <span class="badge ineligible">Ineligible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($student["eligibilityReason"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        </main>

    </div>

</body>
</html>
