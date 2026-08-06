<?php

require_once __DIR__ . "/../../data/database/database.php";

class AllocationWindowService {

    private const SYSTEM_TIMEZONE = "Asia/Kuala_Lumpur";

    private $conn;

    public function __construct() {

        date_default_timezone_set(self::SYSTEM_TIMEZONE);

        $database = new Database();

        $this->conn = $database->connect();

        $this->ensureTable();
    }

    public function getWindow() {

        $query = "
            SELECT
                configID,
                initialAllocationDate,
                finalAllocationDate,
                updatedAt
            FROM ALLOCATION_WINDOW_CONFIG
            WHERE configID = 1
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        $window = $statement->fetch(PDO::FETCH_ASSOC);

        if ($window) {

            return $this->attachStatus($window);
        }

        $defaultWindow = [
            "configID" => 1,
            "initialAllocationDate" => null,
            "finalAllocationDate" => null,
            "updatedAt" => null
        ];

        return $this->attachStatus($defaultWindow);
    }

    public function getReviewPeriod() {

        $this->ensureSystemPhaseTable();

        $query = "
            SELECT
                phaseID,
                phaseName,
                startTimestamp,
                endTimestamp
            FROM SYSTEM_PHASE_TIMELINE
            WHERE phaseName = 'Review Period'
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        $period = $statement->fetch(PDO::FETCH_ASSOC);

        if ($period) {

            return $period;
        }

        return [
            "phaseID" => 3,
            "phaseName" => "Review Period",
            "startTimestamp" => null,
            "endTimestamp" => null
        ];
    }

    public function updateWindow($initialAllocationDate, $finalAllocationDate, $reviewStartDate = "", $reviewEndDate = "") {

        $initialAllocationDate = $this->normalizeDateTime($initialAllocationDate);

        $finalAllocationDate = $this->normalizeDateTime($finalAllocationDate);

        $reviewStartDate = $this->normalizeDateTime($reviewStartDate);

        $reviewEndDate = $this->normalizeDateTime($reviewEndDate);

        if (
            $initialAllocationDate === "" ||
            $finalAllocationDate === "" ||
            $reviewStartDate === "" ||
            $reviewEndDate === ""
        ) {

            return $this->failure(
                "Please set the initial allocation date, final allocation date, review period start, and review period end."
            );
        }

        if (
            $this->toTimestamp($initialAllocationDate)
            >=
            $this->toTimestamp($finalAllocationDate)
        ) {

            return $this->failure(
                "Final allocation date must be later than the initial allocation date."
            );
        }

        if (
            $this->toTimestamp($reviewStartDate)
            <=
            $this->toTimestamp($finalAllocationDate)
        ) {

            return $this->failure(
                "Review period start must be later than the final allocation date."
            );
        }

        if (
            $this->toTimestamp($reviewStartDate)
            >=
            $this->toTimestamp($reviewEndDate)
        ) {

            return $this->failure(
                "Review period end must be later than the review period start."
            );
        }

        try {

            $this->ensureSystemPhaseTable();
            $this->conn->beginTransaction();

            $query = "
                INSERT INTO ALLOCATION_WINDOW_CONFIG
                (
                    configID,
                    initialAllocationDate,
                    finalAllocationDate,
                    updatedAt
                )
                VALUES
                (
                    1,
                    :initialAllocationDate,
                    :finalAllocationDate,
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    initialAllocationDate = VALUES(initialAllocationDate),
                    finalAllocationDate = VALUES(finalAllocationDate),
                    updatedAt = NOW()
            ";

            $statement = $this->conn->prepare($query);

            $statement->bindParam(":initialAllocationDate", $initialAllocationDate);

            $statement->bindParam( ":finalAllocationDate", $finalAllocationDate);

            $statement->execute();

            $phaseQuery = "
                INSERT INTO SYSTEM_PHASE_TIMELINE
                (
                    phaseID,
                    phaseName,
                    startTimestamp,
                    endTimestamp
                )
                VALUES
                (
                    :phaseID,
                    :phaseName,
                    :startTimestamp,
                    :endTimestamp
                )
                ON DUPLICATE KEY UPDATE
                    phaseName = VALUES(phaseName),
                    startTimestamp = VALUES(startTimestamp),
                    endTimestamp = VALUES(endTimestamp)
            ";

            $this->upsertPhase(
                $phaseQuery,
                1,
                "Submission Phase",
                $initialAllocationDate,
                $finalAllocationDate
            );

            $this->upsertPhase(
                $phaseQuery,
                2,
                "Auto-Allocation Phase",
                $finalAllocationDate,
                $reviewStartDate
            );

            $this->upsertPhase(
                $phaseQuery,
                3,
                "Review Period",
                $reviewStartDate,
                $reviewEndDate
            );

            $this->conn->commit();

            return $this->success("Allocation and review timeline updated successfully.");

        } catch (Exception $exception) {

            if ($this->conn->inTransaction()) {

                $this->conn->rollBack();
            }

            return $this->failure("Allocation and review timeline could not be updated.");
        }
    }

    public function isSelectionOpen() {

        return $this->getWindow()["status"] === "open";
    }

    public function hasFinalAllocationDateReached() {

        return $this->getWindow()["status"] === "closed";
    }

    private function attachStatus($window) {

        $initial = $window["initialAllocationDate"] ?? null;

        $final = $window["finalAllocationDate"] ?? null;

        $now = (new DateTimeImmutable("now", new DateTimeZone(self::SYSTEM_TIMEZONE)))->getTimestamp();

        if (empty($initial) || empty($final)) {

            $window["status"] = "not_configured";
            $window["statusText"] = "Allocation window is not configured. Set the initial and final allocation dates before running auto-allocation.";
            $window["canStudentsSubmit"] = false;
            $window["canRunAutoAllocation"] = false;

            return $window;
        }

        if ($now < $this->toTimestamp($initial)) {

            $window["status"] = "upcoming";
            $window["statusText"] = "Supervisor selection has not opened yet.";
            $window["canStudentsSubmit"] = false;
            $window["canRunAutoAllocation"] = false;

            return $window;
        }

        if ($now <= $this->toTimestamp($final)) {

            $window["status"] = "open";
            $window["statusText"] = "Supervisor selection is currently open. Auto-allocation is locked until the final allocation date passes.";
            $window["canStudentsSubmit"] = true;
            $window["canRunAutoAllocation"] = false;

            return $window;
        }

        $window["status"] = "closed";
        $window["statusText"] = "Final allocation date has passed. Unassigned students are pending auto-allocation.";
        $window["canStudentsSubmit"] = false;
        $window["canRunAutoAllocation"] = true;

        return $window;
    }

    private function normalizeDateTime($value) {

        $value = trim((string) $value);

        if ($value === "") {

            return "";
        }

        $dateTime = new DateTimeImmutable($value, new DateTimeZone(self::SYSTEM_TIMEZONE));

        return $dateTime->setTimezone(new DateTimeZone(self::SYSTEM_TIMEZONE))->format("Y-m-d H:i:s");
    }

    private function toTimestamp($value) {

        $dateTime = new DateTimeImmutable((string) $value, new DateTimeZone(self::SYSTEM_TIMEZONE));

        return $dateTime->getTimestamp();
    }

    private function ensureTable() {

        $query = "
            CREATE TABLE IF NOT EXISTS ALLOCATION_WINDOW_CONFIG (
                configID INT PRIMARY KEY,
                initialAllocationDate DATETIME NOT NULL,
                finalAllocationDate DATETIME NOT NULL,
                updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP
            )
        ";

        $this->conn->exec($query);
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

    private function upsertPhase(
        $phaseQuery,
        $phaseID,
        $phaseName,
        $startTimestamp,
        $endTimestamp
    ) {

        $phaseStatement = $this->conn->prepare($phaseQuery);

        $phaseStatement->execute([
            ":phaseID" =>
                $phaseID,

            ":phaseName" =>
                $phaseName,

            ":startTimestamp" =>
                $startTimestamp,

            ":endTimestamp" =>
                $endTimestamp
        ]);
    }

    private function success($message) {

        return ["success" => true, "message" => $message];
    }

    private function failure($message) {

        return ["success" => false, "message" => $message];
    }
}

?>
