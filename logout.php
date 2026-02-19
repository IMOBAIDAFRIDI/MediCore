<?php
/**
 * Logout Page (Legacy Standalone)
 */
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['role']);
unset($_SESSION['name']);
unset($_SESSION['email']);
session_destroy();
header('Location: index.php');
exit();
