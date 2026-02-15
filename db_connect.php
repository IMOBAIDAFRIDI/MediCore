<?php
$servername = "localhost";
include_once 'config.php';

$username = "root";
$password = "";
$dbname = "hospital_db";

// Suppress error reporting for the initial connection attempt
mysqli_report(MYSQLI_REPORT_OFF);

// Try connecting directly to the database
$conn = @new mysqli($servername, $username, $password, $dbname);

// Check if connection failed
if ($conn->connect_errno) {
    // If database unknown (1049), try to create it
    if ($conn->connect_errno == 1049) {
        $conn = new mysqli($servername, $username, $password);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
        if ($conn->query($sql) === TRUE) {
            $conn->select_db($dbname);
        } else {
            die("Error creating database: " . $conn->error);
        }
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Turn error reporting back on
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
