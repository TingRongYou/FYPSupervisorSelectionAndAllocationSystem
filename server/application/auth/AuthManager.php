<?php

require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once "SessionManager.php";

/*
|--------------------------------------------------------------------------
| AuthManager Class
|--------------------------------------------------------------------------
| Handles user authentication and login session management.
*/
class AuthManager {

    private $userDAO;

    public function __construct() {

        $this->userDAO = new UserDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Login Function
    |--------------------------------------------------------------------------
    | Authenticate user using email and password.
    */

    public function login($email, $password) {

        $user = $this->userDAO->getUserByEmail($email);

        /*
        |--------------------------------------------------------------------------
        | Password Verification
        |--------------------------------------------------------------------------
        | Verify entered password against stored password.
        */
        if ($user&&(password_verify($password, $user["password"]))) { // Verify hashed password

            SessionManager::startSession();

            /*
            |--------------------------------------------------------------------------
            | User Session Storage
            |--------------------------------------------------------------------------
            | Store authenticated user information into session.
            */

            SessionManager::setUserSession($user);

            // Login successful
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Login Failure
        |--------------------------------------------------------------------------
        | Return false if authentication fails.
        */

        return false;
    }
}
?>
