<?php
// Database Connection for GamePlan Scheduler
// Created by Harsha Kanaparthi on 02-10-2025
// Secure PDO connection with error handling, utf8mb4 for Unicode, strict mode.

$host = 'localhost';
$dbname = 'gameplan_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Real prepared statements
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage(), 0); // Log error securely
    die('Could not connect to the database. Please try again later.');
}

function getPDO() {
    global $pdo;
    return $pdo;
}
?>