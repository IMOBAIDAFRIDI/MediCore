<?php
include_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php';

$db = medicore_db_config();
$servername = $db['host'];
$username = $db['user'];
$password = $db['pass'];
$dbname = $db['name'];

// Suppress error reporting for the initial connection attempt
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
$flags = 0;
if ($db['ssl']) {
    $conn->ssl_set(null, null, '/etc/ssl/certs/ca-certificates.crt', null, null);
    $flags = MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
}

if (!@$conn->real_connect($servername, $username, $password, $dbname, $db['port'], null, $flags)) {
    // Local only: if the database doesn't exist yet (1049), create it.
    if ($conn->connect_errno == 1049 && !$db['ssl']) {
        $conn = new mysqli($servername, $username, $password, '', $db['port']);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`") === TRUE) {
            $conn->select_db($dbname);
        } else {
            die("Error creating database: " . $conn->error);
        }
    } else {
        error_log("DB connection failed: " . $conn->connect_error);
        http_response_code(500);
        die("Database connection failed. Please try again later.");
    }
}

// Turn error reporting back on
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
