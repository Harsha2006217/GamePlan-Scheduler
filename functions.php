<?php
// functions.php - Advanced Core Functions
// Author: Harsha Kanaparthi
// Date: 30-09-2025

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}

// === SECURITY & VALIDATION FUNCTIONS ===
function safeEcho($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function validateRequired($value, $fieldName, $maxLength = 0) {
    $value = trim($value ?? '');
    if (empty($value) || preg_match('/^\s*$/', $value)) {
        return "$fieldName is required and cannot be empty or contain only spaces.";
    }
    if ($maxLength > 0 && strlen($value) > $maxLength) {
        return "$fieldName exceeds maximum length of $maxLength characters.";
    }
    return null;
}

function validateDate($date) {
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        return "Invalid date format. Use YYYY-MM-DD.";
    }
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return "Date must be today or in the future.";
    }
    return null;
}

function validateTime($time) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        return "Invalid time format. Use HH:MM.";
    }
    return null;
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return null;
}

// === SESSION & AUTH FUNCTIONS ===
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function setMessage($type, $msg) {
    $_SESSION['message'] = ['type' => $type, 'msg' => $msg];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return "<div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>
                {$msg['msg']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

function checkSessionTimeout() {
    if (isLoggedIn() && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_destroy();
        header("Location: login.php?msg=session_timeout");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function updateLastActivity($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
}

// === USER AUTHENTICATION ===
function registerUser($username, $email, $password) {
    $pdo = getDBConnection();
    
    $errors = [];
    if ($err = validateRequired($username, "Username", 50)) $errors[] = $err;
    if ($err = validateEmail($email)) $errors[] = $err;
    if ($err = validateRequired($password, "Password")) $errors[] = $err;
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    
    if (!empty($errors)) return implode(" ", $errors);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = :email AND deleted_at IS NULL");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) return "Email already registered.";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = :username AND deleted_at IS NULL");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetchColumn() > 0) return "Username already taken.";

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
    
    try {
        $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $hash]);
        return null;
    } catch (PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        return "Registration failed. Please try again.";
    }
}

function loginUser($email, $password) {
    $pdo = getDBConnection();
    
    $errors = [];
    if ($err = validateRequired($email, "Email")) $errors[] = $err;
    if ($err = validateRequired($password, "Password")) $errors[] = $err;
    if (!empty($errors)) return implode(" ", $errors);

    $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM Users WHERE email = :email AND deleted_at IS NULL");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return "Invalid email or password.";
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    session_regenerate_id(true);
    updateLastActivity($user['user_id']);
    return null;
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}

// === ADVANCED GAMES CRUD ===
function getOrCreateGame($title, $description = '') {
    $pdo = getDBConnection();
    $title = trim($title);
    
    if (empty($title)) return 0;

    $stmt = $pdo->prepare("SELECT game_id FROM Games WHERE LOWER(titel) = LOWER(:title) AND deleted_at IS NULL");
    $stmt->execute(['title' => $title]);
    $game = $stmt->fetch();
    
    if ($game) return $game['game_id'];

    $stmt = $pdo->prepare("INSERT INTO Games (titel, description) VALUES (:titel, :description)");
    $stmt->execute(['titel' => $title, 'description' => $description]);
    return $pdo->lastInsertId();
}

function addFavoriteGame($userId, $title, $description = '', $note = '') {
    $pdo = getDBConnection();
    
    if ($err = validateRequired($title, "Game title", 100)) return $err;
    
    $gameId = getOrCreateGame($title, $description);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserGames WHERE user_id = :user_id AND game_id = :game_id AND deleted_at IS NULL");
    $stmt->execute(['user_id' => $userId, 'game_id' => $gameId]);
    if ($stmt->fetchColumn() > 0) return "Game already in favorites.";

    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, note) VALUES (:user_id, :game_id, :note)");
    $stmt->execute(['user_id' => $userId, 'game_id' => $gameId, 'note' => $note]);
    return null;
}

function getFavoriteGames($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT ug.user_game_id, g.game_id, g.titel, g.description, ug.note 
                          FROM UserGames ug 
                          JOIN Games g ON ug.game_id = g.game_id 
                          WHERE ug.user_id = :user_id AND ug.deleted_at IS NULL 
                          ORDER BY g.titel");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function updateFavoriteGame($userId, $userGameId, $title, $description, $note) {
    $pdo = getDBConnection();
    
    if ($err = validateRequired($title, "Game title", 100)) return $err;

    $stmt = $pdo->prepare("SELECT ug.game_id FROM UserGames ug WHERE ug.user_game_id = :id AND ug.user_id = :user_id AND ug.deleted_at IS NULL");
    $stmt->execute(['id' => $userGameId, 'user_id' => $userId]);
    $currentGame = $stmt->fetch();
    
    if (!$currentGame) return "Favorite game not found or no permission.";

    $newGameId = getOrCreateGame($title, $description);
    
    $stmt = $pdo->prepare("UPDATE UserGames SET game_id = :game_id, note = :note WHERE user_game_id = :id AND user_id = :user_id");
    $stmt->execute(['game_id' => $newGameId, 'note' => $note, 'id' => $userGameId, 'user_id' => $userId]);
    return null;
}

function deleteFavoriteGame($userId, $userGameId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE UserGames SET deleted_at = NOW() WHERE user_game_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $userGameId, 'user_id' => $userId]);
    return null;
}

// === ADVANCED FRIENDS CRUD ===
function addFriend($userId, $friendUsername, $note = '') {
    $pdo = getDBConnection();
    
    if ($err = validateRequired($friendUsername, "Friend username", 50)) return $err;

    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username AND deleted_at IS NULL");
    $stmt->execute(['username' => $friendUsername]);
    $friend = $stmt->fetch();
    
    if (!$friend) return "User not found.";
    if ($friend['user_id'] == $userId) return "Cannot add yourself as friend.";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = :user_id AND friend_user_id = :friend_id AND deleted_at IS NULL");
    $stmt->execute(['user_id' => $userId, 'friend_id' => $friend['user_id']]);
    if ($stmt->fetchColumn() > 0) return "Already friends.";

    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id, note) VALUES (:user_id, :friend_id, :note)");
    $stmt->execute(['user_id' => $userId, 'friend_id' => $friend['user_id'], 'note' => $note]);
    return null;
}

function getFriends($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT f.friend_id, u.user_id, u.username, f.note,
                          CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'Online' ELSE 'Offline' END AS status
                          FROM Friends f 
                          JOIN Users u ON f.friend_user_id = u.user_id 
                          WHERE f.user_id = :user_id AND f.deleted_at IS NULL AND u.deleted_at IS NULL
                          ORDER BY u.username");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function updateFriendNote($userId, $friendId, $note) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Friends SET note = :note WHERE friend_id = :id AND user_id = :user_id AND deleted_at IS NULL");
    $stmt->execute(['note' => $note, 'id' => $friendId, 'user_id' => $userId]);
    return null;
}

function deleteFriend($userId, $friendId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Friends SET deleted_at = NOW() WHERE friend_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $friendId, 'user_id' => $userId]);
    return null;
}

// === ADVANCED SCHEDULES CRUD ===
function addSchedule($userId, $gameTitle, $scheduleTitle, $date, $time, $description = '', $friendsList = '', $sharedWith = '') {
    $pdo = getDBConnection();
    
    $errors = [];
    if ($err = validateRequired($gameTitle, "Game title", 100)) $errors[] = $err;
    if ($err = validateRequired($scheduleTitle, "Schedule title", 100)) $errors[] = $err;
    if ($err = validateDate($date)) $errors[] = $err;
    if ($err = validateTime($time)) $errors[] = $err;
    if (!empty($errors)) return implode(" ", $errors);

    $gameId = getOrCreateGame($gameTitle);
    
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, schedule_title, date, time, description, friends_list, shared_with) 
                          VALUES (:user_id, :game_id, :schedule_title, :date, :time, :description, :friends_list, :shared_with)");
    $stmt->execute([
        'user_id' => $userId, 
        'game_id' => $gameId, 
        'schedule_title' => $scheduleTitle,
        'date' => $date, 
        'time' => $time, 
        'description' => $description,
        'friends_list' => $friendsList,
        'shared_with' => $sharedWith
    ]);
    return null;
}

function getSchedules($userId, $sort = 'date ASC, time ASC') {
    $pdo = getDBConnection();
    $allowedSort = ['date ASC', 'date DESC', 'time ASC', 'time DESC', 'schedule_title ASC'];
    $sort = in_array($sort, $allowedSort) ? $sort : 'date ASC, time ASC';
    
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.titel AS game_title, s.schedule_title, s.date, s.time, s.description, s.friends_list, s.shared_with
                          FROM Schedules s 
                          JOIN Games g ON s.game_id = g.game_id 
                          WHERE s.user_id = :user_id AND s.deleted_at IS NULL 
                          ORDER BY $sort");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function updateSchedule($userId, $scheduleId, $gameTitle, $scheduleTitle, $date, $time, $description = '', $friendsList = '', $sharedWith = '') {
    $pdo = getDBConnection();
    
    $errors = [];
    if ($err = validateRequired($gameTitle, "Game title", 100)) $errors[] = $err;
    if ($err = validateRequired($scheduleTitle, "Schedule title", 100)) $errors[] = $err;
    if ($err = validateDate($date)) $errors[] = $err;
    if ($err = validateTime($time)) $errors[] = $err;
    if (!empty($errors)) return implode(" ", $errors);

    $gameId = getOrCreateGame($gameTitle);
    
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game_id, schedule_title = :schedule_title, date = :date, time = :time, 
                          description = :description, friends_list = :friends_list, shared_with = :shared_with 
                          WHERE schedule_id = :id AND user_id = :user_id AND deleted_at IS NULL");
    $stmt->execute([
        'game_id' => $gameId, 
        'schedule_title' => $scheduleTitle,
        'date' => $date, 
        'time' => $time, 
        'description' => $description,
        'friends_list' => $friendsList,
        'shared_with' => $sharedWith,
        'id' => $scheduleId, 
        'user_id' => $userId
    ]);
    return null;
}

function deleteSchedule($userId, $scheduleId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Schedules SET deleted_at = NOW() WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $scheduleId, 'user_id' => $userId]);
    return null;
}

// === ADVANCED EVENTS CRUD ===
function addEvent($userId, $eventTitle, $date, $time, $description = '', $reminder = 'none', $externalLink = '', $scheduleId = null, $sharedWith = '') {
    $pdo = getDBConnection();
    
    $errors = [];
    if ($err = validateRequired($eventTitle, "Event title", 100)) $errors[] = $err;
    if ($err = validateDate($date)) $errors[] = $err;
    if ($err = validateTime($time)) $errors[] = $err;
    if (!empty($errors)) return implode(" ", $errors);

    $stmt = $pdo->prepare("INSERT INTO Events (user_id, event_title, date, time, description, reminder, external_link, schedule_id, shared_with) 
                          VALUES (:user_id, :event_title, :date, :time, :description, :reminder, :external_link, :schedule_id, :shared_with)");
    $stmt->execute([
        'user_id' => $userId, 
        'event_title' => $eventTitle,
        'date' => $date, 
        'time' => $time, 
        'description' => $description,
        'reminder' => $reminder,
        'external_link' => $externalLink,
        'schedule_id' => $scheduleId,
        'shared_with' => $sharedWith
    ]);
    return null;
}

function getEvents($userId, $sort = 'date ASC, time ASC') {
    $pdo = getDBConnection();
    $allowedSort = ['date ASC', 'date DESC', 'time ASC', 'time DESC', 'event_title ASC'];
    $sort = in_array($sort, $allowedSort) ? $sort : 'date ASC, time ASC';
    
    $stmt = $pdo->prepare("SELECT e.event_id, e.event_title, e.date, e.time, e.description, e.reminder, e.external_link, e.shared_with,
                          s.schedule_title, g.titel AS game_title
                          FROM Events e 
                          LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id 
                          LEFT JOIN Games g ON s.game_id = g.game_id 
                          WHERE e.user_id = :user_id AND e.deleted_at IS NULL 
                          ORDER BY $sort");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function updateEvent($userId, $eventId, $eventTitle, $date, $time, $description = '', $reminder = 'none', $externalLink = '', $scheduleId = null, $sharedWith = '') {
    $pdo = getDBConnection();
    
    $errors = [];
    if ($err = validateRequired($eventTitle, "Event title", 100)) $errors[] = $err;
    if ($err = validateDate($date)) $errors[] = $err;
    if ($err = validateTime($time)) $errors[] = $err;
    if (!empty($errors)) return implode(" ", $errors);

    $stmt = $pdo->prepare("UPDATE Events SET event_title = :event_title, date = :date, time = :time, description = :description, 
                          reminder = :reminder, external_link = :external_link, schedule_id = :schedule_id, shared_with = :shared_with 
                          WHERE event_id = :id AND user_id = :user_id AND deleted_at IS NULL");
    $stmt->execute([
        'event_title' => $eventTitle,
        'date' => $date, 
        'time' => $time, 
        'description' => $description,
        'reminder' => $reminder,
        'external_link' => $externalLink,
        'schedule_id' => $scheduleId,
        'shared_with' => $sharedWith,
        'id' => $eventId, 
        'user_id' => $userId
    ]);
    return null;
}

function deleteEvent($userId, $eventId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Events SET deleted_at = NOW() WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $eventId, 'user_id' => $userId]);
    return null;
}

// === CALENDAR & DASHBOARD FUNCTIONS ===
function getCalendarItems($userId) {
    $schedules = getSchedules($userId);
    $events = getEvents($userId);
    
    $items = [];
    foreach ($schedules as $schedule) {
        $items[] = [
            'type' => 'schedule',
            'id' => $schedule['schedule_id'],
            'title' => $schedule['schedule_title'],
            'game_title' => $schedule['game_title'],
            'date' => $schedule['date'],
            'time' => $schedule['time'],
            'description' => $schedule['description'],
            'friends_list' => $schedule['friends_list'],
            'shared_with' => $schedule['shared_with']
        ];
    }
    
    foreach ($events as $event) {
        $items[] = [
            'type' => 'event',
            'id' => $event['event_id'],
            'title' => $event['event_title'],
            'game_title' => $event['game_title'] ?? '',
            'date' => $event['date'],
            'time' => $event['time'],
            'description' => $event['description'],
            'reminder' => $event['reminder'],
            'external_link' => $event['external_link'],
            'shared_with' => $event['shared_with']
        ];
    }
    
    usort($items, function($a, $b) {
        $dateA = strtotime($a['date'] . ' ' . $a['time']);
        $dateB = strtotime($b['date'] . ' ' . $b['time']);
        return $dateA <=> $dateB;
    });
    
    return $items;
}

function getReminders($userId) {
    $events = getEvents($userId);
    $reminders = [];
    
    foreach ($events as $event) {
        if ($event['reminder'] != 'none') {
            $eventTime = strtotime($event['date'] . ' ' . $event['time']);
            $reminderIntervals = [
                '15_minutes' => 900,
                '1_hour' => 3600,
                '1_day' => 86400,
                '1_week' => 604800
            ];
            
            $reminderTime = $eventTime - $reminderIntervals[$event['reminder']];
            if ($reminderTime <= time() && $reminderTime > time() - 300) {
                $reminders[] = $event;
            }
        }
    }
    
    return $reminders;
}

// === HELPER FUNCTIONS ===
function getAvailableSchedules($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT schedule_id, schedule_title, date, time FROM Schedules 
                          WHERE user_id = :user_id AND deleted_at IS NULL AND date >= CURDATE() 
                          ORDER BY date, time");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function getAllGames() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT game_id, titel FROM Games WHERE deleted_at IS NULL ORDER BY titel");
    return $stmt->fetchAll();
}
?>