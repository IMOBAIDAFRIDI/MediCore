<?php
/**
 * Admin Credentials Reset Tool
 */
require_once 'db_connect_pdo.php';

$new_email = 'admin@medicore';
$new_password = 'O@baid70245';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, name = 'Admin' WHERE id = ?");
        $stmt->execute([$new_email, $hashed_password, $admin['id']]);
        echo "<h1>Admin Account Updated Successfully!</h1>";
    } else {
        // Create it if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Admin', ?, ?, 'admin', 'approved')");
        $stmt->execute([$new_email, $hashed_password]);
        echo "<h1>Admin Account Created Successfully!</h1>";
    }

    echo "<p><b>New Email:</b> " . htmlspecialchars($new_email) . "</p>";
    echo "<p><b>New Password:</b> " . htmlspecialchars($new_password) . "</p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";

} catch (PDOException $e) {
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
}
?>
