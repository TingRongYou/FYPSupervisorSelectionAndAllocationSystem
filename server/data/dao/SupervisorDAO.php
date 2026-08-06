<?php

require_once __DIR__ . "/../database/database.php";

class SupervisorDAO {

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $conn;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $database = new Database();

        $this->conn = $database->connect();

        $this->ensureUserActivityStatusTable();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Quota By ID
    |--------------------------------------------------------------------------
    */

    public function getQuotaByID($quotaID) {

        $query = "
            SELECT
                quotaID,
                quotaTierName,
                maxSuperviseesAllowed
            FROM QUOTA_CONFIGURATION
            WHERE quotaID = :quotaID
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":quotaID", $quotaID, PDO::PARAM_INT);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Supervisor Profile
    |--------------------------------------------------------------------------
    */

    public function createSupervisorProfile($supervisorID, $quotaID, $assignedQuotaLimit, $employmentCategory,$programme) {

        $query = "
            INSERT INTO SUPERVISOR_PROFILE
            (
                supervisorID,
                quotaID,
                assignedQuotaLimit,
                employmentCategory,
                programme
            )
            VALUES
            (
                :supervisorID,
                :quotaID,
                :assignedQuotaLimit,
                :employmentCategory,
                :programme
            )
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":supervisorID", $supervisorID);

        $statement->bindParam(":quotaID", $quotaID, PDO::PARAM_INT);

        $statement->bindParam(":assignedQuotaLimit", $assignedQuotaLimit, PDO::PARAM_INT);

        $statement->bindParam(":employmentCategory", $employmentCategory);

        $statement->bindParam(":programme", $programme);

        return $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Distinct Programmes
    |--------------------------------------------------------------------------
    */

    public function getSupervisorProgrammes() {

        $query = "
            SELECT DISTINCT programme
            FROM (
                SELECT programme
                FROM SUPERVISOR_PROFILE
                WHERE programme IS NOT NULL
                AND programme != ''

                UNION

                SELECT programme
                FROM STUDENT_PROFILE
                WHERE programme IS NOT NULL
                AND programme != ''
            ) programmeSource
            ORDER BY programme ASC
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Supervisor Discovery Query
    |--------------------------------------------------------------------------
    */

    // Get supervisor by filtering
    public function getSupervisorsForDiscovery($searchName, $programme, $availability) {

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = "
            SELECT

                U.userID,

                U.fullName,

                U.universityEmail,

                U.profilePhotoPath,

                UAS.lastSeenAt,

                UAS.isOnline,

                SP.programme,

                SP.employmentCategory,

                SP.activeTime,

                SP.introVideoLink,

                QC.quotaTierName,

                QC.maxSuperviseesAllowed AS tierQuotaLimit,

                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                ) AS maxSuperviseesAllowed,

                COUNT(
                    DISTINCT AR.allocationID
                ) AS activeStudents

            FROM USER U

            INNER JOIN SUPERVISOR_PROFILE SP
                ON U.userID = SP.supervisorID

            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID

            LEFT JOIN USER_ACTIVITY_STATUS UAS
                ON U.userID = UAS.userID

            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID

            WHERE U.systemRole = 'Supervisor'

            AND U.activeStatus = TRUE
        ";

        /*
        |--------------------------------------------------------------------------
        | Name Search Filter
        |--------------------------------------------------------------------------
        */

        if ($searchName !== "") {

            $query .= "
                AND U.fullName
                LIKE :searchPattern
            ";
        }

        /*
        |--------------------------------------------------------------------------
        | Programme Filter
        |--------------------------------------------------------------------------
        */

        if ($programme !== "") {

            $query .= "
                AND SP.programme = :programme
            ";
        }

        /*
        |--------------------------------------------------------------------------
        | Grouping
        |--------------------------------------------------------------------------
        */

        $query .= "

            GROUP BY

                U.userID,

                U.fullName,

                U.universityEmail,

                U.profilePhotoPath,

                UAS.lastSeenAt,

                UAS.isOnline,

                SP.programme,

                SP.employmentCategory,

                SP.activeTime,

                SP.introVideoLink,

                QC.quotaTierName,

                QC.maxSuperviseesAllowed,

                SP.assignedQuotaLimit,

                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                )
        ";

        /*
        |--------------------------------------------------------------------------
        | Availability Filter
        |--------------------------------------------------------------------------
        */

        if ($availability === "Available") {

            $query .= "
                HAVING
                COUNT(DISTINCT AR.allocationID)
                <
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                )
            ";

        } elseif ($availability === "Full") {

            $query .= "
                HAVING
                COUNT(DISTINCT AR.allocationID)
                >=
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                )
            ";
        }

        /*
        |--------------------------------------------------------------------------
        | Final Ordering
        |--------------------------------------------------------------------------
        */

        $query .= "
            ORDER BY
                U.fullName ASC
        ";

        /*
        |--------------------------------------------------------------------------
        | Prepare Query
        |--------------------------------------------------------------------------
        */

        $statement = $this->conn->prepare($query);

        /*
        |--------------------------------------------------------------------------
        | Bind Name Search
        |--------------------------------------------------------------------------
        */

        if ($searchName !== "") {

            $searchPattern =
                "%"
                . $searchName
                . "%";

            $statement->bindParam(":searchPattern", $searchPattern);
        }

        /*
        |--------------------------------------------------------------------------
        | Bind Programme
        |--------------------------------------------------------------------------
        */

        if ($programme !== "") {

            $statement->bindParam(":programme", $programme);
        }

        /*
        |--------------------------------------------------------------------------
        | Execute
        |--------------------------------------------------------------------------
        */

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Quota Configurations
    |--------------------------------------------------------------------------
    */

    public function getAllQuotaConfigurations() {

        $query = "
            SELECT
                quotaID,
                quotaTierName,
                maxSuperviseesAllowed
            FROM QUOTA_CONFIGURATION
            ORDER BY maxSuperviseesAllowed DESC,
                     quotaTierName ASC
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisors For Administrator Management
    |--------------------------------------------------------------------------
    */

    public function getSupervisorsForManagement($searchName, $programme) {

        $query = "
            SELECT
                U.userID,
                U.fullName,
                U.universityEmail,
                U.activeStatus,
                U.profilePhotoPath,
                SP.programme,
                SP.employmentCategory,
                SP.quotaID,
                SP.assignedQuotaLimit,
                QC.quotaTierName,
                QC.maxSuperviseesAllowed AS tierQuotaLimit,
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                ) AS maxSuperviseesAllowed,
                COUNT(DISTINCT AR.allocationID) AS currentSupervisees
            FROM USER U
            INNER JOIN SUPERVISOR_PROFILE SP
                ON U.userID = SP.supervisorID
            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID
            WHERE U.systemRole = 'Supervisor'
        ";

        if ($searchName !== "") {

            $query .= "
                AND (
                    U.fullName LIKE :searchPattern
                    OR U.userID LIKE :searchPattern
                )
            ";
        }

        if ($programme !== "") {

            $query .= "
                AND SP.programme = :programme
            ";
        }

        $query .= "
            GROUP BY
                U.userID,
                U.fullName,
                U.universityEmail,
                U.activeStatus,
                U.profilePhotoPath,
                SP.programme,
                SP.employmentCategory,
                SP.quotaID,
                SP.assignedQuotaLimit,
                QC.quotaTierName,
                QC.maxSuperviseesAllowed,
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                )
            ORDER BY U.fullName ASC
        ";

        $statement = $this->conn->prepare($query);

        if ($searchName !== "") {

            $searchPattern =
                "%"
                . $searchName
                . "%";

            $statement->bindParam(":searchPattern", $searchPattern);
        }

        if ($programme !== "") {

            $statement->bindParam(":programme", $programme);
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisor Load For Classification
    |--------------------------------------------------------------------------
    */

    public function getSupervisorLoad($supervisorID) {

        $query = "
            SELECT
                SP.supervisorID,
                SP.employmentCategory,
                SP.quotaID,
                SP.assignedQuotaLimit,
                QC.quotaTierName,
                QC.maxSuperviseesAllowed,
                COUNT(DISTINCT AR.allocationID) AS currentSupervisees
            FROM SUPERVISOR_PROFILE SP
            INNER JOIN USER U
                ON SP.supervisorID = U.userID
            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID
            WHERE SP.supervisorID = :supervisorID
            AND U.systemRole = 'Supervisor'
            GROUP BY
                SP.supervisorID,
                SP.employmentCategory,
                SP.quotaID,
                SP.assignedQuotaLimit,
                QC.quotaTierName,
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                )
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":supervisorID", $supervisorID);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supervisor Classification
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorClassification($supervisorID, $employmentCategory, $quotaID, $assignedQuotaLimit) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                employmentCategory = :employmentCategory,
                quotaID = :quotaID,
                assignedQuotaLimit = :assignedQuotaLimit
            WHERE supervisorID = :supervisorID
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":employmentCategory", $employmentCategory);

        $statement->bindParam(":quotaID", $quotaID,PDO::PARAM_INT);

        $statement->bindParam(":assignedQuotaLimit", $assignedQuotaLimit,PDO::PARAM_INT);

        $statement->bindParam( ":supervisorID", $supervisorID);

        return $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supervisor Quota
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorQuota($supervisorID, $quotaID, $assignedQuotaLimit) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                quotaID = :quotaID,
                assignedQuotaLimit = :assignedQuotaLimit
            WHERE supervisorID = :supervisorID
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":quotaID", $quotaID, PDO::PARAM_INT);

        $statement->bindParam(":assignedQuotaLimit", $assignedQuotaLimit, PDO::PARAM_INT);

        $statement->bindParam(":supervisorID", $supervisorID);

        return $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisor Profile
    |--------------------------------------------------------------------------
    */

    public function getSupervisorProfile($supervisorID) {

        $query = "
            SELECT

                U.userID,

                U.fullName,

                U.universityEmail,

                SP.programme,

                SP.employmentCategory,

                SP.introVideoLink,

                QC.quotaID,

                QC.quotaTierName,

                SP.assignedQuotaLimit,

                QC.maxSuperviseesAllowed AS tierQuotaLimit,

                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                ) AS maxSuperviseesAllowed,

                COUNT(
                    DISTINCT AR.allocationID
                ) AS currentSupervisees

            FROM USER U

            INNER JOIN SUPERVISOR_PROFILE SP
                ON U.userID = SP.supervisorID

            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID

            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID

            WHERE U.userID = :supervisorID

            GROUP BY

                U.userID,

                U.fullName,

                U.universityEmail,

                SP.programme,

                SP.employmentCategory,

                SP.introVideoLink,

                QC.quotaID,

                QC.quotaTierName,

                SP.assignedQuotaLimit,

                QC.maxSuperviseesAllowed
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":supervisorID", $supervisorID);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Digital Business Card
    |--------------------------------------------------------------------------
    */

    public function updateDigitalBusinessCard($supervisorID, $programme, $employmentCategory, $introVideoLink) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                programme = :programme,
                employmentCategory = :employmentCategory,
                introVideoLink = :introVideoLink
            WHERE supervisorID = :supervisorID
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":programme", $programme);

        $statement->bindParam(":employmentCategory",$employmentCategory);

        $statement->bindParam(":introVideoLink", $introVideoLink);

        $statement->bindParam(":supervisorID", $supervisorID);

        return $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Introductory Video
    |--------------------------------------------------------------------------
    */

    public function updateIntroVideo($supervisorID, $introVideoLink) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                introVideoLink = :introVideoLink
            WHERE supervisorID = :supervisorID
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":introVideoLink", $introVideoLink);

        $statement->bindParam(":supervisorID", $supervisorID);

        return $statement->execute();
    }

    private function ensureUserActivityStatusTable() {

        $query = "
            CREATE TABLE IF NOT EXISTS USER_ACTIVITY_STATUS (
                userID VARCHAR(20) PRIMARY KEY,
                systemRole VARCHAR(50) NOT NULL,
                lastSeenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                isOnline BOOLEAN NOT NULL DEFAULT FALSE,

                FOREIGN KEY (userID)
                    REFERENCES USER(userID)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            )
        ";

        $this->conn->exec($query);
    }
}

?>
