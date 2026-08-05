<?php

/*
|--------------------------------------------------------------------------
| Utility Functions
|--------------------------------------------------------------------------
| Contains reusable helper functions used across the SSAS system.
*/

/*
|--------------------------------------------------------------------------
| HTML Escape Function
|--------------------------------------------------------------------------
| Prevents XSS attacks by safely escaping output before displaying.
*/

if (!function_exists("ssasEscape")) {
    function ssasEscape($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
    }
}

/*
|--------------------------------------------------------------------------
| User Initial Generator
|--------------------------------------------------------------------------
| Generates user initials for avatar display.
*/
if (!function_exists("ssasInitials")) {
    function ssasInitials($name) {

        $parts = preg_split("/\s+/", trim((string) $name));
        $first = strtoupper(substr($parts[0] ?? "U", 0, 1));
        $second = strtoupper(substr($parts[1] ?? "", 0, 1));

        return $first . $second;
    }
}

/*
|--------------------------------------------------------------------------
| Dynamic Star Rating Generator
|--------------------------------------------------------------------------
| Generates a visually accurate star rating bar based on a 0.0 to 5.0 score.
*/
if (!function_exists("ssasRenderStars")) {
    function ssasRenderStars($rating) {
        
        // Ensure the rating is a safe float between 0 and 5
        $safeRating = max(0, min(5, (float) $rating));
        
        // Calculate what percentage of the 5 stars should be filled
        $percentage = ($safeRating / 5) * 100;

        return "
            <div class=\"dynamic-stars\" aria-label=\"Rating: {$safeRating} out of 5\" title=\"{$safeRating} out of 5\">
                <div class=\"stars-empty\">★★★★★</div>
                <div class=\"stars-filled\" style=\"width: {$percentage}%;\">★★★★★</div>
            </div>
        ";
    }
}

/*
|--------------------------------------------------------------------------
| Role Label Mapping
|--------------------------------------------------------------------------
| Converts internal system roles into readable labels.
*/

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

/*
|--------------------------------------------------------------------------
| Account Dropdown Menu
|--------------------------------------------------------------------------
| Displays logged-in user profile menu.
*/

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
                    <a href=\"../../client/shared/profile.php\"><span class=\"menu-icon profile-menu-icon\"></span> Profile</a>
                    <a href=\"../../client/shared/setPassword.php\"><span class=\"menu-icon password-menu-icon\"></span> Set Password</a>
                    <a class=\"logout-link\" href=\"../../server/application/auth/logout.php\"><span class=\"menu-icon logout-menu-icon\"></span> Logout</a>
                </nav>
            </div>
        ";
    }
}
/*
|--------------------------------------------------------------------------
| Shared Topbar
|--------------------------------------------------------------------------
| Generates reusable SSAS top navigation bar.
*/

if (!function_exists("ssasTopbar")) {
    function ssasTopbar($title = "TAR UMT SSAS") {

        $role = $_SESSION["systemRole"] ?? "User";
        $dateTime = (new DateTime("now", new DateTimeZone("Asia/Kuala_Lumpur")))->format("D M d H:i:s");

        // Dynamically route the logo link based on the active session role
        $dashboardLink = "#";
        if ($role === "Student") {
            $dashboardLink = "../../client/student/studentDashboard.php";
        } elseif ($role === "Supervisor") {
            $dashboardLink = "../../client/supervisor/supervisorDashboard.php";
        } elseif ($role === "Administrator") {
            $dashboardLink = "../../client/admin/adminDashboard.php";
        }

        return "
            <header class=\"topbar\">
                <div class=\"topbar-brand\">
                    <a href=\"" . $dashboardLink . "\" style=\"display: flex; align-items: center; text-decoration: none; color: inherit;\">
                        <img class=\"brand-logo\" src=\"../../client/assets/img/tarumt_logo_only.png\" alt=\"TAR UMT logo\">
                        <span>" . ssasEscape($title) . "</span>
                    </a>
                </div>
                <div class=\"topbar-right\">
                    <div class=\"topbar-clock\">" . ssasEscape($dateTime) . " MYT</div>
                    " . ssasAccountMenu() . "
                </div>
            </header>
        ";
    }
}

/*
|--------------------------------------------------------------------------
| Status Message Generator
|--------------------------------------------------------------------------
| Displays success or error messages from URL parameters.
*/

if (!function_exists("ssasStatusMessage")) {
    function ssasStatusMessage() {
        if (!isset($_GET["status"], $_GET["message"])) {
            return "";
        }

        $class = $_GET["status"] === "success" ? "success" : "error";

        return "<div class=\"message {$class}\">" . ssasEscape($_GET["message"]) . "</div>";
    }
}

/*
|--------------------------------------------------------------------------
| Dashboard URL Mapping
|--------------------------------------------------------------------------
| Returns dashboard page URL based on user role.
*/

if (!function_exists("ssasDashboardUrlForRole")) {
    function ssasDashboardUrlForRole($role) {

        if ($role === "Administrator") {
            return "../../client/admin/adminDashboard.php";
        }

        if ($role === "Supervisor") {
            return "../../client/supervisor/supervisorDashboard.php";
        }

        if ($role === "Student") {
            return "../../client/student/studentDashboard.php";
        }

        return "../../client/auth/login.html";
    }
}

/*
|--------------------------------------------------------------------------
| Portal Sidebar Generator
|--------------------------------------------------------------------------
| Dynamically generates sidebar navigation based on user role.
*/

if (!function_exists("ssasPortalSidebar")) {
    function ssasPortalSidebar($activePage) {

        $role = $_SESSION["systemRole"] ?? "User";
        $roleTitle = "SSAS User";
        $roleSubtitle = "User Portal";
        $links = [];

        /*
        |--------------------------------------------------------------------------
        | Supervisor Navigation
        |--------------------------------------------------------------------------
        */

        if ($role === "Supervisor") {
            $roleTitle = "SSAS Supervisor";
            $roleSubtitle = "Supervisor Portal";
            $links = [
                // format: [ID, Label, URL, Chevron, isParent, isSubnav]
                ["dashboard", "Dashboard", "../../client/supervisor/supervisorDashboard.php", "", false],
                
                ["profile-management", "Profile Management", "#", "v", true],
                ["business-card", "Digital Business Card", "../../client/supervisor/manageDigitalBusinessCard.php", "", false, true],
                ["expertise-tags", "Expertise & Tags", "../../client/supervisor/manageExpertiseTags.php", "", false, true],
                ["intro-video", "Introduction Video", "../../client/supervisor/manageIntroVideo.php", "", false, true],
                ["past-projects", "Past Projects", "../../client/supervisor/managePastProjects.php", "", false, true],
                
                ["requests", "Requests & Decisions", "#", "v", true],
                ["incoming-requests", "Incoming Requests", "../../client/supervisor/supervisorIncomingRequests.php", "", false, true],
                ["decision-action", "Decision Action", "../../client/supervisor/supervisorIncomingRequests.php", "", false, true],
                
                ["supervision", "Supervision", "../../client/supervisor/supervisorMySupervisees.php", "", false],
                ["student-reviews", "Student Reviews", "../../client/supervisor/supervisorStudentReviews.php", "", false],
                
                ["reports", "Reports", "#", "v", true],
                ["report-demographics", "Applicant Demographic Chart", "../../client/supervisor/supervisorApplicantDemographics.php", "", false, true],
                ["report-history", "Supervision History Log", "../../client/supervisor/supervisorHistoryLog.php", "", false, true],
                ["report-utilization", "Slot Utilization Tracker", "../../client/supervisor/supervisorSlotUtilization.php", "", false, true]
            ];

        /*
        |--------------------------------------------------------------------------
        | Administrator Navigation
        |--------------------------------------------------------------------------
        */

        } elseif ($role === "Administrator") {
            $roleTitle = "SSAS Admin";
            $roleSubtitle = "Management Portal";
            $links = [
                // format: [ID, Label, URL, Chevron, isParent, isSubnav]
                ["dashboard", "Dashboard", "../../client/admin/adminDashboard.php", "", false],
                ["supervisors", "Supervisors Management", "../../client/admin/supervisorsManagement.php", "", false],
                ["eligibility", "Students Eligibility", "../../client/admin/studentEligibility.php", "", false],
                ["quota", "Quota Management", "../../client/admin/quotaManagement.php", "", false],
                ["allocations", "Allocations", "../../client/admin/autoAllocation.php", "", false],
                ["reviews", "Supervisor Reviews Audit", "../../client/admin/adminSupervisorReviews.php", "", false],
                
                // Define the Reports dropdown parent
                ["reports", "Reports", "#", "v", true],
                // Add the two child reports
                ["report-cohort", "Cohort Overview", "../../client/admin/adminCohortOverview.php", "", false, true],
                ["report-allocation", "Allocation Summary", "../../client/admin/adminAllocationSummary.php", "", false, true]
            ];

        /*
        |--------------------------------------------------------------------------
        | Student Navigation
        |--------------------------------------------------------------------------
        */

        } elseif ($role === "Student") {
            $roleTitle = "SSAS Student";
            $roleSubtitle = "Student Portal";
            
            // These paths MUST use ../../client/student/ because they are 
            // being rendered from inside the shared/ folder.
            $links = [
                ["dashboard", "Dashboard", "../../client/student/studentDashboard.php", "", false],
                ["discovery", "Supervisor Discovery", "../../client/student/studentDiscovery.php", "", false],
                // Points to the read-only profile:
                ["profile", "Student Profile", "../../client/shared/profile.php", "", false], 
                ["application-status", "Application Status", "../../client/student/studentApplicationStatus.php", "", false],
                ["review", "Supervisor Review", "../../client/student/studentReview.php", "", false],
                ["timeline", "Timeline & Milestones", "../../client/student/studentTimeline.php", "", false]
            ];
        }

        // Store generated navigation HTML
        $navigation = "";
        $subnavOpen = false;

        /*
        |--------------------------------------------------------------------------
        | Generate Navigation Menu
        |--------------------------------------------------------------------------
        */
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
                    $navigation .= "<div class=\"portal-subnav\" style=\"display: none;\">";
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
            $hrefAttribute = $isParent ? " onclick=\"togglePortalSubnav(this)\" style=\"cursor: pointer;\"" : " href=\"" . ssasEscape($href) . "\"";
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

        /*
        |--------------------------------------------------------------------------
        | Return Sidebar HTML
        |--------------------------------------------------------------------------
        */

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

            </aside>
        ";
    }
}

?>
