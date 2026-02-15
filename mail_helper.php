<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once 'env_loader.php';

// Load .env file
loadEnv(__DIR__ . '/.env');

// Internal function to actually send mail via SMTP
function sendMailNow($to, $subject, $body, $attachment = null) {
    if (empty($to)) return false;

    $mail = new PHPMailer(true);
    
    $smtp_email = getenv('SMTP_EMAIL');
    $smtp_password = getenv('SMTP_PASSWORD');

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_email; 
        $mail->Password   = $smtp_password; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom($smtp_email, 'MediCore System');
        $mail->addAddress($to);

        // Attachments
        if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment); 
        }

        //Content
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
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Public function that queues the email instead of sending
function sendMail($to, $subject, $body, $attachment = null, $delete_after = false) {
    global $conn;
    
    // Escape content
    $to = $conn->real_escape_string($to);
    $subject = $conn->real_escape_string($subject);
    $body = $conn->real_escape_string($body);
    $attachment = $attachment ? "'" . $conn->real_escape_string($attachment) . "'" : "NULL";
    $del = $delete_after ? 1 : 0;
    
    $sql = "INSERT INTO email_queue (to_email, subject, body, attachment, delete_attachment, status) 
            VALUES ('$to', '$subject', '$body', $attachment, $del, 'pending')";
            
    return $conn->query($sql);
}

// Function to process the queue (called via AJAX)
function processEmailQueue($limit = 5) {
    global $conn;
    
    // Fetch pending emails
    $sql = "SELECT * FROM email_queue WHERE status='pending' LIMIT $limit";
    $result = $conn->query($sql);
    
    $count = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if (sendMailNow($row['to_email'], $row['subject'], $row['body'], $row['attachment'])) {
                $status = 'sent';
                $count++;
                
                // Cleanup attachment if requested
                if ($row['delete_attachment'] == 1 && $row['attachment'] && file_exists($row['attachment'])) {
                    unlink($row['attachment']);
                }
            } else {
                $status = 'failed';
            }
            
            // Update status
            $conn->query("UPDATE email_queue SET status='$status', sent_at=NOW() WHERE id={$row['id']}");
        }
    }
    return $count;
}
?>
