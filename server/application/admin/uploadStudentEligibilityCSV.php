<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/EligibilityService.php";

/*
|--------------------------------------------------------------------------
| Session Bootstrap
|--------------------------------------------------------------------------
| Start session before processing supervisor account updates.
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Guard
|--------------------------------------------------------------------------
| The CSV selector submits by POST only; direct GET access is rejected.
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid request method"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
| Only administrators are allowed to update supervisor accounts.
*/

SessionManager::requireRole(
    "Administrator"
);

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
| Blocks forged CSV imports before uploaded data is processed.
*/

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Upload Presence Check
|--------------------------------------------------------------------------
| Confirms the selected CSV reached PHP without an upload error.
*/

if (
    !isset($_FILES["studentCSV"])
    ||
    $_FILES["studentCSV"]["error"] !== UPLOAD_ERR_OK
) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Please upload a valid CSV file"
    );

    exit();
}

$fileName =
    $_FILES["studentCSV"]["name"];

/*
|--------------------------------------------------------------------------
| File Type Check
|--------------------------------------------------------------------------
| Keeps the upload endpoint limited to CSV eligibility imports.
*/

$extension =
    strtolower(
        pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        )
    );

if ($extension !== "csv") {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Only CSV files are allowed"
    );

    exit();
}

if ((int) $_FILES["studentCSV"]["size"] > 5242880) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=CSV file cannot exceed 5MB"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Eligibility Import
|--------------------------------------------------------------------------
| Imports records only; the eligibility batch is run separately from the main button.
*/

$eligibilityService =
    new EligibilityService();

$result =
    $eligibilityService
    ->importStudentEligibilityCSV(
        $_SESSION["systemRole"],
        $_FILES["studentCSV"]["tmp_name"]
    );

$status =
    $result["success"]
    ? "success"
    : "error";

$message =
    urlencode(
        $result["message"]
    );

$uploadedFile =
    urlencode(
        $fileName
    );

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
| Returns to the eligibility screen while preserving the selected file name for display.
*/

header(
    "Location: ../../../client/admin/studentEligibility.php?status={$status}&message={$message}&uploadedFile={$uploadedFile}"
);

exit();

?>
