<?php
// Generate Clean Database with only 10 vaccines
$filename = "medicore_clean_database.sql";
$fp = fopen($filename, 'w');

fwrite($fp, "-- MediCore Clean Database\n");
fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

fwrite($fp, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
fwrite($fp, "START TRANSACTION;\n");
fwrite($fp, "SET time_zone = \"+00:00\";\n\n");

// 1. Users Table
$usersTable = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','hospital','patient') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `address` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`, `address`, `location`, `image`, `created_at`) VALUES
(1, 'Admin', 'admin@medicore.com', '03001234567', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', 'MediCore HQ', 'Islamabad', NULL, NOW());

";
fwrite($fp, $usersTable);

// 2. Vaccines Table
$vaccinesTable = "
CREATE TABLE IF NOT EXISTS `vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `vaccines` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Pfizer-BioNTech Comirnaty', 'mRNA COVID-19 vaccine.', 'https://cdn-prod.medicalnewstoday.com/content/images/articles/326/326715/a-healthcare-professional-giving-a-vaccine-to-a-woman.jpg', NOW()),
(2, 'Moderna Spikevax', 'mRNA vaccine for COVID-19.', 'https://cdn.britannica.com/13/234213-050-73781543/syringe-vial-moderna-vaccine-covid-19-coronavirus.jpg', NOW()),
(3, 'AstraZeneca Vaxzevria', 'Viral vector vaccine.', 'https://www.ema.europa.eu/sites/default/files/styles/ema_medium/public/vaxzevria_0.jpg', NOW()),
(4, 'Sinopharm BIBP', 'Inactivated virus vaccine.', 'https://gavi.org/sites/default/files/styles/medium/public/covid/WHO-team-delivering-covid-19-vaccines-iStock-1300772422.jpg', NOW()),
(5, 'Sputnik V', 'Viral vector vaccine.', 'https://static.dw.com/image/56404774_605.jpg', NOW()),
(6, 'Janssen COVID-19', 'Viral vector vaccine (single dose).', 'https://www.janssen.com/sites/www/files/styles/adaptive_large/public/media_image/2021-02/janssen-covid-19-vaccine-vial.jpg', NOW()),
(7, 'CoronaVac (Sinovac)', 'Inactivated vaccine.', 'https://www.who.int/images/default-source/wpro/countries/viet-nam/health-topics/covid-19/sinovac-vacine.jpg', NOW()),
(8, 'Covaxin (Bharat)', 'Whole-virion inactivated.', 'https://www.bharatbiotech.com/images/covaxin/covaxin-india.jpg', NOW()),
(9, 'Novavax Nuvaxovid', 'Protein-based vaccine.', 'https://www.novavax.com/sites/default/files/2021-06/Novavax-Vaccine-Vial.jpg', NOW()),
(10, 'CanSino Convidecia', 'Viral vector vaccine.', 'https://media.defense.gov/2021/Mar/10/2002597851/825/780/0/210310-D-BD104-001.JPG', NOW());

";
fwrite($fp, $vaccinesTable);

// 3. Hospital Vaccines Table
$hospitalVaccinesTable = "
CREATE TABLE IF NOT EXISTS `hospital_vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`hospital_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

";
fwrite($fp, $hospitalVaccinesTable);

// 4. Appointments Table
$appointmentsTable = "
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) DEFAULT NULL,
  `type` enum('test','vaccination') NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','approved','completed','cancelled','rejected') DEFAULT 'pending',
  `result` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hospital_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

";
fwrite($fp, $appointmentsTable);

// 5. Audit Logs Table
$auditTable = "
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

";
fwrite($fp, $auditTable);

// 6. Articles Table
$articlesTable = "
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `articles` (`id`, `title`, `content`, `image`, `url`, `created_at`) VALUES
(1, 'Understanding COVID-19 Variants', 'COVID-19 variants like Delta and Omicron have changed the way the virus spreads. Learn about the symptoms and precautions.', 'https://max-website20-images.s3.ap-south-1.amazonaws.com/LF_7_and_NB_1_8_1_2020166e24.jpg', 'https://www.who.int/health-topics/coronavirus', NOW()),
(2, 'Importance of Vaccination', 'Vaccines are the most effective way to protect yourself and your loved ones from severe illness caused by COVID-19.', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrIC3ILFmtXdXSPdaAWbzki3W8a8KruM8s_Q&s', 'https://www.cdc.gov/coronavirus/2019-ncov/vaccines/index.html', NOW()),
(3, 'Post-COVID care tips', 'Recovering from COVID-19 can take time. Here are some tips to help you regain your strength and health after infection.', 'https://www.royalclinicdubai.com/wp-content/uploads/2023/12/Post-Covid-Rehabilitation-infographic-in-Dubai-1.jpg', 'https://www.who.int/emergencies/diseases/novel-coronavirus-2019/advice-for-public', NOW());

";
fwrite($fp, $articlesTable);

fwrite($fp, "\nCOMMIT;\n");
fclose($fp);

echo "Clean SQL Dump Created: $filename\n";
echo "Database contains:\n";
echo "- 1 Admin user (admin@medicore.com / password)\n";
echo "- 10 Vaccines\n";
echo "- 3 Articles\n";
echo "- Empty tables for: hospitals, patients, appointments, hospital_vaccines, audit_logs\n";
?>
