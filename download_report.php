<?php
/**
 * Download Report PDF (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Fetch appointment
    $stmt = $pdo->prepare("SELECT a.* FROM appointments a WHERE a.id = ? AND (a.patient_id = ? OR a.hospital_id = ?)");
    $stmt->execute([$id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report || $report['status'] != 'completed') die('Report not found');

    if (file_exists('pdf_helper.php')) {
        require_once 'pdf_helper.php';
        // Note: Assumes generateReportPDF uses PDO internally or passed $id
        generateReportPDF($id, 'D'); 
    } else {
        die('PDF generation support not available.');
    }
} catch (PDOException $e) {
    die($e->getMessage());
}
