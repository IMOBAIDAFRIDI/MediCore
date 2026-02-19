<?php
/**
 * Update Result (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital') {
    header('Location: login.php');
    exit();
}

$hospital_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Fetch appointment
    $stmt = $pdo->prepare("SELECT a.*, p.name as patient_name FROM appointments a JOIN users p ON a.patient_id = p.id WHERE a.id = ? AND a.hospital_id = ?");
    $stmt->execute([$id, $hospital_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) die('Appointment not found');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) die('Invalid CSRF');

        $result = $_POST['result'];
        $remarks = $_POST['remarks'];

        $stmt = $pdo->prepare("UPDATE appointments SET result = ?, remarks = ?, status = 'completed' WHERE id = ? AND hospital_id = ?");
        if ($stmt->execute([$result, $remarks, $id, $hospital_id])) {
            // Queue result notification email
            require_once 'mail_helper.php';
            $stmtPatient = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmtPatient->execute([$appt['patient_id']]);
            $pData = $stmtPatient->fetch();

            $subject = "Medical Result Updated - MediCore";
            $body = "Dear <strong>{$pData['name']}</strong>,<br><br>
                     Your medical report for the appointment on <strong>{$appt['appointment_date']}</strong> has been updated.<br>
                     <strong>Result:</strong> " . strtoupper($result) . "<br>
                     <strong>Remarks:</strong> " . (empty($remarks) ? 'N/A' : htmlspecialchars($remarks)) . "<br><br>
                     You can check your dashboard for the full digital report.";
            
            sendMail($pData['email'], $subject, $body);

            header('Location: hospital_dashboard.php?msg=result_updated');
            exit();
        }
    }
} catch (PDOException $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Result | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .result-box { max-width: 600px; margin: 40px auto; padding: 40px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: var(--text-muted); }
        select, textarea { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="glass-card result-box animate-fade">
            <h2 style="text-align:center; margin-bottom: 30px;">Update Patient Result</h2>
            
            <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($appt['patient_name']); ?></p>
                <p><strong>Type:</strong> <?php echo strtoupper($appt['type']); ?></p>
                <p><strong>Date:</strong> <?php echo $appt['appointment_date']; ?></p>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label>Result / Status</label>
                    <select name="result" required>
                        <option value="">-- Select Result --</option>
                        <?php if ($appt['type'] == 'test'): ?>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                        <?php else: ?>
                            <option value="vaccinated">Vaccinated</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="4" placeholder="Additional notes..."></textarea>
                </div>

                <div style="display:flex; gap:15px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary flex-1" style="padding:12px;">Submit & Complete</button>
                    <a href="hospital_dashboard.php" class="btn btn-outline flex-1" style="padding:12px; text-align:center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
