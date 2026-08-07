<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/PastProjectService.php";
require_once __DIR__ . "/../shared/accountLayout.php";

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
$projectPage = max(1, (int) ($_GET["projectPage"] ?? 1));
$projectsPerPage = 3;

if (isset($_GET["editProjectID"])) {
    $editingProject = $pastProjectService->getProjectByID($_GET["editProjectID"], $_SESSION["userID"]);
    $showProjectForm = $editingProject !== null;
}

$projectCount = $summary["totalProjects"];
$studentsSupervised = $summary["studentsSupervised"];
$totalProjectPages = max(1, (int) ceil($projectCount / $projectsPerPage));
$projectPage = min($projectPage, $totalProjectPages);
$projectOffset = ($projectPage - 1) * $projectsPerPage;
$visibleProjects = array_slice($projects, $projectOffset, $projectsPerPage);
$projectStart = $projectCount === 0 ? 0 : $projectOffset + 1;
$projectEnd = min($projectOffset + count($visibleProjects), $projectCount);

function projectPdfUrl($path) {
    $fileName = basename((string) $path);

    return "../../storage/past_projects/" . rawurlencode($fileName);
}

function projectImageUrl($path) {
    $fileName = basename((string) $path);

    return "../../storage/past_project_images/" . rawurlencode($fileName);
}

function projectPageUrl($page) {
    return "managePastProjects.php?projectPage=" . max(1, (int) $page);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Past Projects Showcase", "supervisor"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="content-shell">
        <?php echo ssasPortalSidebar("past-projects"); ?>
        <main class="main">
            <?php echo ssasStatusMessage(); ?>

            <section class="card hero">
                <div>
                    <h1><?php echo $editingProject ? "Edit Past Project" : ($showProjectForm ? "Add Past Project" : "Past Projects Showcase"); ?></h1>
                    <p><?php echo $editingProject ? "Update the project details, PDF document, and cover image shown in the showcase." : ($showProjectForm ? "Add a completed student research project to your showcase." : "A record of successfully completed student research projects."); ?></p>
                    <?php if (!$showProjectForm): ?>
                        <div style="display:flex; gap:44px; margin-top:26px;">
                            <div>
                                <div class="stat-label" style="color: #b9d2ff;">Total Projects</div>
                                <div class="stat-value" style="font-size: 28px; font-weight: 800; color: #fff;"><?php echo ssasEscape($projectCount); ?></div>
                            </div>
                            <div>
                                <div class="stat-label" style="color: #b9d2ff;">Students Supervised</div>
                                <div class="stat-value" style="font-size: 28px; font-weight: 800; color: #fff;"><?php echo ssasEscape($studentsSupervised); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!$showProjectForm): ?>
                    <a class="button" href="managePastProjects.php?addProject=1">+ Add New Project</a>
                <?php endif; ?>
            </section>

            <form class="card project-form" style="display: <?php echo ssasEscape($showProjectForm ? "block" : "none"); ?>;" action="../../server/application/supervisor/managePastProjectProcess.php" method="POST" enctype="multipart/form-data" id="projectForm">
                <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                <input type="hidden" name="action" value="<?php echo ssasEscape($editingProject ? "update" : "add"); ?>">
                <?php if ($editingProject): ?>
                    <input type="hidden" name="projectID" value="<?php echo ssasEscape($editingProject["projectID"]); ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div>
                        <label>Project Title</label>
                        <input type="text" id="projectTitle" name="projectTitle" maxlength="255" value="<?php echo ssasEscape($editingProject["projectTitle"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Completion Year</label>
                        <input type="number" id="completionYear" name="completionYear" min="2000" max="<?php echo ssasEscape(((int) date("Y")) + 1); ?>" value="<?php echo ssasEscape($editingProject["completionYear"] ?? ""); ?>" required>
                    </div>
                    <div>
                        <label>Alumni Name</label>
                        <input type="text" id="alumniName" name="alumniName" maxlength="100" value="<?php echo ssasEscape($editingProject["alumniName"] ?? ""); ?>" required>
                    </div>
                    
                    <div class="full">
                        <label>Project Description / Abstract</label>
                        <textarea id="projectDescription" name="projectDescription" maxlength="1000" required><?php echo ssasEscape($editingProject["projectDescription"] ?? ""); ?></textarea>
                        <p class="file-note">Briefly describe the project scope, domain, technology, or research outcome.</p>
                    </div>
                    
                    <div class="full">
                        <div class="upload-grid">
                            <div>
                                <label>Past Project PDF</label>
                                <label class="upload-tile <?php echo !empty($editingProject["projectPDFPath"]) ? "has-current-file" : ""; ?>" for="projectPDF">
                                    <span class="upload-icon">PDF</span>
                                    <span class="upload-title"><?php echo !empty($editingProject["projectPDFPath"]) ? "Current PDF uploaded" : "Choose project document"; ?></span>
                                    <span class="upload-meta"><?php echo !empty($editingProject["projectPDFPath"]) ? "Students can already open this PDF. Choose a new file only if you want to replace it." : "Required PDF, maximum 5.0 MB. Students can open it from the public profile."; ?></span>
                                    <span class="selected-file" data-default-label="<?php echo !empty($editingProject["projectPDFPath"]) ? "No replacement selected" : "No PDF selected"; ?>" data-file-label="projectPDF"><?php echo !empty($editingProject["projectPDFPath"]) ? "No replacement selected" : "No PDF selected"; ?></span>
                                </label>
                                <input class="native-file" type="file" id="projectPDF" name="projectPDF" accept="application/pdf,.pdf" <?php echo $editingProject ? "" : "required"; ?>>
                                <?php if (!empty($editingProject["projectPDFPath"])): ?>
                                    <div class="existing-file">
                                        <a class="pdf-link" href="<?php echo ssasEscape(projectPdfUrl($editingProject["projectPDFPath"])); ?>" target="_blank" rel="noopener">View Current PDF</a>
                                        <label style="margin:0; display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:14px; color:#526a7f; font-weight: normal;">
                                            <input type="checkbox" name="removeProjectPDF" value="1" style="width:auto; height:auto;">
                                            Remove current PDF
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label>Project Cover Image</label>
                                <label class="upload-tile <?php echo !empty($editingProject["projectImagePath"]) ? "has-current-file" : ""; ?>" for="projectImage">
                                    <span class="upload-icon">IMG</span>
                                    <span class="upload-title"><?php echo !empty($editingProject["projectImagePath"]) ? "Current cover image uploaded" : "Choose cover image"; ?></span>
                                    <span class="upload-meta"><?php echo !empty($editingProject["projectImagePath"]) ? "The project card is already using this image. Choose a new file only if you want to replace it." : "Required JPG or PNG, maximum 5.0 MB. This replaces the generated card banner."; ?></span>
                                    <span class="selected-file" data-default-label="<?php echo !empty($editingProject["projectImagePath"]) ? "No replacement selected" : "No image selected"; ?>" data-file-label="projectImage"><?php echo !empty($editingProject["projectImagePath"]) ? "No replacement selected" : "No image selected"; ?></span>
                                </label>
                                <input class="native-file" type="file" id="projectImage" name="projectImage" accept="image/jpeg,image/png,.jpg,.jpeg,.png" <?php echo $editingProject ? "" : "required"; ?>>
                                <?php if (!empty($editingProject["projectImagePath"])): ?>
                                    <div class="existing-file">
                                        <a class="pdf-link" href="<?php echo ssasEscape(projectImageUrl($editingProject["projectImagePath"])); ?>" target="_blank" rel="noopener">View Current Image</a>
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
                        <a class="button secondary" href="managePastProjects.php">Cancel</a>
                    </div>
                </div>
            </form>

            <?php if (!$showProjectForm): ?>
                <?php if (empty($projects)): ?>
                    <section class="card empty">No past projects have been added yet.</section>
                <?php else: ?>
                    <section class="project-grid">
                        <?php foreach ($visibleProjects as $index => $project): ?>
                            <article class="card project-card">
                                <div class="project-visual alt<?php echo ssasEscape(($projectOffset + $index) % 3); ?> <?php echo !empty($project["projectImagePath"]) ? "has-image" : ""; ?>">
                                    <?php if (!empty($project["projectImagePath"])): ?>
                                        <img src="<?php echo ssasEscape(projectImageUrl($project["projectImagePath"])); ?>" alt="">
                                    <?php endif; ?>
                                    <span class="complete">Completed</span>
                                    <span class="project-visual-title"><?php echo ssasEscape($project["projectTitle"]); ?></span>
                                </div>
                                
                                <div class="project-body">
                                    <div class="year"><?php echo ssasEscape($project["completionYear"]); ?> Academic Year</div>
                                    <h2 class="project-title"><?php echo ssasEscape($project["projectTitle"]); ?></h2>
                                    <p class="project-desc"><?php echo ssasEscape($project["projectDescription"] ?: "No project description has been added yet."); ?></p>
                                    
                                    <div class="pill-row">
                                        <span class="pill">Research</span>
                                        <span class="pill">FYP</span>
                                        <span class="pill">Alumni: <?php echo ssasEscape($project["alumniName"]); ?></span>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <?php if (!empty($project["projectPDFPath"])): ?>
                                            <a class="pdf-link" href="<?php echo ssasEscape(projectPdfUrl($project["projectPDFPath"])); ?>" target="_blank" rel="noopener">View PDF</a>
                                        <?php endif; ?>
                                        <a class="button secondary small-button" href="managePastProjects.php?editProjectID=<?php echo ssasEscape($project["projectID"]); ?>">Edit</a>
                                        <form action="../../server/application/supervisor/managePastProjectProcess.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="projectID" value="<?php echo ssasEscape($project["projectID"]); ?>">
                                            <button class="button danger small-button" type="submit" onclick="return confirm('Delete this project?')">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                    
                    <div class="footer-page">
                        <span>Showing <?php echo ssasEscape($projectStart); ?>-<?php echo ssasEscape($projectEnd); ?> of <?php echo ssasEscape($projectCount); ?> archived projects</span>
                        <div class="table-pager" aria-label="Past projects pagination">
                            <?php if ($projectPage > 1): ?>
                                <a class="table-page-button" href="<?php echo ssasEscape(projectPageUrl($projectPage - 1)); ?>" aria-label="Previous projects page">&lt;</a>
                            <?php else: ?>
                                <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                            <?php endif; ?>

                            <span class="table-page-count">Page <?php echo ssasEscape($projectPage); ?> of <?php echo ssasEscape($totalProjectPages); ?></span>

                            <?php if ($projectPage < $totalProjectPages): ?>
                                <a class="table-page-button" href="<?php echo ssasEscape(projectPageUrl($projectPage + 1)); ?>" aria-label="Next projects page">&gt;</a>
                            <?php else: ?>
                                <span class="table-page-button disabled" aria-hidden="true">&gt;</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
