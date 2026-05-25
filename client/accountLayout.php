<?php

if (!function_exists("ssasEscape")) {

    function ssasEscape($value) {

        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

if (!function_exists("ssasInitials")) {

    function ssasInitials($name) {

        $parts = preg_split("/\s+/", trim((string) $name));
        $first = strtoupper(substr($parts[0] ?? "U", 0, 1));
        $second = strtoupper(substr($parts[1] ?? "", 0, 1));

        return $first . $second;
    }
}

if (!function_exists("ssasRoleLabel")) {

    function ssasRoleLabel($role) {

        if ($role === "Administrator") {
            return "Administrator";
        }

        if ($role === "Supervisor") {
            return "Full-Time Lecturer";
        }

        if ($role === "Student") {
            return "Student";
        }

        return "SSAS User";
    }
}

if (!function_exists("ssasAccountStyles")) {

    function ssasAccountStyles() {

        return <<<CSS
            .topbar { height: 52px !important; background: #1195c1 !important; color: #fff !important; display: flex !important; align-items: center !important; justify-content: space-between !important; padding: 0 16px !important; box-shadow: 0 3px 12px rgba(11,79,138,.16) !important; }
            .topbar-brand { display: flex !important; align-items: center !important; gap: 10px !important; font-size: 18px !important; font-weight: 900 !important; letter-spacing: .2px !important; }
            .brand-logo { width: 30px; height: 30px; object-fit: contain; border-radius: 4px; background: #fff; }
            .topbar-right { display: flex !important; align-items: stretch !important; align-self: stretch !important; margin-right: -16px !important; }
            .topbar-clock { display: flex; align-items: center; gap: 6px; padding: 0 14px; color: rgba(255,255,255,.86); font-size: 12px; white-space: nowrap; }
            .topbar-clock:before { content: ""; width: 12px; height: 12px; border: 2px solid rgba(255,255,255,.72); border-radius: 50%; display: inline-block; }
            .account-menu { position: relative; display: flex; align-items: stretch; min-height: 52px; }
            .account-toggle { border: 0; color: #fff; background: #49a7d1; display: flex; align-items: center; gap: 10px; padding: 0 14px; cursor: pointer; font-family: Arial, Helvetica, sans-serif; min-width: 190px; }
            .account-avatar { width: 34px; height: 34px; border-radius: 50%; background: #fff; color: #0b4f8a; display: grid; place-items: center; font-weight: 900; border: 2px solid rgba(255,255,255,.75); flex: 0 0 auto; font-size: 12px; }
            .account-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
            .account-copy { text-align: left; line-height: 1.15; min-width: 0; }
            .account-copy span { display: block; font-size: 11px; font-weight: 700; }
            .account-copy strong { display: block; font-size: 12px; max-width: 126px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; }
            .account-caret { margin-left: auto; width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid #fff; }
            .account-dropdown { position: absolute; right: 0; top: 52px; z-index: 30; min-width: 190px; background: #fff; border: 1px solid #c9d3dc; box-shadow: 0 6px 16px rgba(0,0,0,.18); display: none; }
            .account-menu:hover .account-dropdown, .account-menu:focus-within .account-dropdown { display: block; }
            .account-dropdown a { display: flex; align-items: center; gap: 10px; padding: 12px 14px; color: #333; text-decoration: none; font-size: 13px; border-bottom: 1px solid #edf1f4; }
            .account-dropdown a:hover { background: #f4f8fc; color: #0b66d8; }
            .account-dropdown a:last-child { border-bottom: 0; }
            .account-dropdown .logout-link { padding-top: 16px; padding-bottom: 16px; border-top: 1px solid #dfe5ea; }
            .menu-icon { width: 16px; height: 16px; display: inline-grid; place-items: center; color: #222; position: relative; flex: 0 0 16px; }
            .menu-icon:before { content: ""; display: block; border: 2px solid currentColor; }
            .profile-menu-icon:before { width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 9px 0 -2px currentColor; }
            .password-menu-icon:before { width: 8px; height: 8px; border-radius: 50%; }
            .password-menu-icon:after { content: ""; width: 8px; height: 2px; background: currentColor; position: absolute; right: 0; bottom: 1px; box-shadow: 4px 0 0 currentColor; }
            .logout-menu-icon:before { width: 10px; height: 10px; border-radius: 50%; border-top-color: transparent; }
            .logout-menu-icon:after { content: ""; width: 2px; height: 8px; background: currentColor; position: absolute; top: 0; }
            @media (max-width: 720px) {
                .topbar { height: auto; flex-wrap: wrap; gap: 10px; padding: 12px; }
                .topbar-right { width: 100%; justify-content: flex-end; margin-right: 0; }
                .topbar-clock { padding: 8px 10px; }
                .account-menu { min-height: 44px; }
                .account-toggle { min-width: 0; padding: 8px 12px; }
                .account-copy strong { max-width: 92px; }
                .account-dropdown { top: 44px; }
            }
CSS;
    }
}

if (!function_exists("ssasAccountMenu")) {

    function ssasAccountMenu() {

        $name = $_SESSION["fullName"] ?? "SSAS User";
        $profilePhotoPath = $_SESSION["profilePhotoPath"] ?? "";
        $avatar = ssasEscape(ssasInitials($name));

        if ($profilePhotoPath !== "") {

            $avatar = "<img src=\"" . ssasEscape($profilePhotoPath) . "\" alt=\"Profile photo\">";
        }

        return "
            <div class=\"account-menu\">
                <button class=\"account-toggle\" type=\"button\" aria-label=\"Open account menu\">
                    <span class=\"account-avatar\">" . $avatar . "</span>
                    <span class=\"account-copy\">
                        <span>Welcome,</span>
                        <strong>" . ssasEscape($name) . "</strong>
                    </span>
                    <span class=\"account-caret\" aria-hidden=\"true\"></span>
                </button>
                <nav class=\"account-dropdown\" aria-label=\"Account menu\">
                    <a href=\"profile.php\"><span class=\"menu-icon profile-menu-icon\"></span> Profile</a>
                    <a href=\"setPassword.php\"><span class=\"menu-icon password-menu-icon\"></span> Set Password</a>
                    <a class=\"logout-link\" href=\"../server/application/logout.php\"><span class=\"menu-icon logout-menu-icon\"></span> Logout</a>
                </nav>
            </div>
        ";
    }
}

if (!function_exists("ssasTopbar")) {

    function ssasTopbar($title = "TAR UMT SSAS") {

        $role = $_SESSION["systemRole"] ?? "User";
        $dateTime = date("D M d H:i:s");

        return "
            <header class=\"topbar\">
                <div class=\"topbar-brand\">
                    <img class=\"brand-logo\" src=\"assets/logo.png\" alt=\"TAR UMT logo\">
                    <span>" . ssasEscape($title) . "</span>
                </div>
                <div class=\"topbar-right\">
                    <div class=\"topbar-clock\">" . ssasEscape($dateTime) . "</div>
                    " . ssasAccountMenu() . "
                </div>
            </header>
        ";
    }
}

if (!function_exists("ssasStatusMessage")) {

    function ssasStatusMessage() {

        if (!isset($_GET["status"], $_GET["message"])) {

            return "";
        }

        $class = $_GET["status"] === "success" ? "success" : "error";

        return "<div class=\"message {$class}\">" . ssasEscape($_GET["message"]) . "</div>";
    }
}

if (!function_exists("ssasPortalShellStyles")) {

    function ssasPortalShellStyles() {

        return <<<CSS
            .portal-shell { display: flex; min-height: calc(100vh - 52px); background: #f4f8fc; }
            .portal-sidebar { width: 212px; flex: 0 0 212px; background: #fff; border-right: 1px solid #dce8f3; padding: 16px 10px; }
            .portal-role-card { display: flex; gap: 10px; align-items: center; padding: 6px 9px 14px; margin-bottom: 8px; }
            .portal-role-icon { width: 30px; height: 30px; border-radius: 7px; background: #0d5be8; color: #fff; display: grid; place-items: center; font-size: 12px; font-weight: 900; }
            .portal-role-title { margin: 0; color: #10263d; font-weight: 900; font-size: 14px; }
            .portal-role-subtitle { margin: 2px 0 0; color: #6b7f91; font-size: 10px; }
            .portal-nav-link, .portal-nav-parent { display: flex; align-items: center; gap: 8px; color: #526a7f; text-decoration: none; padding: 9px 10px; border-radius: 6px; margin-bottom: 5px; font-size: 12px; background: #f1f5f9; border: 0; width: 100%; min-height: 32px; cursor: pointer; }
            .portal-nav-link:hover, .portal-nav-link.active, .portal-nav-parent.active { background: #eaf3ff; color: #0d5be8; }
            .portal-nav-text { flex: 1; }
            .portal-nav-chevron { color: #315e8c; font-weight: 900; }
            .portal-nav-icon { width: 16px; height: 16px; border-radius: 4px; display: grid; place-items: center; border: 1px solid #c7d9ee; background: #fff; position: relative; flex: 0 0 16px; }
            .portal-nav-icon:before { content: ""; width: 8px; height: 8px; border: 1px solid #7d96b4; border-radius: 2px; display: block; }
            .portal-profile-icon:before { width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 9px 0 -2px #7d96b4; }
            .portal-password-icon:before { width: 8px; height: 8px; border-radius: 50%; }
            .portal-password-icon:after { content: ""; width: 8px; height: 2px; background: #7d96b4; position: absolute; right: 1px; bottom: 2px; box-shadow: 4px 0 0 #7d96b4; }
            .portal-logout-icon:before { width: 8px; height: 1px; border-width: 0 0 1px; border-radius: 0; box-shadow: -3px -3px 0 -1px #7d96b4, -3px 3px 0 -1px #7d96b4; }
            .portal-subnav { position: relative; margin: -3px 0 10px 26px; padding: 3px 0 3px 14px; border-left: 1px solid #bfcbd8; }
            .portal-subnav a { position: relative; display: block; color: #6b7f91; text-decoration: none; font-size: 11px; padding: 5px 0; line-height: 1.25; }
            .portal-subnav a:before { content: ""; position: absolute; left: -14px; top: 12px; width: 10px; height: 1px; background: #bfcbd8; }
            .portal-subnav a.active { color: #0d5be8; font-weight: 800; }
            .portal-main { flex: 1; padding: 26px 28px 42px; max-width: 100%; }
            @media (max-width: 900px) {
                .portal-shell { display: block; }
                .portal-sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #dce8f3; }
                .portal-main { padding: 20px; }
            }
CSS;
    }
}

if (!function_exists("ssasDashboardUrlForRole")) {

    function ssasDashboardUrlForRole($role) {

        if ($role === "Administrator") {
            return "adminDashboard.php";
        }

        if ($role === "Supervisor") {
            return "supervisorDashboard.php";
        }

        if ($role === "Student") {
            return "studentDashboard.php";
        }

        return "dashboard.php";
    }
}

if (!function_exists("ssasPortalSidebar")) {

    function ssasPortalSidebar($activePage) {

        $role = $_SESSION["systemRole"] ?? "User";
        $roleTitle = "SSAS User";
        $roleSubtitle = "User Portal";
        $links = [];

        if ($role === "Supervisor") {

            $roleTitle = "SSAS Supervisor";
            $roleSubtitle = "Supervisor Portal";
            $links = [
                ["dashboard", "Dashboard", "supervisorDashboard.php", "", false],
                ["profile-management", "Profile Management", "#", "v", true],
                ["business-card", "Digital Business Card", "manageDigitalBusinessCard.php", "", false, true],
                ["expertise-tags", "Expertise & Tags", "manageExpertiseTags.php", "", false, true],
                ["intro-video", "Introduction Video", "manageIntroVideo.php", "", false, true],
                ["past-projects", "Past Projects", "managePastProjects.php", "", false, true],
                ["requests", "Requests & Decisions", "#", "v", false],
                ["supervision", "Supervision", "#", "", false],
                ["reports", "Reports", "#", "v", false]
            ];

        } elseif ($role === "Administrator") {

            $roleTitle = "SSAS Admin";
            $roleSubtitle = "Management Portal";
            $links = [
                ["dashboard", "Dashboard", "adminDashboard.php", "", false],
                ["supervisors", "Supervisors Management", "createSupervisorForm.php", "", false],
                ["eligibility", "Students Eligibility", "#", "", false],
                ["quota", "Quota Management", "#", "", false],
                ["allocations", "Allocations", "#", "", false],
                ["reports", "Reports", "#", "v", false]
            ];

        } elseif ($role === "Student") {

            $roleTitle = "SSAS Student";
            $roleSubtitle = "Student Portal";
            $links = [
                ["dashboard", "Dashboard", "studentDashboard.php", "", false],
                ["discovery", "Discovery", "studentDiscovery.php", "", false],
                ["profile", "Profile", "profile.php", "", false],
                ["review", "Review", "#", "", false]
            ];
        }

        $navigation = "";
        $subnavOpen = false;

        foreach ($links as $link) {

            $key = $link[0];
            $label = $link[1];
            $href = $link[2];
            $chevron = $link[3];
            $isParent = $link[4];
            $isSubnav = $link[5] ?? false;
            $isActive = $activePage === $key ? "active" : "";

            if ($isSubnav) {

                if (!$subnavOpen) {

                    $navigation .= "<div class=\"portal-subnav\">";
                    $subnavOpen = true;
                }

                $navigation .= "
                    <a class=\"{$isActive}\" href=\"" . ssasEscape($href) . "\">" . ssasEscape($label) . "</a>
                ";

                continue;
            }

            if ($subnavOpen) {

                $navigation .= "</div>";
                $subnavOpen = false;
            }

            $tag = $isParent ? "div" : "a";
            $hrefAttribute = $isParent ? "" : " href=\"" . ssasEscape($href) . "\"";
            $chevronMarkup = $chevron !== "" ? "<span class=\"portal-nav-chevron\">" . ssasEscape($chevron) . "</span>" : "";

            $navigation .= "
                <{$tag} class=\"portal-nav-link {$isActive}\"{$hrefAttribute}>
                    <span class=\"portal-nav-icon\"></span>
                    <span class=\"portal-nav-text\">" . ssasEscape($label) . "</span>
                    {$chevronMarkup}
                </{$tag}>
            ";
        }

        if ($subnavOpen) {

            $navigation .= "</div>";
        }

        return "
            <aside class=\"portal-sidebar\">
                <div class=\"portal-role-card\">
                    <div class=\"portal-role-icon\">" . ssasEscape(substr($role, 0, 1)) . "</div>
                    <div>
                        <p class=\"portal-role-title\">" . ssasEscape($roleTitle) . "</p>
                        <p class=\"portal-role-subtitle\">" . ssasEscape($roleSubtitle) . "</p>
                    </div>
                </div>

                {$navigation}

                <a class=\"portal-nav-link\" href=\"../server/application/logout.php\">
                    <span class=\"portal-nav-icon portal-logout-icon\"></span>
                    <span class=\"portal-nav-text\">Logout</span>
                </a>
            </aside>
        ";
    }
}

?>
