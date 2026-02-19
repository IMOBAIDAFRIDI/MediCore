<?php
// Secure Data Seeder for MediCore
// Usage: Run this file once to populate database with mock data
// Warning: This script can take some time to execute.

// Database Configuration
$host = 'localhost';
$dbname = 'medicore'; // Update with your actual DB name
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Passwords
    // 12345678 => $2y$10$HnMX8WViSggQPV4PfKHl9uuL.S7Rc2wjB5tHkxlKQak5XqFMsp2WS
    // admin123 => $2y$10$NvBp2zqC5p1iXZpgdYeoJeDQc1Kzv9ijpiqkxF7Y92nOdZUs7gGLW
    
    $commonPassword = '$2y$10$HnMX8WViSggQPV4PfKHl9uuL.S7Rc2wjB5tHkxlKQak5XqFMsp2WS';
    $adminPassword = '$2y$10$NvBp2zqC5p1iXZpgdYeoJeDQc1Kzv9ijpiqkxF7Y92nOdZUs7gGLW'; // admin123

    echo "<h1>MediCore Data Seeder</h1>";
    
    // 1. Admin
    echo "<h3>1. Seeding Admin...</h3>";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@medicore.com'");
    $stmt->execute();
    if($stmt->rowCount() == 0) {
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@medicore.com', ?, 'admin')";
        $pdo->prepare($sql)->execute([$adminPassword]);
        echo "Admin created (admin@medicore.com / admin123)<br>";
    } else {
        echo "Admin already exists.<br>";
    }

    // 2. Hospitals (200 Worldwide)
    echo "<h3>2. Seeding 200 Hospitals...</h3>";
    $hospitalCount = 0;
    
    // List of major worldwide cities/locations for realism
    $locations = [
        'New York, USA', 'London, UK', 'Dubai, UAE', 'Berlin, Germany', 
        'Paris, France', 'Tokyo, Japan', 'Sydney, Australia', 'Toronto, Canada',
        'Mumbai, India', 'Karachi, Pakistan', 'Lahore, Pakistan', 'Islamabad, Pakistan',
        'Beijing, China', 'Moscow, Russia', 'Istanbul, Turkey', 'Sao Paulo, Brazil',
        'Cairo, Egypt', 'Seoul, South Korea', 'Mexico City, Mexico', 'Jakarta, Indonesia'
    ];
    
    $hospitalNamesPool = [
        "General", "Memorial", "City", "University", "Community", "International", 
        "Specialist", "Central", "Royal", "National", "Metropolitan", "Alliance",
        "Apex", "Prime", "Elite", "Global", "Unity", "Pioneer", "Summit", "Beacon"
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, location, address, status, created_at) VALUES (?, ?, ?, 'hospital', ?, ?, 'approved', NOW())");
    
    for ($i = 1; $i <= 200; $i++) {
        $loc = $locations[array_rand($locations)];
        $name = $hospitalNamesPool[array_rand($hospitalNamesPool)] . " Hospital " . rand(100, 999);
        $email = "hospital{$i}@bltiwd.com"; // Requested syntax (though usually hospital logic varies, sticking to request)
        // User asked for "their email after @ bltiwd.com", assuming for patients, but let's do safe unique emails
        
        // Check exist
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if($check->rowCount() > 0) continue;
        
        $address = "Block " . rand(1, 20) . ", Street " . rand(1, 100) . ", " . $loc;
        
        $stmt->execute([$name, $email, $commonPassword, $loc, $address]);
        $hospitalCount++;
    }
    echo "Added $hospitalCount new hospitals.<br>";

    // Get all valid hospital IDs for assigning patients
    $hospStmt = $pdo->query("SELECT id FROM users WHERE role = 'hospital'");
    $hospitalIds = $hospStmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Patients (600)
    echo "<h3>3. Seeding 600 Patients...</h3>";
    $patientCount = 0;
    
    $firstNames = ['James','John','Robert','Michael','William','David','Richard','Charles','Joseph','Thomas','Mary','Patricia','Jennifer','Linda','Elizabeth','Barbara','Susan','Jessica','Sarah','Karen','Ali','Ahmed','Sara','Fatima','Zain','Omar','Ayesha','Bilal','Hassan','Usman'];
    $lastNames = ['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Khan','Ali','Ahmed','Butt','Sheikh','Malik','Rehman','Chaudhry','Iqbal','Shah'];
    
    // Common medical issues
    $issues = [
        "Flu Symptoms", "Diabetes Checkup", "High Blood Pressure", "Vaccination Request", 
        "Viral Infection", "Mild Fever", "Allergy Reaction", "Routine Checkup", 
        "Back Pain", "Migraine", "Stomach Infection", "COVID-19 Booster",
        "Child Vaccination", "Skin Rash", "Joint Pain"
    ];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, dob, phone, address, hospital_id, issue, created_at) VALUES (?, ?, ?, 'patient', ?, ?, ?, ?, ?, NOW())");
    
    for ($i = 1; $i <= 600; $i++) {
        $fname = $firstNames[array_rand($firstNames)];
        $lname = $lastNames[array_rand($lastNames)];
        $fullName = "$fname $lname";
        $email = strtolower($fname) . "." . strtolower($lname) . rand(1, 9999) . "@bltiwd.com";
        
        // Random Details
        $dob = date('Y-m-d', rand(strtotime('1950-01-01'), strtotime('2015-12-30')));
        $phone = "+92" . rand(300, 349) . rand(1000000, 9999999);
        $address = "House " . rand(1, 500) . ", St " . rand(1, 50);
        $issue = $issues[array_rand($issues)];
        
        // Assign random hospital if available
        $hospId = (!empty($hospitalIds)) ? $hospitalIds[array_rand($hospitalIds)] : null;
        
        // Check match
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if($check->rowCount() > 0) continue;

        $stmt->execute([$fullName, $email, $commonPassword, $dob, $phone, $address, $hospId, $issue]);
        $patientCount++;
    }
    echo "Added $patientCount new patients.<br>";
    
    echo "<h2>Seeding Complete!</h2>";
    echo "<p>Admin: admin@medicore.com / admin123</p>";
    echo "<p>Others Password: 12345678</p>";

} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
