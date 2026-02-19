<?php
/**
 * Update Appointment Status (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital') {
    die('Unauthorized');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id || !in_array($action, ['approve', 'reject', 'cancel'])) {
    die('Invalid Request');
}

try {
    $status = ($action == 'approve') ? 'approved' : (($action == 'reject') ? 'rejected' : 'cancelled');
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND hospital_id = ?");
    if ($stmt->execute([$status, $id, $_SESSION['user_id']])) {
        header('Location: hospital_dashboard.php?msg=status_updated');
        exit();
    } else {
        die('Update failed');
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
