<?php
include 'db_connect.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];

    // Validation
    if (strlen($name) < 3) {
        $error = "Name must be at least 3 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password_raw) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Phone number must be between 10 and 15 digits.";
    } elseif (empty($address)) {
        $error = "Address is required.";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

    // Check for duplicate email
    $checkQuery = "SELECT * FROM patients WHERE email = '$email'";
    $result = $conn->query($checkQuery);

    if ($result->num_rows > 0) {
        $error = "Patient with this email already exists!";
        echo "<script>alert('Patient with this email already exists!');</script>";
    } else {
        $sql = "INSERT INTO patients (name, email, password, address, phone, dob) VALUES ('$name', '$email', '$password', '$address', '$phone', '$dob')";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Registration successful! <a href='login.php'>Login here</a>";
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
    <title>Patient Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">C19 Portal</div>
                <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
                <div class="nav-links" id="navLinks">
                    <a href="index.php" class="btn btn-outline" style="border:none;">Home</a>
                    <a href="login.php" class="btn btn-primary">Login</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" style="display: flex; justify-content: center; padding-top: 60px; padding-bottom: 60px;">
        <div class="auth-box animate-fade" style="width: 100%; max-width: 500px;">
            <h2 style="text-align: center; margin-bottom: 30px;">Patient Registration</h2>
            <?php if($error) echo "<div class='badge badge-danger' style='width:100%; margin-bottom:20px; text-align:center;'>$error</div>"; ?>
            <?php if($success) echo "<div class='badge badge-success' style='width:100%; margin-bottom:20px; text-align:center;'>$success</div>"; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required minlength="3" placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="name@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="8" placeholder="Create a password (min 8 chars)">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" required pattern="[0-9]{10,15}" title="Phone number must be 10-15 digits" placeholder="+1 234 567 890">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" required placeholder="Your full address"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            </form>
            <p style="text-align:center; margin-top:20px; color:var(--text-muted);">Already have an account? <a href="login.php" style="color:var(--primary);">Login</a></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
