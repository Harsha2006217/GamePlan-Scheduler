<?php
// Secure Database Connection for GamePlan Scheduler
// Author: Harsha Kanaparthi - 02-10-2025
// Establishes PDO connection with error handling and UTF-8 support.
// Use in production with environment variables for credentials.

$host = 'localhost';
$dbname = 'gameplan_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // True prepared statements for security
} catch (PDOException $e) {
    // Log error securely, show user-friendly message
    error_log("DB Connection failed: " . $e->getMessage(), 0);
    die('Database connection error. Please try later or contact support.');
}

function getPDO() {
    global $pdo;
    return $pdo;
}
?>