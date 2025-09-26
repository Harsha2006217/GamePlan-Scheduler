<?php
/**
 * GamePlan Scheduler - Core Functions
 * Advanced Professional PHP Functions for Gaming Schedule Management
 * 
 * This file contains all core functions for user authentication, profile management,
 * friend management, schedule management, event management, and utilities.
 * Implements enterprise-level security with PDO prepared statements, input validation,
 * and comprehensive error handling.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0 Professional Edition
 * @since 2025-09-30
 */

// ===================================
// DATABASE CONFIGURATION
// ===================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===================================
// PDO CONNECTION
// ===================================
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true,
                ]
            );
            $pdo->exec("SET time_zone = '+00:00'");
            $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    return $pdo;
}

// ===================================
// USER MANAGEMENT FUNCTIONS
// ===================================
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUser($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT user_id, username, email, last_activity FROM Users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getUserProfile($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT f.friend_id) as friends_count,
               COUNT(DISTINCT s.schedule_id) as schedules_count,
               COUNT(DISTINCT e.event_id) as events_count
        FROM Users u
        LEFT JOIN Friends f ON (u.user_id = f.user_id OR u.user_id = f.friend_user_id)
        LEFT JOIN Schedules s ON u.user_id = s.user_id
        LEFT JOIN Events e ON u.user_id = e.user_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function registerUser($username, $email, $password) {
    try {
        // Validate inputs
        if (empty(trim($username)) || empty(trim($email)) || empty($password)) {
            throw new Exception("All fields are required");
        }
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new Exception("Username must be between 3 and 50 characters");
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new Exception("Username can only contain letters, numbers, underscore, and dash");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        $pdo = getDBConnection();
        
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username or email already exists");
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        // Insert user
        $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $password_hash]);

        return true;
    } catch (Exception $e) {
        error_log("Registration failed: " . $e->getMessage());
        return false;
    }
}

function loginUser($email, $password) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['login_time'] = time();
            
            // Update last activity
            $stmt = $pdo->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            logActivity($user['user_id'], 'login');
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Login failed: " . $e->getMessage());
        return false;
    }
}

function updateUserProfile($user_id, $data) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Users SET first_name = ?, last_name = ?, bio = ? WHERE user_id = ?");
    $stmt->execute([$data['first_name'] ?? '', $data['last_name'] ?? '', $data['bio'] ?? '', $user_id]);
}

// ===================================
// FRIEND MANAGEMENT FUNCTIONS
// ===================================
function getFriends($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.last_activity
        FROM Friends f
        JOIN Users u ON (f.user_id = u.user_id OR f.friend_user_id = u.user_id)
        WHERE (f.user_id = ? OR f.friend_user_id = ?) AND u.user_id != ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    return $stmt->fetchAll();
}

function getOnlineFriends($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.last_activity
        FROM Friends f
        JOIN Users u ON (f.user_id = u.user_id OR f.friend_user_id = u.user_id)
        WHERE (f.user_id = ? OR f.friend_user_id = ?) AND u.user_id != ? 
        AND u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    return $stmt->fetchAll();
}

function searchUsers($query, $current_user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT user_id, username 
        FROM Users 
        WHERE username LIKE ? AND user_id != ? 
        LIMIT 10
    ");
    $stmt->execute(['%' . $query . '%', $current_user_id]);
    return $stmt->fetchAll();
}

function addFriend($user_id, $friend_username) {
    try {
        if (empty(trim($friend_username))) {
            return ['success' => false, 'message' => 'Username is required'];
        }

        $pdo = getDBConnection();
        
        // Get friend user_id
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
        $stmt->execute([$friend_username]);
        $friend = $stmt->fetch();
        
        if (!$friend) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if ($friend['user_id'] == $user_id) {
            return ['success' => false, 'message' => 'Cannot add yourself as friend'];
        }
        
        // Check if already friends
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM Friends 
            WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)
        ");
        $stmt->execute([$user_id, $friend['user_id'], $friend['user_id'], $user_id]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Already friends'];
        }
        
        // Add friendship
        $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $friend['user_id']]);
        
        logActivity($user_id, 'friend_added', $friend['user_id']);
        return ['success' => true, 'message' => 'Friend added successfully', 'friend_id' => $friend['user_id']];
    } catch (Exception $e) {
        error_log("Add friend failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add friend'];
    }
}

function removeFriend($user_id, $friend_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        DELETE FROM Friends 
        WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)
    ");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
}

// ===================================
// GAME MANAGEMENT FUNCTIONS
// ===================================
function getGames() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT game_id, titel, description FROM Games ORDER BY titel");
    return $stmt->fetchAll();
}

function getGameById($game_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM Games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    return $stmt->fetch();
}

function getFavoriteGames($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT g.game_id, g.titel, g.description
        FROM UserGames ug
        JOIN Games g ON ug.game_id = g.game_id
        WHERE ug.user_id = ?
        ORDER BY g.titel
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function addFavoriteGame($user_id, $game_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT IGNORE INTO UserGames (user_id, game_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $game_id]);
}

function removeFavoriteGame($user_id, $game_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user_id, $game_id]);
}

// ===================================
// SCHEDULE MANAGEMENT FUNCTIONS
// ===================================
function getSchedules($user_id, $sort_by = 'date', $order = 'ASC', $filter_game = '', $filter_date = '') {
    $pdo = getDBConnection();
    $where = ["s.user_id = ?"];
    $params = [$user_id];
    
    if (!empty($filter_game)) {
        $where[] = "g.titel LIKE ?";
        $params[] = '%' . $filter_game . '%';
    }
    
    if (!empty($filter_date)) {
        $where[] = "DATE(s.date) = ?";
        $params[] = $filter_date;
    }
    
    $valid_sorts = ['date', 'time', 'game_titel', 'created_at'];
    $valid_orders = ['ASC', 'DESC'];
    if (!in_array($sort_by, $valid_sorts)) $sort_by = 'date';
    if (!in_array($order, $valid_orders)) $order = 'ASC';
    
    $query = "
        SELECT s.*, g.titel as game_titel, g.genre, g.description,
               COUNT(DISTINCT f.friend_id) as friend_count,
               GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as friend_names
        FROM Schedules s 
        LEFT JOIN Games g ON s.game_id = g.game_id 
        LEFT JOIN ScheduleFriends sf ON s.schedule_id = sf.schedule_id
        LEFT JOIN Friends f ON sf.friend_id = f.friend_id
        LEFT JOIN Users u ON f.friend_user_id = u.user_id
        WHERE " . implode(" AND ", $where) . "
        GROUP BY s.schedule_id
        ORDER BY s.$sort_by $order, s.time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getScheduleById($schedule_id, $user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$schedule_id, $user_id]);
    return $stmt->fetch();
}

function addSchedule($user_id, $game_id, $date, $time, $friends_str) {
    try {
        // Validate inputs
        if (empty($game_id) || empty($date) || empty($time)) {
            throw new Exception("All fields are required");
        }
        
        $event_time = strtotime("$date $time");
        if ($event_time === false || $event_time <= time()) {
            throw new Exception("Schedule must be in the future");
        }
        
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $game_id, $date, $time, $friends_str]);
        
        $schedule_id = $pdo->lastInsertId();
        logActivity($user_id, 'schedule_created', $schedule_id);
        return $schedule_id;
    } catch (Exception $e) {
        error_log("Add schedule failed: " . $e->getMessage());
        return false;
    }
}

function updateSchedule($schedule_id, $user_id, $game_id, $date, $time, $friends_str) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = ?, date = ?, time = ?, friends = ? WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $date, $time, $friends_str, $schedule_id, $user_id]);
    logActivity($user_id, 'schedule_updated', $schedule_id);
}

function deleteSchedule($schedule_id, $user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$schedule_id, $user_id]);
    logActivity($user_id, 'schedule_deleted', $schedule_id);
    return $stmt->rowCount() > 0;
}

// ===================================
// EVENT MANAGEMENT FUNCTIONS
// ===================================
function getEvents($user_id, $sort_by = 'date', $order = 'ASC', $filter_type = 'all', $search = '') {
    $pdo = getDBConnection();
    $where = ["e.user_id = ?"];
    $params = [$user_id];
    
    if ($filter_type === 'upcoming') {
        $where[] = "CONCAT(e.date, ' ', e.time) > NOW()";
    } elseif ($filter_type === 'past') {
        $where[] = "CONCAT(e.date, ' ', e.time) <= NOW()";
    } elseif ($filter_type === 'tournament') {
        $where[] = "e.event_type = 'tournament'";
    } elseif ($filter_type === 'meetup') {
        $where[] = "e.event_type = 'meetup'";
    }
    
    if (!empty($search)) {
        $where[] = "(e.title LIKE ? OR e.description LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $valid_sorts = ['date', 'title', 'type', 'reminder', 'participants'];
    $valid_orders = ['ASC', 'DESC'];
    if (!in_array($sort_by, $valid_sorts)) $sort_by = 'date';
    if (!in_array($order, $valid_orders)) $order = 'ASC';
    
    $query = "
        SELECT e.*, g.titel as game_titel,
               GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as shared_with
        FROM Events e
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
        LEFT JOIN Users u ON eum.friend_id = u.user_id
        WHERE " . implode(" AND ", $where) . "
        GROUP BY e.event_id
        ORDER BY e.$sort_by $order, e.time ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
    // Process shared_with
    foreach ($events as &$event) {
        if (!empty($event['shared_with'])) {
            $event['shared_with'] = array_map('trim', explode(',', $event['shared_with']));
        } else {
            $event['shared_with'] = [];
        }
    }
    
    return $events;
}

function getEventById($event_id, $user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    return $stmt->fetch();
}

function addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends) {
    try {
        // Validate inputs
        if (empty(trim($title))) {
            throw new Exception("Title is required");
        }
        if (strlen($title) > 100) {
            throw new Exception("Title must be 100 characters or less");
        }
        if (preg_match('/^\s*$/', $title)) {
            throw new Exception("Title cannot be only spaces");
        }
        
        $event_time = strtotime("$date $time");
        if ($event_time === false || $event_time <= time()) {
            throw new Exception("Event must be in the future");
        }
        
        if (strlen($description) > 500) {
            throw new Exception("Description must be 500 characters or less");
        }
        
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $title, $date, $time, $description, $reminder, $schedule_id]);
        $event_id = $pdo->lastInsertId();
        
        // Add shared friends
        if (!empty($shared_friends)) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
            foreach ($shared_friends as $friend_id) {
                $stmt->execute([$event_id, $friend_id]);
            }
        }
        
        $pdo->commit();
        logActivity($user_id, 'event_created', $event_id);
        return $event_id;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Add event failed: " . $e->getMessage());
        return false;
    }
}

function updateEvent($event_id, $user_id, $title, $date, $time, $description, $reminder, $shared_friends, $schedule_id) {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE Events SET title = ?, date = ?, time = ?, description = ?, reminder = ?, schedule_id = ? WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$title, $date, $time, $description, $reminder, $schedule_id, $event_id, $user_id]);
    
    // Update shared friends
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    if (!empty($shared_friends)) {
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
        foreach ($shared_friends as $friend_id) {
            $stmt->execute([$event_id, $friend_id]);
        }
    }
    
    $pdo->commit();
    logActivity($user_id, 'event_updated', $event_id);
}

function deleteEvent($event_id, $user_id) {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    
    $pdo->commit();
    logActivity($user_id, 'event_deleted', $event_id);
    return $stmt->rowCount() > 0;
}

// ===================================
// UTILITY FUNCTIONS
// ===================================
function logActivity($user_id, $action, $target_id = null, $details = '') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, target_id, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $action, $target_id, json_encode($details)]);
}

function validateInput($input, $type) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'date':
            return strtotime($input) !== false;
        case 'time':
            return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input);
        case 'username':
            return preg_match('/^[a-zA-Z0-9_-]+$/', $input) && strlen($input) >= 3 && strlen($input) <= 50;
        default:
            return !empty(trim($input));
    }
}

function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function getClientIP() {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// ===================================
// PASSWORD MANAGEMENT
// ===================================
const PASSWORD_MIN_LENGTH = 8;

function changePassword($user_id, $current_password, $new_password, $confirm_password) {
    try {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All password fields are required");
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match");
        }
        
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            throw new Exception("New password must be at least " . PASSWORD_MIN_LENGTH . " characters");
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
            throw new Exception("New password must contain uppercase, lowercase, and number");
        }
        
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password_hash'])) {
            throw new Exception("Current password is incorrect");
        }
        
        $new_hash = password_hash($new_password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $stmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$new_hash, $user_id]);
        
        logActivity($user_id, 'password_changed');
        return true;
    } catch (Exception $e) {
        error_log("Password change failed: " . $e->getMessage());
        return false;
    }
}

// ===================================
// STATISTICS FUNCTIONS
// ===================================
function getUserEventStatistics($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_events,
            COUNT(CASE WHEN CONCAT(date, ' ', time) > NOW() THEN 1 END) as upcoming_events,
            COUNT(CASE WHEN CONCAT(date, ' ', time) <= NOW() THEN 1 END) as past_events,
            COUNT(DISTINCT CASE WHEN event_id IN (SELECT event_id FROM EventUserMap) THEN event_id END) as shared_events
        FROM Events 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// ===================================
// REMINDER FUNCTIONS
// ===================================
function getReminders($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM Events 
        WHERE user_id = ? AND reminder != 'none' 
        AND CONCAT(date, ' ', time) > NOW()
        ORDER BY date, time
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function shouldShowReminder($event, $reminder_type) {
    $event_time = strtotime($event['date'] . ' ' . $event['time']);
    $now = time();
    
    switch ($reminder_type) {
        case '1hour':
            return $event_time - $now <= 3600 && $event_time > $now;
        case '1day':
            return $event_time - $now <= 86400 && $event_time > $now;
        default:
            return false;
    }
}

// ===================================
// INITIALIZATION
// ===================================
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'gc_maxlifetime' => 1800,
    ]);
}
?>