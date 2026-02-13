<?php
include 'db_connect.php';

// Users/Admins Table (Simplified for single admin or multiple)
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'admins' checked/created successfully.<br>";
} else {
    echo "Error creating table 'admins': " . $conn->error . "<br>";
}

// Default Admin
$password = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT INTO admins (id, username, password) VALUES (1, 'admin', '$password') ON DUPLICATE KEY UPDATE password='$password'";
$conn->query($sql);

// Hospitals Table
$sql = "CREATE TABLE IF NOT EXISTS hospitals (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    location VARCHAR(50) NOT NULL,
    image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'hospitals' checked/created successfully.<br>";
} else {
    echo "Error creating table 'hospitals': " . $conn->error . "<br>";
}

// Seed Hospitals
$password_hosp = password_hash("hospital123", PASSWORD_DEFAULT);
$logo1 = "https://image.pollinations.ai/prompt/modern%20hospital%20logo%20blue%20cross%20vector?width=400&height=400&nologo=true";
$logo2 = "https://image.pollinations.ai/prompt/medical%20center%20logo%20green%20health%20vector?width=400&height=400&nologo=true";

$sql_hosp1 = "INSERT INTO hospitals (id, name, email, password, address, location, status, image) VALUES 
(1, 'City Care Hospital', 'city@hospital.com', '$password_hosp', '123 Main St, Downtown', 'New York', 'approved', '$logo1') ON DUPLICATE KEY UPDATE image='$logo1'";
$sql_hosp2 = "INSERT INTO hospitals (id, name, email, password, address, location, status, image) VALUES 
(2, 'Metro General', 'metro@hospital.com', '$password_hosp', '456 Elm St, Uptown', 'Chicago', 'approved', '$logo2') ON DUPLICATE KEY UPDATE image='$logo2'";
$conn->query($sql_hosp1);
$conn->query($sql_hosp2);


// Patients Table
$sql = "CREATE TABLE IF NOT EXISTS patients (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'patients' checked/created successfully.<br>";
} else {
    echo "Error creating table 'patients': " . $conn->error . "<br>";
}

// Seed Patients
$password_pat = password_hash("patient123", PASSWORD_DEFAULT);
$sql_pat1 = "INSERT IGNORE INTO patients (id, name, email, password, address, phone, dob) VALUES 
(1, 'John Doe', 'john@example.com', '$password_pat', '123 First St', '555-0101', '1980-05-15')";
$sql_pat2 = "INSERT IGNORE INTO patients (id, name, email, password, address, phone, dob) VALUES 
(2, 'Jane Smith', 'jane@example.com', '$password_pat', '456 Second Ave', '555-0102', '1992-11-20')";
$conn->query($sql_pat1);
$conn->query($sql_pat2);


// Vaccines Table
$sql = "CREATE TABLE IF NOT EXISTS vaccines (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    availability ENUM('available', 'unavailable') DEFAULT 'available'
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'vaccines' checked/created successfully.<br>";
} else {
    echo "Error creating table 'vaccines': " . $conn->error . "<br>";
}
// Seed some vaccines with images
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (1, 'Covishield', 'Viral Vector Support', 'available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsreI1BqfMSDlNsP6nB8kEeVLTHKm_bxNPLA&s')");
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (2, 'Covaxin', 'Inactivated Virus', 'available', 'https://www.clinicaltrialsarena.com/wp-content/uploads/sites/22/2021/02/Image-3-COVAXIN-BBV152-1.jpg')");
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (3, 'Pfizer-BioNTech', 'mRNA Technology', 'available', 'https://c.files.bbci.co.uk/1229D/production/_118379347_f959b3bf-db0b-4dc9-822e-64a7ea6170e0.jpg')");
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (4, 'Moderna', 'mRNA Technology', 'available', 'https://petrieflom.law.harvard.edu/wp-content/uploads/2024/07/shutterstock_1899669364-scaled-2.jpg')");
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (5, 'Sputnik V', 'Viral Vector', 'available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxvKOekvDdmkV4tVrzlutaRoDazC_X5CE0Ow&s')");
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (6, 'Johnson & Johnson', 'Viral Vector', 'available', 'https://www.conehealth.com/app/files/public/63329ab8-ad31-4fef-9a1e-2ca76fdd5faf/WM%20Johnson%20Johnson%20COVID-19%20Vaccine-%206%20Facts%20You%20Should%20Know.png')");
$conn->query("INSERT IGNORE INTO vaccines (id, name, description, availability, image) VALUES (7, 'Sinopharm', 'Inactivated Virus', 'available', 'https://www.reuters.com/resizer/v2/https%3A%2F%2Farchive-images.prod.global.a201836.reutersmedia.net%2F2021%2F01%2F19%2FLYNXMPEH0I02M.JPG?auth=33c54a60c98f5a26ab69c4e6d45226384c43eaf0fe2a9df83e2004a1ef5cba48&width=1920&quality=80')");

// Hospital Vaccines Table (Pivot Table)
$sql = "CREATE TABLE IF NOT EXISTS hospital_vaccines (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT(11) NOT NULL,
    vaccine_id INT(11) NOT NULL,
    status ENUM('available', 'unavailable') DEFAULT 'unavailable',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hospital_vaccine (hospital_id, vaccine_id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'hospital_vaccines' checked/created successfully.<br>";
} else {
    echo "Error creating table 'hospital_vaccines': " . $conn->error . "<br>";
}

// Appointments Table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(6) UNSIGNED NOT NULL,
    hospital_id INT(6) UNSIGNED,
    type ENUM('test', 'vaccination') NOT NULL,
    vaccine_id INT(6) UNSIGNED,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    result ENUM('pending', 'negative', 'positive', 'vaccinated') DEFAULT 'pending',
    remarks TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'appointments' checked/created successfully.<br>";
} else {
    echo "Error creating table 'appointments': " . $conn->error . "<br>";
}

// Seed Appointments
$sql_appt1 = "INSERT IGNORE INTO appointments (id, patient_id, hospital_id, type, vaccine_id, appointment_date, appointment_time, status, result, remarks) VALUES 
(1, 1, 1, 'vaccination', 1, '2023-01-10', '09:00:00', 'completed', 'vaccinated', 'First dose administered')";
$sql_appt2 = "INSERT IGNORE INTO appointments (id, patient_id, hospital_id, type, vaccine_id, appointment_date, appointment_time, status, result, remarks) VALUES 
(2, 1, 1, 'test', NULL, '2023-01-15', '10:00:00', 'completed', 'negative', 'Routine check')";
$sql_appt3 = "INSERT IGNORE INTO appointments (id, patient_id, hospital_id, type, vaccine_id, appointment_date, appointment_time, status, result, remarks) VALUES 
(3, 2, 2, 'vaccination', 3, '2023-01-20', '14:30:00', 'approved', 'pending', 'Scheduled')";
$conn->query($sql_appt1);
$conn->query($sql_appt2);
$conn->query($sql_appt3);


// Articles Table
$sql = "CREATE TABLE IF NOT EXISTS articles (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    content TEXT NOT NULL,
    image VARCHAR(255),
    author VARCHAR(100) DEFAULT 'MediCore Team',
    published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'articles' checked/created successfully.<br>";
} else {
    echo "Error creating table 'articles': " . $conn->error . "<br>";
}

// Seed Articles
$art_img1 = "https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80";
$art_img2 = "https://images.unsplash.com/photo-1584362917165-526a968f4b23?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80";
$art_img3 = "https://images.unsplash.com/photo-1624727828489-a1e03b79bba8?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80";

$content1 = "Booster doses are an important part of protecting yourself and your community from COVID-19. As immunity wanes over time, a booster shot helps to bring your protection back up to a high level. Recent studies have shown that boosters significantly reduce the risk of severe illness and hospitalization.";
$content2 = "mRNA vaccines represent a breakthrough in vaccine technology. Unlike traditional vaccines that use weakened or inactivated germs, mRNA vaccines use a molecule called messenger RNA to teach our cells how to make a protein that triggers an immune response. This technology allows for faster development and production of vaccines.";
$content3 = "The global vaccination effort has reached a new milestone, with over 70% of the world population having received at least one dose. However, disparities remain between improved and developing nations. MediCore is committed to bridging this gap by ensuring equitable distribution of vaccines to all corners of the globe.";

$sql_art1 = "INSERT IGNORE INTO articles (id, title, summary, content, image, author) VALUES 
(1, 'Why Booster Doses Matter', 'Understanding the importance of maintaining high immunity levels.', '$content1', '$art_img1', 'Dr. Sarah Johnson')";
$sql_art2 = "INSERT IGNORE INTO articles (id, title, summary, content, image, author) VALUES 
(2, 'The Science of mRNA', 'How messenger RNA technology is revolutionizing modern medicine.', '$content2', '$art_img2', 'Research Team')";
$sql_art3 = "INSERT IGNORE INTO articles (id, title, summary, content, image, author) VALUES 
(3, 'Global Vaccination Milestone', 'Over 70% of the global population is now partially vaccinated.', '$content3', '$art_img3', 'Global Health Watch')";

$conn->query($sql_art1);
$conn->query($sql_art2);
$conn->query($sql_art3);


echo "<br><b>Database setup and seeding completed successfully!</b><br>";
echo "<b>Default Admin:</b> admin / admin123<br>";
echo "<b>Default Hospital:</b> city@hospital.com / hospital123<br>";
echo "<b>Default Patient:</b> john@example.com / patient123<br>";

$conn->close();
?>
