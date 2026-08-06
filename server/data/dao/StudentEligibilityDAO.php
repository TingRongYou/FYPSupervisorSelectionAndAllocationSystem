<?php

require_once __DIR__ . "/../database/database.php";

class StudentEligibilityDAO {

    private $conn;

    public function __construct() {

        $database = new Database();

        $this->conn = $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Students For Eligibility Dashboard
    |--------------------------------------------------------------------------
    */

    public function getStudentsForEligibility($searchName, $programme, $eligibilityStatus) {

        $query = "
            SELECT
                SP.studentID AS userID,
                COALESCE(U.fullName, SP.fullName) AS fullName,
                COALESCE(U.universityEmail, SP.universityEmail) AS universityEmail,
                SP.programme,
                SP.intakeBatch,
                SP.currentSem,
                SP.academicStatus,
                SP.cgpa,
                SP.eligibilityStatus
            FROM STUDENT_ELIGIBILITY_RECORD SP
            LEFT JOIN USER U
                ON SP.studentID = U.userID
                AND U.systemRole = 'Student'
            WHERE 1 = 1
        ";

        if ($searchName !== "") {

            $query .= "
                AND (
                SP.fullName LIKE :searchPattern
                    OR SP.studentID LIKE :searchPattern
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
            ORDER BY SP.fullName ASC
        ";

        $statement = $this->conn->prepare($query);

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

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Distinct Student Programmes
    |--------------------------------------------------------------------------
    */

    public function getStudentProgrammes() {

        $query = "
            SELECT DISTINCT programme
            FROM STUDENT_ELIGIBILITY_RECORD
            WHERE programme IS NOT NULL
            AND programme != ''
            ORDER BY programme ASC
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEligibilityRules() {

        $query = "
            SELECT
                minimumCGPA,
                requiredNextSemester,
                blockedAcademicStatus
            FROM ELIGIBILITY_RULE_CONFIGURATION
            WHERE ruleID = 1
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function updateEligibilityRules($minimumCGPA, $requiredNextSemester, $blockedAcademicStatus) {

        $query = "
            INSERT INTO ELIGIBILITY_RULE_CONFIGURATION
            (
                ruleID,
                minimumCGPA,
                requiredNextSemester,
                blockedAcademicStatus,
                updatedAt
            )
            VALUES
            (
                1,
                :minimumCGPA,
                :requiredNextSemester,
                :blockedAcademicStatus,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                minimumCGPA = VALUES(minimumCGPA),
                requiredNextSemester = VALUES(requiredNextSemester),
                blockedAcademicStatus = VALUES(blockedAcademicStatus),
                updatedAt = NOW()
        ";

        $statement = $this->conn->prepare($query);

        $statement->bindParam(":minimumCGPA", $minimumCGPA);

        $statement->bindParam(":requiredNextSemester", $requiredNextSemester);

        $statement->bindParam(":blockedAcademicStatus", $blockedAcademicStatus);

        return $statement->execute();
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
            FROM STUDENT_ELIGIBILITY_RECORD
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
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
                universityEmail,
                icNumber,
                fullName,
                programme,
                intakeBatch,
                currentSem,
                academicStatus,
                cgpa
            FROM STUDENT_ELIGIBILITY_RECORD
        ";

        $statement = $this->conn->prepare($query);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Batch Update Eligibility Status
    |--------------------------------------------------------------------------
    */

    public function updateEligibilityStatuses($eligibilityResults) {

        try {

            $this->conn->beginTransaction();

            $query = "
                UPDATE STUDENT_ELIGIBILITY_RECORD
                SET eligibilityStatus = :eligibilityStatus
                WHERE studentID = :studentID
            ";

            $statement = $this->conn->prepare($query);

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
                    activeStatus = VALUES(activeStatus)
            ";

            $userStatement = $this->conn->prepare($userQuery);

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

            $profileStatement = $this->conn->prepare($profileQuery);

            foreach ($eligibilityResults as $result) {

                $studentID = $result["studentID"];

                $eligibilityStatus = $result["eligibilityStatus"] ? 1 : 0;

                $statement->bindParam(":studentID", $studentID);

                $statement->bindParam(":eligibilityStatus", $eligibilityStatus, PDO::PARAM_INT);

                $statement->execute();

                $activeStatus = $eligibilityStatus;

                $userStatement->bindParam(":userID", $studentID);

                $userStatement->bindParam(":fullName", $result["fullName"]);

                $userStatement->bindParam(":universityEmail", $result["universityEmail"]);

                $userStatement->bindParam(":activeStatus", $activeStatus, PDO::PARAM_INT);

                $userStatement->bindParam(":password", $result["hashedPassword"]);

                if ($eligibilityStatus === 1) {

                    $userStatement->execute();

                    $profileStatement->bindParam(":studentID", $studentID);

                    $profileStatement->bindParam(":programme", $result["programme"]);

                    $profileStatement->bindParam(":intakeBatch", $result["intakeBatch"]);

                    $profileStatement->bindParam( ":currentSem", $result["currentSem"]);

                    $profileStatement->bindParam(":academicStatus", $result["academicStatus"]);

                    $profileStatement->bindParam(":cgpa", $result["cgpa"]);

                    $profileStatement->bindParam(":eligibilityStatus", $eligibilityStatus, PDO::PARAM_INT);

                    $profileStatement->execute();
                }
            }

            return $this->conn->commit();

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

    public function importStudentRecords($studentRecords) {

        try {

            $this->conn->beginTransaction();

            $eligibilityQuery = "
                INSERT INTO STUDENT_ELIGIBILITY_RECORD
                (
                    studentID,
                    universityEmail,
                    icNumber,
                    fullName,
                    programme,
                    intakeBatch,
                    currentSem,
                    academicStatus,
                    cgpa,
                    eligibilityStatus,
                    importedAt
                )
                VALUES
                (
                    :studentID,
                    :universityEmail,
                    :icNumber,
                    :fullName,
                    :programme,
                    :intakeBatch,
                    :currentSem,
                    :academicStatus,
                    :cgpa,
                    :eligibilityStatus,
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    universityEmail = VALUES(universityEmail),
                    icNumber = VALUES(icNumber),
                    fullName = VALUES(fullName),
                    programme = VALUES(programme),
                    intakeBatch = VALUES(intakeBatch),
                    currentSem = VALUES(currentSem),
                    academicStatus = VALUES(academicStatus),
                    cgpa = VALUES(cgpa),
                    eligibilityStatus = VALUES(eligibilityStatus),
                    importedAt = NOW()
            ";

            $eligibilityStatement = $this->conn->prepare($eligibilityQuery);

            foreach ($studentRecords as $record) {

                $eligibilityStatus = $record["eligibilityStatus"] ? 1 : 0;

                $eligibilityStatement->bindParam(":studentID", $record["studentID"]);

                $eligibilityStatement->bindParam(":universityEmail", $record["universityEmail"]);

                $eligibilityStatement->bindParam(":icNumber", $record["icNumber"]);

                $eligibilityStatement->bindParam(":fullName", $record["fullName"]);

                $eligibilityStatement->bindParam(":programme", $record["programme"]);

                $eligibilityStatement->bindParam(":intakeBatch",$record["intakeBatch"]);

                $eligibilityStatement->bindParam(":currentSem", $record["currentSem"]);

                $eligibilityStatement->bindParam(":academicStatus", $record["academicStatus"]);

                $eligibilityStatement->bindParam(":cgpa", $record["cgpa"]);

                $eligibilityStatement->bindParam(":eligibilityStatus", $eligibilityStatus, PDO::PARAM_INT);

                $eligibilityStatement->execute();
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
