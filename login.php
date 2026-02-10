<?php
session_start();
include 'db_connect.php';

$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_username = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email_username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

    // 1. Check Admin
    $sql = "SELECT * FROM admins WHERE username = '$email_username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['name'] = $row['username'];
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // 2. Check Hospital
    $sql = "SELECT * FROM hospitals WHERE email = '$email_username' AND status = 'approved'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = 'hospital';
            $_SESSION['name'] = $row['name'];
            header("Location: hospital_dashboard.php");
            exit();
        }
    }

    // 3. Check Patient
    $sql = "SELECT * FROM patients WHERE email = '$email_username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = 'patient';
            $_SESSION['name'] = $row['name'];
            header("Location: patient_dashboard.php");
            exit();
        }
    }

    // If no match found or password incorrect (generic error for security)
    if (empty($error)) {
        $error = "Invalid email/username or password.";
    }
    } // End else
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - MediCore Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .split-screen { display: flex; min-height: 100vh; }
        .split-left { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .split-right { flex: 1; background: url('https://images.unsplash.com/photo-1581056771107-24ca5f033842?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover; position: relative; }
        .split-right::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to right, var(--background), transparent); }
        .auth-box { width: 100%; max-width: 450px; background: rgba(22, 22, 22, 0.9); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; border: 1px solid var(--glass-border); box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        
        @media (max-width: 900px) { .split-right { display: none; } }
    </style>
</head>
<body style="min-height: 100vh; background: var(--background);">

    <div class="split-screen">
        <div class="split-left">
            <div class="auth-box animate-fade">
                <div style="text-align: center; margin-bottom: 30px;">
                    <a href="index.php" style="font-size: 2rem; font-weight: 700; color: var(--primary);"><i class="fa-solid fa-virus-covid"></i> MediCore</a>
                    <p style="color: var(--text-muted); margin-top: 10px;">Welcome back to the portal</p>
                </div>
                
                <?php if($error) echo "<div style='padding: 10px; background: rgba(248, 113, 113, 0.2); color: #f87171; border-radius: 8px; margin-bottom: 20px; text-align: center;'>$error</div>"; ?>
                
                <form method="POST" action="">

                    <div class="form-group">
                        <label>Email / Username</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-envelope" style="position: absolute; left: 15px; top: 16px; color: var(--text-muted);"></i>
                            <input type="text" name="email" required placeholder="Type your email..." style="padding-left: 45px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-lock" style="position: absolute; left: 15px; top: 16px; color: var(--text-muted);"></i>
                            <input type="password" name="password" required placeholder="Type your password..." style="padding-left: 45px;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Secure Login</button>
                </form>
                
                <p style="margin-top: 30px; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
                    New Registration? <a href="register_patient.php" style="color: var(--primary);">Patient</a> or <a href="register_hospital.php" style="color: var(--primary);">Hospital</a>
                </p>
            </div>
        </div>
        <div class="split-right"></div>
    </div>

</body>
</html>
