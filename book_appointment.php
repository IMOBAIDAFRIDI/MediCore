<?php
/**
 * Book Appointment (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header('Location: login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle selection from query params
$selected_hospital = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;
$selected_vaccine = isset($_GET['vaccine_id']) ? intval($_GET['vaccine_id']) : 0;
$type = $selected_vaccine ? 'vaccination' : 'test';

// Find selected vaccine name if vaccine_id is provided
$selected_vaccine_name = '';
if ($selected_vaccine) {
    foreach ($vaccines ?? [] as $v) {
        if ($v['id'] == $selected_vaccine) {
            $selected_vaccine_name = $v['name'];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('Invalid CSRF');
    }

    $hospital_id = intval($_POST['hospital_id']);
    $type = $_POST['type'];
    $vaccine_id = !empty($_POST['vaccine_id']) ? intval($_POST['vaccine_id']) : null;
    $date = $_POST['date'];
    $time = $_POST['time'];

    if (!$hospital_id || !$date || !$time) {
        $error = "Please fill all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, hospital_id, type, vaccine_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            if ($stmt->execute([$patient_id, $hospital_id, $type, $vaccine_id, $date, $time])) {
                // Queue notification email
                require_once 'mail_helper.php';
                $stmtPatient = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $stmtPatient->execute([$patient_id]);
                $pData = $stmtPatient->fetch();
                
                $subject = "Appointment Booked - MediCore";
                $body = "Dear <strong>{$pData['name']}</strong>,<br><br>
                         Your appointment for a <strong>" . strtoupper($type) . "</strong> has been booked successfully.<br>
                         <strong>Date:</strong> $date<br>
                         <strong>Time:</strong> $time<br><br>
                         Please wait for the hospital to confirm your request.";
                
                sendMail($pData['email'], $subject, $body);

                header('Location: patient_dashboard.php?msg=booked');
                exit();
            } else {
                $error = "Booking failed.";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch lists
$selected_hospital_info = null;
try {
    // Agar hospital_id URL mein hai to us hospital ki info fetch karo
    if ($selected_hospital) {
        $stmtH = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'hospital' AND status = 'approved'");
        $stmtH->execute([$selected_hospital]);
        $selected_hospital_info = $stmtH->fetch(PDO::FETCH_ASSOC);
    }

    if ($selected_hospital && $selected_hospital_info) {
        // Sirf us hospital ki available vaccines fetch karo
        $stmtV = $pdo->prepare("
            SELECT v.* FROM vaccines v
            INNER JOIN hospital_vaccines hv ON v.id = hv.vaccine_id
            WHERE hv.hospital_id = ? AND hv.status = 'available'
            ORDER BY v.name ASC
        ");
        $stmtV->execute([$selected_hospital]);
        $vaccines = $stmtV->fetchAll(PDO::FETCH_ASSOC);
        // Hospitals list ki zaroorat nahi (hospital fixed hai)
        $hospitals = [];
    } elseif ($selected_vaccine) {
        $vaccines = $pdo->query("SELECT * FROM vaccines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        // Saare hospitals fetch karo, saath mein yeh bhi ke vaccine available hai ya nahi
        $stmt = $pdo->prepare("
            SELECT u.*,
                   CASE WHEN hv.status = 'available' THEN 1 ELSE 0 END as has_vaccine
            FROM users u
            LEFT JOIN hospital_vaccines hv ON u.id = hv.hospital_id AND hv.vaccine_id = ?
            WHERE u.role = 'hospital' AND u.status = 'approved'
            ORDER BY has_vaccine DESC, u.name ASC
        ");
        $stmt->execute([$selected_vaccine]);
        $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $vaccines = $pdo->query("SELECT * FROM vaccines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $hospitals = $pdo->query("SELECT *, 1 as has_vaccine FROM users WHERE role = 'hospital' AND status = 'approved' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die($e->getMessage());
}

// Find selected vaccine name
$selected_vaccine_name = '';
if ($selected_vaccine) {
    foreach ($vaccines as $v) {
        if ($v['id'] == $selected_vaccine) {
            $selected_vaccine_name = $v['name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .booking-card { max-width: 420px; margin: 20px auto; padding: 24px 28px; }
        .form-group { margin-bottom: 12px; }

        /* Labels */
        label { display: block; margin-bottom: 5px; color: rgba(255,255,255,0.6); font-size: 0.82rem; font-weight: 500; letter-spacing: 0.3px; }

        /* Inputs & Selects */
        select, input[type="date"], input[type="time"] {
            width: 100%;
            padding: 9px 12px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            color: #ffffff;
            outline: none;
            font-size: 0.9rem;
            appearance: auto;
            -webkit-appearance: auto;
        }
        select option {
            background: #1e293b;
            color: #ffffff;
        }
        select:focus, input[type="date"]:focus, input[type="time"]:focus {
            border-color: var(--primary);
            background: rgba(255,255,255,0.1);
        }

        /* Date/Time color fix for webkit */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.6;
            cursor: pointer;
        }
        input[type="date"], input[type="time"] {
            color-scheme: dark;
        }

        /* Type buttons */
        .type-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
        .type-btn { padding: 10px; text-align: center; border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; cursor: pointer; transition: 0.3s; font-size: 0.9rem; color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.04); }
        .type-btn:hover { color: #fff; background: rgba(255,255,255,0.08); }
        .type-btn.active { border-color: var(--primary); background: rgba(0, 242, 254, 0.1); color: var(--primary); }

        /* Heading */
        .booking-card h2 { color: #ffffff; }

        /* Cancel link */
        .cancel-link { color: rgba(255,255,255,0.45); text-decoration: none; font-size: 0.88rem; transition: color 0.2s; }
        .cancel-link:hover { color: rgba(255,255,255,0.75); }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

    <div style="width: 100%;">
        <div class="glass-card booking-card animate-fade">
            <h2 style="margin-bottom: 18px; text-align: center; font-size: 1.3rem;"><i class="fa-solid fa-calendar-plus text-primary mr-2"></i> Book Appointment</h2>
            
            <?php if($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <?php if ($selected_vaccine): ?>
                    <!-- Type locked to vaccination when coming from vaccine card -->
                    <input type="hidden" name="type" id="appt_type" value="vaccination">
                    <div class="form-group">
                        <label>Appointment Type</label>
                        <div style="padding:12px 16px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;color:#10b981;font-weight:600;display:flex;align-items:center;gap:10px;">
                            <i class="fa-solid fa-syringe"></i> Vaccination
                        </div>
                    </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Appointment Type</label>
                    <div class="type-selector">
                        <div class="type-btn <?php echo $type == 'test' ? 'active' : ''; ?>" onclick="setType('test')">COVID Test</div>
                        <div class="type-btn <?php echo $type == 'vaccination' ? 'active' : ''; ?>" onclick="setType('vaccination')">Vaccination</div>
                    </div>
                    <input type="hidden" name="type" id="appt_type" value="<?php echo $type; ?>">
                </div>
                <?php endif; ?>

                <?php if ($selected_hospital && $selected_hospital_info): ?>
                    <!-- Hospital fixed: partner hospital se book kiya gaya -->
                    <div class="form-group">
                        <label>Hospital</label>
                        <div style="padding:12px 16px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:8px;color:#fff;font-weight:600;display:flex;align-items:center;gap:10px;">
                            <i class="fa-solid fa-hospital" style="color:#3b82f6;"></i>
                            <?php echo htmlspecialchars($selected_hospital_info['name']); ?>
                            <?php if (!empty($selected_hospital_info['location'])): ?>
                                <span style="font-size:0.8rem;color:rgba(255,255,255,0.45);font-weight:400;">
                                    <i class="fa-solid fa-location-dot" style="color:#f59e0b;margin-left:6px;"></i>
                                    <?php echo htmlspecialchars($selected_hospital_info['location']); ?>
                                </span>
                            <?php endif; ?>
                            <span style="margin-left:auto;font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:400;">Partner Hospital</span>
                        </div>
                        <input type="hidden" name="hospital_id" value="<?php echo $selected_hospital; ?>">
                    </div>
                <?php else: ?>
                <div class="form-group">
                    <label><?php echo $selected_vaccine ? 'Select Hospital (offering this vaccine)' : 'Select Hospital'; ?></label>
                    <select name="hospital_id" required>
                        <option value="">-- Choose Hospital --</option>
                        <?php foreach($hospitals as $h): ?>
                            <?php if ($h['has_vaccine']): ?>
                            <option value="<?php echo $h['id']; ?>" <?php echo $selected_hospital == $h['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($h['name']); ?> (<?php echo htmlspecialchars($h['location']); ?>)
                            </option>
                            <?php else: ?>
                            <option value="" disabled style="color:#888;">
                                <?php echo htmlspecialchars($h['name']); ?> — Vaccine Unavailable
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($selected_vaccine && $selected_vaccine_name): ?>
                    <!-- Vaccine locked: show as fixed text, not dropdown -->
                    <div class="form-group">
                        <label>Selected Vaccine</label>
                        <div style="padding:12px 16px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:8px;color:#fff;font-weight:600;display:flex;align-items:center;gap:10px;">
                            <i class="fa-solid fa-shield-virus" style="color:#10b981;"></i>
                            <?php echo htmlspecialchars($selected_vaccine_name); ?>
                            <span style="margin-left:auto;font-size:0.75rem;color:rgba(255,255,255,0.4);font-weight:400;">Pre-selected</span>
                        </div>
                        <input type="hidden" name="vaccine_id" value="<?php echo $selected_vaccine; ?>">
                    </div>
                <?php elseif ($selected_hospital && $selected_hospital_info): ?>
                    <!-- Hospital fixed: us hospital ki available vaccines dikhao -->
                    <?php if (!empty($vaccines)): ?>
                    <div class="form-group" id="vaccine_group" style="<?php echo $type == 'vaccination' ? '' : 'display:none;'; ?>">
                        <label>Select Vaccine <span style="font-size:0.75rem;color:rgba(255,255,255,0.4);">(Available at this hospital)</span></label>
                        <select name="vaccine_id">
                            <option value="">-- Choose Vaccine --</option>
                            <?php foreach($vaccines as $v): ?>
                                <option value="<?php echo $v['id']; ?>">
                                    <?php echo htmlspecialchars($v['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="form-group" id="vaccine_group" style="<?php echo $type == 'vaccination' ? '' : 'display:none;'; ?>">
                        <label>Select Vaccine</label>
                        <div style="padding:12px 16px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:8px;color:#ef4444;font-size:0.88rem;display:flex;align-items:center;gap:8px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            No vaccines currently available at this hospital.
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="form-group" id="vaccine_group" style="<?php echo $type == 'vaccination' ? '' : 'display:none;'; ?>">
                    <label>Select Vaccine</label>
                    <select name="vaccine_id">
                        <option value="">-- Choose Vaccine --</option>
                        <?php foreach($vaccines as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $selected_vaccine == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Preferred Date</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Preferred Time</label>
                        <input type="time" name="time" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="padding: 11px; font-size: 1rem; margin-top: 14px;">Confirm Booking</button>
                <div style="text-align: center; margin-top: 12px;">
                    <a href="patient_dashboard.php" class="cancel-link">← Cancel and Go Back</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function setType(t) {
            document.getElementById('appt_type').value = t;
            document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            if(t === 'vaccination') {
                document.getElementById('vaccine_group').style.display = 'block';
            } else {
                document.getElementById('vaccine_group').style.display = 'none';
                document.querySelector('select[name="vaccine_id"]').value = "";
            }
        }
    </script>
</body>
</html>
