<?php
require_once 'functions.php';

// Destroy all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear remember me cookie
setcookie('remember_token', '', time() - 3600, '/');

// Redirect to login page
header("Location: login.php?success=You have been logged out successfully");
exit();
?>