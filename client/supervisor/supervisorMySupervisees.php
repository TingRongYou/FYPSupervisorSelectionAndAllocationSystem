<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/../shared/accountLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$requestDAO = new RequestDAO();
$supervisorID = $_SESSION["userID"];
$page = max(1, (int) ($_GET["page"] ?? 1));
$perPage = 3;
$totalSupervisees = $requestDAO->countSuperviseesBySupervisor($supervisorID);
$totalPages = max(1, (int) ceil($totalSupervisees / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$supervisees = $requestDAO->getSuperviseesBySupervisor($supervisorID, $perPage, $offset);
$start = $totalSupervisees === 0 ? 0 : $offset + 1;
$end = min($offset + count($supervisees), $totalSupervisees);

function formatResearchTitle($title) {
    $title = trim((string) $title);

    if (strlen($title) <= 42) {
        return $title;
    }

    return substr($title, 0, 39) . "...";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("My Supervisees", "supervisor"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo ssasPortalSidebar("supervision"); ?>
        <main class="main">
            <div class="page-shell">
                <section class="page-head">
                    <h1>My Supervisees</h1>
                    <p>Students under your supervision</p>
                </section>

                <section class="summary-strip card">
                    <div class="summary-item">
                        <span class="dot"></span>
                        <span class="summary-label">Total<br>Supervisees:</span>
                        <span class="summary-value"><?php echo ssasEscape($totalSupervisees); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="dot green"></span>
                        <span class="summary-label">Active<br>Students:</span>
                        <span class="summary-value"><?php echo ssasEscape($totalSupervisees); ?></span>
                    </div>
                    <div class="updated">Last updated: Today, <?php echo ssasEscape(date("h:i A")); ?></div>
                </section>

                <section class="table-card card">
                    <?php if (empty($supervisees)): ?>
                        <div class="empty-state">No supervisees - Your supervisees list is currently empty.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Student ID</th>
                                    <th>Research Title</th>
                                    <th>Programme</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supervisees as $supervisee): ?>
                                    <tr>
                                        <td>
                                            <div class="student-cell">
                                                <div class="avatar">
                                                    <?php if (!empty($supervisee["profilePhotoPath"])): ?>
                                                        <img src="<?php echo ssasEscape($supervisee["profilePhotoPath"]); ?>" alt="">
                                                    <?php else: ?>
                                                        <?php echo ssasEscape(ssasInitials($supervisee["fullName"])); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="student-name"><?php echo ssasEscape($supervisee["fullName"]); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo ssasEscape($supervisee["studentID"]); ?></td>
                                        <td><?php echo ssasEscape(formatResearchTitle($supervisee["projectTitle"])); ?></td>
                                        <td><div class="programme"><?php echo ssasEscape($supervisee["programme"]); ?></div></td>
                                        <td><span class="status">Active</span></td>
                                        <td>
                                            <?php if (!empty($supervisee["requestID"]) && $supervisee["decisionStatus"] !== "Proposal Requested"): ?>
                                                <a class="detail-link" href="supervisorRequestDecision.php?requestID=<?php echo ssasEscape($supervisee["requestID"]); ?>&source=supervision">View<br>Details</a>
                                            <?php elseif ($supervisee["decisionStatus"] === "Proposal Requested"): ?>
                                                <span class="link-button requested">Proposal<br>Requested</span>
                                            <?php else: ?>
                                                <form class="inline-form" action="../../server/application/supervisor/requestProposal.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                                                    <input type="hidden" name="allocationID" value="<?php echo ssasEscape($supervisee["allocationID"]); ?>">
                                                    <button class="link-button" type="submit">Request<br>Proposal</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="footer-row">
                            <span>Showing <?php echo ssasEscape($start); ?>-<?php echo ssasEscape($end); ?> of <?php echo ssasEscape($totalSupervisees); ?> supervisees</span>
                            <div class="table-pager" aria-label="Supervisees pagination">
                                <?php if ($page > 1): ?>
                                    <a class="table-page-button" href="?page=<?php echo ssasEscape($page - 1); ?>" aria-label="Previous supervisees page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo ssasEscape($page); ?> of <?php echo ssasEscape($totalPages); ?></span>

                                <?php if ($page < $totalPages): ?>
                                    <a class="table-page-button" href="?page=<?php echo ssasEscape($page + 1); ?>" aria-label="Next supervisees page">&gt;</a>
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
