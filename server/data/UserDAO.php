<?php

require_once "database.php";

class UserDAO {

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
    | Retrieve User By Email
    |--------------------------------------------------------------------------
    */

    public function getUserByEmail(
        $email
    ) {

        $query = "

            SELECT

                userID,
                fullName,
                universityEmail,
                systemRole,
                activeStatus,
                password

            FROM USER

            WHERE universityEmail = :email

            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":email",
            $email
        );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve User By ID
    |--------------------------------------------------------------------------
    */

    public function getUserByID(
        $userID
    ) {

        $query = "

            SELECT

                userID,
                fullName,
                universityEmail,
                systemRole,
                activeStatus,
                password

            FROM USER

            WHERE userID = :userID

            LIMIT 1
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":userID",
            $userID
        );

        $statement->execute();

        return
            $statement->fetch(
                PDO::FETCH_ASSOC
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    */

    public function createUser(
        $userID,
        $fullName,
        $universityEmail,
        $systemRole,
        $password
    ) {

        /*
        |--------------------------------------------------------------------------
        | Password Hashing
        |--------------------------------------------------------------------------
        */

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        /*
        |--------------------------------------------------------------------------
        | Insert Query
        |--------------------------------------------------------------------------
        */

        $query = "

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
                :systemRole,
                TRUE,
                :password
            )
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        /*
        |--------------------------------------------------------------------------
        | Bind Parameters
        |--------------------------------------------------------------------------
        */

        $statement->bindParam(
            ":userID",
            $userID
        );

        $statement->bindParam(
            ":fullName",
            $fullName
        );

        $statement->bindParam(
            ":universityEmail",
            $universityEmail
        );

        $statement->bindParam(
            ":systemRole",
            $systemRole
        );

        $statement->bindParam(
            ":password",
            $hashedPassword
        );

        /*
        |--------------------------------------------------------------------------
        | Execute Query
        |--------------------------------------------------------------------------
        */

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete User By ID
    |--------------------------------------------------------------------------
    */

    public function deleteUserByID(
        $userID
    ) {

        $query = "

            DELETE FROM USER

            WHERE userID = :userID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":userID",
            $userID
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Active Status
    |--------------------------------------------------------------------------
    */

    public function updateUserActiveStatus(
        $userID,
        $activeStatus
    ) {

        $query = "

            UPDATE USER

            SET activeStatus = :activeStatus

            WHERE userID = :userID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindValue(
            ":activeStatus",
            (bool) $activeStatus,
            PDO::PARAM_BOOL
        );

        $statement->bindParam(
            ":userID",
            $userID
        );

        return
            $statement->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve All Supervisors
    |--------------------------------------------------------------------------
    */

    public function getAllSupervisors() {

        $query = "

            SELECT

                userID,
                fullName,
                universityEmail,
                activeStatus

            FROM USER

            WHERE systemRole = 'Supervisor'

            ORDER BY fullName ASC
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
    | Check Email Exists
    |--------------------------------------------------------------------------
    */

    public function emailExists(
        $email
    ) {

        $query = "

            SELECT COUNT(*) AS total

            FROM USER

            WHERE universityEmail = :email
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":email",
            $email
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
    | Check User ID Exists
    |--------------------------------------------------------------------------
    */

    public function userIDExists(
        $userID
    ) {

        $query = "

            SELECT COUNT(*) AS total

            FROM USER

            WHERE userID = :userID
        ";

        $statement =
            $this->conn->prepare(
                $query
            );

        $statement->bindParam(
            ":userID",
            $userID
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