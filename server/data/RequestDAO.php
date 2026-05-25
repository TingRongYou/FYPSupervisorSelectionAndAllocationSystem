<?php

require_once __DIR__ . "/database.php";

class RequestDAO {

    private $conn;

    public function __construct() {

        $database =
            new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Count Pending Requests For Supervisor
    |--------------------------------------------------------------------------
    */

    public function countPendingRequestsBySupervisor(
        $supervisorID
    ) {

        $query = "
            SELECT COUNT(*)
            FROM APPLICATION_REQUEST
            WHERE supervisorID = :supervisorID
            AND decisionStatus = 'Pending'
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
            (int) $statement->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Count Active Supervisees
    |--------------------------------------------------------------------------
    */

    public function countActiveSupervisees(
        $supervisorID
    ) {

        $query = "
            SELECT COUNT(*)
            FROM ALLOCATION_RECORD
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

        return
            (int) $statement->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Supervisor Quota Limit
    |--------------------------------------------------------------------------
    */

    public function getSupervisorQuotaLimit(
        $supervisorID
    ) {

        $query = "
            SELECT
                QC.maxSuperviseesAllowed
            FROM SUPERVISOR_PROFILE SP
            INNER JOIN QUOTA_CONFIGURATION QC
                ON SP.quotaID = QC.quotaID
            WHERE SP.supervisorID = :supervisorID
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
            (int) $statement->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Student Applications For Supervisor
    |--------------------------------------------------------------------------
    | Retrieves only applications submitted to the authenticated supervisor.
    */

    public function getRecentApplicationsBySupervisor(
        $supervisorID,
        $limit
    ) {

        $query = "
            SELECT
                ARQ.requestID,
                ARQ.studentID,
                ARQ.projectTitle,
                ARQ.decisionStatus,
                ARQ.applicationDate,
                U.fullName,
                SP.programme
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            WHERE ARQ.supervisorID = :supervisorID
            ORDER BY ARQ.applicationDate DESC
            LIMIT :limit
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
            ":limit",
            $limit,
            PDO::PARAM_INT
        );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Active Phase Lookup
    |--------------------------------------------------------------------------
    */

    public function getActiveSystemPhase() {

        $query = "
            SELECT
                phaseID,
                phaseName,
                startTimestamp,
                endTimestamp
            FROM SYSTEM_PHASE_TIMELINE
            WHERE NOW() BETWEEN startTimestamp AND endTimestamp
            ORDER BY endTimestamp ASC
            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }
}

?>
