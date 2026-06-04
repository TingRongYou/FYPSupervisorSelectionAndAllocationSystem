<?php

require_once "../../server/application/auth/SessionManager.php";
require_once "../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$requestDAO = new RequestDAO();
$supervisorID = $_SESSION["userID"];
$page = max(1, (int) ($_GET["page"] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$totalSupervisees = $requestDAO->countSuperviseesBySupervisor($supervisorID);
$supervisees = $requestDAO->getSuperviseesBySupervisor($supervisorID, $perPage, $offset);
$totalPages = max(1, (int) ceil($totalSupervisees / $perPage));
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Supervisees | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .page-shell { max-width: 1320px; margin: 0 auto; }
        .page-head { margin: 4px 0 22px; }
        .page-head h1 { margin: 0 0 6px; color: #172033; font-size: 28px; }
        .page-head p { margin: 0; color: #6b7f91; }
        .summary-strip { display: grid; grid-template-columns: 1fr 1fr auto; align-items: center; gap: 22px; padding: 18px 24px; margin-bottom: 22px; }
        .summary-item { display: flex; align-items: center; gap: 12px; min-height: 44px; border-right: 1px solid #edf2f7; }
        .summary-item:last-child { border-right: 0; }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #0d5be8; }
        .dot.green { background: #22c55e; }
        .summary-label { color: #7c8da0; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .summary-value { color: #172033; font-size: 18px; font-weight: 900; margin-left: 18px; }
        .updated { color: #7c8da0; font-size: 14px; white-space: nowrap; }
        .table-card { overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 22px; text-align: left; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        th { background: #f8fafc; color: #7c8da0; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .student-cell { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-size: 14px; font-weight: 900; flex: 0 0 auto; }
        .avatar { overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .student-name { color: #172033; font-weight: 900; }
        .muted { color: #7c8da0; font-size: 14px; margin-top: 3px; }
        .programme { color: #526a7f; line-height: 1.35; max-width: 230px; }
        .status { display: inline-flex; justify-content: center; min-width: 68px; padding: 6px 10px; border-radius: 6px; background: #dcfce7; color: #166534; font-size: 13px; font-weight: 900; text-transform: uppercase; }
        .detail-link { color: #0d5be8; font-size: 14px; font-weight: 900; text-decoration: none; line-height: 1.2; display: inline-block; }
        .detail-link.disabled { color: #9aacc0; pointer-events: none; }
        .inline-form { margin: 0; }
        .link-button { border: 0; background: transparent; padding: 0; color: #0d5be8; font: inherit; font-size: 14px; font-weight: 900; text-align: left; line-height: 1.2; cursor: pointer; }
        .link-button.requested { color: #64748b; cursor: default; }
        .footer-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 22px; color: #6b7f91; font-size: 14px; }
        .pager { display: flex; align-items: center; gap: 7px; }
        .pager a, .pager span { min-width: 30px; height: 30px; border-radius: 7px; display: grid; place-items: center; text-decoration: none; color: #526a7f; border: 1px solid #dbe6f0; background: #fff; font-weight: 800; }
        .pager .active { background: #003f8f; color: #fff; border-color: #003f8f; }
        .empty-state { padding: 34px; color: #6b7f91; }
        @media (max-width: 900px) { .summary-strip { grid-template-columns: 1fr; } .summary-item { border-right: 0; border-bottom: 1px solid #edf2f7; padding-bottom: 12px; } table { min-width: 820px; } .table-card { overflow-x: auto; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("supervision"); ?>
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
                        <span class="summary-value"><?php echo e($totalSupervisees); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="dot green"></span>
                        <span class="summary-label">Active<br>Students:</span>
                        <span class="summary-value"><?php echo e($totalSupervisees); ?></span>
                    </div>
                    <div class="updated">Last updated: Today, <?php echo e(date("h:i A")); ?></div>
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
                                                        <img src="<?php echo e($supervisee["profilePhotoPath"]); ?>" alt="">
                                                    <?php else: ?>
                                                        <?php echo e(supervisorInitials($supervisee["fullName"])); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="student-name"><?php echo e($supervisee["fullName"]); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo e($supervisee["studentID"]); ?></td>
                                        <td><?php echo e(formatResearchTitle($supervisee["projectTitle"])); ?></td>
                                        <td><div class="programme"><?php echo e($supervisee["programme"]); ?></div></td>
                                        <td><span class="status">Active</span></td>
                                        <td>
                                            <?php if (!empty($supervisee["requestID"]) && $supervisee["decisionStatus"] !== "Proposal Requested"): ?>
                                                <a class="detail-link" href="supervisorRequestDecision.php?requestID=<?php echo e($supervisee["requestID"]); ?>&source=supervision">View<br>Details</a>
                                            <?php elseif ($supervisee["decisionStatus"] === "Proposal Requested"): ?>
                                                <span class="link-button requested">Proposal<br>Requested</span>
                                            <?php else: ?>
                                                <form class="inline-form" action="../../server/application/supervisor/requestProposal.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                                                    <input type="hidden" name="allocationID" value="<?php echo e($supervisee["allocationID"]); ?>">
                                                    <button class="link-button" type="submit">Request<br>Proposal</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="footer-row">
                            <span>Showing <?php echo e($start); ?> to <?php echo e($end); ?> of <?php echo e($totalSupervisees); ?> supervisees</span>
                            <div class="pager">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo e($page - 1); ?>">&lt;</a>
                                <?php endif; ?>
                                <span class="active"><?php echo e($page); ?></span>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo e($page + 1); ?>">&gt;</a>
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
