<?php
session_start();
include 'db_connect.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];
$appt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Appointment Details
$sql = "SELECT a.*, p.name as pname, p.email FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.id = $appt_id AND a.hospital_id = $hospital_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Invalid Request or Appointment not found.";
    exit();
}

$appointment = $result->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result_status = $_POST['result'];
    $remarks = $conn->real_escape_string($_POST['remarks']); // Basic sanitization

    // Validate result based on type
    // Start with a basic update query
    $update_sql = "UPDATE appointments SET 
                   status = 'completed', 
                   result = '$result_status', 
                   remarks = '$remarks' 
                   WHERE id = $appt_id AND hospital_id = $hospital_id";

    if ($conn->query($update_sql) === TRUE) {
        
        // Send Email Notification
        if (!empty($appointment['email'])) {
            include_once 'mail_helper.php';
            $subject = "Medical Report Available - MediCore";
            $body = "Dear <strong>{$appointment['pname']}</strong>,<br><br>
                     Your medical report is now available.<br>
                     <strong>Result Status:</strong> " . ucfirst($result_status) . "<br>";
            
            if (!empty($remarks)) {
                $body .= "<strong>Remarks:</strong> $remarks<br>";
            }
            
            $body .= "<br>Please login to your patient dashboard to view the full details/certificate.";
            
            sendMail($appointment['email'], $subject, $body);
        }

        header("Location: hospital_dashboard.php");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Result</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <div class="container" style="display: flex; justify-content: center; padding-top: 60px; padding-bottom: 60px;">
        <div class="auth-box animate-fade" style="width: 100%; max-width: 600px;">
            <h2 style="margin-bottom: 20px; text-align: center;">Update Patient Result</h2>
            
            <?php if(isset($error)) echo "<div class='badge badge-danger' style='width:100%; text-align:center; margin-bottom:20px;'>$error</div>"; ?>

            <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--glass-border);">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span style="color:var(--text-muted);">Patient Name</span>
                    <span style="font-weight:600;"><?php echo $appointment['pname']; ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span style="color:var(--text-muted);">Appointment Type</span>
                    <span class="badge badge-warning"><?php echo strtoupper($appointment['type']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted);">Scheduled Date</span>
                    <span><?php echo $appointment['appointment_date']; ?> <small style="color:var(--text-muted);"><?php echo $appointment['appointment_time']; ?></small></span>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="result">Result / Status</label>
                    <select name="result" id="result" required>
                        <option value="">-- Select Result --</option>
                        <?php if ($appointment['type'] == 'test'): ?>
                            <option value="negative">Negative</option>
                            <option value="positive">Positive</option>
                        <?php else: ?>
                            <option value="vaccinated">Vaccinated</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="remarks">Remarks (Optional)</label>
                    <textarea name="remarks" id="remarks" rows="4" placeholder="Enter any additional notes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Update & Complete</button>
            </form>
        </div>
    </div>
</body>
</html>
