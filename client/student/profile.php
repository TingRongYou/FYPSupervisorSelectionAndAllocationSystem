<?php

// import another PHP file's code
require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/StudentProfileFacade.php";
require_once __DIR__ . "/../shared/accountLayout.php";

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
    header("Location: ../auth/login.php?status=error&message=Student profile was not found"); // Redirect browser elsewhere and stop the scripts immediately
    exit();
}

$profile = $payload["profile"];
$allTags = $payload["allTags"];
$selectedTagIDs = $payload["selectedTagIDs"];

SessionManager::setProfilePhotoPath($profile["profilePhotoPath"] ?? "");

function researchTagCode($tagName) { // Turns tag name into short code for little badge next to the checkboxes
    $normalisedName = strtolower(preg_replace("/\s+/", " ", trim((string) $tagName)));

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Student Profile", "student"); 
    ?>
</head>
<body>
    <?php echo ssasTopbar("TAR UMT SSAS"); ?>
    <div class="layout">
        <?php echo ssasPortalSidebar("student-profile"); ?>
        <main class="main">
            <div class="profile-shell">
                <?php echo ssasStatusMessage(); ?>
                <section class="page-header student-hero">
                    <div>
                        <p class="eyebrow">Profile Management</p>
                        <h1>Student Profile</h1>
                        <p class="subtitle">Manage your personal information and academic recommendation signals.</p>
                    </div>
                </section>

                <form id="studentProfileForm" class="profile-grid view-mode" action="../../server/application/student/updateStudentProfile.php" method="POST" enctype="multipart/form-data"> <!-- enctype="multipart/form-data" tells browser, this form is allowed to upload files -->
                    <input type="hidden" name="csrf_token" value="<?php echo ssasEscape($_SESSION["csrf_token"]); ?>"> <!-- Security measures to ensure request to save profile actually comes from the page instead of a malicious third-party site -->
                    <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

                    <aside class="side-panel">
                        <section class="avatar-wrap">
                            <!-- The avatar is interactive upload button -->
                            <label class="avatar interactive-photo" for="avatarFile">
                                <div id="avatarPreview" style="width: 100%; height: 100%; display: grid; place-items: center;">
                                    <?php if (!empty($profile["profilePhotoPath"])): ?>
                                        <img src="<?php echo ssasEscape($profile["profilePhotoPath"]); ?>" alt="Profile photo" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?php echo ssasEscape(ssasInitials($profile["fullName"])); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="photo-overlay">
                                    <span class="overlay-title">Upload</span>
                                    <span class="overlay-note">JPG or PNG<br>Max 2.0MB</span>
                                </div>
                                
                                <input id="avatarFile" name="avatarFile" type="file" accept="image/jpeg,image/png" disabled style="display: none;">
                            </label>
                            
                            <p class="identity-name"><?php echo ssasEscape($profile["fullName"]); ?></p>
                            <p class="identity-id"><?php echo ssasEscape($profile["studentID"]); ?></p>
                            
                            <!-- Hidden element ensures existing student.js preview logic doesn't break -->
                            <span id="avatarFileName" style="display: none;"></span>
                        </section>

                        <section class="readonly-block"> <!-- Display academic record (read-only)-->
                            <p class="readonly-title">Academic Record</p>
                            <div class="readonly-field"><span>Programme</span><strong><?php echo ssasEscape($profile["programme"]); ?></strong></div>
                            <div class="readonly-field"><span>Intake Batch</span><strong><?php echo ssasEscape($profile["intakeBatch"]); ?></strong></div>
                            <div class="readonly-field"><span>Current Semester</span><strong><?php echo ssasEscape($profile["currentSem"]); ?></strong></div>
                            <div class="readonly-field"><span>Academic Status</span><strong><?php echo ssasEscape($profile["academicStatus"]); ?></strong></div>
                            <div class="readonly-field"><span>CGPA</span><strong><?php echo ssasEscape(number_format((float) $profile["cgpa"], 4)); ?></strong></div>
                            <div class="readonly-field"><span>Email</span><strong><?php echo ssasEscape($profile["universityEmail"]); ?></strong></div>
                        </section>
                    </aside>

                    <section class="content-panel">
                        <section class="form-section">
                            <div class="section-title">
                                <h2>Personal Details</h2>
                                <span class="counter" id="bioCounter">0 / 500</span>
                            </div>
                            <label for="personalBio">Personal Bio</label>
                            <textarea id="personalBio" name="personalBio" maxlength="500" data-placeholder="Summarise your background, research interest, and project direction." disabled><?php echo ssasEscape($profile["personalBio"]); ?></textarea>
                            <p class="field-note">Visible to supervisors reviewing your profile.</p>

                            <div class="two-col" style="margin-top: 16px;">
                                <div>
                                    <label for="contactNumber">Mobile Number</label>
                                    <input id="contactNumber" name="contactNumber" type="text" maxlength="20" pattern="0[0-9]{2}-[0-9]{3}\s[0-9]{4}" title="Format must be 0xx-xxx xxxx (e.g., 012-345 6789)" value="<?php echo ssasEscape($profile["contactNumber"]); ?>" data-placeholder="e.g., 012-345 6789" disabled>
                                </div>
                                <div>
                                    <label>Eligibility</label> <!-- Read-only field -->
                                    <div class="readonly-field" style="margin: 0;"><strong><?php echo ssasEscape($profile["eligibilityStatus"]) ? "Eligible for FYP" : "Not Eligible"; ?></strong></div>
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
                                                <span class="tag-info-group">
                                                    <span class="tag-code"><?php echo ssasEscape(researchTagCode($tag["tagName"])); ?></span>
                                                    <?php echo ssasEscape($tag["tagName"]); ?>
                                                </span>
                                                
                                                <input type="checkbox" name="interestTags[]" value="<?php echo ssasEscape($tag["tagID"]); ?>" data-name="<?php echo ssasEscape($tag["tagName"]); ?>" <?php echo $checked ? "checked" : ""; ?> disabled>
                                            </span>
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
                                    <input id="linkedInURL" name="linkedInURL" type="url" value="<?php echo ssasEscape($profile["linkedInURL"]); ?>" data-placeholder="https://linkedin.com/in/username" disabled>
                                </div>
                                <div>
                                    <label for="githubURL">GitHub URL</label>
                                    <input id="githubURL" name="githubURL" type="url" value="<?php echo ssasEscape($profile["githubURL"]); ?>" data-placeholder="https://github.com/username" disabled>
                                </div>
                            </div>
                            <div style="margin-top: 16px;">
                                <label for="portfolioURL">Portfolio URL</label>
                                <input id="portfolioURL" name="portfolioURL" type="url" value="<?php echo ssasEscape($profile["portfolioURL"]); ?>" data-placeholder="https://yourportfolio.com" disabled>
                            </div>
                        </section>

                        <div class="form-actions">
                            <!-- Visible by default -->
                            <button class="button" type="button" id="editProfileBtn">Edit Profile</button>
                            
                            <!-- Hidden by default -->
                            <button class="button secondary" type="reset" id="cancelEditBtn" style="display: none;">Cancel</button>
                            <button class="button" type="submit" id="saveProfileBtn" style="display: none;">Save Changes</button>
                        </div>
                    </section>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
