<?php

class Database {
    private $host = "localhost"; // Server address
    private $db_name = "ssas_db"; // Database name
    private $username = "root";
    private $password = "";

    public function connect() {
        try {
            $conn = new PDO( // Create a new connection object with the information provided
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Tells PHP to throw actual Exception whenever database error occurs

            return $conn; // Return active connection object to the application

        } catch(PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
}
?>
