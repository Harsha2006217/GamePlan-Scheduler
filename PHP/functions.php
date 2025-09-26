<?php
// GamePlan Scheduler - Core Functions Library
// Professional PHP functions with security, validation, and error handling

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gameplan_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('SESSION_LIFETIME', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('USERNAME_MIN_LENGTH', 3);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Initialize session with security settings
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

        session_start();

        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 300) { // Regenerate every 5 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }

        // Check session expiry
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            logoutUser();
        }
        $_SESSION['last_activity'] = time();
    }
}

// Database Connection
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Set connection timeout
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);

        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }

    return $pdo;
}

// ====================
// Authentication Functions
// ====================

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;

    static $user = null;
    if ($user === null) {
        $user = getUserById(getCurrentUserId());
    }
    return $user;
}

// Login user
function loginUser($email, $password) {
    $pdo = getDBConnection();

    // Check for brute force protection
    if (isLoginLocked($email)) {
        throw new Exception("Account temporarily locked due to too many failed login attempts. Try again later.");
    }

    // Get user by email
    $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        logFailedLogin($email);
        throw new Exception("Invalid email or password");
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        logFailedLogin($email);
        throw new Exception("Invalid email or password");
    }

    // Clear failed login attempts on successful login
    clearFailedLogins($email);

    // Update last activity
    updateUserActivity($user['user_id']);

    // Set session data
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];

    // Log successful login
    logActivity($user['user_id'], 'login', 'User logged in successfully');

    return $user;
}

// Register new user
function registerUser($username, $email, $password) {
    $pdo = getDBConnection();

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception("All fields are required");
    }

    if (strlen($username) < USERNAME_MIN_LENGTH) {
        throw new Exception("Username must be at least " . USERNAME_MIN_LENGTH . " characters");
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        throw new Exception("Username can only contain letters, numbers, and underscores");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address");
    }

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters");
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number");
    }

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        throw new Exception("Username or email already exists");
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $email, $passwordHash]);

    $userId = $pdo->lastInsertId();

    // Log registration
    logActivity($userId, 'register', 'User account created');

    return $userId;
}

// Logout user
function logoutUser() {
    if (isLoggedIn()) {
        logActivity(getCurrentUserId(), 'logout', 'User logged out');
    }

    // Clear session
    session_unset();
    session_destroy();

    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

// ====================
// User Management Functions
// ====================

// Get user by ID
function getUserById($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT user_id, username, email, first_name, last_name, bio, avatar_url, last_activity, created_at FROM Users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Get user profile (with additional data)
function getUserProfile($userId) {
    $user = getUserById($userId);
    if ($user) {
        // Add additional profile data
        $user['friends_count'] = getFriendsCount($userId);
        $user['schedules_count'] = getSchedulesCount($userId);
        $user['events_count'] = getEventsCount($userId);
    }
    return $user;
}

// Update user profile
function updateUserProfile($userId, $data) {
    $pdo = getDBConnection();

    $allowedFields = ['first_name', 'last_name', 'bio'];
    $updates = [];
    $params = [];

    foreach ($data as $field => $value) {
        if (in_array($field, $allowedFields)) {
            $updates[] = "$field = ?";
            $params[] = $value;
        }
    }

    if (empty($updates)) {
        throw new Exception("No valid fields to update");
    }

    $params[] = $userId;
    $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    logActivity($userId, 'profile_update', 'User profile updated');
}

// Update user activity
function updateUserActivity($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = ?");
    $stmt->execute([$userId]);
}

// ====================
// Friend Management Functions
// ====================

// Add friend
function addFriend($userId, $friendUsername) {
    $pdo = getDBConnection();

    // Get friend user ID
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->execute([$friendUsername]);
    $friend = $stmt->fetch();

    if (!$friend) {
        throw new Exception("User not found");
    }

    if ($friend['user_id'] == $userId) {
        throw new Exception("You cannot add yourself as a friend");
    }

    // Check if already friends
    $stmt = $pdo->prepare("SELECT friend_id FROM Friends WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)");
    $stmt->execute([$userId, $friend['user_id'], $friend['user_id'], $userId]);
    if ($stmt->fetch()) {
        throw new Exception("You are already friends with this user");
    }

    // Add friendship (bidirectional)
    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (?, ?)");
    $stmt->execute([$userId, $friend['user_id']]);

    logActivity($userId, 'friend_add', "Added friend: $friendUsername");
}

// Remove friend
function removeFriend($userId, $friendId) {
    $pdo = getDBConnection();

    // Remove friendship (both directions)
    $stmt = $pdo->prepare("DELETE FROM Friends WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);

    if ($stmt->rowCount() == 0) {
        throw new Exception("Friend relationship not found");
    }

    logActivity($userId, 'friend_remove', "Removed friend with ID: $friendId");
}

// Get friends list
function getFriends($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.last_activity
        FROM Users u
        INNER JOIN Friends f ON (f.friend_user_id = u.user_id AND f.user_id = ?) OR (f.user_id = u.user_id AND f.friend_user_id = ?)
        WHERE u.user_id != ?
        ORDER BY u.last_activity DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

// Get online friends
function getOnlineFriends($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.last_activity
        FROM Users u
        INNER JOIN Friends f ON (f.friend_user_id = u.user_id AND f.user_id = ?) OR (f.user_id = u.user_id AND f.friend_user_id = ?)
        WHERE u.user_id != ? AND u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY u.last_activity DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

// Search users
function searchUsers($query, $excludeUserId = null) {
    $pdo = getDBConnection();
    $sql = "SELECT user_id, username FROM Users WHERE username LIKE ? AND user_id != ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $query . '%', $excludeUserId]);
    return $stmt->fetchAll();
}

// ====================
// Game Management Functions
// ====================

// Get all games
function getGames() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM Games ORDER BY titel ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get game by ID
function getGameById($gameId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM Games WHERE game_id = ?");
    $stmt->execute([$gameId]);
    return $stmt->fetch();
}

// ====================
// Favorite Games Functions
// ====================

// Add favorite game
function addFavoriteGame($userId, $gameId) {
    $pdo = getDBConnection();

    // Check if game exists
    if (!getGameById($gameId)) {
        throw new Exception("Game not found");
    }

    // Check if already favorite
    $stmt = $pdo->prepare("SELECT user_game_id FROM UserGames WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$userId, $gameId]);
    if ($stmt->fetch()) {
        throw new Exception("Game is already in your favorites");
    }

    // Add favorite
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id) VALUES (?, ?)");
    $stmt->execute([$userId, $gameId]);

    logActivity($userId, 'favorite_add', "Added favorite game ID: $gameId");
}

// Remove favorite game
function removeFavoriteGame($userId, $gameId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$userId, $gameId]);

    if ($stmt->rowCount() == 0) {
        throw new Exception("Favorite game not found");
    }

    logActivity($userId, 'favorite_remove', "Removed favorite game ID: $gameId");
}

// Get favorite games
function getFavoriteGames($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT g.*, ug.added_at
        FROM Games g
        INNER JOIN UserGames ug ON g.game_id = ug.game_id
        WHERE ug.user_id = ?
        ORDER BY ug.added_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// ====================
// Schedule Management Functions
// ====================

// Add schedule
function addSchedule($userId, $gameId, $date, $time, $friends) {
    $pdo = getDBConnection();

    // Validate game exists
    if (!getGameById($gameId)) {
        throw new Exception("Invalid game selected");
    }

    // Validate date/time
    $scheduleDateTime = strtotime("$date $time");
    if ($scheduleDateTime === false || $scheduleDateTime <= time()) {
        throw new Exception("Schedule must be in the future");
    }

    // Insert schedule
    $stmt = $pdo->prepare("INSERT INTO Schedules (user_id, game_id, date, time, friends) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $gameId, $date, $time, $friends]);

    $scheduleId = $pdo->lastInsertId();

    logActivity($userId, 'schedule_create', "Created schedule ID: $scheduleId");

    return $scheduleId;
}

// Update schedule
function updateSchedule($scheduleId, $userId, $gameId, $date, $time, $friends) {
    $pdo = getDBConnection();

    // Check ownership
    $stmt = $pdo->prepare("SELECT schedule_id FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$scheduleId, $userId]);
    if (!$stmt->fetch()) {
        throw new Exception("Schedule not found or access denied");
    }

    // Validate game exists
    if (!getGameById($gameId)) {
        throw new Exception("Invalid game selected");
    }

    // Validate date/time
    $scheduleDateTime = strtotime("$date $time");
    if ($scheduleDateTime === false || $scheduleDateTime <= time()) {
        throw new Exception("Schedule must be in the future");
    }

    // Update schedule
    $stmt = $pdo->prepare("UPDATE Schedules SET game_id = ?, date = ?, time = ?, friends = ? WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$gameId, $date, $time, $friends, $scheduleId, $userId]);

    logActivity($userId, 'schedule_update', "Updated schedule ID: $scheduleId");
}

// Delete schedule
function deleteSchedule($scheduleId, $userId) {
    $pdo = getDBConnection();

    // Check ownership
    $stmt = $pdo->prepare("SELECT schedule_id FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$scheduleId, $userId]);
    if (!$stmt->fetch()) {
        throw new Exception("Schedule not found or access denied");
    }

    // Delete schedule
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$scheduleId, $userId]);

    logActivity($userId, 'schedule_delete', "Deleted schedule ID: $scheduleId");
}

// Get schedules
function getSchedules($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, g.titel as game_title, g.genre, g.platform
        FROM Schedules s
        INNER JOIN Games g ON s.game_id = g.game_id
        WHERE s.user_id = ?
        ORDER BY s.date ASC, s.time ASC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Get schedule by ID
function getScheduleById($scheduleId, $userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, g.titel as game_title, g.genre, g.platform
        FROM Schedules s
        INNER JOIN Games g ON s.game_id = g.game_id
        WHERE s.schedule_id = ? AND s.user_id = ?
    ");
    $stmt->execute([$scheduleId, $userId]);
    return $stmt->fetch();
}

// ====================
// Event Management Functions
// ====================

// Add event
function addEvent($userId, $title, $date, $time, $description, $reminder, $sharedFriends, $scheduleId = null) {
    $pdo = getDBConnection();

    // Validate date/time
    $eventDateTime = strtotime("$date $time");
    if ($eventDateTime === false || $eventDateTime <= time()) {
        throw new Exception("Event must be in the future");
    }

    // Insert event
    $stmt = $pdo->prepare("INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $date, $time, $description, $reminder, $scheduleId]);

    $eventId = $pdo->lastInsertId();

    // Add shared friends
    if (!empty($sharedFriends) && is_array($sharedFriends)) {
        $sharedUsernames = [];
        foreach ($sharedFriends as $friendId) {
            // Verify friendship
            $stmt = $pdo->prepare("SELECT friend_id FROM Friends WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)");
            $stmt->execute([$userId, $friendId, $friendId, $userId]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
                $stmt->execute([$eventId, $friendId]);

                // Get username for logging
                $stmt = $pdo->prepare("SELECT username FROM Users WHERE user_id = ?");
                $stmt->execute([$friendId]);
                $friend = $stmt->fetch();
                if ($friend) {
                    $sharedUsernames[] = $friend['username'];
                }
            }
        }
    }

    logActivity($userId, 'event_create', "Created event: $title" . (!empty($sharedUsernames) ? " (shared with: " . implode(', ', $sharedUsernames) . ")" : ""));

    return $eventId;
}

// Update event
function updateEvent($eventId, $userId, $title, $date, $time, $description, $reminder, $sharedFriends, $scheduleId = null) {
    $pdo = getDBConnection();

    // Check ownership
    $stmt = $pdo->prepare("SELECT event_id FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $userId]);
    if (!$stmt->fetch()) {
        throw new Exception("Event not found or access denied");
    }

    // Validate date/time
    $eventDateTime = strtotime("$date $time");
    if ($eventDateTime === false || $eventDateTime <= time()) {
        throw new Exception("Event must be in the future");
    }

    // Update event
    $stmt = $pdo->prepare("UPDATE Events SET title = ?, date = ?, time = ?, description = ?, reminder = ?, schedule_id = ? WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$title, $date, $time, $description, $reminder, $scheduleId, $eventId, $userId]);

    // Update shared friends
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
    $stmt->execute([$eventId]);

    if (!empty($sharedFriends) && is_array($sharedFriends)) {
        foreach ($sharedFriends as $friendId) {
            // Verify friendship
            $stmt = $pdo->prepare("SELECT friend_id FROM Friends WHERE (user_id = ? AND friend_user_id = ?) OR (user_id = ? AND friend_user_id = ?)");
            $stmt->execute([$userId, $friendId, $friendId, $userId]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
                $stmt->execute([$eventId, $friendId]);
            }
        }
    }

    logActivity($userId, 'event_update', "Updated event ID: $eventId");
}

// Delete event
function deleteEvent($eventId, $userId) {
    $pdo = getDBConnection();

    // Check ownership
    $stmt = $pdo->prepare("SELECT event_id FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $userId]);
    if (!$stmt->fetch()) {
        throw new Exception("Event not found or access denied");
    }

    // Delete event (cascade will handle EventUserMap)
    $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $userId]);

    logActivity($userId, 'event_delete', "Deleted event ID: $eventId");
}

// Get events
function getEvents($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*,
               GROUP_CONCAT(u.username SEPARATOR ',') as shared_with,
               g.titel as game_title
        FROM Events e
        LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
        LEFT JOIN Users u ON eum.friend_id = u.user_id
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        WHERE e.user_id = ?
        GROUP BY e.event_id
        ORDER BY e.date ASC, e.time ASC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Get event by ID
function getEventById($eventId, $userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*,
               GROUP_CONCAT(u.username SEPARATOR ',') as shared_with,
               g.titel as game_title
        FROM Events e
        LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
        LEFT JOIN Users u ON eum.friend_id = u.user_id
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        WHERE e.event_id = ? AND e.user_id = ?
        GROUP BY e.event_id
    ");
    $stmt->execute([$eventId, $userId]);
    return $stmt->fetch();
}

// ====================
// Security Functions
// ====================

// Log failed login attempt
function logFailedLogin($identifier) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (identifier) VALUES (?)");
    $stmt->execute([$identifier]);
}

// Check if login is locked
function isLoginLocked($identifier) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts
        FROM login_attempts
        WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$identifier, LOGIN_LOCKOUT_TIME]);
    $result = $stmt->fetch();

    return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
}

// Clear failed login attempts
function clearFailedLogins($identifier) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE identifier = ?");
    $stmt->execute([$identifier]);
}

// ====================
// Activity Logging
// ====================

// Log user activity
function logActivity($userId, $action, $details = '') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

// ====================
// Statistics Functions
// ====================

// Get friends count
function getFriendsCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Friends
        WHERE user_id = ? OR friend_user_id = ?
    ");
    $stmt->execute([$userId, $userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Get schedules count
function getSchedulesCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Schedules WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Get events count
function getEventsCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Events WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// ====================
// Utility Functions
// ====================

// Sanitize output
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get client IP address
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Initialize session on include
initSession();
?>