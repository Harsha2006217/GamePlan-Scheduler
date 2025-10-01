<?php
require 'functions.php';
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

global $pdo;
try {
    $stmt = $pdo->prepare("UPDATE Notifications SET is_read = 1 WHERE user_id = :user_id");
    $success = $stmt->execute(['user_id' => $user_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error marking notifications as read']);
}