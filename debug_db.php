<?php
include 'db_connect.php';

echo "<h2>Appointment & Email Diagnosis</h2>";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check Appointments with Patient Emails
$sql = "SELECT a.id, a.status, a.hospital_id, p.name, p.email 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        ORDER BY a.id DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' cellspacing='0' cellpadding='5'>";
    echo "<tr><th>Appt ID</th><th>Status</th><th>Hospital ID</th><th>Patient Name</th><th>Patient Email</th><th>Email Valid?</th></tr>";
    while($row = $result->fetch_assoc()) {
        $valid = filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? "Yes" : "NO";
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['hospital_id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>$valid</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No appointments found.";
}

// Check if any hospital has pending appointments
$sql_pending = "SELECT COUNT(*) as count FROM appointments WHERE status='pending'";
$pending = $conn->query($sql_pending)->fetch_assoc()['count'];
echo "<h3>Total Pending Appointments: $pending</h3>";

$conn->close();
?>
