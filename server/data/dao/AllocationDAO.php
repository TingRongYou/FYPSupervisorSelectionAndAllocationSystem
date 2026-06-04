<?php

require_once __DIR__ . "/../database/database.php";

class AllocationDAO {

    private $conn;

    public function __construct() {

        $database =
            new Database();

        $this->conn =
            $database->connect();

        $this->ensureAutoAllocationLogTable();
        $this->ensureAutoAllocationNotificationTable();
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

    /*
    |--------------------------------------------------------------------------
    | Store Auto Allocation Log
    |--------------------------------------------------------------------------
    */

    public function createAutoAllocationLog(
        $triggeredByAdminID,
        $finalAllocationDate,
        $eligibleCount,
        $matchedCount,
        $unassignedCount,
        $status,
        $message
    ) {

        $query = "
            INSERT INTO AUTO_ALLOCATION_LOG
            (
                triggeredByAdminID,
                triggeredAt,
                finalAllocationDate,
                eligibleCount,
                matchedCount,
                unassignedCount,
                logStatus,
                resultMessage
            )
            VALUES
            (
                :triggeredByAdminID,
                NOW(),
                :finalAllocationDate,
                :eligibleCount,
                :matchedCount,
                :unassignedCount,
                :logStatus,
                :resultMessage
            )
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindValue(
            ":triggeredByAdminID",
            $triggeredByAdminID
        );

        $statement->bindValue(
            ":finalAllocationDate",
            $finalAllocationDate
        );

        $statement->bindValue(
            ":eligibleCount",
            (int) $eligibleCount,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":matchedCount",
            (int) $matchedCount,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":unassignedCount",
            (int) $unassignedCount,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":logStatus",
            $status
        );

        $statement->bindValue(
            ":resultMessage",
            $message
        );

        $created =
            $statement->execute();

        return $created
            ? (int) $this->conn->lastInsertId()
            : false;
    }

    /*
    |--------------------------------------------------------------------------
    | Store Auto Allocation Notification Records
    |--------------------------------------------------------------------------
    */

    public function createAutoAllocationNotifications(
        $logID,
        $allocations
    ) {

        if (empty($allocations) || empty($logID)) {

            return true;
        }

        $query = "
            INSERT INTO AUTO_ALLOCATION_NOTIFICATION
            (
                logID,
                recipientUserID,
                notificationType,
                notificationMessage,
                createdAt,
                readStatus
            )
            VALUES
            (
                :logID,
                :recipientUserID,
                :notificationType,
                :notificationMessage,
                NOW(),
                FALSE
            )
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        foreach ($allocations as $allocation) {

            $studentMessage =
                "You have been auto-allocated to supervisor " .
                $allocation["supervisorID"] .
                ".";

            $supervisorMessage =
                "Student " .
                $allocation["studentID"] .
                " has been auto-allocated to your supervision list.";

            $this->insertAutoAllocationNotification(
                $statement,
                $logID,
                $allocation["studentID"],
                "StudentAutoAllocation",
                $studentMessage
            );

            $this->insertAutoAllocationNotification(
                $statement,
                $logID,
                $allocation["supervisorID"],
                "SupervisorAutoAllocation",
                $supervisorMessage
            );
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Recent Auto Allocation Logs
    |--------------------------------------------------------------------------
    */

    public function getRecentAutoAllocationLogs(
        $limit = 5
    ) {

        $query = "
            SELECT
                AAL.logID,
                AAL.triggeredByAdminID,
                U.fullName AS triggeredByAdminName,
                AAL.triggeredAt,
                AAL.finalAllocationDate,
                AAL.eligibleCount,
                AAL.matchedCount,
                AAL.unassignedCount,
                AAL.logStatus,
                AAL.resultMessage
            FROM AUTO_ALLOCATION_LOG AAL
            LEFT JOIN USER U
                ON AAL.triggeredByAdminID = U.userID
            ORDER BY AAL.triggeredAt DESC,
                AAL.logID DESC
            LIMIT :limit
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindValue(
            ":limit",
            (int) $limit,
            PDO::PARAM_INT
        );

        $statement->execute();

        return $statement
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Auto Allocation Log Table
    |--------------------------------------------------------------------------
    */

    private function ensureAutoAllocationLogTable() {

        $query = "
            CREATE TABLE IF NOT EXISTS AUTO_ALLOCATION_LOG (
                logID INT PRIMARY KEY AUTO_INCREMENT,
                triggeredByAdminID VARCHAR(20) NULL,
                triggeredAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                finalAllocationDate DATETIME NULL,
                eligibleCount INT NOT NULL DEFAULT 0,
                matchedCount INT NOT NULL DEFAULT 0,
                unassignedCount INT NOT NULL DEFAULT 0,
                logStatus VARCHAR(30) NOT NULL,
                resultMessage VARCHAR(500) NOT NULL,

                FOREIGN KEY (triggeredByAdminID)
                    REFERENCES USER(userID)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            )
        ";

        $this->conn
            ->exec($query);
    }

    private function ensureAutoAllocationNotificationTable() {

        $query = "
            CREATE TABLE IF NOT EXISTS AUTO_ALLOCATION_NOTIFICATION (
                notificationID INT PRIMARY KEY AUTO_INCREMENT,
                logID INT NOT NULL,
                recipientUserID VARCHAR(20) NOT NULL,
                notificationType VARCHAR(50) NOT NULL,
                notificationMessage VARCHAR(500) NOT NULL,
                createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                readStatus BOOLEAN NOT NULL DEFAULT FALSE,

                FOREIGN KEY (logID)
                    REFERENCES AUTO_ALLOCATION_LOG(logID)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                FOREIGN KEY (recipientUserID)
                    REFERENCES USER(userID)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            )
        ";

        $this->conn
            ->exec($query);
    }

    private function insertAutoAllocationNotification(
        $statement,
        $logID,
        $recipientUserID,
        $notificationType,
        $notificationMessage
    ) {

        $statement->bindValue(
            ":logID",
            (int) $logID,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ":recipientUserID",
            $recipientUserID
        );

        $statement->bindValue(
            ":notificationType",
            $notificationType
        );

        $statement->bindValue(
            ":notificationMessage",
            $notificationMessage
        );

        $statement->execute();
    }
}

?>
