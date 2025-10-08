<?php
// db.php - Database Connection Script
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Establishes a secure PDO connection to the MySQL database with error handling.
// Uses try-catch for robust connection management and sets attributes for prepared statements.
// Database configuration constants for security and modularity
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Replace with actual username in production
define('DB_PASS', '');     // Replace with actual password in production
define('DB_NAME', 'gameplan_db');
define('DB_CHARSET', 'utf8mb4');

// Function to get PDO connection
function getDBConnection() {
    static $pdo = null; // Singleton pattern for efficiency, reuse connection
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Return associative arrays
            PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
            PDO::ATTR_PERSISTENT => true, // Persistent connection for performance
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error securely without exposing details to user
            error_log("Database Connection Failed: " . $e->getMessage(), 0);
            die("Sorry, there was an issue connecting to the database. Please try again later.");
        }
    }
    return $pdo;
}
?>