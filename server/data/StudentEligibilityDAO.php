<?php

require_once __DIR__ . "/database.php";

class StudentEligibilityDAO {

    private $conn;

    public function __construct() {

        $database =
            new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Students For Eligibility Dashboard
    |--------------------------------------------------------------------------
    */

    public function getStudentsForEligibility(
        $searchName,
        $programme,
        $eligibilityStatus
    ) {

        $query = "
            SELECT
                U.userID,
                U.fullName,
                U.universityEmail,
                SP.programme,
                SP.intakeBatch,
                SP.currentSem,
                SP.academicStatus,
                SP.cgpa,
                SP.eligibilityStatus
            FROM USER U
            INNER JOIN STUDENT_PROFILE SP
                ON U.userID = SP.studentID
            WHERE U.systemRole = 'Student'
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

        if ($eligibilityStatus !== "") {

            $query .= "
                AND SP.eligibilityStatus = :eligibilityStatus
            ";
        }

        $query .= "
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

        if ($eligibilityStatus !== "") {

            $statusValue =
                (int) $eligibilityStatus;

            $statement->bindParam(
                ":eligibilityStatus",
                $statusValue,
                PDO::PARAM_INT
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
    | Retrieve Distinct Student Programmes
    |--------------------------------------------------------------------------
    */

    public function getStudentProgrammes() {

        $query = "
            SELECT DISTINCT programme
            FROM STUDENT_PROFILE
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
    | Retrieve Eligibility Summary
    |--------------------------------------------------------------------------
    */

    public function getEligibilitySummary() {

        $query = "
            SELECT
                COUNT(*) AS totalStudents,
                SUM(CASE WHEN eligibilityStatus = TRUE THEN 1 ELSE 0 END) AS eligibleStudents,
                SUM(CASE WHEN eligibilityStatus = FALSE THEN 1 ELSE 0 END) AS ineligibleStudents
            FROM STUDENT_PROFILE
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
    | Retrieve All Students For Batch Processing
    |--------------------------------------------------------------------------
    */

    public function getAllStudentsForBatch() {

        $query = "
            SELECT
                studentID,
                currentSem,
                academicStatus,
                cgpa
            FROM STUDENT_PROFILE
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
    | Batch Update Eligibility Status
    |--------------------------------------------------------------------------
    */

    public function updateEligibilityStatuses(
        $eligibilityResults
    ) {

        try {

            $this->conn->beginTransaction();

            $query = "
                UPDATE STUDENT_PROFILE
                SET eligibilityStatus = :eligibilityStatus
                WHERE studentID = :studentID
            ";

            $statement =
                $this->conn->prepare(
                    $query
                );

            foreach ($eligibilityResults as $result) {

                $studentID =
                    $result["studentID"];

                $eligibilityStatus =
                    $result["eligibilityStatus"]
                    ? 1
                    : 0;

                $statement->bindParam(
                    ":studentID",
                    $studentID
                );

                $statement->bindParam(
                    ":eligibilityStatus",
                    $eligibilityStatus,
                    PDO::PARAM_INT
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
