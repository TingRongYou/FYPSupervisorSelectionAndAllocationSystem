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
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/supervisor.css">
    <script src="../assets/js/supervisor.js" defer></script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("past-projects"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>

            <section class="card hero">
                <div>
                    <h1>Past Projects Showcase</h1>
                    <p>A record of successfully completed student research projects.</p>
                    <div style="display:flex; gap:44px; margin-top:26px;">
                        <div>
                            <div class="stat-label" style="color: #b9d2ff;">Total Projects</div>
                            <div class="stat-value" style="font-size: 28px; font-weight: 800; color: #fff;"><?php echo e($projectCount); ?></div>
                        </div>
                        <div>
                            <div class="stat-label" style="color: #b9d2ff;">Students Supervised</div>
                            <div class="stat-value" style="font-size: 28px; font-weight: 800; color: #fff;"><?php echo e($studentsSupervised); ?></div>
                        </div>
                    </div>
                </div>
                <a class="button" href="managePastProjects.php?addProject=1">+ Add New Project</a>
            </section>

            <form class="card project-form" style="display: <?php echo $showProjectForm ? "block" : "none"; ?>;" action="../../server/application/supervisor/managePastProjectProcess.php" method="POST" enctype="multipart/form-data" id="projectForm">
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
                                        <label style="margin:0; display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:14px; color:#526a7f; font-weight: normal;">
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
                                        <label style="margin:0; display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:14px; color:#526a7f; font-weight: normal;">
                                            <input type="checkbox" name="removeProjectImage" value="1" style="width:auto; height:auto;">
                                            Remove current image
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="full" style="margin-top: 10px;">
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
</body>
</html>