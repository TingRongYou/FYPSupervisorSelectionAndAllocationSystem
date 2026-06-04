<?php

require_once __DIR__ . "/../../data/dao/RequestDAO.php";
require_once __DIR__ . "/../states/FYPApplication.php";
require_once __DIR__ . "/../states/PendingState.php";
require_once __DIR__ . "/../states/AcceptedState.php";
require_once __DIR__ . "/../states/RejectedState.php";
require_once __DIR__ . "/../states/TimeoutState.php";
require_once __DIR__ . "/../states/WithdrawnState.php";
require_once __DIR__ . "/../states/AutoAllocatedState.php";

class RequestDecisionService {

    private $requestDAO;

    public function __construct() {

        $this->requestDAO =
            new RequestDAO();
    }

    public function processDecision(
        $requestID,
        $supervisorID,
        $decisionStatus,
        $supervisorComment
    ) {

        $requestID =
            (int) $requestID;

        $supervisorID =
            trim((string) $supervisorID);

        $decisionStatus =
            trim((string) $decisionStatus);

        $supervisorComment =
            trim((string) $supervisorComment);

        if (
            $requestID <= 0 ||
            !in_array($decisionStatus, ["Accepted", "Rejected"], true)
        ) {

            return $this->failure(
                "Invalid decision request."
            );
        }

        if ($supervisorComment === "") {

            return $this->failure(
                "Comment Required - Please provide a reason for rejection to help the student improve their next application."
            );
        }

        $request =
            $this->requestDAO
            ->getApplicationRequestForSupervisor(
                $requestID,
                $supervisorID
            );

        if (!$request) {

            return $this->failure(
                "The selected request was not found."
            );
        }

        $state =
            $this->stateFromStatus(
                $request["decisionStatus"]
            );

        $application =
            new FYPApplication(
                $request["requestID"],
                $request["supervisorID"],
                $request["studentID"],
                $state
            );

        $stateResult =
            $decisionStatus === "Accepted"
                ? $application->accept($supervisorComment)
                : $application->reject($supervisorComment);

        if (!$stateResult["success"]) {

            return $stateResult;
        }

        return
            $this->requestDAO
            ->processSupervisorDecision(
                $application->getRequestID(),
                $application->getSupervisorID(),
                $stateResult["decisionStatus"],
                $stateResult["comment"]
            );
    }

    private function stateFromStatus($status) {

        if ($status === "Accepted") {

            return new AcceptedState();
        }

        if ($status === "Rejected") {

            return new RejectedState();
        }

        if ($status === "Rejected-Timeout") {

            return new TimeoutState();
        }

        if ($status === "Withdrawn") {

            return new WithdrawnState();
        }

        if ($status === "Auto-Allocated") {

            return new AutoAllocatedState();
        }

        return new PendingState();
    }

    private function failure($message) {

        return [
            "success" => false,
            "message" => $message
        ];
    }
}

?>
