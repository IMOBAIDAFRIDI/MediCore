<?php
session_start();
include 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

// Handle Update
if (isset($_POST['update_hospital'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $address = $conn->real_escape_string($_POST['address']);
    $location = $conn->real_escape_string($_POST['location']);
    
    // Default to keeping existing image
    $image_query_part = "";
    
    // Handle Image Upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetype = $_FILES['image']['type'];
        $filesize = $_FILES['image']['size'];
    
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $allowed)) {
            $msg = "Error: Invalid file format. Only JPG, PNG, GIF, and WebP allowed.";
        } elseif ($filesize > 5000000) { // 5MB limit
            $msg = "Error: File size too large. Max 5MB.";
        } else {
            // Ensure uploads directory exists
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $new_filename = time() . "_" . $filename; // Rename file to avoid collisions
            $target_path = "uploads/" . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image = $conn->real_escape_string($target_path);
                $image_query_part = ", image='$image'";
            } else {
                $msg = "Error uploading file.";
            }
        }
    } else {
        // Fallback: If URL provided via text input (legacy support or external URL)
        // But we prioritize file upload. If no file, check if url field was used? 
        // Current design uses file input. Let's stick to file input as primary.
        // If user wants to paste URL, we might need a separate field or logic, 
        // but user requested "profile image and logo same", implying file upload sync.
        // We'll trust the file upload. 
    }

    if (empty($msg)) {
        $sql = "UPDATE hospitals SET name='$name', email='$email', address='$address', location='$location' $image_query_part WHERE id=$id";
        if ($conn->query($sql)) {
            header("Location: admin_hospitals.php");
            exit();
        } else {
            $msg = "Error updating record: " . $conn->error;
        }
    }
}

// Fetch Hospital Data
$sql = "SELECT * FROM hospitals WHERE id=$id";
$result = $conn->query($sql);
$hospital = $result->fetch_assoc();

if (!$hospital) {
    die("Hospital not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Hospital | MediCore Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .image-preview-container {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary);
            margin-bottom: 15px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="glass-nav">
        <a href="admin_dashboard.php" class="nav-brand">
            <i class="fa-solid fa-heart-pulse text-primary"></i>
            <span>MediCore</span>
        </a>
         <div class="nav-links-container">
            <a href="admin_dashboard.php" class="nav-link">
                <i class="fa-solid fa-chart-grid"></i> Dashboard
            </a>
            <a href="admin_hospitals.php" class="nav-link active">
                <i class="fa-solid fa-hospital"></i> Hospitals
            </a>
             <a href="logout.php" class="nav-link" style="color: #ef4444;">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </nav>

    <div class="main-wrapper animate-fade">
        <div class="page-header-simple">
            <div>
                <h1>Edit Hospital</h1>
                <p class="text-muted">Update hospital details</p>
            </div>
            <a href="admin_hospitals.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <div class="glass-card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit Details</h3>
            </div>
            <div class="p-4">
                <?php if($msg) echo "<div class='badge badge-danger mb-3' style='display:block'>$msg</div>"; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label class="text-muted mb-2 d-block">Hospital Name</label>
                        <input type="text" name="name" class="glass-input" value="<?php echo htmlspecialchars($hospital['name']); ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-muted mb-2 d-block">Email Address</label>
                        <input type="email" name="email" class="glass-input" value="<?php echo htmlspecialchars($hospital['email']); ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-muted mb-2 d-block">Location (City)</label>
                        <input type="text" name="location" class="glass-input" value="<?php echo htmlspecialchars($hospital['location']); ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-muted mb-2 d-block">Hospital Logo</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="image-preview-container">
                                <?php 
                                    $current_img = !empty($hospital['image']) ? $hospital['image'] : 'https://cdn-icons-png.flaticon.com/512/33/33777.png';
                                ?>
                                <img src="<?php echo htmlspecialchars($current_img); ?>" alt="Current Logo" class="image-preview" id="preview-img">
                            </div>
                            <div style="flex-grow: 1;">
                                <input type="file" name="image" class="glass-input" accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted d-block mt-1">Leave empty to keep current logo.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="text-muted mb-2 d-block">Full Address</label>
                        <textarea name="address" class="glass-input" rows="3" required><?php echo htmlspecialchars($hospital['address']); ?></textarea>
                    </div>

                    <button type="submit" name="update_hospital" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
