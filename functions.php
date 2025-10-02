<?php
// Core Functions for GamePlan Scheduler
// Created by Harsha Kanaparthi on 02-10-2025
// This file contains all business logic, queries, and validation functions.
// Structured with short, focused methods for readability. All queries use prepared statements for security.
// Validation includes trim, length checks, regex for formats, and business rules (e.g., future dates only).

require_once 'db.php';

// Start session if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token on POST requests
function validateCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setMessage('danger', 'Invalid request. Please try again.');
        header('Location: index.php');
        exit;
    }
}

// Set session message for alerts
function setMessage($type, $msg) {
    $_SESSION['alert'] = ['type' => $type, 'msg' => $msg];
}

// Get and clear session message
function getMessage() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Require user login, redirect if not
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        setMessage('danger', 'Please log in to access this page.');
        header('Location: login.php');
        exit;
    }
}

// Get current user ID
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

// Register new user with validation
function registerUser($username, $email, $password) {
    global $pdo;
    $username = trim($username);
    $email = trim($email);
    if (empty($username) || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return 'Username must be 1-50 characters, alphanumeric with hyphen/underscore.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format.';
    }
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        return 'Username or email already taken.';
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);
    return true;
}

// Login user with validation
function loginUser($username, $password) {
    global $pdo;
    $username = trim($username);
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id'] = $user['user_id'];
        return true;
    }
    return 'Incorrect username or password.';
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check for session timeout (30 min)
function checkTimeout() {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT last_activity FROM Users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $last = $stmt->fetchColumn();
        if (time() - strtotime($last) > 1800) { // 30 minutes
            logoutUser();
        } else {
            // Update activity on valid request
            $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
    }
}

// Get all games
function getGames() {
    global $pdo;
    $stmt = $pdo->query("SELECT game_id, titel FROM Games ORDER BY titel ASC");
    return $stmt->fetchAll();
}

// Add favorite game
function addFavoriteGame($game_id) {
    global $pdo;
    $user_id = getUserId();
    if (!is_numeric($game_id)) {
        return 'Invalid game ID.';
    }
    $stmt = $pdo->prepare("SELECT titel, description FROM Games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    if (!$game) {
        return 'Game not found.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserGames WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user_id, $game_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Already added to favorites.';
    }
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $game_id, $game['titel'], $game['description']]);
    return true;
}

// Get user's favorite games
function getFavoriteGames($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT gametitel AS titel, game_description AS description FROM UserGames WHERE user_id = ? ORDER BY gametitel ASC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Add friend
function addFriend($friend_username) {
    global $pdo;
    $user_id = getUserId();
    $friend_username = trim($friend_username);
    if (empty($friend_username) || !preg_match('/^[a-zA-Z0-9_-]+$/', $friend_username)) {
        return 'Invalid username.';
    }
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->execute([$friend_username]);
    $friend_id = $stmt->fetchColumn();
    if (!$friend_id || $friend_id == $user_id) {
        return 'User not found or cannot add self.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = ? AND friend_user_id = ?");
    $stmt->execute([$user_id, $friend_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Already friends.';
    }
    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $friend_id]);
    return true;
}

// Get friends list with status
function getFriends($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, 
        IF(TIMESTAMPDIFF(SECOND, u.last_activity, CURRENT_TIMESTAMP) < 300, 'Online', 'Offline') AS status
        FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = ? ORDER BY u.username ASC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Add schedule
function addSchedule($game_id, $date, $time, $friends_arr) {
    global $pdo;
    $user_id = getUserId();
    if (!is_numeric($game_id)) {
        return 'Select a game.';
    }
    if (strtotime($date) < strtotime('today')) {
        return 'Date must be today or future.';
    }
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    $friends = implode(',', $friends_arr);
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $game_id, $date, $time, $friends]);
    return true;
}

// Get schedules
function getSchedules($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.titel AS game_titel, s.date, s.time, s.friends 
        FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = ? ORDER BY s.date ASC, s.time ASC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Get schedule by ID
function getScheduleById($id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    return $stmt->fetch();
}

// Edit schedule
function editSchedule($id, $game_id, $date, $time, $friends_arr) {
    global $pdo;
    $user_id = getUserId();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'Schedule not found or no permission.';
    }
    if (!is_numeric($game_id)) {
        return 'Select a game.';
    }
    if (strtotime($date) < strtotime('today')) {
        return 'Date must be today or future.';
    }
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    $friends = implode(',', $friends_arr);
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = ?, date = ?, time = ?, friends = ? WHERE schedule_id = ?");
    $stmt->execute([$game_id, $date, $time, $friends, $id]);
    return true;
}

// Add event
function addEvent($title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    global $pdo;
    $user_id = getUserId();
    $title = trim($title);
    $description = trim($description);
    if (empty($title) || strlen($title) > 100) {
        return 'Title must be 1-100 characters.';
    }
    if (strtotime($date) < strtotime('today')) {
        return 'Date must be today or future.';
    }
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    if (strlen($description) > 500) {
        return 'Description max 500 characters.';
    }
    if ($schedule_id && !is_numeric($schedule_id)) {
        return 'Invalid schedule ID.';
    }
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $date, $time, $description, $reminder, $schedule_id ?: null]);
    $event_id = $pdo->lastInsertId();
    foreach ($shared_friends as $friend_id) {
        if (is_numeric($friend_id)) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
            $stmt->execute([$event_id, $friend_id]);
        }
    }
    return true;
}

// Get events with shared users
function getEvents($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, s.game_id FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id WHERE e.user_id = ? ORDER BY e.date ASC, e.time ASC");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll();
    foreach ($events as &$event) {
        $stmt = $pdo->prepare("SELECT u.username FROM EventUserMap em JOIN Users u ON em.friend_id = u.user_id WHERE em.event_id = ?");
        $stmt->execute([$event['event_id']]);
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $events;
}

// Get event by ID
function getEventById($id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $event = $stmt->fetch();
    if ($event) {
        $stmt = $pdo->prepare("SELECT friend_id FROM EventUserMap WHERE event_id = ?");
        $stmt->execute([$id]);
        $event['shared_friends'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $event;
}

// Edit event
function editEvent($id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    global $pdo;
    $user_id = getUserId();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'Event not found or no permission.';
    }
    $title = trim($title);
    $description = trim($description);
    if (empty($title) || strlen($title) > 100) {
        return 'Title must be 1-100 characters.';
    }
    if (strtotime($date) < strtotime('today')) {
        return 'Date must be today or future.';
    }
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    if (strlen($description) > 500) {
        return 'Description max 500 characters.';
    }
    if ($schedule_id && !is_numeric($schedule_id)) {
        return 'Invalid schedule ID.';
    }
    $stmt = $pdo->prepare("UPDATE Events SET title = ?, date = ?, time = ?, description = ?, reminder = ?, schedule_id = ? WHERE event_id = ?");
    $stmt->execute([$title, $date, $time, $description, $reminder, $schedule_id ?: null, $id]);
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->execute([$id]);
    foreach ($shared_friends as $friend_id) {
        if (is_numeric($friend_id)) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
            $stmt->execute([$id, $friend_id]);
        }
    }
    return true;
}

// Delete schedule
function deleteSchedule($id) {
    global $pdo;
    $user_id = getUserId();
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    return $stmt->rowCount() > 0;
}

// Delete event (cascades to EventUserMap)
function deleteEvent($id) {
    global $pdo;
    $user_id = getUserId();
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    return $stmt->rowCount() > 0;
}

// Get merged calendar data
function getCalendarData($user_id) {
    $schedules = getSchedules($user_id);
    $events = getEvents($user_id);
    $calendar = array_merge($schedules, $events);
    usort($calendar, function($a, $b) {
        $a_time = strtotime($a['date'] . ' ' . $a['time']);
        $b_time = strtotime($b['date'] . ' ' . $b['time']);
        return $a_time - $b_time;
    });
    return $calendar;
}

// Get due reminders for pop-ups
function getDueReminders($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT title, date, time, reminder FROM Events WHERE user_id = ? AND date >= CURDATE()");
    $stmt->execute([$user_id]);
    $reminders = [];
    $now = time();
    foreach ($stmt->fetchAll() as $event) {
        $event_time = strtotime($event['date'] . ' ' . $event['time']);
        $reminder_time = 0;
        if ($event['reminder'] == '1 hour before') {
            $reminder_time = $event_time - 3600;
        } elseif ($event['reminder'] == '1 day before') {
            $reminder_time = $event_time - 86400;
        }
        if ($reminder_time > 0 && $now >= $reminder_time && $now < $event_time) {
            $reminders[] = "Reminder: " . $event['title'] . " at " . $event['time'] . " on " . $event['date'];
        }
    }
    return $reminders;
}
?>