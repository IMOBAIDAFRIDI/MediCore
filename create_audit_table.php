<?php
include 'db_connect_pdo.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        user_type VARCHAR(20) DEFAULT 'guest',
        action VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "Table 'audit_logs' created successfully (or already exists).";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
