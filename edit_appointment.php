<?php
session_start();
include 'db_connect.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id']; // Get hospital ID from session
$appt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status = $_POST['status'];
    $remarks = $conn->real_escape_string($_POST['remarks']);

    $sql = "UPDATE appointments SET 
            appointment_date = '$date', 
            appointment_time = '$time', 
            status = '$status', 
            remarks = '$remarks' 
            WHERE id = $appt_id AND hospital_id = $hospital_id"; // Ensure filtering by hospital

    if ($conn->query($sql) === TRUE) {
        header("Location: hospital_dashboard.php");
        exit();
    } else {
        $error = "Error updating appointment: " . $conn->error;
    }
}

// Fetch Data
$sql = "SELECT a.*, p.name as pname FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.id = $appt_id AND a.hospital_id = $hospital_id"; // Ensure filtering by hospital
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Appointment not found or access denied.");
}
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Appointment</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh;">
    
    <div class="auth-box animate-fade" style="width:100%; max-width:500px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3>Edit Appointment</h3>
            <a href="hospital_dashboard.php" class="btn btn-outline" style="padding:4px 8px;"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <?php if(isset($error)) echo "<p style='color:var(--danger);'>$error</p>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Patient Name</label>
                <input type="text" value="<?php echo $row['pname']; ?>" disabled style="opacity:0.7;">
            </div>

            <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div>
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo $row['appointment_date']; ?>" required>
                </div>
                <div>
                    <label>Time</label>
                    <input type="time" name="time" value="<?php echo date('H:i', strtotime($row['appointment_time'])); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="pending" <?php if($row['status']=='pending') echo 'selected'; ?>>Pending</option>
                    <option value="approved" <?php if($row['status']=='approved') echo 'selected'; ?>>Approved</option>
                    <option value="rejected" <?php if($row['status']=='rejected') echo 'selected'; ?>>Rejected</option>
                    <option value="completed" <?php if($row['status']=='completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" rows="3"><?php echo $row['remarks']; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Update Appointment</button>
        </form>
    </div>

</body>
</html>
