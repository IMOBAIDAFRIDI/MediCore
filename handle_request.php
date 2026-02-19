<?php
/**
 * Handle Hospital Requests (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die('Unauthorized');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    die('Invalid Request');
}

try {
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'hospital'");
    
    if ($stmt->execute([$status, $id])) {
        // Queue status update email
        require_once 'mail_helper.php';
        $stmtHosp = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmtHosp->execute([$id]);
        $hData = $stmtHosp->fetch();

        $subject = "Application Status Update - MediCore";
        if ($action == 'approve') {
            $body = "Dear <strong>{$hData['name']}</strong>,<br><br>
                     Congratulations! Your application to join the MediCore Network has been <strong>APPROVED</strong>.<br>
                     You can now log in to your dashboard to manage vaccines and appointments.";
        } else {
            $body = "Dear <strong>{$hData['name']}</strong>,<br><br>
                     We regret to inform you that your application to join the MediCore Network has been <strong>REJECTED</strong> at this time.<br>
                     Please contact support if you believe this was in error.";
        }
        
        sendMail($hData['email'], $subject, $body);

        header('Location: admin_dashboard.php?msg=' . $status);
        exit();
    } else {
        die('Update failed');
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
