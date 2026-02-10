<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=covid_appointments_report.csv');

$output = fopen('php://output', 'w');

// Header Row
fputcsv($output, array('Appointment ID', 'Patient Name', 'Hospital Name', 'Type', 'Date', 'Time', 'Status', 'Result', 'Result Date'));

// Fetch Data
$sql = "SELECT a.id, p.name as pname, h.name as hname, a.type, a.appointment_date, a.appointment_time, a.status, a.result, a.result_date 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN hospitals h ON a.hospital_id = h.id 
        ORDER BY a.appointment_date DESC";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
?>
