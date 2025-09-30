<?php
/**
 * GamePlan Scheduler - Logout Handler
 * 
 * Secure logout functionality with proper session cleanup and security measures.
 * 
 * @author Harsha Kanaparthi
 * @version 1.0
 * @date 2025-09-30
 */

require_once 'db.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Log the logout event
$username = $_SESSION['username'] ?? 'Unknown';
logEvent("User logged out: $username");

// Destroy session completely
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Start new session for flash message
session_start();
session_regenerate_id(true);

// Redirect to login page with logout confirmation
header("Location: login.php");
exit;
?>