<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store it in the session.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token.
 * @param string $token The token to verify.
 * @return bool True if valid, false otherwise.
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        // error_log("CSRF Fail: Session token missing or input empty.");
        return false;
    }
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        // error_log("CSRF Fail: Token mismatch. Session: " . $_SESSION['csrf_token'] . " Input: " . $token);
        return false;
    }
    return true;
}

/**
 * Render the CSRF token input field.
 */
function get_csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
?>
