<?php
/**
 * Edit Appointment (Legacy Standalone)
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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hospital_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT a.*, p.name as patient_name FROM appointments a JOIN users p ON a.patient_id = p.id WHERE a.id = ? AND a.hospital_id = ?");
    $stmt->execute([$id, $hospital_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) die('Appointment not found');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) die('Invalid CSRF');

        $date = $_POST['date'];
        $time = $_POST['time'];
        $status = $_POST['status'];
        $remarks = $_POST['remarks'];

        $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = ?, remarks = ? WHERE id = ? AND hospital_id = ?");
        if ($stmt->execute([$date, $time, $status, $remarks, $id, $hospital_id])) {
            header('Location: hospital_dashboard.php?msg=updated');
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
    <title>Edit Appointment | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-box { max-width: 500px; margin: 40px auto; padding: 40px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: var(--text-muted); }
        input, select, textarea { width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="glass-card edit-box animate-fade">
            <h2 style="text-align:center; margin-bottom: 30px;">Edit Appointment</h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label>Patient Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($appt['patient_name']); ?>" disabled style="opacity:0.6;">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;" class="form-group">
                    <div>
                        <label>Date</label>
                        <input type="date" name="date" value="<?php echo $appt['appointment_date']; ?>" required>
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="time" value="<?php echo date('H:i', strtotime($appt['appointment_time'])); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="pending" <?php echo $appt['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $appt['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $appt['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $appt['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="3"><?php echo htmlspecialchars($appt['remarks'] ?? ''); ?></textarea>
                </div>

                <div style="display:flex; gap:15px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary flex-1">Update</button>
                    <a href="hospital_dashboard.php" class="btn btn-outline flex-1" style="text-align:center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
