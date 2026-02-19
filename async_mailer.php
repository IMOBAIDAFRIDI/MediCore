<?php
/**
 * async_mailer.php
 * Called non-blocking via curl from sendMailAsync()
 * Receives email data via POST, sends immediately via SMTP
 */

// Only accept local requests
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    exit('Forbidden');
}

ignore_user_abort(true);
set_time_limit(30);

require_once __DIR__ . '/mail_helper.php';

$to          = $_POST['to']          ?? '';
$subject     = $_POST['subject']     ?? '';
$body        = $_POST['body']        ?? '';
$attachment  = $_POST['attachment']  ?? null;
$del_after   = $_POST['del_after']   ?? '0';
$queue_id    = intval($_POST['queue_id'] ?? 0);

if (empty($to)) exit('No recipient');

// Send email
$result = sendMailNow($to, $subject, $body, $attachment ?: null);

// Delete attachment if needed
if ($result && $del_after == '1' && $attachment && file_exists($attachment)) {
    @unlink($attachment);
}

// Update queue record if provided
if ($queue_id > 0) {
    global $pdo;
    try {
        $status = $result ? 'sent' : 'failed';
        $pdo->prepare("UPDATE email_queue SET status = ?, sent_at = NOW() WHERE id = ?")
            ->execute([$status, $queue_id]);
    } catch (Exception $e) {
        error_log("async_mailer queue update failed: " . $e->getMessage());
    }
}

echo $result ? 'OK' : 'FAIL';
?>
