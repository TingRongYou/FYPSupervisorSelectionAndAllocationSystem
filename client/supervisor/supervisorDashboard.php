<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/business/services/SupervisorDashboardService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Authentication and RBAC Validation
|--------------------------------------------------------------------------
*/

SessionManager::requireRole(
    "Supervisor"
);

$dashboardService =
    new SupervisorDashboardService();

$allocationStatusFilter =
    trim($_GET["allocationStatus"] ?? "");

$proposalStatusFilter =
    trim($_GET["proposalStatus"] ?? "");

$allocationPage =
    max(
        1,
        (int) ($_GET["allocationPage"] ?? 1)
    );

$allowedAllocationFilters =
    ["", "Auto-Allocated", "Accepted", "Allocated"];

$allowedProposalFilters =
    ["", "Pending", "Accepted", "Rejected", "Proposal Requested", "Not Submitted"];

if (!in_array($allocationStatusFilter, $allowedAllocationFilters, true)) {

    $allocationStatusFilter = "";
}

if (!in_array($proposalStatusFilter, $allowedProposalFilters, true)) {

    $proposalStatusFilter = "";
}

$dashboard =
    $dashboardService
    ->getDashboardData(
        $_SESSION["userID"],
        $allocationStatusFilter,
        $proposalStatusFilter,
        $allocationPage
    );

function allocationPageUrl($page, $allocationStatusFilter, $proposalStatusFilter) {

    $query = [
        "allocationPage" => max(1, (int) $page)
    ];

    if ($allocationStatusFilter !== "") {

        $query["allocationStatus"] = $allocationStatusFilter;
    }

    if ($proposalStatusFilter !== "") {

        $query["proposalStatus"] = $proposalStatusFilter;
    }

    return "supervisorDashboard.php?" . http_build_query($query);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/supervisor.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script src="../assets/js/supervisor.js" defer></script>
</head>

<body>
    <?php echo supervisorTopbar(); ?>

    <div class="content-shell">
        <?php echo supervisorSidebar("dashboard"); ?>

        <main class="main">
            <?php if ($dashboard["deadlineAlert"]["show"]): ?>
                <section class="alert">
                    <div>
                        <strong>!</strong>
                        <?php echo e($dashboard["deadlineAlert"]["message"]); ?>
                    </div>
                    <span>x</span>
                </section>
            <?php endif; ?>

            <section class="hero-card">
                <h1>Welcome back, <?php echo e($_SESSION["fullName"]); ?>.</h1>
                <p>
                    You have <?php echo e($dashboard["pendingRequests"]); ?> new applications requiring your immediate attention.
                    <br>
                    Your current supervision load is healthy.
                </p>

                <div class="metric-grid">
                    <div class="metric">
                        <div class="metric-label">Incoming Requests</div>
                        <div class="metric-value"><?php echo e($dashboard["pendingRequests"]); ?></div>
                    </div>

                    <div class="metric">
                        <div class="metric-label">Active Supervisees</div>
                        <div class="metric-value">
                            <?php echo e($dashboard["activeSupervisees"]); ?>
                            <span style="font-size: 15px; color: #b9d2ff;">/ <?php echo e($dashboard["maxSuperviseesAllowed"]); ?></span>
                        </div>
                    </div>

                    <div class="metric">
                        <div class="metric-label">Quota Usage</div>
                        <div class="metric-value">
                            <?php echo e($dashboard["quotaUsage"]); ?>%
                            <span class="quota-line">
                                <span style="width: <?php echo e($dashboard["quotaUsage"]); ?>%;"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <a class="button" href="supervisorIncomingRequests.php">
                    View All Requests
                </a>
            </section>

            <section class="applications">
                <div class="section-header">
                    <h2>Recent Student Allocations</h2>
                    <form class="allocation-filter" method="GET">
                        <input type="hidden" name="allocationPage" value="1">
                        <select name="allocationStatus" aria-label="Allocation status">
                            <?php foreach ($allowedAllocationFilters as $option): ?>
                                <option value="<?php echo e($option); ?>" <?php echo $allocationStatusFilter === $option ? "selected" : ""; ?>>
                                    <?php echo $option === "" ? "All Allocations" : e($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="proposalStatus" aria-label="Proposal status">
                            <?php foreach ($allowedProposalFilters as $option): ?>
                                <option value="<?php echo e($option); ?>" <?php echo $proposalStatusFilter === $option ? "selected" : ""; ?>>
                                    <?php echo $option === "" ? "All Proposals" : e($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Filter</button>
                    </form>
                </div>

                <?php if (empty($dashboard["recentApplications"])): ?>
                    <div class="empty-state">
                        No students have been allocated to you yet.
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Programme</th>
                                    <th>Research Focus</th>
                                    <th>Status</th>
                                    <th>Proposal Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard["recentApplications"] as $application): ?>
                                    <tr>
                                        <td>
                                            <div class="student-cell">
                                                <div class="student-avatar">
                                                    <?php if (!empty($application["profilePhotoPath"])): ?>
                                                        <img src="<?php echo e($application["profilePhotoPath"]); ?>" alt="">
                                                    <?php else: ?>
                                                        <?php echo e(supervisorInitials($application["fullName"])); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="student-name">
                                                        <?php echo e($application["fullName"]); ?>
                                                    </div>
                                                    <div class="muted">
                                                        <?php echo e($application["studentID"]); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo e($application["programme"]); ?></td>
                                        <td>
                                            <span class="focus-tag">
                                                <?php echo e($application["researchFocus"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status <?php echo e($application["statusClass"]); ?>">
                                                <?php echo e($application["decisionStatus"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status <?php echo e($application["proposalStatusClass"]); ?>">
                                                <?php echo e($application["proposalStatusText"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($application["requestID"])): ?>
                                                <a class="action-button" href="supervisorRequestDecision.php?requestID=<?php echo e($application["requestID"]); ?>">
                                                    <?php echo e($application["actionText"]); ?>
                                                </a>
                                            <?php else: ?>
                                                <a class="action-button" href="supervisorMySupervisees.php">
                                                    <?php echo e($application["actionText"]); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <span>
                            <?php
                                $recentTotal =
                                    (int) $dashboard["recentApplicationsTotal"];

                                $recentPage =
                                    (int) $dashboard["recentApplicationsPage"];

                                $recentPerPage =
                                    (int) $dashboard["recentApplicationsPerPage"];

                                $recentTotalPages =
                                    (int) $dashboard["recentApplicationsTotalPages"];

                                $recentStart =
                                    $recentTotal > 0
                                        ? (($recentPage - 1) * $recentPerPage) + 1
                                        : 0;

                                $recentEnd =
                                    min(
                                        $recentTotal,
                                        $recentPage * $recentPerPage
                                    );
                            ?>
                            Showing <?php echo e($recentStart); ?>-<?php echo e($recentEnd); ?> of <?php echo e($recentTotal); ?> recent allocations
                        </span>
                        <div class="table-pager" aria-label="Recent allocations pagination">
                            <?php if ($recentPage > 1): ?>
                                <a class="table-page-button" href="<?php echo e(allocationPageUrl($recentPage - 1, $allocationStatusFilter, $proposalStatusFilter)); ?>" aria-label="Previous allocations page">&lt;</a>
                            <?php else: ?>
                                <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                            <?php endif; ?>

                            <span class="table-page-count">
                                Page <?php echo e($recentPage); ?> of <?php echo e($recentTotalPages); ?>
                            </span>

                            <?php if ($recentPage < $recentTotalPages): ?>
                                <a class="table-page-button" href="<?php echo e(allocationPageUrl($recentPage + 1, $allocationStatusFilter, $proposalStatusFilter)); ?>" aria-label="Next allocations page">&gt;</a>
                            <?php else: ?>
                                <span class="table-page-button disabled" aria-hidden="true">&gt;</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
