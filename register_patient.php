<?php
/**
 * Patient Registration Page (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$data = [
    'name' => '', 'email' => '', 'password' => '', 'confirm_password' => '',
    'dob' => '', 'phone' => '', 'address' => '',
    'name_err' => '', 'email_err' => '', 'password_err' => '',
    'confirm_password_err' => '', 'dob_err' => '', 'phone_err' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('Invalid CSRF Token');
    }

    $data['name'] = trim($_POST['name']);
    $data['email'] = trim($_POST['email']);
    $data['password'] = trim($_POST['password']);
    $data['confirm_password'] = trim($_POST['confirm_password']);
    $data['dob'] = trim($_POST['dob']);
    $data['phone'] = trim($_POST['phone']);
    $data['address'] = trim($_POST['address']);

    // Validate Email
    if (empty($data['email'])) {
        $data['email_err'] = 'Please enter email';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $data['email_err'] = 'Email is already taken';
        }
    }

    // Validate Password
    if (empty($data['password'])) {
        $data['password_err'] = 'Please enter password';
    } elseif (strlen($data['password']) < 6) {
        $data['password_err'] = 'Password must be at least 6 characters';
    } elseif (!preg_match('/[A-Z]/', $data['password'])) {
        $data['password_err'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $data['password'])) {
        $data['password_err'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $data['password'])) {
        $data['password_err'] = 'Password must contain at least one number';
    }

    if ($data['password'] != $data['confirm_password']) {
        $data['confirm_password_err'] = 'Passwords do not match';
    }

    if (empty($data['email_err']) && empty($data['password_err']) && empty($data['confirm_password_err'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = 'INSERT INTO users (name, email, password, role, dob, phone, address, status) VALUES (?, ?, ?, "patient", ?, ?, ?, "approved")';
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$data['name'], $data['email'], $hashed_password, $data['dob'], $data['phone'], $data['address']])) {
            // Queue Welcome Email
            require_once 'mail_helper.php';
            $subject = "Welcome to MediCore - Registration Successful";
            $body = "Dear <strong>{$data['name']}</strong>,<br><br>
                     Thank you for registering with <strong>MediCore</strong>. Your account has been created successfully.<br>
                     You can now log in to book appointments and view your medical reports.";
            
            sendMail($data['email'], $subject, $body);

            header('Location: login.php?msg=registration_success');
            exit();
        } else {
            die('Something went wrong');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <style>
        .split-screen { display: flex; min-height: 100vh; }
        .split-left { flex: 1; display: flex; align-items: center; justify-content: center; padding: 15px; background: var(--background); }
        .split-right { flex: 1; background: url('https://t3.ftcdn.net/jpg/03/19/52/64/360_F_319526449_XOvMGnNoLlPKUgVqPNscEhDsZvlmBSbY.jpg') center/cover; position: relative; }
        .split-right::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to right, var(--background), transparent); }
        .auth-box { width: 100%; max-width: 480px; background: rgba(22, 22, 22, 0.9); backdrop-filter: blur(10px); padding: 20px; border-radius: 16px; border: 1px solid var(--glass-border); box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        @media (max-width: 900px) { .split-right { display: none; } }
        .form-group { margin-bottom: 8px; }
        .form-group label { display: block; margin-bottom: 3px; color: var(--text-light); font-weight: 500; font-size: 0.8rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px 10px; font-size: 0.85rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; color: #fff; outline: none; transition: all 0.3s; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary); background: rgba(255, 255, 255, 0.08); box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1); }
        .btn-primary { background: var(--primary); color: #000; font-weight: 600; padding: 8px; font-size: 0.9rem; border-radius: 6px; border: none; cursor: pointer; transition: all 0.3s ease; width: 100%; margin-top: 5px; }
    </style>
</head>
<body style="min-height: 100vh; background: var(--background);">

    <div class="split-screen">
        <div class="split-left">
            <div class="auth-box animate-fade">
                <div style="text-align: center; margin-bottom: 15px;">
                    <a href="index.php" style="font-size: 1.6rem; font-weight: 700; color: var(--primary);"><i class="fa-solid fa-virus-covid"></i> MediCore</a>
                    <p style="color: var(--text-muted); margin-top: 2px; font-size: 0.85rem;">Patient Registration</p>
                </div>

                <?php if (!empty($data['email_err'])) echo "<div style='color:#f87171; text-align:center; font-size:0.8rem;'>{$data['email_err']}</div>"; ?>
                <?php if (!empty($data['password_err'])) echo "<div style='color:#f87171; text-align:center; font-size:0.8rem;'>{$data['password_err']}</div>"; ?>
                <?php if (!empty($data['confirm_password_err'])) echo "<div style='color:#f87171; text-align:center; font-size:0.8rem;'>{$data['confirm_password_err']}</div>"; ?>

                <form method="POST" action="register_patient.php">
                    <?php echo get_csrf_input(); ?>
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-user" style="position: absolute; left: 10px; top: 9px; color: var(--text-muted); font-size: 0.85rem;"></i>
                            <input type="text" name="name" required placeholder="John Doe" value="<?php echo htmlspecialchars($data['name']); ?>" style="padding-left: 30px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-envelope" style="position: absolute; left: 10px; top: 9px; color: var(--text-muted); font-size: 0.85rem;"></i>
                            <input type="email" name="email" required placeholder="john@example.com" value="<?php echo htmlspecialchars($data['email']); ?>" style="padding-left: 30px;">
                        </div>
                    </div>
                    
                    <div class="form-group" style="display:flex; gap:10px;">
                        <div style="flex:1;">
                            <label>Password</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-lock" style="position: absolute; left: 10px; top: 9px; color: var(--text-muted); font-size: 0.85rem;"></i>
                                <input type="password" name="password" required 
                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$"
                                       title="Must contain at least 6 characters, including one uppercase, one lowercase, and one number"
                                       placeholder="******" style="padding-left: 30px;">
                            </div>
                            <small style="display:block; font-size:0.65rem; color: #64748b; margin-top:3px;">Min 6 chars, A-z, 0-9</small>
                        </div>
                        <div style="flex:1;">
                            <label>Confirm</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-lock" style="position: absolute; left: 10px; top: 9px; color: var(--text-muted); font-size: 0.85rem;"></i>
                                <input type="password" name="confirm_password" required placeholder="******" style="padding-left: 30px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Date of Birth</label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-calendar" style="position: absolute; left: 10px; top: 9px; color: var(--text-muted); font-size: 0.85rem;"></i>
                            <input type="date" name="dob" required style="padding-left: 30px; color-scheme: dark;" value="<?php echo htmlspecialchars($data['dob']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" id="mobile_code" name="phone_display" style="width: 100%; border-radius: 6px;" value="<?php echo htmlspecialchars($data['phone']); ?>">
                        <input type="hidden" name="phone" id="full_phone" value="<?php echo htmlspecialchars($data['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" required placeholder="Your full address..." rows="1" style="height: 38px; padding-top: 8px;"><?php echo htmlspecialchars($data['address']); ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Secure Register</button>
                    
                    <div style="margin-top: 10px; text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                        Already have an account? <a href="login.php" style="color: var(--primary); text-decoration: none;">Login here</a>
                        <br>
                        <a href="index.php" style="color: var(--text-muted); font-size: 0.75rem; margin-top: 5px; display: inline-block;">Back to Home</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="split-right"></div>
    </div>

    <script>
        var input = document.querySelector("#mobile_code");
        var iti = window.intlTelInput(input, {
             initialCountry: "pk",
             placeholderNumberType: "MOBILE",
             separateDialCode: true,
             utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        });
        
        var form = document.querySelector("form");
        form.addEventListener("submit", function() {
            var fullNumber = iti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
            document.querySelector("#full_phone").value = fullNumber;
        });
    </script>
    <style>
        .iti { width: 100%; display: block; }
        .iti__country-list { z-index: 100 !important; max-height: 250px; background-color: #222; color: #fff; border: 1px solid #444; }
        .iti--allow-dropdown input { padding-left: 90px !important; }
    </style>
</body>
</html>
