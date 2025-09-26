<?php
/**
 * GamePlan Scheduler - Enterprise Database Configuration
 * Advanced Gaming Schedule Management Platform
 * 
 * @author Harsha Kanaparthi
 * @version 2.0 Professional Edition
 * @security Enterprise-grade PDO with prepared statements
 */

// ===================================
// DATABASE CONFIGURATION
// ===================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_db');
define('DB_USER', 'root'); // Change for production
define('DB_PASS', ''); // Set secure password for production
define('DB_CHARSET', 'utf8mb4');

// ===================================
// PDO OPTIONS - ENTERPRISE SECURITY
// ===================================
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => true, // Connection pooling
];

try {
    // Create PDO instance
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $options
    );

    // Set timezone for consistent timestamps
    $pdo->exec("SET time_zone = '+00:00'");
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

} catch (PDOException $e) {
    // Log error securely (don't expose in production)
    error_log("Database connection failed: " . $e->getMessage());

    // Show user-friendly error
    die("Database connection error. Please try again later.");
}

// Function to get database connection
function getDB() {
    global $pdo;
    return $pdo;
}

// Function to execute prepared statements safely
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Database query error");
    }
}

// Function to get single row
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Function to get multiple rows
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Function to get row count
function rowCount($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

// Function to insert and get last ID
function insertAndGetId($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

// Security function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to regenerate session ID for security
function regenerateSession() {
    session_regenerate_id(true);
}

// Function to log user activity
function logActivity($user_id, $action, $details = '') {
    $sql = "INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())";
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    executeQuery($sql, [$user_id, $action, $details, $ip]);
}

// Function to check rate limiting (brute force protection)
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 900) {
    $sql = "SELECT COUNT(*) as attempts FROM login_attempts
            WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $attempts = fetchOne($sql, [$identifier, $time_window]);

    if ($attempts['attempts'] >= $max_attempts) {
        return false; // Rate limit exceeded
    }

    return true; // OK to proceed
}

// Function to log failed login attempt
function logFailedLogin($identifier) {
    $sql = "INSERT INTO login_attempts (identifier, created_at) VALUES (?, NOW())";
    executeQuery($sql, [$identifier]);
}

// Function to clean old login attempts
function cleanOldLoginAttempts($days = 30) {
    $sql = "DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    executeQuery($sql, [$days]);
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'gc_maxlifetime' => 1800, // 30 minutes
    ]);
}

// Clean old login attempts periodically (1% chance)
if (rand(1, 100) === 1) {
    cleanOldLoginAttempts();
}
?>