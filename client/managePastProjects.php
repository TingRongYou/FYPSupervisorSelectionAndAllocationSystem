<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/PastProjectService.php";
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
        .form-grid { display: grid; grid-template-columns: 1.5fr .6fr .9fr auto; gap: 12px; align-items: end; }
        .project-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
        .project-card { overflow: hidden; }
        .project-visual { height: 150px; background: radial-gradient(circle at 45% 42%, rgba(35,210,255,.45), transparent 0 28%, rgba(5,24,39,.95) 29% 100%), linear-gradient(135deg, #08233d, #0d5be8); color: #fff; display: grid; place-items: center; font-weight: 900; text-align: center; padding: 16px; position: relative; text-transform: uppercase; letter-spacing: .8px; }
        .project-visual.alt1 { background: radial-gradient(circle at center, rgba(255,255,255,.55), transparent 0 18%, rgba(5,24,39,.94) 19% 100%), linear-gradient(135deg, #10223a, #30b6a5); }
        .project-visual.alt2 { background: repeating-linear-gradient(0deg, rgba(44,255,148,.2) 0 2px, transparent 2px 14px), linear-gradient(135deg, #091b23, #194060); }
        .complete { position: absolute; top: 12px; right: 12px; background: #dff8e6; color: #14733e; border-radius: 999px; padding: 5px 9px; font-size: 10px; font-weight: 900; }
        .project-body { padding: 18px; }
        .year { color: #0d5be8; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .7px; }
        .project-title { margin: 6px 0 8px; color: #1d2b3a; font-size: 17px; line-height: 1.35; }
        .project-desc { color: #526a7f; line-height: 1.55; font-size: 13px; min-height: 58px; }
        .pill-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
        .pill { background: #eef3f8; color: #526a7f; border-radius: 6px; padding: 5px 8px; font-size: 11px; font-weight: 800; }
        .card-actions { display: flex; gap: 8px; margin-top: 14px; }
        .small-button { min-height: 32px; padding: 0 12px; border-radius: 6px; font-size: 12px; }
        .danger { background: #c93838; color: #fff; }
        .footer-page { display: flex; justify-content: space-between; align-items: center; color: #6b7f91; font-size: 13px; margin-top: 26px; }
        .pages span { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; background: #fff; margin-left: 5px; }
        .pages .active { background: #0d5be8; color: #fff; }
        .empty { padding: 26px; color: #526a7f; }
        @media (max-width: 1100px) { .project-grid, .form-grid { grid-template-columns: 1fr; } .project-form { display: block; } }
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

            <form class="card project-form" action="../server/application/managePastProjectProcess.php" method="POST" id="projectForm">
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
                    <button class="button" type="submit"><?php echo $editingProject ? "Update Project" : "Add Project"; ?></button>
                </div>
            </form>

            <?php if (empty($projects)): ?>
                <section class="card empty">No past projects have been added yet.</section>
            <?php else: ?>
                <section class="project-grid">
                    <?php foreach ($projects as $index => $project): ?>
                        <article class="card project-card">
                            <div class="project-visual alt<?php echo e($index % 3); ?>">
                                <span class="complete">Completed</span>
                                <?php echo e($project["projectTitle"]); ?>
                            </div>
                            <div class="project-body">
                                <div class="year"><?php echo e($project["completionYear"]); ?> Academic Year</div>
                                <h2 class="project-title"><?php echo e($project["projectTitle"]); ?></h2>
                                <p class="project-desc">Completed by <?php echo e($project["alumniName"]); ?> as part of the supervisor project showcase.</p>
                                <div class="pill-row">
                                    <span class="pill">Research</span>
                                    <span class="pill">FYP</span>
                                </div>
                                <div class="card-actions">
                                    <a class="button secondary small-button" href="managePastProjects.php?editProjectID=<?php echo e($project["projectID"]); ?>">Edit</a>
                                    <form action="../server/application/managePastProjectProcess.php" method="POST">
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
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        const form = document.getElementById("projectForm");
        if (form) {
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
