<?php
/**
 * Background Mailer
 * Called in background by hospital_action.php
 * Processes email_queue — max 10 seconds total
 */

// No session needed, runs as background process
ignore_user_abort(true);   // keep running even if HTTP connection closes
set_time_limit(15);        // hard PHP limit

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect_pdo.php';
require_once __DIR__ . '/mail_helper.php';

$start = microtime(true);
$MAX_SECONDS = 10;

try {
    // Pick pending emails (newest first so action emails go first)
    $stmt = $pdo->prepare("
        SELECT * FROM email_queue
        WHERE status = 'pending'
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($emails as $email) {
        // Stop if we've used 10 seconds
        if ((microtime(true) - $start) >= $MAX_SECONDS) break;

        $sent = sendMailNow(
            $email['to_email'],
            $email['subject'],
            $email['body'],
            $email['attachment'] ?? null
        );

        $status = $sent ? 'sent' : 'failed';

        // Update queue status
        $pdo->prepare("UPDATE email_queue SET status = ?, sent_at = NOW() WHERE id = ?")
            ->execute([$status, $email['id']]);

        // Delete attachment file if needed
        if ($sent && !empty($email['delete_attachment']) && $email['delete_attachment'] == 1
            && !empty($email['attachment']) && file_exists($email['attachment'])) {
            @unlink($email['attachment']);
        }
    }

} catch (Exception $e) {
    error_log("bg_mailer error: " . $e->getMessage());
}
?>
