<?php
/**
 * Quick Database Setup Tool
 */
$servername = "localhost";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$servername", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Database Setup Starting...</h1>";
    
    // 1. Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS medicore");
    $pdo->exec("USE medicore");
    echo "Database 'medicore' ensured.<br>";
    
    // 2. Load Schema
    $sql = file_get_contents('medicore_clean_database.sql');
    if ($sql === false) {
        die("Could not find medicore_clean_database.sql");
    }
    
    // Split by semicolons for multi-query execution
    // Simple split might fail on triggers/procedures but good for this schema
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $q = trim($query);
        if (!empty($q)) {
            $pdo->exec($q);
        }
    }
    echo "Tables and Seed data imported successfully!<br>";
    
    echo "<h2>Final Status: SUCCESS</h2>";
    echo "<p>You can now use the login page with <b>admin@medicore.com</b> / <b>admin123</b></p>";
    
} catch (PDOException $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
}
?>
