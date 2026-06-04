<?php

require_once __DIR__ . "/../database/database.php";

class StudentProfileDAO {

    private $conn;

    public function __construct() {

        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getStudentProfile($studentID) {

        $query = "
            SELECT
                U.userID,
                U.fullName,
                U.universityEmail,
                U.systemRole,
                U.activeStatus,
                U.profilePhotoPath,
                SP.studentID,
                SP.programme,
                SP.intakeBatch,
                SP.currentSem,
                SP.academicStatus,
                SP.cgpa,
                SP.contactNumber,
                SP.personalBio,
                SP.avatarFilePath,
                SP.linkedInURL,
                SP.githubURL,
                SP.portfolioURL,
                SP.eligibilityStatus
            FROM STUDENT_PROFILE SP
            INNER JOIN USER U
                ON SP.studentID = U.userID
            WHERE SP.studentID = :studentID
            LIMIT 1
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(":studentID", $studentID);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStudentProfile($studentID, $profileData, $tagIDs, $avatarFilePath = null) {

        try {

            $this->conn->beginTransaction();

            $profileQuery = "
                UPDATE STUDENT_PROFILE
                SET
                    contactNumber = :contactNumber,
                    personalBio = :personalBio,
                    linkedInURL = :linkedInURL,
                    githubURL = :githubURL,
                    portfolioURL = :portfolioURL
                    " . ($avatarFilePath !== null ? ", avatarFilePath = :avatarFilePath" : "") . "
                WHERE studentID = :studentID
            ";

            $profileStatement = $this->conn->prepare($profileQuery);
            $profileStatement->bindValue(":contactNumber", $profileData["contactNumber"]);
            $profileStatement->bindValue(":personalBio", $profileData["personalBio"]);
            $profileStatement->bindValue(":linkedInURL", $profileData["linkedInURL"]);
            $profileStatement->bindValue(":githubURL", $profileData["githubURL"]);
            $profileStatement->bindValue(":portfolioURL", $profileData["portfolioURL"]);

            if ($avatarFilePath !== null) {

                $profileStatement->bindValue(":avatarFilePath", $avatarFilePath);
            }

            $profileStatement->bindValue(":studentID", $studentID);
            $profileStatement->execute();

            if ($avatarFilePath !== null) {

                $userQuery = "
                    UPDATE USER
                    SET profilePhotoPath = :profilePhotoPath
                    WHERE userID = :studentID
                    AND systemRole = 'Student'
                ";

                $userStatement = $this->conn->prepare($userQuery);
                $userStatement->bindValue(":profilePhotoPath", $avatarFilePath);
                $userStatement->bindValue(":studentID", $studentID);
                $userStatement->execute();
            }

            $deleteQuery = "
                DELETE FROM STUDENT_TAG_SELECTION
                WHERE studentID = :studentID
            ";

            $deleteStatement = $this->conn->prepare($deleteQuery);
            $deleteStatement->bindValue(":studentID", $studentID);
            $deleteStatement->execute();

            $insertQuery = "
                INSERT INTO STUDENT_TAG_SELECTION
                (
                    studentID,
                    tagID
                )
                VALUES
                (
                    :studentID,
                    :tagID
                )
            ";

            $insertStatement = $this->conn->prepare($insertQuery);

            foreach ($tagIDs as $tagID) {

                $insertStatement->bindValue(":studentID", $studentID);
                $insertStatement->bindValue(":tagID", (int) $tagID, PDO::PARAM_INT);
                $insertStatement->execute();
            }

            $this->conn->commit();

            return true;

        } catch (Exception $exception) {

            if ($this->conn->inTransaction()) {

                $this->conn->rollBack();
            }

            return false;
        }
    }
}

?>
