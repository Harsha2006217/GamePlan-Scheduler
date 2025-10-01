<?php
require 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Validate CSRF token
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['status_type']) || 
    !in_array($input['status_type'], ['online', 'playing', 'break', 'looking', 'offline'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid status type']));
}

$user_id = $_SESSION['user_id'];
$status_type = $input['status_type'];
$game_id = null;
$status_message = null;

// Validate game_id if status is 'playing'
if ($status_type === 'playing') {
    if (!isset($input['game_id']) || !is_numeric($input['game_id'])) {
        http_response_code(400);
        exit(json_encode(['error' => 'Game ID required for playing status']));
    }
    $game_id = (int)$input['game_id'];
}

// Get optional status message
if (isset($input['status_message'])) {
    $status_message = trim($input['status_message']);
    if (strlen($status_message) > 255) {
        $status_message = substr($status_message, 0, 255);
    }
}

// Update status
if (updateUserStatus($user_id, $status_type, $game_id, $status_message)) {
    // Get updated status
    $status = getUserStatus($user_id);
    
    // If status changed to 'playing', notify friends
    if ($status_type === 'playing') {
        $game = getGameById($game_id);
        $user = getUserById($user_id);
        
        // Get online friends
        $friends = getFriendsStatus($user_id);
        foreach ($friends as $friend) {
            if ($friend['status_type'] !== 'offline') {
                createNotification(
                    $friend['user_id'],
                    'Gaming Activity',
                    "{$user['username']} is nu {$game['titel']} aan het spelen!",
                    'friend_playing',
                    $game_id
                );
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'status' => $status
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Could not update status']);
}