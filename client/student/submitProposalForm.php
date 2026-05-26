<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorProfileService.php";
require_once __DIR__ . "/studentLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$supervisorID = trim($_GET["supervisorID"] ?? "");
$profileService = new SupervisorProfileService();
$profile = $supervisorID !== "" ? $profileService->getDigitalBusinessCard($supervisorID) : null;

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
    <style>
        <?php echo ssasAccountStyles(); ?>
        <?php echo studentSidebarStyles(); ?>

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #172033; }
        .main { flex: 1; padding: 34px 56px 56px; }
        .proposal-shell { width: 100%; max-width: 1320px; margin: 0 auto; }
        .breadcrumb { color: #9aacc0; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 900; margin-bottom: 14px; }
        h1 { margin: 0 0 10px; color: #003f8f; font-size: 30px; line-height: 1.1; }
        .subtitle { color: #5d7085; line-height: 1.55; margin: 0 0 30px; max-width: 820px; white-space: nowrap; }
        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        .proposal-card { display: grid; grid-template-columns: minmax(0, 1fr) 340px; background: #fff; border: 1px solid #d9e7f3; border-radius: 12px; overflow: hidden; box-shadow: 0 14px 28px rgba(11,79,138,.08); }
        .form-panel { padding: 56px 62px; }
        .side-panel { padding: 46px 40px; background: #fbfdff; border-left: 1px solid #edf2f7; }
        label { display: block; color: #7c8da0; text-transform: uppercase; letter-spacing: 1px; font-size: 10px; font-weight: 900; margin-bottom: 10px; }
        input[type="text"] { width: 100%; height: 52px; border: 1px solid #dbe6f0; border-radius: 8px; background: #f8fafc; padding: 0 14px; color: #172033; font-size: 14px; }
        .hint { margin: 9px 0 32px; color: #9aacc0; font-size: 11px; }
        .drop-zone { min-height: 350px; border: 1px dashed #cfe0ef; border-radius: 10px; background: #f8fbff; display: grid; place-items: center; text-align: center; color: #526a7f; position: relative; cursor: pointer; padding: 30px; transition: border-color .2s, background .2s, color .2s; }
        .drop-zone.valid { border-color: #22c55e; background: #f0fdf4; color: #166534; }
        .drop-zone.invalid { border-color: #ef4444; background: #fff5f5; color: #991b1b; }
        .drop-zone.valid .upload-icon { background: #dcfce7; color: #166534; }
        .drop-zone.invalid .upload-icon { background: #fee2e2; color: #991b1b; }
        .drop-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .upload-icon { width: 46px; height: 46px; border-radius: 50%; background: #eaf3ff; color: #003f8f; display: grid; place-items: center; margin: 0 auto 12px; font-size: 22px; font-weight: 900; }
        .upload-title { color: #172033; font-size: 13px; font-weight: 900; }
        .upload-hint { color: #9aacc0; font-size: 12px; margin-top: 5px; }
        .file-name { display: block; color: #0d5be8; font-weight: 800; margin-top: 12px; word-break: break-word; font-size: 15px; line-height: 1.45; }
        .drop-zone.valid .file-name { color: #166534; }
        .drop-zone.invalid .file-name { color: #991b1b; }
        .tip { margin: 14px 0 32px; border: 1px solid #dbe6f0; background: #f8fbff; color: #0d5be8; font-size: 11px; font-weight: 800; border-radius: 7px; padding: 12px 14px; }
        .button { width: 100%; min-height: 50px; border: 0; border-radius: 7px; background: #003f8f; color: #fff; font-size: 13px; font-weight: 900; cursor: pointer; box-shadow: 0 12px 22px rgba(0,63,143,.22); }
        .requirements-title { color: #003f8f; text-transform: uppercase; letter-spacing: 1px; font-size: 11px; font-weight: 900; margin-bottom: 24px; }
        .requirement { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 22px; padding: 10px; border-radius: 9px; transition: background .2s, border-color .2s; }
        .check { width: 22px; height: 22px; border-radius: 50%; background: #f1f7ff; color: #003f8f; display: grid; place-items: center; font-weight: 900; flex: 0 0 auto; }
        .requirement strong { display: block; color: #172033; font-size: 12px; }
        .requirement span { display: block; color: #9aacc0; font-size: 11px; margin-top: 2px; }
        .requirement.valid { background: #f0fdf4; }
        .requirement.valid .check { background: #22c55e; color: #fff; }
        .requirement.valid strong { color: #166534; }
        .requirement.valid span { color: #15803d; }
        .requirement.invalid { background: #fff5f5; }
        .requirement.invalid .check { background: #ef4444; color: #fff; }
        .requirement.invalid strong { color: #991b1b; }
        .requirement.invalid span { color: #b91c1c; }
        .help-card { margin-top: 36px; border: 1px solid #d9e7f3; border-radius: 12px; padding: 18px; background: #fff; color: #526a7f; font-size: 12px; line-height: 1.5; }
        .help-card strong { display: block; color: #9aacc0; text-transform: uppercase; letter-spacing: 1px; font-size: 10px; margin-bottom: 8px; }
        .empty { background: #fff; border: 1px dashed #aac7df; border-radius: 8px; padding: 28px; color: #526a7f; }
        @media (max-width: 1100px) { .subtitle { white-space: normal; } }
        @media (max-width: 900px) { .main { padding: 24px 20px; } .proposal-card { grid-template-columns: 1fr; } .side-panel { border-left: 0; border-top: 1px solid #edf2f7; } }
    </style>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("discovery"); ?>
        <main class="main">
            <div class="proposal-shell">
                <div class="breadcrumb">Discovery > <?php echo $profile ? e($profile["fullName"]) : "Supervisor"; ?> > Submit Proposal</div>
                <?php echo statusMessage(); ?>
                <h1>Proposal Submission</h1>
                <p class="subtitle">Upload your project proposal for review. Ensure all documentation adheres to the TAR UMT SSAS guidelines.</p>

                <?php if (!$profile): ?>
                    <section class="empty">Supervisor profile was not found.</section>
                <?php else: ?>
                    <form class="proposal-card" action="../../server/application/student/submitProposal.php" method="POST" enctype="multipart/form-data" id="proposalForm">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                        <input type="hidden" name="supervisorID" value="<?php echo e($supervisorID); ?>">
                        <section class="form-panel">
                            <label for="projectTitle">Project Title</label>
                            <input type="text" id="projectTitle" name="projectTitle" maxlength="120" required placeholder="e.g., AI-Driven Analytics for Campus Sustainability">
                            <p class="hint">Must be unique and concise (max 120 characters).</p>

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
                            <button class="button" type="submit">Submit Proposal</button>
                        </section>

                        <aside class="side-panel">
                            <div class="requirements-title">Requirements</div>
                            <div class="requirement">
                                <span class="check">âœ“</span>
                                <div><strong>File Type</strong><span>PDF only</span></div>
                            </div>
                            <div class="requirement">
                                <span class="check">âœ“</span>
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

    <script>
        const proposalPDF = document.getElementById("proposalPDF");
        const fileName = document.getElementById("fileName");
        const dropZone = document.getElementById("dropZone");
        const uploadIcon = document.querySelector(".upload-icon");
        const requirementRows = document.querySelectorAll(".requirement");
        const fileTypeRequirement = requirementRows[0] || null;
        const fileSizeRequirement = requirementRows[1] || null;
        const maxBytes = 5 * 1024 * 1024;

        function setRequirementState(element, state) {
            if (!element) {
                return;
            }

            element.classList.remove("valid", "invalid");

            if (state) {
                element.classList.add(state);
            }
        }

        function validateFile(file, showMissing = false) {
            if (!file) {
                fileName.textContent = "";
                dropZone.classList.remove("valid", "invalid");
                if (uploadIcon) {
                    uploadIcon.textContent = "â†‘";
                }
                setRequirementState(fileTypeRequirement, showMissing ? "invalid" : "");
                setRequirementState(fileSizeRequirement, showMissing ? "invalid" : "");
                return false;
            }

            const isPdf = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");
            const isWithinLimit = file.size <= maxBytes;

            setRequirementState(fileTypeRequirement, isPdf ? "valid" : "invalid");
            setRequirementState(fileSizeRequirement, isWithinLimit ? "valid" : "invalid");

            if (!isPdf || !isWithinLimit) {
                dropZone.classList.remove("valid");
                dropZone.classList.add("invalid");
                if (uploadIcon) {
                    uploadIcon.textContent = "!";
                }
                fileName.textContent = file.name + " - " + (!isPdf ? "PDF only" : "exceeds 5MB");
                proposalPDF.value = "";
                return false;
            }

            dropZone.classList.remove("invalid");
            dropZone.classList.add("valid");
            if (uploadIcon) {
                uploadIcon.textContent = "âœ“";
            }
            fileName.textContent = file.name;
            return true;
        }

        if (proposalPDF) {
            proposalPDF.addEventListener("change", function() {
                validateFile(proposalPDF.files[0]);
            });
        }

        if (dropZone) {
            dropZone.addEventListener("dragover", function(event) {
                event.preventDefault();
            });

            dropZone.addEventListener("drop", function(event) {
                event.preventDefault();

                if (event.dataTransfer.files.length) {
                    proposalPDF.files = event.dataTransfer.files;
                    validateFile(event.dataTransfer.files[0]);
                }
            });
        }

        const proposalForm = document.getElementById("proposalForm");

        if (proposalForm) {
            proposalForm.addEventListener("submit", function(event) {
                if (!proposalForm.checkValidity() || !validateFile(proposalPDF.files[0], true)) {
                    event.preventDefault();
                    return;
                }

                if (!confirm("Submit this proposal to the selected supervisor?")) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>



