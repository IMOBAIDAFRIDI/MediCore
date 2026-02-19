<?php
require_once 'mail_helper.php';

echo "Testing with documented password (with spaces)...\n";
$res = sendMailNow('imobaidafridi@gmail.com', 'Test With Spaces', 'This is a test');
echo $res ? "SUCCESS\n" : "FAILED\n";

echo "\nTesting without spaces...\n";
$env_path = __DIR__ . '/.env';
$lines = file($env_path, FILE_IGNORE_NEW_LINES);
foreach($lines as &$line) {
    if(strpos($line, 'SMTP_PASSWORD') === 0) {
        $line = 'SMTP_PASSWORD=ymqcuacjsinykroj';
    }
}
file_put_contents($env_path, implode("\n", $lines));
// Reload env
putenv('SMTP_PASSWORD=ymqcuacjsinykroj');
$_ENV['SMTP_PASSWORD'] = 'ymqcuacjsinykroj';

$res = sendMailNow('imobaidafridi@gmail.com', 'Test Without Spaces', 'This is a test');
echo $res ? "SUCCESS\n" : "FAILED\n";
?>
