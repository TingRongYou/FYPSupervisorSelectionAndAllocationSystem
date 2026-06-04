<?php

require_once __DIR__ . "/../database/database.php";

class AdminReportDAO {

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
    | Filter Option Queries
    |--------------------------------------------------------------------------
    | These methods feed the report filter dropdowns from live system data.
    */

    public function getProgrammeOptions() {

        $query = "
            SELECT DISTINCT programme
            FROM STUDENT_PROFILE
            WHERE programme IS NOT NULL
            AND programme != ''
            ORDER BY programme ASC
        ";

        $statement =
            $this->conn->prepare($query);

        $statement->execute();

        return
            $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBatchOptions() {

        $query = "
            SELECT DISTINCT intakeBatch
            FROM STUDENT_PROFILE
            WHERE intakeBatch IS NOT NULL
            AND intakeBatch != ''
            ORDER BY intakeBatch DESC
        ";

        $statement =
            $this->conn->prepare($query);

        $statement->execute();

        return
            $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSpecializationOptions() {

        $query = "
            SELECT DISTINCT RT.tagName AS specialization
            FROM RESEARCH_TAG RT
            INNER JOIN STUDENT_TAG_SELECTION STS
                ON RT.tagID = STS.tagID
            ORDER BY RT.tagName ASC
        ";

        $statement =
            $this->conn->prepare($query);

        $statement->execute();

        return
            $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Cohort Overview Data
    |--------------------------------------------------------------------------
    | Retrieves active student records and joins allocation/specialization data
    | so the report can show assigned and unassigned students in one roster.
    */

    public function fetchStudents($filters = []) {

        $params = [];

        $query = "
            SELECT
                SP.studentID,
                U.fullName,
                U.universityEmail,
                U.profilePhotoPath,
                SP.programme,
                SP.intakeBatch,
                SP.currentSem,
                SP.academicStatus,
                SP.cgpa,
                SP.eligibilityStatus,
                AR.allocationID,
                AR.allocationMethod,
                AR.allocationDate,
                SU.fullName AS supervisorName,
                AR.supervisorID,
                GROUP_CONCAT(DISTINCT RT.tagName ORDER BY RT.tagName SEPARATOR ', ') AS specializations
            FROM STUDENT_PROFILE SP
            INNER JOIN USER U
                ON SP.studentID = U.userID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.studentID = AR.studentID
            LEFT JOIN USER SU
                ON AR.supervisorID = SU.userID
            LEFT JOIN STUDENT_TAG_SELECTION STS
                ON SP.studentID = STS.studentID
            LEFT JOIN RESEARCH_TAG RT
                ON STS.tagID = RT.tagID
            WHERE U.systemRole = 'Student'
            AND U.activeStatus = TRUE
        ";

        // Apply the DFD-supported administrator filters only when selected.
        if (($filters["programme"] ?? "") !== "") {
            $query .= " AND SP.programme = :programme";
            $params[":programme"] = $filters["programme"];
        }

        if (($filters["batch"] ?? "") !== "") {
            $query .= " AND SP.intakeBatch = :batch";
            $params[":batch"] = $filters["batch"];
        }

        if (($filters["specialization"] ?? "") !== "") {
            $query .= "
                AND EXISTS (
                    SELECT 1
                    FROM STUDENT_TAG_SELECTION FSTS
                    INNER JOIN RESEARCH_TAG FRT
                        ON FSTS.tagID = FRT.tagID
                    WHERE FSTS.studentID = SP.studentID
                    AND FRT.tagName = :specialization
                )
            ";
            $params[":specialization"] = $filters["specialization"];
        }

        if (($filters["status"] ?? "") === "assigned") {
            $query .= " AND AR.allocationID IS NOT NULL";
        }

        if (($filters["status"] ?? "") === "unassigned") {
            $query .= " AND AR.allocationID IS NULL";
        }

        // GROUP BY is required because a student can have multiple tag rows.
        $query .= "
            GROUP BY
                SP.studentID,
                U.fullName,
                U.universityEmail,
                U.profilePhotoPath,
                SP.programme,
                SP.intakeBatch,
                SP.currentSem,
                SP.academicStatus,
                SP.cgpa,
                SP.eligibilityStatus,
                AR.allocationID,
                AR.allocationMethod,
                AR.allocationDate,
                SU.fullName,
                AR.supervisorID
            ORDER BY
                SP.intakeBatch DESC,
                SP.programme ASC,
                U.fullName ASC
        ";

        $statement =
            $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->execute();

        return
            $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Allocation Summary Data
    |--------------------------------------------------------------------------
    | Retrieves supervisor quota capacity and current allocation counts for
    | workload monitoring.
    */

    public function fetchSupervisors($programme = "") {

        $query = "
            SELECT
                SP.supervisorID,
                U.fullName,
                U.profilePhotoPath,
                SP.programme,
                SP.employmentCategory,
                SP.activeTime,
                QC.quotaTierName,
                COALESCE(SP.assignedQuotaLimit, QC.maxSuperviseesAllowed) AS maxSuperviseesAllowed,
                COUNT(DISTINCT AR.allocationID) AS currentTotal,
                MAX(AR.allocationDate) AS lastAllocationDate
            FROM SUPERVISOR_PROFILE SP
            INNER JOIN USER U
                ON SP.supervisorID = U.userID
            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID
            WHERE U.systemRole = 'Supervisor'
            AND U.activeStatus = TRUE
        ";

        if ($programme !== "") {
            $query .= " AND SP.programme = :programme";
        }

        $query .= "
            GROUP BY
                SP.supervisorID,
                U.fullName,
                U.profilePhotoPath,
                SP.programme,
                SP.employmentCategory,
                SP.activeTime,
                QC.quotaTierName,
                SP.assignedQuotaLimit,
                QC.maxSuperviseesAllowed
            ORDER BY
                currentTotal DESC,
                U.fullName ASC
        ";

        $statement =
            $this->conn->prepare($query);

        if ($programme !== "") {
            $statement->bindValue(":programme", $programme);
        }

        $statement->execute();

        return
            $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Pending Request Count
    |--------------------------------------------------------------------------
    | Counts pending proposal requests for the summary KPI card.
    */

    public function countPendingRequests($programme = "") {

        $query = "
            SELECT COUNT(*) AS pendingTotal
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            WHERE ARQ.decisionStatus = 'Pending'
        ";

        if ($programme !== "") {
            $query .= " AND SP.programme = :programme";
        }

        $statement =
            $this->conn->prepare($query);

        if ($programme !== "") {
            $statement->bindValue(":programme", $programme);
        }

        $statement->execute();

        $row =
            $statement->fetch(PDO::FETCH_ASSOC);

        return
            (int) ($row["pendingTotal"] ?? 0);
    }
}

?>
