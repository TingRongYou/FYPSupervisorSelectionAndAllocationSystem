<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the RequestDAO used to retrieve supervisor dashboard data,
| pending requests, active supervisees, quota usage, and system phase details.
*/
require_once __DIR__ . "/../../data/dao/RequestDAO.php";

/*
|--------------------------------------------------------------------------
| Supervisor Dashboard Service
|--------------------------------------------------------------------------
| Handles the business logic for the supervisor dashboard, including request
| counts, supervisee counts, quota usage, recent applications, and alerts.
*/
class SupervisorDashboardService {

    /*
    |--------------------------------------------------------------------------
    | Dashboard Constants
    |--------------------------------------------------------------------------
    */
    private const RECENT_APPLICATION_LIMIT = 3;

    private const ALERT_WINDOW_HOURS = 72;

    /*
    |--------------------------------------------------------------------------
    | DAO Dependency
    |--------------------------------------------------------------------------
    */
    private $requestDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes the request data access dependency.
    */
    public function __construct() {

        $this->requestDAO =
            new RequestDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Dashboard Data
    |--------------------------------------------------------------------------
    | Compiles all supervisor dashboard metrics into a single response array.
    */
    public function getDashboardData(
        $supervisorID,
        $allocationStatus = "",
        $proposalStatus = ""
    ) {

        /*
        |--------------------------------------------------------------------------
        | Count Pending Requests
        |--------------------------------------------------------------------------
        */
        $pendingRequests =
            $this->requestDAO
                ->countPendingRequestsBySupervisor(
                    $supervisorID
                );

        /*
        |--------------------------------------------------------------------------
        | Count Active Supervisees
        |--------------------------------------------------------------------------
        */
        $activeSupervisees =
            $this->requestDAO
                ->countActiveSupervisees(
                    $supervisorID
                );

        /*
        |--------------------------------------------------------------------------
        | Get Supervisor Quota Limit
        |--------------------------------------------------------------------------
        */
        $maxSuperviseesAllowed =
            $this->requestDAO
                ->getSupervisorQuotaLimit(
                    $supervisorID
                );

        /*
        |--------------------------------------------------------------------------
        | Get Recent Allocations
        |--------------------------------------------------------------------------
        */
        $recentApplications =
            $this->requestDAO
                ->getRecentAllocationsBySupervisor(
                    $supervisorID,
                    self::RECENT_APPLICATION_LIMIT,
                    $allocationStatus,
                    $proposalStatus
                );

        /*
        |--------------------------------------------------------------------------
        | Get Active System Phase
        |--------------------------------------------------------------------------
        */
        $activePhase =
            $this->requestDAO
                ->getActiveSystemPhase();

        /*
        |--------------------------------------------------------------------------
        | Calculate Quota Usage
        |--------------------------------------------------------------------------
        */
        $quotaUsage =
            $maxSuperviseesAllowed > 0
                ? round(
                    (
                        $activeSupervisees /
                        $maxSuperviseesAllowed
                    ) * 100
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | Build Dashboard Response
        |--------------------------------------------------------------------------
        */
        return [
            "pendingRequests" =>
                $pendingRequests,

            "activeSupervisees" =>
                $activeSupervisees,

            "maxSuperviseesAllowed" =>
                $maxSuperviseesAllowed,

            "quotaUsage" =>
                min(100, $quotaUsage),

            "recentApplications" =>
                $this->formatApplications($recentApplications),

            "deadlineAlert" =>
                $this->buildDeadlineAlert(
                    $activePhase,
                    $pendingRequests
                )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Format Applications
    |--------------------------------------------------------------------------
    | Adds display-friendly fields for recent application cards.
    */
    private function formatApplications(
        $applications
    ) {

        foreach ($applications as $index => $application) {

            $applications[$index]["researchFocus"] =
                $application["projectTitle"];

            $applications[$index]["statusClass"] =
                $this->getStatusClass(
                    $application["decisionStatus"]
                );

            $applications[$index]["proposalStatusText"] =
                $this->getProposalStatusText(
                    $application
                );

            $applications[$index]["proposalStatusClass"] =
                $this->getStatusClass(
                    $applications[$index]["proposalStatusText"]
                );

            $applications[$index]["actionText"] =
                empty($application["requestID"])
                    ? "View Supervisees"
                    : (
                        $application["decisionStatus"] === "Pending"
                            ? "View Request"
                            : "View Details"
                    );
        }

        return $applications;
    }

    private function getProposalStatusText(
        $application
    ) {

        $proposalStatus =
            trim((string) ($application["proposalStatus"] ?? ""));

        if ($proposalStatus === "Proposal Requested") {

            return "Requested";
        }

        if ($proposalStatus !== "") {

            return $proposalStatus;
        }

        return "Not Submitted";
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Class
    |--------------------------------------------------------------------------
    | Converts decision status into a CSS-friendly status class.
    */
    private function getStatusClass(
        $status
    ) {

        $normalizedStatus =
            strtolower(
                trim($status)
            );

        if ($normalizedStatus === "accepted") {

            return "accepted";
        }

        if (
            $normalizedStatus === "allocated" ||
            $normalizedStatus === "auto-allocated"
        ) {

            return "allocated";
        }

        if ($normalizedStatus === "rejected") {

            return "rejected";
        }

        if (
            $normalizedStatus === "requested" ||
            $normalizedStatus === "not submitted"
        ) {

            return "pending";
        }

        return "pending";
    }

    /*
    |--------------------------------------------------------------------------
    | Build Deadline Alert
    |--------------------------------------------------------------------------
    | Shows a red advisory alert only when there are pending requests and the
    | active phase deadline is within the configured advisory window.
    */
    private function buildDeadlineAlert(
        $activePhase,
        $pendingRequests
    ) {

        /*
        |--------------------------------------------------------------------------
        | Alert Eligibility Validation
        |--------------------------------------------------------------------------
        */
        if (
            !$activePhase ||
            $pendingRequests <= 0
        ) {

            return [
                "show" => false,
                "message" => ""
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Deadline Time Remaining
        |--------------------------------------------------------------------------
        */
        $deadlineTimestamp =
            strtotime(
                $activePhase["endTimestamp"]
            );

        $secondsRemaining =
            $deadlineTimestamp - time();

        /*
        |--------------------------------------------------------------------------
        | Advisory Window Validation
        |--------------------------------------------------------------------------
        */
        if (
            $secondsRemaining <= 0 ||
            $secondsRemaining > self::ALERT_WINDOW_HOURS * 3600
        ) {

            return [
                "show" => false,
                "message" => ""
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Build Deadline Alert Message
        |--------------------------------------------------------------------------
        */
        return [
            "show" => true,

            "message" =>
                "Faculty Advisory: " .
                $activePhase["phaseName"] .
                " deadline is " .
                date("l, jS M", $deadlineTimestamp) .
                ". Please finalize all pending reviews."
        ];
    }
}

?>
