<?php

require_once __DIR__ . "/../database/database.php";

/*
|--------------------------------------------------------------------------
| Supervisor Report DAO
|--------------------------------------------------------------------------
| Data access layer for supervisor analytics reports. It reads from the
| application request, supervisor profile, quota, and allocation tables
| described by the supervisor report.
*/

class SupervisorReportDAO {

    private $conn;

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $database =
            new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Selection Period Start Timestamp
    |--------------------------------------------------------------------------
    | Finds the system-wide selection period opening timestamp for utilization
    | calculations. Falls back to the earliest configured phase if no phase name
    | explicitly contains "Selection".
    */

    public function getSelectionPeriodStartTimestamp() {

        $query = "
            SELECT startTimestamp
            FROM SYSTEM_PHASE_TIMELINE
            WHERE phaseName LIKE '%Selection%'
            ORDER BY startTimestamp ASC
            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->execute();

        $startTimestamp =
            $statement->fetchColumn();

        if ($startTimestamp) {

            return $startTimestamp;
        }

        $fallbackQuery = "
            SELECT MIN(startTimestamp)
            FROM SYSTEM_PHASE_TIMELINE
        ";

        $fallbackStatement =
            $this->conn->prepare(
                $fallbackQuery
            );

        $fallbackStatement->execute();

        return
            $fallbackStatement->fetchColumn()
            ?: "";
    }

    /*
    |--------------------------------------------------------------------------
    | Applicant Demographics Queries
    |--------------------------------------------------------------------------
    | Supports UC600 by counting allocated supervisees by programme and listing
    | available filter years/expertise tag matches.
    */

    public function getApplicantProgrammes(
        $supervisorID,
        $year
    ) {

        $conditions = [
            "AR.supervisorID = :supervisorID"
        ];

        if ($year !== "") {

            $conditions[] = "YEAR(AR.allocationDate) = :year";
        }

        $query = "
            SELECT
                SP.programme,
                COUNT(*) AS totalApplicants
            FROM ALLOCATION_RECORD AR
            INNER JOIN STUDENT_PROFILE SP
                ON AR.studentID = SP.studentID
            WHERE " . implode(" AND ", $conditions) . "
            GROUP BY SP.programme
            ORDER BY totalApplicants DESC,
                SP.programme ASC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        if ($year !== "") {

            $statement->bindValue(
                ":year",
                (int) $year,
                PDO::PARAM_INT
            );
        }

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Applicant Year Filter Options
    |--------------------------------------------------------------------------
    */

    public function getApplicantYears(
        $supervisorID
    ) {

        $query = "
            SELECT DISTINCT YEAR(allocationDate) AS applicationYear
            FROM ALLOCATION_RECORD
            WHERE supervisorID = :supervisorID
            ORDER BY applicationYear DESC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_COLUMN
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Matched Expertise Tag Counts
    |--------------------------------------------------------------------------
    */

    public function getMatchedExpertiseTags(
        $supervisorID,
        $year = ""
    ) {

        $applicationJoinFilter =
            $year !== ""
            ? "AND YEAR(AR.allocationDate) = :year"
            : "";

        $query = "
            SELECT
                RT.tagName,
                COUNT(DISTINCT AR.studentID) AS interestedStudents
            FROM SUPERVISOR_TAG_SELECTION SVT
            INNER JOIN RESEARCH_TAG RT
                ON SVT.tagID = RT.tagID
            LEFT JOIN STUDENT_TAG_SELECTION STT
                ON SVT.tagID = STT.tagID
            LEFT JOIN ALLOCATION_RECORD AR
                ON STT.studentID = AR.studentID
                AND AR.supervisorID = :supervisorID
                {$applicationJoinFilter}
            WHERE SVT.supervisorID = :supervisorID
            GROUP BY RT.tagName
            ORDER BY interestedStudents DESC,
                RT.tagName ASC
            LIMIT 6
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        if ($year !== "") {

            $statement->bindValue(
                ":year",
                (int) $year,
                PDO::PARAM_INT
            );
        }

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Primary Supervisor Expertise
    |--------------------------------------------------------------------------
    | Reads the supervisor's selected expertise tags for the history summary
    | card without exposing other supervisors' profile data.
    */

    public function getPrimaryExpertiseTag(
        $supervisorID
    ) {

        $query = "
            SELECT RT.tagName
            FROM SUPERVISOR_TAG_SELECTION SVT
            INNER JOIN RESEARCH_TAG RT
                ON SVT.tagID = RT.tagID
            WHERE SVT.supervisorID = :supervisorID
            ORDER BY RT.tagName ASC
            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->execute();

        $tagName =
            $statement->fetchColumn();

        return
            $tagName
            ? $tagName
            : "No expertise tag selected";
    }

    /*
    |--------------------------------------------------------------------------
    | Supervision History Queries
    |--------------------------------------------------------------------------
    | Supports UC601 by fetching actual supervised student assignments from
    | the allocation store. Past projects are only a public showcase, not the
    | authoritative supervision history.
    */

    public function getSupervisionHistory(
        $supervisorID,
        $year
    ) {

        $conditions = [
            "AR.supervisorID = :supervisorID"
        ];

        if ($year !== "") {

            $conditions[] = "YEAR(AR.allocationDate) = :completionYear";
        }

        $query = "
            SELECT
                AR.allocationID,
                AR.studentID,
                U.fullName AS alumniName,
                SP.programme,
                SP.currentSem,
                YEAR(AR.allocationDate) AS completionYear,
                AR.allocationDate,
                AR.allocationMethod,
                COALESCE(ARQ.projectTitle, 'Assigned Supervision') AS projectTitle,
                COALESCE(ARQ.decisionStatus, 'Not Submitted') AS proposalStatus,
                CASE
                    WHEN AR.allocationMethod = 'System Auto-Match'
                        THEN 'Auto-Allocated'
                    WHEN AR.allocationMethod IN ('Approved Request', 'Supervisor Decision')
                        THEN 'Accepted'
                    ELSE 'Active'
                END AS statusLabel
            FROM ALLOCATION_RECORD AR
            INNER JOIN STUDENT_PROFILE SP
                ON AR.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            LEFT JOIN APPLICATION_REQUEST ARQ
                ON ARQ.requestID = (
                    SELECT MAX(LatestARQ.requestID)
                    FROM APPLICATION_REQUEST LatestARQ
                    WHERE LatestARQ.studentID = AR.studentID
                    AND LatestARQ.supervisorID = AR.supervisorID
                )
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY AR.allocationDate DESC,
                AR.allocationID DESC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        if ($year !== "") {

            $statement->bindValue(
                ":completionYear",
                (int) $year,
                PDO::PARAM_INT
            );
        }

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | History Year Filter Options
    |--------------------------------------------------------------------------
    */

    public function getHistoryYears(
        $supervisorID
    ) {

        $query = "
            SELECT DISTINCT YEAR(allocationDate) AS completionYear
            FROM ALLOCATION_RECORD
            WHERE supervisorID = :supervisorID
            ORDER BY completionYear DESC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_COLUMN
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Slot Utilization Query
    |--------------------------------------------------------------------------
    | Supports UC602 by joining supervisor quota configuration with current
    | allocation counts.
    */

    public function getSlotUtilization(
        $supervisorID
    ) {

        $query = "
            SELECT
                SP.supervisorID,
                SP.programme,
                QC.quotaTierName,
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                ) AS maxSuperviseesAllowed,
                COUNT(DISTINCT AR.allocationID) AS currentSlots
            FROM SUPERVISOR_PROFILE SP
            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID
            WHERE SP.supervisorID = :supervisorID
            GROUP BY
                SP.supervisorID,
                SP.programme,
                QC.quotaTierName,
                SP.assignedQuotaLimit,
                QC.maxSuperviseesAllowed
            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Department Benchmark Query
    |--------------------------------------------------------------------------
    | Calculates anonymized aggregate fill rate for all active supervisors.
    */

    public function getDepartmentAverageFillRate() {

        $query = "
            SELECT AVG(fillRate) AS averageFillRate
            FROM (
                SELECT
                    SP.supervisorID,
                    CASE
                        WHEN COALESCE(SP.assignedQuotaLimit, QC.maxSuperviseesAllowed) = 0
                        THEN 0
                        ELSE
                            COUNT(DISTINCT AR.allocationID)
                            /
                            COALESCE(SP.assignedQuotaLimit, QC.maxSuperviseesAllowed)
                            *
                            100
                    END AS fillRate
                FROM SUPERVISOR_PROFILE SP
                INNER JOIN USER U
                    ON SP.supervisorID = U.userID
                INNER JOIN QUOTA_CONFIGURATION QC
                    ON SP.quotaID = QC.quotaID
                LEFT JOIN ALLOCATION_RECORD AR
                    ON SP.supervisorID = AR.supervisorID
                WHERE U.systemRole = 'Supervisor'
                AND U.activeStatus = TRUE
                GROUP BY
                    SP.supervisorID,
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
            ) SupervisorFillRates
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->execute();

        return
            (float) $statement->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Personal Weekly Trend
    |--------------------------------------------------------------------------
    | Counts this supervisor's recent allocation activity by weekday.
    */

    public function getWeeklyAllocationTrend(
        $supervisorID
    ) {

        $periodStart =
            $this->getSelectionPeriodStartTimestamp();

        $query = "
            SELECT
                DAYOFWEEK(allocationDate) AS weekdayNumber,
                COUNT(*) AS personalTotal
            FROM ALLOCATION_RECORD
            WHERE supervisorID = :supervisorID
            AND allocationDate >= :periodStart
            GROUP BY DAYOFWEEK(allocationDate)
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->bindValue(
            ":periodStart",
            $periodStart !== "" ? $periodStart : date("Y-m-d 00:00:00", strtotime("-6 days"))
        );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Department Weekly Benchmark
    |--------------------------------------------------------------------------
    | Includes zero-activity supervisors so the benchmark reflects the whole
    | active department rather than only supervisors with allocations.
    */

    public function getDepartmentWeeklyAverageTrend() {

        $periodStart =
            $this->getSelectionPeriodStartTimestamp();

        $query = "
            SELECT
                Weekdays.weekdayNumber,
                AVG(
                    COALESCE(
                        SupervisorDailyTotals.supervisorTotal,
                        0
                    )
                ) AS departmentAverage
            FROM (
                SELECT 1 AS weekdayNumber
                UNION ALL SELECT 2
                UNION ALL SELECT 3
                UNION ALL SELECT 4
                UNION ALL SELECT 5
                UNION ALL SELECT 6
                UNION ALL SELECT 7
            ) Weekdays
            CROSS JOIN (
                SELECT SP.supervisorID
                FROM SUPERVISOR_PROFILE SP
                INNER JOIN USER U
                    ON SP.supervisorID = U.userID
                WHERE U.systemRole = 'Supervisor'
                AND U.activeStatus = TRUE
            ) ActiveSupervisors
            LEFT JOIN (
                SELECT
                    supervisorID,
                    DAYOFWEEK(allocationDate) AS weekdayNumber,
                    COUNT(*) AS supervisorTotal
                FROM ALLOCATION_RECORD
                WHERE allocationDate >= :periodStart
                GROUP BY
                    supervisorID,
                    DAYOFWEEK(allocationDate)
            ) SupervisorDailyTotals
                ON ActiveSupervisors.supervisorID = SupervisorDailyTotals.supervisorID
                AND Weekdays.weekdayNumber = SupervisorDailyTotals.weekdayNumber
            GROUP BY Weekdays.weekdayNumber
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindValue(
            ":periodStart",
            $periodStart !== "" ? $periodStart : date("Y-m-d 00:00:00", strtotime("-6 days"))
        );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

}

?>
