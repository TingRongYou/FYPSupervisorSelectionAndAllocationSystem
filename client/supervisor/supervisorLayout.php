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

        if (!isset($_GET["status"], $_GET["message"])) {

            return "";
        }

        $class = $_GET["status"] === "success" ? "success" : "error";

        return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
    }
}

function supervisorBaseStyles() {

    $accountStyles = ssasAccountStyles();

    return <<<CSS
        {$accountStyles}
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f8fc; color: #1d2b3a; }
        .content-shell { display: flex; min-height: calc(100vh - 52px); }
        .sidebar { width: 280px; flex: 0 0 280px; background: #fff; border-right: 1px solid #dde8f2; padding: 26px 18px; }
        .role-card { display: flex; gap: 12px; align-items: center; width: 100%; min-height: 62px; padding: 12px; border-radius: 8px; background: #eef6fc; margin-bottom: 20px; }
        .role-card > div:last-child { min-width: 0; }
        .role-icon { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 8px; background: #0b66d8; color: #fff; display: grid; place-items: center; font-size: 15px; font-weight: 800; line-height: 1; }
        .role-title { margin: 0; color: #0b3760; font-weight: 800; font-size: 14px; }
        .role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 12px; font-weight: 400; }
        .nav-link, .nav-parent { display: flex; align-items: center; gap: 10px; color: #526a7f; text-decoration: none; padding: 12px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; font-weight: 600; background: #f1f5f9; border: 0; width: 100%; min-height: 40px; cursor: pointer; transition: background .2s, color .2s, transform .2s; white-space: nowrap; line-height: 1.2; }
        .nav-link:hover, .nav-link.active, .nav-parent.active { background: #eaf3ff; color: #0b66d8; transform: translateX(2px); font-weight: 600; }
        .nav-text { flex: 1; }
        .nav-icon { display: none; }
        .nav-chevron { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; color: #7d96b4; font-size: 13px; font-weight: 700; }
        .nav-link:hover .nav-chevron, .nav-link.active .nav-chevron, .nav-parent.active .nav-chevron { color: #0b66d8; }
        .subnav { position: relative; margin: -3px 0 8px 13px; padding: 2px 0 2px 22px; border-left: 1px solid #cbd8e6; }
        .subnav:after { content: ""; position: absolute; left: -1px; right: 0; bottom: 0; height: 1px; background: #cbd8e6; }
        .subnav a { position: relative; display: block; color: #6b7f91; text-decoration: none; font-size: 14px; font-weight: 600; padding: 7px 10px; line-height: 1.3; border-radius: 6px; white-space: nowrap; }
        .subnav a:before { content: ""; position: absolute; left: -22px; top: 50%; width: 16px; height: 1px; background: #cbd8e6; }
        .subnav a:hover,
        .subnav a.active { background: #f1f7ff; color: #0d5be8; font-weight: 600; }
        .main { flex: 1; padding: 26px 28px 42px; max-width: 100%; }
        .hero { background: #0d5be8; color: #fff; border-radius: 10px; padding: 28px 32px; box-shadow: 0 12px 24px rgba(13,91,232,.2); margin-bottom: 24px; display: flex; justify-content: space-between; gap: 20px; align-items: center; }
        .hero h1 { margin: 0 0 8px; font-size: 28px; }
        .hero p { margin: 0; color: #dbe9ff; line-height: 1.5; }
        .hero-stat { min-width: 190px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 10px; padding: 18px; }
        .stat-label { color: #b9d2ff; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; }
        .stat-value { font-size: 26px; font-weight: 900; margin-top: 6px; }
        .progress { height: 7px; border-radius: 999px; background: rgba(255,255,255,.25); overflow: hidden; margin-top: 8px; }
        .progress span { display: block; height: 100%; background: #fff; border-radius: inherit; }
        .card { background: #fff; border: 1px solid #d9e7f3; border-radius: 10px; box-shadow: 0 8px 22px rgba(11,79,138,.08); }
        .message { border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-weight: 800; }
        .message.success { background: #e5f6ed; color: #177345; border: 1px solid #a9dfbf; }
        .message.error { background: #fdeaea; color: #a52d2d; border: 1px solid #f0b8b8; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; border: 0; border-radius: 8px; padding: 0 18px; background: #0d5be8; color: #fff; text-decoration: none; font-size: 14px; font-weight: 800; cursor: pointer; }
        .button.secondary { background: #e9edf2; color: #2f4053; }
        input, select, textarea { width: 100%; border: 1px solid #dbe6f0; border-radius: 8px; background: #f6f8fb; color: #1d2b3a; font-size: 14px; }
        input, select { height: 42px; padding: 0 12px; }
        textarea { min-height: 130px; resize: vertical; padding: 12px; font-family: Arial, Helvetica, sans-serif; }
        label { display: block; color: #6b7f91; font-size: 14px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; margin-bottom: 7px; }
        @media (max-width: 980px) { .content-shell { display: block; } .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #dde8f2; } .hero { display: block; } .hero-stat { margin-top: 18px; } }
        @media (max-width: 720px) { .topbar { height: auto; padding: 16px; } .main { padding: 22px; } }
CSS;
}

function supervisorTopbar() {

    return ssasTopbar("TAR UMT SSAS");
}

function supervisorSidebar($activePage) {

    $profilePages = [
        "business-card",
        "expertise-tags",
        "intro-video",
        "past-projects"
    ];

    $dashboardActive = $activePage === "dashboard" ? "active" : "";
    $profileActive = in_array($activePage, $profilePages, true) ? "active" : "";
    $requestPages = [
        "incoming-requests",
        "decision-action"
    ];
    $requestActive = in_array($activePage, $requestPages, true) ? "active" : "";
    $supervisionActive = $activePage === "supervision" ? "active" : "";
    $reportPages = [
        "report-demographics",
        "report-history",
        "report-utilization"
    ];
    $reportActive = in_array($activePage, $reportPages, true) ? "active" : "";
    $studentReviewsActive = $activePage === "student-reviews" ? "active" : "";

    $items = [
        "business-card" => ["Digital Business Card", "manageDigitalBusinessCard.php"],
        "expertise-tags" => ["Expertise & Tags", "manageExpertiseTags.php"],
        "intro-video" => ["Introduction Video", "manageIntroVideo.php"],
        "past-projects" => ["Past Projects", "managePastProjects.php"]
    ];

    $subnav = "";

    foreach ($items as $key => $item) {

        $active = $activePage === $key ? "active" : "";

        $subnav .= "
            <a class=\"{$active}\" href=\"" . e($item[1]) . "\">
                " . e($item[0]) . "
            </a>
        ";
    }

    $requestItems = [
        "incoming-requests" => ["Incoming Requests", "supervisorIncomingRequests.php"],
        "decision-action" => ["Decision Action", "supervisorIncomingRequests.php"]
    ];

    $requestSubnav = "";

    foreach ($requestItems as $key => $item) {

        $active = $activePage === $key ? "active" : "";

        $requestSubnav .= "
            <a class=\"{$active}\" href=\"" . e($item[1]) . "\">
                " . e($item[0]) . "
            </a>
        ";
    }

    $reportItems = [
        "report-demographics" => ["Applicant Demographic Chart", "supervisorApplicantDemographics.php"],
        "report-history" => ["Supervision History Log", "supervisorHistoryLog.php"],
        "report-utilization" => ["Slot Utilization Tracker", "supervisorSlotUtilization.php"]
    ];

    $reportSubnav = "";

    foreach ($reportItems as $key => $item) {

        $active = $activePage === $key ? "active" : "";

        $reportSubnav .= "
            <a class=\"{$active}\" href=\"" . e($item[1]) . "\">
                " . e($item[0]) . "
            </a>
        ";
    }

    return "
        <aside class=\"sidebar\">
            <div class=\"role-card\">
                <div class=\"role-icon\">S</div>
                <div>
                    <p class=\"role-title\">SSAS Supervisor</p>
                    <p class=\"role-subtitle\">Supervisor Portal</p>
                </div>
            </div>

            <a class=\"nav-link {$dashboardActive}\" href=\"supervisorDashboard.php\">
                <span class=\"nav-icon dashboard-icon\"></span>
                <span class=\"nav-text\">Dashboard</span>
            </a>

            <div class=\"nav-parent {$profileActive}\" onclick=\"toggleProfileMenu()\">
                <span class=\"nav-icon profile-icon\"></span>
                <span class=\"nav-text\">Profile Management</span>
                <span class=\"nav-chevron\">v</span>
            </div>

            <div class=\"subnav\" id=\"profileSubnav\" style=\"display: " . ($profileActive ? "block" : "none") . ";\">
                {$subnav}
            </div>

            <div class=\"nav-parent {$requestActive}\" onclick=\"toggleRequestMenu()\">
                <span class=\"nav-icon request-icon\"></span>
                <span class=\"nav-text\">Requests & Decisions</span>
                <span class=\"nav-chevron\">v</span>
            </div>

            <div class=\"subnav\" id=\"requestSubnav\" style=\"display: " . ($requestActive ? "block" : "none") . ";\">
                {$requestSubnav}
            </div>

            <a class=\"nav-link {$supervisionActive}\" href=\"supervisorMySupervisees.php\">
                <span class=\"nav-icon supervision-icon\"></span>
                <span class=\"nav-text\">Supervision</span>
            </a>

            <a class=\"nav-link {$studentReviewsActive}\" href=\"supervisorStudentReviews.php\">
                <span class=\"nav-icon review-icon\"></span>
                <span class=\"nav-text\">Student Reviews</span>
            </a>

            <div class=\"nav-parent {$reportActive}\" onclick=\"toggleReportMenu()\">
                <span class=\"nav-icon report-icon\"></span>
                <span class=\"nav-text\">Reports</span>
                <span class=\"nav-chevron\">v</span>
            </div>

            <div class=\"subnav\" id=\"reportSubnav\" style=\"display: " . ($reportActive ? "block" : "none") . ";\">
                {$reportSubnav}
            </div>

        </aside>

        <script>
            function toggleProfileMenu() {
                const subnav = document.getElementById('profileSubnav');

                if (subnav.style.display === 'none' || subnav.style.display === '') {
                    subnav.style.display = 'block';
                } else {
                    subnav.style.display = 'none';
                }
            }

            function toggleRequestMenu() {
                const subnav = document.getElementById('requestSubnav');

                if (subnav.style.display === 'none' || subnav.style.display === '') {
                    subnav.style.display = 'block';
                } else {
                    subnav.style.display = 'none';
                }
            }

            function toggleReportMenu() {
                const subnav = document.getElementById('reportSubnav');

                if (subnav.style.display === 'none' || subnav.style.display === '') {
                    subnav.style.display = 'block';
                } else {
                    subnav.style.display = 'none';
                }
            }
        </script>
    ";
}

?>
