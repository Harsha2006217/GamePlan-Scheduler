<?php
// Core functions for GamePlan Scheduler

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connect to database
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $db;
}

// User functions
function registerUser($username, $email, $password) {
    $db = getDB();
    $hash = password_hash($password, PASSWORD_ARGON2ID, ['cost' => 12]);
    $stmt = $db->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bindParam(1, $username);
    $stmt->bindParam(2, $email);
    $stmt->bindParam(3, $hash);
    return $stmt->execute();
}

function loginUser($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id, password_hash FROM Users WHERE email = ?");
    $stmt->bindParam(1, $email);
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        logActivity($user['user_id'], 'login');
        return true;
    }
    return false;
}

function getUser($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT username, email FROM Users WHERE user_id = ?");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    return $stmt->fetch();
}

// Profile functions
function saveProfile($user_id, $favorite_games) {
    $db = getDB();
    $db->beginTransaction();
    $stmt = $db->prepare("DELETE FROM UserGames WHERE user_id = ?");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    if (!empty($favorite_games)) {
        $games = explode(',', $favorite_games);
        foreach ($games as $game) {
            $game = trim($game);
            if (!empty($game)) {
                $stmt = $db->prepare("INSERT INTO UserGames (user_id, game_id) SELECT ?, game_id FROM Games WHERE title = ?");
                $stmt->bindParam(1, $user_id);
                $stmt->bindParam(2, $game);
                $stmt->execute();
            }
        }
    }
    $db->commit();
}

function getFavoriteGames($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT g.title, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = ?");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Friend functions
function addFriend($user_id, $friend_username) {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->bindParam(1, $friend_username);
    $stmt->execute();
    $friend = $stmt->fetch();
    if ($friend && $friend['user_id'] != $user_id) {
        $stmt = $db->prepare("INSERT IGNORE INTO Friends (user_id, friend_user_id) VALUES (?, ?)");
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $friend['user_id']);
        return $stmt->execute();
    }
    return false;
}

function getFriends($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.username, u.last_activity FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = ?");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Schedule functions
function addSchedule($user_id, $game_id, $date, $time, $friends) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $game_id);
    $stmt->bindParam(3, $date);
    $stmt->bindParam(4, $time);
    $stmt->bindParam(5, $friends);
    return $stmt->execute();
}

function getSchedules($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.schedule_id, g.title AS game_title, s.date, s.time, s.friends FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = ? ORDER BY s.date, s.time");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function editSchedule($schedule_id, $user_id, $game_id, $date, $time, $friends) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE Schedules SET game_id = ?, date = ?, time = ?, friends = ? WHERE schedule_id = ? AND user_id = ?");
    $stmt->bindParam(1, $game_id);
    $stmt->bindParam(2, $date);
    $stmt->bindParam(3, $time);
    $stmt->bindParam(4, $friends);
    $stmt->bindParam(5, $schedule_id);
    $stmt->bindParam(6, $user_id);
    return $stmt->execute();
}

function deleteSchedule($schedule_id, $user_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->bindParam(1, $schedule_id);
    $stmt->bindParam(2, $user_id);
    return $stmt->execute();
}

// Event functions
function addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $db = getDB();
    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $title);
    $stmt->bindParam(3, $date);
    $stmt->bindParam(4, $time);
    $stmt->bindParam(5, $description);
    $stmt->bindParam(6, $reminder);
    $stmt->bindParam(7, $schedule_id);
    $stmt->execute();
    $event_id = $db->lastInsertId();
    foreach ($shared_friends as $friend_id) {
        $stmt = $db->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
        $stmt->bindParam(1, $event_id);
        $stmt->bindParam(2, $friend_id);
        $stmt->execute();
    }
    $db->commit();
    return true;
}

function getEvents($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT e.event_id, e.title, e.date, e.time, e.description, e.reminder, g.title AS game_title FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id LEFT JOIN Games g ON s.game_id = g.game_id WHERE e.user_id = ? ORDER BY e.date, e.time");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    $events = $stmt->fetchAll();
    foreach ($events as &$event) {
        $stmt = $db->prepare("SELECT u.username FROM EventUserMap eum JOIN Users u ON eum.friend_id = u.user_id WHERE eum.event_id = ?");
        $stmt->bindParam(1, $event['event_id']);
        $stmt->execute();
        $shared = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $event['shared_with'] = $shared;
    }
    return $events;
}

function editEvent($event_id, $user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $db = getDB();
    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE Events SET title = ?, date = ?, time = ?, description = ?, reminder = ?, schedule_id = ? WHERE event_id = ? AND user_id = ?");
    $stmt->bindParam(1, $title);
    $stmt->bindParam(2, $date);
    $stmt->bindParam(3, $time);
    $stmt->bindParam(4, $description);
    $stmt->bindParam(5, $reminder);
    $stmt->bindParam(6, $schedule_id);
    $stmt->bindParam(7, $event_id);
    $stmt->bindParam(8, $user_id);
    $stmt->execute();
    $stmt = $db->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->bindParam(1, $event_id);
    $stmt->execute();
    foreach ($shared_friends as $friend_id) {
        $stmt = $db->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
        $stmt->bindParam(1, $event_id);
        $stmt->bindParam(2, $friend_id);
        $stmt->execute();
    }
    $db->commit();
    return true;
}

function deleteEvent($event_id, $user_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->bindParam(1, $event_id);
    $stmt->bindParam(2, $user_id);
    return $stmt->execute();
}

// Utility functions
function getGames() {
    $db = getDB();
    $stmt = $db->query("SELECT game_id, title FROM Games");
    return $stmt->fetchAll();
}

function logActivity($user_id, $action) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
    $stmt->bindParam(1, $user_id);
    $stmt->bindParam(2, $action);
    $stmt->execute();
}

function validateInput($input, $type) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'date':
            return strtotime($input) !== false;
        case 'time':
            return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input);
        default:
            return !empty(trim($input));
    }
}
?>