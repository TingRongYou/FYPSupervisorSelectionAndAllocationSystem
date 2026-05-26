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
        empty($alumniName)
    ) {

        header(
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=All fields are required"
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
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Project title too long"
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
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Alumni name too long"
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
            "Location: ../../../client/supervisor/managePastProjects.php?status=error&message=Invalid completion year"
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

            $alumniName
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

                $alumniName
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

header(
    "Location: ../../../client/supervisor/managePastProjects.php?status={$status}&message={$message}"
);

exit();

?>



