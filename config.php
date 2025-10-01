<?php
// GamePlan Scheduler - Configuration File

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_scheduler');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'GamePlan Scheduler');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/gameplan'); // Change this to your domain
define('APP_TIMEZONE', 'Europe/Amsterdam');

// Security Configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('BCRYPT_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', 'uploads/');

// Email Configuration (for future features)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'harsha.kanaparthi20062@gmail.com');
define('SMTP_PASS', 'Harsha@2006');
define('SMTP_FROM', 'noreply@gameplanscheduler.com');

// Feature Flags
define('FEATURE_EVENTS', true);
define('FEATURE_FRIENDS', true);
define('FEATURE_NOTIFICATIONS', true);
define('FEATURE_REMINDERS', true);

// Debug Mode (set to false in production)
define('DEBUG_MODE', true);

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'logs/error.log');
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Create necessary directories if they don't exist
$directories = ['uploads', 'logs', 'assets/cache'];
foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

// Application Constants
define('USER_ROLES', [
    'user' => 1,
    'moderator' => 2,
    'admin' => 3
]);

define('EVENT_TYPES', [
    'Tournament' => 'Competitive events with prizes',
    'Practice' => 'Training and skill development',
    'Ranked' => 'Serious matches affecting rankings',
    'Casual' => 'Fun, relaxed gaming sessions'
]);

define('SCHEDULE_RECURRING', [
    'None' => 'One-time session',
    'Daily' => 'Repeat daily',
    'Weekly' => 'Repeat weekly',
    'Monthly' => 'Repeat monthly'
]);

define('SKILL_LEVELS', [
    'Beginner' => 'New to the game',
    'Intermediate' => 'Comfortable with basics',
    'Advanced' => 'Skilled player',
    'Expert' => 'Master level'
]);

// Utility function to get configuration value
function config($key, $default = null) {
    $config = [
        'app_name' => APP_NAME,
        'app_version' => APP_VERSION,
        'app_url' => APP_URL,
        'debug_mode' => DEBUG_MODE,
        'upload_path' => UPLOAD_PATH,
        'max_file_size' => MAX_FILE_SIZE
    ];
    
    return $config[$key] ?? $default;
}

// Check if installation is required
function isInstallationRequired() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        return false;
    } catch (Exception $e) {
        return true;
    }
}

// Redirect to installation if needed
if (isInstallationRequired() && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit();
}
?>