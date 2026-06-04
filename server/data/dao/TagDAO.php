<?php

require_once __DIR__ . "/../database/database.php";

class TagDAO {

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

        $database =
            new Database();

        $this->conn =
            $database->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve All Research Tags
    |--------------------------------------------------------------------------
    */

    public function getAllTags() {

        $query = "

            SELECT

                tagID,

                tagName

            FROM RESEARCH_TAG

            ORDER BY tagName ASC
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
    | Retrieve Supervisor Tag IDs
    |--------------------------------------------------------------------------
    */

    public function getSupervisorTagIDs(
        $supervisorID
    ) {

        $query = "

            SELECT

                tagID

            FROM SUPERVISOR_TAG_SELECTION

            WHERE supervisorID = :supervisorID

            ORDER BY tagID ASC
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
                PDO::FETCH_COLUMN
            );
    }

    public function getStudentTagIDs(
        $studentID
    ) {

        $query = "

            SELECT

                tagID

            FROM STUDENT_TAG_SELECTION

            WHERE studentID = :studentID

            ORDER BY tagID ASC
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

        return $statement
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSupervisorTagMap(
        $supervisorIDs
    ) {

        if (empty($supervisorIDs)) {

            return [];
        }

        $placeholders =
            implode(
                ",",
                array_fill(0, count($supervisorIDs), "?")
            );

        $query = "
            SELECT
                STS.supervisorID,
                RT.tagID,
                RT.tagName
            FROM SUPERVISOR_TAG_SELECTION STS
            INNER JOIN RESEARCH_TAG RT
                ON STS.tagID = RT.tagID
            WHERE STS.supervisorID IN ({$placeholders})
            ORDER BY RT.tagName ASC
        ";

        $statement =
            $this->conn->prepare($query);

        foreach ($supervisorIDs as $index => $supervisorID) {

            $statement->bindValue(
                $index + 1,
                $supervisorID
            );
        }

        $statement->execute();

        $rows =
            $statement->fetchAll(PDO::FETCH_ASSOC);

        $tagMap =
            [];

        foreach ($rows as $row) {

            $supervisorID =
                $row["supervisorID"];

            if (!isset($tagMap[$supervisorID])) {

                $tagMap[$supervisorID] =
                    [];
            }

            $tagMap[$supervisorID][] = [
                "tagID" =>
                    (int) $row["tagID"],

                "tagName" =>
                    $row["tagName"]
            ];
        }

        return $tagMap;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Tag IDs Exist
    |--------------------------------------------------------------------------
    */

    public function tagIDsExist(
        $tagIDs
    ) {

        /*
        |--------------------------------------------------------------------------
        | Empty Validation
        |--------------------------------------------------------------------------
        */

        if (empty($tagIDs)) {

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Placeholders
        |--------------------------------------------------------------------------
        */

        $placeholders =
            implode(
                ",",
                array_fill(
                    0,
                    count($tagIDs),
                    "?"
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query = "

            SELECT COUNT(*) AS total

            FROM RESEARCH_TAG

            WHERE tagID IN ({$placeholders})
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        /*
        |--------------------------------------------------------------------------
        | Bind Tag IDs
        |--------------------------------------------------------------------------
        */

        foreach (
            $tagIDs
            as $index => $tagID
        ) {

            $statement->bindValue(

                $index + 1,

                (int) $tagID,

                PDO::PARAM_INT
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Execute
        |--------------------------------------------------------------------------
        */

        $statement->execute();

        $total =
            (int)
            $statement->fetchColumn();

        /*
        |--------------------------------------------------------------------------
        | Exact Match Validation
        |--------------------------------------------------------------------------
        */

        return
            $total === count($tagIDs);
    }

    /*
    |--------------------------------------------------------------------------
    | Replace Supervisor Tags
    |--------------------------------------------------------------------------
    */

    public function replaceSupervisorTags(
        $supervisorID,
        $tagIDs
    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | Begin Transaction
            |--------------------------------------------------------------------------
            */

            $this->conn->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Delete Existing Tags
            |--------------------------------------------------------------------------
            */

            $deleteQuery = "

                DELETE FROM SUPERVISOR_TAG_SELECTION

                WHERE supervisorID = :supervisorID
            ";

            $deleteStatement =
                $this->conn->prepare(
                    $deleteQuery
                );

            $deleteStatement->bindParam(
                ":supervisorID",
                $supervisorID
            );

            $deleteStatement->execute();

            /*
            |--------------------------------------------------------------------------
            | Insert New Tags
            |--------------------------------------------------------------------------
            */

            $insertQuery = "

                INSERT INTO SUPERVISOR_TAG_SELECTION
                (
                    supervisorID,
                    tagID
                )
                VALUES
                (
                    :supervisorID,
                    :tagID
                )
            ";

            $insertStatement =
                $this->conn->prepare(
                    $insertQuery
                );

            foreach (
                $tagIDs
                as $tagID
            ) {

                $insertStatement->bindValue(
                    ":supervisorID",
                    $supervisorID
                );

                $insertStatement->bindValue(
                    ":tagID",
                    (int) $tagID,
                    PDO::PARAM_INT
                );

                $insertStatement->execute();
            }

            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            return
                $this->conn->commit();

        } catch (Exception $exception) {

            /*
            |--------------------------------------------------------------------------
            | Rollback Transaction
            |--------------------------------------------------------------------------
            */

            if (
                $this->conn->inTransaction()
            ) {

                $this->conn->rollBack();
            }

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Tag Names By IDs
    |--------------------------------------------------------------------------
    */

    public function getTagNamesByIDs(
        $tagIDs
    ) {

        /*
        |--------------------------------------------------------------------------
        | Empty Validation
        |--------------------------------------------------------------------------
        */

        if (empty($tagIDs)) {

            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Placeholders
        |--------------------------------------------------------------------------
        */

        $placeholders =
            implode(
                ",",
                array_fill(
                    0,
                    count($tagIDs),
                    "?"
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */

        $query = "

            SELECT

                tagID,

                tagName

            FROM RESEARCH_TAG

            WHERE tagID IN ({$placeholders})

            ORDER BY tagName ASC
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        /*
        |--------------------------------------------------------------------------
        | Bind IDs
        |--------------------------------------------------------------------------
        */

        foreach (
            $tagIDs
            as $index => $tagID
        ) {

            $statement->bindValue(

                $index + 1,

                (int) $tagID,

                PDO::PARAM_INT
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Execute
        |--------------------------------------------------------------------------
        */

        $statement->execute();

        return
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Check Supervisor Has Tags
    |--------------------------------------------------------------------------
    */

    public function supervisorHasTags(
        $supervisorID
    ) {

        $query = "

            SELECT COUNT(*) AS total

            FROM SUPERVISOR_TAG_SELECTION

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
            ((int) $result["total"]) > 0;
    }
}

?>
