<?php
// functions.php - Core Functions and Queries
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Contains all database queries, validation logic, and helper functions.
// Organized by sections: User Auth, Profile, Friends, Schedules, Events, Helpers.
// Uses PDO prepared statements for security against SQL injection.

// Require database connection
require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true); // Regenerate session ID for security
}

// --- Helper Functions ---

// Secure output escaping
function safeEcho($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Validate required fields and trim
function validateRequired($value, $fieldName, $maxLength = 0) {
    $value = trim($value);
    if (empty($value) || preg_match('/^\s*$/', $value)) {
        return "$fieldName may not be empty or contain only spaces.";
    }
    if ($maxLength > 0 && strlen($value) > $maxLength) {
        return "$fieldName exceeds maximum length of $maxLength characters.";
    }
    return null;
}

// Validate date (future date only)
function validateDate($date) {
    if (strtotime($date) === false) {
        return "Invalid date format.";
    }
    if (strtotime($date) < time()) {
        return "Date must be in the future.";
    }
    return null;
}

// Validate time (positive, valid format)
function validateTime($time) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        return "Invalid time format (HH:MM).";
    }
    return null;
}

// Validate email
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return null;
}

// Set session message
function setMessage($type, $msg) {
    $_SESSION['message'] = ['type' => $type, 'msg' => $msg];
}

// Get and clear session message
function getMessage() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return "<div class='alert alert-{$msg['type']}'>{$msg['msg']}</div>";
    }
    return '';
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getUserId() {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : 0;
}

// Update last activity for online status
function updateLastActivity($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
}

// Session timeout check (30 minutes)
function checkSessionTimeout() {
    if (isLoggedIn() && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_destroy();
        header("Location: login.php?msg=session_timeout");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// --- User Authentication ---

// Register new user
function registerUser($username, $email, $password) {
    $pdo = getDBConnection();
    
    // Validate inputs
    if ($err = validateRequired($username, "Username", 50)) return $err;
    if ($err = validateEmail($email)) return $err;
    if ($err = validateRequired($password, "Password")) return $err;
    if (strlen($password) < 8) return "Password must be at least 8 characters.";

    // Check if email exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) return "Email already registered.";

    // Hash password
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :hash)");
    try {
        $stmt->execute(['username' => $username, 'email' => $email, 'hash' => $hash]);
        return null; // Success
    } catch (PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        return "Registration failed. Please try again.";
    }
}

// Login user
function loginUser($email, $password) {
    $pdo = getDBConnection();
    
    // Validate inputs
    if ($err = validateRequired($email, "Email")) return $err;
    if ($err = validateRequired($password, "Password")) return $err;

    // Fetch user
    $stmt = $pdo->prepare("SELECT user_id, password_hash FROM Users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return "Invalid email or password.";
    }

    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    session_regenerate_id(true);
    updateLastActivity($pdo, $user['user_id']);
    return null; // Success
}

// Logout
function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}

// --- Profile Management ---

// Add favorite game
function addFavoriteGame($userId, $gameId) {
    $pdo = getDBConnection();
    
    if (empty($gameId) || !is_numeric($gameId)) return "Invalid game selection.";

    // Check if already added
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserGames WHERE user_id = :user_id AND game_id = :game_id");
    $stmt->execute(['user_id' => $userId, 'game_id' => $gameId]);
    if ($stmt->fetchColumn() > 0) return "Game already in favorites.";

    // Insert
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id) VALUES (:user_id, :game_id)");
    $stmt->execute(['user_id' => $userId, 'game_id' => $gameId]);
    return null;
}

// Get favorite games
function getFavoriteGames($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT g.game_id, g.titel, g.description FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id WHERE ug.user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

// --- Friends Management ---

// Add friend
function addFriend($userId, $friendUsername) {
    $pdo = getDBConnection();
    
    if ($err = validateRequired($friendUsername, "Friend username", 50)) return $err;

    // Get friend ID
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username");
    $stmt->execute(['username' => $friendUsername]);
    $friend = $stmt->fetch();
    if (!$friend) return "User not found.";
    
    $friendId = $friend['user_id'];
    if ($friendId == $userId) return "Cannot add yourself as friend.";

    // Check if already friends
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = :user_id AND friend_user_id = :friend_id");
    $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
    if ($stmt->fetchColumn() > 0) return "Already friends.";

    // Insert (mutual friendship)
    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (:user_id, :friend_id), (:friend_id, :user_id)");
    $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
    return null;
}

// Get friends list with online status
function getFriends($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, 
                           CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'Online' ELSE 'Offline' END AS status 
                           FROM Friends f JOIN Users u ON f.friend_user_id = u.user_id WHERE f.user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

// --- Schedules Management ---

// Add schedule
function addSchedule($userId, $gameId, $date, $time, $friends = []) {
    $pdo = getDBConnection();
    
    // Validate
    if (empty($gameId) || !is_numeric($gameId)) return "Invalid game selection.";
    if ($err = validateDate($date)) return $err;
    if ($err = validateTime($time)) return $err;

    // Prepare friends as comma-separated string
    $friendsStr = implode(',', $friends);

    // Insert
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (:user_id, :game_id, :date, :time, :friends)");
    $stmt->execute(['user_id' => $userId, 'game_id' => $gameId, 'date' => $date, 'time' => $time, 'friends' => $friendsStr]);
    return null;
}

// Get schedules
function getSchedules($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT s.schedule_id, g.titel AS game_titel, s.date, s.time, s.friends 
                           FROM Schedules s JOIN Games g ON s.game_id = g.game_id WHERE s.user_id = :user_id ORDER BY s.date, s.time LIMIT 50");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

// Edit schedule
function editSchedule($userId, $scheduleId, $gameId, $date, $time, $friends = []) {
    $pdo = getDBConnection();
    
    // Check ownership
    if (!checkOwnership($pdo, 'Schedules', 'schedule_id', $scheduleId, $userId)) return "No permission to edit.";

    // Validate same as add
    if (empty($gameId) || !is_numeric($gameId)) return "Invalid game selection.";
    if ($err = validateDate($date)) return $err;
    if ($err = validateTime($time)) return $err;

    $friendsStr = implode(',', $friends);

    // Update
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = :game_id, date = :date, time = :time, friends = :friends WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['game_id' => $gameId, 'date' => $date, 'time' => $time, 'friends' => $friendsStr, 'id' => $scheduleId, 'user_id' => $userId]);
    return null;
}

// Delete schedule
function deleteSchedule($userId, $scheduleId) {
    $pdo = getDBConnection();
    
    if (!checkOwnership($pdo, 'Schedules', 'schedule_id', $scheduleId, $userId)) return "No permission to delete.";

    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $scheduleId, 'user_id' => $userId]);
    return null;
}

// --- Events Management ---

// Add event
function addEvent($userId, $title, $date, $time, $description, $reminder, $scheduleId = null, $sharedFriends = []) {
    $pdo = getDBConnection();
    
    // Validate
    if ($err = validateRequired($title, "Title", 100)) return $err;
    if ($err = validateDate($date)) return $err;
    if ($err = validateTime($time)) return $err;
    if (!empty($description) && strlen($description) > 500) return "Description too long (max 500 characters).";
    if (!in_array($reminder, ['none', '1_hour', '1_day'])) return "Invalid reminder option.";
    if (!empty($scheduleId) && !is_numeric($scheduleId)) return "Invalid schedule link.";

    // Insert event
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) 
                           VALUES (:user_id, :title, :date, :time, :description, :reminder, :schedule_id)");
    $stmt->execute([
        'user_id' => $userId, 'title' => $title, 'date' => $date, 'time' => $time, 
        'description' => $description, 'reminder' => $reminder, 'schedule_id' => $scheduleId ?: null
    ]);
    $eventId = $pdo->lastInsertId();

    // Share with friends
    foreach ($sharedFriends as $friendId) {
        if (is_numeric($friendId)) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
            $stmt->execute(['event_id' => $eventId, 'friend_id' => $friendId]);
        }
    }
    return null;
}

// Get events
function getEvents($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT e.event_id, e.title, e.date, e.time, e.description, e.reminder, s.game_id, 
                           GROUP_CONCAT(u.username SEPARATOR ', ') AS shared_with 
                           FROM Events e 
                           LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id 
                           LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id 
                           LEFT JOIN Users u ON eum.friend_id = u.user_id 
                           WHERE e.user_id = :user_id GROUP BY e.event_id ORDER BY e.date, e.time LIMIT 50");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

// Edit event
function editEvent($userId, $eventId, $title, $date, $time, $description, $reminder, $scheduleId = null, $sharedFriends = []) {
    $pdo = getDBConnection();
    
    if (!checkOwnership($pdo, 'Events', 'event_id', $eventId, $userId)) return "No permission to edit.";

    // Validate same as add
    if ($err = validateRequired($title, "Title", 100)) return $err;
    if ($err = validateDate($date)) return $err;
    if ($err = validateTime($time)) return $err;
    if (!empty($description) && strlen($description) > 500) return "Description too long (max 500 characters).";
    if (!in_array($reminder, ['none', '1_hour', '1_day'])) return "Invalid reminder option.";
    if (!empty($scheduleId) && !is_numeric($scheduleId)) return "Invalid schedule link.";

    // Update event
    $stmt = $pdo->prepare("UPDATE Events SET title = :title, date = :date, time = :time, description = :description, 
                           reminder = :reminder, schedule_id = :schedule_id WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute([
        'title' => $title, 'date' => $date, 'time' => $time, 'description' => $description, 
        'reminder' => $reminder, 'schedule_id' => $scheduleId ?: null, 'id' => $eventId, 'user_id' => $userId
    ]);

    // Clear existing shares
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);

    // Add new shares
    foreach ($sharedFriends as $friendId) {
        if (is_numeric($friendId)) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (:event_id, :friend_id)");
            $stmt->execute(['event_id' => $eventId, 'friend_id' => $friendId]);
        }
    }
    return null;
}

// Delete event
function deleteEvent($userId, $eventId) {
    $pdo = getDBConnection();
    
    if (!checkOwnership($pdo, 'Events', 'event_id', $eventId, $userId)) return "No permission to delete.";

    // Delete shares first
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
    $stmt->execute(['event_id' => $eventId]);

    // Delete event
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $eventId, 'user_id' => $userId]);
    return null;
}

// --- Games Management (Pre-populated games) ---

// Get all games
function getGames() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT game_id, titel, description FROM Games ORDER BY titel");
    return $stmt->fetchAll();
}

// --- Ownership Check Helper ---
function checkOwnership($pdo, $table, $idColumn, $id, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $idColumn = :id AND user_id = :user_id");
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    return $stmt->fetchColumn() > 0;
}

// --- Calendar Merge ---
function getCalendarItems($userId) {
    $schedules = getSchedules($userId);
    $events = getEvents($userId);

    // Merge and sort by date/time
    $items = array_merge($schedules, $events);
    usort($items, function($a, $b) {
        $dateA = strtotime($a['date'] . ' ' . $a['time']);
        $dateB = strtotime($b['date'] . ' ' . $b['time']);
        return $dateA <=> $dateB;
    });

    return $items;
}
?>