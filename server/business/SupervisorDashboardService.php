<?php

require_once __DIR__ . "/../data/RequestDAO.php";

class SupervisorDashboardService {

    private const RECENT_APPLICATION_LIMIT = 3;

    private const ALERT_WINDOW_HOURS = 72;

    private $requestDAO;

    public function __construct() {

        $this->requestDAO =
            new RequestDAO();
    }

    public function getDashboardData(
        $supervisorID
    ) {

        $pendingRequests =
            $this->requestDAO
            ->countPendingRequestsBySupervisor(
                $supervisorID
            );

        $activeSupervisees =
            $this->requestDAO
            ->countActiveSupervisees(
                $supervisorID
            );

        $maxSuperviseesAllowed =
            $this->requestDAO
            ->getSupervisorQuotaLimit(
                $supervisorID
            );

        $recentApplications =
            $this->requestDAO
            ->getRecentApplicationsBySupervisor(
                $supervisorID,
                self::RECENT_APPLICATION_LIMIT
            );

        $activePhase =
            $this->requestDAO
            ->getActiveSystemPhase();

        $quotaUsage =
            $maxSuperviseesAllowed > 0
            ? round(
                (
                    $activeSupervisees
                    /
                    $maxSuperviseesAllowed
                )
                *
                100
            )
            : 0;

        return [
            "pendingRequests" => $pendingRequests,
            "activeSupervisees" => $activeSupervisees,
            "maxSuperviseesAllowed" => $maxSuperviseesAllowed,
            "quotaUsage" => min(100, $quotaUsage),
            "recentApplications" => $this->formatApplications($recentApplications),
            "deadlineAlert" => $this->buildDeadlineAlert($activePhase, $pendingRequests)
        ];
    }

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

            $applications[$index]["actionText"] =
                $application["decisionStatus"] === "Pending"
                ? "View Request"
                : "View Details";
        }

        return $applications;
    }

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

        if ($normalizedStatus === "rejected") {

            return "rejected";
        }

        return "pending";
    }

    /*
    |--------------------------------------------------------------------------
    | Conditional Red Advisory Alert
    |--------------------------------------------------------------------------
    | The alert appears only when there are pending requests and the active
    | phase deadline is within the configured advisory window.
    */

    private function buildDeadlineAlert(
        $activePhase,
        $pendingRequests
    ) {

        if (!$activePhase || $pendingRequests <= 0) {

            return [
                "show" => false,
                "message" => ""
            ];
        }

        $deadlineTimestamp =
            strtotime(
                $activePhase["endTimestamp"]
            );

        $secondsRemaining =
            $deadlineTimestamp
            -
            time();

        if (
            $secondsRemaining <= 0
            ||
            $secondsRemaining > self::ALERT_WINDOW_HOURS * 3600
        ) {

            return [
                "show" => false,
                "message" => ""
            ];
        }

        return [
            "show" => true,
            "message" => "Faculty Advisory: "
                . $activePhase["phaseName"]
                . " deadline is "
                . date("l, jS M", $deadlineTimestamp)
                . ". Please finalize all pending reviews."
        ];
    }
}

?>
