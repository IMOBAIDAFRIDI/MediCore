<?php
require_once 'mail_helper.php';

echo "Testing Port 465 with SSL...\n";
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_EMAIL');
    $mail->Password   = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;
    $mail->Timeout    = 10;

    $mail->setFrom(getenv('SMTP_EMAIL'), 'MediCore Debug');
    $mail->addAddress(getenv('SMTP_EMAIL'));
    $mail->isHTML(true);
    $mail->Subject = 'SSL Test';
    $mail->Body    = 'Testing SSL on port 465';

    $mail->send();
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "FAILED: " . $mail->ErrorInfo . "\n";
}
?>
