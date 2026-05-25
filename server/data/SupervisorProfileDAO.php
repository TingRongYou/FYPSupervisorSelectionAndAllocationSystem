<?php

require_once __DIR__ . "/database.php";

class SupervisorProfileDAO {

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

                U.profilePhotoPath,

                SP.programme,

                SP.employmentCategory,

                SP.supervisorBio,

                SP.introVideoLink,

                SP.introVideoDescription,

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

            AND U.systemRole = 'Supervisor'

            AND U.activeStatus = TRUE

            GROUP BY

                U.userID,

                U.fullName,

                U.universityEmail,

                U.profilePhotoPath,

                SP.programme,

                SP.employmentCategory,

                SP.supervisorBio,

                SP.introVideoLink,

                SP.introVideoDescription,

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
        $introVideoLink,
        $supervisorBio
    ) {

        $query = "

            UPDATE SUPERVISOR_PROFILE

            SET

                programme = :programme,

                employmentCategory = :employmentCategory,

                introVideoLink = :introVideoLink,

                supervisorBio = :supervisorBio

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
            ":supervisorBio",
            $supervisorBio
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
        $introVideoLink,
        $introVideoDescription
    ) {

        $query = "

            UPDATE SUPERVISOR_PROFILE

            SET
                introVideoLink = :introVideoLink,

                introVideoDescription = :introVideoDescription

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
            ":introVideoDescription",
            $introVideoDescription
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
    | Check Supervisor Profile Exists
    |--------------------------------------------------------------------------
    */

    public function supervisorProfileExists(
        $supervisorID
    ) {

        $query = "

            SELECT COUNT(*) AS total

            FROM SUPERVISOR_PROFILE

            WHERE supervisorID = :supervisorID
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

        $result =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return
            ((int) $result["total"]) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisor Quota Summary
    |--------------------------------------------------------------------------
    */

    public function getSupervisorQuotaSummary(
        $supervisorID
    ) {

        $query = "

            SELECT

                QC.quotaTierName,

                QC.maxSuperviseesAllowed,

                COUNT(
                    DISTINCT AR.allocationID
                ) AS currentSupervisees

            FROM SUPERVISOR_PROFILE SP

            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID

            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.supervisorID = AR.supervisorID

            WHERE SP.supervisorID = :supervisorID

            GROUP BY

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
}

?>
