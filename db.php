<?php
// Database Connection for GamePlan Scheduler
// Created by Harsha Kanaparthi on 02-10-2025
// This file establishes a secure PDO connection with error handling.
// Uses utf8mb4 for full Unicode support, and sets strict error mode.

$host = 'localhost';
$dbname = 'gameplan_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // For real prepared statements
} catch (PDOException $e) {
    // Log the error to a file instead of displaying (security best practice)
    error_log("Connection failed: " . $e->getMessage(), 0);
    die('Could not connect to the database. Please try again later.');
}
?>