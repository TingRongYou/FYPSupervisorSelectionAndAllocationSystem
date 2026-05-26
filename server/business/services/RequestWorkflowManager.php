<?php

require_once __DIR__ . "/../../data/dao/RequestDAO.php";
require_once __DIR__ . "/../../data/storage/ProposalStorageDAO.php";
require_once __DIR__ . "/SupervisorProfileService.php";

class RequestWorkflowManager {

    private const MAX_TITLE_LENGTH = 120;

    private $requestDAO;
    private $supervisorProfileService;
    private $proposalStorageDAO;

    public function __construct() {

        $this->requestDAO = new RequestDAO();
        $this->supervisorProfileService = new SupervisorProfileService();
        $this->proposalStorageDAO = new ProposalStorageDAO();
    }

    public function submitProposal($studentID, $supervisorID, $projectTitle, $proposalFile) {

        $studentID = trim($studentID);
        $supervisorID = trim($supervisorID);
        $projectTitle = trim($projectTitle);

        if ($studentID === "" || $supervisorID === "" || $projectTitle === "") {

            return $this->failure("Project title and proposal document are required.");
        }

        if (strlen($projectTitle) > self::MAX_TITLE_LENGTH) {

            return $this->failure("Project title cannot exceed 120 characters.");
        }

        $supervisor = $this->supervisorProfileService->getDigitalBusinessCard($supervisorID);

        if (!$supervisor) {

            return $this->failure("Selected supervisor was not found.");
        }

        if (($supervisor["status"] ?? "Full") !== "Available") {

            return $this->failure("This supervisor is currently not available for new proposals.");
        }

        if ($this->requestDAO->studentHasActiveRequest($studentID)) {

            return $this->failure("You already have a pending proposal request.");
        }

        $proposalResult = $this->proposalStorageDAO->storeProposalPDF($proposalFile, $studentID);

        if (!$proposalResult["success"]) {

            return $this->failure($proposalResult["message"]);
        }

        $created = $this->requestDAO->createApplicationRequest(
            $studentID,
            $supervisorID,
            $projectTitle,
            $proposalResult["path"]
        );

        if (!$created) {

            return $this->failure("Proposal request could not be submitted.");
        }

        return $this->success("Proposal submitted successfully.");
    }

    private function success($message) {

        return [
            "success" => true,
            "message" => $message
        ];
    }

    private function failure($message) {

        return [
            "success" => false,
            "message" => $message
        ];
    }
}

?>


