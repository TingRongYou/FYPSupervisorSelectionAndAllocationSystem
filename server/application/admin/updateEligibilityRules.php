<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/EligibilityService.php";

/*
|--------------------------------------------------------------------------
| Session Bootstrap
|--------------------------------------------------------------------------
| Start administrator session before processing eligibility rule updates.
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| Ensure rule updates are only submitted through POST request.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid request method");

    exit();
}
/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators are allowed to edit eligibility rules.
*/

SessionManager::requireRole("Administrator");

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
| Blocks forged eligibility rule changes before configuration is updated.
*/

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {
    header("Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid CSRF token");

    exit();
}

/*
|--------------------------------------------------------------------------
| Eligibility Rule Update
|--------------------------------------------------------------------------
| Update editable eligibility criteria such as CGPA, semester,
| and blocked academic status.
*/

$eligibilityService = new EligibilityService();

$result =
    $eligibilityService
    ->updateEligibilityRules(
        $_SESSION["systemRole"],
        $_POST["minimumCGPA"] ?? "",
        $_POST["requiredNextSemester"] ?? "",
        $_POST["blockedAcademicStatus"] ?? ""
    );

/*
|--------------------------------------------------------------------------
| Result Status Handling
|--------------------------------------------------------------------------
| Convert service response into success or error status.
*/

$status = $result["success"] ? "success" : "error";

$message = urlencode($result["message"]);

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
| Redirect administrator back to eligibility page with result message.
*/

header("Location: ../../../client/admin/studentEligibility.php?status={$status}&message={$message}");

exit();

?>
