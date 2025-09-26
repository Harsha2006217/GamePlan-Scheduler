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

// Global connection variable
$pdo = null;

/**
 * Get database connection
 * Creates and returns a PDO connection with error handling
 */
function getDbConnection() {
    global $pdo;
    
    // If connection already exists, return it
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Set DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Set PDO options for secure connection
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        // Create new PDO connection
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
    } catch (PDOException $e) {
        // Log error and display user-friendly message
        error_log("Database Connection Error: " . $e->getMessage());
        die("Could not connect to the database. Please contact the administrator.");
    }
}

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
    // Removed activity logging as per schema
    // Placeholder for future implementation
}

// Function to check rate limiting (brute force protection)
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 900) {
    // Simplified rate limiting without database table
    // Use session-based rate limiting
    $key = 'rate_limit_' . $identifier;
    $attempts = $_SESSION[$key] ?? [];
    $attempts = array_filter($attempts, function($time) use ($time_window) {
        return $time > time() - $time_window;
    });
    if (count($attempts) >= $max_attempts) {
        return false;
    }
    return true;
}

// Function to log failed login attempt
function logFailedLogin($identifier) {
    $key = 'rate_limit_' . $identifier;
    $attempts = $_SESSION[$key] ?? [];
    $attempts[] = time();
    $_SESSION[$key] = $attempts;
}

// Function to clean old login attempts
function cleanOldLoginAttempts($days = 30) {
    // Clean session-based attempts
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'rate_limit_') === 0 && is_array($value)) {
            $_SESSION[$key] = array_filter($value, function($time) use ($days) {
                return $time > time() - ($days * 24 * 3600);
            });
        }
    }
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

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Only works on HTTPS
ini_set('session.gc_maxlifetime', 1800); // 30 minutes timeout

// Update user's last activity timestamp
function updateLastActivity($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ?");
    $stmt->execute([$user_id]);
}
?>