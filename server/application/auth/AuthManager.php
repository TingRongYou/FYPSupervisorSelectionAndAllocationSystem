<?php

require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once "SessionManager.php";

class AuthManager {

    private $userDAO;

    public function __construct() {

        $this->userDAO = new UserDAO();
    }

    public function login($email, $password) {

        $user = $this->userDAO->getUserByEmail($email);

        if (
            $user
            &&
            (
                password_verify($password, $user["password"])
                ||
                hash_equals($user["password"], $password)
            )
        ) {

            SessionManager::startSession();

            SessionManager::setUserSession($user);

            return true;
        }

        return false;
    }
}
?>





