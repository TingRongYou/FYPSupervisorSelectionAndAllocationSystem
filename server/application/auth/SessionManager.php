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

        $_SESSION["profilePhotoPath"] =
            $user["profilePhotoPath"] ?? "";

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
    | Update Session Profile Photo
    |--------------------------------------------------------------------------
    */

    public static function setProfilePhotoPath(
        $profilePhotoPath
    ) {

        $_SESSION["profilePhotoPath"] =
            $profilePhotoPath ?? "";
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

            self::redirectToLogin(
                "error",
                "Please login first"
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

            self::redirectToLogin(
                "error",
                "Access denied"
            );

            exit();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Build Login Redirect
    |--------------------------------------------------------------------------
    */

    private static function redirectToLogin(
        $status,
        $message
    ) {

        $scriptName =
            $_SERVER["SCRIPT_NAME"] ?? "";

        if (
            strpos(
                $scriptName,
                "/client/"
            )
            !==
            false
        ) {

            $basePath =
                substr(
                    $scriptName,
                    0,
                    strpos(
                        $scriptName,
                        "/client/"
                    )
                );
        } elseif (
            strpos(
                $scriptName,
                "/server/"
            )
            !==
            false
        ) {

            $basePath =
                substr(
                    $scriptName,
                    0,
                    strpos(
                        $scriptName,
                        "/server/"
                    )
                );
        } else {

            $basePath =
                "";
        }

        header(
            "Location: "
            .
            $basePath
            .
            "/client/auth/login.html?status="
            .
            urlencode($status)
            .
            "&message="
            .
            urlencode($message)
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

                self::redirectToLogin(
                    "error",
                    "Session expired"
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

        self::redirectToLogin(
            "success",
            "Logged out successfully"
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




