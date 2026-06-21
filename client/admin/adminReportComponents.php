<?php

if (!function_exists("e")) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

function adminReportSidebar($active) {
    /*
    |--------------------------------------------------------------------------
    | Report Navigation
    |--------------------------------------------------------------------------
    | Renders the admin sidebar with the report submenu expanded.
    */

    $cohortClass = $active === "cohort" ? "active" : "";
    $allocationClass = $active === "allocation" ? "active" : "";
    $reviewsClass = $active === "reviews" ? "active" : "";
    $reportsClass = in_array($active, ["cohort", "allocation"], true) ? "active" : "";
    $reportsExpanded = in_array($active, ["cohort", "allocation"], true);
    $reportsExpandedAttribute = $reportsExpanded ? "true" : "false";
    $reportTreeClass = $reportsExpanded ? "report-tree open" : "report-tree";

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
        </aside>
    ";
}

function adminReportExportMenu($reportType, $filters = []) {
    /*
    |--------------------------------------------------------------------------
    | Multi-Format Export Menu
    |--------------------------------------------------------------------------
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
    return $html;
}

function adminReportInitials($name) {
    // Builds compact avatar initials for rows without profile photos.
    $parts = preg_split("/\s+/", trim((string) $name));
    return strtoupper(substr($parts[0] ?? "A", 0, 1)) . strtoupper(substr($parts[1] ?? "", 0, 1));
}

function adminLastActiveLabel($dateValue) {
    // Converts the latest allocation timestamp into a short roster label.
    if (empty($dateValue)) {
        return "No activity";
    }

    $seconds = max(0, time() - strtotime($dateValue));

    if ($seconds < 3600) {
        return "Today";
    }
    if ($seconds < 86400) {
        return floor($seconds / 3600) . " hours ago";
    }
    return floor($seconds / 86400) . " days ago";
}

?>