<?php
// Function to log actions to the database
function log_action($pdo, $action, $description = null, $user_id = null, $user_type = 'guest') {
    // If user info is not provided, try to get it from session
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if ($user_type === 'guest' && isset($_SESSION['role'])) {
        $user_type = $_SESSION['role'];
    }

    $ip_address = $_SERVER['REMOTE_ADDR'];

    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user_type, action, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $user_type, $action, $description, $ip_address]);
    } catch (PDOException $e) {
        // Silently fail logging to avoid disrupting the user flow, 
        // OR log to a file as fallback if strict auditing is required.
        error_log("Audit Log Failed: " . $e->getMessage());
    }
}
?>
