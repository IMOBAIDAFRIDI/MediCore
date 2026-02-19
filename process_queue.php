<?php
/**
 * Process Email Queue (Legacy Standalone)
 */
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'mail_helper.php';

// This script can be called by cron or AJAX
$count = processEmailQueue(10);

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'processed' => $count]);
