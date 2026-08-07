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
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Supervisor Management", "admin"); 
    ?>
    <script>
        window.ssasQuotaRules = <?php echo json_encode($quotaByClassification); ?>;
    </script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>

    <div class="content-shell">
        <?php echo ssasPortalSidebar("supervisors"); ?>
        
        <main class="main supervisor-management-main">
            <?php echo ssasStatusMessage(); ?>

            <section class="hero-grid">
                <article class="hero-card">
                    <div>
                        <h1>Supervisor Classification</h1>
                        <p>Audit and manage academic classification levels for all supervisors.</p>
                        <div class="hero-metrics">
                            <div class="metric">
                                <div class="metric-label">Total Active</div>
                                <div class="metric-value"><?php echo ssasEscape($totalSupervisors); ?></div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Allocated</div>
                                <div class="metric-value"><?php echo ssasEscape($averageLoad); ?>%</div>
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
                            <strong><?php echo ssasEscape($averageLoad); ?>%</strong>
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
                        <?php echo ssasEscape($prog["programme"]); ?>
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
                            <input type="text" name="searchName" value="<?php echo ssasEscape($searchName); ?>" placeholder="Search staff...">
                        </div>
                        <select name="programme">
                            <option value="">All Programmes</option>
                            <?php foreach ($programmeOptions as $prog): ?>
                                <option value="<?php echo ssasEscape($prog["programme"]); ?>" <?php echo selected($selectedProgramme, $prog["programme"]); ?>>
                                    <?php echo ssasEscape($prog["programme"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" type="submit">Apply</button>
                        <a class="btn btn-secondary" href="supervisorsManagement.php">Reset</a>
                        </form>
                    </div>
                </div>

                <form class="create-panel <?php echo $showCreatePanel ? "show" : ""; ?>" id="createSupervisorPanel" action="../../server/application/admin/createSupervisorProcess.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
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
                                    <option value="<?php echo ssasEscape($prog["programme"]); ?>"></option>
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
                                    <option value="<?php echo ssasEscape($classification); ?>">
                                        <?php echo ssasEscape($classification); ?>
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
                                <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
                                <input type="hidden" name="supervisorID" value="<?php echo ssasEscape($supervisor["userID"]); ?>">
                                <input class="classification-quota-id" type="hidden" name="quotaID" value="<?php echo ssasEscape($selectedQuotaID); ?>">

                                <div class="person-cell">
                                    <div class="avatar">
                                        <?php if ($profilePhoto !== ""): ?>
                                            <img src="<?php echo ssasEscape($profilePhoto); ?>" alt="">
                                        <?php else: ?>
                                            <?php echo ssasEscape($initials); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="min-width:0;">
                                        <p class="person-name"><?php echo ssasEscape($supervisor["fullName"]); ?></p>
                                        <p class="person-meta"><?php echo ssasEscape($supervisor["employmentCategory"]); ?></p>
                                    </div>
                                </div>

                                <div class="cell-text"><?php echo ssasEscape($supervisor["userID"]); ?></div>
                                <div class="cell-text"><?php echo ssasEscape($supervisor["programme"]); ?></div>

                                <div>
                                    <select class="classification-select" name="employmentCategory" required>
                                        <?php foreach ($classificationOptions as $classification => $qk): ?>
                                            <option value="<?php echo ssasEscape($classification); ?>" <?php echo selected($selectedClass, $classification); ?>>
                                                <?php echo ssasEscape($classification); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="quota-status-cell" data-current-supervisees="<?php echo ssasEscape($supervisor["currentSupervisees"]); ?>">
                                    <div class="load-row <?php echo $isFull ? "full" : ""; ?>">
                                        <span><?php echo ssasEscape($supervisor["quotaText"]); ?></span>
                                        <span><?php echo ssasEscape($supervisor["loadPercentage"]); ?>%</span>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill <?php echo $isFull ? "full" : ""; ?>" style="width: <?php echo ssasEscape(min($supervisor["loadPercentage"], 100)); ?>%;"></div>
                                    </div>
                                    <span class="avail-badge <?php echo ssasEscape($badgeClass); ?>">
                                        <?php echo ssasEscape($supervisor["availabilityStatus"]); ?>
                                    </span>
                                </div>

                                <div class="action-cell">
                                    <button class="save-btn" type="submit" title="Save changes" aria-label="Save changes">&#10003;</button>
                                    <button class="more-btn" type="button" title="Edit account particulars" data-edit-account
                                        data-supervisor-id="<?php echo ssasEscape($supervisor["userID"]); ?>"
                                        data-full-name="<?php echo ssasEscape($supervisor["fullName"]); ?>"
                                        data-email="<?php echo ssasEscape($supervisor["universityEmail"]); ?>"
                                        data-active-status="<?php echo ssasEscape($supervisor["activeStatus"] ? "1" : "0"); ?>">...</button>
                                </div>
                            </form>
                        <?php endforeach; ?>

                        <div class="showing directory-footer">
                            <span>Showing <?php echo ssasEscape($firstVisibleEntry); ?>-<?php echo ssasEscape($lastVisibleEntry); ?> of <?php echo ssasEscape($totalSupervisors); ?> supervisors</span>
                            <nav class="table-pager" aria-label="Supervisor directory pagination">
                                <?php if ($currentPage > 1): ?>
                                    <a class="table-page-button" href="<?php echo ssasEscape(pageUrl($currentPage - 1, $searchName, $selectedProgramme)); ?>" aria-label="Previous supervisor directory page">&lt;</a>
                                <?php else: ?>
                                    <span class="table-page-button disabled" aria-hidden="true">&lt;</span>
                                <?php endif; ?>

                                <span class="table-page-count">Page <?php echo ssasEscape($currentPage); ?> of <?php echo ssasEscape($totalPages); ?></span>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a class="table-page-button" href="<?php echo ssasEscape(pageUrl($currentPage + 1, $searchName, $selectedProgramme)); ?>" aria-label="Next supervisor directory page">&gt;</a>
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
            <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($csrfToken); ?>">
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
