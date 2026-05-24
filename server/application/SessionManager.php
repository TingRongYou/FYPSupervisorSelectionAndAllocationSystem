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

    private const SESSION_TIMEOUT =
        900; // 15 minutes

    /*
    |--------------------------------------------------------------------------
    | Start Secure Session
    |--------------------------------------------------------------------------
    */

    public static function startSession() {

        if (
            session_status()
            ===
            PHP_SESSION_NONE
        ) {

            session_start();
        }

        /*
        |--------------------------------------------------------------------------
        | Automatic Session Timeout
        |--------------------------------------------------------------------------
        */

        self::validateSessionTimeout();
    }

    /*
    |--------------------------------------------------------------------------
    | Store Authenticated User Session
    |--------------------------------------------------------------------------
    */

    public static function setUserSession(
        $user
    ) {

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

        $_SESSION["userID"] =
            $user["userID"];

        $_SESSION["fullName"] =
            $user["fullName"];

        $_SESSION["systemRole"] =
            $user["systemRole"];

        /*
        |--------------------------------------------------------------------------
        | Session Activity Tracking
        |--------------------------------------------------------------------------
        */

        $_SESSION["lastActivity"] =
            time();
    }

    /*
    |--------------------------------------------------------------------------
    | Check Authentication Status
    |--------------------------------------------------------------------------
    */

    public static function isLoggedIn() {

        return isset(
            $_SESSION["userID"]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Require Authentication
    |--------------------------------------------------------------------------
    */

    public static function requireLogin() {

        if (
            !self::isLoggedIn()
        ) {

            header(
                "Location: ../../client/login.html?status=error&message=Please login first"
            );

            exit();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Control
    |--------------------------------------------------------------------------
    */

    public static function requireRole(
        $role
    ) {

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

        if (

            !isset($_SESSION["systemRole"])

            ||

            $_SESSION["systemRole"]
            !==
            $role
        ) {

            header(
                "Location: ../../client/login.html?status=error&message=Access denied"
            );

            exit();
        }
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

        if (
            !isset($_SESSION["userID"])
        ) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Timeout Validation
        |--------------------------------------------------------------------------
        */

        if (
            isset($_SESSION["lastActivity"])
        ) {

            $inactiveDuration =
                time()
                -
                $_SESSION["lastActivity"];

            if (
                $inactiveDuration
                >
                self::SESSION_TIMEOUT
            ) {

                self::destroySession();

                header(
                    "Location: ../../client/login.html?status=error&message=Session expired"
                );

                exit();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Refresh Activity Timestamp
        |--------------------------------------------------------------------------
        */

        $_SESSION["lastActivity"] =
            time();
    }

    /*
    |--------------------------------------------------------------------------
    | Logout User
    |--------------------------------------------------------------------------
    */

    public static function logout() {

        self::destroySession();

        header(
            "Location: ../../client/login.html?status=success&message=Logged out successfully"
        );

        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Session
    |--------------------------------------------------------------------------
    */

    public static function destroySession() {

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

        if (
            ini_get("session.use_cookies")
        ) {

            $parameters =
                session_get_cookie_params();

            setcookie(

                session_name(),

                "",

                time() - 42000,

                $parameters["path"],

                $parameters["domain"],

                $parameters["secure"],

                $parameters["httponly"]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Destroy Session
        |--------------------------------------------------------------------------
        */

        session_destroy();
    }
}

?>