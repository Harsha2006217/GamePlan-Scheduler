<?php
/**
 * GamePlan Scheduler Core Functions
 * Contains all main functions for the application
 */

// Include database connection
require_once 'db.php';

// Session configuration
session_start();

// Set session cookie parameters for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

/**
 * Check if user is logged in
 * Returns true if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login for protected pages
 * Redirects to login page if user is not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
    
    // Update last activity timestamp
    updateLastActivity();
}

/**
 * Update user's last activity time
 */
function updateLastActivity() {
    if (isLoggedIn()) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
}

/******************************
 * User Authentication Functions
 ******************************/

// Register a new user
function registerUser($username, $email, $password) {
    global $pdo;
    
    // Validate inputs
    if (empty(trim($username)) || empty(trim($email)) || empty($password)) {
        return ['success' => false, 'message' => 'Alle velden zijn verplicht'];
    }
    
    if (strlen($username) > 50) {
        return ['success' => false, 'message' => 'Gebruikersnaam mag maximaal 50 tekens lang zijn'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Ongeldig e-mailadres'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Wachtwoord moet minimaal 8 tekens lang zijn'];
    }
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Gebruikersnaam of e-mailadres bestaat al'];
    }
    
    // Hash password using Argon2id (PHP 7.3+)
    $password_hash = password_hash($password, PASSWORD_ARGON2ID);
    
    // Insert user into database
    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
    
    try {
        $stmt->execute([$username, $email, $password_hash]);
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registratie mislukt: ' . $e->getMessage()];
    }
}

// Authenticate user login
function loginUser($email, $password) {
    global $pdo;
    
    // Validate inputs
    if (empty(trim($email)) || empty($password)) {
        return ['success' => false, 'message' => 'E-mail en wachtwoord zijn verplicht'];
    }
    
    // Get user from database
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Ongeldige inloggegevens'];
    }
    
    $user = $stmt->fetch();
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        // Update last activity
        updateLastActivity($user['user_id']);
        
        // Return user data for session
        return [
            'success' => true, 
            'user_id' => $user['user_id'], 
            'username' => $user['username']
        ];
    } else {
        return ['success' => false, 'message' => 'Ongeldige inloggegevens'];
    }
}

/******************************
 * Profile Management Functions
 ******************************/

 // Get user profile data
function getUserProfile($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT user_id, username, email FROM Users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() != 1) {
        return false;
    }
    
    return $stmt->fetch();
}

// Add favorite game to user profile
function addFavoriteGame($user_id, $game_id) {
    global $pdo;
    
    // Check if game exists
    $stmt = $pdo->prepare("SELECT game_id FROM Games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Game bestaat niet'];
    }
    
    // Check if user already has this game
    $stmt = $pdo->prepare("SELECT * FROM UserGames WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$user_id, $game_id]);
    
    if ($stmt->rowCount() > 0) {
        return ['success' => true, 'message' => 'Game is al toegevoegd aan favorieten'];
    }
    
    // Add game to user favorites
    $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id) VALUES (?, ?)");
    
    try {
        $stmt->execute([$user_id, $game_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Toevoegen mislukt: ' . $e->getMessage()];
    }
}

// Get user's favorite games
function getFavoriteGames($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT g.game_id, g.titel, g.description 
        FROM Games g
        JOIN UserGames ug ON g.game_id = ug.game_id
        WHERE ug.user_id = ?
        ORDER BY g.titel
    ");
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll();
}

// Remove favorite game
function removeFavoriteGame($user_id, $game_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = ? AND game_id = ?");
    
    try {
        $stmt->execute([$user_id, $game_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Verwijderen mislukt: ' . $e->getMessage()];
    }
}

// Save profile with validation
function saveProfile($user_id, $username, $favorite_games) {
    global $pdo;
    
    // Validate inputs
    if (empty(trim($username))) {
        return ['success' => false, 'message' => 'Gebruikersnaam is verplicht'];
    }
    
    if (strlen($username) > 50) {
        return ['success' => false, 'message' => 'Gebruikersnaam mag maximaal 50 tekens lang zijn'];
    }
    
    if (empty(trim($favorite_games)) || preg_match('/^\s*$/', $favorite_games)) {
        return ['success' => false, 'message' => 'Favoriete games mogen niet alleen spaties zijn'];
    }
    
    // Update username
    $stmt = $pdo->prepare("UPDATE Users SET username = ? WHERE user_id = ?");
    
    try {
        $stmt->execute([$username, $user_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Update mislukt: ' . $e->getMessage()];
    }
}

/******************************
 * Friend Management Functions
 ******************************/

// Add friend
function addFriend($user_id, $friend_username) {
    global $pdo;
    
    // Validate input
    if (empty(trim($friend_username))) {
        return ['success' => false, 'message' => 'Gebruikersnaam is verplicht'];
    }
    
    // Get friend user_id
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->execute([$friend_username]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
    }
    
    $friend = $stmt->fetch();
    $friend_user_id = $friend['user_id'];
    
    // Prevent adding self as friend
    if ($friend_user_id == $user_id) {
        return ['success' => false, 'message' => 'Je kunt jezelf niet als vriend toevoegen'];
    }
    
    // Check if already friends
    $stmt = $pdo->prepare("SELECT * FROM Friends WHERE user_id = ? AND friend_user_id = ?");
    $stmt->execute([$user_id, $friend_user_id]);
    
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Deze gebruiker is al je vriend'];
    }
    
    // Add friend
    $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id) VALUES (?, ?)");
    
    try {
        $stmt->execute([$user_id, $friend_user_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Toevoegen mislukt: ' . $e->getMessage()];
    }
}

// Get friends list with online status
function getFriends($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.last_activity 
        FROM Users u
        JOIN Friends f ON u.user_id = f.friend_user_id
        WHERE f.user_id = ?
        ORDER BY u.username
    ");
    $stmt->execute([$user_id]);
    
    $friends = $stmt->fetchAll();
    $current_time = time();
    
    // Add online status (online if active in the last 5 minutes)
    foreach ($friends as &$friend) {
        $last_activity = strtotime($friend['last_activity']);
        $friend['online'] = ($current_time - $last_activity < 300);
    }
    
    return $friends;
}

// Remove friend
function removeFriend($user_id, $friend_user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM Friends WHERE user_id = ? AND friend_user_id = ?");
    
    try {
        $stmt->execute([$user_id, $friend_user_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Verwijderen mislukt: ' . $e->getMessage()];
    }
}

/******************************
 * Game Functions
 ******************************/

// Get all games
function getGames() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT game_id, titel, description FROM Games ORDER BY titel");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Get game details
function getGame($game_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT game_id, titel, description FROM Games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    
    if ($stmt->rowCount() != 1) {
        return false;
    }
    
    return $stmt->fetch();
}

/******************************
 * Schedule Functions
 ******************************/

// Add schedule
function addSchedule($user_id, $game_id, $date, $time, $friends = '') {
    global $pdo;
    
    // Validate inputs
    if (empty($game_id)) {
        return ['success' => false, 'message' => 'Game is verplicht'];
    }
    
    if (empty($date) || strtotime($date) < time()) {
        return ['success' => false, 'message' => 'Datum moet in de toekomst liggen'];
    }
    
    if (empty($time) || preg_match('/^-/', $time)) {
        return ['success' => false, 'message' => 'Tijd moet positief zijn'];
    }
    
    // Insert schedule
    $stmt = $pdo->prepare("
        INSERT INTO Schedules (user_id, game_id, date, time, friends) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([$user_id, $game_id, $date, $time, $friends]);
        return ['success' => true, 'schedule_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Toevoegen mislukt: ' . $e->getMessage()];
    }
}

// Get user schedules
function getSchedules($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT s.schedule_id, s.game_id, g.titel AS game_titel, s.date, s.time, s.friends 
        FROM Schedules s
        JOIN Games g ON s.game_id = g.game_id
        WHERE s.user_id = ? AND s.date >= CURDATE()
        ORDER BY s.date, s.time
    ");
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll();
}

// Get schedules with sorting
function getSchedulesWithSorting($user_id, $sort_by = 'date', $sort_order = 'ASC') {
    global $pdo;
    
    $order_by = '';
    switch ($sort_by) {
        case 'date':
            $order_by = 's.date ' . $sort_order . ', s.time ' . $sort_order;
            break;
        case 'game':
            $order_by = 'g.titel ' . $sort_order;
            break;
        case 'time':
            $order_by = 's.time ' . $sort_order;
            break;
        default:
            $order_by = 's.date ASC, s.time ASC';
    }
    
    $stmt = $pdo->prepare("
        SELECT s.schedule_id, s.game_id, g.titel AS game_titel, s.date, s.time, s.friends 
        FROM Schedules s
        JOIN Games g ON s.game_id = g.game_id
        WHERE s.user_id = ? AND s.date >= CURDATE()
        ORDER BY $order_by
    ");
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll();
}

// Get schedule by ID
function getScheduleById($schedule_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT s.schedule_id, s.game_id, g.titel AS game_titel, s.date, s.time, s.friends 
        FROM Schedules s
        JOIN Games g ON s.game_id = g.game_id
        WHERE s.schedule_id = ? AND s.user_id = ?
    ");
    $stmt->execute([$schedule_id, $user_id]);
    
    if ($stmt->rowCount() != 1) {
        return false;
    }
    
    return $stmt->fetch();
}

// Edit schedule
function editSchedule($schedule_id, $user_id, $game_id, $date, $time, $friends = '') {
    global $pdo;
    
    // Validate inputs (same as add)
    if (empty($game_id)) {
        return ['success' => false, 'message' => 'Game is verplicht'];
    }
    
    if (empty($date) || strtotime($date) < time()) {
        return ['success' => false, 'message' => 'Datum moet in de toekomst liggen'];
    }
    
    if (empty($time) || preg_match('/^-/', $time)) {
        return ['success' => false, 'message' => 'Tijd moet positief zijn'];
    }
    
    // Check if user owns this schedule
    $stmt = $pdo->prepare("SELECT schedule_id FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$schedule_id, $user_id]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Je hebt geen rechten om dit schema te bewerken'];
    }
    
    // Update schedule
    $stmt = $pdo->prepare("
        UPDATE Schedules 
        SET game_id = ?, date = ?, time = ?, friends = ? 
        WHERE schedule_id = ? AND user_id = ?
    ");
    
    try {
        $stmt->execute([$game_id, $date, $time, $friends, $schedule_id, $user_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Bijwerken mislukt: ' . $e->getMessage()];
    }
}

// Delete schedule
function deleteSchedule($schedule_id, $user_id) {
    global $pdo;
    
    // Check if user owns this schedule
    $stmt = $pdo->prepare("SELECT schedule_id FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    $stmt->execute([$schedule_id, $user_id]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Je hebt geen rechten om dit schema te verwijderen'];
    }
    
    // Delete schedule
    $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
    
    try {
        $stmt->execute([$schedule_id, $user_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Verwijderen mislukt: ' . $e->getMessage()];
    }
}

/******************************
 * Event Functions
 ******************************/

// Add event
function addEvent($user_id, $title, $date, $time, $description = '', $reminder = '', $schedule_id = null, $shared_friends = []) {
    global $pdo;
    
    // Validate inputs
    if (empty(trim($title))) {
        return ['success' => false, 'message' => 'Titel is verplicht'];
    }
    
    if (strlen($title) > 100) {
        return ['success' => false, 'message' => 'Titel mag maximaal 100 tekens lang zijn'];
    }
    
    if (empty($date)) {
        return ['success' => false, 'message' => 'Datum is verplicht'];
    }
    
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$date_obj || $date_obj->format('Y-m-d') != $date) {
        return ['success' => false, 'message' => 'Ongeldige datum'];
    }
    
    // Check if date is in the future
    if (strtotime($date) < time()) {
        return ['success' => false, 'message' => 'Datum moet in de toekomst liggen'];
    }
    
    if (empty($time) || preg_match('/^-/', $time)) {
        return ['success' => false, 'message' => 'Tijd moet positief zijn'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert event
        $stmt = $pdo->prepare("
            INSERT INTO Events (user_id, title, date, time, description, reminder, schedule_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $title, $date, $time, $description, $reminder, $schedule_id]);
        
        $event_id = $pdo->lastInsertId();
        
        // Add shared friends
        if (!empty($shared_friends)) {
            $insert_shared = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
            
            foreach ($shared_friends as $friend_id) {
                $insert_shared->execute([$event_id, $friend_id]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        return ['success' => true, 'event_id' => $event_id];
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Toevoegen mislukt: ' . $e->getMessage()];
    }
}

// Get user events
function getEvents($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT e.event_id, e.user_id, e.title, e.date, e.time, e.description, e.reminder,
               e.schedule_id, g.titel AS game_titel
        FROM Events e
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        WHERE e.user_id = ? AND e.date >= CURDATE()
        ORDER BY e.date, e.time
    ");
    $stmt->execute([$user_id]);
    
    $events = $stmt->fetchAll();
    
    // Get shared users for each event
    foreach ($events as &$event) {
        $shared_stmt = $pdo->prepare("
            SELECT u.username 
            FROM EventUserMap eum
            JOIN Users u ON eum.friend_id = u.user_id
            WHERE eum.event_id = ?
        ");
        $shared_stmt->execute([$event['event_id']]);
        $shared_users = $shared_stmt->fetchAll(PDO::FETCH_COLUMN);
        $event['shared_with'] = $shared_users;
    }
    
    return $events;
}

// Get events with filtering and sorting
function getEventsWithFiltering($user_id, $sort_by = 'date', $sort_order = 'ASC', $filter_type = 'all', $search_query = '') {
    global $pdo;
    
    $order_by = '';
    switch ($sort_by) {
        case 'date':
            $order_by = 'e.date ' . $sort_order . ', e.time ' . $sort_order;
            break;
        case 'title':
            $order_by = 'e.title ' . $sort_order;
            break;
        case 'reminder':
            $order_by = 'e.reminder ' . $sort_order;
            break;
        default:
            $order_by = 'e.date ASC, e.time ASC';
    }
    
    $where_clause = 'e.user_id = ?';
    $params = [$user_id];
    
    if (!empty($search_query)) {
        $where_clause .= ' AND e.title LIKE ?';
        $params[] = '%' . $search_query . '%';
    }
    
    $stmt = $pdo->prepare("
        SELECT e.event_id, e.user_id, e.title, e.date, e.time, e.description, e.reminder,
               e.schedule_id, g.titel AS game_titel
        FROM Events e
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        WHERE $where_clause AND e.date >= CURDATE()
        ORDER BY $order_by
    ");
    $stmt->execute($params);
    
    $events = $stmt->fetchAll();
    
    // Get shared users for each event
    foreach ($events as &$event) {
        $shared_stmt = $pdo->prepare("
            SELECT u.username 
            FROM EventUserMap eum
            JOIN Users u ON eum.friend_id = u.user_id
            WHERE eum.event_id = ?
        ");
        $shared_stmt->execute([$event['event_id']]);
        $shared_users = $shared_stmt->fetchAll(PDO::FETCH_COLUMN);
        $event['shared_with'] = $shared_users;
    }
    
    return $events;
}

// Get user event statistics
function getUserEventStatistics($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
            SUM(CASE WHEN date < CURDATE() THEN 1 ELSE 0 END) as past_events,
            (SELECT COUNT(DISTINCT eum.event_id) FROM EventUserMap eum JOIN Events e ON eum.event_id = e.event_id WHERE e.user_id = ?) as shared_events
        FROM Events 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
    
    return $stmt->fetch();
}

// Get event by ID
function getEventById($event_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT e.event_id, e.user_id, e.title, e.date, e.time, e.description, e.reminder,
               e.schedule_id, g.titel AS game_titel
        FROM Events e
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        WHERE e.event_id = ? AND e.user_id = ?
    ");
    $stmt->execute([$event_id, $user_id]);
    
    if ($stmt->rowCount() != 1) {
        return false;
    }
    
    $event = $stmt->fetch();
    
    // Get shared users
    $shared_stmt = $pdo->prepare("
        SELECT u.user_id, u.username 
        FROM EventUserMap eum
        JOIN Users u ON eum.friend_id = u.user_id
        WHERE eum.event_id = ?
    ");
    $shared_stmt->execute([$event_id]);
    $event['shared_with'] = $shared_stmt->fetchAll();
    
    return $event;
}

// Edit event
function editEvent($event_id, $user_id, $title, $date, $time, $description = '', $reminder = '', $schedule_id = null, $shared_friends = []) {
    global $pdo;
    
    // Validate inputs (same as add)
    if (empty(trim($title))) {
        return ['success' => false, 'message' => 'Titel is verplicht'];
    }
    
    if (strlen($title) > 100) {
        return ['success' => false, 'message' => 'Titel mag maximaal 100 tekens lang zijn'];
    }
    
    if (empty($date)) {
        return ['success' => false, 'message' => 'Datum is verplicht'];
    }
    
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$date_obj || $date_obj->format('Y-m-d') != $date) {
        return ['success' => false, 'message' => 'Ongeldige datum'];
    }
    
    if (strtotime($date) < time()) {
        return ['success' => false, 'message' => 'Datum moet in de toekomst liggen'];
    }
    
    if (empty($time) || preg_match('/^-/', $time)) {
        return ['success' => false, 'message' => 'Tijd moet positief zijn'];
    }
    
    // Check if user owns this event
    $stmt = $pdo->prepare("SELECT event_id FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Je hebt geen rechten om dit evenement te bewerken'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update event
        $stmt = $pdo->prepare("
            UPDATE Events 
            SET title = ?, date = ?, time = ?, description = ?, reminder = ?, schedule_id = ? 
            WHERE event_id = ? AND user_id = ?
        ");
        $stmt->execute([$title, $date, $time, $description, $reminder, $schedule_id, $event_id, $user_id]);
        
        // Remove all shared friends
        $delete_shared = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
        $delete_shared->execute([$event_id]);
        
        // Add new shared friends
        if (!empty($shared_friends)) {
            $insert_shared = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id) VALUES (?, ?)");
            
            foreach ($shared_friends as $friend_id) {
                $insert_shared->execute([$event_id, $friend_id]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        return ['success' => true];
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Bijwerken mislukt: ' . $e->getMessage()];
    }
}

// Delete event
function deleteEvent($event_id, $user_id) {
    global $pdo;
    
    // Check if user owns this event
    $stmt = $pdo->prepare("SELECT event_id FROM Events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    
    if ($stmt->rowCount() != 1) {
        return ['success' => false, 'message' => 'Je hebt geen rechten om dit evenement te verwijderen'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete shared users
        $delete_shared = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
        $delete_shared->execute([$event_id]);
        
        // Delete event
        $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        return ['success' => true];
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Verwijderen mislukt: ' . $e->getMessage()];
    }
}

/******************************
 * Calendar Functions
 ******************************/

// Get calendar items (combined schedules and events)
function getCalendarItems($user_id) {
    $schedules = getSchedules($user_id);
    $events = getEvents($user_id);
    
    // Mark items with type
    foreach ($schedules as &$schedule) {
        $schedule['type'] = 'schedule';
    }
    
    foreach ($events as &$event) {
        $event['type'] = 'event';
    }
    
    // Merge and sort by date and time
    $calendar_items = array_merge($schedules, $events);
    
    usort($calendar_items, function($a, $b) {
        $date_compare = strtotime($a['date']) - strtotime($b['date']);
        if ($date_compare == 0) {
            return strtotime($a['time']) - strtotime($b['time']);
        }
        return $date_compare;
    });
    
    return $calendar_items;
}

/******************************
 * Helper Functions
 ******************************/

// Format date to Dutch format
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d-m-Y', $timestamp);
}

// Format time to 24-hour format
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Display error message
function displayError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

// Display success message
function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Validate input based on type
 */
function validateInput($input, $type) {
    switch ($type) {
        case 'date':
            $date_obj = DateTime::createFromFormat('Y-m-d', $input);
            if (!$date_obj || $date_obj->format('Y-m-d') !== $input) {
                return false;
            }
            // Check if date is valid and in future
            return strtotime($input) >= strtotime(date('Y-m-d'));
        case 'time':
            return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input) && !preg_match('/^-/', $input);
        case 'text':
            return !empty(trim($input)) && !preg_match('/^\s*$/', $input) && strlen($input) <= 100;
        default:
            return false;
    }
}
?>