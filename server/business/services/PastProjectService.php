<?php

require_once __DIR__ . "/../../data/dao/PastProjectDAO.php";
require_once __DIR__ . "/../../data/storage/PastProjectDocumentStorageDAO.php";
require_once __DIR__ . "/../../data/storage/PastProjectImageStorageDAO.php";

class PastProjectService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MIN_COMPLETION_YEAR = 2000;

    private const MAX_DESCRIPTION_LENGTH = 1000;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $pastProjectDAO;

    private $documentStorageDAO;

    private $imageStorageDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->pastProjectDAO = new PastProjectDAO();

        $this->documentStorageDAO = new PastProjectDocumentStorageDAO();

        $this->imageStorageDAO = new PastProjectImageStorageDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Projects
    |--------------------------------------------------------------------------
    */

    public function getProjectsBySupervisor($supervisorID) {

        return $this->pastProjectDAO->getProjectsBySupervisor($supervisorID);
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Showcase Summary
    |--------------------------------------------------------------------------
    */

    public function getShowcaseSummary($supervisorID) {

        $projects = $this->getProjectsBySupervisor($supervisorID);

        $studentsSupervised = $this->pastProjectDAO->countActiveSuperviseesBySupervisor($supervisorID);

        return ["totalProjects" => count($projects), "studentsSupervised" => $studentsSupervised];
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Single Project
    |--------------------------------------------------------------------------
    */

    public function getProjectByID($projectID, $supervisorID) {

        if (!$this->isPositiveInteger($projectID)) {

            return null;
        }

        return $this->pastProjectDAO->getProjectByID((int) $projectID, $supervisorID);
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
        $alumniName,
        $projectDescription,
        $projectPDFFile = null,
        $projectImageFile = null
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
                $alumniName,
                $projectDescription
            );

        if (!$validation["success"]) {

            return $validation;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $projectTitle = trim($projectTitle);

        $completionYear = (int) $completionYear;

        $alumniName = trim($alumniName);

        $projectDescription = trim($projectDescription);

        $documentResult =
            $this->documentStorageDAO
            ->storeProjectPDF(
                $projectPDFFile,
                $supervisorID
            );

        if (!$documentResult["success"]) {

            return $this->failure($documentResult["message"]);
        }

        $projectPDFPath = $documentResult["path"];

        $imageResult =
            $this->imageStorageDAO
            ->storeProjectImage(
                $projectImageFile,
                $supervisorID
            );

        if (!$imageResult["success"]) {

            $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);

            return $this->failure($imageResult["message"]);
        }

        $projectImagePath = $imageResult["path"];

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

                $this->documentStorageDAO ->deleteStoredPDF($projectPDFPath);

                $this->imageStorageDAO ->deleteStoredImage($projectImagePath);

                return $this->failure("This past project already exists");
            }

        } catch (Exception $exception) {

            $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);

            $this->imageStorageDAO->deleteStoredImage($projectImagePath);

            return $this->failure("Unable to validate project duplication");
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

                    $alumniName,

                    $projectDescription,

                    $projectPDFPath,

                    $projectImagePath
                );

            if (!$created) {

                $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);

                $this->imageStorageDAO->deleteStoredImage($projectImagePath);

                return $this->failure("Past project could not be added");
            }

        } catch (Exception $exception) {

            $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);

            $this->imageStorageDAO->deleteStoredImage($projectImagePath);

            return $this->failure("System error occurred while adding project");
        }

        return $this->success("Update Successful - Your past projects have been successfully updated in the showcase.");
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
        $alumniName,
        $projectDescription,
        $projectPDFFile = null,
        $removeProjectPDF = false,
        $projectImageFile = null,
        $removeProjectImage = false
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate Project ID
        |--------------------------------------------------------------------------
        */

        if (!$this->isPositiveInteger($projectID)) {

            return $this->failure("Invalid project selected");
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

                return $this->failure("Past project was not found");
            }

        } catch (Exception $exception) {

            return $this->failure("Unable to validate project ownership");
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
                $alumniName,
                $projectDescription
            );

        if (!$validation["success"]) {

            return $validation;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $projectTitle = trim($projectTitle);

        $completionYear = (int) $completionYear;

        $alumniName = trim($alumniName);

        $projectDescription = trim($projectDescription);

        $projectPDFPath =
            $this->normalizeStoredPath(
                $project["projectPDFPath"] ?? null,
                "past_projects"
            );

        $oldProjectPDFPath = $projectPDFPath;

        $projectImagePath =
            $this->normalizeStoredPath(
                $project["projectImagePath"] ?? null,
                "past_project_images"
            );

        $oldProjectImagePath = $projectImagePath;

        if ($removeProjectPDF) {

            $projectPDFPath = null;
        }

        if ($removeProjectImage) {

            $projectImagePath = null;
        }

        $documentResult =
            $this->documentStorageDAO
            ->storeProjectPDF(
                $projectPDFFile,
                $supervisorID
            );

        if (!$documentResult["success"]) {

            return $this->failure($documentResult["message"]);
        }

        if ($documentResult["path"] !== null) {

            $projectPDFPath = $documentResult["path"];
        }

        $imageResult =
            $this->imageStorageDAO
            ->storeProjectImage(
                $projectImageFile,
                $supervisorID
            );

        if (!$imageResult["success"]) {

            if ($documentResult["path"] !== null) {

                $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);
            }

            return $this->failure($imageResult["message"]);
        }

        if ($imageResult["path"] !== null) {

            $projectImagePath = $imageResult["path"];
        }

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

                if ($documentResult["path"] !== null) {

                    $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);
                }

                if ($imageResult["path"] !== null) {

                    $this->imageStorageDAO->deleteStoredImage($projectImagePath);
                }

                return $this->failure("Another project with the same title and year already exists");
            }

        } catch (Exception $exception) {

            if ($documentResult["path"] !== null) {

                $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);
            }

            if ($imageResult["path"] !== null) {

                $this->imageStorageDAO->deleteStoredImage($projectImagePath);
            }

            return $this->failure("Unable to validate duplicate project");
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

                    $alumniName,

                    $projectDescription,

                    $projectPDFPath,

                    $projectImagePath
                );

            if (!$updated) {

                if ($documentResult["path"] !== null) {

                    $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);
                }

                if ($imageResult["path"] !== null) {

                    $this->imageStorageDAO->deleteStoredImage($projectImagePath);
                }

                return $this->failure("Past project could not be updated");
            }

            if ($removeProjectPDF || $documentResult["path"] !== null) {

                $this->documentStorageDAO->deleteStoredPDF($oldProjectPDFPath);
            }

            if ($removeProjectImage || $imageResult["path"] !== null) {

                $this->imageStorageDAO->deleteStoredImage($oldProjectImagePath);
            }

        } catch (Exception $exception) {

            if ($documentResult["path"] !== null) {

                $this->documentStorageDAO->deleteStoredPDF($projectPDFPath);
            }

            if ($imageResult["path"] !== null) {

                $this->imageStorageDAO->deleteStoredImage($projectImagePath);
            }

            return $this->failure("System error occurred while updating project");
        }

        return $this->success("Update Successful - Your past projects have been successfully updated in the showcase.");
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Project
    |--------------------------------------------------------------------------
    */

    public function deleteProject($projectID, $supervisorID) {

        /*
        |--------------------------------------------------------------------------
        | Validate Project ID
        |--------------------------------------------------------------------------
        */

        if (!$this->isPositiveInteger($projectID)) {

            return $this->failure("Invalid project selected");
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

                return $this->failure("Past project was not found");
            }

        } catch (Exception $exception) {

            return $this->failure("Unable to validate project ownership");
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

                return $this->failure("Past project could not be deleted");
            }

            $this->documentStorageDAO->deleteStoredPDF($project["projectPDFPath"] ?? "");

            $this->imageStorageDAO->deleteStoredImage($project["projectImagePath"] ?? "");

        } catch (Exception $exception) {

            return $this->failure("System error occurred while deleting project");
        }

        return $this->success("Update Successful - Your past projects have been successfully updated in the showcase.");
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Inputs
    |--------------------------------------------------------------------------
    */

    private function validateProjectInput($projectTitle, $completionYear, $alumniName, $projectDescription) {

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $projectTitle = trim($projectTitle);

        $completionYear = trim($completionYear);

        $alumniName = trim($alumniName);

        $projectDescription = trim($projectDescription);

        /*
        |--------------------------------------------------------------------------
        | Empty Validation
        |--------------------------------------------------------------------------
        */

        if ($projectTitle === "") {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        if ($alumniName === "") {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        if ($projectDescription === "") {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        /*
        |--------------------------------------------------------------------------
        | Length Validation
        |--------------------------------------------------------------------------
        */

        if (strlen($projectTitle) > 255) {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        if (strlen($alumniName) > 100) {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        if (strlen($projectDescription) > self::MAX_DESCRIPTION_LENGTH) {

            return $this->failure("Validation Error - Project description cannot exceed 1000 characters.");
        }

        /*
        |--------------------------------------------------------------------------
        | Completion Year Validation
        |--------------------------------------------------------------------------
        */

        if (!ctype_digit($completionYear)) {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        $year = (int) $completionYear;

        $maximumYear = ((int) date("Y")) + 1;

        if ( $year < self::MIN_COMPLETION_YEAR || $year > $maximumYear) {

            return $this->failure("Validation Error - Please enter all the required information.");
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return $this->success("Valid project input");
    }

    /*
    |--------------------------------------------------------------------------
    | Positive Integer Validation
    |--------------------------------------------------------------------------
    */

    private function isPositiveInteger($value) {

        return ctype_digit((string) $value) && (int) $value > 0;
    }

    private function normalizeStoredPath($path, $directoryName) {

        $path = trim((string) $path);

        if ($path === "") {

            return null;
        }

        return "storage/" . $directoryName . "/" . basename($path);
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
