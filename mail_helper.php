<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/env_loader.php';

// Load .env file
loadEnv(__DIR__ . '/.env');

// Use global PDO connection
require_once __DIR__ . '/db_connect_pdo.php';

// Internal function to actually send mail via SMTP
function sendMailNow($to, $subject, $body, $attachment = null) {
    if (empty($to)) return false;

    $mail = new PHPMailer(true);
    
    $smtp_email = getenv('SMTP_EMAIL');
    $smtp_password = getenv('SMTP_PASSWORD');

    try {
        $mail->isSMTP();
        $mail->Host        = 'smtp.gmail.com';
        $mail->SMTPAuth    = true;
        $mail->Username    = $smtp_email;
        $mail->Password    = $smtp_password;
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_SMTPS;  // SSL port 465 — faster than STARTTLS
        $mail->Port        = 465;
        $mail->Timeout     = 8;
        $mail->SMTPKeepAlive = false;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom($smtp_email, 'MediCore System');
        $mail->addAddress($to);

        if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment); 
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #0d6efd;'>MediCore Notification</h2>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>
                    $body
                </div>
                <p style='color: #6c757d; font-size: 12px; margin-top: 20px;'>Do not reply to this email. This is an automated message.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed to $to: {$mail->ErrorInfo}");
        return false;
    }
}

// Public function — queues email then fires async curl (user gets instant redirect)
function sendMail($to, $subject, $body, $attachment = null, $delete_after = false) {
    global $pdo;

    // 1. Save to queue table first (so we have a record)
    $queue_id = 0;
    try {
        $stmt = $pdo->prepare("INSERT INTO email_queue (to_email, subject, body, attachment, delete_attachment, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$to, $subject, $body, $attachment, $delete_after ? 1 : 0]);
        $queue_id = $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Queue insert failed: " . $e->getMessage());
    }

    // 2. Fire async non-blocking HTTP request to async_mailer.php
    //    curl returns immediately — email sends in background
    // In Docker (Render) Apache serves the app at / on $PORT; locally under /medicore.
    $async_url = getenv('PORT')
        ? 'http://127.0.0.1:' . getenv('PORT') . '/async_mailer.php'
        : 'http://127.0.0.1/medicore/async_mailer.php';
    $post_data = http_build_query([
        'to'         => $to,
        'subject'    => $subject,
        'body'       => $body,
        'attachment' => $attachment ?? '',
        'del_after'  => $delete_after ? '1' : '0',
        'queue_id'   => $queue_id,
    ]);

    $ch = curl_init($async_url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS     => 500,   // Only wait 500ms — then abandon (fire & forget)
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_NOSIGNAL       => 1,     // Required for ms-level timeouts
    ]);
    curl_exec($ch);
    curl_close($ch);

    return true; // Always return true — email will be sent async
}

// Process queue
function processEmailQueue($limit = 5) {
    global $pdo;
    try {
        set_time_limit(20);
        $stmt = $pdo->prepare("SELECT * FROM email_queue WHERE status='pending' ORDER BY created_at ASC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach($emails as $email) {
            if (sendMailNow($email['to_email'], $email['subject'], $email['body'], $email['attachment'])) {
                $status = 'sent';
                $count++;
                if ($email['delete_attachment'] == 1 && $email['attachment'] && file_exists($email['attachment'])) {
                    @unlink($email['attachment']);
                }
            } else {
                $status = 'failed';
            }
            
            $stmtUpd = $pdo->prepare("UPDATE email_queue SET status = ?, sent_at = NOW() WHERE id = ?");
            $stmtUpd->execute([$status, $email['id']]);
        }
        return $count;
    } catch (Exception $e) {
        error_log("Failed to process email queue: " . $e->getMessage());
        return 0;
    }
}
?>
