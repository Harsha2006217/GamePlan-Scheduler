<?php
require 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// Validate CSRF token
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

// Get search query
$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    exit(json_encode([]));
}

$user_id = $_SESSION['user_id'];

try {
    global $pdo;
    
    // Search users by username, game interests, or activity
    $sql = "
        SELECT DISTINCT 
            u.user_id,
            u.username,
            COUNT(DISTINCT ug.game_id) as common_games
        FROM Users u
        LEFT JOIN UserGames ug ON u.user_id = ug.user_id
        LEFT JOIN UserGames myGames ON myGames.user_id = :user_id
            AND myGames.game_id = ug.game_id
        WHERE u.user_id != :user_id
        AND u.user_id NOT IN (
            SELECT friend_id FROM Friends WHERE user_id = :user_id
            UNION
            SELECT user_id FROM Friends WHERE friend_id = :user_id
        )
        AND (
            u.username LIKE :search
            OR EXISTS (
                SELECT 1 FROM UserGames ug2
                JOIN Games g ON ug2.game_id = g.game_id
                WHERE ug2.user_id = u.user_id
                AND (g.titel LIKE :search OR g.genre LIKE :search)
            )
        )
        GROUP BY u.user_id, u.username
        ORDER BY common_games DESC, u.username
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $search_param = "%{$query}%";
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($results);

} catch (Exception $e) {
    error_log("User search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error searching users']);
}