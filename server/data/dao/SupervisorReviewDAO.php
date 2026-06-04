<?php

require_once __DIR__ . "/../database/database.php";

class SupervisorReviewDAO {

    private $conn;

    public function __construct() {

        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getReviewContextForStudent($studentID) {

        $query = "
            SELECT
                AR.allocationID,
                AR.studentID,
                AR.supervisorID,
                AR.allocationDate,
                AR.allocationMethod,
                U.fullName AS supervisorName,
                U.profilePhotoPath,
                SP.programme,
                SP.employmentCategory,
                SR.reviewID,
                SR.starRating,
                SR.textFeedback,
                SR.isAnonymous
            FROM ALLOCATION_RECORD AR
            INNER JOIN SUPERVISOR_PROFILE SP
                ON AR.supervisorID = SP.supervisorID
            INNER JOIN USER U
                ON SP.supervisorID = U.userID
            LEFT JOIN SUPERVISOR_REVIEW SR
                ON AR.allocationID = SR.allocationID
            WHERE AR.studentID = :studentID
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":studentID", $studentID);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

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

        $statement = $this->conn->prepare($query);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function allocationBelongsToStudent($allocationID, $studentID) {

        $query = "
            SELECT COUNT(*)
            FROM ALLOCATION_RECORD
            WHERE allocationID = :allocationID
            AND studentID = :studentID
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":allocationID", (int) $allocationID, PDO::PARAM_INT);
        $statement->bindValue(":studentID", $studentID);
        $statement->execute();

        return ((int) $statement->fetchColumn()) > 0;
    }

    public function reviewExistsForAllocation($allocationID) {

        $query = "
            SELECT COUNT(*)
            FROM SUPERVISOR_REVIEW
            WHERE allocationID = :allocationID
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":allocationID", (int) $allocationID, PDO::PARAM_INT);
        $statement->execute();

        return ((int) $statement->fetchColumn()) > 0;
    }

    public function createReview($allocationID, $studentID, $starRating, $textFeedback, $isAnonymous) {

        $query = "
            INSERT INTO SUPERVISOR_REVIEW
            (
                allocationID,
                trueStudentID,
                starRating,
                textFeedback,
                isAnonymous
            )
            VALUES
            (
                :allocationID,
                :trueStudentID,
                :starRating,
                :textFeedback,
                :isAnonymous
            )
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":allocationID", (int) $allocationID, PDO::PARAM_INT);
        $statement->bindValue(":trueStudentID", $studentID);
        $statement->bindValue(":starRating", (int) $starRating, PDO::PARAM_INT);
        $statement->bindValue(":textFeedback", $textFeedback);
        $statement->bindValue(":isAnonymous", (bool) $isAnonymous, PDO::PARAM_BOOL);

        return $statement->execute();
    }

    public function getSupervisorReviewStatistics($supervisorID) {

        $query = "
            SELECT
                COUNT(SR.reviewID) AS reviewCount,
                COALESCE(AVG(SR.starRating), 0) AS averageRating
            FROM SUPERVISOR_REVIEW SR
            INNER JOIN ALLOCATION_RECORD AR
                ON SR.allocationID = AR.allocationID
            WHERE AR.supervisorID = :supervisorID
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":supervisorID", $supervisorID);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function getSanitizedReviewsForSupervisor($supervisorID, $limit = 5) {

        $query = "
            SELECT
                SR.reviewID,
                SR.starRating,
                SR.textFeedback,
                SR.isAnonymous,
                SR.trueStudentID,
                U.fullName AS trueStudentName
            FROM SUPERVISOR_REVIEW SR
            INNER JOIN ALLOCATION_RECORD AR
                ON SR.allocationID = AR.allocationID
            INNER JOIN USER U
                ON SR.trueStudentID = U.userID
            WHERE AR.supervisorID = :supervisorID
            ORDER BY SR.reviewID DESC
            LIMIT :limit
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":supervisorID", $supervisorID);
        $statement->bindValue(":limit", (int) $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewAuditRecords($supervisorID = "", $limit = 100) {

        $query = "
            SELECT
                SR.reviewID,
                SR.allocationID,
                SR.trueStudentID,
                SR.starRating,
                SR.textFeedback,
                SR.isAnonymous,
                Student.fullName AS trueStudentName,
                Supervisor.userID AS supervisorID,
                Supervisor.fullName AS supervisorName,
                AR.allocationDate,
                AR.allocationMethod
            FROM SUPERVISOR_REVIEW SR
            INNER JOIN ALLOCATION_RECORD AR
                ON SR.allocationID = AR.allocationID
            INNER JOIN USER Student
                ON SR.trueStudentID = Student.userID
            INNER JOIN USER Supervisor
                ON AR.supervisorID = Supervisor.userID
            WHERE (:supervisorID = '' OR AR.supervisorID = :supervisorID)
            ORDER BY SR.reviewID DESC
            LIMIT :limit
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(":supervisorID", trim((string) $supervisorID));
        $statement->bindValue(":limit", (int) $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
