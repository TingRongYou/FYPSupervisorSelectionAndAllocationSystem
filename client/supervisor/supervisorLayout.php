<?php

require_once __DIR__ . "/../shared/accountLayout.php";

if (!function_exists("e")) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

if (!function_exists("supervisorInitials")) {
    function supervisorInitials($name) {
        $parts = preg_split("/\s+/", trim($name));
        $first = strtoupper(substr($parts[0] ?? "S", 0, 1));
        $second = strtoupper(substr($parts[1] ?? "", 0, 1));
        return $first . $second;
    }
}

if (!function_exists("statusMessage")) {
    function statusMessage() {
        if (!isset($_GET["status"], $_GET["message"])) return "";
        $class = $_GET["status"] === "success" ? "success" : "error";
        return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
    }
}

function supervisorTopbar() {
    return ssasTopbar("TAR UMT SSAS");
}

function supervisorSidebar($activePage) {
    $items = [
        "business-card" => ["Digital Business Card", "manageDigitalBusinessCard.php"],
        "expertise-tags" => ["Expertise & Tags", "manageExpertiseTags.php"],
        "intro-video" => ["Introduction Video", "manageIntroVideo.php"],
        "past-projects" => ["Past Projects", "managePastProjects.php"]
    ];

    $requestItems = [
        "incoming-requests" => ["Incoming Requests", "supervisorIncomingRequests.php"],
        "decision-action" => ["Decision Action", "supervisorIncomingRequests.php"]
    ];

    $reportItems = [
        "report-demographics" => ["Applicant Demographic Chart", "supervisorApplicantDemographics.php"],
        "report-history" => ["Supervision History Log", "supervisorHistoryLog.php"],
        "report-utilization" => ["Slot Utilization Tracker", "supervisorSlotUtilization.php"]
    ];

    $profileActive = in_array($activePage, array_keys($items), true) ? "active" : "";
    $requestActive = in_array($activePage, array_keys($requestItems), true) ? "active" : "";
    $reportActive = in_array($activePage, array_keys($reportItems), true) ? "active" : "";

    $renderSubnav = function($list, $activePage) {
        $html = "";
        foreach ($list as $key => $item) {
            $active = $activePage === $key ? "active" : "";
            $html .= "<a class=\"{$active}\" href=\"" . e($item[1]) . "\">" . e($item[0]) . "</a>";
        }
        return $html;
    };

    return "
        <aside class=\"sidebar\">
            <div class=\"role-card\">
                <div class=\"role-icon\">S</div>
                <div>
                    <p class=\"role-title\">SSAS Supervisor</p>
                    <p class=\"role-subtitle\">Supervisor Portal</p>
                </div>
            </div>

            <a class=\"nav-link " . ($activePage === 'dashboard' ? 'active' : '') . "\" href=\"supervisorDashboard.php\">
                <span class=\"nav-text\">Dashboard</span>
            </a>

            <div class=\"nav-parent {$profileActive}\" onclick=\"toggleProfileMenu()\">
                <span class=\"nav-text\">Profile Management</span>
                <span class=\"nav-chevron\">v</span>
            </div>
            <div class=\"subnav\" id=\"profileSubnav\" style=\"display: " . ($profileActive ? "block" : "none") . ";\">
                {$renderSubnav($items, $activePage)}
            </div>

            <div class=\"nav-parent {$requestActive}\" onclick=\"toggleRequestMenu()\">
                <span class=\"nav-text\">Requests & Decisions</span>
                <span class=\"nav-chevron\">v</span>
            </div>
            <div class=\"subnav\" id=\"requestSubnav\" style=\"display: " . ($requestActive ? "block" : "none") . ";\">
                {$renderSubnav($requestItems, $activePage)}
            </div>

            <a class=\"nav-link " . ($activePage === 'supervision' ? 'active' : '') . "\" href=\"supervisorMySupervisees.php\">
                <span class=\"nav-text\">Supervision</span>
            </a>

            <a class=\"nav-link " . ($activePage === 'student-reviews' ? 'active' : '') . "\" href=\"supervisorStudentReviews.php\">
                <span class=\"nav-text\">Student Reviews</span>
            </a>

            <div class=\"nav-parent {$reportActive}\" onclick=\"toggleReportMenu()\">
                <span class=\"nav-text\">Reports</span>
                <span class=\"nav-chevron\">v</span>
            </div>
            <div class=\"subnav\" id=\"reportSubnav\" style=\"display: " . ($reportActive ? "block" : "none") . ";\">
                {$renderSubnav($reportItems, $activePage)}
            </div>
        </aside>
    ";
}
?>