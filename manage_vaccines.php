<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];
$message = "";

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaccine_id']) && isset($_POST['status'])) {
    $vaccine_id = intval($_POST['vaccine_id']);
    $status = $_POST['status'] === 'available' ? 'available' : 'unavailable';
    
    // Check if entry exists
    $check_sql = "SELECT id FROM hospital_vaccines WHERE hospital_id = ? AND vaccine_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $hospital_id, $vaccine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update
        $update_sql = "UPDATE hospital_vaccines SET status = ? WHERE hospital_id = ? AND vaccine_id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("sii", $status, $hospital_id, $vaccine_id);
        $stmt_update->execute();
    } else {
        // Insert
        $insert_sql = "INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("iis", $hospital_id, $vaccine_id, $status);
        $stmt_insert->execute();
    }
    $message = "Vaccine status updated successfully.";
}

// Fetch all system vaccines
$sql_vaccines = "SELECT v.*, hv.status as hospital_status 
                 FROM vaccines v 
                 LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id AND hv.hospital_id = $hospital_id 
                 ORDER BY v.name ASC";
$result = $conn->query($sql_vaccines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Vaccines | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .vaccine-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .vaccine-item-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center; 
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .vaccine-info h3 {
            margin: 0 0 5px 0;
            color: var(--light);
            font-size: 1.1rem;
        }
        
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 34px;
            border: 1px solid var(--glass-border);
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: var(--text-muted);
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }
        
        input:checked + .slider:before {
            transform: translateX(24px);
            background-color: #10b981;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo"><i class="fa-solid fa-syringe"></i> Manage Vaccines</div>
                <div class="nav-links">
                    <a href="hospital_dashboard.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <div class="card animate-fade">
            <div style="font-size: 1.2rem; margin-bottom: 20px; font-weight: 600;">
                <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Vaccine Inventory Control
            </div>
            <p style="color: var(--text-muted); margin-bottom: 30px;">
                Toggle the switch to mark vaccines as available or unavailable at your facility. 
                Patients will only see vaccines marked as "Available" when booking with your hospital.
            </p>

            <?php if (!empty($message)): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="vaccine-list-grid">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $isChecked = ($row['hospital_status'] == 'available') ? 'checked' : '';
                        
                        echo '
                        <div class="vaccine-item-card">
                            <div class="vaccine-info">
                                <h3>'.$row['name'].'</h3>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">ID: #'.$row['id'].'</span>
                            </div>
                            <form method="POST" class="status-toggle">
                                <input type="hidden" name="vaccine_id" value="'.$row['id'].'">
                                <input type="hidden" name="status" value="'.($isChecked ? 'unavailable' : 'available').'">
                                <label class="switch">
                                    <input type="checkbox" '.$isChecked.' onchange="this.form.submit()">
                                    <span class="slider"></span>
                                </label>
                            </form>
                        </div>
                        ';
                    }
                } else {
                    echo '<p class="text-muted">No vaccines found in the system database.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
