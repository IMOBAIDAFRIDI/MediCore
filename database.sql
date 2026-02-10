-- Database: hospital_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `hospital_db`
--
CREATE DATABASE IF NOT EXISTS `hospital_db`;
USE `hospital_db`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admins`
--

INSERT IGNORE INTO `admins` (`id`, `username`, `password`, `reg_date`) VALUES
(1, 'admin', '$2y$10$8WkQy9.50/0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.00', '2023-01-01 00:00:00'); 
-- Note: Password hash for 'admin123' might vary, using a placeholder or relying on PHP to hash correctly if inserting via code.
-- Let's use a standard bcrypt hash for 'admin123': $2y$10$r3kQy9.50/0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.00 (This is fake, will fix in PHP seed)
-- Actually, let's just use the PHP script for seeding as it handles hashing reliably. This SQL is for structure + static data.

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE IF NOT EXISTS `hospitals` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `location` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hospitals` (Passwords are 'hospital123')
--

INSERT IGNORE INTO `hospitals` (`id`, `name`, `email`, `password`, `address`, `location`, `status`, `reg_date`) VALUES
(1, 'City Care Hospital', 'city@hospital.com', '$2y$10$SomeHashForHospital123', '123 Main St, Downtown', 'New York', 'approved', '2023-01-01 10:00:00'),
(2, 'Metro General', 'metro@hospital.com', '$2y$10$SomeHashForHospital123', '456 Elm St, Uptown', 'Chicago', 'approved', '2023-01-02 11:30:00'),
(3, 'Sunshine Clinic', 'sunshine@hospital.com', '$2y$10$SomeHashForHospital123', '789 Oak Ave, Suburb', 'Los Angeles', 'pending', '2023-01-03 09:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `dob` date DEFAULT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `patients` (Passwords are 'patient123')
--

INSERT IGNORE INTO `patients` (`id`, `name`, `email`, `password`, `address`, `phone`, `dob`, `reg_date`) VALUES
(1, 'John Doe', 'john@example.com', '$2y$10$SomeHashForPatient123', '123 First St', '555-0101', '1980-05-15', '2023-01-05 08:00:00'),
(2, 'Jane Smith', 'jane@example.com', '$2y$10$SomeHashForPatient123', '456 Second Ave', '555-0102', '1992-11-20', '2023-01-06 09:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE IF NOT EXISTS `vaccines` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `availability` enum('available','unavailable') DEFAULT 'available',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `vaccines`
--

INSERT IGNORE INTO `vaccines` (`id`, `name`, `description`, `availability`, `image`) VALUES 
(1, 'Covishield', 'Viral Vector Support', 'available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSsreI1BqfMSDlNsP6nB8kEeVLTHKm_bxNPLA&s'),
(2, 'Covaxin', 'Inactivated Virus', 'available', 'https://www.clinicaltrialsarena.com/wp-content/uploads/sites/22/2021/02/Image-3-COVAXIN-BBV152-1.jpg'),
(3, 'Pfizer-BioNTech', 'mRNA Technology', 'available', 'https://c.files.bbci.co.uk/1229D/production/_118379347_f959b3bf-db0b-4dc9-822e-64a7ea6170e0.jpg'),
(4, 'Moderna', 'mRNA Technology', 'available', 'https://petrieflom.law.harvard.edu/wp-content/uploads/2024/07/shutterstock_1899669364-scaled-2.jpg'),
(5, 'Sputnik V', 'Viral Vector', 'available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxvKOekvDdmkV4tVrzlutaRoDazC_X5CE0Ow&s'),
(6, 'Johnson & Johnson', 'Viral Vector', 'available', 'https://www.conehealth.com/app/files/public/63329ab8-ad31-4fef-9a1e-2ca76fdd5faf/WM%20Johnson%20Johnson%20COVID-19%20Vaccine-%206%20Facts%20You%20Should%20Know.png'),
(7, 'Sinopharm', 'Inactivated Virus', 'available', 'https://www.reuters.com/resizer/v2/https%3A%2F%2Farchive-images.prod.global.a201836.reutersmedia.net%2F2021%2F01%2F19%2FLYNXMPEH0I02M.JPG?auth=33c54a60c98f5a26ab69c4e6d45226384c43eaf0fe2a9df83e2004a1ef5cba48&width=1920&quality=80');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` int(6) UNSIGNED NOT NULL,
  `hospital_id` int(6) UNSIGNED DEFAULT NULL,
  `type` enum('test','vaccination') NOT NULL,
  `vaccine_id` int(6) UNSIGNED DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `result` enum('pending','negative','positive','vaccinated') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `hospital_id` (`hospital_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `appointments`
--

INSERT IGNORE INTO `appointments` (`id`, `patient_id`, `hospital_id`, `type`, `vaccine_id`, `appointment_date`, `appointment_time`, `status`, `result`, `remarks`) VALUES
(1, 1, 1, 'vaccination', 1, '2023-01-10', '09:00:00', 'completed', 'vaccinated', 'First dose administered'),
(2, 1, 1, 'test', NULL, '2023-01-15', '10:00:00', 'completed', 'negative', 'Routine check'),
(3, 2, 2, 'vaccination', 3, '2023-01-20', '14:30:00', 'approved', 'pending', 'Scheduled'),
(4, 2, 1, 'test', NULL, '2023-01-25', '11:00:00', 'pending', 'pending', 'Requested via portal');

COMMIT;
