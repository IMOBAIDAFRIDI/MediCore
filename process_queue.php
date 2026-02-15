<?php
include 'db_connect.php';
include 'mail_helper.php';

header('Content-Type: application/json');

// prevent direct access if needed, but for now simple is fine
$count = processEmailQueue(5);

echo json_encode(['status' => 'success', 'processed' => $count]);
?>
