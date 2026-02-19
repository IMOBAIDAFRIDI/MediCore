<?php
// Script to generate a complete SQL dump for MediCore

$filename = 'medicore_full_database.sql';
$fp = fopen($filename, 'w');

// 1. Header & Table Structures
$header = "-- MediCore Complete Database Dump
-- Generated on " . date('Y-m-d H:i:s') . "

SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
START TRANSACTION;
SET time_zone = \"+00:00\";

--
-- Database: `medicore`
--
CREATE DATABASE IF NOT EXISTS `medicore`;
USE `medicore`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `issue` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `location`, `address`, `phone`, `dob`, `hospital_id`, `status`, `issue`, `created_at`) VALUES
";
fwrite($fp, $header);

// Passwords
$adminPass = '$2y$10$NvBp2zqC5p1iXZpgdYeoJeDQc1Kzv9ijpiqkxF7Y92nOdZUs7gGLW'; // admin123
$userPass = '$2y$10$HnMX8WViSggQPV4PfKHl9uuL.S7Rc2wjB5tHkxlKQak5XqFMsp2WS'; // 12345678

// 2. Admin User
$values = [];
$id = 1;
$values[] = "($id, 'Admin', 'admin@medicore.com', '$adminPass', 'admin', NULL, NULL, NULL, NULL, NULL, 'approved', NULL, NOW())";
$id++;

// 3. Hospitals (200 Worldwide)
$cities = [
    'New York, USA', 'London, UK', 'Tokyo, Japan', 'Paris, France', 'Berlin, Germany', 
    'Dubai, UAE', 'Sydney, Australia', 'Toronto, Canada', 'Moscow, Russia', 'Beijing, China',
    'Mumbai, India', 'Sao Paulo, Brazil', 'Mexico City, Mexico', 'Cairo, Egypt', 'Istanbul, Turkey',
    'Karachi, Pakistan', 'Lahore, Pakistan', 'Seoul, South Korea', 'Bangkok, Thailand', 'Rome, Italy',
    'Madrid, Spain', 'Los Angeles, USA', 'Chicago, USA', 'Houston, USA', 'Phoenix, USA',
    'Melbourne, Australia', 'Brisbane, Australia', 'Manchester, UK', 'Birmingham, UK', 'Glasgow, UK',
    'Lyon, France', 'Marseille, France', 'Munich, Germany', 'Hamburg, Germany', 'Frankfurt, Germany'
];

$hospitalTypes = ['General', 'Memorial', 'City', 'University', 'Community', 'International', 'Specialist', 'Central', 'Royal'];

$hospitalIds = [];

for ($i = 0; $i < 200; $i++) {
    $city = $cities[array_rand($cities)];
    $type = $hospitalTypes[array_rand($hospitalTypes)];
    $name = "$city $type Hospital " . ($i + 1);
    
    // Email logic: hospital1@bltiwd.com ? Or keep hospital1@hospital.com? 
    // User said "sab patients ka and hospitals ka password...". 
    // User said "600 patients bi ho and their email after @ bltiwd.com".
    // It's ambiguous if hospitals need bltiwd.com, but safer to give them standard emails or bltiwd if implied.
    // I will use standard distinct emails for hospitals to look professional, or generic.
    $email = "hospital" . ($i + 1) . "@bltiwd.com"; 
    
    $address = "Street " . rand(1, 100) . ", $city";
    
    $values[] = "($id, '$name', '$email', '$userPass', 'hospital', '$city', '$address', NULL, NULL, NULL, 'approved', NULL, NOW())";
    $hospitalIds[] = $id;
    $id++;
}

// 4. Patients (600)
$firstNames = ['John', 'Jane', 'Michael', 'Emily', 'David', 'Sarah', 'James', 'Jessica', 'Robert', 'Laura', 'William', 'Hannah', 'Richard', 'Emma', 'Joseph', 'Olivia', 'Thomas', 'Ava', 'Charles', 'Mia'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
$issues = ['COVID-19 Test', 'Vaccination', 'Booster Shot', 'COVID-19 Symptoms', 'PCR Test', 'Rapid Antigen Test', 'Post-Covid Checkup', 'Vaccination Certificate', 'Travel Clearance', 'Pfizer Dose 1', 'Moderna Dose 2', 'Sinopharm Booster'];

for ($i = 0; $i < 600; $i++) {
    $fname = $firstNames[array_rand($firstNames)];
    $lname = $lastNames[array_rand($lastNames)];
    $name = "$fname $lname";
    
    // Email: @bltiwd.com
    $email = strtolower($fname) . "." . strtolower($lname) . ($i + 1) . "@bltiwd.com";
    
    $dob = date('Y-m-d', rand(strtotime('1960-01-01'), strtotime('2010-12-31')));
    $phone = "+92" . rand(300, 399) . rand(1000000, 9999999);
    $address = "House " . rand(1, 999) . ", Street " . rand(1, 50);
    $hospId = $hospitalIds[array_rand($hospitalIds)];
    $issue = $issues[array_rand($issues)];
    
    $values[] = "($id, '$name', '$email', '$userPass', 'patient', NULL, '$address', '$phone', '$dob', $hospId, 'approved', '$issue', NOW())";
    $id++;
}

// Write User Data
fwrite($fp, implode(",\n", $values) . ";\n\n");

// 5. Vaccines Table
$vaccinesTable = "
--
-- Table structure for table `vaccines`
--

CREATE TABLE IF NOT EXISTS `vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `description`, `image`, `created_at`) VALUES
";
fwrite($fp, $vaccinesTable);

$vaccineList = [];
$manufacturers = ['Pfizer-BioNTech', 'Moderna', 'AstraZeneca', 'Sinopharm', 'Sinovac', 'Sputnik V', 'Johnson & Johnson', 'Novavax', 'Covaxin', 'CanSino'];
$types = ['mRNA', 'Viral Vector', 'Inactivated', 'Protein Subunit'];
$batches = ['Alpha', 'Beta', 'Delta', 'Omicron', 'Gamma', 'Kappa', 'Epsilon', 'Zeta', 'Eta', 'Theta'];

$vacId = 1;
// Real/Base Vaccines
$baseVaccines = [
    ['Pfizer-BioNTech Comirnaty', 'mRNA COVID-19 vaccine.', 'https://cdn-prod.medicalnewstoday.com/content/images/articles/326/326715/a-healthcare-professional-giving-a-vaccine-to-a-woman.jpg'],
    ['Moderna Spikevax', 'mRNA vaccine for COVID-19.', 'https://cdn.britannica.com/13/234213-050-73781543/syringe-vial-moderna-vaccine-covid-19-coronavirus.jpg'],
    ['AstraZeneca Vaxzevria', 'Viral vector vaccine.', 'https://www.ema.europa.eu/sites/default/files/styles/ema_medium/public/vaxzevria_0.jpg'],
    ['Sinopharm BIBP', 'Inactivated virus vaccine.', 'https://gavi.org/sites/default/files/styles/medium/public/covid/WHO-team-delivering-covid-19-vaccines-iStock-1300772422.jpg'],
    ['Sputnik V', 'Viral vector vaccine.', 'https://static.dw.com/image/56404774_605.jpg'],
    ['Janssen COVID-19', 'Viral vector vaccine (single dose).', 'https://www.janssen.com/sites/www/files/styles/adaptive_large/public/media_image/2021-02/janssen-covid-19-vaccine-vial.jpg'],
    ['CoronaVac (Sinovac)', 'Inactivated vaccine.', 'https://www.who.int/images/default-source/wpro/countries/viet-nam/health-topics/covid-19/sinovac-vacine.jpg'],
    ['Covaxin (Bharat)', 'Whole-virion inactivated.', 'https://www.bharatbiotech.com/images/covaxin/covaxin-india.jpg'],
    ['Novavax Nuvaxovid', 'Protein-based vaccine.', 'https://www.novavax.com/sites/default/files/2021-06/Novavax-Vaccine-Vial.jpg'],
    ['CanSino Convidecia', 'Viral vector vaccine.', 'https://media.defense.gov/2021/Mar/10/2002597851/825/780/0/210310-D-BD104-001.JPG']
];

foreach ($baseVaccines as $vac) {
    // Add base
    $vaccineList[] = "($vacId, '" . addslashes($vac[0]) . "', '" . addslashes($vac[1]) . "', '$vac[2]', NOW())";
    $vacId++;
}

// Generate variants to reach 100+
for ($i = $vacId; $i <= 105; $i++) {
    $man = $manufacturers[array_rand($manufacturers)];
    $type = $types[array_rand($types)];
    $batch = $batches[array_rand($batches)];
    $variant = rand(100, 999);
    
    $name = "$man $batch-$variant";
    $desc = "Batch $variant of $man vaccine. Type: $type.";
    // Use a placeholder or random from base for image
    $img = $baseVaccines[array_rand($baseVaccines)][2];
    
    $vaccineList[] = "($i, '$name', '$desc', '$img', NOW())";
}

fwrite($fp, implode(",\n", $vaccineList) . ";\n\n");


// 6. Hospital Vaccines Table (Inventory)
$hospVaccinesTable = "
--
-- Table structure for table `hospital_vaccines`
--

CREATE TABLE IF NOT EXISTS `hospital_vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'available',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hospital_vaccines`
--

INSERT INTO `hospital_vaccines` (`id`, `hospital_id`, `vaccine_id`, `status`) VALUES
";
fwrite($fp, $hospVaccinesTable);

$hvList = [];
$hvId = 1;
$totalVaccines = 105;

// Distribute randomly: Each hospital gets 10-25 random vaccines
foreach ($hospitalIds as $hid) {
    // Pick random count
    $count = rand(10, 25);
    // Pick unique random vaccines
    $randomVaccines = array_rand(array_flip(range(1, $totalVaccines)), $count);
    
    if(!is_array($randomVaccines)) $randomVaccines = [$randomVaccines];
    
    foreach ($randomVaccines as $vid) {
        $hvList[] = "($hvId, $hid, $vid, 'available')";
        $hvId++;
    }
}
fwrite($fp, implode(",\n", $hvList) . ";\n\n");


// 7. Appointments Table
$appointments = "
--
-- Table structure for table `appointments`
--

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `vaccine_id` int(11) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `type` varchar(50) DEFAULT 'vaccination',
  `status` varchar(50) DEFAULT 'pending',
  `result` varchar(50) DEFAULT NULL,
  `dose_number` int(11) DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `hospital_id`, `patient_id`, `vaccine_id`, `appointment_date`, `appointment_time`, `type`, `status`, `result`, `dose_number`, `remarks`, `created_at`) VALUES
";
fwrite($fp, $appointments);

$appList = [];
$appId = 1;
$statuses = ['pending', 'approved', 'completed', 'cancelled'];

// Generate 400 appointments
for ($i = 0; $i < 400; $i++) {
    $hid = rand(2, 201); // Hospital IDs
    $pid = rand(202, 801); // Patient IDs
    $vid = rand(1, 105); // Vaccine IDs
    
    // Date: Random date in last 3 months to next 1 month
    $date = date('Y-m-d', strtotime(rand(-90, 30) . ' days'));
    $time = rand(9, 17) . ":00:00";
    
    $status = $statuses[array_rand($statuses)];
    $result = NULL;
    $remarks = NULL;
    
    // Determine type: 50% Test, 50% Vaccination
    $isTest = (rand(1, 10) > 5);
    $type = $isTest ? 'test' : 'vaccination';

    if ($status == 'completed') {
        if ($type == 'test') {
            $result = (rand(1, 10) > 7) ? 'positive' : 'negative';
            $remarks = "Patient tested $result for COVID-19.";
        } else {
            $result = 'vaccinated'; 
            $remarks = "Vaccination dose administered successfully.";
        }
    } else {
        $remarks = "Appointment for $type. Status: $status";
    }
    
    // Escape strings if needed, though mostly safe here
    $resultVal = $result ? "'$result'" : "NULL";
    $remarksVal = $remarks ? "'$remarks'" : "NULL";
    
    $appList[] = "($appId, $hid, $pid, $vid, '$date', '$time', '$type', '$status', $resultVal, 1, $remarksVal, NOW())";
    $appId++;
}

fwrite($fp, implode(",\n", $appList) . ";\n\n");

// 8. Articles Table
$articlesTable = "
--
-- Table structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `content`, `image`, `url`, `created_at`) VALUES
";
fwrite($fp, $articlesTable);

$articlesList = [
    [1, 'Understanding COVID-19 Variants', 'COVID-19 variants like Delta and Omicron have changed the way the virus spreads. Learn about the symptoms and precautions.', 'https://max-website20-images.s3.ap-south-1.amazonaws.com/LF_7_and_NB_1_8_1_2020166e24.jpg', 'https://www.who.int/health-topics/coronavirus'],
    [2, 'Importance of Vaccination', 'Vaccines are the most effective way to protect yourself and your loved ones from severe illness caused by COVID-19.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrIC3ILFmtXdXSPdaAWbzki3W8a8KruM8s_Q&s', 'https://www.cdc.gov/coronavirus/2019-ncov/vaccines/index.html'],
    [3, 'Post-COVID care tips', 'Recovering from COVID-19 can take time. Here are some tips to help you regain your strength and health after infection.', 'https://www.royalclinicdubai.com/wp-content/uploads/2023/12/Post-Covid-Rehabilitation-infographic-in-Dubai-1.jpg', 'https://www.who.int/emergencies/diseases/novel-coronavirus-2019/advice-for-public']
];

$artValues = [];
foreach ($articlesList as $art) {
    $artValues[] = "({$art[0]}, '" . addslashes($art[1]) . "', '" . addslashes($art[2]) . "', '{$art[3]}', '{$art[4]}', NOW())";
}

fwrite($fp, implode(",\n", $artValues) . ";\n\n");

echo "SQL Dump Created: $filename";
?>
