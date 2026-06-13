<?php

require_once "../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

$requestID =
    (int) ($_GET["requestID"] ?? 0);

$requestDAO =
    new RequestDAO();

$request =
    $requestID > 0
        ? $requestDAO->getApplicationRequestForStudent(
            $requestID,
            $_SESSION["userID"]
        )
        : null;

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function proposalStatusClass($status) {

    $status =
        strtolower(trim((string) $status));

    if ($status === "accepted") {

        return "accepted";
    }

    if (
        $status === "rejected" ||
        $status === "rejected-timeout"
    ) {

        return "rejected";
    }

    return "pending";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Details | SSAS</title>
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 32px 40px 56px; min-width: 0; }
        .details-shell { width: 100%; max-width: 1280px; margin: 0 auto; }
        .breadcrumb { margin-bottom: 18px; color: #8a9caf; font-size: 13px; font-weight: 900; letter-spacing: .8px; text-transform: uppercase; }
        .breadcrumb a { color: #0b66d8; text-decoration: none; }
        .details-card { padding: 26px; background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; box-shadow: 0 8px 22px rgba(11,79,138,.07); }
        .details-header { margin-bottom: 22px; }
        .eyebrow { margin: 0 0 7px; color: #0b66d8; font-size: 12px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin: 0; color: #172033; font-size: 30px; line-height: 1.15; }
        .status { display: inline-flex; align-items: center; justify-content: center; min-height: 28px; padding: 0 12px; border-radius: 999px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .status.pending { background: #fff4cc; color: #9a6400; }
        .status.accepted { background: #dcfce7; color: #118549; }
        .status.rejected { background: #fee2e2; color: #c02d2d; }
        .decision-panel { margin-bottom: 22px; padding: 18px 20px; border: 1px solid #d9e7f3; border-radius: 10px; background: #f8fbff; }
        .decision-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e1eaf3; }
        .decision-label { margin: 0; color: #7c8da0; font-size: 12px; font-weight: 900; letter-spacing: .8px; text-transform: uppercase; }
        .comment-label { display: block; margin-bottom: 6px; color: #8a9caf; font-size: 11px; font-weight: 900; letter-spacing: .8px; text-transform: uppercase; }
        .comment { min-height: 46px; padding: 12px 14px; border: 1px solid #e1eaf3; border-radius: 8px; background: #fff; color: #526a7f; line-height: 1.55; white-space: pre-wrap; }
        .pdf-viewer { border: 1px solid #d9e7f3; border-radius: 10px; overflow: hidden; background: #f8fbff; }
        .pdf-toolbar { min-height: 54px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px; background: #fff; border-bottom: 1px solid #d9e7f3; }
        .pdf-title { color: #172033; font-weight: 900; }
        .pdf-action { min-height: 34px; padding: 0 12px; border-radius: 7px; background: #0d5be8; color: #fff; display: inline-flex; align-items: center; text-decoration: none; font-size: 13px; font-weight: 900; }
        .pdf-frame { display: block; width: 100%; height: 760px; min-height: 70vh; border: 0; background: #eef6ff; }
        .empty-state { padding: 30px; background: #fff; border: 1px dashed #aac7df; border-radius: 12px; color: #526a7f; }
        @media (max-width: 760px) {
            .main { padding: 24px 18px 46px; }
            .decision-heading { align-items: flex-start; }
            .pdf-toolbar { display: block; }
            .pdf-action { width: 100%; justify-content: center; margin-top: 10px; }
            .pdf-frame { height: 520px; min-height: 0; }
        }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("application-status"); ?>
        <main class="main">
            <div class="details-shell">
                <div class="breadcrumb"><a href="studentApplicationStatus.php">Application Status</a> &gt; Proposal Details</div>

                <?php if (!$request): ?>
                    <section class="empty-state">The selected proposal was not found or does not belong to your account.</section>
                <?php else: ?>
                    <section class="details-card">
                        <header class="details-header">
                            <div>
                                <p class="eyebrow">Project Proposal</p>
                                <h1><?php echo e($request["projectTitle"]); ?></h1>
                            </div>
                        </header>

                        <section class="decision-panel">
                            <div class="decision-heading">
                                <h2 class="decision-label">Supervisor Decision</h2>
                                <span class="status <?php echo e(proposalStatusClass($request["decisionStatus"])); ?>">
                                    <?php echo e($request["decisionStatus"]); ?>
                                </span>
                            </div>
                            <span class="comment-label">Supervisor Comment</span>
                            <div class="comment"><?php echo e(trim((string) $request["supervisorComment"]) !== "" ? $request["supervisorComment"] : "No supervisor comment has been recorded."); ?></div>
                        </section>

                        <?php if (trim((string) $request["proposalPDFPath"]) !== ""): ?>
                            <?php $proposalUrl = "../../server/application/student/viewProposal.php?requestID=" . urlencode($request["requestID"]); ?>
                            <div class="pdf-viewer">
                                <div class="pdf-toolbar">
                                    <div class="pdf-title">Proposal Document</div>
                                    <a class="pdf-action" href="<?php echo e($proposalUrl); ?>" target="_blank">Open in New Tab</a>
                                </div>
                                <iframe class="pdf-frame" src="<?php echo e($proposalUrl); ?>" title="Proposal Document"></iframe>
                            </div>
                        <?php else: ?>
                            <section class="empty-state">No proposal PDF has been submitted for this request.</section>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
