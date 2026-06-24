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

if (!in_array($status, ["Pending", "Accepted", "Rejected", ""], true)) {

    $status = "Pending";
}

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
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css">
    <script src="../assets/js/supervisor.js" defer></script>
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
                        <select id="status" name="status" onchange="this.form.submit()">
                            <?php foreach (["Pending", "Accepted", "Rejected", ""] as $option): ?>
                                <option value="<?php echo e($option); ?>" <?php echo $status === $option ? "selected" : ""; ?>>
                                    <?php echo $option === "" ? "All Statuses" : e($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="button" type="submit">Apply Filters</button>
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
                    <div class="empty-state">
                        <?php echo $status === "Pending" && $search === "" && $programme === "" ? "No Pending Requests - You currently have no pending requests." : "No matching student requests were found."; ?>
                    </div>
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