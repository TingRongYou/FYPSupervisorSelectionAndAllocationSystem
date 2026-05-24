<?php

require_once "../data/UserDAO.php";
require_once "../data/SupervisorDAO.php";

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

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $userDAO;

    private $supervisorDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->userDAO =
            new UserDAO();

        $this->supervisorDAO =
            new SupervisorDAO();
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

        if (
            !$this->isAdministrator(
                $administratorRole
            )
        ) {

            return $this->failure(
                "Access Denied"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Input
        |--------------------------------------------------------------------------
        */

        $supervisorID =
            trim($supervisorID);

        $fullName =
            trim($fullName);

        $universityEmail =
            trim($universityEmail);

        $programme =
            trim($programme);

        $employmentCategory =
            trim($employmentCategory);

        $quotaID =
            trim($quotaID);

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

            return $this->failure(
                "All supervisor account fields are required"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Supervisor ID Validation
        |--------------------------------------------------------------------------
        */

        if (
            strlen($supervisorID) < 3
            ||
            strlen($supervisorID) > 20
        ) {

            return $this->failure(
                "Supervisor ID must be between 3 and 20 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Full Name Validation
        |--------------------------------------------------------------------------
        */

        if (
            strlen($fullName)
            >
            self::MAX_NAME_LENGTH
        ) {

            return $this->failure(
                "Full name cannot exceed 100 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Email Validation
        |--------------------------------------------------------------------------
        */

        if (
            !filter_var(
                $universityEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            return $this->failure(
                "Invalid email format"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Password Validation
        |--------------------------------------------------------------------------
        */

        if (
            strlen($password)
            <
            self::MIN_PASSWORD_LENGTH
        ) {

            return $this->failure(
                "Password must contain at least 8 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Programme Validation
        |--------------------------------------------------------------------------
        */

        if (
            strlen($programme)
            >
            self::MAX_PROGRAMME_LENGTH
        ) {

            return $this->failure(
                "Programme cannot exceed 100 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Employment Category Validation
        |--------------------------------------------------------------------------
        */

        if (
            strlen($employmentCategory)
            >
            self::MAX_EMPLOYMENT_CATEGORY_LENGTH
        ) {

            return $this->failure(
                "Employment category cannot exceed 50 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Quota Validation
        |--------------------------------------------------------------------------
        */

        if (
            !ctype_digit($quotaID)
        ) {

            return $this->failure(
                "Invalid quota tier"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate User ID Validation
        |--------------------------------------------------------------------------
        */

        try {

            if (
                $this->userDAO
                ->getUserByID(
                    $supervisorID
                )
            ) {

                return $this->failure(
                    "User ID already exists"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate supervisor ID"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate Email Validation
        |--------------------------------------------------------------------------
        */

        try {

            if (
                $this->userDAO
                ->getUserByEmail(
                    $universityEmail
                )
            ) {

                return $this->failure(
                    "Email already exists"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate supervisor email"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Quota Existence Validation
        |--------------------------------------------------------------------------
        */

        try {

            $quota =
                $this->supervisorDAO
                ->getQuotaByID(
                    (int) $quotaID
                );

            if (!$quota) {

                return $this->failure(
                    "Invalid quota tier"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate quota tier"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Password Hashing
        |--------------------------------------------------------------------------
        */

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        /*
        |--------------------------------------------------------------------------
        | Create User Record
        |--------------------------------------------------------------------------
        */

        try {

            $userCreated =
                $this->userDAO
                ->createUser(

                    $supervisorID,

                    $fullName,

                    $universityEmail,

                    "Supervisor",

                    $hashedPassword
                );

            if (!$userCreated) {

                return $this->failure(
                    "Supervisor user record could not be created"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while creating user account"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Supervisor Profile
        |--------------------------------------------------------------------------
        */

        try {

            $profileCreated =
                $this->supervisorDAO
                ->createSupervisorProfile(

                    $supervisorID,

                    (int) $quotaID,

                    $employmentCategory,

                    $programme
                );

            /*
            |--------------------------------------------------------------------------
            | Rollback User Creation
            |--------------------------------------------------------------------------
            */

            if (!$profileCreated) {

                $this->userDAO
                    ->deleteUserByID(
                        $supervisorID
                    );

                return $this->failure(
                    "Supervisor profile record could not be created"
                );
            }

        } catch (Exception $exception) {

            /*
            |--------------------------------------------------------------------------
            | Rollback User Creation
            |--------------------------------------------------------------------------
            */

            $this->userDAO
                ->deleteUserByID(
                    $supervisorID
                );

            return $this->failure(
                "System error occurred while creating supervisor profile"
            );
        }

        return $this->success(
            "Supervisor account created successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Administrator Validation
    |--------------------------------------------------------------------------
    */

    private function isAdministrator(
        $administratorRole
    ) {

        return
            $administratorRole
            ===
            "Administrator";
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

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    private function success(
        $message
    ) {

        return [

            "success" => true,

            "message" => $message
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response
    |--------------------------------------------------------------------------
    */

    private function failure(
        $message
    ) {

        return [

            "success" => false,

            "message" => $message
        ];
    }
}

?>