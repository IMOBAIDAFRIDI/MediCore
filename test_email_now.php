<?php
require_once 'mail_helper.php';

$start = microtime(true);

echo "<h2>MediCore SMTP Direct Test</h2>";
echo "<p>Sending email directly (no queue)...</p>";

$result = sendMailNow(
    'imobaidafridi@gmail.com',
    '✅ MediCore Direct SMTP Test - ' . date('H:i:s'),
    '<b>Yeh email directly SMTP se aayi hai!</b><br>No queue, no background process.<br>Time: ' . date('d M Y, h:i:s A')
);

$elapsed = round(microtime(true) - $start, 2);

if ($result) {
    echo "<p style='color:green;font-size:18px;'><b>✅ SUCCESS!</b> Email sent in <b>{$elapsed} seconds</b></p>";
} else {
    echo "<p style='color:red;font-size:18px;'><b>❌ FAILED</b> after {$elapsed} seconds</p>";
    echo "<p>Check PHP error log for details.</p>";
}
?>
