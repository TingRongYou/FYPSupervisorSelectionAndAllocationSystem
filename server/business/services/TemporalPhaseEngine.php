<?php

require_once __DIR__ . "/../../data/database/database.php";
require_once __DIR__ . "/../../data/dao/RequestDAO.php";

class TemporalPhaseEngine {

    private const SYSTEM_TIMEZONE = "Asia/Kuala_Lumpur";
    private static $instance = null;
    private $conn;
    private $requestDAO;

    private function __construct() {
        date_default_timezone_set(self::SYSTEM_TIMEZONE);
        $database = new Database();
        $this->conn = $database->connect();
        $this->requestDAO = new RequestDAO();
        $this->ensureSystemPhaseTable();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new TemporalPhaseEngine();
        }

        return self::$instance;
    }

    public function getPhasePayload() {

        $this->executeTimeoutAutomation();

        $serverNow =
            new DateTimeImmutable(
                "now",
                new DateTimeZone(self::SYSTEM_TIMEZONE)
            );

        $phases =
            $this->getPhases();

        $activePhase =
            null;

        $nextPhase =
            null;

        foreach ($phases as $phase) {

            $start =
                $this->toDateTime($phase["startTimestamp"]);

            $end =
                $this->toDateTime($phase["endTimestamp"]);

            if ($serverNow >= $start && $serverNow <= $end) {

                $activePhase =
                    $phase;

                break;
            }

            if ($serverNow < $start && $nextPhase === null) {

                $nextPhase =
                    $phase;
            }
        }

        $targetPhase =
            $activePhase ?: $nextPhase;

        $remainingSeconds =
            0;

        if ($activePhase) {
            // Epoch subtraction using the server's immutable timestamp
            $remainingSeconds =
                max(0, $this->toDateTime($activePhase["endTimestamp"])->getTimestamp() - $serverNow->getTimestamp());
        } elseif ($nextPhase) {

            $remainingSeconds =
                max(0, $this->toDateTime($nextPhase["startTimestamp"])->getTimestamp() - $serverNow->getTimestamp());
        }

        $canSubmitProposal =
            $activePhase
            &&
            strcasecmp($activePhase["phaseName"], "Submission Phase") === 0;

        return [
            "success" =>
                true,

            "serverTime" =>
                $serverNow->format("Y-m-d H:i:s"),

            "serverEpoch" =>
                $serverNow->getTimestamp(),

            "activePhase" =>
                $activePhase,

            "activePhaseName" =>
                $activePhase["phaseName"] ?? "No Active Phase",

            "nextPhase" =>
                $nextPhase,

            "displayPhase" =>
                $targetPhase,

            "phaseStatus" =>
                $this->getPhaseStatus($activePhase, $nextPhase),

            "startTimestamp" =>
                $targetPhase["startTimestamp"] ?? null,

            "endTimestamp" =>
                $targetPhase["endTimestamp"] ?? null,

            "remainingSeconds" =>
                $remainingSeconds,

            "remainingText" =>
                $this->formatRemaining($remainingSeconds),

            "canSubmitProposal" =>
                $canSubmitProposal,

            "message" =>
                $this->getMessage($activePhase, $nextPhase, $canSubmitProposal),

            "phases" =>
                $this->decoratePhases($phases, $serverNow)
        ];
    }

    public function isSubmissionPhaseActive() {

        $payload =
            $this->getPhasePayload();

        return
            (bool) $payload["canSubmitProposal"];
    }

    public function executeTimeoutAutomation() {

        return
            $this->requestDAO
                ->expireTimedOutPendingRequests();
    }

    private function getPhases() {

        $query = "
            SELECT
                phaseID,
                phaseName,
                startTimestamp,
                endTimestamp
            FROM SYSTEM_PHASE_TIMELINE
            ORDER BY startTimestamp ASC, phaseID ASC
        ";

        $statement =
            $this->conn->prepare($query);

        $statement->execute();

        return
            $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function decoratePhases($phases, $serverNow) {

        $decorated =
            [];

        foreach ($phases as $phase) {

            $start =
                $this->toDateTime($phase["startTimestamp"]);

            $end =
                $this->toDateTime($phase["endTimestamp"]);

            if ($serverNow < $start) {

                $status =
                    "Upcoming";

            } elseif ($serverNow > $end) {

                $status =
                    "Completed";

            } else {

                $status =
                    "Active";
            }

            $phase["status"] =
                $status;

            $phase["remainingText"] =
                $status === "Active"
                    ? $this->formatRemaining(max(0, $end->getTimestamp() - $serverNow->getTimestamp()))
                    : "-";

            $decorated[] =
                $phase;
        }

        return $decorated;
    }

    private function getPhaseStatus($activePhase, $nextPhase) {

        if ($activePhase) {

            return "Active";
        }

        if ($nextPhase) {

            return "Upcoming";
        }

        return "Locked";
    }

    private function getMessage($activePhase, $nextPhase, $canSubmitProposal) {

        if ($canSubmitProposal) {

            return "Submission Phase is active. You may submit supervisor proposals before the countdown ends.";
        }

        if ($activePhase) {

            return $activePhase["phaseName"] . " is active. Proposal submission is currently locked.";
        }

        if ($nextPhase) {

            return "The next phase has not started. Proposal submission is currently locked.";
        }

        return "No active academic phase is configured. Proposal submission is currently locked.";
    }

    // Formats output into strictly chunked strings
    private function formatRemaining($seconds) {
        $seconds = max(0, (int) $seconds);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return
            str_pad((string) $days, 2, "0", STR_PAD_LEFT) . "d "
            . str_pad((string) $hours, 2, "0", STR_PAD_LEFT) . "h "
            . str_pad((string) $minutes, 2, "0", STR_PAD_LEFT) . "m";
    }

    private function toDateTime($value) {

        return
            new DateTimeImmutable(
                (string) $value,
                new DateTimeZone(self::SYSTEM_TIMEZONE)
            );
    }

    private function ensureSystemPhaseTable() {

        $query = "
            CREATE TABLE IF NOT EXISTS SYSTEM_PHASE_TIMELINE (
                phaseID INT PRIMARY KEY,
                phaseName VARCHAR(50) NOT NULL,
                startTimestamp DATETIME NOT NULL,
                endTimestamp DATETIME NOT NULL
            )
        ";

        $this->conn->exec($query);
    }
}

?>
