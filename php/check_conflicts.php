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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token from headers
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

// Validate input
if (!isset($input['gameId'], $input['date'], $input['time']) || 
    !is_numeric($input['gameId']) || 
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date']) ||
    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input['time'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid input']));
}

$game_id = (int)$input['gameId'];
$date = $input['date'];
$time = $input['time'];
$friend_ids = array_filter($input['friendIds'] ?? [], 'is_numeric');

// Check for conflicts
$conflicts = checkScheduleConflicts(
    $_SESSION['user_id'],
    $date,
    $time,
    $friend_ids,
    $game_id
);

// Return conflicts as JSON
header('Content-Type: application/json');
echo json_encode($conflicts);