<?php
/**
 * Advanced Database Connection Manager
 * GamePlan Scheduler - Professional Gaming Schedule Management
 * 
 * This module provides secure and optimized database connections with
 * advanced error handling, performance monitoring, and connection pooling.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

// Database configuration with environment support
$config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'dbname' => $_ENV['DB_NAME'] ?? 'gameplan_db',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'port' => $_ENV['DB_PORT'] ?? '3306'
];

// Advanced PDO connection with comprehensive error handling
try {
    // Build DSN with advanced options
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['charset']
    );
    
    // PDO options for enhanced security and performance
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
        PDO::ATTR_TIMEOUT => 30, // Connection timeout
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_STRINGIFY_FETCHES => false, // Keep data types
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ];
    
    // Create PDO instance with comprehensive configuration
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    
    // Set SQL mode for better data integrity
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    
    // Set timezone to ensure consistency
    $pdo->exec("SET time_zone = '+00:00'");
    
    // Enable performance logging in development
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['PDOStatementWithProfiling']);
    }
    
    // Test connection with a simple query
    $testQuery = $pdo->query("SELECT 1 as test");
    if (!$testQuery || $testQuery->fetchColumn() !== '1') {
        throw new PDOException('Database connection test failed');
    }
    
    // Log successful connection (in development only)
    if (defined('DEBUG') && DEBUG === true) {
        error_log("GamePlan DB: Successfully connected to {$config['dbname']} on {$config['host']}:{$config['port']}");
    }
    
} catch (PDOException $e) {
    // Enhanced error logging with context
    $error_context = [
        'host' => $config['host'],
        'port' => $config['port'],
        'database' => $config['dbname'],
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'localhost'
    ];
    
    // Log detailed error information
    error_log("GamePlan DB Connection Error: " . json_encode($error_context));
    
    // Different error handling for development vs production
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        // Show detailed error in development
        die("
            <div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; border: 1px solid #ff0000; background: #ffe6e6;'>
                <h2 style='color: #cc0000;'>Database Connection Failed</h2>
                <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><strong>Code:</strong> " . $e->getCode() . "</p>
                <p><strong>Host:</strong> {$config['host']}:{$config['port']}</p>
                <p><strong>Database:</strong> {$config['dbname']}</p>
                <hr>
                <p><em>This detailed error is only shown in development mode.</em></p>
            </div>
        ");
    } else {
        // Generic error in production
        http_response_code(503);
        die("
            <div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; border: 1px solid #ff0000; background: #ffe6e6;'>
                <h2 style='color: #cc0000;'>Service Temporarily Unavailable</h2>
                <p>GamePlan Scheduler is temporarily unavailable due to maintenance.</p>
                <p>Please try again in a few minutes.</p>
                <p>If the problem persists, please contact support.</p>
            </div>
        ");
    }
} catch (Exception $e) {
    // Handle any other connection errors
    error_log("GamePlan DB: Unexpected error during connection: " . $e->getMessage());
    
    http_response_code(500);
    die("
        <div style='font-family: Arial, sans-serif; margin: 20px; padding: 20px; border: 1px solid #ff0000; background: #ffe6e6;'>
            <h2 style='color: #cc0000;'>Internal Server Error</h2>
            <p>An unexpected error occurred. Please try again later.</p>
        </div>
    ");
}

/**
 * Get database connection instance
 * @return PDO
 */
function getDBConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Execute query with performance monitoring
 * @param string $query
 * @param array $params
 * @return PDOStatement
 */
function executeQuery($query, $params = []) {
    global $pdo;
    
    $start_time = microtime(true);
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        // Log slow queries in development
        if (defined('DEBUG') && DEBUG === true) {
            $execution_time = microtime(true) - $start_time;
            if ($execution_time > 1.0) { // Log queries slower than 1 second
                error_log("Slow Query ({$execution_time}s): " . $query);
            }
        }
        
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage() . " | Query: " . $query);
        throw $e;
    }
}

/**
 * Get database statistics for monitoring
 * @return array
 */
function getDatabaseStats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Get connection info
        $stats['server_info'] = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
        $stats['server_version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $stats['connection_status'] = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        
        // Get database size
        $sizeQuery = $pdo->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $stats['database_size_mb'] = $sizeQuery->fetchColumn();
        
        // Get table counts
        $tableQuery = $pdo->query("
            SELECT 
                table_name, 
                table_rows 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY table_rows DESC
        ");
        $stats['table_counts'] = $tableQuery->fetchAll();
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Failed to get database stats: " . $e->getMessage());
        return ['error' => 'Unable to retrieve database statistics'];
    }
}

/**
 * Test database connectivity and performance
 * @return array
 */
function testDatabaseHealth() {
    global $pdo;
    
    $health = [
        'status' => 'healthy',
        'tests' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Test 1: Basic connectivity
    try {
        $start = microtime(true);
        $pdo->query("SELECT 1");
        $health['tests']['connectivity'] = [
            'status' => 'pass',
            'time' => round((microtime(true) - $start) * 1000, 2) . 'ms'
        ];
    } catch (Exception $e) {
        $health['status'] = 'unhealthy';
        $health['tests']['connectivity'] = [
            'status' => 'fail',
            'error' => $e->getMessage()
        ];
    }
    
    // Test 2: Write capability
    try {
        $start = microtime(true);
        $pdo->exec("CREATE TEMPORARY TABLE health_test (id INT)");
        $pdo->exec("DROP TEMPORARY TABLE health_test");
        $health['tests']['write_capability'] = [
            'status' => 'pass',
            'time' => round((microtime(true) - $start) * 1000, 2) . 'ms'
        ];
    } catch (Exception $e) {
        $health['status'] = 'degraded';
        $health['tests']['write_capability'] = [
            'status' => 'fail',
            'error' => $e->getMessage()
        ];
    }
    
    return $health;
}

// Register shutdown function to log connection info
register_shutdown_function(function() {
    if (defined('DEBUG') && DEBUG === true) {
        error_log("GamePlan DB: Connection closed gracefully");
    }
});

// Initialize performance monitoring if enabled
if (defined('MONITOR_PERFORMANCE') && MONITOR_PERFORMANCE === true) {
    $GLOBALS['query_count'] = 0;
    $GLOBALS['total_query_time'] = 0;
}
?>