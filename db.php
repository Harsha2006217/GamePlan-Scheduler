<?php
// db.php: PDO connection singleton for security and efficiency
// Configurable for production (use env vars in real apps)
// Human-written: Clear try-catch, options for error handling

function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';  // Use '127.0.0.1' for stricter access
        $dbname = 'gameplan_scheduler';
        $user = 'root';       // Change to secure user in production
        $pass = '';           // Change to secure password in production
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Return associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,                  // Use real prepared statements
            PDO::ATTR_STRINGIFY_FETCHES => false,                 // Prevent string conversion
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log error in production, show generic message
            error_log('Database connection failed: ' . $e->getMessage());
            die('Unable to connect to the database. Please try again later.');
        }
    }
    return $pdo;
}