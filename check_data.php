<?php
include 'db_connect_pdo.php';

echo "<h2>Check Appointments Data</h2>";

$sql = "SELECT a.id, a.patient_id, a.hospital_id, a.status, p.email as p_email, h.email as h_email 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN hospitals h ON a.hospital_id = h.id";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>Appt ID</th><th>Patient Email</th><th>Hospital Email</th><th>Status</th></tr>";
foreach($rows as $row) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['p_email']}</td>
        <td>{$row['h_email']}</td>
        <td>{$row['status']}</td>
    </tr>";
}
echo "</table>";
?>
