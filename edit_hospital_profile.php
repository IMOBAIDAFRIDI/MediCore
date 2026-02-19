<?php
/**
 * Edit Hospital Profile
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
require_once 'csrf_helper.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital') {
    header('Location: login.php');
    exit();
}

$hospital_id = $_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// Fetch Profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('Invalid CSRF Token');
    }

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $location = trim($_POST['location']);
    $address  = trim($_POST['address']);
    $image    = $hospital['image']; // keep existing by default

    // --- Handle Remove Image ---
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        // Delete old file if it's a local upload
        if (!empty($hospital['image']) && strpos($hospital['image'], 'uploads/') !== false) {
            $old_path = __DIR__ . '/' . $hospital['image'];
            if (file_exists($old_path)) @unlink($old_path);
        }
        $image = null;
    }

    // --- Handle New Image Upload ---
    if (!empty($_FILES['profile_image']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);

        if (!in_array($file_type, $allowed)) {
            $msg = 'Only JPG, PNG, GIF, WEBP images are allowed.';
            $msg_type = 'error';
        } elseif ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            $msg = 'Image must be under 5MB.';
            $msg_type = 'error';
        } else {
            // Delete old local file
            if (!empty($hospital['image']) && strpos($hospital['image'], 'uploads/') !== false) {
                $old_path = __DIR__ . '/' . $hospital['image'];
                if (file_exists($old_path)) @unlink($old_path);
            }

            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . $hospital_id . '.' . $ext;
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $dest = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                $image = 'uploads/' . $filename;
            } else {
                $msg = 'Failed to upload image. Check folder permissions.';
                $msg_type = 'error';
            }
        }
    }

    if (empty($msg)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, location = ?, address = ?, image = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $location, $address, $image, $hospital_id])) {
                $msg = 'Profile updated successfully!';
                $msg_type = 'success';
                // Refresh data
                $hospital['name']     = $name;
                $hospital['email']    = $email;
                $hospital['location'] = $location;
                $hospital['address']  = $address;
                $hospital['image']    = $image;
                $_SESSION['name']     = $name;
            }
        } catch (PDOException $e) {
            $msg = "Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

$current_image = $hospital['image'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-container { max-width: 1400px; margin: 10px auto; }
        .form-group { margin-bottom: 7px; }
        label { display: block; margin-bottom: 3px; color: var(--text-muted); font-weight: 600; font-size: 0.82rem; }
        input, textarea { width: 100%; padding: 6px 10px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; transition: all 0.3s; font-size: 0.85rem; }
        input:focus, textarea:focus { border-color: var(--primary); background: rgba(255,255,255,0.08); outline: none; }

        /* Profile Image Circle - Facebook Style */
        .profile-img-wrapper {
            position: relative;
            width: 75px;
            height: 75px;
            margin: 0 auto 3px;
            cursor: pointer;
        }
        .profile-img-circle {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(16, 185, 129, 0.35);
            box-shadow: 0 4px 14px rgba(0,0,0,0.4);
            background: #1e1e1e;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .profile-img-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .profile-img-circle .placeholder-icon {
            font-size: 2rem;
            color: #444;
        }
        /* Facebook-style dark overlay inside circle */
        .profile-img-overlay {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.55);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
        }
        .profile-img-wrapper:hover .profile-img-overlay {
            opacity: 1;
            pointer-events: all;
        }
        .img-overlay-btn {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 0.78rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            transition: transform 0.2s;
            padding: 0;
            line-height: 1;
        }
        .img-overlay-btn i {
            font-size: 1.1rem;
        }
        .img-overlay-btn:hover { transform: scale(1.15); }
        .img-overlay-btn.remove-btn { color: #ff6b6b; }
        .overlay-divider {
            width: 1px;
            height: 28px;
            background: rgba(255,255,255,0.3);
        }

    </style>
</head>
<body style="background: var(--background); color: var(--text-light); height: 100vh; overflow: hidden; display: flex; align-items: center; justify-content: center;">
    <div style="width: 100%; max-width: 680px; padding: 0 16px">
        <div class="glass-card animate-fade" style="padding: 12px 18px; width: 100%;">

            <!-- Title -->
            <div style="text-align: center; margin-bottom: 10px;">
                <h2 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1px;">Edit <span style="color: var(--primary);">Profile</span></h2>
                <p style="color: var(--text-muted); margin-bottom: 10px; font-size: 0.82rem;">Update your hospital information</p>

                <!-- Circular Profile Image - Facebook Style -->
                <div class="profile-img-wrapper" onclick="handleCircleClick(event)">
                    <div class="profile-img-circle" id="imgCircle">
                        <?php if (!empty($current_image)): ?>
                            <img src="<?php echo htmlspecialchars($current_image); ?>" id="previewImg" alt="Hospital Logo">
                        <?php else: ?>
                            <i class="fa-solid fa-hospital placeholder-icon" id="placeholderIcon"></i>
                            <img src="" id="previewImg" alt="Hospital Logo" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <!-- Overlay inside circle -->
                    <div class="profile-img-overlay" id="imgOverlay">
                        <button type="button" class="img-overlay-btn" onclick="event.stopPropagation(); document.getElementById('profile_image').click()" title="Change Image">
                            <i class="fa-solid fa-camera"></i>
                            <span>Change</span>
                        </button>
                        <div class="overlay-divider" id="overlayDivider" style="<?php echo empty($current_image) ? 'display:none;' : ''; ?>"></div>
                        <button type="button" class="img-overlay-btn remove-btn" id="removeBtn"
                            onclick="event.stopPropagation(); removeImage()"
                            title="Remove Image"
                            style="<?php echo empty($current_image) ? 'display:none;' : ''; ?>">
                            <i class="fa-solid fa-trash"></i>
                            <span>Remove</span>
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($msg): ?>
                <?php $bg = $msg_type == 'success' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>
                <?php $clr = $msg_type == 'success' ? '#10b981' : '#ef4444'; ?>
                <div style="background: <?php echo $bg; ?>; color: <?php echo $clr; ?>; padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; border: 1px solid <?php echo $clr; ?>33;">
                    <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php echo get_csrf_input(); ?>

                <!-- Hidden file input -->
                <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                <!-- Hidden flag for remove -->
                <input type="hidden" name="remove_image" id="remove_image" value="0">

                <div class="form-group">
                    <label>Hospital Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($hospital['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($hospital['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Location (City)</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($hospital['location'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Full Address</label>
                    <textarea name="address" rows="2" required><?php echo htmlspecialchars($hospital['address'] ?? ''); ?></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:14px;">
                    <button type="submit" class="btn btn-primary flex-1" style="padding:10px; font-weight: 700; font-size:0.9rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Update Profile
                    </button>
                    <a href="hospital_dashboard.php" class="btn btn-outline flex-1" style="padding:10px; text-align:center; font-weight: 700; font-size:0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('previewImg');
                    const icon = document.getElementById('placeholderIcon');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if (icon) icon.style.display = 'none';
                    document.getElementById('removeBtn').style.display = 'flex';
                    document.getElementById('overlayDivider').style.display = '';
                    document.getElementById('remove_image').value = '0';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage() {
            if (!confirm('Remove the profile image?')) return;
            const img = document.getElementById('previewImg');
            const icon = document.getElementById('placeholderIcon');
            img.src = '';
            img.style.display = 'none';
            if (icon) icon.style.display = 'block';
            document.getElementById('removeBtn').style.display = 'none';
            document.getElementById('overlayDivider').style.display = 'none';
            document.getElementById('remove_image').value = '1';
            // Clear file input
            document.getElementById('profile_image').value = '';
        }
    </script>
</body>
</html>
