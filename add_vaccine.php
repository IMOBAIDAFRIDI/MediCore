<?php
session_start();
include 'db_connect.php';

// Only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vaccine'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $img = $_POST['image']; // URL
    
    $stmt = $conn->prepare("INSERT INTO vaccines (name, description, image) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $desc, $img);
    if ($stmt->execute()) {
        header("Location: manage_vaccines.php?msg=added");
        exit();
    } else {
        $message = "Error adding vaccine: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Vaccine | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-form {
            background: var(--glass-bg);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            max-width: 600px;
            margin: 40px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            color: white;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--light);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo"><i class="fa-solid fa-syringe"></i> Add Vaccine</div>
                <div class="nav-links">
                    <a href="manage_vaccines.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="admin-form animate-fade">
            <h2 style="text-align: center; margin-bottom: 25px; color: var(--primary);"><i class="fa-solid fa-plus-circle"></i> Add New Vaccine</h2>
            
            <?php if (!empty($message)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <i class="fa-solid fa-exclamation-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="add_vaccine" value="1">
                <div class="form-group">
                    <label>Vaccine Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Pfizer-BioNTech">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Brief description of the vaccine..."></textarea>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image" class="form-control" placeholder="https://example.com/image.jpg">
                    <small style="color: var(--text-muted); display: block; margin-top: 5px;">Provide a direct link to an image (JPG, PNG, WebP)</small>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.1rem;"><i class="fa-solid fa-save"></i> Save Vaccine</button>
            </form>
        </div>
    </div>
</body>
</html>
