<?php

if (!function_exists("e")) {
    function e($value) {

        return
            htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

function adminReportStyles() {

    /*
    |--------------------------------------------------------------------------
    | Shared Admin Report UI Styles
    |--------------------------------------------------------------------------
    | Used by both Cohort Overview and Allocation Summary to keep the report
    | module visually consistent.
    */

    return <<<CSS
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #1d2b3a; }
        .content-shell { display: flex; min-height: calc(100vh - 52px); }
        .sidebar { width: 280px; flex: 0 0 280px; background: #fff; border-right: 1px solid #dde8f2; padding: 26px 18px; }
        .role-card { display: flex; gap: 12px; align-items: center; padding: 12px; border-radius: 8px; background: #eef6fc; margin-bottom: 20px; }
        .role-icon { width: 36px; height: 36px; border-radius: 8px; background: #0d5be8; color: #fff; display: grid; place-items: center; font-size: 15px; font-weight: 900; }
        .role-title { margin: 0; color: #10263d; font-weight: 900; font-size: 14px; }
        .role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 13px; }
        .nav-link { display: flex; align-items: center; gap: 10px; color: #526a7f; text-decoration: none; padding: 12px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; font-weight: 400; transition: background .2s, color .2s, transform .2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { background: #eaf3ff; color: #0b66d8; transform: translateX(2px); }
        .sidebar .role-card { min-height: 62px; }
        .sidebar .role-icon { width: 38px; height: 38px; font-size: 15px; font-weight: 800; }
        .sidebar .role-title { font-size: 14px; font-weight: 800; }
        .sidebar .role-subtitle { font-size: 12px; font-weight: 400; text-transform: none; letter-spacing: 0; }
        .sidebar .nav-link,
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active { min-height: 40px; padding: 12px 14px; margin-bottom: 8px; border-radius: 8px; font-size: 14px; font-weight: 600; line-height: 1.2; white-space: nowrap; }
        .nav-link.has-submenu { width: 100%; border: 0; background: #f1f5f9; font-family: inherit; cursor: pointer; justify-content: space-between; text-align: left; }
        .sidebar .nav-link { background: #f1f5f9; color: #526a7f; font-weight: 600; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #eaf3ff; color: #0b66d8; }
        .submenu-caret { color: #7d96b4; font-size: 14px; font-weight: 900; line-height: 1; transition: color .2s, transform .2s; }
        .nav-link.has-submenu[aria-expanded="true"] .submenu-caret { color: #0b66d8; transform: rotate(180deg); }
        .report-tree { display: none; position: relative; margin: -4px 0 8px 16px; padding-left: 14px; border-left: 1px solid #c9d8e8; }
        .report-tree.open { display: block; }
        .report-tree:after { content: ""; position: absolute; left: -1px; right: 0; bottom: 0; height: 1px; background: #c9d8e8; }
        .report-child { position: relative; display: block; padding: 9px 10px; color: #526a7f; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 6px; }
        .report-child:before { content: ""; position: absolute; left: -14px; top: 50%; width: 14px; height: 1px; background: #c9d8e8; }
        .report-child.active, .report-child:hover { color: #0d5be8; background: #f0f6ff; font-weight: 600; }
        .main { flex: 1; padding: 28px 36px 72px; min-width: 0; }
        .report-shell { width: 100%; max-width: none; margin: 0; }
        .report-head { display: flex; justify-content: space-between; gap: 18px; align-items: start; margin-bottom: 22px; }
        .report-head h1 { margin: 0 0 6px; color: #10263d; font-size: 26px; line-height: 1.1; }
        .report-head p { margin: 0; color: #60758a; font-size: 15px; line-height: 1.5; max-width: 620px; }
        .export-menu { display: flex; align-items: center; gap: 8px; }
        .export-menu select { width: 136px; height: 38px; border: 1px solid #d9e7f3; border-radius: 7px; background: #fff; color: #10263d; font-size: 14px; font-weight: 900; padding: 0 10px; }
        .export-menu button, .button { min-height: 38px; border: 0; border-radius: 7px; padding: 0 16px; background: #0d5be8; color: #fff; font-size: 14px; font-weight: 900; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .export-menu button { background: #eef2f7; color: #1d2b3a; }
        .button.secondary { background: #eef2f7; color: #3d5166; }
        .filter-card { background: #fff; border: 1px solid #e1ebf5; border-radius: 12px; padding: 16px; margin-bottom: 22px; box-shadow: 0 12px 28px rgba(11,79,138,.06); }
        .filter-form { display: grid; grid-template-columns: repeat(4, minmax(145px, 1fr)) auto; gap: 12px; align-items: end; }
        .filter-field label { display: block; color: #8a9caf; font-size: 13px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
        .filter-field select { width: 100%; height: 44px; border: 1px solid #dbe6f0; border-radius: 8px; background: #f8fbff; color: #10263d; padding: 0 12px; font-size: 14px; font-weight: 800; }
        .hero-grid { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 22px; margin-bottom: 24px; }
        .cohort-card { min-height: 170px; background: linear-gradient(135deg, #1768f2 0%, #0d48d8 100%); color: #fff; border-radius: 12px; padding: 28px 30px; box-shadow: 0 14px 28px rgba(13,91,232,.22); position: relative; overflow: hidden; }
        .cohort-card:after { content: ""; position: absolute; right: -28px; top: -28px; width: 170px; height: 170px; border: 24px solid rgba(255,255,255,.08); transform: rotate(45deg); }
        .cohort-card h2 { margin: 0 0 4px; font-size: 25px; letter-spacing: .6px; text-transform: uppercase; }
        .cohort-card p { margin: 0 0 26px; color: #c9dcff; font-size: 14px; }
        .metric-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; max-width: 560px; }
        .metric-label { color: #bcd4ff; font-size: 12px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        .metric-value { margin-top: 6px; font-size: 25px; font-weight: 900; }
        .progress-card, .summary-card { background: #fff; border: 1px solid #e1ebf5; border-radius: 12px; padding: 24px; box-shadow: 0 12px 28px rgba(11,79,138,.06); }
        .progress-label { color: #8a9caf; font-size: 13px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        .progress-value { margin: 10px 0 8px; color: #10263d; font-size: 34px; font-weight: 900; }
        .meter { height: 8px; border-radius: 999px; background: #e8eef5; overflow: hidden; }
        .meter span { display: block; height: 100%; background: #0d5be8; border-radius: inherit; }
        .note { margin: 10px 0 0; color: #60758a; font-size: 14px; line-height: 1.5; }
        .table-card { background: #fff; border: 1px solid #e1ebf5; border-radius: 12px; overflow: hidden; box-shadow: 0 12px 28px rgba(11,79,138,.06); }
        .table-headline { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 20px 22px 14px; }
        .table-headline h2 { margin: 0; color: #10263d; font-size: 19px; }
        .table-headline p { margin: 4px 0 0; color: #60758a; font-size: 14px; }
        .table-scroll { overflow-x: auto; }
        .report-table { width: 100%; border-collapse: collapse; min-width: 780px; }
        .report-table th, .report-table td { padding: 15px 18px; border-top: 1px solid #eef3f8; text-align: left; vertical-align: middle; font-size: 14px; }
        .report-table th { background: #fbfdff; color: #8a9caf; font-size: 12px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        .person-cell { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: #26384c; color: #fff; font-size: 12px; font-weight: 900; overflow: hidden; flex: 0 0 auto; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .name { margin: 0; color: #10263d; font-size: 14px; font-weight: 900; }
        .meta { margin: 3px 0 0; color: #60758a; font-size: 14px; }
        .status-pill { display: inline-flex; align-items: center; justify-content: center; min-height: 24px; border-radius: 999px; padding: 0 12px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .status-pill.blue { background: #e7f0ff; color: #0d5be8; }
        .status-pill.red { background: #fee2e2; color: #b42318; }
        .status-pill.orange { background: #fff1db; color: #a84600; }
        .status-pill.green { background: #dcfce7; color: #177345; }
        .capacity-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin-bottom: 24px; }
        .summary-card { min-height: 150px; }
        .summary-card.primary { grid-column: span 2; }
        .summary-card.primary { border-bottom: 4px solid #0d5be8; }
        .summary-number { color: #0d5be8; font-size: 36px; font-weight: 600; margin: 12px 0; }
        .roster-list { display: grid; gap: 14px; }
        .roster-item { display: grid; grid-template-columns: 230px minmax(0, 1fr) 110px 24px; gap: 18px; align-items: center; background: #fff; border-radius: 11px; border-left: 4px solid #0d5be8; padding: 15px 18px; box-shadow: 0 10px 22px rgba(11,79,138,.07); }
        .roster-item.full { border-left-color: #dc2626; }
        .roster-item.high { border-left-color: #b45309; }
        .load-label { display: flex; justify-content: space-between; gap: 14px; color: #526a7f; font-size: 14px; font-weight: 900; margin-bottom: 6px; text-transform: uppercase; }
        .load-label.full { color: #dc2626; }
        .load-label.high { color: #b45309; }
        .last-active { color: #60758a; font-size: 14px; font-weight: 800; text-transform: uppercase; }
        .pagination-note { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-top: 1px solid #eef3f8; color: #7c8da0; font-size: 13px; }
        .empty-message { padding: 22px; color: #526a7f; background: #f8fbff; border-top: 1px solid #eef3f8; }
        @media (max-width: 1100px) {
            .filter-form, .hero-grid, .capacity-grid { grid-template-columns: 1fr; }
            .roster-item { grid-template-columns: 1fr; }
        }
        @media (max-width: 850px) {
            .content-shell { display: block; }
            .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #dde8f2; }
            .main { padding: 22px 18px 70px; }
            .report-head { display: block; }
            .export-menu { margin-top: 14px; }
        }
CSS;
}

function adminReportSidebar($active) {

    /*
    |--------------------------------------------------------------------------
    | Report Navigation
    |--------------------------------------------------------------------------
    | Renders the admin sidebar with the report submenu expanded.
    */

    $cohortClass =
        $active === "cohort" ? "active" : "";

    $allocationClass =
        $active === "allocation" ? "active" : "";

    $reviewsClass =
        $active === "reviews" ? "active" : "";

    $reportsClass =
        in_array($active, ["cohort", "allocation"], true) ? "active" : "";

    $reportsExpanded =
        in_array($active, ["cohort", "allocation"], true);

    $reportsExpandedAttribute =
        $reportsExpanded ? "true" : "false";

    $reportTreeClass =
        $reportsExpanded ? "report-tree open" : "report-tree";

    return "
        <aside class=\"sidebar\">
            <div class=\"role-card\">
                <div class=\"role-icon\">A</div>
                <div>
                    <p class=\"role-title\">SSAS Admin</p>
                    <p class=\"role-subtitle\">Management Portal</p>
                </div>
            </div>
            <a class=\"nav-link\" href=\"adminDashboard.php\">Dashboard</a>
            <a class=\"nav-link\" href=\"supervisorsManagement.php\">Supervisors Management</a>
            <a class=\"nav-link\" href=\"studentEligibility.php\">Students Eligibility</a>
            <a class=\"nav-link\" href=\"quotaManagement.php\">Quota Management</a>
            <a class=\"nav-link\" href=\"autoAllocation.php\">Allocations</a>
            <a class=\"nav-link {$reviewsClass}\" href=\"adminSupervisorReviews.php\">Supervisor Reviews Audit</a>
            <button class=\"nav-link has-submenu {$reportsClass}\" type=\"button\" aria-expanded=\"{$reportsExpandedAttribute}\" aria-controls=\"admin-report-tree\" onclick=\"toggleAdminReports(this)\">
                <span>Reports</span>
                <span class=\"submenu-caret\" aria-hidden=\"true\">v</span>
            </button>
            <div class=\"{$reportTreeClass}\" id=\"admin-report-tree\">
                <a class=\"report-child {$cohortClass}\" href=\"adminCohortOverview.php\">Cohort Overview</a>
                <a class=\"report-child {$allocationClass}\" href=\"adminAllocationSummary.php\">Allocation Summary</a>
            </div>
            <script>
                function toggleAdminReports(button) {
                    const reportTree = document.getElementById(\"admin-report-tree\");
                    const isOpen = button.getAttribute(\"aria-expanded\") === \"true\";
                    button.setAttribute(\"aria-expanded\", isOpen ? \"false\" : \"true\");
                    reportTree.classList.toggle(\"open\", !isOpen);
                }
            </script>
        </aside>
    ";
}

function adminReportExportMenu($reportType, $filters = []) {

    /*
    |--------------------------------------------------------------------------
    | Multi-Format Export Menu
    |--------------------------------------------------------------------------
    | Keeps the current report filters when exporting PDF, CSV, or Excel.
    */

    return "
        <form class=\"export-menu\" method=\"GET\" action=\"../../server/application/admin/exportAdminReport.php\" onsubmit=\"return prepareAdminReportExport(this);\">
            <input type=\"hidden\" name=\"reportType\" value=\"" . e($reportType) . "\">
            " . adminReportHiddenInputs($filters) . "
            <select name=\"format\" aria-label=\"Export format\">
                <option value=\"pdf\">PDF</option>
                <option value=\"csv\">CSV</option>
                <option value=\"xls\">Excel</option>
            </select>
            <button type=\"submit\">Export</button>
        </form>
    ";
}

function adminReportHiddenInputs($filters) {

    $html = "";

    foreach ($filters as $key => $value) {
        $html .= "<input type=\"hidden\" name=\"" . e($key) . "\" value=\"" . e($value) . "\">";
    }

    return
        $html;
}

function adminReportExportScript() {

    /*
    |--------------------------------------------------------------------------
    | PDF Print Isolation
    |--------------------------------------------------------------------------
    | Loads the printable PDF view into a hidden iframe so the current report
    | screen remains visible after the browser print dialog opens.
    */

    return "
        <script>
            function prepareAdminReportExport(form) {
                const formatSelect = form.querySelector('select[name=\"format\"]');
                const format = formatSelect ? formatSelect.value : '';

                if (format === 'pdf') {
                    const params = new URLSearchParams(new FormData(form));
                    let printFrame = document.getElementById('adminReportPrintFrame');

                    if (!printFrame) {
                        printFrame = document.createElement('iframe');
                        printFrame.id = 'adminReportPrintFrame';
                        printFrame.name = 'adminReportPrintFrame';
                        printFrame.style.position = 'fixed';
                        printFrame.style.right = '0';
                        printFrame.style.bottom = '0';
                        printFrame.style.width = '1px';
                        printFrame.style.height = '1px';
                        printFrame.style.border = '0';
                        printFrame.style.opacity = '0';
                        document.body.appendChild(printFrame);
                    }

                    printFrame.src = form.action + '?' + params.toString();

                    return false;
                }

                return true;
            }
        </script>
    ";
}

function adminReportInitials($name) {

    // Builds compact avatar initials for rows without profile photos.
    $parts =
        preg_split("/\s+/", trim((string) $name));

    return
        strtoupper(substr($parts[0] ?? "A", 0, 1))
        . strtoupper(substr($parts[1] ?? "", 0, 1));
}

function adminLastActiveLabel($dateValue) {

    // Converts the latest allocation timestamp into a short roster label.
    if (empty($dateValue)) {
        return "No activity";
    }

    $seconds =
        max(0, time() - strtotime($dateValue));

    if ($seconds < 3600) {
        return "Today";
    }

    if ($seconds < 86400) {
        return floor($seconds / 3600) . " hours ago";
    }

    return floor($seconds / 86400) . " days ago";
}

?>
