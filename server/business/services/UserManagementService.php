<?php

require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";
require_once __DIR__ . "/UserAccountManager.php";

class UserManagementService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MIN_PASSWORD_LENGTH = 8;

    private const MAX_NAME_LENGTH = 100;

    private const MAX_PROGRAMME_LENGTH = 100;

    private const MAX_EMPLOYMENT_CATEGORY_LENGTH = 50;

    private const CLASSIFICATION_QUOTA_RULES = [
        "Full-Time Lecturer" => "Full-Time",
        "Part-Time Lecturer" => "Part-Time",
        "Dean" => "Administrative",
        "Deputy Dean" => "Administrative",
        "Academic Director" => "Administrative",
        "Programme Leader" => "Administrative"
    ];

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $userDAO;

    private $supervisorDAO;

    private $userAccountManager;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->userDAO = new UserDAO();

        $this->supervisorDAO = new SupervisorDAO();

        $this->userAccountManager = new UserAccountManager();
    }

    /*
    |--------------------------------------------------------------------------
    | Create Supervisor Account
    |--------------------------------------------------------------------------
    */

    public function createSupervisorAccount(
        $administratorRole,
        $supervisorID,
        $fullName,
        $universityEmail,
        $password,
        $programme,
        $employmentCategory,
        $quotaID
    ) {

        /*
        |--------------------------------------------------------------------------
        | RBAC Validation
        |--------------------------------------------------------------------------
        */

        if (!$this->isAdministrator($administratorRole)) {

            return $this->failure("Access Denied");
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Input
        |--------------------------------------------------------------------------
        */

        $supervisorID = trim($supervisorID);

        $fullName = trim($fullName);

        $universityEmail = trim($universityEmail);

        $programme = trim($programme);

        $employmentCategory = trim($employmentCategory);

        $quotaID = trim($quotaID);

        /*
        |--------------------------------------------------------------------------
        | Required Field Validation
        |--------------------------------------------------------------------------
        */

        if (
            !$this->hasRequiredSupervisorFields(

                $supervisorID,

                $fullName,

                $universityEmail,

                $password,

                $programme,

                $employmentCategory,

                $quotaID
            )
        ) {

            return $this->failure("All supervisor account fields are required");
        }

        /*
        |--------------------------------------------------------------------------
        | Supervisor ID Validation
        |--------------------------------------------------------------------------
        */

        if (strlen($supervisorID) < 3 || strlen($supervisorID) > 20) {

            return $this->failure("Supervisor ID must be between 3 and 20 characters");
        }

        /*
        |--------------------------------------------------------------------------
        | Full Name Validation
        |--------------------------------------------------------------------------
        */

        if (strlen($fullName) > self::MAX_NAME_LENGTH) {

            return $this->failure("Full name cannot exceed 100 characters");
        }

        /*
        |--------------------------------------------------------------------------
        | Email Validation
        |--------------------------------------------------------------------------
        */

        if (!filter_var($universityEmail, FILTER_VALIDATE_EMAIL)) {

            return $this->failure("Invalid email format");
        }

        /*
        |--------------------------------------------------------------------------
        | Password Validation
        |--------------------------------------------------------------------------
        */

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {

            return $this->failure( "Password must contain at least 8 characters");
        }

        /*
        |--------------------------------------------------------------------------
        | Programme Validation
        |--------------------------------------------------------------------------
        */

        if (strlen($programme) > self::MAX_PROGRAMME_LENGTH) {

            return $this->failure("Programme cannot exceed 100 characters");
        }

        /*
        |--------------------------------------------------------------------------
        | Employment Category Validation
        |--------------------------------------------------------------------------
        */

        if (strlen($employmentCategory) > self::MAX_EMPLOYMENT_CATEGORY_LENGTH) {

            return $this->failure("Employment category cannot exceed 50 characters");
        }

        /*
        |--------------------------------------------------------------------------
        | Quota Validation
        |--------------------------------------------------------------------------
        */

        if (!ctype_digit($quotaID)) {

            return $this->failure("Invalid quota tier");
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate User ID Validation
        |--------------------------------------------------------------------------
        */

        try {

            if ($this->userDAO->getUserByID($supervisorID)) {

                return $this->failure("User ID already exists");
            }

        } catch (Exception $exception) {

            return $this->failure("Unable to validate supervisor ID");
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate Email Validation
        |--------------------------------------------------------------------------
        */

        try {

            if ($this->userDAO->getUserByEmail($universityEmail)) {

                return $this->failure("Email already exists");
            }

        } catch (Exception $exception) {

            return $this->failure("Unable to validate supervisor email");
        }

        /*
        |--------------------------------------------------------------------------
        | Quota Existence Validation
        |--------------------------------------------------------------------------
        */

        try {

            $quota = $this->supervisorDAO->getQuotaByID((int) $quotaID);

            if (!$quota) {

                return $this->failure("Invalid quota tier");
            }

            if (!$this->quotaMatchesClassification($employmentCategory, $quota)) {

                return $this->failure("Selected quota tier does not match the supervisor classification");
            }

        } catch (Exception $exception) {

            return $this->failure("Unable to validate quota tier");
        }

        $supervisor =
            $this->userAccountManager
            ->createRole(
                "Supervisor",
                [
                    "userID" => $supervisorID,
                    "fullName" => $fullName,
                    "universityEmail" => $universityEmail,
                    "password" => $password,
                    "programme" => $programme,
                    "employmentCategory" => $employmentCategory,
                    "baseQuota" => (int) $quota["maxSuperviseesAllowed"]
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Create User Record
        |--------------------------------------------------------------------------
        */

        try {

            $userCreated = $this->userAccountManager->registerUser($supervisor);

            if (!$userCreated) {

                return $this->failure("Supervisor user record could not be created");
            }

        } catch (Exception $exception) {

            return $this->failure("System error occurred while creating user account");
        }

        /*
        |--------------------------------------------------------------------------
        | Create Supervisor Profile
        |--------------------------------------------------------------------------
        */

        try {

            $profileCreated = $this->userAccountManager->registerSupervisorProfile($supervisor, (int) $quotaID);

            /*
            |--------------------------------------------------------------------------
            | Rollback User Creation
            |--------------------------------------------------------------------------
            */

            if (!$profileCreated) {

                $this->userAccountManager->removeUser($supervisorID);

                return $this->failure("Supervisor profile record could not be created");
            }

        } catch (Exception $exception) {

            /*
            |--------------------------------------------------------------------------
            | Rollback User Creation
            |--------------------------------------------------------------------------
            */

            $this->userAccountManager->removeUser($supervisorID);

            return $this->failure("System error occurred while creating supervisor profile");
        }

        return $this->success("Supervisor account created successfully");
    }

    /*
    |--------------------------------------------------------------------------
    | Administrator Validation
    |--------------------------------------------------------------------------
    */

    private function isAdministrator($administratorRole) {

        return $administratorRole === "Administrator";
    }

    /*
    |--------------------------------------------------------------------------
    | Required Field Validation
    |--------------------------------------------------------------------------
    */

    private function hasRequiredSupervisorFields(

        $supervisorID,

        $fullName,

        $universityEmail,

        $password,

        $programme,

        $employmentCategory,

        $quotaID
    ) {

        return

            !empty($supervisorID)

            &&

            !empty($fullName)

            &&

            !empty($universityEmail)

            &&

            !empty($password)

            &&

            !empty($programme)

            &&

            !empty($employmentCategory)

            &&

            !empty($quotaID);
    }

    private function quotaMatchesClassification($employmentCategory, $quota) {

        if (!isset(self::CLASSIFICATION_QUOTA_RULES[$employmentCategory])) {

            return false;
        }

        return stripos($quota["quotaTierName"], self::CLASSIFICATION_QUOTA_RULES[$employmentCategory]) !== false;
    }

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    private function success($message) {

        return ["success" => true, "message" => $message];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response
    |--------------------------------------------------------------------------
    */

    private function failure($message) {

        return ["success" => false, "message" => $message];
    }
}

?>
