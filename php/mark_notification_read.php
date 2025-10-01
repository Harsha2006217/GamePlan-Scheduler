<?php
require 'functions.php';
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['notification_id'] ?? 0;

header('Content-Type: application/json');
if (markNotificationRead($notification_id, $user_id)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error marking notification as read']);
}