<?php
// Quick PDF generation test
$appt = [
    'id'               => 99,
    'patient_name'     => 'Ali Hassan',
    'patient_email'    => 'ali@example.com',
    'patient_phone'    => '0300-1234567',
    'hospital_name'    => 'City General Hospital',
    'hospital_location'=> 'Peshawar, KPK',
    'appointment_date' => '2026-02-18',
    'appointment_time' => '10:30:00',
    'type'             => 'vaccination',
    'vaccine_name'     => 'Pfizer-BioNTech COVID-19',
];
$result_note  = 'Vaccination administered successfully. No adverse reactions observed.';
$auto_remarks = 'This is to certify that Ali Hassan successfully received the Pfizer-BioNTech COVID-19 vaccination at City General Hospital on 18 Feb 2026. The vaccination procedure was carried out by qualified medical personnel following all standard safety protocols and WHO guidelines. The patient was observed for the mandatory post-vaccination monitoring period and showed no immediate adverse reactions. Outcome: Completed. The patient is advised to monitor for any delayed reactions over the next 48-72 hours and to contact the hospital immediately if any unusual symptoms arise. This certificate serves as an official record of vaccination administered under the MediCore Health System.';

require_once __DIR__ . '/hospital_action.php';
// Just call the function
$path = generateAppointmentReport($appt, $result_note, 1, $auto_remarks);
echo file_exists($path) ? "PDF created: $path" : "FAILED";
?>
