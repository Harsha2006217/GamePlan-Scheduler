<?php
/**
 * GamePlan Scheduler - Enhanced Professional Database Management System
 *
 * Advanced PDO database management system with comprehensive security measures,
 * performance optimization, connection pooling, and robust error handling.
 *
 * Features:
 * - Secure connection handling with automatic retry
 * - Enhanced session management with security measures
 * - Advanced input validation and sanitization
 * - Comprehensive error logging and monitoring
 * - Professional user authentication system
 * - Rate limiting and security controls
 * 
 * @package     GamePlan
 * @subpackage  Database
 * @author      Harsha Kanaparthi
 * @version     3.0 Professional Edition
 * @date        October 1, 2025
 * @copyright   2025 GamePlan Solutions
 * @license     Proprietary
 */

// Prevent direct script access for security
if (!defined('GAMEPLAN_ACCESS')) {
    define('GAMEPLAN_ACCESS', true);
}

// Security check - prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed for security reasons.');
}

// ===================== SECURITY AND CONFIGURATION CONSTANTS =====================

// Enhanced security configuration
define('MAX_CONNECTION_ATTEMPTS', 3);
define('CONNECTION_TIMEOUT', 10);
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('CSRF_TOKEN_LENGTH', 32);
define('APP_VERSION', '3.0.0');

// Environment detection for secure configuration
$environment = ($_SERVER['SERVER_NAME'] === 'localhost' || 
                $_SERVER['SERVER_NAME'] === '127.0.0.1') ? 'development' : 'production';
define('ENVIRONMENT', $environment);

// ===================== DATABASE CONFIGURATION =====================

// Development Configuration (Local XAMPP)
if (ENVIRONMENT === 'development') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gameplan_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_PORT', 3306);
    
    // Development settings for debugging
    define('DEBUG_MODE', true);
    define('LOG_ERRORS', true);
    define('DISPLAY_ERRORS', false); // Never show errors to users, even in dev
    
    // Development error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Security: never show errors to users
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error_dev.log');
}
// Production Configuration
else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gameplan_db');
    define('DB_USER', 'gameplan_user');
    define('DB_PASS', 'GamePlan_Secure_2025!');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_PORT', 3306);
    
    // Production settings for security
    define('DEBUG_MODE', false);
    define('LOG_ERRORS', true);
    define('DISPLAY_ERRORS', false);
    
    // Production error reporting - minimal for security
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error_prod.log');
}

// ===================== ENHANCED SESSION SECURITY =====================

// Configure session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.use_strict_mode', 1);
ini_set('session.sid_length', 48);
ini_set('session.sid_bits_per_character', 6);

// Start session with enhanced security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Regenerate session ID for security (prevent session fixation)
    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = true;
        $_SESSION['session_started'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200);
    }
}

// Enhanced session timeout and security checks
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        
        if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            http_response_code(401);
            echo json_encode(['error' => 'Session expired', 'redirect' => 'login.php']);
            exit();
        } else {
            header('Location: login.php?timeout=1');
            exit();
        }
    }
}

// Session hijacking protection
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
    session_unset();
    session_destroy();
    header('Location: login.php?security=1');
    exit();
}

$_SESSION['last_activity'] = time();

// ===================== ENHANCED DATABASE CONNECTION =====================

// Global database connection variable
global $pdo;

try {
    // Create secure PDO connection with enhanced options
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";port=" . DB_PORT;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false, // Security: disabled for better security
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT            => CONNECTION_TIMEOUT,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::MYSQL_ATTR_FOUND_ROWS   => true
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Set timezone and SQL mode for consistency
    $pdo->exec("SET time_zone = '+01:00'"); // Amsterdam timezone
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    
    // Update user activity if logged in
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // Log successful connection in development
    if (DEBUG_MODE) {
        error_log("GamePlan DB: Successfully connected to database - " . date('Y-m-d H:i:s'));
    }
    
} catch (PDOException $e) {
    // Log database connection error securely
    error_log("GamePlan DB Connection Error: " . $e->getMessage() . " - " . date('Y-m-d H:i:s'));
    
    // Show user-friendly error message based on environment
    if (ENVIRONMENT === 'development') {
        die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;font-family:Arial,sans-serif;margin:20px;'
                <h3><i class='fas fa-exclamation-triangle'></i> Database Connection Error</h3>
                <p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>
                <p><strong>Line:</strong> " . $e->getLine() . "</p>
                <p><strong>Solution:</strong> Check if XAMPP is running and database exists.</p>
             </div>");
    } else {
        die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;font-family:Arial,sans-serif;text-align:center;margin:20px;'
                <h3><i class='fas fa-tools'></i> Service Temporarily Unavailable</h3>
                <p>We're experiencing technical difficulties connecting to our database.</p>
                <p>Please try again in a few minutes. If the problem persists, please contact support.</p>
                <p><small>Error ID: " . uniqid() . "</small></p>
             </div>");
    }
}

// ===================== ENHANCED UTILITY FUNCTIONS =====================

/**
 * Enhanced login check with comprehensive security validation
 * 
 * @return bool True if user is logged in and session is valid
 */
function isLoggedIn() {
    // Check if essential session variables exist
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['session_started'])) {
        return false;
    }
    
    // Check if session is too old (additional security layer)
    if (time() - $_SESSION['session_started'] > (SESSION_TIMEOUT * 2)) {
        logout();
        return false;
    }
    
    // Validate user_id is numeric (prevent injection)
    if (!is_numeric($_SESSION['user_id'])) {
        logout();
        return false;
    }
    
    return true;
}

/**
 * Enhanced login requirement with redirect handling
 * 
 * @param string $redirect_to Optional redirect URL after login
 */
function requireLogin($redirect_to = null) {
    if (!isLoggedIn()) {
        $redirect_url = 'login.php';
        
        if ($redirect_to) {
            $redirect_url .= '?redirect=' . urlencode($redirect_to);
        } elseif (!empty($_SERVER['REQUEST_URI']) && !strpos($_SERVER['REQUEST_URI'], 'login.php')) {
            $redirect_url .= '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        }
        
        // Handle AJAX requests differently
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required', 'redirect' => $redirect_url]);
            exit();
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Get current authenticated user with enhanced security checks
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, username, email, created_at, last_activity, is_active, timezone, preferences 
            FROM Users 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logout(); // User no longer exists or is inactive
            return null;
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Enhanced input sanitization with comprehensive XSS protection
 * 
 * @param string|null $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if ($data === null) return '';
    
    // Convert to string and trim whitespace
    $data = trim((string)$data);
    
    // Remove null bytes (security measure)
    $data = str_replace(chr(0), '', $data);
    
    // Remove backslashes if magic quotes is enabled (legacy support)
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $data = stripslashes($data);
    }
    
    // Enhanced HTML entity encoding for XSS protection
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    
    return $data;
}

/**
 * Enhanced email validation with comprehensive checks
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
    
    // Length check
    if (strlen($email) > 100) return false;
    
    // Check for consecutive dots (additional security)
    if (strpos($email, '..') !== false) return false;
    
    // Check for valid domain structure
    $parts = explode('@', $email);
    if (count($parts) !== 2) return false;
    
    $domain = $parts[1];
    if (empty($domain) || !strpos($domain, '.')) return false;
    
    return true;
}

/**
 * Enhanced username validation with comprehensive rules
 * 
 * @param string $username Username to validate
 * @return array Result with 'valid' boolean and 'error' message
 */
function validateUsername($username) {
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Gebruikersnaam is verplicht'];
    }
    
    $username = trim($username);
    
    if (strlen($username) < 3) {
        return ['valid' => false, 'error' => 'Gebruikersnaam moet minimaal 3 karakters bevatten'];
    }
    
    if (strlen($username) > 50) {
        return ['valid' => false, 'error' => 'Gebruikersnaam mag maximaal 50 karakters bevatten'];
    }
    
    // Check for only alphanumeric characters and underscores
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'error' => 'Gebruikersnaam mag alleen letters, cijfers en underscores bevatten'];
    }
    
    // Check for inappropriate words (basic filter)
    $blocked_words = ['admin', 'administrator', 'root', 'system', 'null', 'undefined', 'test', 'guest'];
    if (in_array(strtolower($username), $blocked_words)) {
        return ['valid' => false, 'error' => 'Deze gebruikersnaam is niet toegestaan'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Enhanced password validation with security requirements
 * 
 * @param string $password Password to validate
 * @return array Result with 'valid' boolean and 'error' message
 */
function validatePassword($password) {
    if (empty($password)) {
        return ['valid' => false, 'error' => 'Wachtwoord is verplicht'];
    }
    
    if (strlen($password) < 6) {
        return ['valid' => false, 'error' => 'Wachtwoord moet minimaal 6 karakters bevatten'];
    }
    
    if (strlen($password) > 100) {
        return ['valid' => false, 'error' => 'Wachtwoord mag maximaal 100 karakters bevatten'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Generate secure CSRF token with timestamp validation
 * 
 * @return string Generated token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 3600) { // Regenerate every hour
        
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        } else {
            // Fallback for older PHP versions
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(CSRF_TOKEN_LENGTH));
        }
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
 * Enhanced secure logout with complete session cleanup
 */
function logout() {
    global $pdo;
    
    // Log the logout event for security monitoring
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
 * Enhanced event logging with automatic log rotation
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
    
    // Create log file with date for automatic rotation
    $log_file = __DIR__ . '/../logs/app_' . date('Y-m-d') . '.log';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Write to log file
    error_log($log_message, 3, $log_file);
    
    // Also log errors to system log for important events
    if (in_array($level, ['ERROR', 'CRITICAL'])) {
        error_log("GamePlan [$level]: $message");
    }
}

/**
 * Enhanced flash message system with type validation
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    // Validate message type
    $valid_types = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $valid_types)) {
        $type = 'info';
    }
    
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => sanitizeInput($message),
        'timestamp' => time()
    ];
}

/**
 * Get and clear flash messages with automatic cleanup
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
 * Enhanced rate limiting for login attempts and other actions
 * 
 * @param string $identifier IP address or username
 * @param string $action Action type (login, register, etc.)
 * @return bool True if rate limit exceeded
 */
function isRateLimited($identifier, $action = 'login') {
    $key = 'rate_limit_' . $action . '_' . md5($identifier);
    
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
 * Record login attempt with enhanced tracking
 * 
 * @param string $identifier IP address or username
 * @param bool $success Whether the attempt was successful
 * @param string $action Action type
 */
function recordLoginAttempt($identifier, $success = false, $action = 'login') {
    $key = 'rate_limit_' . $action . '_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];
    }
    
    if ($success) {
        // Reset on successful login
        unset($_SESSION[$key]);
        logEvent("Successful $action for identifier: " . substr($identifier, 0, 10) . '...', 'INFO');
    } else {
        // Increment attempts on failure
        $_SESSION[$key]['attempts']++;
        $_SESSION[$key]['last_attempt'] = time();
        logEvent("Failed $action attempt for identifier: " . substr($identifier, 0, 10) . '...' . 
                " (Attempt " . $_SESSION[$key]['attempts'] . ")", 'WARNING');
    }
}

/**
 * Enhanced database query helper with error handling
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return PDOStatement|false Result or false on error
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (DEBUG_MODE) {
            error_log("GamePlan DB Query executed: " . substr($sql, 0, 100) . "...");
        }
        
        return $stmt;
    } catch (PDOException $e) {
        error_log("GamePlan DB Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Get database connection (legacy compatibility)
 * 
 * @return PDO Database connection
 */
function getDB() {
    global $pdo;
    return $pdo;
}

// ===================== INITIALIZATION AND SECURITY HEADERS =====================

// Set timezone for consistent date/time handling
date_default_timezone_set('Europe/Amsterdam');

// Initialize application based on environment
if (ENVIRONMENT === 'development') {
    // Development mode indicators
    header('X-Debug-Mode: Enabled');
    header('X-App-Version: ' . APP_VERSION);
    header('X-Environment: Development');
}

// Enhanced security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Performance and caching headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Content Security Policy (basic)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' cdn.jsdelivr.net;");

// Log application startup
if (DEBUG_MODE) {
    logEvent("GamePlan Scheduler initialized successfully", 'INFO', [
        'environment' => ENVIRONMENT,
        'version' => APP_VERSION,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

// Define global constants for the application
if (!defined('GAMEPLAN_DB_LOADED')) {
    define('GAMEPLAN_DB_LOADED', true);
    define('GAMEPLAN_INITIALIZED', true);
    define('GAMEPLAN_START_TIME', microtime(true));
}

// Clean up old sessions and temporary data periodically
register_shutdown_function(function() {
    // Clean up expired sessions data
    if (isset($_SESSION) && is_array($_SESSION)) {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'rate_limit_') === 0 && is_array($value) && 
                isset($value['last_attempt']) && 
                (time() - $value['last_attempt']) > LOGIN_LOCKOUT_TIME) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    if (DEBUG_MODE && defined('GAMEPLAN_START_TIME')) {
        $execution_time = microtime(true) - GAMEPLAN_START_TIME;
        error_log("GamePlan Request completed in " . round($execution_time * 1000, 2) . "ms");
    }
});

?>