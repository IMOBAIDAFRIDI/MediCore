<?php
include 'mail_helper.php';
include 'db_connect_pdo.php';

echo "<h2>Testing Queue Insertion</h2>";

$to = "test_recipient@example.com";
$subject = "Test Queue Insertion";
$body = "This is a test body.";

if (sendMail($to, $subject, $body)) {
    echo "SUCCESS: Added to queue.<br>";
} else {
    echo "FAILURE: Could not add to queue.<br>";
    print_r($pdo->errorInfo());
}

// Check count
$stmt = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE to_email = '$to'");
echo "Count for $to: " . $stmt->fetchColumn();
?>
