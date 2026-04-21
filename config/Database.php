<?php
// config/Database.php

class Database {
    // Private properties to keep credentials secure
    private $host = "localhost";
    private $db_name = "puffystyle"; // Your database name
    private $username = "root";      // Default XAMPP username
    private $password = "";          // Default XAMPP password is empty
    public $conn;

    // Method to establish and return the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            // Set error mode to exception to help us debug later
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Uncomment the line below just to test the connection, delete it after testing
            // echo "Database Connected Successfully via OOP!";
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>