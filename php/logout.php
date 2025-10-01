<?php<?php

/**/**

 * GamePlan Scheduler - Professional User Logout System * GamePlan Scheduler - Logout Handler

 * Secure logout with session cleanup and audit logging * 

 *  * Secure logout functionality with proper session cleanup and security measures.

 * @author Harsha Kanaparthi * 

 * @version 2.1 Professional Edition * @author Harsha Kanaparthi

 * @date September 30, 2025 * @version 1.0

 * @description Secure logout system with comprehensive session cleanup * @date 2025-09-30

 */ */



// Enable comprehensive error reporting for developmentrequire_once 'db.php';

error_reporting(E_ALL);require_once 'functions.php';

ini_set('display_errors', 1);

// Check if user is logged in

// Include required filesif (!isLoggedIn()) {

require_once 'db.php';    header('Location: login.php');

require_once 'functions.php';    exit();

}

// Initialize session securely

if (session_status() === PHP_SESSION_NONE) {// Log the logout event

    session_start();$username = $_SESSION['username'] ?? 'Unknown';

}logEvent("User logged out: $username");



// CSRF Protection for logout requests// Destroy session completely

if ($_SERVER['REQUEST_METHOD'] === 'POST') {session_unset();

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {session_destroy();

        // Invalid CSRF token - redirect with error

        header("Location: ../index.php?error=invalid_logout");// Clear session cookie

        exit;if (ini_get("session.use_cookies")) {

    }    $params = session_get_cookie_params();

}    setcookie(session_name(), '', time() - 42000,

        $params["path"], $params["domain"],

// Check if user is logged in        $params["secure"], $params["httponly"]

$was_logged_in = isLoggedIn();    );

$username = $_SESSION['username'] ?? 'Unknown User';}

$user_id = $_SESSION['user_id'] ?? null;

// Start new session for flash message

if ($was_logged_in) {session_start();

    try {session_regenerate_id(true);

        // Log the logout event with timestamp

        error_log("[" . date('Y-m-d H:i:s') . "] User logout: $username (ID: $user_id) from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));// Redirect to login page with logout confirmation

        header("Location: login.php");

        // Update user's last activity in databaseexit;

        if ($user_id) {?>
            $db = getDBConnection();
            $stmt = $db->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        // Perform secure logout
        $logout_success = logoutUser();
        
        if ($logout_success) {
            // Redirect to login page with success message
            header("Location: login.php?logout=success");
            exit;
        } else {
            // Logout failed - redirect with error
            header("Location: ../index.php?error=logout_failed");
            exit;
        }
        
    } catch (Exception $e) {
        // Log the error
        error_log("Logout error for user $username: " . $e->getMessage());
        
        // Force session cleanup even if database update fails
        session_destroy();
        
        // Redirect with error
        header("Location: login.php?error=logout_error");
        exit;
    }
} else {
    // User wasn't logged in - redirect to login page
    header("Location: login.php?message=not_logged_in");
    exit;
}

// This should never be reached, but just in case
header("Location: login.php");
exit;
?>