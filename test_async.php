<?php
require 'config.php';
require 'db_connect_pdo.php';
require 'mail_helper.php';

$start = microtime(true);
$r = sendMail('imobaidafridi@gmail.com', 'Async Test ' . date('H:i:s'), '<b>Async email test! User gets instant response.</b>');
$t = round(microtime(true) - $start, 3);

echo $r 
    ? "<p style='color:green;font-size:18px;'><b>✅ sendMail() returned in <b>{$t}s</b> — email sending in background!</p>"
    : "<p style='color:red;'>❌ FAILED in {$t}s</p>";
?>
