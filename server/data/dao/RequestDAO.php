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
            FROM APPLICATION_REQUEST ARQ
            LEFT JOIN ALLOCATION_RECORD ALR
                ON ARQ.studentID = ALR.studentID
                AND ARQ.supervisorID = ALR.supervisorID
                AND ALR.allocationMethod = 'System Auto-Match'
            WHERE ARQ.supervisorID = :supervisorID
            AND ARQ.decisionStatus = 'Pending'
            AND ALR.allocationID IS NULL
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
    | Recent Student Allocations For Supervisor Dashboard
    |--------------------------------------------------------------------------
    | Retrieves finalized supervisees for the authenticated supervisor. Manual
    | acceptances keep their request details, while auto-allocated students are
    | still shown from the allocation record.
    */

    public function getRecentAllocationsBySupervisor(
        $supervisorID,
        $limit,
        $offset = 0,
        $allocationStatus = "",
        $proposalStatus = ""
    ) {

        $having = [];
        $params = [
            ":supervisorID" => $supervisorID
        ];

        if ($allocationStatus !== "") {

            $having[] = "decisionStatus = :allocationStatus";
            $params[":allocationStatus"] = $allocationStatus;
        }

        if ($proposalStatus !== "") {

            if ($proposalStatus === "Not Submitted") {

                $having[] = "proposalStatus IS NULL";
            } else {

                $having[] = "proposalStatus = :proposalStatus";
                $params[":proposalStatus"] = $proposalStatus;
            }
        }

        $query = "
            SELECT
                ALR.allocationID,
                ARQ.requestID,
                ALR.studentID,
                ALR.allocationDate,
                ALR.allocationMethod,
                COALESCE(ARQ.projectTitle, 'Assigned Supervision') AS projectTitle,
                ARQ.decisionStatus AS proposalStatus,
                CASE
                    WHEN ALR.allocationMethod = 'System Auto-Match'
                        THEN 'Auto-Allocated'
                    ELSE COALESCE(ARQ.decisionStatus, 'Allocated')
                END AS decisionStatus,
                U.fullName,
                U.profilePhotoPath,
                SP.programme
            FROM ALLOCATION_RECORD ALR
            INNER JOIN STUDENT_PROFILE SP
                ON ALR.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            LEFT JOIN APPLICATION_REQUEST ARQ
                ON ARQ.studentID = ALR.studentID
                AND ARQ.supervisorID = ALR.supervisorID
                AND ARQ.decisionStatus IN ('Accepted', 'Pending', 'Rejected', 'Proposal Requested')
            WHERE ALR.supervisorID = :supervisorID
            " . (!empty($having) ? "HAVING " . implode(" AND ", $having) : "") . "
            ORDER BY ALR.allocationDate DESC,
                ALR.allocationID DESC
            LIMIT :limit
            OFFSET :offset
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);
        }

        $statement->bindParam(
            ":limit",
            $limit,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":offset",
            (int) $offset,
            PDO::PARAM_INT
        );

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    public function countRecentAllocationsBySupervisor(
        $supervisorID,
        $allocationStatus = "",
        $proposalStatus = ""
    ) {

        $having = [];
        $params = [
            ":supervisorID" => $supervisorID
        ];

        if ($allocationStatus !== "") {

            $having[] = "decisionStatus = :allocationStatus";
            $params[":allocationStatus"] = $allocationStatus;
        }

        if ($proposalStatus !== "") {

            if ($proposalStatus === "Not Submitted") {

                $having[] = "proposalStatus IS NULL";
            } else {

                $having[] = "proposalStatus = :proposalStatus";
                $params[":proposalStatus"] = $proposalStatus;
            }
        }

        $query = "
            SELECT COUNT(*)
            FROM (
                SELECT
                    ALR.allocationID,
                    ARQ.decisionStatus AS proposalStatus,
                    CASE
                        WHEN ALR.allocationMethod = 'System Auto-Match'
                            THEN 'Auto-Allocated'
                        ELSE COALESCE(ARQ.decisionStatus, 'Allocated')
                    END AS decisionStatus
                FROM ALLOCATION_RECORD ALR
                INNER JOIN STUDENT_PROFILE SP
                    ON ALR.studentID = SP.studentID
                INNER JOIN USER U
                    ON SP.studentID = U.userID
                LEFT JOIN APPLICATION_REQUEST ARQ
                    ON ARQ.studentID = ALR.studentID
                    AND ARQ.supervisorID = ALR.supervisorID
                    AND ARQ.decisionStatus IN ('Accepted', 'Pending', 'Rejected', 'Proposal Requested')
                WHERE ALR.supervisorID = :supervisorID
                " . (!empty($having) ? "HAVING " . implode(" AND ", $having) : "") . "
            ) recentAllocations
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);
        }

        $statement->execute();

        return
            (int) $statement->fetchColumn();
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
            "ARQ.supervisorID = :supervisorID",
            "ALR.allocationID IS NULL"
        ];

        $params = [
            ":supervisorID" => $supervisorID
        ];

        if ($status !== "") {

            $conditions[] = "ARQ.decisionStatus = :status";
            $params[":status"] = $status;
        } else {

            $conditions[] = "ARQ.decisionStatus <> 'Proposal Requested'";
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
            LEFT JOIN ALLOCATION_RECORD ALR
                ON ARQ.studentID = ALR.studentID
                AND ARQ.supervisorID = ALR.supervisorID
                AND ALR.allocationMethod = 'System Auto-Match'
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
            "ARQ.supervisorID = :supervisorID",
            "ALR.allocationID IS NULL"
        ];

        $params = [
            ":supervisorID" => $supervisorID
        ];

        if ($status !== "") {

            $conditions[] = "ARQ.decisionStatus = :status";
            $params[":status"] = $status;
        } else {

            $conditions[] = "ARQ.decisionStatus <> 'Proposal Requested'";
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
            LEFT JOIN ALLOCATION_RECORD ALR
                ON ARQ.studentID = ALR.studentID
                AND ARQ.supervisorID = ALR.supervisorID
                AND ALR.allocationMethod = 'System Auto-Match'
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
                ALR.allocationID,
                ALR.allocationMethod,
                U.fullName,
                U.universityEmail,
                SP.programme,
                SP.cgpa,
                SP.contactNumber,
                SP.personalBio,
                SP.linkedInURL,
                SP.githubURL,
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT RT.tagName
                        ORDER BY RT.tagName
                        SEPARATOR '||'
                    )
                    FROM STUDENT_TAG_SELECTION STS
                    INNER JOIN SUPERVISOR_TAG_SELECTION SVTS
                        ON STS.tagID = SVTS.tagID
                        AND SVTS.supervisorID = ARQ.supervisorID
                    INNER JOIN RESEARCH_TAG RT
                        ON STS.tagID = RT.tagID
                    WHERE STS.studentID = ARQ.studentID
                ) AS matchedTagNames
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN STUDENT_PROFILE SP
                ON ARQ.studentID = SP.studentID
            INNER JOIN USER U
                ON SP.studentID = U.userID
            LEFT JOIN ALLOCATION_RECORD ALR
                ON ARQ.studentID = ALR.studentID
                AND ARQ.supervisorID = ALR.supervisorID
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

        $supervisorComment =
            trim((string) $supervisorComment);

        if ($supervisorComment === "") {

            return [
                "success" => false,
                "message" => "Comment Required - Please provide a reason for rejection to help the student improve their next application."
            ];
        }

        try {

            $this->conn->beginTransaction();

            $requestQuery = "
                SELECT
                    ARQ.requestID,
                    ARQ.studentID,
                    ARQ.supervisorID,
                    ARQ.decisionStatus,
                    ALR.allocationID AS existingSupervisorAllocationID
                FROM APPLICATION_REQUEST ARQ
                LEFT JOIN ALLOCATION_RECORD ALR
                    ON ARQ.studentID = ALR.studentID
                    AND ARQ.supervisorID = ALR.supervisorID
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

            $hasExistingSupervisorAllocation =
                !empty($request["existingSupervisorAllocationID"]);

            if ($request["decisionStatus"] !== "Pending") {

                $this->conn->rollBack();

                return [
                    "success" => false,
                    "message" => "Decision already processed for this proposal. A new decision is only available after the student submits a new proposal."
                ];
            }

            if ($decisionStatus === "Accepted") {

                $allocationQuery = "
                    SELECT supervisorID
                    FROM ALLOCATION_RECORD
                    WHERE studentID = :studentID
                ";

                $allocationStatement = $this->conn->prepare($allocationQuery);
                $allocationStatement->bindParam(":studentID", $request["studentID"]);
                $allocationStatement->execute();
                $allocatedSupervisorIDs =
                    $allocationStatement->fetchAll(PDO::FETCH_COLUMN);

                if (
                    !empty($allocatedSupervisorIDs) &&
                    !in_array($supervisorID, $allocatedSupervisorIDs, true)
                ) {

                    $this->conn->rollBack();

                    return [
                        "success" => false,
                        "message" => "Decision blocked: this student already has an allocated supervisor."
                    ];
                }

                if (!$hasExistingSupervisorAllocation) {

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
                            "message" => "Quota Exceeded - Error: You have reached your maximum quota limit. Cannot accept more students."
                        ];
                    }
                }
            }

            $updateQuery = "
                UPDATE APPLICATION_REQUEST
                SET decisionStatus = :decisionStatus,
                    supervisorComment = :supervisorComment
                WHERE requestID = :requestID
                AND supervisorID = :supervisorID
                AND decisionStatus = 'Pending'
            ";

            $updateStatement = $this->conn->prepare($updateQuery);
            $updateStatement->bindParam(":decisionStatus", $decisionStatus);
            $updateStatement->bindParam(":supervisorComment", $supervisorComment);
            $updateStatement->bindParam(":requestID", $requestID, PDO::PARAM_INT);
            $updateStatement->bindParam(":supervisorID", $supervisorID);
            $updateStatement->execute();

            if ($updateStatement->rowCount() === 0) {

                $this->conn->rollBack();

                return [
                    "success" => false,
                    "message" => "Decision already processed for this proposal. A new decision is only available after the student submits a new proposal."
                ];
            }

            if ($decisionStatus === "Accepted") {

                if (!$hasExistingSupervisorAllocation) {

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
                            'Approved Request'
                        )
                    ";

                    $insertStatement = $this->conn->prepare($insertQuery);
                    $insertStatement->bindParam(":studentID", $request["studentID"]);
                    $insertStatement->bindParam(":supervisorID", $supervisorID);
                    $insertStatement->execute();
                }

                $withdrawQuery = "
                    UPDATE APPLICATION_REQUEST
                    SET decisionStatus = 'Withdrawn',
                        supervisorComment = 'Automatically withdrawn because another proposal was accepted.'
                    WHERE studentID = :studentID
                    AND requestID <> :requestID
                    AND decisionStatus = 'Pending'
                ";

                $withdrawStatement = $this->conn->prepare($withdrawQuery);
                $withdrawStatement->bindParam(":studentID", $request["studentID"]);
                $withdrawStatement->bindParam(":requestID", $requestID, PDO::PARAM_INT);
                $withdrawStatement->execute();
            }

            $this->conn->commit();

            return [
                "success" => true,
                "message" => $decisionStatus === "Accepted"
                    ? (
                        $hasExistingSupervisorAllocation
                            ? "Proposal accepted successfully. The existing supervision allocation remains active."
                            : "Action Successful - The student has been successfully added to your supervision list."
                    )
                    : (
                        $hasExistingSupervisorAllocation
                            ? "Proposal rejected successfully. The existing supervision allocation remains active."
                            : "Student's application for supervision has been rejected successfully."
                    )
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
            AND decisionStatus IN ('Pending', 'Proposal Requested')
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

    // Automatically reject after 72 hours
    public function expireTimedOutRequestsByStudent(
        $studentID
    ) {

        $query = "
            UPDATE APPLICATION_REQUEST
            SET decisionStatus = 'Rejected-Timeout',
                supervisorComment = 'Automatically rejected because the supervisor did not respond within 72 hours.'
            WHERE studentID = :studentID
            AND decisionStatus = 'Pending'
            AND ttlExpirationTimestamp <= NOW()
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":studentID", $studentID);

        return $statement->execute();
    }

    public function expireTimedOutPendingRequests() {

        $query = "
            UPDATE APPLICATION_REQUEST
            SET decisionStatus = 'Rejected-Timeout',
                supervisorComment = 'Automatically rejected because the supervisor did not respond within 72 hours.'
            WHERE decisionStatus = 'Pending'
            AND ttlExpirationTimestamp <= NOW()
        ";

        $statement =
            $this->conn->prepare($query);

        return
            $statement->execute();
    }

    // Get student application statuses
    public function getApplicationsByStudent(
        $studentID
    ) {

        $query = "
            SELECT
                ARQ.requestID,
                ARQ.supervisorID,
                ARQ.projectTitle,
                ARQ.proposalPDFPath,
                ARQ.decisionStatus,
                ARQ.applicationDate,
                ARQ.ttlExpirationTimestamp,
                ARQ.supervisorComment,
                ALR.allocationID,
                U.fullName AS supervisorName,
                SP.employmentCategory,
                SP.programme
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN SUPERVISOR_PROFILE SP
                ON ARQ.supervisorID = SP.supervisorID
            INNER JOIN USER U
                ON SP.supervisorID = U.userID
            LEFT JOIN ALLOCATION_RECORD ALR
                ON ARQ.studentID = ALR.studentID
                AND ARQ.supervisorID = ALR.supervisorID
            WHERE ARQ.studentID = :studentID
            ORDER BY ARQ.applicationDate DESC,
                ARQ.requestID DESC
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":studentID", $studentID);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApplicationRequestForStudent(
        $requestID,
        $studentID
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
                U.fullName AS supervisorName
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN USER U
                ON ARQ.supervisorID = U.userID
            WHERE ARQ.requestID = :requestID
            AND ARQ.studentID = :studentID
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":requestID", $requestID, PDO::PARAM_INT);
        $statement->bindValue(":studentID", $studentID);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
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
                U.profilePhotoPath,
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
                AND ARQ.decisionStatus IN ('Accepted', 'Pending', 'Rejected', 'Proposal Requested')
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

    public function requestProposalForAllocation(
        $allocationID,
        $supervisorID
    ) {

        $query = "
            SELECT
                AR.allocationID,
                AR.studentID,
                AR.supervisorID
            FROM ALLOCATION_RECORD AR
            WHERE AR.allocationID = :allocationID
            AND AR.supervisorID = :supervisorID
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":allocationID", $allocationID, PDO::PARAM_INT);
        $statement->bindValue(":supervisorID", $supervisorID);
        $statement->execute();

        $allocation = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$allocation) {

            return [
                "success" => false,
                "message" => "Allocation record was not found."
            ];
        }

        $existingQuery = "
            SELECT requestID, decisionStatus
            FROM APPLICATION_REQUEST
            WHERE studentID = :studentID
            AND supervisorID = :supervisorID
            AND decisionStatus IN ('Proposal Requested', 'Pending', 'Accepted')
            ORDER BY requestID DESC
            LIMIT 1
        ";

        $existingStatement = $this->conn->prepare($existingQuery);
        $existingStatement->bindValue(":studentID", $allocation["studentID"]);
        $existingStatement->bindValue(":supervisorID", $supervisorID);
        $existingStatement->execute();

        $existing = $existingStatement->fetch(PDO::FETCH_ASSOC);

        if ($existing) {

            return [
                "success" => true,
                "message" => $existing["decisionStatus"] === "Proposal Requested"
                    ? "Proposal request has already been sent to this student."
                    : "A proposal record already exists for this student."
            ];
        }

        $insertQuery = "
            INSERT INTO APPLICATION_REQUEST
            (
                studentID,
                supervisorID,
                projectTitle,
                proposalPDFPath,
                applicationDate,
                ttlExpirationTimestamp,
                decisionStatus,
                supervisorComment
            )
            VALUES
            (
                :studentID,
                :supervisorID,
                'Proposal Requested',
                '',
                NOW(),
                DATE_ADD(NOW(), INTERVAL 7 DAY),
                'Proposal Requested',
                'Supervisor requested your project proposal after auto-allocation.'
            )
        ";

        $insertStatement = $this->conn->prepare($insertQuery);
        $insertStatement->bindValue(":studentID", $allocation["studentID"]);
        $insertStatement->bindValue(":supervisorID", $supervisorID);
        $insertStatement->execute();

        return [
            "success" => true,
            "message" => "Proposal request sent. The student will see it on their dashboard and application status page."
        ];
    }

    public function getProposalRequestForStudent(
        $requestID,
        $studentID
    ) {

        $query = "
            SELECT
                ARQ.requestID,
                ARQ.studentID,
                ARQ.supervisorID,
                ARQ.projectTitle,
                ARQ.decisionStatus,
                ARQ.supervisorComment,
                U.fullName AS supervisorName
            FROM APPLICATION_REQUEST ARQ
            INNER JOIN USER U
                ON ARQ.supervisorID = U.userID
            INNER JOIN ALLOCATION_RECORD ALR
                ON ALR.studentID = ARQ.studentID
                AND ALR.supervisorID = ARQ.supervisorID
            WHERE ARQ.requestID = :requestID
            AND ARQ.studentID = :studentID
            AND ARQ.decisionStatus IN ('Proposal Requested', 'Rejected')
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":requestID", $requestID, PDO::PARAM_INT);
        $statement->bindValue(":studentID", $studentID);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function completeRequestedProposal(
        $requestID,
        $studentID,
        $supervisorID,
        $projectTitle,
        $proposalPDFPath
    ) {

        $query = "
            UPDATE APPLICATION_REQUEST
            SET projectTitle = :projectTitle,
                proposalPDFPath = :proposalPDFPath,
                applicationDate = NOW(),
                ttlExpirationTimestamp = DATE_ADD(NOW(), INTERVAL 72 HOUR),
                decisionStatus = 'Pending',
                supervisorComment = NULL
            WHERE requestID = :requestID
            AND studentID = :studentID
            AND supervisorID = :supervisorID
            AND decisionStatus IN ('Proposal Requested', 'Rejected')
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":projectTitle", $projectTitle);
        $statement->bindValue(":proposalPDFPath", $proposalPDFPath);
        $statement->bindValue(":requestID", $requestID, PDO::PARAM_INT);
        $statement->bindValue(":studentID", $studentID);
        $statement->bindValue(":supervisorID", $supervisorID);
        $statement->execute();

        return $statement->rowCount() === 1;
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
                U.profilePhotoPath AS supervisorPhotoPath,
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
            $this->conn->prepare( // Sends SQL template to database instead of running query immediately, prevent SQL injection
                $query
            );

        $statement->bindParam( // Safely insert actual value ($studentID) into placeholder value, protect malicious code to be pass into input field
            ":studentID",
            $studentID
        );

        $statement->bindParam(
            ":limit",
            $limit,
            PDO::PARAM_INT
        );

        $statement->execute(); // Run final query with injected variables

        return
            $statement->fetchAll( // Gathers all matching rows from the database and returns them as associative array
                PDO::FETCH_ASSOC // Associative array is an array where keys are column names ("supervisorName => Dr.Smith", ...)
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
            AND decisionStatus IN ('Pending', 'Proposal Requested')
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

    public function studentHasAllocation(
        $studentID
    ) {

        $query = "
            SELECT COUNT(*)
            FROM ALLOCATION_RECORD
            WHERE studentID = :studentID
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

    public function isStudentEligibleForFYP(
        $studentID
    ) {

        $query = "
            SELECT eligibilityStatus
            FROM STUDENT_PROFILE
            WHERE studentID = :studentID
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
            (bool) $statement->fetchColumn();
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
                DATE_ADD(NOW(), INTERVAL 72 HOUR),
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
