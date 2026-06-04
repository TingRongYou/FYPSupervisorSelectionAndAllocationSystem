<?php

require_once __DIR__ . "/../database/database.php";

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

        $this->ensurePastProjectColumns();
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
                alumniName,
                COALESCE(projectDescription, '') AS projectDescription,
                projectPDFPath,
                projectImagePath
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
    | Count Active Supervisees
    |--------------------------------------------------------------------------
    */

    public function countActiveSuperviseesBySupervisor(
        $supervisorID
    ) {

        $query = "
            SELECT COUNT(*) AS total
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

        $result =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

        return
            (int) (
                $result["total"]
                ?? 0
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
                alumniName,
                COALESCE(projectDescription, '') AS projectDescription,
                projectPDFPath,
                projectImagePath
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
        $alumniName,
        $projectDescription,
        $projectPDFPath,
        $projectImagePath
    ) {

        $query = "
            INSERT INTO PAST_PROJECT
            (
                supervisorID,
                projectTitle,
                completionYear,
                alumniName,
                projectDescription,
                projectPDFPath,
                projectImagePath
            )
            VALUES
            (
                :supervisorID,
                :projectTitle,
                :completionYear,
                :alumniName,
                :projectDescription,
                :projectPDFPath,
                :projectImagePath
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

        $statement->bindParam(
            ":projectDescription",
            $projectDescription
        );

        $statement->bindParam(
            ":projectPDFPath",
            $projectPDFPath
        );

        $statement->bindParam(
            ":projectImagePath",
            $projectImagePath
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
        $alumniName,
        $projectDescription,
        $projectPDFPath,
        $projectImagePath
    ) {

        $query = "
            UPDATE PAST_PROJECT
            SET
                projectTitle = :projectTitle,
                completionYear = :completionYear,
                alumniName = :alumniName,
                projectDescription = :projectDescription,
                projectPDFPath = :projectPDFPath,
                projectImagePath = :projectImagePath
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
            ":projectDescription",
            $projectDescription
        );

        $statement->bindParam(
            ":projectPDFPath",
            $projectPDFPath
        );

        $statement->bindParam(
            ":projectImagePath",
            $projectImagePath
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

    private function ensurePastProjectColumns() {

        $columns =
            $this->conn
            ->query("SHOW COLUMNS FROM PAST_PROJECT")
            ->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array("projectDescription", $columns, true)) {

            $this->conn
                ->exec("ALTER TABLE PAST_PROJECT ADD projectDescription TEXT NULL AFTER alumniName");
        }

        if (!in_array("projectPDFPath", $columns, true)) {

            $this->conn
                ->exec("ALTER TABLE PAST_PROJECT ADD projectPDFPath VARCHAR(255) NULL AFTER projectDescription");
        }

        if (!in_array("projectImagePath", $columns, true)) {

            $this->conn
                ->exec("ALTER TABLE PAST_PROJECT ADD projectImagePath VARCHAR(255) NULL AFTER projectPDFPath");
        }
    }
}

?>
