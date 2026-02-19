<?php
/**
 * Edit Patient Details | Admin
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: admin_patients.php');
    exit();
}

// Fetch Patient Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
$stmt->execute([$id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient record not found");
}

$error = '';
$success = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $location = $_POST['location'];
    $address = $_POST['address'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, location = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $location, $address, $id]);
        $success = "Patient profile successfully updated!";
        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error = "This email is already registered with another user.";
        } else {
            $error = "Update failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Patient | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { margin: 0; padding: 0; height: 100vh; overflow: hidden; background: var(--background); }
        .glass-nav { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: #000000; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.05); }
        
        .main-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.05), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(59, 130, 246, 0.05), transparent 40%);
        }

        .edit-card { 
            background: rgba(255, 255, 255, 0.02); 
            backdrop-filter: blur(25px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 30px; 
            padding: 25px 35px; 
            max-width: 550px; 
            width: 95%;
            position: relative;
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .branding-internal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .branding-internal i { 
            font-size: 1.5rem; 
            background: linear-gradient(135deg, var(--primary), #7c3aed); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
        }
        .branding-internal h1 { 
            font-size: 1.3rem; 
            font-weight: 800; 
            letter-spacing: 1px; 
            text-transform: uppercase; 
            margin: 0;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .full-width { grid-column: span 2; }
        
        .field-container { margin-bottom: 8px; }
        .field-container label { display: block; margin-bottom: 3px; color: var(--text-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .field-container input, .field-container textarea { 
            width: 100%; 
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 10px; 
            padding: 8px 12px; 
            color: #fff; 
            outline: none; 
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        .field-container input:focus { border-color: var(--primary); background: rgba(255,255,255,0.06); box-shadow: 0 0 15px rgba(139, 92, 246, 0.1); }

        .btn-update { 
            width: 100%; 
            background: linear-gradient(135deg, var(--primary), #7c3aed); 
            color: #fff; 
            padding: 12px; 
            border-radius: 12px; 
            font-weight: 800; 
            border: none; 
            cursor: pointer; 
            transition: all 0.4s; 
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.85rem;
        }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(139, 92, 246, 0.4); opacity: 0.9; }

        .alert { padding: 12px 18px; border-radius: 12px; margin-bottom: 15px; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: rgba(244, 63, 94, 0.1); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.2); }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">

    <div class="main-container">
        <div class="edit-card">
            <div class="branding-internal">
                <i class="fa-solid fa-virus-covid"></i>
                <h1>MediCore</h1>
            </div>
            <div style="text-align: center; margin-bottom: 15px;">
                <h3 style="margin:0; color:#fff; font-size:1rem; text-transform:uppercase; letter-spacing:1px;">EDIT PATIENT PROFILE</h3>
                <p class="text-muted" style="font-size:0.65rem; margin-top:4px;">Master Registry Protocol | ID: #<?php echo $patient['id']; ?></p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="field-container full-width">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>" placeholder="Enter patient's full name" required>
                    </div>
                    <div class="field-container">
                        <label>Email ID</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" placeholder="e.g. name@example.com" required>
                    </div>
                    <div class="field-container">
                        <label>Contact Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>" placeholder="+92 --- -------">
                    </div>
                    <div class="field-container">
                        <label>City / Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($patient['location'] ?? ''); ?>" placeholder="e.g. Lahore">
                    </div>
                    <div class="field-container">
                        <label>Registered Since</label>
                        <input type="text" value="<?php echo date('F d, Y', strtotime($patient['created_at'])); ?>" disabled style="opacity: 0.6; cursor: not-allowed; background: rgba(255,255,255,0.02);">
                    </div>
                    <div class="field-container full-width">
                        <label>Residential Address</label>
                        <textarea name="address" rows="3" placeholder="Enter complete residential details..."><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-update">UPDATE</button>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="admin_patients.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
