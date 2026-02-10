<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hospital_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($dbname);
} else {
    die("Error creating database: " . $conn->error);
}

// Check if hospital_vaccines table exists, if not create it
$table_check = "SHOW TABLES LIKE 'hospital_vaccines'";
if ($conn->query($table_check)->num_rows == 0) {
    $sql_create_hv = "CREATE TABLE hospital_vaccines (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        hospital_id INT(11) NOT NULL,
        vaccine_id INT(11) NOT NULL,
        status ENUM('available', 'unavailable') DEFAULT 'unavailable',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_hospital_vaccine (hospital_id, vaccine_id)
    )";
    if (!$conn->query($sql_create_hv)) {
        // Log error silently or handle appropriately
        error_log("Error creating hospital_vaccines table: " . $conn->error);
    }
}
?>
