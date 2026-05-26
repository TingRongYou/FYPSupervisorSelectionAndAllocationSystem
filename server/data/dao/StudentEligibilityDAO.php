<?php

require_once __DIR__ . "/../database/database.php";

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

            $userQuery = "
                UPDATE USER
                SET activeStatus = :activeStatus
                WHERE userID = :studentID
                AND systemRole = 'Student'
            ";

            $userStatement =
                $this->conn->prepare(
                    $userQuery
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

                $activeStatus =
                    $eligibilityStatus;

                $userStatement->bindParam(
                    ":studentID",
                    $studentID
                );

                $userStatement->bindParam(
                    ":activeStatus",
                    $activeStatus,
                    PDO::PARAM_INT
                );

                $userStatement->execute();
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
    | Import Student Academic Records
    |--------------------------------------------------------------------------
    */

    public function importStudentRecords(
        $studentRecords
    ) {

        try {

            $this->conn->beginTransaction();

            $userQuery = "
                INSERT INTO USER
                (
                    userID,
                    fullName,
                    universityEmail,
                    systemRole,
                    activeStatus,
                    password
                )
                VALUES
                (
                    :userID,
                    :fullName,
                    :universityEmail,
                    'Student',
                    :activeStatus,
                    :password
                )
                ON DUPLICATE KEY UPDATE
                    fullName = VALUES(fullName),
                    universityEmail = VALUES(universityEmail),
                    systemRole = 'Student',
                    activeStatus = VALUES(activeStatus)
            ";

            $profileQuery = "
                INSERT INTO STUDENT_PROFILE
                (
                    studentID,
                    programme,
                    intakeBatch,
                    currentSem,
                    academicStatus,
                    cgpa,
                    eligibilityStatus
                )
                VALUES
                (
                    :studentID,
                    :programme,
                    :intakeBatch,
                    :currentSem,
                    :academicStatus,
                    :cgpa,
                    :eligibilityStatus
                )
                ON DUPLICATE KEY UPDATE
                    programme = VALUES(programme),
                    intakeBatch = VALUES(intakeBatch),
                    currentSem = VALUES(currentSem),
                    academicStatus = VALUES(academicStatus),
                    cgpa = VALUES(cgpa),
                    eligibilityStatus = VALUES(eligibilityStatus)
            ";

            $userStatement =
                $this->conn->prepare(
                    $userQuery
                );

            $profileStatement =
                $this->conn->prepare(
                    $profileQuery
                );

            foreach ($studentRecords as $record) {

                $activeStatus =
                    $record["eligibilityStatus"]
                    ? 1
                    : 0;

                $eligibilityStatus =
                    $record["eligibilityStatus"]
                    ? 1
                    : 0;

                $userStatement->bindParam(
                    ":userID",
                    $record["studentID"]
                );

                $userStatement->bindParam(
                    ":fullName",
                    $record["fullName"]
                );

                $userStatement->bindParam(
                    ":universityEmail",
                    $record["universityEmail"]
                );

                $userStatement->bindParam(
                    ":activeStatus",
                    $activeStatus,
                    PDO::PARAM_INT
                );

                $userStatement->bindParam(
                    ":password",
                    $record["hashedPassword"]
                );

                $userStatement->execute();

                $profileStatement->bindParam(
                    ":studentID",
                    $record["studentID"]
                );

                $profileStatement->bindParam(
                    ":programme",
                    $record["programme"]
                );

                $profileStatement->bindParam(
                    ":intakeBatch",
                    $record["intakeBatch"]
                );

                $profileStatement->bindParam(
                    ":currentSem",
                    $record["currentSem"]
                );

                $profileStatement->bindParam(
                    ":academicStatus",
                    $record["academicStatus"]
                );

                $profileStatement->bindParam(
                    ":cgpa",
                    $record["cgpa"]
                );

                $profileStatement->bindParam(
                    ":eligibilityStatus",
                    $eligibilityStatus,
                    PDO::PARAM_INT
                );

                $profileStatement->execute();
            }

            return $this->conn->commit();

        } catch (Exception $exception) {

            if ($this->conn->inTransaction()) {

                $this->conn->rollBack();
            }

            return false;
        }
    }
}

?>


