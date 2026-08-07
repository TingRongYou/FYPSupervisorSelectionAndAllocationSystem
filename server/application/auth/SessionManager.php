<?php

/*
|--------------------------------------------------------------------------
| Session Manager
|--------------------------------------------------------------------------
| Handles:
| - Session initialization
| - Login session storage
| - Authentication checking
| - Role-Based Access Control (RBAC)
| - Logout handling
|--------------------------------------------------------------------------
*/

class SessionManager {

    /*
    |--------------------------------------------------------------------------
    | Session Timeout Configuration
    |--------------------------------------------------------------------------
    */

    private const SESSION_TIMEOUT = 900; // 15 minutes
    private const ONLINE_THRESHOLD = 900; // 15 minutes

    /*
    |--------------------------------------------------------------------------
    | Start Secure Session
    |--------------------------------------------------------------------------
    */

    public static function startSession() {
        if (session_status()===PHP_SESSION_NONE) {
            session_start();
        }

        /*
        |--------------------------------------------------------------------------
        | Automatic Session Timeout
        |--------------------------------------------------------------------------
        */

        self::validateSessionTimeout();

        self::touchUserActivity();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Authenticated User Session
    |--------------------------------------------------------------------------
    */

    public static function setUserSession($user) {

        /*
        |--------------------------------------------------------------------------
        | Regenerate Session ID
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);

        /*
        |--------------------------------------------------------------------------
        | Store User Session Data
        |--------------------------------------------------------------------------
        */

        $_SESSION["userID"] = $user["userID"];
        $_SESSION["fullName"] = $user["fullName"];
        $_SESSION["systemRole"] = $user["systemRole"];
        $_SESSION["profilePhotoPath"] = $user["profilePhotoPath"] ?? "";

        /*
        |--------------------------------------------------------------------------
        | Session Activity Tracking
        |--------------------------------------------------------------------------
        */

        $_SESSION["lastActivity"] = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Session Profile Photo
    |--------------------------------------------------------------------------
    */

    public static function setProfilePhotoPath($profilePhotoPath) {
        $_SESSION["profilePhotoPath"] = $profilePhotoPath ?? "";
    }

    /*
    |--------------------------------------------------------------------------
    | Check Authentication Status
    |--------------------------------------------------------------------------
    */

    public static function isLoggedIn() {
        return isset($_SESSION["userID"]);
    }

    /*
    |--------------------------------------------------------------------------
    | Require Authentication
    |--------------------------------------------------------------------------
    */

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            self::redirectToLogin("error", "Please login first");

            exit();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Control
    |--------------------------------------------------------------------------
    */

    public static function requireRole( $role) {
        /*
        |--------------------------------------------------------------------------
        | Authentication Validation
        |--------------------------------------------------------------------------
        */

        self::requireLogin();

        /*
        |--------------------------------------------------------------------------
        | Role Validation
        |--------------------------------------------------------------------------
        */

        if (!isset($_SESSION["systemRole"])||$_SESSION["systemRole"]!==$role) {
            self::redirectToLogin("error", "Access denied");

            exit();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CSRF Token
    |--------------------------------------------------------------------------
    | Provides a per-session token for administrator POST actions.
    */

    public static function getCsrfToken() {
        if (empty($_SESSION["csrf_token"])) {

            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }

        return $_SESSION["csrf_token"];
    }

    /*
    |--------------------------------------------------------------------------
    | CSRF Validation
    |--------------------------------------------------------------------------
    | Validates posted tokens before state-changing requests continue.
    */

    public static function validateCsrfToken($token) {
        return
            isset($_SESSION["csrf_token"])
            &&
            is_string($token)
            &&
            hash_equals($_SESSION["csrf_token"],$token);
    }

    /*
    |--------------------------------------------------------------------------
    | Build Login Redirect
    |--------------------------------------------------------------------------
    */

    private static function redirectToLogin($status,$message) {
        $scriptName = $_SERVER["SCRIPT_NAME"] ?? "";

        if (strpos($scriptName,"/client/")!==false) {
            $basePath = substr($scriptName,0,strpos($scriptName,"/client/"));
        } elseif (strpos($scriptName,"/server/")!== false) {
            $basePath = substr($scriptName,0,strpos($scriptName,"/server/"));
        } else {
            $basePath ="";
        }

        header(
            "Location: "
            .
            $basePath
            .
            "/client/auth/login.php?status="
            .
            urlencode($status)
            .
            "&message="
            .
            urlencode($message)
            .
            "&v="
            .
            time()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Session Timeout
    |--------------------------------------------------------------------------
    */

    private static function validateSessionTimeout() {
        /*
        |--------------------------------------------------------------------------
        | Skip if User Not Logged In
        |--------------------------------------------------------------------------
        */
        if (!isset($_SESSION["userID"])) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Timeout Validation
        |--------------------------------------------------------------------------
        */

        if (isset($_SESSION["lastActivity"])) {
            $inactiveDuration = time() - $_SESSION["lastActivity"];

            if ($inactiveDuration > self::SESSION_TIMEOUT ) {

                self::destroySession();

                self::redirectToLogin("error", "Session expired");

                exit();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Refresh Activity Timestamp
        |--------------------------------------------------------------------------
        */

        $_SESSION["lastActivity"] = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Logout User
    |--------------------------------------------------------------------------
    */

    public static function logout() {
        self::destroySession();

        self::redirectToLogin("success", "Logged out successfully");

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Session
    |--------------------------------------------------------------------------
    */

    public static function destroySession() {
        self::markUserOffline();

        /*
        |--------------------------------------------------------------------------
        | Clear Session Variables
        |--------------------------------------------------------------------------
        */

        $_SESSION = [];

        /*
        |--------------------------------------------------------------------------
        | Destroy Cookie
        |--------------------------------------------------------------------------
        */

        if (ini_get("session.use_cookies")) {
            $parameters = session_get_cookie_params();

            setcookie(session_name(), "", time() - 42000, $parameters["path"], $parameters["domain"], $parameters["secure"], $parameters["httponly"]);
        }

        /*
        |--------------------------------------------------------------------------
        | Destroy Session
        |--------------------------------------------------------------------------
        */

        session_destroy();
    }

    private static function touchUserActivity() {
        if (empty($_SESSION["userID"]) || empty($_SESSION["systemRole"])) {
            return;
        }

        require_once __DIR__ . "/../../data/database/database.php";

        try {

            $database = new Database();

            $conn = $database->connect();

            self::ensureUserActivityTable($conn);

            $query = "
                INSERT INTO USER_ACTIVITY_STATUS
                (
                    userID,
                    systemRole,
                    lastSeenAt,
                    isOnline
                )
                VALUES
                (
                    :userID,
                    :systemRole,
                    NOW(),
                    TRUE
                )
                ON DUPLICATE KEY UPDATE
                    systemRole = VALUES(systemRole),
                    lastSeenAt = NOW(),
                    isOnline = TRUE
            ";

            $statement = $conn->prepare($query);

            $statement->execute([
                ":userID" =>
                    $_SESSION["userID"],

                ":systemRole" =>
                    $_SESSION["systemRole"]
            ]);

        } catch (Exception $exception) {
            return;
        }
    }

    private static function markUserOffline() {
        if (empty($_SESSION["userID"])) {
            return;
        }

        require_once __DIR__ . "/../../data/database/database.php";

        try {

            $database = new Database();

            $conn = $database->connect();

            self::ensureUserActivityTable($conn);

            $query = "
                INSERT INTO USER_ACTIVITY_STATUS
                (
                    userID,
                    systemRole,
                    lastSeenAt,
                    isOnline
                )
                VALUES
                (
                    :userID,
                    :systemRole,
                    NOW(),
                    FALSE
                )
                ON DUPLICATE KEY UPDATE
                    lastSeenAt = NOW(),
                    isOnline = FALSE
            ";

            $statement = $conn->prepare($query);

            $statement->execute([
                ":userID" =>
                    $_SESSION["userID"],

                ":systemRole" =>
                    $_SESSION["systemRole"] ?? "User"
            ]);

        } catch (Exception $exception) {
            return;
        }
    }

    private static function ensureUserActivityTable($conn) {

        $query = "
            CREATE TABLE IF NOT EXISTS USER_ACTIVITY_STATUS (
                userID VARCHAR(20) PRIMARY KEY,
                systemRole VARCHAR(50) NOT NULL,
                lastSeenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                isOnline BOOLEAN NOT NULL DEFAULT FALSE,

                FOREIGN KEY (userID)
                    REFERENCES USER(userID)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            )
        ";

        $conn->exec($query);
    }
}

?>
