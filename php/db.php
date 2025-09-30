<?php
/**
 * GamePlan Scheduler - Enhanced Database Connection
 * 
 * This file provides secure database connectivity with comprehensive error handling,
 * session management, and security features for the GamePlan Scheduler application.
 * 
 * @author Harsha Kanaparthi
 * @version 1.1
 * @date 2025-09-30
 */

// Environment configuration
define('ENVIRONMENT', 'development'); // Change to 'production' for live deployment
define('APP_VERSION', '1.1');
define('APP_NAME', 'GamePlan Scheduler');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('CSRF_TOKEN_LENGTH', 32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Configure session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Start session with security checks
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID for security (prevent session fixation)
if (!isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = true;
    $_SESSION['session_started'] = time();
}

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        if (isset($_GET['ajax'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Session expired']);
            exit();
        } else {
            header('Location: login.php?timeout=1');
            exit();
        }
    }
}

$_SESSION['last_activity'] = time();

// Initialize global database connection
global $pdo;

try {
    // Create secure PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false, // Disabled for better security
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT            => 10,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+01:00'"); // Amsterdam timezone
    
    // Update user activity if logged in
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
} catch (PDOException $e) {
    // Log database connection error securely
    error_log("Database Connection Error: " . $e->getMessage() . " - " . date('Y-m-d H:i:s'));
    
    // Show user-friendly error message
    if (ENVIRONMENT === 'development') {
        die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;font-family:Arial,sans-serif;'>
                <h3>Database Connection Error</h3>
                <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>
                <p><strong>Line:</strong> " . $e->getLine() . "</p>
             </div>");
    } else {
        die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;font-family:Arial,sans-serif;text-align:center;'>
                <h3>Service Temporarily Unavailable</h3>
                <p>We're experiencing technical difficulties. Please try again later.</p>
                <p>If the problem persists, please contact support.</p>
             </div>");
    }
}

// ===================== CORE UTILITY FUNCTIONS =====================

/**
 * Check if user is logged in with enhanced security
 * 
 * @return bool True if user is logged in and session is valid
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['session_started'])) {
        return false;
    }
    
    // Check if session is too old (additional security)
    if (time() - $_SESSION['session_started'] > (SESSION_TIMEOUT * 2)) {
        logout();
        return false;
    }
    
    return true;
}

/**
 * Require login for protected pages
 * 
 * @param string $redirect_to Optional redirect URL after login
 */
function requireLogin($redirect_to = null) {
    if (!isLoggedIn()) {
        $redirect_url = 'login.php';
        if ($redirect_to) {
            $redirect_url .= '?redirect=' . urlencode($redirect_to);
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $redirect_url .= '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Get current authenticated user information
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, email, created_at, last_activity FROM Users WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Sanitize input data to prevent XSS
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

/**
 * Validate email format with comprehensive checks
 * 
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function validateEmail($email) {
    if (empty($email)) return false;
    
    // Basic format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Additional checks
    if (strlen($email) > 100) return false;
    if (strpos($email, '..') !== false) return false; // Consecutive dots
    
    return true;
}

/**
 * Validate username with enhanced rules
 * 
 * @param string $username Username to validate
 * @return array Result with 'valid' boolean and 'error' message
 */
function validateUsername($username) {
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Username is required'];
    }
    
    if (strlen($username) < 3) {
        return ['valid' => false, 'error' => 'Username must be at least 3 characters'];
    }
    
    if (strlen($username) > 50) {
        return ['valid' => false, 'error' => 'Username must be less than 50 characters'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
    }
    
    // Check for inappropriate words (basic filter)
    $blocked_words = ['admin', 'administrator', 'root', 'system', 'null', 'undefined'];
    if (in_array(strtolower($username), $blocked_words)) {
        return ['valid' => false, 'error' => 'This username is not allowed'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Generate secure CSRF token
 * 
 * @return string Generated token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 3600) { // Regenerate every hour
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token with timing attack protection
 * 
 * @param string $token Token to verify
 * @return bool True if valid token
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Secure logout with complete session cleanup
 */
function logout() {
    global $pdo;
    
    // Log the logout event
    if (isset($_SESSION['user_id'])) {
        try {
            logEvent("User logged out: " . ($_SESSION['username'] ?? 'Unknown'), 'INFO');
        } catch (Exception $e) {
            error_log("Error logging logout event: " . $e->getMessage());
        }
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Log application events with rotation
 * 
 * @param string $message Log message
 * @param string $level Log level (INFO, WARNING, ERROR, CRITICAL)
 * @param array $context Additional context data
 */
function logEvent($message, $level = 'INFO', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100);
    
    $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    
    $log_message = "[$timestamp] [$level] [User: $user_id] [IP: $ip] $message$context_str" . PHP_EOL;
    
    $log_file = __DIR__ . '/../logs/app_' . date('Y-m-d') . '.log';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Write to log file
    error_log($log_message, 3, $log_file);
    
    // Also log errors to system log
    if (in_array($level, ['ERROR', 'CRITICAL'])) {
        error_log("GamePlan [$level]: $message");
    }
}

/**
 * Set flash message for user feedback
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => sanitizeInput($message),
        'timestamp' => time()
    ];
}

/**
 * Get and clear flash messages
 * 
 * @return array Array of flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    
    // Filter out old messages (older than 5 minutes)
    $filtered_messages = array_filter($messages, function($msg) {
        return (time() - $msg['timestamp']) < 300;
    });
    
    return array_values($filtered_messages);
}

/**
 * Rate limiting for login attempts
 * 
 * @param string $identifier IP address or username
 * @return bool True if rate limit exceeded
 */
function isRateLimited($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];
    }
    
    $data = $_SESSION[$key];
    
    // Reset counter if lockout time has passed
    if (time() - $data['last_attempt'] > LOGIN_LOCKOUT_TIME) {
        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];
        return false;
    }
    
    return $data['attempts'] >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Record login attempt
 * 
 * @param string $identifier IP address or username
 * @param bool $success Whether the attempt was successful
 */
function recordLoginAttempt($identifier, $success = false) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];
    }
    
    if ($success) {
        // Reset on successful login
        unset($_SESSION[$key]);
    } else {
        // Increment attempts on failure
        $_SESSION[$key]['attempts']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
}

// Set timezone for consistent date/time handling
date_default_timezone_set('Europe/Amsterdam');

// Initialize application
if (ENVIRONMENT === 'development') {
    // Development mode indicators
    header('X-Debug-Mode: Enabled');
    header('X-App-Version: ' . APP_VERSION);
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Performance headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>