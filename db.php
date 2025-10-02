<?php
// Database connection file for GamePlan Scheduler
// Author: Harsha Kanaparthi
// Features: PDO with prepared statements support, error mode for debugging, persistent connection for performance,
// UTF-8 charset for international support, try-catch for graceful failure.

$host = 'localhost';  // Database host
$dbname = 'gameplan_db';  // Database name from schema
$user = 'root';  // Default XAMPP user - change for production
$pass = '';  // Default XAMPP password - change for production

try {
    // Establish PDO connection with advanced options
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors for debugging
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Fetch as associative arrays
        PDO::ATTR_EMULATE_PREPARES => false,  // Use real prepared statements for security
        PDO::ATTR_PERSISTENT => true,  // Persistent connections for better performance in loops
    ]);
    // Update last_activity on every request if user is logged in (for timeout and online status)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
    }
} catch (PDOException $e) {
    // Handle connection failure gracefully - log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage(), 0);  // Log to error file
    die("Sorry, we're experiencing technical issues. Please try again later.");  // User message
}

// Function to get PDO instance (for functions.php)
function getPDO() {
    global $pdo;
    return $pdo;
}
?>