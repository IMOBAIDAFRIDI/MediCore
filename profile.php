<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$id = $_SESSION['user_id'];
$msg = "";
$error = "";

// Update logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $address = $_POST['address'];
    
    if ($role == 'patient') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $sql = "UPDATE patients SET name='$name', phone='$phone', address='$address' WHERE id=$id";
    } elseif ($role == 'hospital') {
        $name = $_POST['name'];
        $location = $_POST['location'];
        
        $image_path = "";
        // Handle File Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['logo']['name'];
            $filetype = $_FILES['logo']['type'];
            $filesize = $_FILES['logo']['size'];
        
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), $allowed)) {
               $error = "Error: Please select a valid file format.";
            } else {
                if(file_exists("uploads/" . $filename)){
                    $filename = time() . "_" . $filename; // renaming file
                }
                
                // Ensure uploads directory exists
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/" . $filename);
                $image_path = "uploads/" . $filename;
            }
        }
        
        if (empty($error)) {
            if ($image_path) {
                // If new image uploaded, update image field too
                $sql = "UPDATE hospitals SET name='$name', location='$location', address='$address', image='$image_path' WHERE id=$id";
            } else {
                // Keep old image
                $sql = "UPDATE hospitals SET name='$name', location='$location', address='$address' WHERE id=$id";
            }
        }
    }
    
    if (empty($error) && isset($sql)) {
        if ($conn->query($sql) === TRUE) {
            $_SESSION['name'] = $name; // Update session name
            if (isset($image_path) && $image_path) $_SESSION['image'] = $image_path; // Update session image
            $msg = "Profile updated successfully!";
        } else {
            $msg = "Error updating profile.";
        }
    }
}

// Fetch current data
if ($role == 'patient') {
    $data = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
} elseif ($role == 'hospital') {
    $data = $conn->query("SELECT * FROM hospitals WHERE id=$id")->fetch_assoc();
} else {
    // Admin has no profile to edit in this simple version
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-pic-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary);
            cursor: pointer;
            background: #f0f0f0;
        }
        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-pic-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            text-align: center;
            padding: 5px 0;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .profile-pic-container:hover .profile-pic-overlay {
            opacity: 1;
        }
        #logo-upload {
            display: none;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">My Profile</div>
                <div class="nav-links">
                    <a href="<?php echo $role; ?>_dashboard.php" class="btn btn-outline">Back</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="hero">
        <div class="auth-box animate-fade" style="width: 100%; max-width: 500px;">
            <h3 style="text-align: center; margin-bottom: 20px;">Edit Profile</h3>
            <?php if ($msg) echo "<p style='color: var(--success); margin-bottom: 20px; text-align: center;'>$msg</p>"; ?>
            <?php if ($error) echo "<p style='color: var(--danger); margin-bottom: 20px; text-align: center;'>$error</p>"; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                
                <?php if ($role == 'hospital') { 
                    $current_image = !empty($data['image']) ? $data['image'] : 'https://cdn-icons-png.flaticon.com/512/33/33777.png';
                ?>
                    <div style="text-align: center;">
                        <input type="file" name="logo" id="logo-upload" onchange="previewImage(this)">
                        <label for="logo-upload" class="profile-pic-container" title="Click to change logo">
                            <img src="<?php echo $current_image; ?>" id="profile-preview" class="profile-pic" alt="Hospital Logo">
                            <div class="profile-pic-overlay">
                                <i class="fa-solid fa-camera"></i> Edit
                            </div>
                        </label>
                    </div>
                <?php } ?>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo $data['name']; ?>" required>
                </div>
                
                <?php if ($role == 'patient') { ?>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo $data['phone']; ?>" required>
                    </div>
                <?php } else { ?>
                     <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo $data['location']; ?>" required>
                    </div>
                <?php } ?>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" required><?php echo $data['address']; ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Changes</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
