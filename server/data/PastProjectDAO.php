<?php

require_once __DIR__ . "/database.php";

class PastProjectDAO {

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $conn;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $database = new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve All Projects By Supervisor
    |--------------------------------------------------------------------------
    */

    public function getProjectsBySupervisor(
        $supervisorID
    ) {

        $query = "
            SELECT
                projectID,
                supervisorID,
                projectTitle,
                completionYear,
                alumniName
            FROM PAST_PROJECT
            WHERE supervisorID = :supervisorID
            ORDER BY completionYear DESC,
                projectTitle ASC
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
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Single Project
    |--------------------------------------------------------------------------
    */

    public function getProjectByID(
        $projectID,
        $supervisorID
    ) {

        $query = "
            SELECT
                projectID,
                supervisorID,
                projectTitle,
                completionYear,
                alumniName
            FROM PAST_PROJECT
            WHERE projectID = :projectID
            AND supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":projectID",
            $projectID,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Project
    |--------------------------------------------------------------------------
    */

    public function projectExists(
        $supervisorID,
        $projectTitle,
        $completionYear
    ) {

        $query = "
            SELECT COUNT(*) AS total
            FROM PAST_PROJECT
            WHERE supervisorID = :supervisorID
            AND projectTitle = :projectTitle
            AND completionYear = :completionYear
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
            ":projectTitle",
            $projectTitle
        );

        $statement->bindParam(
            ":completionYear",
            $completionYear,
            PDO::PARAM_INT
        );

        $statement->execute();

        $result =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return
            ((int) $result["total"]) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Project For Update
    |--------------------------------------------------------------------------
    */

    public function projectExistsForOtherProject(
        $projectID,
        $supervisorID,
        $projectTitle,
        $completionYear
    ) {

        $query = "
            SELECT COUNT(*) AS total
            FROM PAST_PROJECT
            WHERE projectID != :projectID
            AND supervisorID = :supervisorID
            AND projectTitle = :projectTitle
            AND completionYear = :completionYear
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":projectID",
            $projectID,
            PDO::PARAM_INT
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
            ":completionYear",
            $completionYear,
            PDO::PARAM_INT
        );

        $statement->execute();

        $result =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return
            ((int) $result["total"]) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Add Project
    |--------------------------------------------------------------------------
    */

    public function addProject(
        $supervisorID,
        $projectTitle,
        $completionYear,
        $alumniName
    ) {

        $query = "
            INSERT INTO PAST_PROJECT
            (
                supervisorID,
                projectTitle,
                completionYear,
                alumniName
            )
            VALUES
            (
                :supervisorID,
                :projectTitle,
                :completionYear,
                :alumniName
            )
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
            ":projectTitle",
            $projectTitle
        );

        $statement->bindParam(
            ":completionYear",
            $completionYear,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":alumniName",
            $alumniName
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Project
    |--------------------------------------------------------------------------
    */

    public function updateProject(
        $projectID,
        $supervisorID,
        $projectTitle,
        $completionYear,
        $alumniName
    ) {

        $query = "
            UPDATE PAST_PROJECT
            SET
                projectTitle = :projectTitle,
                completionYear = :completionYear,
                alumniName = :alumniName
            WHERE projectID = :projectID
            AND supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":projectTitle",
            $projectTitle
        );

        $statement->bindParam(
            ":completionYear",
            $completionYear,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":alumniName",
            $alumniName
        );

        $statement->bindParam(
            ":projectID",
            $projectID,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Project
    |--------------------------------------------------------------------------
    */

    public function deleteProject(
        $projectID,
        $supervisorID
    ) {

        $query = "
            DELETE FROM PAST_PROJECT
            WHERE projectID = :projectID
            AND supervisorID = :supervisorID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":projectID",
            $projectID,
            PDO::PARAM_INT
        );

        $statement->bindParam(
            ":supervisorID",
            $supervisorID
        );

        return
            $statement->execute();
    }
}

?>