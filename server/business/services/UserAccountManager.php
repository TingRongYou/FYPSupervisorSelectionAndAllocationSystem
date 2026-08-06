<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads role factories, administrator entity, and DAO classes required for
| user account creation, supervisor profile registration, and user removal.
*/
require_once __DIR__ . "/../factories/SupervisorFactory.php";
require_once __DIR__ . "/../factories/StudentFactory.php";
require_once __DIR__ . "/../entities/Admin.php";
require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";

/*
|--------------------------------------------------------------------------
| User Account Manager
|--------------------------------------------------------------------------
| Manages user role object creation and coordinates user registration,
| supervisor profile creation, and user removal.
*/
class UserAccountManager {

    /*
    |--------------------------------------------------------------------------
    | DAO Dependencies
    |--------------------------------------------------------------------------
    */
    private $userDAO;

    private $supervisorDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes user and supervisor data access dependencies.
    */
    public function __construct() {

        $this->userDAO = new UserDAO();

        $this->supervisorDAO = new SupervisorDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Create Role
    |--------------------------------------------------------------------------
    | Uses the correct factory or entity class to create a user role object.
    */
    public function createRole($roleType, $data) {

        /*
        |--------------------------------------------------------------------------
        | Supervisor Role Creation
        |--------------------------------------------------------------------------
        */
        if ($roleType === "Supervisor") {

            $factory = new SupervisorFactory();

            return $factory->createRole($data);
        }

        /*
        |--------------------------------------------------------------------------
        | Student Role Creation
        |--------------------------------------------------------------------------
        */
        if ($roleType === "Student") {

            $factory = new StudentFactory();

            return $factory->createRole($data);
        }

        /*
        |--------------------------------------------------------------------------
        | Administrator Role Creation
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Unsupported Role Handling
        |--------------------------------------------------------------------------
        */
        throw new InvalidArgumentException("Unsupported user role");
    }

    /*
    |--------------------------------------------------------------------------
    | Register User
    |--------------------------------------------------------------------------
    | Persists a user account record into the database.
    */
    public function registerUser($user) {

        return $this->userDAO->createUser(
            $user->getUserID(),
            $user->getName(),
            $user->getUniversityEmail(),
            $user->getSystemRole(),
            $user->getPassword()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Register Supervisor Profile
    |--------------------------------------------------------------------------
    | Creates a supervisor-specific profile record after the base user account
    | has been registered.
    */
    public function registerSupervisorProfile($supervisor, $quotaID) {

        return $this->supervisorDAO->createSupervisorProfile(
            $supervisor->getUserID(),
            (int) $quotaID,
            $supervisor->getBaseQuota(),
            $supervisor->getEmploymentCategory(),
            $supervisor->getProgramme()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Remove User
    |--------------------------------------------------------------------------
    | Deletes a user account record by user ID.
    */
    public function removeUser($userID) {

        return $this->userDAO->deleteUserByID($userID);
    }
}

?>
