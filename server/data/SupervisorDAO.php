<?php

require_once "database.php";

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

        $database =
            new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Quota By ID
    |--------------------------------------------------------------------------
    */

    public function getQuotaByID(
        $quotaID
    ) {

        $query = "
            SELECT
                quotaID,
                quotaTierName,
                maxSuperviseesAllowed
            FROM QUOTA_CONFIGURATION
            WHERE quotaID = :quotaID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":quotaID",
            $quotaID,
            PDO::PARAM_INT
        );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Supervisor Profile
    |--------------------------------------------------------------------------
    */

    public function createSupervisorProfile(
        $supervisorID,
        $quotaID,
        $employmentCategory,
        $programme
    ) {

        $query = "
            INSERT INTO SUPERVISOR_PROFILE
            (
                supervisorID,
                quotaID,
                employmentCategory,
                programme
            )
            VALUES
            (
                :supervisorID,
                :quotaID,
                :employmentCategory,
                :programme
            )
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->bindParam(
            ":quotaID",
            $quotaID,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":employmentCategory",
            $employmentCategory
        );

        $statement->bindParam(
            ":programme",
            $programme
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Distinct Programmes
    |--------------------------------------------------------------------------
    */

    public function getSupervisorProgrammes() {

        $query = "
            SELECT DISTINCT programme
            FROM SUPERVISOR_PROFILE
            WHERE programme IS NOT NULL
            AND programme != ''
            ORDER BY programme ASC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Supervisor Discovery Query
    |--------------------------------------------------------------------------
    */

    public function getSupervisorsForDiscovery(
        $searchName,
        $programme,
        $availability
    ) {

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

                SP.programme,

                SP.employmentCategory,

                SP.introVideoLink,

                QC.quotaTierName,

                QC.maxSuperviseesAllowed,

                COUNT(
                    DISTINCT AR.allocationID
                ) AS activeStudents

            FROM USER U

            INNER JOIN SUPERVISOR_PROFILE SP
                ON U.userID = SP.supervisorID

            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID

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

                SP.programme,

                SP.employmentCategory,

                SP.introVideoLink,

                QC.quotaTierName,

                QC.maxSuperviseesAllowed
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
                QC.maxSuperviseesAllowed
            ";

        } elseif ($availability === "Full") {

            $query .= "
                HAVING
                COUNT(DISTINCT AR.allocationID)
                >=
                QC.maxSuperviseesAllowed
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

        $statement =
            $this->conn->prepare(
                $query
            );

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

            $statement->bindParam(
                ":searchPattern",
                $searchPattern
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Bind Programme
        |--------------------------------------------------------------------------
        */

        if ($programme !== "") {

            $statement->bindParam(
                ":programme",
                $programme
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Execute
        |--------------------------------------------------------------------------
        */

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
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

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisors For Administrator Management
    |--------------------------------------------------------------------------
    */

    public function getSupervisorsForManagement(
        $searchName,
        $programme
    ) {

        $query = "
            SELECT
                U.userID,
                U.fullName,
                U.universityEmail,
                SP.programme,
                SP.employmentCategory,
                SP.quotaID,
                QC.quotaTierName,
                QC.maxSuperviseesAllowed,
                COUNT(DISTINCT AR.allocationID) AS currentSupervisees
            FROM USER U
            INNER JOIN SUPERVISOR_PROFILE SP
                ON U.userID = SP.supervisorID
            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID
            WHERE U.systemRole = 'Supervisor'
            AND U.activeStatus = TRUE
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
                SP.programme,
                SP.employmentCategory,
                SP.quotaID,
                QC.quotaTierName,
                QC.maxSuperviseesAllowed
            ORDER BY U.fullName ASC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        if ($searchName !== "") {

            $searchPattern =
                "%"
                . $searchName
                . "%";

            $statement->bindParam(
                ":searchPattern",
                $searchPattern
            );
        }

        if ($programme !== "") {

            $statement->bindParam(
                ":programme",
                $programme
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
    | Retrieve Supervisor Load For Classification
    |--------------------------------------------------------------------------
    */

    public function getSupervisorLoad(
        $supervisorID
    ) {

        $query = "
            SELECT
                SP.supervisorID,
                SP.employmentCategory,
                SP.quotaID,
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
                QC.quotaTierName,
                QC.maxSuperviseesAllowed
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
    | Update Supervisor Classification
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorClassification(
        $supervisorID,
        $employmentCategory,
        $quotaID
    ) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                employmentCategory = :employmentCategory,
                quotaID = :quotaID
            WHERE supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":employmentCategory",
            $employmentCategory
        );

        $statement->bindParam(
            ":quotaID",
            $quotaID,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supervisor Quota
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorQuota(
        $supervisorID,
        $quotaID
    ) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET quotaID = :quotaID
            WHERE supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":quotaID",
            $quotaID,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisor Profile
    |--------------------------------------------------------------------------
    */

    public function getSupervisorProfile(
        $supervisorID
    ) {

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

                QC.maxSuperviseesAllowed,

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

                QC.maxSuperviseesAllowed
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
    | Update Digital Business Card
    |--------------------------------------------------------------------------
    */

    public function updateDigitalBusinessCard(
        $supervisorID,
        $programme,
        $employmentCategory,
        $introVideoLink
    ) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                programme = :programme,
                employmentCategory = :employmentCategory,
                introVideoLink = :introVideoLink
            WHERE supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":programme",
            $programme
        );

        $statement->bindParam(
            ":employmentCategory",
            $employmentCategory
        );

        $statement->bindParam(
            ":introVideoLink",
            $introVideoLink
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Introductory Video
    |--------------------------------------------------------------------------
    */

    public function updateIntroVideo(
        $supervisorID,
        $introVideoLink
    ) {

        $query = "
            UPDATE SUPERVISOR_PROFILE
            SET
                introVideoLink = :introVideoLink
            WHERE supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":introVideoLink",
            $introVideoLink
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        return
            $statement->execute();
    }
}

?>
