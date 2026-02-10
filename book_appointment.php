<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$hospitals = $conn->query("SELECT * FROM hospitals WHERE status='approved'");
$hospitals = $conn->query("SELECT * FROM hospitals WHERE status='approved'");
// $vaccines removed here, moved lower to depend on hospital_id
$error = "";
$success = "";

// Pre-fill from GET
$pre_hospital_id = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;
$pre_vaccine_id = isset($_GET['vaccine_id']) ? intval($_GET['vaccine_id']) : 0;
$pre_type = "";

// Fetch vaccines based on selected hospital
if ($pre_hospital_id > 0) {
    $sql_vac = "SELECT v.* FROM vaccines v 
                JOIN hospital_vaccines hv ON v.id = hv.vaccine_id 
                WHERE hv.hospital_id = $pre_hospital_id AND hv.status = 'available'";
    $vaccines = $conn->query($sql_vac);
} else {
    // If no hospital selected, empty list or maybe all? 
    // Better to show empty and prompt to select hospital first.
    // Or just empty object to avoid errors
    $vaccines = false; 
}

if($pre_vaccine_id) {
    $pre_type = "vaccination";
} elseif($pre_hospital_id) {
    $pre_type = isset($_GET['type']) ? $_GET['type'] : "test";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hospital_id = $_POST['hospital_id'];
    $type = $_POST['type'];
    $vaccine_id = (!empty($_POST['vaccine_id']) && $type == 'vaccination') ? $_POST['vaccine_id'] : "NULL";
    $date = $_POST['date'];
    $time = $_POST['time'];
    $patient_id = $_SESSION['user_id'];
    
    // Basic availability check (optional, skipping for simplicity)
    $sql = "INSERT INTO appointments (patient_id, hospital_id, type, vaccine_id, appointment_date, appointment_time) VALUES ($patient_id, $hospital_id, '$type', $vaccine_id, '$date', '$time')";
    
    if ($conn->query($sql) === TRUE) {
        $success = "Appointment request sent successfully! Wait for hospital approval.";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">Book Appointment</div>
                <div class="nav-links">
                    <a href="patient_dashboard.php" class="btn btn-outline">Back</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="display: flex; justify-content: center; padding-top: 60px; padding-bottom: 60px;">
        <div class="auth-box animate-fade" style="width: 100%; max-width: 600px;">
            <h2 style="text-align: center; margin-bottom: 20px;">Schedule Appointment</h2>
             <?php if($error) echo "<div class='badge badge-danger' style='display:block; text-align:center; margin-bottom:20px;'>$error</div>"; ?>
             <?php if($success) echo "<div class='badge badge-success' style='display:block; text-align:center; margin-bottom:20px;'>$success</div>"; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Select Hospital</label>
                    <select name="hospital_id" id="hospital-select" required onchange="window.location.href='?hospital_id='+this.value">
                        <option value="">-- Choose Hospital --</option>
                        <?php 
                        if ($hospitals->num_rows > 0) {
                            while($h = $hospitals->fetch_assoc()) {
                                $selected = ($h['id'] == $pre_hospital_id) ? "selected" : "";
                                echo "<option value='{$h['id']}' $selected>{$h['name']} ({$h['location']})</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Appointment Type</label>
                    <select name="type" id="type-select" required>
                        <option value="test" <?php echo ($pre_type == 'test') ? 'selected' : ''; ?>>Covid-19 Test</option>
                        <option value="vaccination" <?php echo ($pre_type == 'vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                    </select>
                </div>

                <div class="form-group" id="vaccine-group" style="display:none;">
                    <label>Select Vaccine</label>
                    <select name="vaccine_id" id="vaccine-select">
                        <option value="">-- Choose Vaccine --</option>
                        <?php 
                        if ($vaccines && $vaccines->num_rows > 0) {
                            while($v = $vaccines->fetch_assoc()) {
                                $selected = ($v['id'] == $pre_vaccine_id) ? "selected" : "";
                                echo "<option value='{$v['id']}' $selected>{$v['name']}</option>";
                            }
                        } else {
                            if (!$pre_hospital_id) {
                                echo "<option value='' disabled>Select a hospital first</option>";
                            } else {
                                echo "<option value='' disabled>No vaccines available at this hospital</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label>Date</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div style="flex: 1;">
                        <label>Time</label>
                        <input type="time" name="time" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Book Now</button>
            </form>
        </div>
    </div>
    </div>

    <script>
        const typeSelect = document.getElementById('type-select');
        const vacGroup = document.getElementById('vaccine-group');
        const vacSelect = document.getElementById('vaccine-select');

        function toggleVaccine() {
             if(typeSelect.value === 'vaccination') {
                vacGroup.style.display = 'block';
                vacSelect.required = true;
            } else {
                vacGroup.style.display = 'none';
                vacSelect.required = false;
                // Don't clear value immediately if it was pre-selected, logic might be tricky but for now standard behavior
                if (typeSelect.value !== 'vaccination') vacSelect.value = "";
            }
        }

        typeSelect.addEventListener('change', toggleVaccine);
        
        // Run on load to set initial state
        toggleVaccine();
    </script>
</body>
</html>
