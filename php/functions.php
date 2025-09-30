<?php
/**
 * GamePlan Scheduler - Professional Core Functions Library
 * 
 * Comprehensive business logic functions with enterprise-level validation,
 * security measures, error handling, and performance optimization.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0 - Professional Edition
 * @date 2025-09-30
 * @security Enterprise-grade implementation
 */

require_once 'db.php';

// ==================== AUTHENTICATION & USER MANAGEMENT ====================

/**
 * Register a new user with comprehensive validation and security
 * 
 * @param string $username Username (3-50 characters, alphanumeric + underscore/dash)
 * @param string $email Valid email address
 * @param string $password Password (min 8 chars, complexity requirements)
 * @return array Result with success/error information and user data
 */
function registerUser($username, $email, $password) {
    try {
        $db = getDB();
        
        // Comprehensive input validation
        $validation = validateUserRegistration($username, $email, $password);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Rate limiting check
        if (!RateLimiter::check('register')) {
            return ['success' => false, 'error' => 'Too many registration attempts. Please try again later.'];
        }
        
        $db->beginTransaction();
        
        try {
            // Check for existing username (case-insensitive)
            $stmt = $db->prepare("SELECT user_id FROM Users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $db->rollback();
                return ['success' => false, 'error' => 'Username already exists. Please choose a different username.'];
            }
            
            // Check for existing email
            $stmt = $db->prepare("SELECT user_id FROM Users WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $db->rollback();
                return ['success' => false, 'error' => 'Email address already registered. Please use a different email or try logging in.'];
            }
            
            // Hash password with strong algorithm
            $password_hash = hashPassword($password);
            $verification_token = generateSecureToken(32);
            
            // Insert new user
            $stmt = $db->prepare("
                INSERT INTO Users (username, email, password_hash, verification_token, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$username, $email, $password_hash, $verification_token]);
            
            $user_id = $db->lastInsertId();
            
            // Create notification for new user
            createNotification($user_id, 'system', 'Welcome to GamePlan!', 
                'Thanks for joining our gaming community! Start by adding your favorite games and connecting with friends.');
            
            $db->commit();
            
            // Log successful registration
            error_log("[" . date('Y-m-d H:i:s') . "] New user registered: $username (ID: $user_id)");
            
            return [
                'success' => true, 
                'message' => 'Registration successful! Welcome to GamePlan Scheduler.',
                'user_id' => $user_id,
                'username' => $username,
                'verification_token' => $verification_token
            ];
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed. Please try again later.'];
    }
}

/**
 * Authenticate user login with security measures
 * 
 * @param string $username Username or email
 * @param string $password Plain text password
 * @param bool $remember_me Remember login for extended period
 * @return array Result with success/error information
 */
function loginUser($username, $password, $remember_me = false) {
    try {
        $db = getDB();
        
        // Input validation
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Please enter both username and password.'];
        }
        
        // Rate limiting check
        if (!RateLimiter::check('login', $username)) {
            return ['success' => false, 'error' => 'Too many login attempts. Please try again in 5 minutes.'];
        }
        
        // Find user by username or email
        $stmt = $db->prepare("
            SELECT user_id, username, email, password_hash, is_active, failed_login_attempts, lockout_until 
            FROM Users 
            WHERE (LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)) AND is_active = 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Log failed attempt
            error_log("[" . date('Y-m-d H:i:s') . "] Failed login attempt - user not found: $username");
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }
        
        // Check account lockout
        if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $lockout_remaining = strtotime($user['lockout_until']) - time();
            return ['success' => false, 'error' => "Account temporarily locked. Try again in " . ceil($lockout_remaining / 60) . " minutes."];
        }
        
        // Verify password
        if (!verifyPassword($password, $user['password_hash'])) {
            // Increment failed login attempts
            $failed_attempts = $user['failed_login_attempts'] + 1;
            $lockout_until = null;
            
            if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
                $lockout_until = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            }
            
            $stmt = $db->prepare("
                UPDATE Users 
                SET failed_login_attempts = ?, lockout_until = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([$failed_attempts, $lockout_until, $user['user_id']]);
            
            error_log("[" . date('Y-m-d H:i:s') . "] Failed login attempt - invalid password: " . $user['username'] . " (Attempt $failed_attempts)");
            
            if ($lockout_until) {
                return ['success' => false, 'error' => 'Too many failed attempts. Account locked for 15 minutes.'];
            }
            
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }
        
        // Reset failed login attempts on successful login
        $stmt = $db->prepare("
            UPDATE Users 
            SET failed_login_attempts = 0, lockout_until = NULL, last_activity = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        
        // Start session and login user
        SessionManager::login($user['user_id'], $user['username'], $remember_me);
        
        // Log successful login
        error_log("[" . date('Y-m-d H:i:s') . "] Successful login: " . $user['username'] . " (ID: " . $user['user_id'] . ")");
        
        return [
            'success' => true, 
            'message' => 'Login successful! Welcome back, ' . $user['username'] . '.',
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Login failed. Please try again later.'];
    }
}

/**
 * Comprehensive user registration validation
 * 
 * @param string $username Username to validate
 * @param string $email Email to validate  
 * @param string $password Password to validate
 * @return array Validation result
 */
function validateUserRegistration($username, $email, $password) {
    // Username validation
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Username is required.'];
    }
    
    if (!validateUsername($username)) {
        return ['valid' => false, 'error' => 'Username must be 3-50 characters and contain only letters, numbers, underscores, and dashes.'];
    }
    
    // Check for reserved usernames
    $reserved = ['admin', 'administrator', 'root', 'system', 'gameplan', 'support', 'help', 'api', 'www'];
    if (in_array(strtolower($username), $reserved)) {
        return ['valid' => false, 'error' => 'This username is reserved. Please choose a different one.'];
    }
    
    // Email validation
    if (empty($email)) {
        return ['valid' => false, 'error' => 'Email address is required.'];
    }
    
    if (!validateEmail($email)) {
        return ['valid' => false, 'error' => 'Please enter a valid email address.'];
    }
    
    // Password validation
    if (empty($password)) {
        return ['valid' => false, 'error' => 'Password is required.'];
    }
    
    if (!validatePassword($password)) {
        return ['valid' => false, 'error' => 'Password must be at least 8 characters and contain uppercase, lowercase, and numbers.'];
    }
    
    // Check for common weak passwords
    $weak_passwords = ['password', '12345678', 'qwerty123', 'password123', 'admin123'];
    if (in_array(strtolower($password), $weak_passwords)) {
        return ['valid' => false, 'error' => 'Please choose a stronger password.'];
    }
    
    return ['valid' => true];
}

/**
 * Get user profile information
 * 
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function getUserProfile($user_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT user_id, username, email, created_at, last_activity, profile_picture, is_active
            FROM Users 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Error fetching user profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user profile information
 * 
 * @param int $user_id User ID
 * @param array $data Profile data to update
 * @return array Result
 */
function updateUserProfile($user_id, $data) {
    try {
        $db = getDB();
        
        $allowed_fields = ['username', 'email', 'profile_picture'];
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields) && !empty($value)) {
                if ($field === 'username' && !validateUsername($value)) {
                    return ['success' => false, 'error' => 'Invalid username format.'];
                }
                
                if ($field === 'email' && !validateEmail($value)) {
                    return ['success' => false, 'error' => 'Invalid email format.'];
                }
                
                $updates[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No valid data to update.'];
        }
        
        $params[] = $user_id;
        
        $stmt = $db->prepare("
            UPDATE Users 
            SET " . implode(', ', $updates) . ", last_activity = NOW() 
            WHERE user_id = ? AND is_active = 1
        ");
        
        if ($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Profile updated successfully.'];
        } else {
            return ['success' => false, 'error' => 'Failed to update profile.'];
        }
        
    } catch (Exception $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return ['success' => false, 'error' => 'Update failed. Please try again.'];
    }
}

// ==================== GAMES MANAGEMENT ====================

/**
 * Get all available games with optional filtering
 * 
 * @param array $filters Optional filters (category, search, limit)
 * @return array List of games
 */
function getAllGames($filters = []) {
    try {
        $db = getDB();
        
        $where_conditions = ["g.is_active = 1"];
        $params = [];
        
        // Add search filter
        if (!empty($filters['search'])) {
            $where_conditions[] = "(g.titel LIKE ? OR g.description LIKE ? OR g.category LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Add category filter
        if (!empty($filters['category'])) {
            $where_conditions[] = "g.category = ?";
            $params[] = $filters['category'];
        }
        
        $limit_clause = "";
        if (!empty($filters['limit']) && is_numeric($filters['limit'])) {
            $limit_clause = "LIMIT " . (int)$filters['limit'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT g.*, 
                   COUNT(ug.user_id) as user_count,
                   AVG(ug.play_time_hours) as avg_playtime
            FROM Games g
            LEFT JOIN UserGames ug ON g.game_id = ug.game_id
            WHERE $where_clause
            GROUP BY g.game_id
            ORDER BY g.popularity_score DESC, g.titel ASC
            $limit_clause
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error fetching games: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's favorite games
 * 
 * @param int $user_id User ID
 * @return array List of user's favorite games
 */
function getUserFavoriteGames($user_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT g.*, ug.added_at, ug.play_time_hours, ug.skill_level, ug.favorite
            FROM Games g
            INNER JOIN UserGames ug ON g.game_id = ug.game_id
            WHERE ug.user_id = ? AND g.is_active = 1
            ORDER BY ug.favorite DESC, ug.added_at DESC
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error fetching user favorite games: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a game to user's favorites
 * 
 * @param int $user_id User ID
 * @param int $game_id Game ID
 * @param array $preferences Optional preferences (skill_level, favorite)
 * @return array Result
 */
function addUserFavoriteGame($user_id, $game_id, $preferences = []) {
    try {
        $db = getDB();
        
        // Check if game exists and is active
        $stmt = $db->prepare("SELECT game_id FROM Games WHERE game_id = ? AND is_active = 1");
        $stmt->execute([$game_id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Game not found.'];
        }
        
        // Check if already in favorites
        $stmt = $db->prepare("SELECT user_id FROM UserGames WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$user_id, $game_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Game already in your favorites.'];
        }
        
        // Add to favorites
        $skill_level = $preferences['skill_level'] ?? 'Beginner';
        $favorite = $preferences['favorite'] ?? 0;
        
        $stmt = $db->prepare("
            INSERT INTO UserGames (user_id, game_id, skill_level, favorite, added_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$user_id, $game_id, $skill_level, $favorite])) {
            // Update game popularity
            $db->prepare("UPDATE Games SET popularity_score = popularity_score + 1 WHERE game_id = ?")->execute([$game_id]);
            
            return ['success' => true, 'message' => 'Game added to your favorites!'];
        } else {
            return ['success' => false, 'error' => 'Failed to add game to favorites.'];
        }
        
    } catch (Exception $e) {
        error_log("Error adding favorite game: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add game. Please try again.'];
    }
}

/**
 * Remove a game from user's favorites
 * 
 * @param int $user_id User ID
 * @param int $game_id Game ID
 * @return array Result
 */
function removeUserFavoriteGame($user_id, $game_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("DELETE FROM UserGames WHERE user_id = ? AND game_id = ?");
        
        if ($stmt->execute([$user_id, $game_id]) && $stmt->rowCount() > 0) {
            // Update game popularity
            $db->prepare("UPDATE Games SET popularity_score = GREATEST(0, popularity_score - 1) WHERE game_id = ?")->execute([$game_id]);
            
            return ['success' => true, 'message' => 'Game removed from favorites.'];
        } else {
            return ['success' => false, 'error' => 'Game not found in your favorites.'];
        }
        
    } catch (Exception $e) {
        error_log("Error removing favorite game: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to remove game. Please try again.'];
    }
}
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username is already taken'];
        }
        
        // Check for existing email
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email address is already registered'];
        }
        
        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        
        // Create user account
        $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        
        logEvent("New user registered: $username (ID: $userId)", 'INFO', ['email' => $email]);
        
        return [
            'success' => true, 
            'user_id' => $userId, 
            'message' => 'Account created successfully! You can now log in.'
        ];
        
    } catch (PDOException $e) {
        logEvent("User registration failed: " . $e->getMessage(), 'ERROR', ['username' => $username, 'email' => $email]);
        return ['success' => false, 'error' => 'Registration failed. Please try again later.'];
    }
}

/**
 * Authenticate user login with rate limiting
 * 
 * @param string $username Username or email
 * @param string $password Password
 * @return array Result with success/error information
 */
function loginUser($username, $password) {
    global $pdo;
    
    try {
        // Rate limiting check
        $identifier = $_SERVER['REMOTE_ADDR'] . '_' . $username;
        if (isRateLimited($identifier)) {
            return ['success' => false, 'error' => 'Too many login attempts. Please try again in 15 minutes.'];
        }
        
        // Input validation
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Please enter both username and password'];
        }
        
        // Find user by username or email
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, is_active FROM Users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            recordLoginAttempt($identifier, false);
            logEvent("Login attempt with invalid username: $username", 'WARNING');
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            recordLoginAttempt($identifier, false);
            logEvent("Login attempt with invalid password for user: " . $user['username'], 'WARNING');
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Successful login
        recordLoginAttempt($identifier, true);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['session_started'] = time();
        
        // Update last activity
        $stmt = $pdo->prepare("UPDATE Users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        
        logEvent("User logged in: " . $user['username'], 'INFO');
        
        return ['success' => true, 'user' => $user, 'message' => 'Login successful'];
        
    } catch (PDOException $e) {
        logEvent("Login error: " . $e->getMessage(), 'ERROR', ['username' => $username]);
        return ['success' => false, 'error' => 'Login failed. Please try again later.'];
    }
}

// ===================== PROFILE MANAGEMENT FUNCTIONS =====================

/**
 * Get all available games with caching
 * 
 * @return array List of games
 */
function getGames() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT game_id, titel, description, category FROM Games WHERE is_active = 1 ORDER BY category, titel");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logEvent("Error fetching games: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Get user's favorite games with detailed information
 * 
 * @param int $userId User ID
 * @return array List of favorite games
 */
function getFavoriteGames($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT g.game_id, g.titel, g.description, g.category, ug.added_at
            FROM Games g
            INNER JOIN UserGames ug ON g.game_id = ug.game_id
            WHERE ug.user_id = ? AND g.is_active = 1
            ORDER BY g.category, g.titel
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logEvent("Error fetching favorite games for user $userId: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Add game to user's favorites with validation
 * 
 * @param int $userId User ID
 * @param int $gameId Game ID
 * @return array Result array
 */
function addFavoriteGame($userId, $gameId) {
    global $pdo;
    
    try {
        // Validate inputs
        if (!is_numeric($userId) || !is_numeric($gameId)) {
            return ['success' => false, 'error' => 'Invalid user or game ID'];
        }
        
        // Check if game exists and is active
        $stmt = $pdo->prepare("SELECT titel FROM Games WHERE game_id = ? AND is_active = 1");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found'];
        }
        
        // Check if already in favorites
        $stmt = $pdo->prepare("SELECT 1 FROM UserGames WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$userId, $gameId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Game is already in your favorites'];
        }
        
        // Check favorite games limit (max 10)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserGames WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        if ($count >= 10) {
            return ['success' => false, 'error' => 'You can only have up to 10 favorite games'];
        }
        
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO UserGames (user_id, game_id, added_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $gameId]);
        
        logEvent("User $userId added game {$game['titel']} to favorites", 'INFO');
        
        return ['success' => true, 'message' => "Added {$game['titel']} to your favorites"];
        
    } catch (PDOException $e) {
        logEvent("Error adding favorite game: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'game_id' => $gameId]);
        return ['success' => false, 'error' => 'Failed to add game to favorites'];
    }
}

/**
 * Remove game from user's favorites
 * 
 * @param int $userId User ID
 * @param int $gameId Game ID
 * @return array Result array
 */
function removeFavoriteGame($userId, $gameId) {
    global $pdo;
    
    try {
        // Validate inputs
        if (!is_numeric($userId) || !is_numeric($gameId)) {
            return ['success' => false, 'error' => 'Invalid user or game ID'];
        }
        
        // Get game title for logging
        $stmt = $pdo->prepare("
            SELECT g.titel 
            FROM Games g 
            INNER JOIN UserGames ug ON g.game_id = ug.game_id 
            WHERE ug.user_id = ? AND ug.game_id = ?
        ");
        $stmt->execute([$userId, $gameId]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return ['success' => false, 'error' => 'Game not found in your favorites'];
        }
        
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$userId, $gameId]);
        
        if ($stmt->rowCount() > 0) {
            logEvent("User $userId removed game {$game['titel']} from favorites", 'INFO');
            return ['success' => true, 'message' => "Removed {$game['titel']} from your favorites"];
        } else {
            return ['success' => false, 'error' => 'Game was not in your favorites'];
        }
        
    } catch (PDOException $e) {
        logEvent("Error removing favorite game: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'game_id' => $gameId]);
        return ['success' => false, 'error' => 'Failed to remove game from favorites'];
    }
}

// ===================== FRIEND MANAGEMENT FUNCTIONS =====================

/**
 * Get user's friends list with online status
 * 
 * @param int $userId User ID
 * @return array List of friends with status
 */
function getFriends($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.user_id, 
                u.username, 
                u.email,
                u.last_activity,
                f.created_at as friendship_date,
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, CURRENT_TIMESTAMP) <= 5 THEN 1 
                    ELSE 0 
                END as is_online
            FROM Friends f
            INNER JOIN Users u ON u.user_id = f.friend_user_id
            WHERE f.user_id = ? AND f.status = 'accepted' AND u.is_active = 1
            ORDER BY is_online DESC, u.username ASC
        ");
        $stmt->execute([$userId]);
        
        $friends = $stmt->fetchAll();
        
        // Add formatted last activity
        foreach ($friends as &$friend) {
            $friend['last_activity_formatted'] = formatTimeAgo($friend['last_activity']);
            $friend['friendship_duration'] = formatTimeAgo($friend['friendship_date']);
        }
        
        return $friends;
        
    } catch (PDOException $e) {
        logEvent("Error fetching friends for user $userId: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Search users by username with pagination
 * 
 * @param string $searchTerm Search term
 * @param int $currentUserId Current user ID to exclude
 * @param int $limit Results limit
 * @return array Matching users
 */
function searchUsers($searchTerm, $currentUserId, $limit = 10) {
    global $pdo;
    
    try {
        // Sanitize search term
        $searchTerm = trim($searchTerm);
        if (strlen($searchTerm) < 2) {
            return [];
        }
        
        $searchPattern = '%' . $searchTerm . '%';
        
        $stmt = $pdo->prepare("
            SELECT 
                u.user_id, 
                u.username, 
                u.last_activity,
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, CURRENT_TIMESTAMP) <= 5 THEN 1 
                    ELSE 0 
                END as is_online,
                CASE 
                    WHEN f.friend_id IS NOT NULL THEN 1 
                    ELSE 0 
                END as is_friend
            FROM Users u
            LEFT JOIN Friends f ON (u.user_id = f.friend_user_id AND f.user_id = ? AND f.status = 'accepted')
            WHERE u.username LIKE ? 
                AND u.user_id != ? 
                AND u.is_active = 1
            ORDER BY is_friend DESC, is_online DESC, u.username ASC
            LIMIT ?
        ");
        $stmt->execute([$currentUserId, $searchPattern, $currentUserId, $limit]);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        logEvent("Error searching users: " . $e->getMessage(), 'ERROR', ['search_term' => $searchTerm]);
        return [];
    }
}

/**
 * Add a friend with validation
 * 
 * @param int $userId Current user ID
 * @param string $friendUsername Friend's username
 * @return array Result array
 */
function addFriend($userId, $friendUsername) {
    global $pdo;
    
    try {
        // Validate input
        if (empty(trim($friendUsername))) {
            return ['success' => false, 'error' => 'Please enter a username'];
        }
        
        // Find friend by username
        $stmt = $pdo->prepare("SELECT user_id, username FROM Users WHERE username = ? AND is_active = 1");
        $stmt->execute([$friendUsername]);
        $friend = $stmt->fetch();
        
        if (!$friend) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        $friendId = $friend['user_id'];
        
        // Prevent self-friending
        if ($userId == $friendId) {
            return ['success' => false, 'error' => 'You cannot add yourself as a friend'];
        }
        
        // Check if already friends
        $stmt = $pdo->prepare("SELECT status FROM Friends WHERE user_id = ? AND friend_user_id = ?");
        $stmt->execute([$userId, $friendId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if ($existing['status'] == 'accepted') {
                return ['success' => false, 'error' => 'You are already friends with this user'];
            } else {
                return ['success' => false, 'error' => 'Friend request already pending'];
            }
        }
        
        // Check friend limit (max 50 friends)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Friends WHERE user_id = ? AND status = 'accepted'");
        $stmt->execute([$userId]);
        $friendCount = $stmt->fetchColumn();
        
        if ($friendCount >= 50) {
            return ['success' => false, 'error' => 'You can only have up to 50 friends'];
        }
        
        // Add friendship
        $stmt = $pdo->prepare("INSERT INTO Friends (user_id, friend_user_id, status, created_at) VALUES (?, ?, 'accepted', CURRENT_TIMESTAMP)");
        $stmt->execute([$userId, $friendId]);
        
        logEvent("User $userId added {$friend['username']} as friend", 'INFO');
        
        return ['success' => true, 'message' => "Added {$friend['username']} as a friend"];
        
    } catch (PDOException $e) {
        logEvent("Error adding friend: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'friend_username' => $friendUsername]);
        return ['success' => false, 'error' => 'Failed to add friend'];
    }
}

/**
 * Remove a friend
 * 
 * @param int $userId Current user ID
 * @param int $friendId Friend's user ID
 * @return array Result array
 */
function removeFriend($userId, $friendId) {
    global $pdo;
    
    try {
        // Validate inputs
        if (!is_numeric($userId) || !is_numeric($friendId)) {
            return ['success' => false, 'error' => 'Invalid user ID'];
        }
        
        // Get friend's username for logging
        $stmt = $pdo->prepare("
            SELECT u.username 
            FROM Users u 
            INNER JOIN Friends f ON u.user_id = f.friend_user_id 
            WHERE f.user_id = ? AND f.friend_user_id = ?
        ");
        $stmt->execute([$userId, $friendId]);
        $friend = $stmt->fetch();
        
        if (!$friend) {
            return ['success' => false, 'error' => 'Friend not found'];
        }
        
        // Remove friendship
        $stmt = $pdo->prepare("DELETE FROM Friends WHERE user_id = ? AND friend_user_id = ?");
        $stmt->execute([$userId, $friendId]);
        
        if ($stmt->rowCount() > 0) {
            logEvent("User $userId removed {$friend['username']} from friends", 'INFO');
            return ['success' => true, 'message' => "Removed {$friend['username']} from your friends"];
        } else {
            return ['success' => false, 'error' => 'Friendship not found'];
        }
        
    } catch (PDOException $e) {
        logEvent("Error removing friend: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'friend_id' => $friendId]);
        return ['success' => false, 'error' => 'Failed to remove friend'];
    }
}

// ===================== SCHEDULE MANAGEMENT FUNCTIONS =====================

/**
 * Get user's schedules with detailed information
 * 
 * @param int $userId User ID
 * @param string $sortBy Sort field (date, game, time)
 * @param string $sortOrder Sort order (ASC, DESC)
 * @return array List of schedules
 */
function getSchedules($userId, $sortBy = 'date', $sortOrder = 'ASC') {
    global $pdo;
    
    try {
        // Validate sort parameters
        $allowedSortFields = ['date', 'time', 'game_titel', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'date';
        $sortOrder = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) ? strtoupper($sortOrder) : 'ASC';
        
        $stmt = $pdo->prepare("
            SELECT 
                s.schedule_id,
                s.user_id,
                s.game_id,
                g.titel as game_titel,
                g.category as game_category,
                s.date,
                s.time,
                s.friends,
                s.description,
                s.created_at,
                u.username as creator_name
            FROM Schedules s
            INNER JOIN Games g ON s.game_id = g.game_id
            INNER JOIN Users u ON s.user_id = u.user_id
            WHERE (s.user_id = ? OR s.user_id IN (
                SELECT friend_user_id FROM Friends WHERE user_id = ? AND status = 'accepted'
            ))
            AND s.date >= CURDATE()
            AND g.is_active = 1
            ORDER BY s.$sortBy $sortOrder, s.time ASC
        ");
        $stmt->execute([$userId, $userId]);
        
        $schedules = $stmt->fetchAll();
        
        // Process friend lists and add metadata
        foreach ($schedules as &$schedule) {
            $schedule['friend_names'] = [];
            $schedule['is_owner'] = ($schedule['user_id'] == $userId);
            $schedule['formatted_date'] = formatDate($schedule['date']);
            $schedule['formatted_time'] = formatTime($schedule['time']);
            
            if (!empty($schedule['friends'])) {
                $friendIds = array_filter(explode(',', $schedule['friends']), 'is_numeric');
                if (!empty($friendIds)) {
                    $placeholders = str_repeat('?,', count($friendIds) - 1) . '?';
                    $stmt = $pdo->prepare("SELECT username FROM Users WHERE user_id IN ($placeholders) AND is_active = 1");
                    $stmt->execute($friendIds);
                    $schedule['friend_names'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            }
        }
        
        return $schedules;
        
    } catch (PDOException $e) {
        logEvent("Error fetching schedules for user $userId: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Add a new schedule with comprehensive validation
 * 
 * @param int $userId User ID
 * @param int $gameId Game ID
 * @param string $date Date (Y-m-d format)
 * @param string $time Time (H:i format)
 * @param array $friendIds Array of friend IDs
 * @param string $description Optional description
 * @return array Result array
 */
function addSchedule($userId, $gameId, $date, $time, $friendIds = [], $description = '') {
    global $pdo;
    
    try {
        // Validate inputs
        if (!is_numeric($userId) || !is_numeric($gameId)) {
            return ['success' => false, 'error' => 'Invalid user or game ID'];
        }
        
        if (empty($date) || empty($time)) {
            return ['success' => false, 'error' => 'Date and time are required'];
        }
        
        // Validate date format and future date
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            return ['success' => false, 'error' => 'Invalid date format'];
        }
        
        if ($dateObj < new DateTime('today')) {
            return ['success' => false, 'error' => 'Schedule date must be today or in the future'];
        }
        
        // Validate time format
        $timeObj = DateTime::createFromFormat('H:i', $time);
        if (!$timeObj || $timeObj->format('H:i') !== $time) {
            return ['success' => false, 'error' => 'Invalid time format'];
        }
        
        // Check for negative time (additional validation)
        if (strpos($time, '-') === 0) {
            return ['success' => false, 'error' => 'Time cannot be negative'];
        }
        
        // Validate game exists
        $stmt = $pdo->prepare("SELECT titel FROM Games WHERE game_id = ? AND is_active = 1");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return ['success' => false, 'error' => 'Selected game not found'];
        }
        
        // Validate description length
        if (strlen($description) > 255) {
            return ['success' => false, 'error' => 'Description must be less than 255 characters'];
        }
        
        // Validate and filter friend IDs
        $validFriendIds = [];
        if (!empty($friendIds)) {
            foreach ($friendIds as $friendId) {
                if (is_numeric($friendId) && $friendId != $userId) {
                    // Verify friendship exists
                    $stmt = $pdo->prepare("SELECT 1 FROM Friends WHERE user_id = ? AND friend_user_id = ? AND status = 'accepted'");
                    $stmt->execute([$userId, $friendId]);
                    if ($stmt->fetch()) {
                        $validFriendIds[] = $friendId;
                    }
                }
            }
        }
        
        // Check for schedule conflicts (same user, date, time)
        $stmt = $pdo->prepare("SELECT schedule_id FROM Schedules WHERE user_id = ? AND date = ? AND time = ?");
        $stmt->execute([$userId, $date, $time]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'You already have a schedule at this date and time'];
        }
        
        // Create schedule
        $friendsString = !empty($validFriendIds) ? implode(',', $validFriendIds) : '';
        
        $stmt = $pdo->prepare("
            INSERT INTO Schedules (user_id, game_id, date, time, friends, description, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, $gameId, $date, $time, $friendsString, trim($description)]);
        
        $scheduleId = $pdo->lastInsertId();
        
        logEvent("User $userId created schedule for {$game['titel']} on $date at $time", 'INFO', ['schedule_id' => $scheduleId]);
        
        return [
            'success' => true, 
            'schedule_id' => $scheduleId, 
            'message' => "Schedule created for {$game['titel']} on " . formatDate($date) . " at " . formatTime($time)
        ];
        
    } catch (PDOException $e) {
        logEvent("Error creating schedule: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'game_id' => $gameId]);
        return ['success' => false, 'error' => 'Failed to create schedule'];
    }
}

/**
 * Update an existing schedule with validation
 * 
 * @param int $userId User ID
 * @param int $scheduleId Schedule ID
 * @param array $data Schedule data
 * @return array Result array
 */
function updateSchedule($userId, $scheduleId, $data) {
    global $pdo;
    
    try {
        // Validate inputs
        if (!is_numeric($userId) || !is_numeric($scheduleId)) {
            return ['success' => false, 'error' => 'Invalid user or schedule ID'];
        }
        
        // Check if schedule exists and belongs to user
        $stmt = $pdo->prepare("SELECT game_id, date, time, friends FROM Schedules WHERE schedule_id = ? AND user_id = ?");
        $stmt->execute([$scheduleId, $userId]);
        $schedule = $stmt->fetch();
        
        if (!$schedule) {
            return ['success' => false, 'error' => 'Schedule not found or you do not have permission to edit this schedule'];
        }
        
        // Validate date and time
        if (isset($data['date']) && isset($data['time'])) {
            $dateObj = DateTime::createFromFormat('Y-m-d', $data['date']);
            $timeObj = DateTime::createFromFormat('H:i', $data['time']);
            
            if (!$dateObj || $dateObj->format('Y-m-d') !== $data['date']) {
                return ['success' => false, 'error' => 'Invalid date format'];
            }
            
            if (!$timeObj || $timeObj->format('H:i') !== $data['time']) {
                return ['success' => false, 'error' => 'Invalid time format'];
            }
        }
        
        // Validate game ID if provided
        if (isset($data['game_id'])) {
            if (!is_numeric($data['game_id'])) {
                return ['success' => false, 'error' => 'Invalid game ID'];
            }
            
            // Check if game exists
            $stmt = $pdo->prepare("SELECT titel FROM Games WHERE game_id = ? AND is_active = 1");
            $stmt->execute([$data['game_id']]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return ['success' => false, 'error' => 'Selected game not found'];
            }
        }
        
        // Validate friends list
        $validFriendIds = [];
        if (isset($data['friends'])) {
            foreach ($data['friends'] as $friendId) {
                if (is_numeric($friendId) && $friendId != $userId) {
                    // Verify friendship exists
                    $stmt = $pdo->prepare("SELECT 1 FROM Friends WHERE user_id = ? AND friend_user_id = ? AND status = 'accepted'");
                    $stmt->execute([$userId, $friendId]);
                    if ($stmt->fetch()) {
                        $validFriendIds[] = $friendId;
                    }
                }
            }
        }
        
        // Update schedule
        $stmt = $pdo->prepare("
            UPDATE Schedules 
            SET 
                game_id = ?, 
                date = ?, 
                time = ?, 
                friends = ?, 
                description = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE schedule_id = ? AND user_id = ?
        ");
        $stmt->execute([
            $data['game_id'] ?? $schedule['game_id'],
            $data['date'] ?? $schedule['date'],
            $data['time'] ?? $schedule['time'],
            !empty($validFriendIds) ? implode(',', $validFriendIds) : $schedule['friends'],
            $data['description'] ?? $schedule['description'],
            $scheduleId,
            $userId
        ]);
        
        logEvent("User $userId updated schedule $scheduleId", 'INFO');
        
        return ['success' => true, 'message' => 'Schedule updated successfully'];
        
    } catch (PDOException $e) {
        logEvent("Error updating schedule: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'schedule_id' => $scheduleId]);
        return ['success' => false, 'error' => 'Failed to update schedule'];
    }
}

/**
 * Delete a schedule
 * 
 * @param int $userId User ID
 * @param int $scheduleId Schedule ID
 * @return array Result array
 */
function deleteSchedule($userId, $scheduleId) {
    global $pdo;
    
    try {
        // Validate inputs
        if (!is_numeric($userId) || !is_numeric($scheduleId)) {
            return ['success' => false, 'error' => 'Invalid user or schedule ID'];
        }
        
        // Delete schedule
        $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
        $stmt->execute([$scheduleId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            logEvent("User $userId deleted schedule $scheduleId", 'INFO');
            return ['success' => true, 'message' => 'Schedule deleted successfully'];
        } else {
            return ['success' => false, 'error' => 'Schedule not found or you do not have permission to delete this schedule'];
        }
        
    } catch (PDOException $e) {
        logEvent("Error deleting schedule: " . $e->getMessage(), 'ERROR', ['user_id' => $userId, 'schedule_id' => $scheduleId]);
        return ['success' => false, 'error' => 'Failed to delete schedule'];
    }
}

// ===================== EVENT MANAGEMENT FUNCTIONS =====================

/**
 * Create new event
 * 
 * @param int $userId User ID (creator)
 * @param array $data Event data
 * @return array Result array
 */
function createEvent($userId, $data) {
    global $pdo;
    
    try {
        // Validate required fields
        if (empty($data['title']) || empty($data['event_date'])) {
            return ['success' => false, 'error' => 'Title and date are required'];
        }
        
        // Validate date format
        $eventDate = DateTime::createFromFormat('Y-m-d H:i', $data['event_date']);
        if (!$eventDate) {
            return ['success' => false, 'error' => 'Invalid date format'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO Events (creator_id, title, description, event_date, max_participants, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $data['title'],
            $data['description'] ?? '',
            $eventDate->format('Y-m-d H:i:s'),
            $data['max_participants'] ?? 10
        ]);
        
        $eventId = $pdo->lastInsertId();
        
        // Add creator as participant
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, user_id, status, joined_at) VALUES (?, ?, 'confirmed', NOW())");
        $stmt->execute([$eventId, $userId]);
        
        logEvent("Event created: ID $eventId by User $userId");
        
        return ['success' => true, 'event_id' => $eventId, 'message' => 'Event created successfully'];
        
    } catch (PDOException $e) {
        error_log("Create event error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to create event'];
    }
}

/**
 * Get events (public or user's events)
 * 
 * @param int $userId User ID (optional, for filtering user's events)
 * @param bool $includeParticipants Include participant information
 * @return array Array of events
 */
function getEvents($userId = null, $includeParticipants = false) {
    global $pdo;
    
    try {
        if ($userId) {
            // Get events user is participating in
            $sql = "
                SELECT DISTINCT e.*, u.username as creator_name
                FROM Events e
                JOIN Users u ON u.user_id = e.creator_id
                LEFT JOIN EventUserMap eum ON eum.event_id = e.event_id
                WHERE e.creator_id = ? OR (eum.user_id = ? AND eum.status = 'confirmed')
                ORDER BY e.event_date ASC
            ";
            $params = [$userId, $userId];
        } else {
            // Get all future events
            $sql = "
                SELECT e.*, u.username as creator_name
                FROM Events e
                JOIN Users u ON u.user_id = e.creator_id
                WHERE e.event_date > NOW()
                ORDER BY e.event_date ASC
            ";
            $params = [];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        if ($includeParticipants) {
            foreach ($events as &$event) {
                $event['participants'] = getEventParticipants($event['event_id']);
            }
        }
        
        return $events;
        
    } catch (PDOException $e) {
        error_log("Get events error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get event participants
 * 
 * @param int $eventId Event ID
 * @return array Array of participants
 */
function getEventParticipants($eventId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, eum.status, eum.joined_at
            FROM EventUserMap eum
            JOIN Users u ON u.user_id = eum.user_id
            WHERE eum.event_id = ?
            ORDER BY eum.joined_at ASC
        ");
        $stmt->execute([$eventId]);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get participants error: " . $e->getMessage());
        return [];
    }
}

/**
 * Join event
 * 
 * @param int $eventId Event ID
 * @param int $userId User ID
 * @return array Result array
 */
function joinEvent($eventId, $userId) {
    global $pdo;
    
    try {
        // Check if already joined
        $stmt = $pdo->prepare("SELECT * FROM EventUserMap WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$eventId, $userId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Already joined this event'];
        }
        
        // Check if event is full
        $stmt = $pdo->prepare("
            SELECT e.max_participants, COUNT(eum.user_id) as current_participants
            FROM Events e
            LEFT JOIN EventUserMap eum ON eum.event_id = e.event_id AND eum.status = 'confirmed'
            WHERE e.event_id = ?
            GROUP BY e.event_id
        ");
        $stmt->execute([$eventId]);
        $eventInfo = $stmt->fetch();
        
        if ($eventInfo && $eventInfo['current_participants'] >= $eventInfo['max_participants']) {
            return ['success' => false, 'error' => 'Event is full'];
        }
        
        // Join event
        $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, user_id, status, joined_at) VALUES (?, ?, 'confirmed', NOW())");
        $stmt->execute([$eventId, $userId]);
        
        logEvent("User joined event: User $userId joined Event $eventId");
        
        return ['success' => true, 'message' => 'Successfully joined event'];
        
    } catch (PDOException $e) {
        error_log("Join event error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to join event'];
    }
}

// ===================== UTILITY FUNCTIONS =====================

/**
 * Get dashboard statistics for user
 * 
 * @param int $userId User ID
 * @return array Statistics array
 */
function getDashboardStats($userId) {
    global $pdo;
    
    try {
        $stats = [];
        
        // Count friends
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Friends WHERE (user_id = ? OR friend_user_id = ?) AND status = 'accepted'");
        $stmt->execute([$userId, $userId]);
        $stats['friends'] = $stmt->fetchColumn();
        
        // Count games
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM UserGames WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['games'] = $stmt->fetchColumn();
        
        // Count upcoming schedules
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Schedules WHERE user_id = ? AND scheduled_time > NOW() AND status = 'scheduled'");
        $stmt->execute([$userId]);
        $stats['schedules'] = $stmt->fetchColumn();
        
        // Count upcoming events
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.event_id) as count 
            FROM Events e
            LEFT JOIN EventUserMap eum ON eum.event_id = e.event_id
            WHERE (e.creator_id = ? OR (eum.user_id = ? AND eum.status = 'confirmed'))
            AND e.event_date > NOW()
        ");
        $stmt->execute([$userId, $userId]);
        $stats['events'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        return ['friends' => 0, 'games' => 0, 'schedules' => 0, 'events' => 0];
    }
}

/**
 * Get upcoming activities for dashboard
 * 
 * @param int $userId User ID
 * @param int $limit Number of activities to return
 * @return array Array of upcoming activities
 */
function getUpcomingActivities($userId, $limit = 5) {
    global $pdo;
    
    try {
        $activities = [];
        
        // Get upcoming schedules
        $stmt = $pdo->prepare("
            SELECT 'schedule' as type, s.schedule_id as id, g.name as title, s.scheduled_time as date_time, s.description
            FROM Schedules s
            JOIN Games g ON g.game_id = s.game_id
            WHERE s.user_id = ? AND s.scheduled_time > NOW() AND s.status = 'scheduled'
            ORDER BY s.scheduled_time ASC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        $activities = array_merge($activities, $stmt->fetchAll());
        
        // Get upcoming events
        $stmt = $pdo->prepare("
            SELECT 'event' as type, e.event_id as id, e.title, e.event_date as date_time, e.description
            FROM Events e
            LEFT JOIN EventUserMap eum ON eum.event_id = e.event_id
            WHERE (e.creator_id = ? OR (eum.user_id = ? AND eum.status = 'confirmed'))
            AND e.event_date > NOW()
            ORDER BY e.event_date ASC
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $limit]);
        $activities = array_merge($activities, $stmt->fetchAll());
        
        // Sort by date and limit
        usort($activities, function($a, $b) {
            return strtotime($a['date_time']) - strtotime($b['date_time']);
        });
        
        return array_slice($activities, 0, $limit);
        
    } catch (PDOException $e) {
        error_log("Get upcoming activities error: " . $e->getMessage());
        return [];
    }
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate($date, $format = 'j M Y, H:i') {
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Check if user owns resource
 * 
 * @param int $userId User ID
 * @param string $table Table name
 * @param string $idColumn ID column name
 * @param int $resourceId Resource ID
 * @return bool True if user owns resource
 */
function userOwnsResource($userId, $table, $idColumn, $resourceId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM $table WHERE $idColumn = ?");
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();
        
        return $resource && $resource['user_id'] == $userId;
        
    } catch (PDOException $e) {
        error_log("Check ownership error: " . $e->getMessage());
        return false;
    }
}
?>