<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/PastProjectService.php";

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Validation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Invalid request method"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!SessionManager::isLoggedIn()) {

    header("Location: ../../../client/auth/login.html");
    exit();
}

/*
|--------------------------------------------------------------------------
| RBAC
|--------------------------------------------------------------------------
*/

SessionManager::requireRole("Supervisor");

/*
|--------------------------------------------------------------------------
| CSRF Validation
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {

    header(
        "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Service Layer
|--------------------------------------------------------------------------
*/

$pastProjectService = new PastProjectService();

/*
|--------------------------------------------------------------------------
| Sanitize Inputs
|--------------------------------------------------------------------------
*/

$action = trim($_POST["action"] ?? "");

$projectID =
    trim($_POST["projectID"] ?? "");

$projectTitle =
    trim($_POST["projectTitle"] ?? "");

$completionYear =
    trim($_POST["completionYear"] ?? "");

$alumniName =
    trim($_POST["alumniName"] ?? "");

$projectDescription =
    trim($_POST["projectDescription"] ?? "");

$projectPDF =
    $_FILES["projectPDF"] ?? null;

$projectImage =
    $_FILES["projectImage"] ?? null;

$removeProjectPDF =
    isset($_POST["removeProjectPDF"]) &&
    $_POST["removeProjectPDF"] === "1";

$removeProjectImage =
    isset($_POST["removeProjectImage"]) &&
    $_POST["removeProjectImage"] === "1";

/*
|--------------------------------------------------------------------------
| Backend Validation
|--------------------------------------------------------------------------
*/

$currentYear = ((int) date("Y")) + 1;

if (
    in_array($action, ["add", "update"])
) {

    /*
    |--------------------------------------------------------------------------
    | Empty Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($projectTitle) ||
        empty($completionYear) ||
        empty($alumniName) ||
        empty($projectDescription)
    ) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Validation%20Error%20-%20Please%20enter%20all%20the%20required%20information."
        );

        exit();
    }

    if (
        $action === "add" &&
        (
            !is_array($projectPDF) ||
            !isset($projectPDF["error"]) ||
            (int) $projectPDF["error"] === UPLOAD_ERR_NO_FILE ||
            !is_array($projectImage) ||
            !isset($projectImage["error"]) ||
            (int) $projectImage["error"] === UPLOAD_ERR_NO_FILE
        )
    ) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Validation%20Error%20-%20Please%20upload%20both%20the%20past%20project%20PDF%20and%20cover%20image.&addProject=1"
        );

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | Title Length
    |--------------------------------------------------------------------------
    */

    if (strlen($projectTitle) > 255) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Validation%20Error%20-%20Please%20enter%20all%20the%20required%20information."
        );

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | Alumni Length
    |--------------------------------------------------------------------------
    */

    if (strlen($alumniName) > 100) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Validation%20Error%20-%20Please%20enter%20all%20the%20required%20information."
        );

        exit();
    }

    if (strlen($projectDescription) > 1000) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Validation%20Error%20-%20Project%20description%20cannot%20exceed%201000%20characters."
        );

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | Completion Year Validation
    |--------------------------------------------------------------------------
    */

    if (
        !is_numeric($completionYear) ||
        (int) $completionYear < 2000 ||
        (int) $completionYear > $currentYear
    ) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Validation%20Error%20-%20Please%20enter%20all%20the%20required%20information."
        );

        exit();
    }
}

/*
|--------------------------------------------------------------------------
| Action Routing
|--------------------------------------------------------------------------
*/

if ($action === "add") {

    $result =
        $pastProjectService->addProject(

            $_SESSION["userID"],

            $projectTitle,

            (int) $completionYear,

            $alumniName,

            $projectDescription,

            $projectPDF,

            $projectImage
        );

} elseif ($action === "update") {

    if (empty($projectID)) {

        $result = [
            "success" => false,
            "message" => "Project ID is required"
        ];

    } else {

        $result =
            $pastProjectService->updateProject(

                $projectID,

                $_SESSION["userID"],

                $projectTitle,

                (int) $completionYear,

                $alumniName,

                $projectDescription,

                $projectPDF,

                $removeProjectPDF,

                $projectImage,

                $removeProjectImage
            );
    }

} elseif ($action === "delete") {

    if (empty($projectID)) {

        $result = [
            "success" => false,
            "message" => "Project ID is required"
        ];

    } else {

        $result =
            $pastProjectService->deleteProject(

                $projectID,

                $_SESSION["userID"]
            );
    }

} else {

    $result = [
        "success" => false,
        "message" => "Invalid project action"
    ];
}

/*
|--------------------------------------------------------------------------
| Redirect Result
|--------------------------------------------------------------------------
*/

$status =
    $result["success"]
    ? "success"
    : "error";

$message =
    urlencode($result["message"]);

$returnQuery =
    "";

if (!$result["success"]) {

    if ($action === "update" && !empty($projectID)) {

        $returnQuery =
            "&editProjectID=" . urlencode($projectID);

    } elseif ($action === "add") {

        $returnQuery =
            "&addProject=1";
    }
}

header(
    "Location: ../../../client/supervisor/managePastProjects.php?status={$status}&message={$message}{$returnQuery}"
);

exit();

?>
