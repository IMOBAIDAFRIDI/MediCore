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

function sendMail($to, $subject, $body) {
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
        // Log error instead of displaying to user to avoid breaking flow
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
