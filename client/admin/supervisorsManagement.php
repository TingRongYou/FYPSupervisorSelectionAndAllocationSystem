<?php

    require_once "../../server/application/auth/SessionManager.php";
    require_once "../../server/business/services/SupervisorManagementService.php";
    require_once __DIR__ . "/../shared/accountLayout.php";

    SessionManager::startSession();
    SessionManager::requireRole("Administrator");

$csrfToken = SessionManager::getCsrfToken();

$supervisorManagementService = new SupervisorManagementService();

    $searchName        = trim($_GET["searchName"] ?? "");
    $selectedProgramme = trim($_GET["programme"]  ?? "");
    $showCreatePanel   = ($_GET["source"] ?? "") === "createSupervisor"
        && ($_GET["status"] ?? "") === "error";
    $currentPage       = max(1, (int) ($_GET["page"] ?? 1));
    $rowsPerPage       = 3;

    $supervisors      = $supervisorManagementService->getSupervisorDirectory($_GET);
    $quotaOptions     = $supervisorManagementService->getQuotaOptions();
    $programmeOptions = $supervisorManagementService->getProgrammeOptions();

    $classificationOptions = [
        "Full-Time Lecturer" => "Full-Time",
        "Part-Time Lecturer" => "Part-Time",
        "Dean"               => "Administrative",
        "Deputy Dean"        => "Administrative",
        "Academic Director"  => "Administrative",
        "Programme Leader"   => "Administrative"
    ];

    $quotaByClassification = [];
    foreach ($classificationOptions as $classification => $quotaKeyword) {
        foreach ($quotaOptions as $quota) {
            if (stripos($quota["quotaTierName"], $quotaKeyword) !== false) {
                $quotaByClassification[$classification] = [
                    "quotaID"               => (int) $quota["quotaID"],
                    "quotaTierName"         => $quota["quotaTierName"],
                    "maxSuperviseesAllowed" => (int) $quota["maxSuperviseesAllowed"]
                ];
                break;
            }
        }
    }

    $totalSupervisors = count($supervisors);
    $totalPages       = max(1, (int) ceil($totalSupervisors / $rowsPerPage));
    $currentPage      = min($currentPage, $totalPages);
    $visibleSupervisors = array_slice($supervisors, ($currentPage - 1) * $rowsPerPage, $rowsPerPage);
    $firstVisibleEntry = empty($visibleSupervisors) ? 0 : (($currentPage - 1) * $rowsPerPage) + 1;
    $lastVisibleEntry = empty($visibleSupervisors) ? 0 : $firstVisibleEntry + count($visibleSupervisors) - 1;
    $allocatedTotal   = 0;
    $capacityTotal    = 0;
    foreach ($supervisors as $s) {
        $allocatedTotal += (int) $s["currentSupervisees"];
        $capacityTotal  += (int) $s["maxSuperviseesAllowed"];
    }
    $averageLoad = $capacityTotal > 0 ? round(($allocatedTotal / $capacityTotal) * 100) : 0;

    function e($v)                { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
    function selected($a, $b)     { return (string) $a === (string) $b ? "selected" : ""; }
    function activeFilter($a, $b) { return (string) $a === (string) $b ? "active" : ""; }

    function pageUrl($page, $searchName, $programme) {
        $query = ["page" => max(1, (int) $page)];

        if ($searchName !== "") {
            $query["searchName"] = $searchName;
        }

        if ($programme !== "") {
            $query["programme"] = $programme;
        }

        return "supervisorsManagement.php?" . http_build_query($query);
    }

    function statusMessage() {
        if (!isset($_GET["status"], $_GET["message"])) return "";
        $cls = $_GET["status"] === "success" ? "success" : "error";
        return '<div class="message ' . $cls . '">' . e($_GET["message"]) . '</div>';
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Management | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . "/../assets/css/admin.css"); ?>">
    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">
    <script>
        window.ssasQuotaRules = <?php echo json_encode($quotaByClassification); ?>;
    </script>
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="content-shell">
        <aside class="sidebar">
            <div class="role-card">
                <div class="role-icon">A</div>
                <div>
                    <p class="role-title">SSAS Admin</p>
                    <p class="role-subtitle">Management Portal</p>
                </div>
            </div>

            <a class="nav-link" href="adminDashboard.php">Dashboard</a>
            <a class="nav-link active" href="supervisorsManagement.php">Supervisors Management</a>
            <a class="nav-link" href="studentEligibility.php">Students Eligibility</a>
            <a class="nav-link" href="quotaManagement.php">Quota Management</a>
            <a class="nav-link" href="autoAllocation.php">Allocations</a>
            <a class="nav-link" href="adminSupervisorReviews.php">Supervisor Reviews Audit</a>
            <button class="nav-link has-submenu" type="button" aria-expanded="false" aria-controls="admin-report-tree" onclick="toggleAdminReports(this)">
                <span>Reports</span>
                <span class="submenu-caret" aria-hidden="true">v</span>
            </button>
            <div class="report-tree" id="admin-report-tree">
                <a class="report-child" href="adminCohortOverview.php">Cohort Overview</a>
                <a class="report-child" href="adminAllocationSummary.php">Allocation Summary</a>
            </div>
        </aside>
        
        <main class="main supervisor-management-main">
            <?php echo statusMessage(); ?>

            <section class="hero-grid">
                <article class="hero-card">
                    <div>
                        <h1>Supervisor Classification</h1>
                        <p>Audit and manage academic classification levels for all supervisors.</p>
                        <div class="hero-metrics">
                            <div class="metric">
                                <div class="metric-label">Total Active</div>
                                <div class="metric-value"><?php echo e($totalSupervisors); ?></div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Allocated</div>
                                <div class="metric-value"><?php echo e($averageLoad); ?>%</div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="status-card">
                    <h2>Status Summary</h2>
                    <?php
                        $r = 45;
                        $circumference = round(2 * M_PI * $r, 2);
                        $offset = round($circumference * (1 - $averageLoad / 100), 2);
                    ?>
                    <div class="ring-wrap">
                        <svg class="ring-svg" viewBox="0 0 110 110">
                            <circle class="ring-bg" cx="55" cy="55" r="<?php echo $r; ?>"/>
                            <circle class="ring-fill" cx="55" cy="55" r="<?php echo $r; ?>"
                                stroke-dasharray="<?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo $offset; ?>"/>
                        </svg>
                        <div class="ring-label">
                            <strong><?php echo e($averageLoad); ?>%</strong>
                            <span>Allocated</span>
                        </div>
                    </div>
                    <p class="status-caption">Overall allocation efficiency across all active programmes.</p>
                </article>
            </section>

            <nav class="quick-filter" aria-label="Programme filters">
                <span class="quick-label">Quick Filter</span>
                <a class="filter-pill <?php echo activeFilter($selectedProgramme, ""); ?>" href="supervisorsManagement.php">All Programme</a>
                <?php foreach ($programmeOptions as $prog): ?>
                    <a class="filter-pill <?php echo activeFilter($selectedProgramme, $prog["programme"]); ?>"
                       href="supervisorsManagement.php?programme=<?php echo urlencode($prog["programme"]); ?>">
                        <?php echo e($prog["programme"]); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <section class="panel supervisor-directory-panel">
                <div class="directory-header">
                    <div class="directory-title">
                        <h2>Supervisor Directory</h2>
                        <p>Search, filter, and update supervisor classification records.</p>
                    </div>
                    <div class="directory-tools">
                        <button class="btn btn-ghost" type="button" data-open-create>Add Supervisor</button>
                        <form class="search-form" method="GET" action="supervisorsManagement.php">
                        <input type="hidden" name="page" value="1">
                        <div class="search-wrap">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="text" name="searchName" value="<?php echo e($searchName); ?>" placeholder="Search staff...">
                        </div>
                        <select name="programme">
                            <option value="">All Programmes</option>
                            <?php foreach ($programmeOptions as $prog): ?>
                                <option value="<?php echo e($prog["programme"]); ?>" <?php echo selected($selectedProgramme, $prog["programme"]); ?>>
                                    <?php echo e($prog["programme"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" type="submit">Apply</button>
                        <a class="btn btn-secondary" href="supervisorsManagement.php">Reset</a>
                        </form>
                    </div>
                </div>

                <form class="create-panel <?php echo $showCreatePanel ? "show" : ""; ?>" id="createSupervisorPanel" action="../../server/application/admin/createSupervisorProcess.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="returnTo" value="supervisorsManagement">
                    <input type="hidden" name="quotaID" id="createQuotaID" required>

                    <div class="create-title">
                        <div>
                            <h3>Add Supervisor</h3>
                            <p>Create the login account and supervisor profile in one step.</p>
                        </div>
                        <button class="btn btn-secondary" type="button" data-close-create>Close</button>
                    </div>

                    <div class="create-grid">
                        <div class="create-field">
                            <label for="createSupervisorID">Staff ID</label>
                            <input type="text" id="createSupervisorID" name="supervisorID" maxlength="20" required>
                        </div>

                        <div class="create-field wide">
                            <label for="createFullName">Full Name</label>
                            <input type="text" id="createFullName" name="fullName" maxlength="100" required>
                        </div>

                        <div class="create-field">
                            <label for="createProgramme">Programme</label>
                            <div class="combo-field">
                                <input type="text" id="createProgramme" name="programme" maxlength="100" list="programmeList" placeholder="Type or choose programme" autocomplete="off" required>
                            </div>
                            <datalist id="programmeList">
                                <?php foreach ($programmeOptions as $prog): ?>
                                    <option value="<?php echo e($prog["programme"]); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="create-field wide">
                            <label for="createEmail">University Email</label>
                            <input type="email" id="createEmail" name="universityEmail" maxlength="100" required>
                        </div>

                        <div class="create-field">
                            <label for="createPassword">Temporary Password</label>
                            <input type="password" id="createPassword" name="password" minlength="8" required>
                        </div>

                        <div class="create-field">
                            <label for="createEmploymentCategory">Classification</label>
                            <select id="createEmploymentCategory" name="employmentCategory" required>
                                <option value="">Select classification</option>
                                <?php foreach ($classificationOptions as $classification => $qk): ?>
                                    <option value="<?php echo e($classification); ?>">
                                        <?php echo e($classification); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="create-actions">
                        <button class="btn btn-secondary" type="reset">Clear</button>
                        <button class="btn btn-primary" type="submit">Create Supervisor</button>
                    </div>
                </form>

                <div class="dir-table">
                    <div class="thead-row">
                        <div>Name</div>
                        <div>Staff ID</div>
                        <div>Programme</div>
                        <div>Classification</div>
                        <div>Quota Status</div>
                        <div>Actions</div>
                    </div>

                    <?php if (empty($visibleSupervisors)): ?>
                        <div class="empty">No supervisors found for the selected criteria.</div>
                    <?php else: ?>
                        <?php foreach ($visibleSupervisors as $supervisor): ?>
                            <?php
                                $isFull = $supervisor["availabilityStatus"] === "Full";
                                $profilePhoto = $supervisor["profilePhotoPath"] ?? "";
                                $initials = strtoupper(substr($supervisor["fullName"], 0, 1));
                                $selectedClass = $supervisor["employmentCategory"];
                                $expectedQuota = $quotaByClassification[$selectedClass] ?? null;
                                $selectedQuotaID = $expectedQuota["quotaID"] ?? $supervisor["quotaID"];
                                $badgeClass = strtolower($supervisor["availabilityStatus"]);
                            ?>

                            <form class="data-row" action="../../server/application/admin/updateSupervisorClassification.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="supervisorID" value="<?php echo e($supervisor["userID"]); ?>">
                                <input class="classification-quota-id" type="hidden" name="quotaID" value="<?php echo e($selectedQuotaID); ?>">

                                <div class="person-cell">
                                    <div class="avatar">
                                        <?php if ($profilePhoto !== ""): ?>
                                            <img src="<?php echo e($profilePhoto); ?>" alt="">
                                        <?php else: ?>
                                            <?php echo e($initials); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="min-width:0;">
                                        <p class="person-name"><?php echo e($supervisor["fullName"]); ?></p>
                                        <p class="person-meta"><?php echo e($supervisor["employmentCategory"]); ?></p>
                                    </div>
                                </div>

                                <div class="cell-text"><?php echo e($supervisor["userID"]); ?></div>
                                <div class="cell-text"><?php echo e($supervisor["programme"]); ?></div>

                                <div>
                                    <select class="classification-select" name="employmentCategory" required>
                                        <?php foreach ($classificationOptions as $classification => $qk): ?>
                                            <option value="<?php echo e($classification); ?>" <?php echo selected($selectedClass, $classification); ?>>
                                                <?php echo e($classification); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="quota-status-cell" data-current-supervisees="<?php echo e($supervisor["currentSupervisees"]); ?>">
                                    <div class="load-row <?php echo $isFull ? "full" : ""; ?>">
                                        <span><?php echo e($supervisor["quotaText"]); ?></span>
                                        <span><?php echo e($supervisor["loadPercentage"]); ?>%</span>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill <?php echo $isFull ? "full" : ""; ?>" style="width: <?php echo e(min($supervisor["loadPercentage"], 100)); ?>%;"></div>
                                    </div>
                                    <span class="avail-badge <?php echo e($badgeClass); ?>">
                                        <?php echo e($supervisor["availabilityStatus"]); ?>
                                    </span>
                                </div>

                                <div class="action-cell">
                                    <button class="save-btn" type="submit" title="Save changes" aria-label="Save changes">&#10003;</button>
                                    <button class="more-btn" type="button" title="Edit account particulars" data-edit-account
                                        data-supervisor-id="<?php echo e($supervisor["userID"]); ?>"
                                        data-full-name="<?php echo e($supervisor["fullName"]); ?>"
                                        data-email="<?php echo e($supervisor["universityEmail"]); ?>"
                                        data-active-status="<?php echo $supervisor["activeStatus"] ? "1" : "0"; ?>">...</button>
                                </div>
                            </form>
                        <?php endforeach; ?>

                        <div class="showing directory-footer">
                            <span>Showing <?php echo e($firstVisibleEntry); ?>-<?php echo e($lastVisibleEntry); ?> of <?php echo e($totalSupervisors); ?> supervisors</span>
                            <nav class="table-pager" aria-label="Supervisor directory pagination">
                                <?php if ($currentPage > 1): ?>
                                    <a class="table-page-button" href="<?php echo e(pageUrl($currentPage - 1, $searchName, $selectedProgramme)); ?>" aria-label="Previous supervisor directory page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo e($currentPage); ?> of <?php echo e($totalPages); ?></span>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a class="table-page-button" href="<?php echo e(pageUrl($currentPage + 1, $searchName, $selectedProgramme)); ?>" aria-label="Next supervisor directory page">&gt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&gt;</span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-backdrop" id="accountModal" aria-hidden="true">
        <form class="account-modal" action="../../server/application/admin/updateSupervisorAccount.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <div class="modal-head">
                <div>
                    <h3>Edit Account Particulars</h3>
                    <p>Update account details that supervisors cannot change from their own profile page.</p>
                </div>
                <button class="modal-close" type="button" data-close-account-modal>&#x2715;</button>
            </div>

            <div class="modal-body">
                <div class="modal-field">
                    <label for="editSupervisorID">Staff ID</label>
                    <input type="text" id="editSupervisorID" name="supervisorID" readonly>
                </div>

                <div class="modal-field">
                    <label for="editActiveStatus">Account Status</label>
                    <select id="editActiveStatus" name="activeStatus" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="modal-field wide">
                    <label for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" name="fullName" maxlength="100" required>
                </div>

                <div class="modal-field wide">
                    <label for="editUniversityEmail">University Email</label>
                    <input type="email" id="editUniversityEmail" name="universityEmail" maxlength="100" required>
                </div>

                <div class="modal-note">
                    Staff ID and system role are locked. Classification changes must use the classification dropdown in the directory row.
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary" type="button" data-close-account-modal>Cancel</button>
                <button class="btn btn-primary" type="submit">Save Account</button>
            </div>
        </form>
    </div>
</body>
</html>
