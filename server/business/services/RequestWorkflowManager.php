<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the request DAO, proposal storage handler, and supervisor profile
| service required for proposal submission workflow.
*/
require_once __DIR__ . "/../../data/dao/RequestDAO.php";
require_once __DIR__ . "/../../data/storage/ProposalStorageDAO.php";
require_once __DIR__ . "/SupervisorProfileService.php";
require_once __DIR__ . "/AllocationWindowService.php";
require_once __DIR__ . "/SupervisorAvailabilityService.php";
require_once __DIR__ . "/TemporalPhaseEngine.php";

/*
|--------------------------------------------------------------------------
| Request Workflow Manager
|--------------------------------------------------------------------------
| Handles the student proposal request workflow, including validation,
| supervisor availability checking, PDF storage, and request creation.
*/
class RequestWorkflowManager {

    /*
    |--------------------------------------------------------------------------
    | Request Constraints
    |--------------------------------------------------------------------------
    */
    private const MAX_TITLE_LENGTH = 255;

    // Defining strict concurrency threshold
    private const MAX_PENDING_REQUESTS = 3;

    /*
    |--------------------------------------------------------------------------
    | Service Dependencies
    |--------------------------------------------------------------------------
    */
    private $requestDAO;

    private $supervisorProfileService;

    private $proposalStorageDAO;

    private $allocationWindowService;

    private $availabilityService;

    private $temporalPhaseEngine;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes DAO, storage, and service dependencies.
    */
    public function __construct() {
        $this->requestDAO = new RequestDAO();
        $this->supervisorProfileService = new SupervisorProfileService();
        $this->proposalStorageDAO = new ProposalStorageDAO();
        $this->allocationWindowService = new AllocationWindowService();
        $this->availabilityService = new SupervisorAvailabilityService();

        // Accesses the global temporal engine Singleton
        $this->temporalPhaseEngine = TemporalPhaseEngine::getInstance();
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Proposal
    |--------------------------------------------------------------------------
    | Validates proposal details, confirms supervisor availability, stores the
    | proposal PDF, and creates a pending application request.
    */
    public function submitProposal($studentID, $supervisorID, $projectTitle, $proposalFile, $requestID = 0) {

        /*
        |--------------------------------------------------------------------------
        | Normalize Input Values
        |--------------------------------------------------------------------------
        */
        $studentID = trim($studentID);

        $supervisorID = trim($supervisorID);

        $projectTitle = trim($projectTitle);

        $requestID = (int) $requestID;

        $requestedProposal = null;

        if ($requestID > 0) {

            $requestedProposal = $this->requestDAO->getProposalRequestForStudent($requestID, $studentID);

            if (!$requestedProposal) {

                return $this->failure("Proposal request was not found or has already been completed.");
            }

            $supervisorID = $requestedProposal["supervisorID"];
        }

        /*
        |--------------------------------------------------------------------------
        | Required Field Validation
        |--------------------------------------------------------------------------
        */
        if ($studentID === "" || $supervisorID === "" || $projectTitle === "") {

            return $this->failure("Project title and proposal document are required.");
        }

        /*
        |--------------------------------------------------------------------------
        | Project Title Length Validation
        |--------------------------------------------------------------------------
        */
        if (strlen($projectTitle) > self::MAX_TITLE_LENGTH) {

            return $this->failure("Project title cannot exceed 255 characters.");
        }

        /*
        |--------------------------------------------------------------------------
        | Allocation Window Validation
        |--------------------------------------------------------------------------
        */
        if ($requestedProposal === null) {

            $allocationWindow = $this->allocationWindowService->getWindow();

            if (!$allocationWindow["canStudentsSubmit"]) {

                return $this->failure(
                    $allocationWindow["status"] === "closed"
                        ? "The final allocation date has passed. You are now pending auto-allocation if you remain unassigned."
                        : $allocationWindow["statusText"]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Server-Side Phase Validation
        |--------------------------------------------------------------------------
        | Uses the singleton temporal engine so proposal POST access follows the
        | central server timeline, not a browser-controlled clock.
        */
        if ($requestedProposal === null && !$this->temporalPhaseEngine->isSubmissionPhaseActive()) {
            return $this->failure("Submission phase has expired. Proposal submission is currently locked.");
        }

        /*
        |--------------------------------------------------------------------------
        | Eligibility Validation
        |--------------------------------------------------------------------------
        */
        if (!$this->requestDAO->isStudentEligibleForFYP($studentID)) {

            return $this->failure("Requirements Not Met for FYP.");
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Allocation Validation
        |--------------------------------------------------------------------------
        */
        if ($requestedProposal === null && $this->requestDAO->studentHasAllocation($studentID)) {

            return $this->failure("You have already been allocated to a supervisor.");
        }

        /*
        |--------------------------------------------------------------------------
        | Load Selected Supervisor
        |--------------------------------------------------------------------------
        */
        $supervisor =$this->supervisorProfileService->getDigitalBusinessCard($supervisorID);

        if (!$supervisor) {

            return $this->failure("Selected supervisor was not found.");
        }

        if ($requestedProposal === null) {

            $availability = $this->availabilityService->canStudentApply($supervisorID);

            if (!$availability["success"]) {

                return $this->failure($availability["message"]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Concurrency Limit Validation
        |--------------------------------------------------------------------------
        | Enforces the maximum of three concurrent pending proposal requests.
        */
        // Validates active requests against threshold
        if (
            $requestedProposal === null &&
            $this->requestDAO->countPendingRequestsByStudent($studentID)
            >= self::MAX_PENDING_REQUESTS
        ) {

            return $this->failure("You may only have up to 3 active pending proposals.");
        }

        /*
        |--------------------------------------------------------------------------
        | Store Proposal PDF
        |--------------------------------------------------------------------------
        */
        $proposalResult = $this->proposalStorageDAO->storeProposalPDF($proposalFile, $studentID);

        if (!$proposalResult["success"]) {

            return $this->failure($proposalResult["message"]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Application Request
        |--------------------------------------------------------------------------
        */
        $created =
            $requestedProposal !== null
                ? $this->requestDAO
                    ->completeRequestedProposal(
                        $requestID,
                        $studentID,
                        $supervisorID,
                        $projectTitle,
                        $proposalResult["path"]
                    )
                : $this->requestDAO
                    ->createApplicationRequest(
                        $studentID,
                        $supervisorID,
                        $projectTitle,
                        $proposalResult["path"]
                    );

        if (!$created) {

            return $this->failure("Proposal request could not be submitted.");
        }

        /*
        |--------------------------------------------------------------------------
        | Proposal Submission Success
        |--------------------------------------------------------------------------
        */
        return $this->success("Your proposal has been successfully submitted to the supervisor. They have 72 hours to respond.");
    }

    /*
    |--------------------------------------------------------------------------
    | Success Response Helper
    |--------------------------------------------------------------------------
    */
    private function success($message) {

        return ["success" => true, "message" => $message];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response Helper
    |--------------------------------------------------------------------------
    */
    private function failure($message) {

        return ["success" => false, "message" => $message];
    }
}

?>
