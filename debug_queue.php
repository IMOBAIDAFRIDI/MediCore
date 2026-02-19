<?php
include 'db_connect_pdo.php';
include 'mail_helper.php';

echo "<h2>Email Queue Debugger</h2>";

// Check pending count
$stmt = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status='pending'");
$pending = $stmt->fetchColumn();
echo "Pending Emails: $pending<br>";

// Show last 5 emails
$stmt = $pdo->query("SELECT * FROM email_queue ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>ID</th><th>To</th><th>Subject</th><th>Status</th><th>Created</th></tr>";
foreach($rows as $row) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['to_email']}</td>
        <td>{$row['subject']}</td>
        <td>{$row['status']}</td>
        <td>{$row['created_at']}</td>
    </tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Processing Queue Now...</h3>";

// Force process
$processed = processEmailQueue(5);
echo "Processed: $processed emails.<br>";

// Check failed ones
$stmt = $pdo->query("SELECT * FROM email_queue WHERE status='failed' ORDER BY id DESC LIMIT 5");
$failed = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($failed) > 0) {
    echo "<h3>Recent Failures</h3>";
    foreach($failed as $row) {
        echo "ID: {$row['id']} - To: {$row['to_email']} - Status: {$row['status']} <br>";
    }
    echo "Check PHP error log for PHPMailer details.";
}
?>
