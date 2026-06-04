<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/PastProjectService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pastProjectService = new PastProjectService();
$projects = $pastProjectService->getProjectsBySupervisor($_SESSION["userID"]);
$summary = $pastProjectService->getShowcaseSummary($_SESSION["userID"]);
$editingProject = null;
$showProjectForm = isset($_GET["addProject"]);

if (isset($_GET["editProjectID"])) {

    $editingProject = $pastProjectService->getProjectByID($_GET["editProjectID"], $_SESSION["userID"]);
    $showProjectForm = $editingProject !== null;
}

$projectCount = $summary["totalProjects"];
$studentsSupervised = $summary["studentsSupervised"];

function projectPdfUrl($path) {

    $fileName =
        basename((string) $path);

    return "../../storage/past_projects/" . rawurlencode($fileName);
}

function projectImageUrl($path) {

    $fileName =
        basename((string) $path);

    return "../../storage/past_project_images/" . rawurlencode($fileName);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Projects Showcase | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .hero { position: relative; overflow: hidden; }
        .hero:after { content: ""; position: absolute; right: -55px; top: -35px; width: 230px; height: 230px; border-radius: 50%; background: rgba(255,255,255,.12); }
        .hero > * { position: relative; z-index: 1; }
        .hero .button { background: #fff; color: #0d5be8; }
        .project-form { padding: 22px; margin-bottom: 24px; display: <?php echo $showProjectForm ? "block" : "none"; ?>; }
        .form-grid { display: grid; grid-template-columns: 1.5fr .6fr .9fr; gap: 12px; align-items: end; }
        .form-grid .full { grid-column: 1 / -1; }
        textarea { width: 100%; min-height: 118px; border: 1px solid #dbe6f0; border-radius: 7px; padding: 12px; resize: vertical; font: inherit; color: #172033; }
        input[type="file"].native-file { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .upload-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .upload-tile { border: 1px dashed #aac7df; border-radius: 10px; background: #f8fbff; padding: 16px; min-height: 128px; display: grid; gap: 10px; align-content: center; cursor: pointer; transition: border-color .15s ease, background .15s ease; }
        .upload-tile:hover { border-color: #0d5be8; background: #eef6ff; }
        .upload-icon { width: 38px; height: 38px; border-radius: 10px; background: #eaf3ff; color: #0d5be8; display: grid; place-items: center; font-weight: 900; }
        .upload-title { color: #1d2b3a; font-size: 15px; font-weight: 900; }
        .upload-meta { color: #6b7f91; font-size: 13px; line-height: 1.4; }
        .selected-file { color: #0d5be8; font-size: 13px; font-weight: 800; word-break: break-word; }
        .file-note { margin: 7px 0 0; color: #6b7f91; font-size: 13px; }
        .existing-file { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 10px; color: #526a7f; font-size: 14px; }
        .project-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
        .project-card { overflow: hidden; }
        .project-visual { height: 172px; background: linear-gradient(135deg, #08233d, #0d5be8); color: #fff; display: grid; place-items: center; font-weight: 900; text-align: center; padding: 22px; position: relative; text-transform: uppercase; letter-spacing: .8px; overflow: hidden; }
        .project-visual:before { content: ""; position: absolute; inset: 18px; border: 1px solid rgba(255,255,255,.18); border-radius: 12px; }
        .project-visual.alt1 { background: linear-gradient(135deg, #10223a, #30b6a5); }
        .project-visual.alt2 { background: linear-gradient(135deg, #091b23, #194060); }
        .project-visual img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; }
        .project-visual.has-image:after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(5,24,39,.12), rgba(5,24,39,.55)); }
        .project-visual.has-image:before { display: none; }
        .project-visual-title { position: relative; z-index: 1; max-width: 90%; }
        .complete { position: absolute; z-index: 2; top: 12px; right: 12px; background: #dff8e6; color: #14733e; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 900; }
        .project-body { padding: 18px; }
        .year { color: #0d5be8; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: .7px; }
        .project-title { margin: 6px 0 8px; color: #1d2b3a; font-size: 17px; line-height: 1.35; }
        .project-desc { color: #526a7f; line-height: 1.55; font-size: 14px; min-height: 72px; }
        .pill-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
        .pill { background: #eef3f8; color: #526a7f; border-radius: 6px; padding: 6px 9px; font-size: 14px; font-weight: 800; }
        .pdf-link { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; padding: 0 12px; border-radius: 6px; background: #eaf3ff; color: #0d5be8; font-size: 14px; font-weight: 900; text-decoration: none; }
        .card-actions { display: flex; gap: 8px; margin-top: 14px; }
        .small-button { min-height: 34px; padding: 0 12px; border-radius: 6px; font-size: 14px; }
        .danger { background: #c93838; color: #fff; }
        .footer-page { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; color: #6b7f91; font-size: 14px; margin-top: 26px; }
        .footer-page .pages { justify-self: center; }
        .pages span { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; background: #fff; margin-left: 5px; }
        .pages .active { background: #0d5be8; color: #fff; }
        .empty { padding: 26px; color: #526a7f; }
        @media (max-width: 1100px) { .project-grid, .form-grid, .upload-grid { grid-template-columns: 1fr; } .project-form { display: block; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("past-projects"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>

            <section class="hero">
                <div>
                    <h1>Past Projects Showcase</h1>
                    <p>A record of successfully completed student research projects.</p>
                    <div style="display:flex; gap:44px; margin-top:26px;">
                        <div><div class="stat-label">Total Projects</div><div class="stat-value"><?php echo e($projectCount); ?></div></div>
                        <div><div class="stat-label">Students Supervised</div><div class="stat-value"><?php echo e($studentsSupervised); ?></div></div>
                    </div>
                </div>
                <a class="button" href="managePastProjects.php?addProject=1">+ Add New Project</a>
            </section>

            <form class="card project-form" action="../../server/application/supervisor/managePastProjectProcess.php" method="POST" enctype="multipart/form-data" id="projectForm">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                <input type="hidden" name="action" value="<?php echo $editingProject ? "update" : "add"; ?>">
                <?php if ($editingProject): ?>
                    <input type="hidden" name="projectID" value="<?php echo e($editingProject["projectID"]); ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div>
                        <label>Project Title</label>
                        <input type="text" id="projectTitle" name="projectTitle" maxlength="255" value="<?php echo e($editingProject["projectTitle"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Completion Year</label>
                        <input type="number" id="completionYear" name="completionYear" min="2000" max="<?php echo e(((int) date("Y")) + 1); ?>" value="<?php echo e($editingProject["completionYear"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Alumni Name</label>
                        <input type="text" id="alumniName" name="alumniName" maxlength="100" value="<?php echo e($editingProject["alumniName"] ?? ""); ?>" required>
                    </div>
                    <div class="full">
                        <label>Project Description / Abstract</label>
                        <textarea id="projectDescription" name="projectDescription" maxlength="1000" required><?php echo e($editingProject["projectDescription"] ?? ""); ?></textarea>
                        <p class="file-note">Briefly describe the project scope, domain, technology, or research outcome.</p>
                    </div>
                    <div class="full">
                        <div class="upload-grid">
                            <div>
                                <label>Past Project PDF</label>
                                <label class="upload-tile" for="projectPDF">
                                    <span class="upload-icon">PDF</span>
                                    <span class="upload-title">Choose project document</span>
                                    <span class="upload-meta">Optional PDF, maximum 5.0 MB. Students can open it from the public profile.</span>
                                    <span class="selected-file" data-file-label="projectPDF">No PDF selected</span>
                                </label>
                                <input class="native-file" type="file" id="projectPDF" name="projectPDF" accept="application/pdf,.pdf">
                                <?php if (!empty($editingProject["projectPDFPath"])): ?>
                                    <div class="existing-file">
                                        <a class="pdf-link" href="<?php echo e(projectPdfUrl($editingProject["projectPDFPath"])); ?>" target="_blank" rel="noopener">View Current PDF</a>
                                        <label style="margin:0; display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:14px; color:#526a7f;">
                                            <input type="checkbox" name="removeProjectPDF" value="1" style="width:auto; height:auto;">
                                            Remove current PDF
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label>Project Cover Image</label>
                                <label class="upload-tile" for="projectImage">
                                    <span class="upload-icon">IMG</span>
                                    <span class="upload-title">Choose cover image</span>
                                    <span class="upload-meta">Optional JPG or PNG, maximum 2.0 MB. This replaces the generated card banner.</span>
                                    <span class="selected-file" data-file-label="projectImage">No image selected</span>
                                </label>
                                <input class="native-file" type="file" id="projectImage" name="projectImage" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                                <?php if (!empty($editingProject["projectImagePath"])): ?>
                                    <div class="existing-file">
                                        <a class="pdf-link" href="<?php echo e(projectImageUrl($editingProject["projectImagePath"])); ?>" target="_blank" rel="noopener">View Current Image</a>
                                        <label style="margin:0; display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:14px; color:#526a7f;">
                                            <input type="checkbox" name="removeProjectImage" value="1" style="width:auto; height:auto;">
                                            Remove current image
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="full">
                        <button class="button" type="submit"><?php echo $editingProject ? "Update Project" : "Add Project"; ?></button>
                        <?php if ($editingProject): ?>
                            <a class="button secondary" href="managePastProjects.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (empty($projects)): ?>
                <section class="card empty">No past projects have been added yet.</section>
            <?php else: ?>
                <section class="project-grid">
                    <?php foreach ($projects as $index => $project): ?>
                        <article class="card project-card">
                            <div class="project-visual alt<?php echo e($index % 3); ?> <?php echo !empty($project["projectImagePath"]) ? "has-image" : ""; ?>">
                                <?php if (!empty($project["projectImagePath"])): ?>
                                    <img src="<?php echo e(projectImageUrl($project["projectImagePath"])); ?>" alt="">
                                <?php endif; ?>
                                <span class="complete">Completed</span>
                                <span class="project-visual-title"><?php echo e($project["projectTitle"]); ?></span>
                            </div>
                            <div class="project-body">
                                <div class="year"><?php echo e($project["completionYear"]); ?> Academic Year</div>
                                <h2 class="project-title"><?php echo e($project["projectTitle"]); ?></h2>
                                <p class="project-desc"><?php echo e($project["projectDescription"] ?: "No project description has been added yet."); ?></p>
                                <div class="pill-row">
                                    <span class="pill">Research</span>
                                    <span class="pill">FYP</span>
                                    <span class="pill">Alumni: <?php echo e($project["alumniName"]); ?></span>
                                </div>
                                <div class="card-actions">
                                    <?php if (!empty($project["projectPDFPath"])): ?>
                                        <a class="pdf-link" href="<?php echo e(projectPdfUrl($project["projectPDFPath"])); ?>" target="_blank" rel="noopener">View PDF</a>
                                    <?php endif; ?>
                                    <a class="button secondary small-button" href="managePastProjects.php?editProjectID=<?php echo e($project["projectID"]); ?>">Edit</a>
                                    <form action="../../server/application/supervisor/managePastProjectProcess.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="projectID" value="<?php echo e($project["projectID"]); ?>">
                                        <button class="button danger small-button" type="submit" onclick="return confirm('Delete this project?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
                <div class="footer-page">
                    <span>Showing <?php echo e($projectCount); ?> archived projects</span>
                    <div class="pages"><span class="active">1</span><span>2</span><span>3</span></div>
                    <span></span>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        const form = document.getElementById("projectForm");
        if (form) {
            document.querySelectorAll(".native-file").forEach(function(input) {
                input.addEventListener("change", function() {
                    const label = document.querySelector('[data-file-label="' + input.id + '"]');
                    if (label) {
                        label.textContent = input.files.length ? input.files[0].name : "No file selected";
                    }
                });
            });

            form.addEventListener("submit", function(event) {
                const year = parseInt(document.getElementById("completionYear").value);
                const currentYear = new Date().getFullYear() + 1;
                if (isNaN(year) || year < 2000 || year > currentYear) {
                    event.preventDefault();
                    alert("Please enter a valid completion year.");
                }
            });
        }
    </script>
</body>
</html>
