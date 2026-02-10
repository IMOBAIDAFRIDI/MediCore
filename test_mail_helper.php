<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'mail_helper.php';

echo "Attempting to send mail via helper...\n";

if (sendMail('imobaidafridi@gmail.com', 'Helper Test', 'This is a test from mail_helper.php')) {
    echo "Mail sent successfully via helper.";
} else {
    echo "Mail FAILED via helper.";
}
?>
