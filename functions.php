<?php
// Core functions for GamePlan Scheduler
// Author: Harsha Kanaparthi
// Features: All CRUD operations with bound parameters, advanced validation (regex, lengths, future dates),
// Helper functions for online status, calendar merging/sorting, CSRF token generation/validation,
// Error messaging via sessions, efficient joins and limits for performance.

require_once 'db.php';  // Include PDO connection

// Start session if not started (for CSRF, messages, auth)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set (per session)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token on POST requests
function validateCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setMessage('error', 'Invalid request. Please try again.');
        header('Location: index.php');
        exit;
    }
}

// Set session message for alerts (success/error)
function setMessage($type, $msg) {
    $_SESSION['message'] = ['type' => $type, 'msg' => $msg];
}

// Get and clear message for display
function getMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return $msg;
    }
    return null;
}

// Check if user is logged in, redirect if not
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        setMessage('error', 'Please log in to access this page.');
        header('Location: login.php');
        exit;
    }
}

// Get current user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Register new user with validation
function registerUser($username, $email, $password) {
    $pdo = getPDO();
    // Validate inputs
    if (empty(trim($username)) || strlen($username) > 50 || preg_match('/[^\w-]/', $username)) {
        return 'Invalid username: 1-50 alphanumeric characters only.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        return 'Invalid email address.';
    }
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    // Check for duplicates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        return 'Username or email already taken.';
    }
    // Hash password and insert
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
    $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $hash]);
    return true;  // Success
}

// Login user with validation
function loginUser($username, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        // Update last activity
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user['user_id']]);
        return true;
    }
    return 'Invalid username or password.';
}

// Logout user
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check session timeout (30 minutes = 1800 seconds)
function checkTimeout() {
    $pdo = getPDO();
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT last_activity FROM Users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $last = $stmt->fetchColumn();
        if (time() - strtotime($last) > 1800) {
            logoutUser();
        }
    }
}

// Get all games for select dropdown
function getGames() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT game_id, titel FROM Games ORDER BY titel ASC LIMIT 50");
    return $stmt->fetchAll();
}

// Add favorite game with validation
function addFavoriteGame($game_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    // Validate game exists
    $stmt = $pdo->prepare("SELECT titel, description FROM Games WHERE game_id = :game_id");
    $stmt->execute(['game_id' => $game_id]);
    $game = $stmt->fetch();
    if (!$game) {
        return 'Invalid game selected.';
    }
    // Check not already favorite
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserGames WHERE user_id = :user_id AND game_id = :game_id");
    $stmt->execute(['user_id' => $user_id, 'game_id' => $game_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Game already in favorites.';
    }
    // Insert
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, gametitel, game_description) VALUES (:user_id, :game_id, :titel, :desc)");
    $stmt->execute(['user_id' => $user_id, 'game_id' => $game_id, 'titel' => $game['titel'], 'desc' => $game['description']]);
    return true;
}

// Get user's favorite games
function getFavoriteGames($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT g.titel, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = :user_id ORDER BY g.titel ASC LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Add friend with validation
function addFriend($friend_username) {
    $user_id = getUserId();
    $pdo = getPDO();
    if (empty(trim($friend_username))) {
        return 'Username required.';
    }
    // Find friend ID
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username AND user_id != :user_id");
    $stmt->execute(['username' => $friend_username, 'user_id' => $user_id]);
    $friend_id = $stmt->fetchColumn();
    if (!$friend_id) {
        return 'User not found or cannot add yourself.';
    }
    // Check not already friends
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = :user_id AND friend_user_id = :friend_id");
    $stmt->execute(['user_id' => $user_id, 'friend_id' => $friend_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Already friends.';
    }
    // Insert
    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (:user_id, :friend_id)");
    $stmt->execute(['user_id' => $user_id, 'friend_id' => $friend_id]);
    return true;
}

// Get user's friends with online status (online if last_activity within 5 minutes = 300 seconds)
function getFriends($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, 
        CASE WHEN UNIX_TIMESTAMP(u.last_activity) > UNIX_TIMESTAMP() - 300 THEN 'Online' ELSE 'Offline' END AS status
        FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = :user_id ORDER BY u.username ASC LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Add schedule with validation
function addSchedule($game_id, $date, $time, $friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    // Validate
    if (empty($game_id) || !is_numeric($game_id)) {
        return 'Game required.';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):00$/', $time)) {
        return 'Invalid time format (HH:MM:00).';
    }
    $friends_str = implode(',', $friends);  // Array to comma-separated
    // Insert
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (:user_id, :game_id, :date, :time, :friends)");
    $stmt->execute(['user_id' => $user_id, 'game_id' => $game_id, 'date' => $date, 'time' => $time, 'friends' => $friends_str]);
    return true;
}

// Get user's schedules
function getSchedules($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.titel AS game_titel, s.date, s.time, s.friends 
        FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user_id ORDER BY s.date ASC, s.time ASC LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll();
}

// Edit schedule with validation and ownership check
function editSchedule($schedule_id, $game_id, $date, $time, $friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    // Ownership check
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $schedule_id, 'user_id' => $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'No permission to edit this schedule.';
    }
    // Validate same as add
    if (empty($game_id) || !is_numeric($game_id)) {
        return 'Game required.';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):00$/', $time)) {
        return 'Invalid time format.';
    }
    $friends_str = implode(',', $friends);
    // Update
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game_id, date = :date, time = :time, friends = :friends WHERE schedule_id = :id");
    $stmt->execute(['game_id' => $game_id, 'date' => $date, 'time' => $time, 'friends' => $friends_str, 'id' => $schedule_id]);
    return true;
}

// Get single schedule for edit
function getScheduleById($schedule_id, $user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $schedule_id, 'user_id' => $user_id]);
    return $stmt->fetch();
}

// Add event with validation
function addEvent($title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    // Validate
    if (empty(trim($title)) || strlen($title) > 100 || preg_match('/^\s*$/', $title)) {
        return 'Title required, max 100 chars, not just spaces.';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):00$/', $time)) {
        return 'Invalid time format.';
    }
    if (strlen($description) > 500) {
        return 'Description max 500 chars.';
    }
    if (!empty($schedule_id) && !is_numeric($schedule_id)) {
        return 'Invalid schedule link.';
    }
    // Insert event
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (:user_id, :title, :date, :time, :desc, :reminder, :schedule_id)");
    $stmt->execute(['user_id' => $user_id, 'title' => $title, 'date' => $date, 'time' => $time, 'desc' => $description, 'reminder' => $reminder, 'schedule_id' => $schedule_id ?: null]);
    $event_id = $pdo->lastInsertId();
    // Insert shared friends
    foreach ($shared_friends as $friend_id) {
        if (is_numeric($friend_id)) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
            $stmt->execute(['event_id' => $event_id, 'friend_id' => $friend_id]);
        }
    }
    return true;
}

// Get user's events with shared friends
function getEvents($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT e.*, g.titel AS schedule_game FROM Events e LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id LEFT JOIN Games g ON s.game_id = g.game_id WHERE e.user_id = :user_id ORDER BY e.date ASC, e.time ASC LIMIT 50");
    $stmt->execute(['user_id' => $user_id]);
    $events = $stmt->fetchAll();
    // Fetch shared friends for each event
    foreach ($events as &$event) {
        $stmt = $pdo->prepare("SELECT u.username FROM EventUserMap em JOIN Users u ON em.friend_id = u.user_id WHERE em.event_id = :event_id");
        $stmt->execute(['event_id' => $event['event_id']]);
        $event['shared_with'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $events;
}

// Edit event with validation and ownership
function editEvent($event_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    $user_id = getUserId();
    $pdo = getPDO();
    // Ownership check
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'No permission to edit this event.';
    }
    // Validate same as add
    if (empty(trim($title)) || strlen($title) > 100 || preg_match('/^\s*$/', $title)) {
        return 'Title required, max 100 chars, not just spaces.';
    }
    if (strtotime($date) < time()) {
        return 'Date must be in the future.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):00$/', $time)) {
        return 'Invalid time format.';
    }
    if (strlen($description) > 500) {
        return 'Description max 500 chars.';
    }
    if (!empty($schedule_id) && !is_numeric($schedule_id)) {
        return 'Invalid schedule link.';
    }
    // Update event
    $stmt = $pdo->prepare("UPDATE Events SET title = :title, date = :date, time = :time, description = :desc, reminder = :reminder, schedule_id = :schedule_id WHERE event_id = :id");
    $stmt->execute(['title' => $title, 'date' => $date, 'time' => $time, 'desc' => $description, 'reminder' => $reminder, 'schedule_id' => $schedule_id ?: null, 'id' => $event_id]);
    // Clear old shares and add new
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $event_id]);
    foreach ($shared_friends as $friend_id) {
        if (is_numeric($friend_id)) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
            $stmt->execute(['event_id' => $event_id, 'friend_id' => $friend_id]);
        }
    }
    return true;
}

// Get single event for edit
function getEventById($event_id, $user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
    $event = $stmt->fetch();
    if ($event) {
        // Get shared friends
        $stmt = $pdo->prepare("SELECT friend_id FROM EventUserMap WHERE event_id = :event_id");
        $stmt->execute(['event_id' => $event_id]);
        $event['shared_friends'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $event;
}

// Delete schedule with ownership check
function deleteSchedule($schedule_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $schedule_id, 'user_id' => $user_id]);
    return $stmt->rowCount() > 0;
}

// Delete event with ownership check (cascades to EventUserMap)
function deleteEvent($event_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $event_id, 'user_id' => $user_id]);
    return $stmt->rowCount() > 0;
}

// Get merged calendar data (schedules + events, sorted by date/time)
function getCalendarData($user_id) {
    $schedules = getSchedules($user_id);
    $events = getEvents($user_id);
    // Merge arrays
    $calendar = array_merge($schedules, $events);
    // Sort by date then time (usort for efficiency on small datasets)
    usort($calendar, function($a, $b) {
        $dateA = strtotime($a['date'] . ' ' . $a['time']);
        $dateB = strtotime($b['date'] . ' ' . $b['time']);
        return $dateA - $dateB;
    });
    return $calendar;
}

// Get reminders for JS pop-ups (events with reminder due soon)
function getDueReminders($user_id) {
    $pdo = getPDO();
    $now = time();
    $stmt = $pdo->prepare("SELECT title, date, time, reminder FROM Events WHERE user_id = :user_id AND date >= CURDATE()");
    $stmt->execute(['user_id' => $user_id]);
    $reminders = [];
    foreach ($stmt->fetchAll() as $event) {
        $eventTime = strtotime($event['date'] . ' ' . $event['time']);
        $reminderOffset = 0;
        if ($event['reminder'] === '1 hour before') {
            $reminderOffset = 3600;
        } elseif ($event['reminder'] === '1 day before') {
            $reminderOffset = 86400;
        }
        if ($reminderOffset > 0 && $eventTime - $reminderOffset <= $now && $eventTime > $now) {
            $reminders[] = "Reminder: {$event['title']} at {$event['date']} {$event['time']}";
        }
    }
    return $reminders;
}
?>