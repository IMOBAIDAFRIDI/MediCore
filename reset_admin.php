<?php
/**
 * Admin Credentials Reset Tool (command line only).
 *
 *   ADMIN_PASSWORD='choose-a-strong-one' php reset_admin.php
 *   php reset_admin.php 'choose-a-strong-one'
 *
 * The password is never stored in this file. On the live site the admin
 * password comes from the ADMIN_PASSWORD environment variable instead
 * (applied automatically on every boot by deploy/init_db.php).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this tool from the command line.');
}

require_once __DIR__ . '/db_connect_pdo.php';

$new_email = getenv('ADMIN_EMAIL') ?: 'admin@medicore';
$new_password = $argv[1] ?? getenv('ADMIN_PASSWORD');
if (!$new_password) {
    fwrite(STDERR, "Provide the new password as an argument or in ADMIN_PASSWORD.\n");
    exit(1);
}
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, name = 'Admin' WHERE id = ?");
        $stmt->execute([$new_email, $hashed_password, $admin['id']]);
        echo "Admin account updated: $new_email\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES ('Admin', ?, ?, 'admin', 'approved')");
        $stmt->execute([$new_email, $hashed_password]);
        echo "Admin account created: $new_email\n";
    }
} catch (PDOException $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
