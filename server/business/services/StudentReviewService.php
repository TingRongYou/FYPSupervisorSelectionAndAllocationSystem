<?php

require_once __DIR__ . "/../../data/dao/SupervisorReviewDAO.php";
require_once __DIR__ . "/SupervisorReviewProxy.php";

class StudentReviewService {

    private const MAX_FEEDBACK_LENGTH = 1000;

    private $reviewDAO;

    public function __construct() {

        $this->reviewDAO = new SupervisorReviewDAO();
    }

    // Gather all review information from database and organize into a single payload array
    public function getReviewPagePayload($studentID) {

        $context = $this->reviewDAO->getReviewContextForStudent($studentID); // Retrieve details about student's relationship with supervisor
        $phase = $this->reviewDAO->getActiveSystemPhase();
        $isReviewPeriod = $this->isReviewPeriod($phase);

        $statistics = null;

        if ($context && !empty($context["supervisorID"])) {

            $statistics = $this->reviewDAO
                ->getSupervisorReviewStatistics($context["supervisorID"]);
        }

        return [
            "context" => $context,
            "phase" => $phase,
            "isReviewPeriod" => $isReviewPeriod,
            "statistics" => $statistics,
            "canSubmit" => $context &&
                !$context["reviewID"] &&
                $isReviewPeriod
        ];
    }

    public function submitReview($studentID, $allocationID, $starRating, $textFeedback, $isAnonymous) {

        $studentID = trim((string) $studentID);
        $allocationID = (int) $allocationID;
        $starRating = (int) $starRating;
        $textFeedback = trim((string) $textFeedback);
        $isAnonymous = (bool) $isAnonymous;

        if (!$this->isReviewPeriod($this->reviewDAO->getActiveSystemPhase())) {

            return $this->failure(
                "Submission failed. Reviews can only be submitted during the Review Period."
            );
        }

        if (!$this->reviewDAO->allocationBelongsToStudent($allocationID, $studentID)) {

            return $this->failure(
                "Submission failed. You must have a completed allocation before submitting a review."
            );
        }

        // Dependency check: prevents duplicate reviews for the same allocation
        if ($this->reviewDAO->reviewExistsForAllocation($allocationID)) {
            return $this->failure("You have already submitted an evaluation for your supervisor this semester.");
        }

        // Constraint: mandatory 1 to 5 rating scale
        if ($starRating < 1 || $starRating > 5) {
            return $this->failure("Submission failed. You must select a star rating (1-5) to submit a review. Text feedback is optional.");
        }

        // Constraint C1: Feedback payload limit
        if (strlen($textFeedback) > self::MAX_FEEDBACK_LENGTH) {
            return $this->failure("Submission failed. Feedback cannot exceed 1000 characters.");
        }

        $created = $this->reviewDAO->createReview(
            $allocationID,
            $studentID,
            $starRating,
            $textFeedback,
            $isAnonymous
        );

        if (!$created) {

            return $this->failure(
                "Submission failed. Please try again."
            );
        }

        return $this->success(
            "Your review has been successfully submitted. Thank you for your feedback."
        );
    }

    public function getSanitizedReviewsForSupervisor($supervisorID) {
        $reviews = $this->reviewDAO->getSanitizedReviewsForSupervisor($supervisorID);

        return array_map(
            function ($review) {
                return (new AnonymousReviewProxy( // Decides which part of raw data the user is allowed to see
                    new SupervisorReviewRecord($review) // Holds all of the raw data 
                ))->display();
            },
            $reviews
        );
    }

    public function getReviewAuditRecords($supervisorID = "") {

        return
            $this->reviewDAO
                ->getReviewAuditRecords($supervisorID);
    }

    private function isReviewPeriod($phase) {

        return $phase &&
            strtolower(trim((string) $phase["phaseName"])) === "review period";
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
