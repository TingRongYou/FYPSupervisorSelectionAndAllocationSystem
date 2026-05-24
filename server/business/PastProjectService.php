<?php

require_once __DIR__ . "/../data/PastProjectDAO.php";

class PastProjectService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MIN_COMPLETION_YEAR = 2000;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $pastProjectDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->pastProjectDAO =
            new PastProjectDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Projects
    |--------------------------------------------------------------------------
    */

    public function getProjectsBySupervisor(
        $supervisorID
    ) {

        return $this->pastProjectDAO
            ->getProjectsBySupervisor(
                $supervisorID
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Single Project
    |--------------------------------------------------------------------------
    */

    public function getProjectByID(
        $projectID,
        $supervisorID
    ) {

        if (
            !$this->isPositiveInteger(
                $projectID
            )
        ) {

            return null;
        }

        return $this->pastProjectDAO
            ->getProjectByID(
                (int) $projectID,
                $supervisorID
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Project
    |--------------------------------------------------------------------------
    */

    public function addProject(
        $supervisorID,
        $projectTitle,
        $completionYear,
        $alumniName
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate Inputs
        |--------------------------------------------------------------------------
        */

        $validation =
            $this->validateProjectInput(
                $projectTitle,
                $completionYear,
                $alumniName
            );

        if (!$validation["success"]) {

            return $validation;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $projectTitle =
            trim($projectTitle);

        $completionYear =
            (int) $completionYear;

        $alumniName =
            trim($alumniName);

        /*
        |--------------------------------------------------------------------------
        | Duplicate Protection
        |--------------------------------------------------------------------------
        */

        try {

            $exists =
                $this->pastProjectDAO
                ->projectExists(

                    $supervisorID,

                    $projectTitle,

                    $completionYear
                );

            if ($exists) {

                return $this->failure(
                    "This past project already exists"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate project duplication"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */

        try {

            $created =
                $this->pastProjectDAO
                ->addProject(

                    $supervisorID,

                    $projectTitle,

                    $completionYear,

                    $alumniName
                );

            if (!$created) {

                return $this->failure(
                    "Past project could not be added"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while adding project"
            );
        }

        return $this->success(
            "Past project added successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Project
    |--------------------------------------------------------------------------
    */

    public function updateProject(
        $projectID,
        $supervisorID,
        $projectTitle,
        $completionYear,
        $alumniName
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate Project ID
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isPositiveInteger(
                $projectID
            )
        ) {

            return $this->failure(
                "Invalid project selected"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Validation
        |--------------------------------------------------------------------------
        */

        try {

            $project =
                $this->pastProjectDAO
                ->getProjectByID(
                    (int) $projectID,
                    $supervisorID
                );

            if (!$project) {

                return $this->failure(
                    "Past project was not found"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate project ownership"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Inputs
        |--------------------------------------------------------------------------
        */

        $validation =
            $this->validateProjectInput(
                $projectTitle,
                $completionYear,
                $alumniName
            );

        if (!$validation["success"]) {

            return $validation;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $projectTitle =
            trim($projectTitle);

        $completionYear =
            (int) $completionYear;

        $alumniName =
            trim($alumniName);

        /*
        |--------------------------------------------------------------------------
        | Duplicate Protection
        |--------------------------------------------------------------------------
        */

        try {

            $exists =
                $this->pastProjectDAO
                ->projectExistsForOtherProject(

                    (int) $projectID,

                    $supervisorID,

                    $projectTitle,

                    $completionYear
                );

            if ($exists) {

                return $this->failure(
                    "Another project with the same title and year already exists"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate duplicate project"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        try {

            $updated =
                $this->pastProjectDAO
                ->updateProject(

                    (int) $projectID,

                    $supervisorID,

                    $projectTitle,

                    $completionYear,

                    $alumniName
                );

            if (!$updated) {

                return $this->failure(
                    "Past project could not be updated"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while updating project"
            );
        }

        return $this->success(
            "Past project updated successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Project
    |--------------------------------------------------------------------------
    */

    public function deleteProject(
        $projectID,
        $supervisorID
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate Project ID
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isPositiveInteger(
                $projectID
            )
        ) {

            return $this->failure(
                "Invalid project selected"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ownership Validation
        |--------------------------------------------------------------------------
        */

        try {

            $project =
                $this->pastProjectDAO
                ->getProjectByID(
                    (int) $projectID,
                    $supervisorID
                );

            if (!$project) {

                return $this->failure(
                    "Past project was not found"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate project ownership"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        try {

            $deleted =
                $this->pastProjectDAO
                ->deleteProject(
                    (int) $projectID,
                    $supervisorID
                );

            if (!$deleted) {

                return $this->failure(
                    "Past project could not be deleted"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while deleting project"
            );
        }

        return $this->success(
            "Past project deleted successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Inputs
    |--------------------------------------------------------------------------
    */

    private function validateProjectInput(
        $projectTitle,
        $completionYear,
        $alumniName
    ) {

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $projectTitle =
            trim($projectTitle);

        $completionYear =
            trim($completionYear);

        $alumniName =
            trim($alumniName);

        /*
        |--------------------------------------------------------------------------
        | Empty Validation
        |--------------------------------------------------------------------------
        */

        if ($projectTitle === "") {

            return $this->failure(
                "Project title is required"
            );
        }

        if ($alumniName === "") {

            return $this->failure(
                "Alumni name is required"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Length Validation
        |--------------------------------------------------------------------------
        */

        if (
            strlen($projectTitle) > 255
        ) {

            return $this->failure(
                "Project title cannot exceed 255 characters"
            );
        }

        if (
            strlen($alumniName) > 100
        ) {

            return $this->failure(
                "Alumni name cannot exceed 100 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Completion Year Validation
        |--------------------------------------------------------------------------
        */

        if (
            !ctype_digit($completionYear)
        ) {

            return $this->failure(
                "Completion year must be numeric"
            );
        }

        $year =
            (int) $completionYear;

        $maximumYear =
            ((int) date("Y")) + 1;

        if (
            $year < self::MIN_COMPLETION_YEAR ||
            $year > $maximumYear
        ) {

            return $this->failure(
                "Completion year is outside the allowed range"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return $this->success(
            "Valid project input"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Positive Integer Validation
    |--------------------------------------------------------------------------
    */

    private function isPositiveInteger(
        $value
    ) {

        return
            ctype_digit(
                (string) $value
            )
            &&
            (int) $value > 0;
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