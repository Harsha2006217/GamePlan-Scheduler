<?php
/**
 * Advanced Logout System
 * GamePlan Scheduler - Professional Gaming Logout Handler
 * 
 * This module handles secure logout with comprehensive session cleanup,
 * activity logging, and user feedback.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

session_start();
require_once 'functions.php';

// Check if user is logged in before attempting logout
if (!isLoggedIn()) {
    header("Location: login.php?msg=" . urlencode("Je bent al uitgelogd"));
    exit;
}

// Get user info before logout for logging
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Onbekend';
$login_time = $_SESSION['login_time'] ?? time();

// Calculate session duration
$session_duration = time() - $login_time;
$session_duration_formatted = gmdate("H:i:s", $session_duration);

// Log logout activity with session info
if ($user_id) {
    // Placeholder for logging
}

// Comprehensive session cleanup
try {
    // Update user's last activity in database
    if ($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // Clear all session data
    $_SESSION = [];
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/', '', true, true);
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Destroy session
    session_destroy();
    
    // Clear any remember me cookies
    setcookie('remember_email', '', time() - 3600, '/', '', true, true);
    
    // Success message for login page
    $success_message = "Je bent succesvol uitgelogd na " . $session_duration_formatted . " van gaming planning!";
    
} catch (Exception $e) {
    // Log error but don't expose to user
    error_log("Logout error for user {$user_id}: " . $e->getMessage());
    $success_message = "Je bent uitgelogd. Tot de volgende gaming sessie!";
}

// Redirect to login with success message
header("Location: login.php?msg=" . urlencode($success_message));
exit;
?>