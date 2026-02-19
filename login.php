<?php
/**
 * Login Page (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header('Location: admin_dashboard.php');
    elseif ($_SESSION['role'] == 'hospital') header('Location: hospital_dashboard.php');
    else header('Location: patient_dashboard.php');
    exit();
}

$email = '';
$email_err = '';
$password_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('Invalid CSRF Token');
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email)) $email_err = 'Please enter email';
    if (empty($password)) $password_err = 'Please enter password';

    if (empty($email_err) && empty($password_err)) {
        // Authenticate
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'pending' || $user['status'] == 'rejected') {
                    $email_err = 'Your account status is ' . $user['status'] . '. Please contact support.';
                } else {
                    // Create Session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];

                    if ($user['role'] == 'admin') header('Location: admin_dashboard.php');
                    elseif ($user['role'] == 'hospital') header('Location: hospital_dashboard.php');
                    else header('Location: patient_dashboard.php');
                    exit();
                }
            } else {
                $password_err = 'Password incorrect';
            }
        } else {
            $email_err = 'No account found with this email.';
        }
    }
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
                
                <?php if(!empty($email_err)) echo "<div style='padding: 10px; background: rgba(248, 113, 113, 0.2); color: #f87171; border-radius: 8px; margin-bottom: 20px; text-align: center;'>" . $email_err . "</div>"; ?>
                <?php if(!empty($password_err)) echo "<div style='padding: 10px; background: rgba(248, 113, 113, 0.2); color: #f87171; border-radius: 8px; margin-bottom: 20px; text-align: center;'>" . $password_err . "</div>"; ?>
                
                <form method="POST" action="login.php">
                    <?php echo get_csrf_input(); ?>
                    <div class="form-group">
                        <label>Email / Username</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-envelope" style="position: absolute; left: 15px; top: 16px; color: var(--text-muted);"></i>
                            <input type="text" name="email" required placeholder="Type your email..." value="<?php echo htmlspecialchars($email); ?>" style="padding-left: 45px;">
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
