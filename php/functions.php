<?php
// GamePlan Scheduler - Core Functions
// PDO database connection and operations

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connect to database
function getDbConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// User authentication
function registerUser($username, $email, $password) {
    $pdo = getDbConnection();
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);
    return $pdo->lastInsertId();
}

function loginUser($email, $password) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        updateLastActivity($user['user_id']);
        logActivity($user['user_id'], 'login');
        return true;
    }
    return false;
}

function updateLastActivity($user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

function logActivity($user_id, $action) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
    $stmt->execute([$user_id, $action]);
}

// Profile and games
function getFavoriteGames($user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT g.title, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function addFavoriteGame($user_id, $game_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE game_id = game_id");
    $stmt->execute([$user_id, $game_id]);
}

function getGames() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT game_id, title FROM Games");
    return $stmt->fetchAll();
}

// Friends
function getFriends($user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT u.username, u.last_activity FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function addFriend($user_id, $friend_username) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->execute([$friend_username]);
    $friend = $stmt->fetch();
    if ($friend && $friend['user_id'] != $user_id) {
        $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE friend_user_id = friend_user_id");
        $stmt->execute([$user_id, $friend['user_id']]);
        return true;
    }
    return false;
}

// Schedules
function getSchedules($user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.title AS game_title, s.date, s.time, s.friends FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = ? ORDER BY s.date, s.time");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function addSchedule($user_id, $game_id, $date, $time, $friends) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $game_id, $date, $time, implode(',', $friends)]);
    logActivity($user_id, 'add_schedule');
}

function editSchedule($schedule_id, $user_id, $game_id, $date, $time, $friends) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = ?, date = ?, time = ?, friends = ? WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $date, $time, implode(',', $friends), $schedule_id, $user_id]);
    logActivity($user_id, 'edit_schedule');
}

function deleteSchedule($schedule_id, $user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$schedule_id, $user_id]);
    logActivity($user_id, 'delete_schedule');
}

// Events
function getEvents($user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT e.event_id, e.title, e.date, e.time, e.description, e.reminder, s.title AS schedule_title FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id WHERE e.user_id = ? ORDER BY e.date, e.time");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll();
    foreach ($events as &$event) {
        $stmt = $pdo->prepare("SELECT u.username FROM EventUserMap eum JOIN Users u ON eum.friend_id = u.user_id WHERE eum.event_id = ?");
        $stmt->execute([$event['event_id']]);
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $events;
}

function addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $date, $time, $description, $reminder, $schedule_id]);
    $event_id = $pdo->lastInsertId();
    foreach ($shared_friends as $friend_id) {
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
        $stmt->execute([$event_id, $friend_id]);
    }
    logActivity($user_id, 'add_event');
}

function editEvent($event_id, $user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE Events SET title = ?, date = ?, time = ?, description = ?, reminder = ?, schedule_id = ? WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$title, $date, $time, $description, $reminder, $schedule_id, $event_id, $user_id]);
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->execute([$event_id]);
    foreach ($shared_friends as $friend_id) {
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
        $stmt->execute([$event_id, $friend_id]);
    }
    logActivity($user_id, 'edit_event');
}

function deleteEvent($event_id, $user_id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    logActivity($user_id, 'delete_event');
}

// Validation helpers
function validateInput($input, $type) {
    $input = trim($input);
    if (empty($input)) return false;
    switch ($type) {
        case 'email': return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'date': return strtotime($input) !== false && strtotime($input) >= strtotime('today');
        case 'time': return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input);
        case 'text': return strlen($input) <= 100 && !preg_match('/^\s*$/', $input);
        default: return true;
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>