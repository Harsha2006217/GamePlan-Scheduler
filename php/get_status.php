<?php
require 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Validate CSRF token
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$user_id = $_SESSION['user_id'];

// Update user's last activity
updateLastActivity($user_id);

// Get current user's status
$my_status = getUserStatus($user_id);

// Get friends' statuses
$friends_status = getFriendsStatus($user_id);

// Return combined status information
echo json_encode([
    'my_status' => $my_status,
    'friends_status' => $friends_status
]);