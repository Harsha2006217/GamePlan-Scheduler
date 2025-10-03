<?php
require_once 'db.php';

// Advanced Functions Library for GamePlan Scheduler
// Author: Harsha Kanaparthi - 02-10-2025
// Includes all CRUD operations, validation, security (CSRF, hashing, escaping), session management, reminders, calendar logic.
// Human-style coding with varied comments and logical flow for readability.

session_start();

// CSRF Protection - Generate and validate tokens
if (!isset($_SESSION['csrf_token']) || time() - ($_SESSION['csrf_time'] ?? 0) > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_time'] = time();
}

function validateCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setMessage('error', 'Security error: Invalid form submission. Please try again.');
        header('Location: index.php');
        exit;
    }
}

// Message System for User Feedback - Success/Error Alerts
function setMessage($type, $msg) {
    $_SESSION['message'] = ['type' => $type, 'msg' => $msg];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return $msg;
    }
    return null;
}

// Authentication Helpers - Require Login, Get ID, Timeout Check
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        setMessage('error', 'Please log in to access this feature.');
        header('Location: login.php');
        exit;
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function checkTimeout() {
    $pdo = getPDO();
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT last_activity FROM Users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $last = $stmt->fetchColumn();
        if (time() - strtotime($last) > 1800) { // 30 minutes
            session_destroy();
            setMessage('error', 'Session timed out. Please log in again.');
            header('Location: login.php');
            exit;
        }
        // Update activity on each request
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
    }
}

// User Registration - Advanced Validation
function registerUser($username, $email, $password) {
    $pdo = getPDO();
    $username = trim($username);
    $email = trim($email);
    if (empty($username) || strlen($username) > 50 || !preg_match('/^[\w-]+$/', $username)) {
        return 'Invalid username: 1-50 chars, alphanumeric/hyphen/underscore only.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        return 'Invalid email format.';
    }
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        return 'Username or email already taken.';
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
    $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $hash]);
    return true;
}

// User Login - Secure Verification
function loginUser($username, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE username = :username");
    $stmt->execute(['username' => trim($username)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user['user_id']]);
        return true;
    }
    return 'Invalid username or password.';
}

// Logout - Secure Session Destruction
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Game Functions - Get List, Add Favorite
function getGames() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT game_id, titel FROM Games ORDER BY titel ASC LIMIT 50");
    return $stmt->fetchAll();
}

function addFavoriteGame($game_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    if (!is_numeric($game_id)) {
        return 'Invalid game selection.';
    }
    $stmt = $pdo->prepare("SELECT titel, description FROM Games WHERE game_id = :game_id");
    $stmt->execute(['game_id' => $game_id]);
    $game = $stmt->fetch();
    if (!$game) {
        return 'Game not found.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserGames WHERE user_id = :user_id AND game_id = :game_id");
    $stmt->execute(['user_id' => $user_id, 'game_id' => $game_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Game already in favorites.';
    }
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES (:user_id, :game_id, :titel, :desc)");
    $stmt->execute(['user_id' => $user_id, 'game_id' => $game_id, 'titel' => $game['titel'], 'desc' => $game['description']]);
    return true;
}

function getFavoriteGames($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT g.titel, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = :user_id ORDER BY g.titel ASC LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Friend Functions - Add, Get List
function addFriend($friend_username) {
    $user_id = getUserId();
    $pdo = getPDO();
    $friend_username = trim($friend_username);
    if (empty($friend_username)) {
        return 'Username required.';
    }
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username AND user_id != :user_id");
    $stmt->execute(['username' => $friend_username, 'user_id' => $user_id]);
    $friend_id = $stmt->fetchColumn();
    if (!$friend_id) {
        return 'User not found or cannot add self.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = :user_id AND friend_user_id = :friend_id");
    $stmt->execute(['user_id' => $user_id, 'friend_id' => $friend_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Already friends.';
    }
    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (:user_id, :friend_id)");
    $stmt->execute(['user_id' => $user_id, 'friend_id' => $friend_id]);
    return true;
}

function getFriends($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, 
        CASE WHEN UNIX_TIMESTAMP(u.last_activity) > UNIX_TIMESTAMP() - 300 THEN 'Online' ELSE 'Offline' END AS status
        FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = :user_id ORDER BY u.username ASC LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Schedule Functions - Add, Get, Edit, Get By ID
function addSchedule($game_id, $date, $time, $friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    if (!is_numeric($game_id)) {
        return 'Game selection required.';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    $friends_str = implode(',', array_filter($friends, 'is_numeric'));
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (:user_id, :game_id, :date, :time, :friends)");
    $stmt->execute(['user_id' => $user_id, 'game_id' => $game_id, 'date' => $date, 'time' => $time, 'friends' => $friends_str]);
    return true;
}

function getSchedules($user_id, $sort = 'date ASC, time ASC') {
    $pdo = getPDO();
    $sort = in_array($sort, ['date ASC, time ASC', 'date DESC, time DESC']) ? $sort : 'date ASC, time ASC';
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.titel AS game_titel, s.date, s.time, s.friends 
        FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user_id ORDER BY $sort LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

function editSchedule($schedule_id, $game_id, $date, $time, $friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $schedule_id, 'user_id' => $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'No permission to edit this schedule.';
    }
    if (!is_numeric($game_id)) {
        return 'Game selection required.';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    $friends_str = implode(',', array_filter($friends, 'is_numeric'));
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game_id, date = :date, time = :time, friends = :friends WHERE schedule_id = :id");
    $stmt->execute(['game_id' => $game_id, 'date' => $date, 'time' => $time, 'friends' => $friends_str, 'id' => $schedule_id]);
    return true;
}

function getScheduleById($schedule_id, $user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $schedule_id, 'user_id' => $user_id]);
    return $stmt->fetch();
}

// Event Functions - Add, Get, Edit, Get By ID, Due Reminders
function addEvent($title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    $title = trim($title);
    $description = trim($description);
    if (empty($title) || strlen($title) > 100 || preg_match('/^\s*$/', $title)) {
        return 'Title required (1-100 chars, no spaces only).';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    if (strlen($description) > 500) {
        return 'Description max 500 chars.';
    }
    if (!empty($schedule_id) && !is_numeric($schedule_id)) {
        return 'Invalid schedule link.';
    }
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (:user_id, :title, :date, :time, :desc, :reminder, :schedule_id)");
    $stmt->execute(['user_id' => $user_id, 'title' => $title, 'date' => $date, 'time' => $time, 'desc' => $description, 'reminder' => $reminder, 'schedule_id' => $schedule_id ?: null]);
    $event_id = $pdo->lastInsertId();
    foreach (array_filter($shared_friends, 'is_numeric') as $friend_id) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
        $stmt->execute(['event_id' => $event_id, 'friend_id' => $friend_id]);
    }
    return true;
}

function getEvents($user_id, $sort = 'date ASC, time ASC') {
    $pdo = getPDO();
    $sort = in_array($sort, ['date ASC, time ASC', 'date DESC, time DESC']) ? $sort : 'date ASC, time ASC';
    $stmt = $pdo->prepare("SELECT e.*, g.titel AS schedule_game FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id LEFT JOIN Games g ON s.game_id = g.game_id WHERE e.user_id = :user_id ORDER BY $sort LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    $events = $stmt->fetchAll();
    foreach ($events as &$event) {
        $stmt = $pdo->prepare("SELECT u.username FROM EventUserMap em JOIN Users u ON em.friend_id = u.user_id WHERE em.event_id = :event_id");
        $stmt->execute(['event_id' => $event['event_id']]);
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $events;
}

function editEvent($event_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'No permission to edit this event.';
    }
    $title = trim($title);
    $description = trim($description);
    if (empty($title) || strlen($title) > 100 || preg_match('/^\s*$/', $title)) {
        return 'Title required (1-100 chars, no spaces only).';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d:00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    if (strlen($description) > 500) {
        return 'Description max 500 chars.';
    }
    if (!empty($schedule_id) && !is_numeric($schedule_id)) {
        return 'Invalid schedule link.';
    }
    $stmt = $pdo->prepare("UPDATE Events SET title = :title, date = :date, time = :time, description = :desc, reminder = :reminder, schedule_id = :schedule_id WHERE event_id = :id");
    $stmt->execute(['title' => $title, 'date' => $date, 'time' => $time, 'desc' => $description, 'reminder' => $reminder, 'schedule_id' => $schedule_id ?: null, 'id' => $event_id]);
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $event_id]);
    foreach (array_filter($shared_friends, 'is_numeric') as $friend_id) {
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
        $stmt->execute(['event_id' => $event_id, 'friend_id' => $friend_id]);
    }
    return true;
}

function getEventById($event_id, $user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
    $event = $stmt->fetch();
    if ($event) {
        $stmt = $pdo->prepare("SELECT friend_id FROM EventUserMap WHERE event_id = :event_id");
        $stmt->execute(['event_id' => $event_id]);
        $event['shared_friends'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $event;
}

function getDueReminders($user_id) {
    $pdo = getPDO();
    $now = time();
    $stmt = $pdo->prepare("SELECT title, date, time, reminder FROM Events WHERE user_id = :user_id AND date >= CURDATE()");
    $stmt->execute(['user_id' => $user_id]);
    $reminders = [];
    foreach ($stmt->fetchAll() as $event) {
        $eventTime = strtotime($event['date'] . ' ' . $event['time']);
        $offset = 0;
        if ($event['reminder'] === '1 hour before') $offset = 3600;
        elseif ($event['reminder'] === '1 day before') $offset = 86400;
        if ($offset > 0 && $eventTime - $offset <= $now && $eventTime > $now) {
            $reminders[] = "Reminder: {$event['title']} on {$event['date']} at {$event['time']}";
        }
    }
    return $reminders;
}

// Calendar - Merge and Sort Schedules/Events
function getCalendarData($user_id) {
    $schedules = getSchedules($user_id);
    $events = getEvents($user_id);
    $calendar = array_merge($schedules, $events);
    usort($calendar, function($a, $b) {
        $timeA = strtotime($a['date'] . ' ' . $a['time']);
        $timeB = strtotime($b['date'] . ' ' . $b['time']);
        return $timeA - $timeB;
    });
    return $calendar;
}

// Deletion Functions - Secure with Ownership Check
function deleteSchedule($schedule_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $schedule_id, 'user_id' => $user_id]);
    return $stmt->rowCount() > 0;
}

function deleteEvent($event_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
    return $stmt->rowCount() > 0;
}
?>