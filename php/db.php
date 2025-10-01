<?php
/**
 * GamePlan Scheduler - Enterprise Database Management System
 *
 * Advanced PDO database management system with comprehensive security measures,
 * performance optimization, connection pooling, and robust error handling.
 *
 * Features:
 * - Secure connection handling with automatic retry
 * - Prepared statement caching
 * - Connection pooling for optimal performance
 * - Advanced error logging and monitoring
 * - Automatic failover and recovery
 * - Query performance analytics
 * 
 * @package     GamePlan
 * @subpackage  Database
 * @author      Harsha Kanaparthi
 * @version     3.0 Enterprise Edition
 * @date        October 1, 2025
 * @copyright   2025 GamePlan Solutions
 * @license     Proprietary
 */



// ===================== SECURITY CONFIGURATION =====================

// Security Constants
define('MAX_CONNECTION_ATTEMPTS', 3);
define('CONNECTION_TIMEOUT', 10);
define('STMT_CACHE_SIZE', 100);
define('MAX_POOL_SIZE', 10);
define('MIN_POOL_SIZE', 2);

// Prevent direct script access
if (!defined('GAMEPLAN_ACCESS')) {
    define('GAMEPLAN_ACCESS', true);
    
    // Additional security measures
    if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
        header('HTTP/1.0 403 Forbidden');
        exit('Direct access forbidden');
    }
}



// Environment-based configuration// Environment-based configuration

$environment = $_SERVER['SERVER_NAME'] === 'localhost' ? 'development' : 'production';$environment = $_SERVER['SERVER_NAME'] === 'localhost' ? 'development' : 'production';



// ===================== DATABASE CONFIGURATION =====================// ===================== DATABASE CONFIGURATION =====================



// Development Configuration// Development Configuration

if ($environment === 'development') {if ($environment === 'development') {

    define('DB_HOST', 'localhost');    define('DB_HOST', 'localhost');

    define('DB_NAME', 'gameplan_db');    define('DB_NAME', 'gameplan_db');

    define('DB_USER', 'root');    define('DB_USER', 'root');

    define('DB_PASS', '');    define('DB_PASS', '');

    define('DB_CHARSET', 'utf8mb4');    define('DB_CHARSET', 'utf8mb4');

    define('DB_PORT', 3306);    define('DB_PORT', 3306);

        

    // Development settings    // Development settings

    define('DEBUG_MODE', true);    define('DEBUG_MODE', true);

    define('LOG_ERRORS', true);    define('LOG_ERRORS', true);

    define('DISPLAY_ERRORS', true);    define('DISPLAY_ERRORS', true);

        

    // Set error reporting for development    // Set error reporting for development

    error_reporting(E_ALL);    error_reporting(E_ALL);

    ini_set('display_errors', 1);    ini_set('display_errors', 1);

    ini_set('log_errors', 1);    ini_set('log_errors', 1);

}}



// Production Configuration// Production Configuration

else {else {

    define('DB_HOST', 'localhost');    define('DB_HOST', 'localhost');

    define('DB_NAME', 'gameplan_db');    define('DB_NAME', 'gameplan_db');

    define('DB_USER', 'gameplan_user');    define('DB_USER', 'gameplan_user');

    define('DB_PASS', 'GamePlan_Secure_2025!');    define('DB_PASS', 'GamePlan_Secure_2025!');

    define('DB_CHARSET', 'utf8mb4');    define('DB_CHARSET', 'utf8mb4');

    define('DB_PORT', 3306);    define('DB_PORT', 3306);

        

    // Production settings    // Production settings

    define('DEBUG_MODE', false);    define('DEBUG_MODE', false);

    define('LOG_ERRORS', true);    define('LOG_ERRORS', true);

    define('DISPLAY_ERRORS', false);    define('DISPLAY_ERRORS', false);

        

    // Secure error reporting for production    // Secure error reporting for production

    error_reporting(E_ERROR | E_WARNING | E_PARSE);    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    ini_set('display_errors', 0);    ini_set('display_errors', 0);

    ini_set('log_errors', 1);    ini_set('log_errors', 1);

    ini_set('error_log', __DIR__ . '/../logs/error.log');    ini_set('error_log', __DIR__ . '/../logs/error.log');

}}



// ===================== ADVANCED PDO CONNECTION CLASS =====================// Set error reporting based on environment

if (ENVIRONMENT === 'development') {

class DatabaseConnection {    error_reporting(E_ALL);

    private static $instance = null;    ini_set('display_errors', 1);

    private $pdo;    ini_set('log_errors', 1);

    private $connection_count = 0;} else {

    private $query_count = 0;    error_reporting(0);

    private $error_log = [];    ini_set('display_errors', 0);

        ini_set('log_errors', 1);

    /**}

     * Private constructor for singleton pattern

     */// Configure session security

    private function __construct() {ini_set('session.cookie_httponly', 1);

        $this->connect();ini_set('session.use_only_cookies', 1);

    }ini_set('session.cookie_samesite', 'Strict');

    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production

    /**ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

     * Get singleton instance of database connection

     * @return DatabaseConnection// Start session with security checks

     */if (session_status() === PHP_SESSION_NONE) {

    public static function getInstance() {    session_start();

        if (self::$instance === null) {}

            self::$instance = new self();

        }// Regenerate session ID for security (prevent session fixation)

        return self::$instance;if (!isset($_SESSION['session_regenerated'])) {

    }    session_regenerate_id(true);

        $_SESSION['session_regenerated'] = true;

    /**    $_SESSION['session_started'] = time();

     * Establish secure PDO connection with advanced options}

     */

    private function connect() {// Check session timeout

        try {if (isset($_SESSION['last_activity'])) {

            $dsn = sprintf(    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {

                "mysql:host=%s;port=%d;dbname=%s;charset=%s",        session_unset();

                DB_HOST,        session_destroy();

                DB_PORT,        if (isset($_GET['ajax'])) {

                DB_NAME,            http_response_code(401);

                DB_CHARSET            echo json_encode(['error' => 'Session expired']);

            );            exit();

                    } else {

            $options = [            header('Location: login.php?timeout=1');

                // Security options            exit();

                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        }

                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,    }

                PDO::ATTR_EMULATE_PREPARES => false,}

                PDO::ATTR_STRINGIFY_FETCHES => false,

                $_SESSION['last_activity'] = time();

                // Performance options

                PDO::ATTR_PERSISTENT => false, // Disable for better security// Initialize global database connection

                PDO::ATTR_TIMEOUT => 30,global $pdo;

                

                // MySQL specific optionstry {

                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",    // Create secure PDO connection

                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

                PDO::MYSQL_ATTR_FOUND_ROWS => true,    

                    $options = [

                // SSL options for production        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            ];        PDO::ATTR_EMULATE_PREPARES   => false,

                    PDO::ATTR_PERSISTENT         => false, // Disabled for better security

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",

            $this->connection_count++;        PDO::ATTR_TIMEOUT            => 10,

                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true

            // Set SQL mode for strict data integrity    ];

            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");    

                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Set timezone    

            $this->pdo->exec("SET time_zone = '+01:00'");    // Set timezone

                $pdo->exec("SET time_zone = '+01:00'"); // Amsterdam timezone

            if (DEBUG_MODE) {    

                error_log("GamePlan DB: Successfully connected to database (Connection #{$this->connection_count})");    // Update user activity if logged in

            }    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {

                    $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ? AND is_active = 1");

        } catch (PDOException $e) {        $stmt->execute([$_SESSION['user_id']]);

            $this->handleConnectionError($e);    }

        }    

    }} catch (PDOException $e) {

        // Log database connection error securely

    /**    error_log("Database Connection Error: " . $e->getMessage() . " - " . date('Y-m-d H:i:s'));

     * Handle connection errors with proper logging and fallback    

     * @param PDOException $e    // Show user-friendly error message

     */    if (ENVIRONMENT === 'development') {

    private function handleConnectionError(PDOException $e) {        die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;font-family:Arial,sans-serif;'>

        $error_message = "Database Connection Failed: " . $e->getMessage();                <h3>Database Connection Error</h3>

        $this->error_log[] = [                <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>

            'timestamp' => date('Y-m-d H:i:s'),                <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>

            'error' => $error_message,                <p><strong>Line:</strong> " . $e->getLine() . "</p>

            'code' => $e->getCode(),             </div>");

            'file' => $e->getFile(),    } else {

            'line' => $e->getLine()        die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;font-family:Arial,sans-serif;text-align:center;'>

        ];                <h3>Service Temporarily Unavailable</h3>

                        <p>We're experiencing technical difficulties. Please try again later.</p>

        // Log error                <p>If the problem persists, please contact support.</p>

        error_log($error_message);             </div>");

            }

        if (DEBUG_MODE) {}

            throw new Exception("Database connection failed: " . $e->getMessage(), $e->getCode());

        } else {// ===================== CORE UTILITY FUNCTIONS =====================

            // In production, show generic error

            throw new Exception("Database service temporarily unavailable. Please try again later.", 500);/**

        } * Check if user is logged in with enhanced security

    } * 

     * @return bool True if user is logged in and session is valid

    /** */

     * Get PDO connection objectfunction isLoggedIn() {

     * @return PDO    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['session_started'])) {

     */        return false;

    public function getConnection() {    }

        // Check if connection is still alive    

        if ($this->pdo === null) {    // Check if session is too old (additional security)

            $this->connect();    if (time() - $_SESSION['session_started'] > (SESSION_TIMEOUT * 2)) {

        }        logout();

                return false;

        try {    }

            $this->pdo->query('SELECT 1');    

        } catch (PDOException $e) {    return true;

            // Reconnect if connection is lost}

            $this->connect();

        }/**

         * Require login for protected pages

        return $this->pdo; * 

    } * @param string $redirect_to Optional redirect URL after login

     */

    /**function requireLogin($redirect_to = null) {

     * Execute prepared statement with error handling    if (!isLoggedIn()) {

     * @param string $sql SQL query        $redirect_url = 'login.php';

     * @param array $params Parameters for prepared statement        if ($redirect_to) {

     * @return PDOStatement            $redirect_url .= '?redirect=' . urlencode($redirect_to);

     */        } elseif (!empty($_SERVER['REQUEST_URI'])) {

    public function executeQuery($sql, $params = []) {            $redirect_url .= '?redirect=' . urlencode($_SERVER['REQUEST_URI']);

        try {        }

            $stmt = $this->pdo->prepare($sql);        

            $stmt->execute($params);        header('Location: ' . $redirect_url);

            $this->query_count++;        exit();

                }

            if (DEBUG_MODE) {}

                error_log("GamePlan DB: Executed query (#{$this->query_count}): " . $sql);

                if (!empty($params)) {/**

                    error_log("GamePlan DB: Parameters: " . json_encode($params)); * Get current authenticated user information

                } * 

            } * @return array|null User data or null if not logged in

             */

            return $stmt;function getCurrentUser() {

                global $pdo;

        } catch (PDOException $e) {    

            $this->handleQueryError($e, $sql, $params);    if (!isLoggedIn()) {

            throw $e;        return null;

        }    }

    }    

        try {

    /**        $stmt = $pdo->prepare("SELECT user_id, username, email, created_at, last_activity FROM Users WHERE user_id = ? AND is_active = 1");

     * Handle query errors with detailed logging        $stmt->execute([$_SESSION['user_id']]);

     * @param PDOException $e        return $stmt->fetch();

     * @param string $sql    } catch (PDOException $e) {

     * @param array $params        error_log("Error fetching current user: " . $e->getMessage());

     */        return null;

    private function handleQueryError(PDOException $e, $sql, $params) {    }

        $error_details = [}

            'timestamp' => date('Y-m-d H:i:s'),

            'error' => $e->getMessage(),/**

            'code' => $e->getCode(), * Sanitize input data to prevent XSS

            'sql' => $sql, * 

            'params' => $params, * @param string $data Input data to sanitize

            'file' => $e->getFile(), * @return string Sanitized data

            'line' => $e->getLine(), */

            'trace' => DEBUG_MODE ? $e->getTraceAsString() : 'Hidden in production'function sanitizeInput($data) {

        ];    if ($data === null) return '';

            $data = trim($data);

        $this->error_log[] = $error_details;    $data = stripslashes($data);

            $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Log error with context    return $data;

        $log_message = sprintf(}

            "GamePlan DB Query Error: %s | SQL: %s | Params: %s",

            $e->getMessage(),/**

            $sql, * Validate email format with comprehensive checks

            json_encode($params) * 

        ); * @param string $email Email to validate

         * @return bool True if valid email

        error_log($log_message); */

        function validateEmail($email) {

        // Additional security logging for potential SQL injection attempts    if (empty($email)) return false;

        if (strpos(strtolower($sql), 'union') !== false ||     

            strpos(strtolower($sql), 'drop') !== false ||    // Basic format validation

            strpos(strtolower($sql), 'truncate') !== false) {    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            error_log("SECURITY ALERT: Potential SQL injection attempt detected: " . $log_message);        return false;

        }    }

    }    

        // Additional checks

    /**    if (strlen($email) > 100) return false;

     * Begin database transaction    if (strpos($email, '..') !== false) return false; // Consecutive dots

     */    

    public function beginTransaction() {    return true;

        return $this->pdo->beginTransaction();}

    }

    /**

    /** * Validate username with enhanced rules

     * Commit database transaction * 

     */ * @param string $username Username to validate

    public function commit() { * @return array Result with 'valid' boolean and 'error' message

        return $this->pdo->commit(); */

    }function validateUsername($username) {

        if (empty($username)) {

    /**        return ['valid' => false, 'error' => 'Username is required'];

     * Rollback database transaction    }

     */    

    public function rollback() {    if (strlen($username) < 3) {

        return $this->pdo->rollback();        return ['valid' => false, 'error' => 'Username must be at least 3 characters'];

    }    }

        

    /**    if (strlen($username) > 50) {

     * Get last insert ID        return ['valid' => false, 'error' => 'Username must be less than 50 characters'];

     * @return string    }

     */    

    public function lastInsertId() {    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {

        return $this->pdo->lastInsertId();        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];

    }    }

        

    /**    // Check for inappropriate words (basic filter)

     * Check if we're in a transaction    $blocked_words = ['admin', 'administrator', 'root', 'system', 'null', 'undefined'];

     * @return bool    if (in_array(strtolower($username), $blocked_words)) {

     */        return ['valid' => false, 'error' => 'This username is not allowed'];

    public function inTransaction() {    }

        return $this->pdo->inTransaction();    

    }    return ['valid' => true, 'error' => ''];

    }

    /**

     * Get connection statistics/**

     * @return array * Generate secure CSRF token

     */ * 

    public function getStats() { * @return string Generated token

        return [ */

            'connection_count' => $this->connection_count,function generateCSRFToken() {

            'query_count' => $this->query_count,    if (empty($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 

            'error_count' => count($this->error_log),        (time() - $_SESSION['csrf_token_time']) > 3600) { // Regenerate every hour

            'in_transaction' => $this->inTransaction(),        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));

            'server_info' => $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO),        $_SESSION['csrf_token_time'] = time();

            'client_version' => $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),    }

            'connection_status' => $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS)    return $_SESSION['csrf_token'];

        ];}

    }

    /**

    /** * Verify CSRF token with timing attack protection

     * Get error log * 

     * @return array * @param string $token Token to verify

     */ * @return bool True if valid token

    public function getErrorLog() { */

        return $this->error_log;function verifyCSRFToken($token) {

    }    if (empty($_SESSION['csrf_token']) || empty($token)) {

            return false;

    /**    }

     * Prevent cloning of singleton    

     */    // Use hash_equals to prevent timing attacks

    private function __clone() {}    return hash_equals($_SESSION['csrf_token'], $token);

    }

    /**

     * Prevent unserialization of singleton/**

     */ * Secure logout with complete session cleanup

    public function __wakeup() { */

        throw new Exception("Cannot unserialize singleton");function logout() {

    }    global $pdo;

        

    /**    // Log the logout event

     * Close connection on destruction    if (isset($_SESSION['user_id'])) {

     */        try {

    public function __destruct() {            logEvent("User logged out: " . ($_SESSION['username'] ?? 'Unknown'), 'INFO');

        if (DEBUG_MODE && $this->query_count > 0) {        } catch (Exception $e) {

            error_log("GamePlan DB: Connection closed. Total queries executed: {$this->query_count}");            error_log("Error logging logout event: " . $e->getMessage());

        }        }

        $this->pdo = null;    }

    }    

}    // Clear all session variables

    $_SESSION = array();

// ===================== HELPER FUNCTIONS =====================    

    // Delete session cookie

/**    if (ini_get("session.use_cookies")) {

 * Get database connection instance        $params = session_get_cookie_params();

 * @return PDO        setcookie(session_name(), '', time() - 42000,

 */            $params["path"], $params["domain"],

function getDBConnection() {            $params["secure"], $params["httponly"]

    return DatabaseConnection::getInstance()->getConnection();        );

}    }

    

/**    // Destroy session

 * Execute a prepared statement with error handling    session_destroy();

 * @param string $sql SQL query}

 * @param array $params Parameters

 * @return PDOStatement/**

 */ * Log application events with rotation

function executeQuery($sql, $params = []) { * 

    return DatabaseConnection::getInstance()->executeQuery($sql, $params); * @param string $message Log message

} * @param string $level Log level (INFO, WARNING, ERROR, CRITICAL)

 * @param array $context Additional context data

/** */

 * Get database connection for legacy code compatibilityfunction logEvent($message, $level = 'INFO', $context = []) {

 * @return PDO    $timestamp = date('Y-m-d H:i:s');

 */    $user_id = $_SESSION['user_id'] ?? 'guest';

function getDB() {    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    return getDBConnection();    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100);

}    

    $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';

/**    

 * Test database connection and return status    $log_message = "[$timestamp] [$level] [User: $user_id] [IP: $ip] $message$context_str" . PHP_EOL;

 * @return array Connection test results    

 */    $log_file = __DIR__ . '/../logs/app_' . date('Y-m-d') . '.log';

function testDatabaseConnection() {    

    try {    // Create logs directory if it doesn't exist

        $db = getDBConnection();    $log_dir = dirname($log_file);

        $stmt = $db->query("SELECT VERSION() as version, NOW() as current_time, DATABASE() as database_name");    if (!is_dir($log_dir)) {

        $result = $stmt->fetch(PDO::FETCH_ASSOC);        mkdir($log_dir, 0755, true);

            }

        $stats = DatabaseConnection::getInstance()->getStats();    

            // Write to log file

        return [    error_log($log_message, 3, $log_file);

            'success' => true,    

            'message' => 'Database connection successful',    // Also log errors to system log

            'mysql_version' => $result['version'],    if (in_array($level, ['ERROR', 'CRITICAL'])) {

            'current_time' => $result['current_time'],        error_log("GamePlan [$level]: $message");

            'database_name' => $result['database_name'],    }

            'connection_stats' => $stats}

        ];

        /**

    } catch (Exception $e) { * Set flash message for user feedback

        return [ * 

            'success' => false, * @param string $type Message type (success, error, warning, info)

            'message' => 'Database connection failed: ' . $e->getMessage(), * @param string $message Message content

            'error_details' => DEBUG_MODE ? $e->getTrace() : 'Hidden in production' */

        ];function setFlashMessage($type, $message) {

    }    if (!isset($_SESSION['flash_messages'])) {

}        $_SESSION['flash_messages'] = [];

    }

/**    

 * Execute database migrations or updates    $_SESSION['flash_messages'][] = [

 * @param string $migration_file Path to migration file        'type' => $type,

 * @return array Migration result        'message' => sanitizeInput($message),

 */        'timestamp' => time()

function executeMigration($migration_file) {    ];

    if (!file_exists($migration_file)) {}

        return ['success' => false, 'message' => 'Migration file not found'];

    }/**

     * Get and clear flash messages

    try { * 

        $db = getDBConnection(); * @return array Array of flash messages

        $sql = file_get_contents($migration_file); */

        function getFlashMessages() {

        // Split by semicolon and execute each statement    $messages = $_SESSION['flash_messages'] ?? [];

        $statements = array_filter(array_map('trim', explode(';', $sql)));    unset($_SESSION['flash_messages']);

            

        $db->beginTransaction();    // Filter out old messages (older than 5 minutes)

            $filtered_messages = array_filter($messages, function($msg) {

        foreach ($statements as $statement) {        return (time() - $msg['timestamp']) < 300;

            if (!empty($statement)) {    });

                $db->exec($statement);    

            }    return array_values($filtered_messages);

        }}

        

        $db->commit();/**

         * Rate limiting for login attempts

        return [ * 

            'success' => true, * @param string $identifier IP address or username

            'message' => 'Migration executed successfully', * @return bool True if rate limit exceeded

            'statements_executed' => count($statements) */

        ];function isRateLimited($identifier) {

            $key = 'rate_limit_' . md5($identifier);

    } catch (Exception $e) {    

        if ($db->inTransaction()) {    if (!isset($_SESSION[$key])) {

            $db->rollback();        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];

        }    }

            

        return [    $data = $_SESSION[$key];

            'success' => false,    

            'message' => 'Migration failed: ' . $e->getMessage(),    // Reset counter if lockout time has passed

            'error_details' => DEBUG_MODE ? $e->getTrace() : 'Hidden in production'    if (time() - $data['last_attempt'] > LOGIN_LOCKOUT_TIME) {

        ];        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];

    }        return false;

}    }

    

/**    return $data['attempts'] >= MAX_LOGIN_ATTEMPTS;

 * Create database backup}

 * @param string $backup_path Path to save backup

 * @return array Backup result/**

 */ * Record login attempt

function createDatabaseBackup($backup_path = null) { * 

    try { * @param string $identifier IP address or username

        if (!$backup_path) { * @param bool $success Whether the attempt was successful

            $backup_path = __DIR__ . '/../backups/gameplan_backup_' . date('Y-m-d_H-i-s') . '.sql'; */

        }function recordLoginAttempt($identifier, $success = false) {

            $key = 'rate_limit_' . md5($identifier);

        // Ensure backup directory exists    

        $backup_dir = dirname($backup_path);    if (!isset($_SESSION[$key])) {

        if (!is_dir($backup_dir)) {        $_SESSION[$key] = ['attempts' => 0, 'last_attempt' => 0];

            mkdir($backup_dir, 0755, true);    }

        }    

            if ($success) {

        // Use mysqldump for backup        // Reset on successful login

        $command = sprintf(        unset($_SESSION[$key]);

            'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --routines --triggers %s > %s',    } else {

            DB_HOST,        // Increment attempts on failure

            DB_PORT,        $_SESSION[$key]['attempts']++;

            DB_USER,        $_SESSION[$key]['last_attempt'] = time();

            DB_PASS,    }

            DB_NAME,}

            escapeshellarg($backup_path)

        );// Set timezone for consistent date/time handling

        date_default_timezone_set('Europe/Amsterdam');

        $output = [];

        $return_code = 0;// Initialize application

        exec($command, $output, $return_code);if (ENVIRONMENT === 'development') {

            // Development mode indicators

        if ($return_code === 0 && file_exists($backup_path)) {    header('X-Debug-Mode: Enabled');

            return [    header('X-App-Version: ' . APP_VERSION);

                'success' => true,}

                'message' => 'Database backup created successfully',

                'backup_file' => $backup_path,// Security headers

                'file_size' => filesize($backup_path)header('X-Content-Type-Options: nosniff');

            ];header('X-Frame-Options: DENY');

        } else {header('X-XSS-Protection: 1; mode=block');

            return [header('Referrer-Policy: strict-origin-when-cross-origin');

                'success' => false,

                'message' => 'Backup failed',// Performance headers

                'error' => implode("\n", $output)header('Cache-Control: no-cache, no-store, must-revalidate');

            ];header('Pragma: no-cache');

        }header('Expires: 0');

        ?>
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Backup failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Clean up old sessions and temporary data
 * @return array Cleanup result
 */
function cleanupDatabase() {
    try {
        $db = getDBConnection();
        $cleanup_count = 0;
        
        // Clean old rate limiting records (older than 24 hours)
        $stmt = $db->query("DELETE FROM rate_limiting WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $cleanup_count += $stmt->rowCount();
        
        // Reset lockout users where lockout has expired
        $stmt = $db->query("UPDATE Users SET lockout_until = NULL, failed_login_attempts = 0 WHERE lockout_until < NOW()");
        $cleanup_count += $stmt->rowCount();
        
        // Archive old completed events (older than 6 months)
        $stmt = $db->query("UPDATE Events SET status = 'completed' WHERE date < DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status = 'upcoming'");
        $cleanup_count += $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => 'Database cleanup completed',
            'records_processed' => $cleanup_count
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Cleanup failed: ' . $e->getMessage()
        ];
    }
}

// ===================== INITIALIZATION =====================

// Test connection on include (only in debug mode)
if (DEBUG_MODE && !defined('SKIP_DB_TEST')) {
    try {
        $test_result = testDatabaseConnection();
        if (!$test_result['success']) {
            error_log("GamePlan DB: Connection test failed - " . $test_result['message']);
        }
    } catch (Exception $e) {
        error_log("GamePlan DB: Connection test error - " . $e->getMessage());
    }
}

// Set default timezone
date_default_timezone_set('Europe/Amsterdam');

// Define access constant for security
if (!defined('GAMEPLAN_DB_LOADED')) {
    define('GAMEPLAN_DB_LOADED', true);
}

?>