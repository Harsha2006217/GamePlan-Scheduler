<?php
require_once 'db.php';

// Utility functions for GamePlan Scheduler
// Created by Harsha Kanaparthi on 02-10-2025
// Includes authentication, CRUD operations, validation, and reminder logic

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token
function validateCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }
}

// Set session message
function setMessage($type, $message) {
    $_SESSION['message'] = ['type' => $type, 'message' => $message];
}

// Get and clear session message
function getMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return $msg;
    }
    return null;
}

// Require user login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        setMessage('danger', 'Please log in to access this page');
        header('Location: login.php');
        exit;
    }
}

// Get current user ID
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

// Register new user
function registerUser($username, $email, $password) {
    global $pdo;
    $username = trim($username);
    $email = trim($email);
    $password = trim($password);

    if (empty($username) || empty($email) || empty($password)) {
        return 'All fields are required';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format';
    }

    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters';
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        return 'Username or email already taken';
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
    $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $hash]);

    return true;
}

// Login user
function loginUser($username, $password) {
    global $pdo;
    $username = trim($username);
    $password = trim($password);

    if (empty($username) || empty($password)) {
        return 'All fields are required';
    }

    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        return true;
    }

    return 'Invalid username or password';
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check session timeout (30 minutes)
function checkTimeout() {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT last_activity FROM Users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $last_activity = $stmt->fetchColumn();

        if (time() - strtotime($last_activity) > 1800) {
            logoutUser();
        } else {
            $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
        }
    }
}

// Get all games
function getGames() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM Games");
    return $stmt->fetchAll();
}

// Add favorite game
function addFavoriteGame($game_id) {
    global $pdo;
    $user_id = getUserId();

    if (empty($game_id)) {
        return 'Game ID is required';
    }

    $stmt = $pdo->prepare("SELECT titel, description FROM Games WHERE game_id = :game_id");
    $stmt->execute(['game_id' => $game_id]);
    $game = $stmt->fetch();

    if (!$game) {
        return 'Game not found';
    }

    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES (:user_id, :game_id, :gametitel, :game_description)");
    $stmt->execute([
        'user_id' => $user_id,
        'game_id' => $game_id,
        'gametitel' => $game['titel'],
        'game_description' => $game['description']
    ]);

    return true;
}

// Get user's favorite games
function getFavoriteGames($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT gametitel, game_description FROM UserGames WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Add friend
function addFriend($friend_username) {
    global $pdo;
    $user_id = getUserId();

    if (empty($friend_username)) {
        return 'Friend username is required';
    }

    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username");
    $stmt->execute(['username' => $friend_username]);
    $friend = $stmt->fetch();

    if (!$friend) {
        return 'User not found';
    }

    if ($friend['user_id'] == $user_id) {
        return 'Cannot add yourself as friend';
    }

    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (:user_id, :friend_user_id)");
    $stmt->execute(['user_id' => $user_id, 'friend_user_id' => $friend['user_id']]);

    return true;
}

// Get user's friends
function getFriends($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.username, CASE WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, CURRENT_TIMESTAMP) < 5 THEN 'Online' ELSE 'Offline' END AS status FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Add schedule
function addSchedule($game_id, $date, $time, $friends_arr) {
    global $pdo;
    $user_id = getUserId();

    if (empty($game_id) || empty($date) || empty($time)) {
        return 'All fields are required';
    }

    $friends = implode(',', $friends_arr);

    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (:user_id, :game_id, :date, :time, :friends)");
    $stmt->execute([
        'user_id' => $user_id,
        'game_id' => $game_id,
        'date' => $date,
        'time' => $time,
        'friends' => $friends
    ]);

    return true;
}

// Get user's schedules
function getSchedules($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.titel AS game_titel, s.date, s.time, s.friends FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user_id ORDER BY s.date, s.time");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Edit schedule
function editSchedule($schedule_id, $game_id, $date, $time, $friends_arr) {
    global $pdo;
    $user_id = getUserId();

    if (empty($game_id) || empty($date) || empty($time)) {
        return 'All fields are required';
    }

    $friends = implode(',', $friends_arr);

    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game_id, date = :date, time = :time, friends = :friends WHERE schedule_id = :schedule_id AND user_id = :user_id");
    $stmt->execute([
        'game_id' => $game_id,
        'date' => $date,
        'time' => $time,
        'friends' => $friends,
        'schedule_id' => $schedule_id,
        'user_id' => $user_id
    ]);

    return $stmt->rowCount() > 0 ? true : 'Schedule not found or no permission';
}

// Get schedule by ID
function getScheduleById($schedule_id) {
    global $pdo;
    $user_id = getUserId();
    $stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = :schedule_id AND user_id = :user_id");
    $stmt->execute(['schedule_id' => $schedule_id, 'user_id' => $user_id]);
    return $stmt->fetch();
}

// Add event
function addEvent($title, $date, $time, $description, $reminder, $schedule_id, $friend_ids) {
    global $pdo;
    $user_id = getUserId();

    if (empty($title) || empty($date) || empty($time)) {
        return 'All fields are required';
    }

    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (:user_id, :title, :date, :time, :description, :reminder, :schedule_id)");
    $stmt->execute([
        'user_id' => $user_id,
        'title' => $title,
        'date' => $date,
        'time' => $time,
        'description' => $description,
        'reminder' => $reminder,
        'schedule_id' => $schedule_id
    ]);

    $event_id = $pdo->lastInsertId();

    foreach ($friend_ids as $friend_id) {
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
        $stmt->execute(['event_id' => $event_id, 'friend_id' => $friend_id]);
    }

    return true;
}

// Get user's events
function getEvents($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.event_id, e.title, e.date, e.time, e.description, e.reminder, e.schedule_id FROM Events e WHERE e.user_id = :user_id ORDER BY e.date, e.time");
    $stmt->execute(['user_id' => $user_id]);
    $events = $stmt->fetchAll();

    foreach ($events as &$event) {
        $stmt = $pdo->prepare("SELECT u.username FROM EventUserMap m JOIN Users u ON m.friend_id = u.user_id WHERE m.event_id = :event_id");
        $stmt->execute(['event_id' => $event['event_id']]);
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return $events;
}

// Edit event
function editEvent($event_id, $title, $date, $time, $description, $reminder, $schedule_id, $friend_ids) {
    global $pdo;
    $user_id = getUserId();

    if (empty($title) || empty($date) || empty($time)) {
        return 'All fields are required';
    }

    $stmt = $pdo->prepare("UPDATE Events SET title = :title, date = :date, time = :time, description = :description, reminder = :reminder, schedule_id = :schedule_id WHERE event_id = :event_id AND user_id = :user_id");
    $stmt->execute([
        'title' => $title,
        'date' => $date,
        'time' => $time,
        'description' => $description,
        'reminder' => $reminder,
        'schedule_id' => $schedule_id,
        'event_id' => $event_id,
        'user_id' => $user_id
    ]);

    if ($stmt->rowCount() == 0) {
        return 'Event not found or no permission';
    }

    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $event_id]);

    foreach ($friend_ids as $friend_id) {
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
        $stmt->execute(['event_id' => $event_id, 'friend_id' => $friend_id]);
    }

    return true;
}

// Get event by ID
function getEventById($event_id) {
    global $pdo;
    $user_id = getUserId();
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = :event_id AND user_id = :user_id");
    $stmt->execute(['event_id' => $event_id, 'user_id' => $user_id]);
    $event = $stmt->fetch();

    if ($event) {
        $stmt = $pdo->prepare("SELECT friend_id FROM EventUserMap WHERE event_id = :event_id");
        $stmt->execute(['event_id' => $event_id]);
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return $event;
}

// Delete schedule
function deleteSchedule($schedule_id) {
    global $pdo;
    $user_id = getUserId();

    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = :schedule_id AND user_id = :user_id");
    $stmt->execute(['schedule_id' => $schedule_id, 'user_id' => $user_id]);

    return $stmt->rowCount() > 0;
}

// Delete event
function deleteEvent($event_id) {
    global $pdo;
    $user_id = getUserId();

    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = :event_id AND user_id = :user_id");
    $stmt->execute(['event_id' => $event_id, 'user_id' => $user_id]);

    return $stmt->rowCount() > 0;
}

// Get calendar overview (merged schedules and events)
function getCalendarOverview() {
    global $pdo;
    $user_id = getUserId();

    $stmt = $pdo->prepare("SELECT 'schedule' AS type, s.schedule_id AS id, g.titel AS title, s.date, s.time, '' AS description, '' AS reminder, s.friends AS shared_with FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user_id 
                           UNION 
                           SELECT 'event' AS type, e.event_id AS id, e.title, e.date, e.time, e.description, e.reminder, GROUP_CONCAT(u.username) AS shared_with FROM Events e LEFT JOIN EventUserMap m ON e.event_id = m.event_id LEFT JOIN Users u ON m.friend_id = u.user_id WHERE e.user_id = :user_id GROUP BY e.event_id 
                           ORDER BY date, time");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Get due reminders
function getDueReminders() {
    global $pdo;
    $user_id = getUserId();

    $stmt = $pdo->prepare("SELECT title, date, time, reminder FROM Events WHERE user_id = :user_id AND reminder IS NOT NULL");
    $stmt->execute(['user_id' => $user_id]);
    $events = $stmt->fetchAll();

    $reminders = [];
    $now = time();

    foreach ($events as $event) {
        $event_time = strtotime($event['date'] . ' ' . $event['time']);

        $reminder_time = 0;
        if ($event['reminder'] == '1 hour before') {
            $reminder_time = $event_time - 3600;
        } elseif ($event['reminder'] == '1 day before') {
            $reminder_time = $event_time - 86400;
        }

        if ($reminder_time > 0 && $now >= $reminder_time && $now < $event_time) {
            $reminders[] = "Reminder: " . $event['title'] . " on " . $event['date'] . " at " . $event['time'];
        }
    }

    return $reminders;
}
?>