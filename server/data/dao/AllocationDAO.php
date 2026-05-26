<?php

require_once __DIR__ . "/../database/database.php";

class AllocationDAO {

    private $conn;

    public function __construct() {

        $database =
            new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Eligible Unassigned Students
    |--------------------------------------------------------------------------
    */

    public function getEligibleUnassignedStudents() {

        $query = "
            SELECT
                SP.studentID,
                SP.programme,
                SP.cgpa
            FROM STUDENT_PROFILE SP
            INNER JOIN USER U
                ON SP.studentID = U.userID
            LEFT JOIN ALLOCATION_RECORD AR
                ON SP.studentID = AR.studentID
            WHERE U.systemRole = 'Student'
            AND U.activeStatus = TRUE
            AND SP.eligibilityStatus = TRUE
            AND AR.allocationID IS NULL
            ORDER BY SP.cgpa DESC,
                SP.studentID ASC
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
    | Retrieve Supervisors With Current Load
    |--------------------------------------------------------------------------
    */

    public function getSupervisorsWithCapacity() {

        $query = "
            SELECT
                SP.supervisorID,
                SP.programme,
                COALESCE(
                    SP.assignedQuotaLimit,
                    QC.maxSuperviseesAllowed
                ) AS maxSuperviseesAllowed,
                COUNT(DISTINCT AR.allocationID) AS currentTotal
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
                SP.programme,
                SP.assignedQuotaLimit,
                QC.maxSuperviseesAllowed
            HAVING COUNT(DISTINCT AR.allocationID) < COALESCE(
                SP.assignedQuotaLimit,
                QC.maxSuperviseesAllowed
            )
            ORDER BY currentTotal ASC,
                     SP.supervisorID ASC
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
    | Allocation Summary
    |--------------------------------------------------------------------------
    */

    public function getAllocationSummary() {

        $query = "
            SELECT
                (SELECT COUNT(*) FROM STUDENT_PROFILE WHERE eligibilityStatus = TRUE) AS eligibleStudents,
                (SELECT COUNT(*) FROM ALLOCATION_RECORD) AS allocatedStudents,
                (
                    SELECT COUNT(*)
                    FROM STUDENT_PROFILE SP
                    LEFT JOIN ALLOCATION_RECORD AR
                        ON SP.studentID = AR.studentID
                    WHERE SP.eligibilityStatus = TRUE
                    AND AR.allocationID IS NULL
                ) AS unassignedStudents,
                (
                    SELECT COUNT(*)
                    FROM APPLICATION_REQUEST
                    WHERE decisionStatus = 'Pending'
                ) AS pendingRequests
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

    /*
    |--------------------------------------------------------------------------
    | Commit Auto Allocation
    |--------------------------------------------------------------------------
    */

    public function commitAllocations(
        $allocations
    ) {

        try {

            $this->conn->beginTransaction();

            $query = "
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
                    :allocationMethod
                )
            ";

            $statement =
                $this->conn->prepare(
                    $query
                );

            foreach ($allocations as $allocation) {

                $studentID =
                    $allocation["studentID"];

                $supervisorID =
                    $allocation["supervisorID"];

                $allocationMethod =
                    $allocation["allocationMethod"];

                $statement->bindParam(
                    ":studentID",
                    $studentID
                );

                $statement->bindParam(
                    ":supervisorID",
                    $supervisorID
                );

                $statement->bindParam(
                    ":allocationMethod",
                    $allocationMethod
                );

                $statement->execute();
            }

            return
                $this->conn->commit();

        } catch (Exception $exception) {

            if ($this->conn->inTransaction()) {

                $this->conn->rollBack();
            }

            return false;
        }
    }
}

?>


