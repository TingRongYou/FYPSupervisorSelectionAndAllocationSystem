<?php

require_once __DIR__ . "/../auth/SessionManager.php";

SessionManager::startSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid request method"
    );

    exit();
}

SessionManager::requireRole(
    "Administrator"
);

if (!SessionManager::validateCsrfToken($_POST["csrf_token"] ?? "")) {

    header(
        "Location: ../../../client/admin/studentEligibility.php?status=error&message=Invalid CSRF token"
    );

    exit();
}

unset(
    $_SESSION["eligibility_csv_uploaded"],
    $_SESSION["eligibility_csv_file_name"]
);

header(
    "Location: ../../../client/admin/studentEligibility.php?status=success&message=CSV file selection has been removed"
);

exit();

?>
