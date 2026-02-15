<?php
include 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS email_queue (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    delete_attachment TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table email_queue created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
