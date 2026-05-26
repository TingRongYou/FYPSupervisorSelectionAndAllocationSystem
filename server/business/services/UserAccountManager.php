<?php

require_once __DIR__ . "/../factories/SupervisorFactory.php";
require_once __DIR__ . "/../factories/StudentFactory.php";
require_once __DIR__ . "/../entities/Admin.php";
require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";

class UserAccountManager {

    private $userDAO;
    private $supervisorDAO;

    public function __construct() {

        $this->userDAO = new UserDAO();
        $this->supervisorDAO = new SupervisorDAO();
    }

    public function createRole($roleType, $data) {

        if ($roleType === "Supervisor") {
            $factory = new SupervisorFactory();
            return $factory->createRole($data);
        }

        if ($roleType === "Student") {
            $factory = new StudentFactory();
            return $factory->createRole($data);
        }

        if ($roleType === "Administrator") {
            return new Admin(
                $data["userID"] ?? "",
                $data["fullName"] ?? "",
                $data["universityEmail"] ?? "",
                $data["password"] ?? "",
                $data["activeStatus"] ?? true,
                $data["programme"] ?? ""
            );
        }

        throw new InvalidArgumentException("Unsupported user role");
    }

    public function registerUser($user) {

        return $this->userDAO->createUser(
            $user->getUserID(),
            $user->getName(),
            $user->getUniversityEmail(),
            $user->getSystemRole(),
            $user->getPassword()
        );
    }

    public function registerSupervisorProfile($supervisor, $quotaID) {

        return $this->supervisorDAO->createSupervisorProfile(
            $supervisor->getUserID(),
            (int) $quotaID,
            $supervisor->getBaseQuota(),
            $supervisor->getEmploymentCategory(),
            $supervisor->getProgramme()
        );
    }

    public function removeUser($userID) {

        return $this->userDAO->deleteUserByID($userID);
    }
}

?>


