<?php

require_once __DIR__ . "/../shared/accountLayout.php";

if (!function_exists("studentSidebar")) {
    function studentSidebar($activePage) {
        $items = [
            ["dashboard", "Dashboard", "studentDashboard.php"],
            ["discovery", "Supervisor Discovery", "studentDiscovery.php"],
            ["profile", "Student Profile", "profile.php"],
            ["application-status", "Application Status", "studentApplicationStatus.php"],
            ["review", "Supervisor Review", "studentReview.php"],
            ["timeline", "Timeline & Milestones", "studentTimeline.php"]
        ];

        $navigation = "";
        foreach ($items as $item) {
            $active = $activePage === $item[0] ? "active" : "";
            $navigation .= "
                <a class=\"nav-link {$active}\" href=\"" . htmlspecialchars($item[2], ENT_QUOTES, "UTF-8") . "\">
                    " . htmlspecialchars($item[1], ENT_QUOTES, "UTF-8") . "
                </a>
            ";
        }

        return "
            <aside class=\"sidebar\">
                <div class=\"role-card\">
                    <div class=\"role-icon\">S</div>
                    <div>
                        <p class=\"role-title\">SSAS Student</p>
                        <p class=\"role-subtitle\">Student Portal</p>
                    </div>
                </div>
                {$navigation}
            </aside>
        ";
    }
}
?>