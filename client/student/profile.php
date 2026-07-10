<?php

// import another PHP file's code
require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/StudentProfileFacade.php";
require_once __DIR__ . "/studentLayout.php";

// Only allows access to students
SessionManager::startSession(); // auth/sessionManager.php
SessionManager::requireRole("Student"); // :: is a Scope Resolution Operator, used to access static properties, methods or constants of a class without needing to create an object instance

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32)); // Generate random CSRF token so server can verify form submission
}

// Creates an object and calls a method on it to fetch this student's profile data from database
$profileFacade = new StudentProfileFacade();
$payload = $profileFacade->getProfilePayload($_SESSION["userID"]);

if (!$payload) {

    header("Location: ../auth/login.html?status=error&message=Student profile was not found"); // Redirect browser elsewhere and stop the scripts immediately
    exit();
}

$profile = $payload["profile"];
$allTags = $payload["allTags"];
$selectedTagIDs = $payload["selectedTagIDs"];

SessionManager::setProfilePhotoPath(
    $profile["profilePhotoPath"] ?? ""
);

// Helper functions
function e($value) { // Escape text before printing into HTMl, preventing XSS attacks

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function initials($name) { // Turns name into abbreviation for profile pic

    $parts = preg_split("/\s+/", trim((string) $name));
    $first = strtoupper(substr($parts[0] ?? "S", 0, 1));
    $second = strtoupper(substr($parts[1] ?? "", 0, 1));

    return $first . $second;
}
 
function researchTagCode($tagName) { // Turns tag name into short code for little badge next to the checkboxes

    $normalisedName = strtolower(
        preg_replace("/\s+/", " ", trim((string) $tagName))
    );

    $tagCodes = [
        "artificial intelligence" => "AI",
        "software engineering" => "SE",
        "cybersecurity" => "CY",
        "cyber security" => "CY",
    ];

    if (isset($tagCodes[$normalisedName])) {

        return $tagCodes[$normalisedName];
    }

    $words = preg_split("/[\s\-\/]+/", trim((string) $tagName));
    $code = "";

    foreach ($words as $word) {

        if ($word === "") {

            continue;
        }

        $code .= strtoupper(substr($word, 0, 1));

        if (strlen($code) >= 2) {

            break;
        }
    }

    return $code !== "" ? $code : strtoupper(substr((string) $tagName, 0, 2));
}

function statusMessage() { // Build a successe or error HTML banner

    if (!isset($_GET["status"], $_GET["message"])) {

        return "";
    }

    $class = $_GET["status"] === "success" ? "success" : "error";

    return "<div class=\"message {$class}\">" . e($_GET["message"]) . "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | SSAS</title>
    <link rel="stylesheet" href="../assets/css/shared.css"> <!-- Pull css and js file -->
    <link rel="stylesheet" href="../assets/css/student.css">
    <script src="../assets/js/student.js" defer></script>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo studentSidebar("profile"); ?>
        <main class="main">
            <div class="profile-shell">
                <?php echo statusMessage(); ?>
                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Profile Management</p>
                        <h1>Student Profile</h1>
                        <p class="subtitle">Manage your personal information and academic recommendation signals.</p>
                    </div>
                </section>

                <form id="studentProfileForm" class="profile-grid" action="../../server/application/student/updateStudentProfile.php" method="POST" enctype="multipart/form-data"> <!-- enctype="multipart/form-data" tells browser, this form is allowed to upload files -->
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>"> <!-- Security measures to ensure request to save profile actually comes from the page instead of a malicious third-party site -->
                    <input type="hidden" name="MAX_FILE_SIZE" value="524288">

                    <aside class="side-panel">
                        <section class="avatar-wrap">
                            <div class="avatar" id="avatarPreview">
                                <?php if (!empty($profile["profilePhotoPath"])): ?> <!-- If have profile photo, display it-->
                                    <img src="<?php echo e($profile["profilePhotoPath"]); ?>" alt="Profile photo">
                                <?php else: ?>
                                    <?php echo e(initials($profile["fullName"])); ?> <!-- Else, display the abbreviated name -->
                                <?php endif; ?>
                            </div>
                            <p class="identity-name"><?php echo e($profile["fullName"]); ?></p> <!-- Display full name -->
                            <p class="identity-id"><?php echo e($profile["studentID"]); ?></p> <!-- Display student ID -->
                            <label class="avatar-upload" for="avatarFile">
                                Choose Avatar
                                <input id="avatarFile" name="avatarFile" type="file" accept="image/jpeg,image/png">
                            </label>
                            <span class="file-name" id="avatarFileName">JPG or PNG, max 0.5MB.</span>
                        </section>

                        <section class="readonly-block"> <!-- Display academic record (read-only)-->
                            <p class="readonly-title">Academic Record</p>
                            <div class="readonly-field"><span>Programme</span><strong><?php echo e($profile["programme"]); ?></strong></div>
                            <div class="readonly-field"><span>Intake Batch</span><strong><?php echo e($profile["intakeBatch"]); ?></strong></div>
                            <div class="readonly-field"><span>Current Semester</span><strong><?php echo e($profile["currentSem"]); ?></strong></div>
                            <div class="readonly-field"><span>Academic Status</span><strong><?php echo e($profile["academicStatus"]); ?></strong></div>
                            <div class="readonly-field"><span>CGPA</span><strong><?php echo e(number_format((float) $profile["cgpa"], 4)); ?></strong></div>
                            <div class="readonly-field"><span>Email</span><strong><?php echo e($profile["universityEmail"]); ?></strong></div>
                        </section>
                    </aside>

                    <section class="content-panel">
                        <section class="form-section">
                            <div class="section-title">
                                <h2>Personal Details</h2>
                                <span class="counter" id="bioCounter">0 / 500</span>
                            </div>
                            <label for="personalBio">Personal Bio</label>
                            <textarea id="personalBio" name="personalBio" maxlength="500" placeholder="Summarise your background, research interest, and project direction."><?php echo e($profile["personalBio"]); ?></textarea>
                            <p class="field-note">Visible to supervisors reviewing your profile.</p>

                            <div class="two-col" style="margin-top: 16px;">
                                <div>
                                    <label for="contactNumber">Mobile Number</label>
                                    <input id="contactNumber" name="contactNumber" type="text" maxlength="20" value="<?php echo e($profile["contactNumber"]); ?>" placeholder="e.g., 0123456789">
                                </div>
                                <div>
                                    <label>Eligibility</label> <!-- Read-only field -->
                                    <div class="readonly-field" style="margin: 0;"><strong><?php echo $profile["eligibilityStatus"] ? "Eligible for FYP" : "Not Eligible"; ?></strong></div>
                                </div>
                            </div>
                        </section>

                        <section class="form-section">
                            <div class="section-title">
                                <h2>Selected Interests</h2>
                                <span class="counter" id="tagCounter"><?php echo count($selectedTagIDs); ?> / 5 active</span>
                            </div>
                            <div class="tag-box">
                                <div class="selected-tags" id="selectedTags"></div>
                                <div class="tag-grid">
                                    <?php foreach ($allTags as $tag): ?> <!-- Display all of the tags -->
                                        <?php $checked = in_array((int) $tag["tagID"], $selectedTagIDs, true); ?>
                                        <label class="tag-option <?php echo $checked ? "selected" : ""; ?>">
                                            <span class="tag-name">
                                                <span class="tag-code"><?php echo e(researchTagCode($tag["tagName"])); ?></span>
                                                <?php echo e($tag["tagName"]); ?>
                                            </span>
                                            <input type="checkbox" name="interestTags[]" value="<?php echo e($tag["tagID"]); ?>" data-name="<?php echo e($tag["tagName"]); ?>" <?php echo $checked ? "checked" : ""; ?>>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="field-note">Select one to five interests. These tags feed the recommendation engine.</p>
                        </section>

                        <section class="form-section">
                            <div class="section-title">
                                <h2>Portfolio Links</h2>
                            </div>
                            <div class="two-col">
                                <div>
                                    <label for="linkedInURL">LinkedIn URL</label>
                                    <input id="linkedInURL" name="linkedInURL" type="url" value="<?php echo e($profile["linkedInURL"]); ?>" placeholder="https://linkedin.com/in/username">
                                </div>
                                <div>
                                    <label for="githubURL">GitHub URL</label>
                                    <input id="githubURL" name="githubURL" type="url" value="<?php echo e($profile["githubURL"]); ?>" placeholder="https://github.com/username">
                                </div>
                            </div>
                            <div style="margin-top: 16px;">
                                <label for="portfolioURL">Portfolio URL</label>
                                <input id="portfolioURL" name="portfolioURL" type="url" value="<?php echo e($profile["portfolioURL"]); ?>" placeholder="https://yourportfolio.com">
                            </div>
                        </section>

                        <div class="form-actions">
                            <button class="button secondary" type="reset">Cancel</button>
                            <button class="button" type="submit">Save Changes</button>
                        </div>
                    </section>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
