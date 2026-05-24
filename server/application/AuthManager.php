<?php

require_once "../data/UserDAO.php";
require_once "SessionManager.php";

class AuthManager {

    private $userDAO;

    public function __construct() {

        $this->userDAO = new UserDAO();
    }

    public function login($email, $password) {

        $user = $this->userDAO->getUserByEmail($email);

        if ($user && $user['password'] === $password) {

            SessionManager::startSession();

            SessionManager::setUserSession($user);

            return true;
        }

        return false;
    }
}
?>