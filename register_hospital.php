<?php
include 'db_connect.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $address = trim($_POST['address']);
    $location = trim($_POST['location']);
    // Default image for new registrations
    $image = 'https://cdn-icons-png.flaticon.com/512/33/33777.png';

    // Validation
    if (strlen($name) < 3) {
        $error = "Hospital name must be at least 3 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password_raw) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (empty($location)) {
        $error = "Location is required.";
    } elseif (empty($address)) {
        $error = "Address is required.";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

    // Check for duplicate email
    $checkQuery = "SELECT * FROM hospitals WHERE email = '$email'";
    $result = $conn->query($checkQuery);

    if ($result->num_rows > 0) {
        $error = "Hospital with this email already exists!";
        echo "<script>alert('Hospital with this email already exists!');</script>";
    } else {
        $sql = "INSERT INTO hospitals (name, email, password, address, location, image, status) VALUES ('$name', '$email', '$password', '$address', '$location', '$image', 'pending')";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Registration successful! Status is Pending Admin Approval. <a href='login.php'>Login</a>";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
    } // End validation else
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">C19 Portal</div>
                <div class="nav-links">
                    <a href="index.php" class="btn btn-outline" style="border:none;">Home</a>
                    <a href="login.php" class="btn btn-primary">Login</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="display: flex; justify-content: center; padding-top: 60px; padding-bottom: 60px;">
        <div class="auth-box animate-fade" style="width: 100%; max-width: 500px;">
            <h2 style="text-align: center; margin-bottom: 30px;">Hospital Registration</h2>
            <?php if($error) echo "<div class='badge badge-danger' style='width:100%; margin-bottom:20px; text-align:center;'>$error</div>"; ?>
            <?php if($success) echo "<div class='badge badge-success' style='width:100%; margin-bottom:20px; text-align:center;'>$success</div>"; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Hospital Name</label>
                    <input type="text" name="name" required minlength="3" placeholder="e.g. City General Hospital">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="contact@hospital.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="8" placeholder="Secure password (min 8 chars)">
                </div>

                <div class="form-group">
                    <label>Location (City/Area)</label>
                    <input type="text" name="location" required placeholder="e.g. New York">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" required placeholder="Full physical address"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Application</button>
            </form>
            <p style="text-align:center; margin-top:20px; color:var(--text-muted);">Already registered? <a href="login.php" style="color:var(--primary);">Login</a></p>
        </div>
    </div>
</body>
</html>
