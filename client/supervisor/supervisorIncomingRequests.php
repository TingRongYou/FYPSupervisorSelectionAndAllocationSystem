<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

$requestDAO = new RequestDAO();
$supervisorID = $_SESSION["userID"];
$status = trim($_GET["status"] ?? "Pending");
$search = trim($_GET["search"] ?? "");
$programme = trim($_GET["programme"] ?? "");
$page = max(1, (int) ($_GET["page"] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$applications = $requestDAO->getApplicationsBySupervisor($supervisorID, $status, $search, $programme, $perPage, $offset);
$totalApplications = $requestDAO->countApplicationsBySupervisor($supervisorID, $status, $search, $programme);
$programmes = $requestDAO->getStudentProgrammesForSupervisor($supervisorID);
$totalPages = max(1, (int) ceil($totalApplications / $perPage));
$start = $totalApplications === 0 ? 0 : $offset + 1;
$end = min($offset + count($applications), $totalApplications);

function statusClass($status) {

    $normalized = strtolower(trim($status));

    if ($normalized === "accepted") {

        return "accepted";
    }

    if ($normalized === "rejected") {

        return "rejected";
    }

    return "pending";
}

function formatMonthYear($date) {

    if (!$date) {

        return "-";
    }

    return date("M Y", strtotime($date));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incoming Requests | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .page-head { margin: 4px 0 22px; }
        .page-head h1 { margin: 0 0 6px; color: #172033; font-size: 28px; }
        .page-head p { margin: 0; color: #6b7f91; }
        .toolbar { padding: 18px; margin-bottom: 18px; }
        .search-row { display: grid; grid-template-columns: minmax(260px, 1fr) 180px 150px; gap: 12px; align-items: end; }
        .filter-row { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-top: 18px; }
        .filter-row select { width: 190px; }
        .count-label { color: #7c8da0; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; }
        .request-table { overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px 18px; text-align: left; border-bottom: 1px solid #e5edf5; vertical-align: middle; }
        th { color: #7c8da0; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .student-cell { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-size: 11px; font-weight: 900; }
        .student-name { color: #172033; font-weight: 900; }
        .muted { color: #7c8da0; font-size: 12px; margin-top: 3px; }
        .status { display: inline-flex; min-width: 82px; justify-content: center; padding: 7px 13px; border-radius: 999px; font-size: 11px; font-weight: 900; }
        .status.pending { background: #fff0bf; color: #9a6500; }
        .status.accepted { background: #dcfce7; color: #166534; }
        .status.rejected { background: #fee2e2; color: #991b1b; }
        .link-action { color: #0d5be8; font-weight: 900; text-decoration: none; }
        .footer-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; color: #6b7f91; font-size: 12px; }
        .pager { display: flex; align-items: center; gap: 7px; }
        .pager a, .pager span { min-width: 30px; height: 30px; border-radius: 7px; display: grid; place-items: center; text-decoration: none; color: #526a7f; border: 1px solid #dbe6f0; background: #fff; font-weight: 800; }
        .pager .active { background: #003f8f; color: #fff; border-color: #003f8f; }
        .empty-state { padding: 32px; color: #6b7f91; }
        @media (max-width: 900px) { .search-row { grid-template-columns: 1fr; } .filter-row { display: block; } .filter-row select { width: 100%; margin-top: 10px; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("incoming-requests"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>
            <section class="page-head">
                <h1>Incoming Requests</h1>
                <p>Review and manage student requests.</p>
            </section>

            <form class="toolbar card" method="GET">
                <div class="search-row">
                    <div>
                        <label for="search">Search</label>
                        <input id="search" name="search" value="<?php echo e($search); ?>" placeholder="Search by student name or ID">
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (["Pending", "Accepted", "Rejected", ""] as $option): ?>
                                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? "selected" : ""; ?>>
                                    <?php echo $option === "" ? "All Statuses" : e($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="button" type="submit">Sort by Date</button>
                </div>
                <div class="filter-row">
                    <select name="programme" onchange="this.form.submit()">
                        <option value="">Programme: All Faculties</option>
                        <?php foreach ($programmes as $programmeOption): ?>
                            <option value="<?php echo e($programmeOption); ?>" <?php echo $programme === $programmeOption ? "selected" : ""; ?>>
                                <?php echo e($programmeOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="count-label">Displaying <?php echo e($start); ?>-<?php echo e($end); ?> of <?php echo e($totalApplications); ?> entries</span>
                </div>
            </form>

            <section class="request-table card">
                <?php if (empty($applications)): ?>
                    <div class="empty-state">No matching student requests were found.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Programme</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td>
                                        <div class="student-cell">
                                            <div class="avatar"><?php echo e(supervisorInitials($application["fullName"])); ?></div>
                                            <div class="student-name"><?php echo e($application["fullName"]); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo e($application["studentID"]); ?></td>
                                    <td><?php echo e($application["programme"]); ?></td>
                                    <td><?php echo e(formatMonthYear($application["applicationDate"])); ?></td>
                                    <td><span class="status <?php echo e(statusClass($application["decisionStatus"])); ?>"><?php echo e($application["decisionStatus"]); ?></span></td>
                                    <td><a class="link-action" href="supervisorRequestDecision.php?requestID=<?php echo e($application["requestID"]); ?>">View Proposal</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="footer-row">
                        <span>Showing <?php echo e($start); ?> to <?php echo e($end); ?> of <?php echo e($totalApplications); ?> entries</span>
                        <div class="pager">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo e(http_build_query(array_merge($_GET, ["page" => $page - 1]))); ?>">&lt;</a>
                            <?php endif; ?>
                            <span class="active"><?php echo e($page); ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo e(http_build_query(array_merge($_GET, ["page" => $page + 1]))); ?>">&gt;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>


