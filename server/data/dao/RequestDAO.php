<?php

require_once __DIR__ . "/../database/database.php";

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
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                ) AS maxSuperviseesAllowed
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

    public function getApplicationsBySupervisor(
        $supervisorID,
        $status,
        $search,
        $programme,
        $limit,
        $offset
    ) {

        $conditions = [
            "ARQ.supervisorID = :supervisorID"
        ];

        $params = [
            ":supervisorID" => $supervisorID
        ];

        if ($status !== "") {

            $conditions[] = "ARQ.decisionStatus = :status";
            $params[":status"] = $status;
        }

        if ($search !== "") {

            $conditions[] = "(U.fullName LIKE :search OR ARQ.studentID LIKE :search OR ARQ.projectTitle LIKE :search)";
            $params[":search"] = "%" . $search . "%";
        }

        if ($programme !== "") {

            $conditions[] = "SP.programme = :programme";
            $params[":programme"] = $programme;
        }

        $query = "
            SELECT
                ARQ.requestID,
                ARQ.studentID,
                ARQ.projectTitle,
                ARQ.decisionStatus,
                ARQ.applicationDate,
                ARQ.ttlExpirationTimestamp,
                U.fullName,
                SP.programme
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY ARQ.applicationDate DESC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $this->conn->prepare($query);

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);
        }

        $statement->bindValue(":limit", $limit, PDO::PARAM_INT);
        $statement->bindValue(":offset", $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countApplicationsBySupervisor(
        $supervisorID,
        $status,
        $search,
        $programme
    ) {

        $conditions = [
            "ARQ.supervisorID = :supervisorID"
        ];

        $params = [
            ":supervisorID" => $supervisorID
        ];

        if ($status !== "") {

            $conditions[] = "ARQ.decisionStatus = :status";
            $params[":status"] = $status;
        }

        if ($search !== "") {

            $conditions[] = "(U.fullName LIKE :search OR ARQ.studentID LIKE :search OR ARQ.projectTitle LIKE :search)";
            $params[":search"] = "%" . $search . "%";
        }

        if ($programme !== "") {

            $conditions[] = "SP.programme = :programme";
            $params[":programme"] = $programme;
        }

        $query = "
            SELECT COUNT(*)
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            WHERE " . implode(" AND ", $conditions) . "
        ";

        $statement = $this->conn->prepare($query);

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function getStudentProgrammesForSupervisor(
        $supervisorID
    ) {

        $query = "
            SELECT DISTINCT SP.programme
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            WHERE ARQ.supervisorID = :supervisorID
            ORDER BY SP.programme
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":supervisorID", $supervisorID);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getApplicationRequestForSupervisor(
        $requestID,
        $supervisorID
    ) {

        $query = "
            SELECT
                ARQ.requestID,
                ARQ.studentID,
                ARQ.supervisorID,
                ARQ.projectTitle,
                ARQ.proposalPDFPath,
                ARQ.applicationDate,
                ARQ.ttlExpirationTimestamp,
                ARQ.decisionStatus,
                ARQ.supervisorComment,
                U.fullName,
                SP.programme
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            WHERE ARQ.requestID = :requestID
            AND ARQ.supervisorID = :supervisorID
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":requestID", $requestID, PDO::PARAM_INT);
        $statement->bindParam(":supervisorID", $supervisorID);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRequestDecision(
        $requestID,
        $supervisorID,
        $decisionStatus,
        $supervisorComment
    ) {

        $query = "
            UPDATE APPLICATION_REQUEST
            SET decisionStatus = :decisionStatus,
                supervisorComment = :supervisorComment
            WHERE requestID = :requestID
            AND supervisorID = :supervisorID
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":decisionStatus", $decisionStatus);
        $statement->bindParam(":supervisorComment", $supervisorComment);
        $statement->bindParam(":requestID", $requestID, PDO::PARAM_INT);
        $statement->bindParam(":supervisorID", $supervisorID);

        return $statement->execute() && $statement->rowCount() > 0;
    }

    public function processSupervisorDecision(
        $requestID,
        $supervisorID,
        $decisionStatus,
        $supervisorComment
    ) {

        try {

            $this->conn->beginTransaction();

            $requestQuery = "
                SELECT
                    ARQ.requestID,
                    ARQ.studentID,
                    ARQ.supervisorID,
                    ARQ.decisionStatus
                FROM APPLICATION_REQUEST ARQ
                WHERE ARQ.requestID = :requestID
                AND ARQ.supervisorID = :supervisorID
                FOR UPDATE
            ";

            $requestStatement = $this->conn->prepare($requestQuery);
            $requestStatement->bindParam(":requestID", $requestID, PDO::PARAM_INT);
            $requestStatement->bindParam(":supervisorID", $supervisorID);
            $requestStatement->execute();

            $request = $requestStatement->fetch(PDO::FETCH_ASSOC);

            if (!$request) {

                $this->conn->rollBack();

                return [
                    "success" => false,
                    "message" => "The selected request was not found."
                ];
            }

            if ($request["decisionStatus"] !== "Pending") {

                $this->conn->rollBack();

                return [
                    "success" => false,
                    "message" => "Decision already processed for this request."
                ];
            }

            if ($decisionStatus === "Accepted") {

                $allocationQuery = "
                    SELECT COUNT(*)
                    FROM ALLOCATION_RECORD
                    WHERE studentID = :studentID
                ";

                $allocationStatement = $this->conn->prepare($allocationQuery);
                $allocationStatement->bindParam(":studentID", $request["studentID"]);
                $allocationStatement->execute();

                if ((int) $allocationStatement->fetchColumn() > 0) {

                    $this->conn->rollBack();

                    return [
                        "success" => false,
                        "message" => "Decision blocked: this student already has an allocated supervisor."
                    ];
                }

                $capacityQuery = "
                    SELECT
                        COALESCE(SP.assignedQuotaLimit, QC.maxSuperviseesAllowed) AS quotaLimit,
                        COUNT(DISTINCT AR.allocationID) AS currentSupervisees
                    FROM SUPERVISOR_PROFILE SP
                    INNER JOIN QUOTA_CONFIGURATION QC
                        ON SP.quotaID = QC.quotaID
                    LEFT JOIN ALLOCATION_RECORD AR
                        ON SP.supervisorID = AR.supervisorID
                    WHERE SP.supervisorID = :supervisorID
                    GROUP BY
                        SP.supervisorID,
                        SP.assignedQuotaLimit,
                        QC.maxSuperviseesAllowed
                ";

                $capacityStatement = $this->conn->prepare($capacityQuery);
                $capacityStatement->bindParam(":supervisorID", $supervisorID);
                $capacityStatement->execute();

                $capacity = $capacityStatement->fetch(PDO::FETCH_ASSOC);

                if (
                    !$capacity ||
                    (int) $capacity["currentSupervisees"] >= (int) $capacity["quotaLimit"]
                ) {

                    $this->conn->rollBack();

                    return [
                        "success" => false,
                        "message" => "Decision blocked: supervisor quota is already full."
                    ];
                }
            }

            $updateQuery = "
                UPDATE APPLICATION_REQUEST
                SET decisionStatus = :decisionStatus,
                    supervisorComment = :supervisorComment
                WHERE requestID = :requestID
                AND supervisorID = :supervisorID
            ";

            $updateStatement = $this->conn->prepare($updateQuery);
            $updateStatement->bindParam(":decisionStatus", $decisionStatus);
            $updateStatement->bindParam(":supervisorComment", $supervisorComment);
            $updateStatement->bindParam(":requestID", $requestID, PDO::PARAM_INT);
            $updateStatement->bindParam(":supervisorID", $supervisorID);
            $updateStatement->execute();

            if ($decisionStatus === "Accepted") {

                $insertQuery = "
                    INSERT INTO ALLOCATION_RECORD
                    (
                        studentID,
                        supervisorID,
                        allocationDate,
                        allocationMethod
                    )
                    VALUES
                    (
                        :studentID,
                        :supervisorID,
                        NOW(),
                        'Supervisor Decision'
                    )
                ";

                $insertStatement = $this->conn->prepare($insertQuery);
                $insertStatement->bindParam(":studentID", $request["studentID"]);
                $insertStatement->bindParam(":supervisorID", $supervisorID);
                $insertStatement->execute();
            }

            $this->conn->commit();

            return [
                "success" => true,
                "message" => "Decision Updated - Student request has been " . strtolower($decisionStatus) . "."
            ];

        } catch (Exception $exception) {

            if ($this->conn->inTransaction()) {

                $this->conn->rollBack();
            }

            return [
                "success" => false,
                "message" => "Decision could not be saved. Please try again."
            ];
        }
    }

    public function countPendingRequestsByStudent(
        $studentID
    ) {

        $query = "
            SELECT COUNT(*)
            FROM APPLICATION_REQUEST
            WHERE studentID = :studentID
            AND decisionStatus = 'Pending'
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":studentID",
            $studentID
        );

        $statement->execute();

        return
            (int) $statement->fetchColumn();
    }

    public function getSuperviseesBySupervisor(
        $supervisorID,
        $limit,
        $offset
    ) {

        $query = "
            SELECT
                AR.allocationID,
                AR.studentID,
                AR.allocationDate,
                U.fullName,
                SP.programme,
                ARQ.requestID,
                COALESCE(ARQ.projectTitle, 'Assigned Supervision') AS projectTitle,
                COALESCE(ARQ.decisionStatus, 'Active') AS decisionStatus
            FROM ALLOCATION_RECORD AR
            INNER JOIN STUDENT_PROFILE SP
                ON AR.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            LEFT JOIN APPLICATION_REQUEST ARQ
                ON ARQ.studentID = AR.studentID
                AND ARQ.supervisorID = AR.supervisorID
                AND ARQ.decisionStatus = 'Accepted'
            WHERE AR.supervisorID = :supervisorID
            ORDER BY AR.allocationDate DESC,
                AR.allocationID DESC
            LIMIT :limit OFFSET :offset
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":supervisorID", $supervisorID);
        $statement->bindParam(":limit", $limit, PDO::PARAM_INT);
        $statement->bindParam(":offset", $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSuperviseesBySupervisor(
        $supervisorID
    ) {

        return $this->countActiveSupervisees($supervisorID);
    }

    public function getRecentApplicationsByStudent(
        $studentID,
        $limit
    ) {

        $query = "
            SELECT
                ARQ.requestID,
                ARQ.supervisorID,
                ARQ.projectTitle,
                ARQ.decisionStatus,
                ARQ.applicationDate,
                ARQ.ttlExpirationTimestamp,
                U.fullName AS supervisorName,
                SP.employmentCategory,
                SP.programme
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN SUPERVISOR_PROFILE SP
                ON ARQ.supervisorID = SP.supervisorID
            INNER JOIN USER U
                ON SP.supervisorID = U.userID
            WHERE ARQ.studentID = :studentID
            ORDER BY ARQ.applicationDate DESC
            LIMIT :limit
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":studentID",
            $studentID
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

    public function getAllocationByStudent(
        $studentID
    ) {

        $query = "
            SELECT
                AR.allocationID,
                AR.supervisorID,
                AR.allocationDate,
                AR.allocationMethod,
                U.fullName AS supervisorName,
                SP.employmentCategory
            FROM ALLOCATION_RECORD AR
            INNER JOIN SUPERVISOR_PROFILE SP
                ON AR.supervisorID = SP.supervisorID
            INNER JOIN USER U
                ON SP.supervisorID = U.userID
            WHERE AR.studentID = :studentID
            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":studentID",
            $studentID
        );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }

    public function studentHasActiveRequest(
        $studentID
    ) {

        $query = "
            SELECT COUNT(*)
            FROM APPLICATION_REQUEST
            WHERE studentID = :studentID
            AND decisionStatus = 'Pending'
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":studentID",
            $studentID
        );

        $statement->execute();

        return
            ((int) $statement->fetchColumn()) > 0;
    }

    public function createApplicationRequest(
        $studentID,
        $supervisorID,
        $projectTitle,
        $proposalPDFPath
    ) {

        $query = "
            INSERT INTO APPLICATION_REQUEST
            (
                studentID,
                supervisorID,
                projectTitle,
                proposalPDFPath,
                applicationDate,
                ttlExpirationTimestamp,
                decisionStatus
            )
            VALUES
            (
                :studentID,
                :supervisorID,
                :projectTitle,
                :proposalPDFPath,
                NOW(),
                DATE_ADD(NOW(), INTERVAL 7 DAY),
                'Pending'
            )
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":studentID",
            $studentID
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->bindParam(
            ":projectTitle",
            $projectTitle
        );

        $statement->bindParam(
            ":proposalPDFPath",
            $proposalPDFPath
        );

        return
            $statement->execute();
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


