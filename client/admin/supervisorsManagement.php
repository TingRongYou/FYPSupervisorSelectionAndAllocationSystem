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
    $rowsPerPage       = 10;

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
        $query = [
            "page" => max(1, (int) $page)
        ];

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
        <style>
            <?php echo ssasAccountStyles(); ?>

            *, *::before, *::after { box-sizing: border-box; }

            body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #1d2b3a; }

            /* Shell */
            .content-shell { display: flex; min-height: calc(100vh - 52px); }

        /* Sidebar */
        .sidebar { width: 220px; flex: 0 0 220px; background: #fff; border-right: 1px solid #dce8f3; padding: 16px 10px; }
        .role-card { display: flex; gap: 10px; align-items: center; padding: 6px 9px 14px; margin-bottom: 8px; }
        .role-icon { width: 34px; height: 34px; border-radius: 8px; background: #0d5be8; color: #fff; display: grid; place-items: center; font-size: 13px; font-weight: 900; flex-shrink: 0; }
        .role-title    { margin: 0; color: #10263d; font-weight: 900; font-size: 13px; }
        .role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
        .nav-link { display: flex; align-items: center; gap: 10px; color: #526a7f; text-decoration: none; padding: 9px 12px; border-radius: 7px; margin-bottom: 3px; font-size: 12px; font-weight: 600; }
        .nav-link:hover, .nav-link.active { background: #eaf3ff; color: #0d5be8; }
        .nav-link.active { font-weight: 800; }
        .nav-icon    { font-size: 14px; width: 18px; text-align: center; }
        .nav-chevron { margin-left: auto; font-size: 10px; }

        .sidebar { width: 280px; flex: 0 0 280px; border-right: 1px solid #dde8f2; padding: 26px 18px; }
        .role-card { gap: 12px; padding: 12px; border-radius: 8px; background: #eef6fc; margin-bottom: 20px; }
        .role-icon { width: 36px; height: 36px; border-radius: 8px; font-size: 15px; }
        .role-title { font-size: 14px; }
        .role-subtitle { font-size: 12px; text-transform: none; letter-spacing: 0; }
        .nav-link { gap: 10px; padding: 12px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; font-weight: 400; transition: background .2s, color .2s, transform .2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { color: #0b66d8; transform: translateX(2px); }
        .nav-icon, .nav-chevron { display: none; }
        .sidebar .role-card { min-height: 62px; }
        .sidebar .role-icon { width: 38px; height: 38px; font-size: 15px; font-weight: 800; }
        .sidebar .role-title { font-size: 14px; font-weight: 800; }
        .sidebar .role-subtitle { font-size: 12px; font-weight: 400; text-transform: none; letter-spacing: 0; }
        .sidebar .nav-link,
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active { min-height: 40px; padding: 12px 14px; margin-bottom: 8px; border-radius: 8px; font-size: 14px; font-weight: 600; line-height: 1.2; white-space: nowrap; }

        /* Main */
        .main { flex: 1; padding: 26px 28px 60px; min-width: 0; overflow-x: hidden; }

        /* Hero */
        .hero-grid { display: grid; grid-template-columns: 1fr 220px; gap: 20px; margin-bottom: 20px; }

        .hero-card {
            background: linear-gradient(135deg, #1565e8 0%, #0d48c0 100%);
            color: #fff; border-radius: 14px; padding: 28px 30px;
            display: flex; justify-content: space-between; align-items: flex-end; gap: 20px;
        }
        .hero-card h1 { margin: 0 0 6px; font-size: 26px; font-weight: 700; }
        .hero-card p  { margin: 0 0 22px; color: #c8deff; font-size: 13px; line-height: 1.5; }
        .hero-metrics { display: flex; gap: 12px; }
        .metric { min-width: 100px; background: rgba(255,255,255,.15); border-radius: 10px; padding: 14px 18px; }
        .metric-label { color: #a8c8ff; font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 800; }
        .metric-value { margin-top: 6px; font-size: 26px; font-weight: 900; }
        .hero-actions { display: flex; gap: 10px; flex-shrink: 0; align-self: center; }
        .hero-btn { border-radius: 8px; height: 36px; padding: 0 20px; font-weight: 800; font-size: 12px; cursor: pointer; border: none; }
        .hero-btn.primary   { background: #fff; color: #0d5be8; }
        .hero-btn.secondary { background: rgba(255,255,255,.18); color: #fff; border: 1px solid rgba(255,255,255,.3); }

        /* Status ring card */
        .status-card { background: #fff; border: 1px solid #d9e7f3; border-radius: 14px; padding: 24px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .status-card h2 { margin: 0 0 16px; color: #10263d; font-size: 13px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 900; }
        .ring-wrap { position: relative; width: 110px; height: 110px; margin-bottom: 14px; }
        .ring-svg  { width: 110px; height: 110px; transform: rotate(-90deg); }
        .ring-bg   { fill: none; stroke: #e8f0fb; stroke-width: 10; }
        .ring-fill { fill: none; stroke: #0d5be8; stroke-width: 10; stroke-linecap: round; }
        .ring-label { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .ring-label strong { color: #0d5be8; font-size: 22px; font-weight: 900; line-height: 1; }
        .ring-label span   { color: #6b7f91; font-size: 11px; text-transform: uppercase; letter-spacing: .8px; margin-top: 2px; }
        .status-caption { margin: 0; color: #8a9caf; font-size: 13px; line-height: 1.5; }

        /* Quick filter */
        .quick-filter { display: flex; gap: 10px; align-items: center; padding: 12px 0; margin-bottom: 16px; overflow-x: auto; flex-wrap: wrap; }
        .quick-label  { color: #8a9caf; font-size: 12px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; white-space: nowrap; }
        .filter-pill  { display: inline-flex; align-items: center; min-height: 30px; border-radius: 999px; padding: 0 16px; color: #526a7f; background: #fff; border: 1px solid #d4e2f0; text-decoration: none; font-size: 13px; font-weight: 800; white-space: nowrap; }
        .filter-pill:hover  { border-color: #0d5be8; color: #0d5be8; }
        .filter-pill.active { background: #0d5be8; color: #fff; border-color: #0d5be8; }

        /* Panel */
        .panel { background: #fff; border: 1px solid #d9e7f3; border-radius: 14px; overflow: hidden; }

        .directory-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding: 20px 26px 16px; }
        .directory-header h2 { margin: 0; font-size: 22px; color: #10263d; font-weight: 900; line-height: 1.2; flex-shrink: 0; }
        .search-form { display: flex; flex-wrap: nowrap; gap: 8px; align-items: center; }
        .search-wrap { position: relative; flex: 0 0 200px; min-width: 0; }
        .search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #526a7f; pointer-events: none; }
        .search-wrap input { width: 100%; padding-left: 34px; background: #fff; }
        .search-form select { flex: 0 0 140px; background: #fff; }
        .search-form .btn { flex-shrink: 0; justify-content: center; white-space: nowrap; }
        .search-form .btn-ghost { flex-shrink: 0; }

        input, select {
            height: 34px; border: 1px solid #dbe6f0; border-radius: 7px;
            background: #f6f8fb; color: #1d2b3a; padding: 0 10px;
            font-size: 14px; outline: none;
        }
        input:focus, select:focus { border-color: #0d5be8; background: #fff; }

        .btn { border: 0; min-height: 36px; border-radius: 7px; padding: 0 16px; font-weight: 800; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-primary   { background: #0d5be8; color: #fff; }
        .btn-secondary { background: #eef2f7; color: #3d5166; }
        .btn-ghost { background: #eaf3ff; color: #0d5be8; border: 1px solid #cfe0f5; }

        .create-panel { display: none; border-top: 1px solid #edf2f7; background: #fbfdff; padding: 20px 22px 22px; }
        .create-panel.show { display: block; }
        .create-title { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
        .create-title h3 { margin: 0; color: #10263d; font-size: 17px; font-weight: 900; }
        .create-title p { margin: 4px 0 0; color: #6b7f91; font-size: 13px; line-height: 1.45; }
        .create-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; align-items: end; }
        .create-field { min-width: 0; }
        .create-field label { display: block; margin-bottom: 6px; color: #526a7f; font-size: 12px; font-weight: 900; letter-spacing: .6px; text-transform: uppercase; }
        .create-field input, .create-field select { width: 100%; }
        .create-field.wide { grid-column: span 2; }
        .create-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }

        /* Directory table */
        .dir-table { width: 100%; }

        .thead-row,
        .data-row {
            display: grid;
            grid-template-columns: 240px 110px 120px 170px minmax(280px, 1fr) 118px;
            align-items: center;
        }

        .thead-row {
            padding: 13px 22px;
            background: #f8fafd;
            border-top: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
            gap: 16px;
        }
        .thead-row > div {
            color: #8a9caf; font-size: 12px; font-weight: 900;
            letter-spacing: 1px; text-transform: uppercase;
        }
        .thead-row > div:last-child { text-align: right; }

        .data-row {
            padding: 16px 22px;
            border-bottom: 1px solid #edf2f7;
            gap: 16px;
            min-height: 78px;
        }
        .data-row:last-of-type { border-bottom: none; }
        .data-row:hover        { background: #fafcff; }

        /* Name cell */
        .person-cell { display: flex; gap: 10px; align-items: center; min-width: 0; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #26384c; color: #fff; display: grid; place-items: center; font-size: 12px; font-weight: 900; flex-shrink: 0; overflow: hidden; }
        .avatar img  { width: 100%; height: 100%; object-fit: cover; }
        .person-name { margin: 0; font-size: 15px; font-weight: 900; color: #10263d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .person-meta { margin: 3px 0 0; font-size: 13px; color: #8a9caf; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .cell-text { color: #526a7f; font-size: 15px; }

        /* Selects inside rows */
        .classification-select { width: 100%; font-size: 14px; }

        /* Quota / load bar */
        .quota-status-cell { width: 100%; }
        .load-row   { display: flex; justify-content: space-between; color: #526a7f; font-size: 14px; font-weight: 900; margin-bottom: 7px; gap: 12px; }
        .load-row.full { color: #b42318; }
        .bar-track  { height: 8px; background: #edf2f7; border-radius: 999px; overflow: hidden; }
        .bar-fill   { height: 100%; background: #0d5be8; border-radius: inherit; }
        .bar-fill.full { background: #d93c3c; }
        .avail-badge { display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 900; padding: 4px 10px; border-radius: 999px; }
        .avail-badge.available { background: #e6f4ec; color: #177345; }
        .avail-badge.full      { background: #fdeaea; color: #b42318; }
        .avail-badge.active    { background: #e6f4ec; color: #177345; }
        .avail-badge.inactive  { background: #eef2f7; color: #526a7f; }

        /* Action cell */
        .action-cell { display: flex; gap: 8px; justify-content: flex-end; align-items: center; min-width: 0; }
        .save-btn { min-width: 46px; height: 34px; padding: 0 12px; border-radius: 8px; border: none; background: #0d5be8; color: #fff; font-weight: 900; font-size: 13px; cursor: pointer; display: grid; place-items: center; }
        .save-btn:hover { background: #0947c2; }
        .more-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #dbe6f0; background: #fff; color: #526a7f; font-size: 18px; cursor: pointer; display: grid; place-items: center; line-height: 1; }

        .modal-backdrop { position: fixed; inset: 0; z-index: 50; display: none; align-items: center; justify-content: center; background: rgba(15, 33, 55, .38); padding: 24px; }
        .modal-backdrop.show { display: flex; }
        .account-modal { width: 560px; max-width: 100%; background: #fff; border: 1px solid #d9e7f3; border-radius: 14px; box-shadow: 0 24px 60px rgba(20, 45, 78, .26); overflow: hidden; }
        .modal-head { padding: 20px 22px; background: #f8fafd; border-bottom: 1px solid #edf2f7; display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
        .modal-head h3 { margin: 0; color: #10263d; font-size: 18px; font-weight: 900; }
        .modal-head p { margin: 5px 0 0; color: #6b7f91; font-size: 14px; line-height: 1.45; }
        .modal-close { border: 0; background: #eef2f7; color: #526a7f; border-radius: 7px; width: 34px; height: 34px; cursor: pointer; font-weight: 900; }
        .modal-body { padding: 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .modal-field { min-width: 0; }
        .modal-field.wide { grid-column: span 2; }
        .modal-field label { display: block; margin-bottom: 6px; color: #526a7f; font-size: 12px; font-weight: 900; letter-spacing: .6px; text-transform: uppercase; }
        .modal-field input, .modal-field select { width: 100%; height: 36px; }
        .modal-field input[readonly] { background: #eaf1fb; color: #526a7f; }
        .modal-note { grid-column: span 2; border-left: 3px solid #0d5be8; background: #eef6ff; color: #526a7f; padding: 11px 12px; font-size: 14px; line-height: 1.45; }
        .modal-actions { padding: 16px 22px 22px; display: flex; justify-content: flex-end; gap: 8px; }

        .showing { padding: 12px 22px; color: #8a9caf; font-size: 13px; border-top: 1px solid #edf2f7; }
        .directory-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .pager { display: flex; gap: 5px; align-items: center; }
        .page-pill { width: 30px; height: 30px; border: 1px solid #dce8f3; border-radius: 6px; display: grid; place-items: center; color: #6b7f91; background: #fff; font-size: 14px; font-weight: 800; cursor: pointer; text-decoration: none; }
        .page-pill.active { background: #0d5be8; color: #fff; border-color: #0d5be8; }
        .empty   { padding: 36px; color: #8a9caf; text-align: center; font-size: 13px; }

        @media (max-width: 1100px) {
            .search-form { flex-wrap: wrap; }
            .search-wrap { flex: 1 1 160px; }
            .thead-row,
            .data-row { grid-template-columns: 200px 90px 100px 150px minmax(230px, 1fr) 112px; }
        }
        @media (max-width: 900px) {
            .hero-grid { grid-template-columns: 1fr; }
            .status-card { display: none; }
            .create-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 820px) {
            .content-shell { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid #dce8f3; }
            .main { padding: 16px 14px 60px; }
            .hero-card { flex-direction: column; align-items: flex-start; }
            .panel { overflow-x: auto; }
            .dir-table { min-width: 920px; }
            .create-panel { min-width: 780px; }
            .search-form { flex-wrap: wrap; }
            .search-wrap, .search-form select { flex: 1 1 100%; }
            .create-grid { grid-template-columns: 1fr; }
            .create-field.wide { grid-column: span 1; }
            .modal-body { grid-template-columns: 1fr; }
            .modal-field.wide, .modal-note { grid-column: span 1; }
        }
        </style>
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
                <a class="nav-link" href="adminCohortOverview.php">Reports</a>
            </aside>
            <main class="main">
                <?php echo statusMessage(); ?>

                <!-- Hero -->
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
                            $r            = 45;
                            $circumference = round(2 * M_PI * $r, 2);
                            $offset        = round($circumference * (1 - $averageLoad / 100), 2);
                        ?>
                        <div class="ring-wrap">
                            <svg class="ring-svg" viewBox="0 0 110 110">
                                <circle class="ring-bg"   cx="55" cy="55" r="<?php echo $r; ?>"/>
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

                <!-- Quick filter -->
                <nav class="quick-filter" aria-label="Programme filters">
                    <span class="quick-label">Quick Filter</span>
                    <a class="filter-pill <?php echo activeFilter($selectedProgramme, ""); ?>"
                    href="supervisorsManagement.php">All Programme</a>
                    <?php foreach ($programmeOptions as $prog): ?>
                        <a class="filter-pill <?php echo activeFilter($selectedProgramme, $prog["programme"]); ?>"
                        href="supervisorsManagement.php?programme=<?php echo urlencode($prog["programme"]); ?>">
                            <?php echo e($prog["programme"]); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Directory panel -->
                <section class="panel">
                    <div class="directory-header">
                        <h2>Supervisor Directory</h2>
                        <form class="search-form" method="GET" action="supervisorsManagement.php">
                            <button class="btn btn-ghost" type="button" data-open-create>Add Supervisor</button>
                            <div class="search-wrap">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input type="text" name="searchName"
                                    value="<?php echo e($searchName); ?>"
                                    placeholder="Search staff...">
                            </div>
                            <select name="programme">
                                <option value="">All Programmes</option>
                                <?php foreach ($programmeOptions as $prog): ?>
                                    <option value="<?php echo e($prog["programme"]); ?>"
                                            <?php echo selected($selectedProgramme, $prog["programme"]); ?>>
                                        <?php echo e($prog["programme"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary"   type="submit">Apply</button>
                            <a      class="btn btn-secondary" href="supervisorsManagement.php">Reset</a>
                        </form>
                    </div>

                    <form class="create-panel <?php echo $showCreatePanel ? "show" : ""; ?>" id="createSupervisorPanel" action="../../server/application/admin/createSupervisorProcess.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="returnTo" value="supervisorsManagement">
                        <!-- FIX: quotaID is now populated by the classification change handler -->
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
                                <input type="text" id="createProgramme" name="programme" maxlength="100" list="programmeList" required>
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
                        <!-- Header row -->
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
                                    $isFull          = $supervisor["availabilityStatus"] === "Full";
                                    $profilePhoto    = $supervisor["profilePhotoPath"] ?? "";
                                    $initials        = strtoupper(substr($supervisor["fullName"], 0, 1));
                                    $selectedClass   = $supervisor["employmentCategory"];
                                    $expectedQuota   = $quotaByClassification[$selectedClass] ?? null;
                                    $selectedQuotaID = $expectedQuota["quotaID"] ?? $supervisor["quotaID"];
                                    $badgeClass      = strtolower($supervisor["availabilityStatus"]);
                                ?>

                                <form class="data-row"
                                    action="../../server/application/admin/updateSupervisorClassification.php"
                                    method="POST">

                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="supervisorID" value="<?php echo e($supervisor["userID"]); ?>">
                                    <input type="hidden" name="quotaID"      value="<?php echo e($selectedQuotaID); ?>">

                                    <!-- 1. Name -->
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

                                    <!-- 2. Staff ID -->
                                    <div class="cell-text"><?php echo e($supervisor["userID"]); ?></div>

                                    <!-- 3. Programme -->
                                    <div class="cell-text"><?php echo e($supervisor["programme"]); ?></div>

                                    <!-- 4. Classification -->
                                    <div>
                                        <select class="classification-select" name="employmentCategory" required>
                                            <?php foreach ($classificationOptions as $classification => $qk): ?>
                                                <option value="<?php echo e($classification); ?>"
                                                        <?php echo selected($selectedClass, $classification); ?>>
                                                    <?php echo e($classification); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- 5. Quota Status -->
                                    <div class="quota-status-cell">
                                        <div class="load-row <?php echo $isFull ? "full" : ""; ?>">
                                            <span><?php echo e($supervisor["quotaText"]); ?></span>
                                            <span><?php echo e($supervisor["loadPercentage"]); ?>%</span>
                                        </div>
                                        <div class="bar-track">
                                            <div class="bar-fill <?php echo $isFull ? "full" : ""; ?>"
                                                style="width: <?php echo e(min($supervisor["loadPercentage"], 100)); ?>%;"></div>
                                        </div>
                                        <span class="avail-badge <?php echo e($badgeClass); ?>">
                                            <?php echo e($supervisor["availabilityStatus"]); ?>
                                        </span>
                                    </div>

                                    <!-- 6. Actions -->
                                    <div class="action-cell">
                                        <button class="save-btn" type="submit" title="Save">OK</button>
                                        <button
                                            class="more-btn"
                                            type="button"
                                            title="Edit account particulars"
                                            data-edit-account
                                            data-supervisor-id="<?php echo e($supervisor["userID"]); ?>"
                                            data-full-name="<?php echo e($supervisor["fullName"]); ?>"
                                            data-email="<?php echo e($supervisor["universityEmail"]); ?>"
                                            data-active-status="<?php echo $supervisor["activeStatus"] ? "1" : "0"; ?>"
                                        >...</button>
                                    </div>

                                </form>
                            <?php endforeach; ?>

                            <div class="showing directory-footer">
                                <span>
                                    Showing <?php echo e($firstVisibleEntry); ?>-<?php echo e($lastVisibleEntry); ?> of <?php echo e($totalSupervisors); ?> supervisors
                                </span>
                                <nav class="pager" aria-label="Pagination">
                                    <a class="page-pill" href="<?php echo e(pageUrl(max(1, $currentPage - 1), $searchName, $selectedProgramme)); ?>">&lt;</a>
                                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                                        <a class="page-pill <?php echo $page === $currentPage ? "active" : ""; ?>"
                                        href="<?php echo e(pageUrl($page, $searchName, $selectedProgramme)); ?>">
                                            <?php echo e($page); ?>
                                        </a>
                                    <?php endfor; ?>
                                    <a class="page-pill" href="<?php echo e(pageUrl(min($totalPages, $currentPage + 1), $searchName, $selectedProgramme)); ?>">&gt;</a>
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

        <script>
            // ── References ──────────────────────────────────────────────────────
            const quotaRules        = <?php echo json_encode($quotaByClassification); ?>;
            const createPanel       = document.getElementById("createSupervisorPanel");
            const createClassSelect = document.getElementById("createEmploymentCategory");
            const createQuotaID     = document.getElementById("createQuotaID");
            const accountModal      = document.getElementById("accountModal");
            const editSupervisorID  = document.getElementById("editSupervisorID");
            const editFullName      = document.getElementById("editFullName");
            const editUniversityEmail = document.getElementById("editUniversityEmail");
            const editActiveStatus  = document.getElementById("editActiveStatus");

            // ── FIX 1: open / close create panel ────────────────────────────────
            document.querySelectorAll("[data-open-create]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    createPanel.classList.add("show");
                    createPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
                });
            });

            document.querySelectorAll("[data-close-create]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    createPanel.classList.remove("show");
                });
            });

            // ── FIX 2: populate hidden quotaID when classification changes ───────
            createClassSelect.addEventListener("change", () => {
                const rule = quotaRules[createClassSelect.value];
                createQuotaID.value = rule ? rule.quotaID : "";
            });

            // Seed value on page load in case panel is pre-opened (error redirect)
            (function seedQuotaID() {
                const rule = quotaRules[createClassSelect.value];
                createQuotaID.value = rule ? rule.quotaID : "";
            })();

            // ── Account modal ────────────────────────────────────────────────────
            function openAccountModal(button) {
                editSupervisorID.value    = button.dataset.supervisorId  || "";
                editFullName.value        = button.dataset.fullName      || "";
                editUniversityEmail.value = button.dataset.email         || "";
                editActiveStatus.value    = button.dataset.activeStatus  || "1";
                accountModal.classList.add("show");
                accountModal.setAttribute("aria-hidden", "false");
                editFullName.focus();
            }

            function closeAccountModal() {
                accountModal.classList.remove("show");
                accountModal.setAttribute("aria-hidden", "true");
            }

            document.querySelectorAll("[data-edit-account]").forEach((button) => {
                button.addEventListener("click", () => openAccountModal(button));
            });

            document.querySelectorAll("[data-close-account-modal]").forEach((button) => {
                button.addEventListener("click", closeAccountModal);
            });

            // Close modal on backdrop click
            accountModal.addEventListener("click", (event) => {
                if (event.target === accountModal) {
                    closeAccountModal();
                }
            });

            // Close modal on Escape key
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && accountModal.classList.contains("show")) {
                    closeAccountModal();
                }
            });
        </script>
    </body>
</html>
