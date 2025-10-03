<?php
// functions.php: Advanced utility functions for DB, auth, validation
// PDO connection, queries, security (CSRF, hashing), messages

session_start();  // Start session for all pages

// PDO connection (singleton-style for efficiency)
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'gameplan_scheduler';
        $user = 'root';  // Adjust for production
        $pass = '';      // Adjust for production
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

// Generate CSRF token
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRF() {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Invalid CSRF token.');
    }
    unset($_SESSION['csrf_token']);  // Regenerate after use
    generateCSRF();
}

// Set flash message
function setMessage($type, $msg) {
    $_SESSION['message'] = ['type' => $type, 'msg' => $msg];
}

// Get flash message
function getMessage() {
    $msg = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);
    return $msg;
}

// Require login (redirect if not)
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Get current user ID
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

// Register user (with validation)
function registerUser($username, $email, $password) {
    if (strlen($username) < 1 || strlen($username) > 50 || !ctype_alnum($username)) {
        return 'Invalid username: 1-50 alphanumeric characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email.';
    }
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        return 'Username or email already exists.';
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);
    return true;
}

// Login user
function loginUser($username, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT user_id, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return 'Invalid credentials.';
    }
    $_SESSION['user_id'] = $user['user_id'];
    session_regenerate_id(true);  // Security
    $stmt = $pdo->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return true;
}

// Get all games
function getGames() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM games ORDER BY titel');
    return $stmt->fetchAll();
}

// Get user's favorite games
function getFavoriteGames($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT g.* FROM games g JOIN user_games ug ON g.game_id = ug.game_id WHERE ug.user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Add favorite game
function addFavoriteGame($game_id) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_games WHERE user_id = ? AND game_id = ?');
    $stmt->execute([$user_id, $game_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Game already favorited.';
    }
    $stmt = $pdo->prepare('INSERT INTO user_games (user_id, game_id) VALUES (?, ?)');
    $stmt->execute([$user_id, $game_id]);
    return true;
}

// Get friends list with status
function getFriends($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT u.username, f.status FROM friends f JOIN users u ON f.friend_user_id = u.user_id WHERE f.user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Add friend (by username)
function addFriend($friend_username) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ?');
    $stmt->execute([$friend_username]);
    $friend = $stmt->fetch();
    if (!$friend) {
        return 'User not found.';
    }
    $friend_id = $friend['user_id'];
    if ($friend_id == $user_id) {
        return 'Cannot add yourself.';
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM friends WHERE user_id = ? AND friend_user_id = ?');
    $stmt->execute([$user_id, $friend_id]);
    if ($stmt->fetchColumn() > 0) {
        return 'Already friends.';
    }
    $stmt = $pdo->prepare('INSERT INTO friends (user_id, friend_user_id) VALUES (?, ?)');
    $stmt->execute([$user_id, $friend_id]);
    return true;
}

// Get schedules for user
function getSchedules($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM schedules WHERE user_id = ? ORDER BY date, time');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// Add schedule
function addSchedule($game_id, $game, $date, $time, $friends, $reminder) {
    $user_id = getUserId();
    $pdo = getPDO();
    if (!strtotime($date) || !strtotime($time)) {
        return 'Invalid date/time.';
    }
    $friends_str = implode(',', array_map('intval', $friends));  // Sanitize
    $stmt = $pdo->prepare('INSERT INTO schedules (user_id, game_id, game, date, time, friends, reminder) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $game_id, $game, $date, $time, $friends_str, $reminder]);
    return true;
}

// Edit schedule (similar to add, with ID)
function editSchedule($schedule_id, $game_id, $game, $date, $time, $friends, $reminder) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM schedules WHERE schedule_id = ? AND user_id = ?');
    $stmt->execute([$schedule_id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'Schedule not found or not yours.';
    }
    $friends_str = implode(',', array_map('intval', $friends));
    $stmt = $pdo->prepare('UPDATE schedules SET game_id = ?, game = ?, date = ?, time = ?, friends = ?, reminder = ? WHERE schedule_id = ?');
    $stmt->execute([$game_id, $game, $date, $time, $friends_str, $reminder, $schedule_id]);
    return true;
}

// Get events for user
function getEvents($user_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT e.*, GROUP_CONCAT(eum.friend_id) AS shared_with FROM events e LEFT JOIN event_user_map eum ON e.event_id = eum.event_id WHERE e.user_id = ? GROUP BY e.event_id ORDER BY date, time');
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll();
    foreach ($events as &$event) {
        $event['shared_with'] = explode(',', $event['shared_with'] ?? '');
    }
    return $events;
}

// Add event
function addEvent($schedule_id, $title, $date, $time, $description, $reminder, $shared_with) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM schedules WHERE schedule_id = ? AND user_id = ?');
    $stmt->execute([$schedule_id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'Schedule not found or not yours.';
    }
    $stmt = $pdo->prepare('INSERT INTO events (schedule_id, user_id, title, date, time, description, reminder) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$schedule_id, $user_id, $title, $date, $time, $description, $reminder]);
    $event_id = $pdo->lastInsertId();
    foreach ($shared_with as $friend_id) {
        $stmt = $pdo->prepare('INSERT INTO event_user_map (event_id, friend_id) VALUES (?, ?)');
        $stmt->execute([$event_id, (int)$friend_id]);
    }
    return true;
}

// Edit event (similar)
function editEvent($event_id, $schedule_id, $title, $date, $time, $description, $reminder, $shared_with) {
    $user_id = getUserId();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE event_id = ? AND user_id = ?');
    $stmt->execute([$event_id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'Event not found or not yours.';
    }
    $stmt = $pdo->prepare('UPDATE events SET schedule_id = ?, title = ?, date = ?, time = ?, description = ?, reminder = ? WHERE event_id = ?');
    $stmt->execute([$schedule_id, $title, $date, $time, $description, $reminder, $event_id]);
    $stmt = $pdo->prepare('DELETE FROM event_user_map WHERE event_id = ?');
    $stmt->execute([$event_id]);
    foreach ($shared_with as $friend_id) {
        $stmt = $pdo->prepare('INSERT INTO event_user_map (event_id, friend_id) VALUES (?, ?)');
        $stmt->execute([$event_id, (int)$friend_id]);
    }
    return true;
}

// Delete item (generic)
function deleteItem($type, $id) {
    $user_id = getUserId();
    $pdo = getPDO();
    $table = '';
    $id_col = '';
    switch ($type) {
        case 'schedule':
            $table = 'schedules';
            $id_col = 'schedule_id';
            break;
        case 'event':
            $table = 'events';
            $id_col = 'event_id';
            break;
        default:
            return 'Invalid type.';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $id_col = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        return 'Item not found or not yours.';
    }
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_col = ?");
    $stmt->execute([$id]);
    return true;
}

// Get reminders (for JS popups)
function getReminders($user_id) {
    $pdo = getPDO();
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('SELECT "Schedule: " AS type, game AS title, CONCAT(date, " ", time) AS datetime FROM schedules WHERE user_id = ? AND reminder != "none" AND CONCAT(date, " ", time) > ? UNION SELECT "Event: " AS type, title, CONCAT(date, " ", time) AS datetime FROM events WHERE user_id = ? AND reminder != "none" AND CONCAT(date, " ", time) > ?');
    $stmt->execute([$user_id, $now, $user_id, $now]);
    $reminders = [];
    foreach ($stmt->fetchAll() as $item) {
        $reminders[] = "Reminder: {$item['type']} {$item['title']} at {$item['datetime']}";
    }
    return $reminders;
}

generateCSRF();  // Always generate on load
?>