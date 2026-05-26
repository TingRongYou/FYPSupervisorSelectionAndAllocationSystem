<?php

class Database {
    private $host = "localhost";
    private $db_name = "ssas_db";
    private $username = "root";
    private $password = "";

    public function connect() {
        try {
            $conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;

        } catch(PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
}
?>
