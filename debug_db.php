<?php
include 'db_connect.php';

echo "<h2>Database Diagnosis</h2>";

// Check Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful.<br>";

// List Tables
echo "<h3>Existing Tables:</h3>";
$tables = $conn->query("SHOW TABLES");
if ($tables) {
    while ($row = $tables->fetch_array()) {
        echo $row[0] . "<br>";
    }
} else {
    echo "Error listing tables: " . $conn->error;
}

// Check Vaccines
echo "<h3>Checking 'vaccines' table:</h3>";
$check_vac = $conn->query("SELECT * FROM vaccines");
if ($check_vac) {
    echo "Vaccines table exists. Rows: " . $check_vac->num_rows . "<br>";
    if ($check_vac->num_rows == 0) {
        echo "<b>WARNING: Vaccines table is empty!</b><br>";
        // Re-seed if empty
        echo "Attempting to re-seed vaccines...<br>";
        $conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (1, 'Covishield', 'Viral Vector Support', 'available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsreI1BqfMSDlNsP6nB8kEeVLTHKm_bxNPLA&s')");
        $conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (2, 'Covaxin', 'Inactivated Virus', 'available', 'https://www.clinicaltrialsarena.com/wp-content/uploads/sites/22/2021/02/Image-3-COVAXIN-BBV152-1.jpg')");
        $conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (3, 'Pfizer-BioNTech', 'mRNA Technology', 'available', 'https://c.files.bbci.co.uk/1229D/production/_118379347_f959b3bf-db0b-4dc9-822e-64a7ea6170e0.jpg')");
        $conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (4, 'Moderna', 'mRNA Technology', 'available', 'https://petrieflom.law.harvard.edu/wp-content/uploads/2024/07/shutterstock_1899669364-scaled-2.jpg')");
        echo "Seeded 4 vaccines.<br>";
    }
} else {
    echo "Error checking vaccines table: " . $conn->error . "<br>";
    // Create if missing
    $sql = "CREATE TABLE IF NOT EXISTS vaccines (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        availability ENUM('available', 'unavailable') DEFAULT 'available'
    )";
    if ($conn->query($sql) === TRUE) {
        echo "Vaccines table created.<br>";
        // Seed
        $conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (1, 'Covishield', 'Viral Vector Support', 'available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsreI1BqfMSDlNsP6nB8kEeVLTHKm_bxNPLA&s')");
        echo "Seeded initial vaccine.<br>";
    } else {
        echo "Failed to create vaccines table: " . $conn->error . "<br>";
    }
}

$conn->close();
?>
