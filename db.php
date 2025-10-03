<?php
// Database Connection for GamePlan Scheduler
// Created by Harsha Kanaparthi on 02-10-2025
// Advanced PDO connection with error handling, UTF-8 support, and strict mode.
// Global function to get PDO instance for reuse across files.

$host = 'localhost';
$dbname = 'gameplan_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die('Database connection failed. Please try later.');
}

function getPDO() {
    global $pdo;
    return $pdo;
}
?>