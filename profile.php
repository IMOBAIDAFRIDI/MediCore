<?php
/**
 * User Profile (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = '';

try {
    // Fetch current user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) die('Invalid CSRF');

        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $image = $user['image']; // Default to current

        // Handle Image Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = 'uploads/' . time() . '_' . $_FILES['logo']['name'];
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $new_name)) {
                    $image = $new_name;
                }
            }
        }

        // Update
        $stmt = $pdo->prepare("UPDATE users SET name = ?, address = ?, phone = ?, location = ?, image = ? WHERE id = ?");
        if ($stmt->execute([$name, $address, $phone, $location, $image, $user_id])) {
            $_SESSION['name'] = $name;
            header('Location: profile.php?msg=updated');
            exit();
        } else {
            $error = "Update failed.";
        }
    }
} catch (PDOException $e) {
    die($e->getMessage());
}

$home_url = 'index.php';
if ($role == 'admin') $home_url = 'admin_dashboard.php';
elseif ($role == 'hospital') $home_url = 'hospital_dashboard.php';
elseif ($role == 'patient') $home_url = 'patient_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-pic-container { width: 120px; height: 120px; border-radius: 50%; border: 3px solid var(--primary); overflow: hidden; margin: 0 auto 20px; cursor: pointer; position: relative; }
        .profile-pic { width: 100%; height: 100%; object-fit: cover; }
        .overlay { position: absolute; bottom: 0; background: rgba(0,0,0,0.6); width: 100%; padding: 5px; text-align: center; color: white; font-size: 0.8rem; opacity: 0; transition: 0.3s; }
        .profile-pic-container:hover .overlay { opacity: 1; }
        .profile-box { max-width: 500px; margin: 40px auto; padding: 40px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: var(--text-muted); }
        input, textarea { width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="glass-card profile-box animate-fade">
            <h2 style="text-align:center; margin-bottom: 30px;">Edit My Profile</h2>

            <?php if($msg): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    Profile updated successfully!
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <?php if($role == 'hospital'): ?>
                    <div class="profile-pic-container" onclick="document.getElementById('logo-input').click()">
                        <img id="preview" src="<?php echo !empty($user['image']) ? $user['image'] : 'https://cdn-icons-png.flaticon.com/512/33/33777.png'; ?>" class="profile-pic">
                        <div class="overlay"><i class="fa fa-camera"></i> Change</div>
                    </div>
                    <input type="file" name="logo" id="logo-input" style="display:none" onchange="previewImg(this)">
                <?php endif; ?>

                <div class="form-group">
                    <label>Name / Username</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <?php if($role == 'patient'): ?>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Location (City)</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($user['location']); ?>" required>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>

                <div style="display:flex; gap:15px; margin-top:20px;">
                    <button type="submit" class="btn btn-primary flex-1" style="padding:12px;">Save Changes</button>
                    <a href="<?php echo $home_url; ?>" class="btn btn-outline flex-1" style="padding:12px; text-align:center;">Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImg(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = e => document.getElementById('preview').src = e.target.result;
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
