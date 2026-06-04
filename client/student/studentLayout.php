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

            $active =
                $activePage === $item[0]
                ? "active"
                : "";

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

if (!function_exists("studentSidebarStyles")) {

    function studentSidebarStyles() {

        return <<<CSS
            .layout { display: flex; min-height: calc(100vh - 52px); }
            .sidebar { width: 280px; flex: 0 0 280px; background: #fff; border-right: 1px solid #dde8f2; padding: 26px 18px; color: #1d2b3a; }
            .role-card { display: flex; gap: 12px; align-items: center; padding: 12px; border-radius: 8px; background: #eef6fc; margin-bottom: 20px; }
            .role-icon { width: 38px; height: 38px; border-radius: 8px; background: #0b66d8; color: #fff; display: grid; place-items: center; font-size: 15px; font-weight: 800; }
            .role-title { margin: 0; color: #0b3760; font-weight: 800; font-size: 14px; }
            .role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 12px; font-weight: 400; }
            .nav-link { display: flex; align-items: center; color: #526a7f; text-decoration: none; padding: 12px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; font-weight: 600; background: #f1f5f9; min-height: 40px; transition: background .2s, color .2s, transform .2s; white-space: nowrap; line-height: 1.2; }
            .nav-link:hover,
            .nav-link.active { background: #eaf3ff; color: #0b66d8; transform: translateX(2px); font-weight: 600; }
            @media (max-width: 900px) {
                .layout { display: block; }
                .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #dde8f2; }
            }
CSS;
    }
}

?>
