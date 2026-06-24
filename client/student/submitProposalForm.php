<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorProfileService.php";
require_once __DIR__ . "/../../server/business/services/AllocationWindowService.php";
require_once __DIR__ . "/../../server/data/dao/RequestDAO.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$supervisorID = trim($_GET["supervisorID"] ?? "");
$requestID = (int) ($_GET["requestID"] ?? 0);
$requestDAO = new RequestDAO();
$requestedProposal =
    $requestID > 0
        ? $requestDAO->getProposalRequestForStudent($requestID, $_SESSION["userID"])
        : null;

if ($requestedProposal) {

    $supervisorID =
        $requestedProposal["supervisorID"];
}

$isResubmission =
    $requestedProposal &&
    ($requestedProposal["decisionStatus"] ?? "") === "Rejected";

$profileService = new SupervisorProfileService();
$profile = $supervisorID !== "" ? $profileService->getDigitalBusinessCard($supervisorID) : null;
$allocationWindowService = new AllocationWindowService();
$allocationWindow = $allocationWindowService->getWindow();
$canApplyToSupervisor =
    $profile && (
        (bool) ($profile["canApply"] ?? false) ||
        $requestedProposal !== null
    );

function e($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function statusMessage() {

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class = $_GET["status"] === "success" ? "success" : "error";

    return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Submission | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("discovery"); ?>
        <main class="main">
            <div class="proposal-shell">
                <div class="breadcrumb">Discovery > <?php echo $profile ? e($profile["fullName"]) : "Supervisor"; ?> > <?php echo $isResubmission ? "Resubmit Proposal" : "Submit Proposal"; ?></div>
                <?php echo statusMessage(); ?>
                
                <h1><?php echo $isResubmission ? "Proposal Resubmission" : "Proposal Submission"; ?></h1>
                <p class="subtitle">
                    <?php echo $isResubmission
                        ? "Upload a revised project proposal for supervisor review."
                        : "Upload your project proposal for review. Ensure all documentation adheres to the guidelines."; ?>
                </p>

                <?php if (!$profile): ?>
                    <section class="empty">Supervisor profile was not found.</section>
                <?php elseif (!$allocationWindow["canStudentsSubmit"] && !$requestedProposal): ?>
                    <section class="empty"><?php echo e($allocationWindow["statusText"]); ?></section>
                <?php elseif (!$canApplyToSupervisor): ?>
                    <section class="empty"><?php echo e($profile["message"] ?? "This supervisor is not accepting proposals at this time."); ?></section>
                <?php else: ?>
                    <form class="proposal-card" action="../../server/application/student/submitProposal.php" method="POST" enctype="multipart/form-data" id="proposalForm">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <input type="hidden" name="supervisorID" value="<?php echo e($supervisorID); ?>">
                        <input type="hidden" name="requestID" value="<?php echo e($requestID); ?>">
                        <section class="form-panel">
                            <label for="projectTitle">Project Title</label>
                            <input type="text" id="projectTitle" name="projectTitle" maxlength="255" required placeholder="e.g., AI-Driven Analytics for Campus Sustainability">
                            <p class="hint">Must be unique and concise (max 255 characters).</p>

                            <label for="proposalPDF">Proposal Document (PDF)</label>
                            <div class="drop-zone" id="dropZone">
                                <div>
                                    <div class="upload-icon">↑</div>
                                    <div class="upload-title">Click to upload or drag and drop</div>
                                    <div class="upload-hint">PDF format only (Max 5.0MB)</div>
                                    <span class="file-name" id="fileName"></span>
                                </div>
                                <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                                <input type="file" id="proposalPDF" name="proposalPDF" accept="application/pdf" required>
                            </div>
                            <div class="tip">Recommended: Include a bibliography in the appendix.</div>
                            <button class="button" type="submit"><?php echo $isResubmission ? "Resubmit Proposal" : "Submit Proposal"; ?></button>
                        </section>

                        <aside class="side-panel">
                            <div class="requirements-title">Requirements</div>
                            <div class="requirement">
                                <span class="check">✓</span>
                                <div><strong>File Type</strong><span>PDF only</span></div>
                            </div>
                            <div class="requirement">
                                <span class="check">✓</span>
                                <div><strong>File Size</strong><span>Less than 5MB</span></div>
                            </div>
                            <div class="help-card">
                                <strong>Need Help?</strong>
                                Contact the Research Office if you encounter issues.
                            </div>
                        </aside>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>