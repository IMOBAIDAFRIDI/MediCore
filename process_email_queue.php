<?php
// Background Email Processor
// This script processes emails from the queue
// Run this via AJAX or cron job

require_once __DIR__ . '/mail_helper.php';

// Process up to 5 emails at a time
$sent = processEmailQueue(2);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'sent' => $sent,
    'message' => "$sent email(s) processed"
]);
?>
