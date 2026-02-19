<?php
/**
 * View Report (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Fetch appointment with hospital and patient info
    $stmt = $pdo->prepare("SELECT a.*, p.name as pname, p.phone, h.name as hname, h.address as haddress 
                          FROM appointments a 
                          JOIN users p ON a.patient_id = p.id 
                          JOIN users h ON a.hospital_id = h.id 
                          WHERE a.id = ? AND (a.patient_id = ? OR a.hospital_id = ?)");
    $stmt->execute([$id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report || $report['status'] != 'completed') die('Report not found or not ready.');
} catch (PDOException $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report - #<?php echo $report['id']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #030712; color: white; padding: 40px; }
        .report-card { background: #111827; max-width: 800px; margin: 0 auto; padding: 40px; border-radius: 20px; border: 1px solid #374151; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .header { border-bottom: 2px solid #374151; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-start; }
        .h-details h1 { margin: 0; color: #00f2fe; font-size: 28px; }
        .h-details p { margin: 5px 0; color: #9ca3af; }
        .status-badge { padding: 8px 16px; border-radius: 99px; font-weight: bold; font-size: 12px; background: rgba(0, 242, 254, 0.1); color: #00f2fe; text-transform: uppercase; }
        .section { margin-bottom: 40px; }
        .section h3 { border-bottom: 1px solid #374151; padding-bottom: 10px; color: #f3f4f6; margin-bottom: 20px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .label { font-size: 11px; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; display: block; tracking-wider: 1px; }
        .value { font-size: 16px; font-weight: 600; color: #f3f4f6; }
        .result-box { text-align: center; padding: 40px; background: rgba(255,255,255,0.03); border: 2px dashed #374151; border-radius: 16px; }
        .result-val { font-size: 32px; font-weight: 800; letter-spacing: 1px; }
        .positive { color: #f43f5e; }
        .negative { color: #10b981; }
        .vaccinated { color: #3b82f6; }
        .btn-print { background: #4b5563; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; }
        @media print {
            body { background: white; color: black; padding: 0; }
            .report-card { box-shadow: none; border: none; background: white; color: black; max-width: 100%; border: 1px solid #eee; }
            .btn-print { display: none; }
            .section h3 { color: black; border-color: #eee; }
            .value { color: black; }
            .h-details h1 { color: #000; }
        }
    </style>
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto 20px; display: flex; justify-content: space-between;">
        <a href="javascript:history.back()" style="color: #9ca3af; text-decoration: none;"><i class="fa fa-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn-print"><i class="fa fa-print"></i> Print Report</button>
    </div>
    <div class="report-card">
        <div class="header">
            <div class="h-details">
                <h1>Medical Report</h1>
                <p><strong><?php echo htmlspecialchars($report['hname']); ?></strong></p>
                <p><?php echo htmlspecialchars($report['haddress']); ?></p>
            </div>
            <span class="status-badge"><?php echo strtoupper($report['type']); ?></span>
        </div>

        <div class="section">
            <h3>Patient Information</h3>
            <div class="grid">
                <div class="field">
                    <span class="label">Patient Name</span>
                    <div class="value"><?php echo htmlspecialchars($report['pname']); ?></div>
                </div>
                <div class="field">
                    <span class="label">Appointment ID</span>
                    <div class="value">#<?php echo $report['id']; ?></div>
                </div>
                 <div class="field">
                    <span class="label">Collection Date</span>
                    <div class="value"><?php echo $report['appointment_date']; ?></div>
                </div>
                 <div class="field">
                    <span class="label">Contact Number</span>
                    <div class="value"><?php echo $report['phone']; ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Detection Results</h3>
            <div class="result-box">
                <span class="label">Official Outcome</span>
                <div class="result-val <?php echo $report['result']; ?>"><?php echo strtoupper($report['result']); ?></div>
                <?php if(!empty($report['remarks'])): ?>
                    <p style="margin-top: 20px; color: #9ca3af; line-height: 1.6;"><?php echo htmlspecialchars($report['remarks']); ?></p>
                <?php endif; ?>
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); font-size: 11px; color: #6b7280;">
                    CERTIFIED BY MEDICORE HEALTHCARE NETWORK • ISO 9001:2015
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 50px; color: #4b5563; font-size: 12px; font-style: italic;">
            <p>This is a digitally verified report. Authenticity can be checked via your MediCore account.</p>
        </div>
    </div>
</body>
</html>
