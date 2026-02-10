<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];
$hospital_name = $_SESSION['name'];
// Fetch counts for dashboard stats
$sql_counts = "SELECT 
    COUNT(*) as total_appts,
    SUM(CASE WHEN STATUS = 'pending' THEN 1 ELSE 0 END) as pending_appts,
    SUM(CASE WHEN STATUS = 'approved' THEN 1 ELSE 0 END) as approved_appts,
    SUM(CASE WHEN STATUS = 'completed' THEN 1 ELSE 0 END) as completed_appts
FROM appointments WHERE hospital_id = $hospital_id";
$res_counts = $conn->query($sql_counts);
$counts = $res_counts->fetch_assoc();

// Fetch Hospital Details (Image, Name) to ensure up-to-date
$sql_hosp = "SELECT name, image FROM hospitals WHERE id = $hospital_id";
$res_hosp = $conn->query($sql_hosp);
if ($res_hosp->num_rows > 0) {
    $h_data = $res_hosp->fetch_assoc();
    $hospital_name = $h_data['name']; // Ensure name is also up to date
    $hospital_image = !empty($h_data['image']) ? $h_data['image'] : 'https://cdn-icons-png.flaticon.com/512/33/33777.png';
} else {
    $hospital_image = 'https://cdn-icons-png.flaticon.com/512/33/33777.png';
}


// Handle Actions (Approve/Reject directly)
if (isset($_GET['action'])) {
    $appt_id = intval($_GET['id']);
    $action = $_GET['action'];
    $valid_actions = ['approved', 'rejected', 'completed'];
    if (in_array($action, $valid_actions)) {
        
        // Fetch patient details for email
        if ($action == 'approved') {
            $p_query = $conn->query("SELECT p.email, p.name, a.appointment_date, a.appointment_time FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id=$appt_id");
            if ($p_query->num_rows > 0) {
                $p_data = $p_query->fetch_assoc();
                include_once 'mail_helper.php';
                sendMail($p_data['email'], "Appointment Confirmed - MediCore", 
                    "Dear <strong>{$p_data['name']}</strong>,<br><br>
                    Your appointment request has been <strong>CONFIRMED</strong>.<br>
                    <strong>Date:</strong> {$p_data['appointment_date']}<br>
                    <strong>Time:</strong> {$p_data['appointment_time']}<br><br>
                    Please arrive on time at the hospital.");
            }
        }

        $conn->query("UPDATE appointments SET status='$action' WHERE id=$appt_id AND hospital_id=$hospital_id");
        header("Location: hospital_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Dashboard | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 28px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(125deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%, rgba(255, 255, 255, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-info {
            position: relative;
            z-index: 2;
        }

        .stat-value {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 4px;
            line-height: 1;
            letter-spacing: -2px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        /* Decorative circle behind icon */
        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--glow-color, rgba(255,255,255,0.1)) 0%, transparent 70%);
            opacity: 0.4;
            transition: all 0.5s ease;
            z-index: 0;
        }

        .stat-card:hover::after {
            width: 200px;
            height: 200px;
            opacity: 0.6;
        }

        .stat-icon {
            font-size: 3.5rem;
            position: absolute;
            right: 20px;
            bottom: 20px;
            opacity: 0.15;
            z-index: 1;
            transition: all 0.4s ease;
            transform: rotate(-10deg);
        }

        .stat-card:hover .stat-icon {
            transform: rotate(0deg) scale(1.2) translate(-10px, -10px);
            opacity: 0.3;
        }

        /* Unique Glows per card */
        .card-primary { --glow-color: var(--primary-glow); border-bottom: 2px solid var(--primary); }
        .card-warning { --glow-color: rgba(245, 158, 11, 0.4); border-bottom: 2px solid var(--warning); }
        .card-accent  { --glow-color: rgba(6, 182, 212, 0.4); border-bottom: 2px solid var(--accent); }
        .card-success { --glow-color: rgba(16, 185, 129, 0.4); border-bottom: 2px solid var(--success); }

        
        .section-header { display: flex; align-items: center; justify_content: space-between; margin-bottom: 20px; }
        .section-title { font-size: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        
        .action-btn-group { display: flex; gap: 8px; }
        
        /* Table enhancements */
        .table-hover tbody tr:hover { background: rgba(255,255,255,0.03); }
        .table-responsive { overflow-x: auto; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo"><i class="fa-solid fa-hospital-user"></i> MediCore Hospital</div>
                <div class="nav-links" style="display: flex; align-items: center;">
                    <?php 
                        // Image is now fetched from DB at top of file
                    ?>
                    <img src="<?php echo $hospital_image; ?>" alt="Logo" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover; border: 2px solid var(--glass-border); background: #fff;">
                    <span style="color: var(--text-muted); margin-right: 10px;">Welcome, <?php echo $hospital_name; ?></span>
                    <a href="manage_vaccines.php" class="btn btn-primary" style="padding: 8px 16px; margin-right: 10px;"><i class="fa-solid fa-syringe"></i> Manage Vaccines</a>
                    <a href="profile.php" class="btn btn-outline" style="padding: 8px 16px;"><i class="fa-solid fa-gear"></i></a>
                    <a href="logout.php" class="btn btn-primary" style="padding: 8px 16px;">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <!-- Statistics Section -->
        <div class="dashboard-grid animate-fade">
            <div class="stat-card card-primary">
                <div class="stat-info">
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-value" style="color: var(--primary);"><?php echo $counts['total_appts']; ?></div>
                </div>
                <i class="fa-solid fa-calendar-check stat-icon" style="color: var(--primary);"></i>
            </div>
            <div class="stat-card card-warning">
                <div class="stat-info">
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-value" style="color: var(--warning);"><?php echo $counts['pending_appts']; ?></div>
                </div>
                <i class="fa-solid fa-clock stat-icon" style="color: var(--warning);"></i>
            </div>
            <div class="stat-card card-accent">
                <div class="stat-info">
                    <div class="stat-label">Scheduled</div>
                    <div class="stat-value" style="color: var(--accent);"><?php echo $counts['approved_appts']; ?></div>
                </div>
                <i class="fa-solid fa-clipboard-list stat-icon" style="color: var(--accent);"></i>
            </div>
            <div class="stat-card card-success">
                <div class="stat-info">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value" style="color: var(--success);"><?php echo $counts['completed_appts']; ?></div>
                </div>
                <i class="fa-solid fa-check-double stat-icon" style="color: var(--success);"></i>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Main Content Column -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                
                <!-- Incoming Requests -->
                <div class="card animate-fade" style="animation-delay: 0.1s;">
                    <div class="section-header">
                        <div class="section-title"><i class="fa-solid fa-bell" style="color: var(--warning);"></i> Incoming Requests</div>
                    </div>
                    <div class="table-container table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Proposed Date</th>
                                    <th>Actions</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT a.*, p.name as pname FROM appointments a 
                                        JOIN patients p ON a.patient_id = p.id 
                                        WHERE a.hospital_id = $hospital_id AND a.status = 'pending' ORDER BY a.appointment_date ASC";
                                $result = $conn->query($sql);
                                
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                            <td>
                                                <div style='font-weight:600;'>{$row['pname']}</div>
                                            </td>
                                            <td><span class='badge badge-warning'>" . strtoupper($row['type']) . "</span></td>
                                            <td>
                                                <div>{$row['appointment_date']}</div>
                                                <small style='color:var(--text-muted);'>{$row['appointment_time']}</small>
                                            </td>
                                            <td>
                                                <div class='action-btn-group'>
                                                    <a href='?action=approved&id={$row['id']}' class='btn btn-primary' title='Approve'><i class='fa-solid fa-check'></i></a>
                                                    <a href='?action=rejected&id={$row['id']}' class='btn btn-outline' style='border-color:var(--danger); color:var(--danger);' title='Reject'><i class='fa-solid fa-xmark'></i></a>
                                                </div>
                                            </td>
                                            <td>
                                                <a href='edit_appointment.php?id={$row['id']}' class='btn btn-outline' style='padding:6px 10px;'><i class='fa-solid fa-pen'></i></a>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:var(--text-muted);'>No pending requests at the moment.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Update Results (Scheduled Appointments) -->
                <div class="card animate-fade" style="animation-delay: 0.2s;">
                    <div class="section-header">
                        <div class="section-title"><i class="fa-solid fa-flask" style="color: var(--accent);"></i> Update Results</div>
                    </div>
                    <div class="table-container table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT a.*, p.name as pname FROM appointments a 
                                        JOIN patients p ON a.patient_id = p.id 
                                        WHERE a.hospital_id = $hospital_id AND a.status = 'approved' ORDER BY a.appointment_date ASC";
                                $result = $conn->query($sql);
                                
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                            <td><strong>{$row['pname']}</strong></td>
                                            <td><span class='badge badge-info' style='background:rgba(6,182,212,0.15); color:#06b6d4; border:1px solid rgba(6,182,212,0.2);'>" . strtoupper($row['type']) . "</span></td>
                                            <td>{$row['appointment_date']}</td>
                                            <td>
                                                <a href='update_result.php?id={$row['id']}' class='btn btn-primary' style='padding: 6px 16px; font-size: 0.85rem;'>Update Result</a>
                                                <a href='edit_appointment.php?id={$row['id']}' class='btn btn-outline' style='padding:6px 10px; margin-left:5px;'><i class='fa-solid fa-pen'></i></a>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:var(--text-muted);'>No scheduled appointments pending results.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right Column: Recent History (Editable) -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <div class="card animate-fade" style="animation-delay: 0.3s; height: 100%;">
                    <div class="section-header">
                        <div class="section-title" style="font-size: 1.2rem;"><i class="fa-solid fa-clock-rotate-left"></i> Recent History</div>
                    </div>
                    <div style="overflow-y: auto; max-height: 600px; padding-right: 5px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php
                            $sql = "SELECT a.*, p.name as pname FROM appointments a 
                                    JOIN patients p ON a.patient_id = p.id 
                                    WHERE a.hospital_id = $hospital_id AND (a.status = 'completed' OR a.status = 'rejected') 
                                    ORDER BY a.id DESC LIMIT 10";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $statusColor = $row['status'] == 'completed' ? 'var(--success)' : 'var(--danger)';
                                    $icon = $row['status'] == 'completed' ? 'fa-check' : 'fa-ban';
                                    
                                    echo "<tr style='border-bottom: 1px solid var(--glass-border);'>
                                        <td style='padding: 15px 0;'>
                                            <div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 6px;'>
                                                <div style='font-weight:600; font-size:0.95rem;'>{$row['pname']}</div>
                                                <div style='font-size:0.75rem; color:var(--text-muted);'>{$row['appointment_date']}</div>
                                            </div>
                                            <div style='display:flex; justify-content:space-between; align-items:center;'>
                                                <span class='badge' style='background:rgba(255,255,255,0.05); color:$statusColor; padding:4px 8px; font-size:0.75rem;'>
                                                    <i class='fa-solid $icon'></i> " . ucfirst($row['status']) . "
                                                </span>
                                                <div>
                                                    <a href='edit_appointment.php?id={$row['id']}' class='btn btn-outline' style='padding: 4px 8px; font-size: 0.7rem;' title='Edit Details'><i class='fa-solid fa-pen'></i></a>
                                                    " . ($row['status'] == 'completed' ? "<a href='update_result.php?id={$row['id']}' class='btn btn-outline' style='padding: 4px 8px; font-size: 0.7rem;' title='Edit Result'><i class='fa-solid fa-flask'></i></a>" : "") . "
                                                </div>
                                            </div>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td style='text-align:center; padding:30px; color:var(--text-muted);'>No history yet.</td></tr>";
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
