<?php
session_start();
require 'db.php';

// Enhanced security configurations
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

/**
 * Advanced password hashing with Argon2ID for enterprise security
 * Implements state-of-the-art password security standards
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Enhanced user login with advanced brute force protection
 * Implements comprehensive security measures and activity logging
 */
function loginUser($email, $password) {
    global $pdo;
    
    try {
        // Check for brute force attempts with sliding window
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                              WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([get_client_ip()]);
        $attempts = $stmt->fetch()['attempts'];
        
        if ($attempts >= 5) {
            logSecurityEvent('login_blocked_brute_force', [
                'ip' => get_client_ip(),
                'email' => $email,
                'attempts' => $attempts
            ]);
            return ['success' => false, 'message' => 'Te veel inlogpogingen. Probeer over 15 minuten opnieuw.'];
        }
        
        // Enhanced user lookup with account status validation
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, account_status, 
                              last_login, failed_login_attempts 
                              FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && $user['account_status'] === 'active' && 
            password_verify($password, $user['password_hash'])) {
            
            // Clear failed attempts on successful login
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([get_client_ip()]);
            
            // Reset user failed attempts counter
            $stmt = $pdo->prepare("UPDATE Users SET failed_login_attempts = 0, 
                                  last_login = NOW(), last_activity = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Create secure session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = get_client_ip();
            session_regenerate_id(true);
            
            // Log successful login
            logUserActivity($user['user_id'], 'login', null, [
                'ip_address' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'login_method' => 'standard'
            ]);
            
            return ['success' => true, 'message' => 'Succesvol ingelogd', 'user' => $user];
            
        } else {
            // Record failed attempt with enhanced logging
            $stmt = $pdo->prepare("INSERT INTO login_attempts 
                                  (ip_address, email_attempted, user_agent, created_at) 
                                  VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                get_client_ip(), 
                $email, 
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            // Increment user failed attempts if user exists
            if ($user) {
                $stmt = $pdo->prepare("UPDATE Users SET failed_login_attempts = failed_login_attempts + 1 
                                      WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
            }
            
            return ['success' => false, 'message' => 'Ongeldige email of wachtwoord'];
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Er is een fout opgetreden bij het inloggen'];
    }
}

/**
 * Enhanced client IP detection with proxy support
 */
function get_client_ip() {
    $ipkeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 
               'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 
               'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipkeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Advanced CSRF protection with token validation
 */
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Comprehensive input validation system with advanced security
 */
function validateInput($input, $type, $options = []) {
    $input = trim($input);
    
    switch ($type) {
        case 'username':
            if (strlen($input) < 3 || strlen($input) > 50) return false;
            return preg_match('/^[a-zA-Z0-9_-]+$/', $input);
            
        case 'email':
            if (strlen($input) > 100) return false;
            $email = filter_var($input, FILTER_VALIDATE_EMAIL);
            if (!$email) return false;
            // Additional domain validation
            $domain = substr(strrchr($email, "@"), 1);
            return checkdnsrr($domain, "MX");
            
        case 'password':
            if (strlen($input) < 8 || strlen($input) > 128) return false;
            return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $input);
            
        case 'title':
            if (empty($input)) return false;
            // Fix #1001 - Enhanced whitespace validation
            if (preg_match('/^\s*$/', $input)) return false;
            if (strlen($input) > 100) return false;
            // Check for potentially harmful content
            if (preg_match('/[<>"\']/', $input)) return false;
            return true;
            
        case 'date':
            if (empty($input)) return false;
            $date = DateTime::createFromFormat('Y-m-d', $input);
            // Fix #1004 - Enhanced date validation
            if (!$date || $date->format('Y-m-d') !== $input) return false;
            if ($date < new DateTime('today')) return false;
            if ($date > new DateTime('+2 years')) return false;
            return true;
            
        case 'time':
            if (empty($input)) return false;
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input)) return false;
            return true;
            
        case 'description':
            // Fix #1004 - Enhanced description validation
            if (strlen($input) > 1000) return false;
            if (!empty($input) && preg_match('/^\s+$/', $input)) return false;
            return true;
            
        case 'games':
            // Fix #1001 - Enhanced games validation
            if (empty($input)) return false;
            if (preg_match('/^\s+$/', $input)) return false;
            if (strlen($input) > 500) return false;
            // Check for harmful content
            if (preg_match('/[<>]/', $input)) return false;
            return true;
            
        default:
            return !empty(trim($input));
    }
}

/**
 * Enhanced user profile management with comprehensive data
 */
function getProfile($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COUNT(DISTINCT f.friend_id) as friend_count, 
                   COUNT(DISTINCT s.schedule_id) as schedule_count, 
                   COUNT(DISTINCT e.event_id) as event_count,
                   (SELECT COUNT(*) FROM Friends f2 
                    WHERE f2.friend_user_id = u.user_id) as mutual_friends,
                   CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                        THEN 'online' ELSE 'offline' END as status
            FROM Users u 
            LEFT JOIN Friends f ON u.user_id = f.user_id 
            LEFT JOIN Schedules s ON u.user_id = s.user_id AND s.status = 'active'
            LEFT JOIN Events e ON u.user_id = e.user_id 
            WHERE u.user_id = :id AND u.account_status = 'active'
            GROUP BY u.user_id
        ");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Advanced friend management with comprehensive validation
 */
function addFriend($user_id, $friend_username) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Enhanced validation
        if (!validateInput($friend_username, 'username')) {
            return ['success' => false, 'message' => 'Ongeldige username format'];
        }
        
        // Find friend with comprehensive checks
        $stmt = $pdo->prepare("SELECT user_id, username, account_status, privacy_level 
                              FROM Users WHERE username = :username AND user_id != :user");
        $stmt->execute([':username' => $friend_username, ':user' => $user_id]);
        $friend = $stmt->fetch();
        
        if (!$friend || $friend['account_status'] !== 'active') {
            return ['success' => false, 'message' => 'Gebruiker niet gevonden of niet actief'];
        }
        
        $friend_id = $friend['user_id'];
        
        // Check existing friendship
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Friends WHERE 
                              (user_id = :user AND friend_user_id = :friend) OR 
                              (user_id = :friend AND friend_user_id = :user)");
        $stmt->execute([':user' => $user_id, ':friend' => $friend_id]);
        
        if ($stmt->fetch()['count'] > 0) {
            return ['success' => false, 'message' => 'Jullie zijn al vrienden'];
        }
        
        // Add bidirectional friendship with timestamps
        $stmt = $pdo->prepare("INSERT INTO Friends 
                              (user_id, friend_user_id, status, created_at) 
                              VALUES (?, ?, 'accepted', NOW())");
        $stmt->execute([$user_id, $friend_id]);
        $stmt->execute([$friend_id, $user_id]);
        
        // Log friendship creation
        logUserActivity($user_id, 'friend_added', $friend_id, [
            'friend_username' => $friend_username
        ]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Vriend succesvol toegevoegd'];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log("Error adding friend: " . $e->getMessage());
        return ['success' => false, 'message' => 'Er is een fout opgetreden'];
    }
}

/**
 * Enhanced friends retrieval with filtering and status
 */
function getFriends($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, f.created_at as friend_since,
                   CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                        THEN 'online' ELSE 'offline' END as status,
                   (SELECT COUNT(*) FROM Schedules s 
                    WHERE FIND_IN_SET(u.user_id, s.friends) AND s.user_id = :user_id) as shared_schedules,
                   (SELECT COUNT(*) FROM EventUserMap eum 
                    JOIN Events e ON eum.event_id = e.event_id 
                    WHERE eum.friend_id = u.user_id AND e.user_id = :user_id2) as shared_events
            FROM Friends f 
            JOIN Users u ON f.friend_user_id = u.user_id 
            WHERE f.user_id = :user_id3 AND u.account_status = 'active' AND f.status = 'accepted'
            ORDER BY u.last_activity DESC
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':user_id2' => $user_id,
            ':user_id3' => $user_id
        ]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting friends: " . $e->getMessage());
        return [];
    }
}

/**
 * Advanced friends filtering with search and sorting
 */
function getFriendsWithFiltering($user_id, $search_query = '', $status_filter = 'all', $sort_by = 'username') {
    global $pdo;
    
    try {
        $where_conditions = [];
        $params = [':user_id' => $user_id, ':user_id2' => $user_id, ':user_id3' => $user_id];
        
        // Search filter
        if (!empty($search_query)) {
            $where_conditions[] = "u.username LIKE :search";
            $params[':search'] = '%' . $search_query . '%';
        }
        
        // Status filter
        if ($status_filter === 'online') {
            $where_conditions[] = "u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        } elseif ($status_filter === 'offline') {
            $where_conditions[] = "u.last_activity <= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        }
        
        $where_sql = !empty($where_conditions) ? 'AND ' . implode(' AND ', $where_conditions) : '';
        
        // Sort mapping
        $sort_mapping = [
            'username' => 'u.username ASC',
            'status' => 'status DESC, u.username ASC',
            'last_activity' => 'u.last_activity DESC',
            'friend_since' => 'f.created_at DESC'
        ];
        $order_by = $sort_mapping[$sort_by] ?? 'u.username ASC';
        
        $sql = "
            SELECT u.*, f.created_at as friend_since,
                   CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                        THEN 'online' ELSE 'offline' END as status,
                   (SELECT COUNT(*) FROM Schedules s 
                    WHERE FIND_IN_SET(u.user_id, s.friends) AND s.user_id = :user_id) as shared_schedules,
                   (SELECT COUNT(*) FROM EventUserMap eum 
                    JOIN Events e ON eum.event_id = e.event_id 
                    WHERE eum.friend_id = u.user_id AND e.user_id = :user_id2) as shared_events,
                   (SELECT GROUP_CONCAT(g.titel SEPARATOR ', ') 
                    FROM UserGames ug JOIN Games g ON ug.game_id = g.game_id 
                    WHERE ug.user_id = u.user_id LIMIT 5) as favorite_games
            FROM Friends f 
            JOIN Users u ON f.friend_user_id = u.user_id 
            WHERE f.user_id = :user_id3 AND u.account_status = 'active' AND f.status = 'accepted'
            {$where_sql}
            ORDER BY {$order_by}
            LIMIT 50
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error filtering friends: " . $e->getMessage());
        return [];
    }
}

/**
 * Get comprehensive friend statistics
 */
function getFriendStatistics($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_friends,
                COUNT(CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                          THEN 1 END) as online_friends,
                COUNT(CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                          THEN 1 END) as recent_activity
            FROM Friends f 
            JOIN Users u ON f.friend_user_id = u.user_id 
            WHERE f.user_id = :user_id AND u.account_status = 'active' AND f.status = 'accepted'
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch() ?: ['total_friends' => 0, 'online_friends' => 0, 'recent_activity' => 0];
    } catch (Exception $e) {
        error_log("Error getting friend statistics: " . $e->getMessage());
        return ['total_friends' => 0, 'online_friends' => 0, 'recent_activity' => 0];
    }
}

/**
 * Enhanced schedule management with comprehensive validation
 */
function getSchedules($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, g.titel as game_titel, g.genre, g.description as game_description,
                   COUNT(CASE WHEN FIND_IN_SET(f.friend_user_id, s.friends) 
                             AND u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                         THEN 1 END) as online_friends_count
            FROM Schedules s 
            LEFT JOIN Games g ON s.game_id = g.game_id 
            LEFT JOIN Friends f ON f.user_id = s.user_id
            LEFT JOIN Users u ON f.friend_user_id = u.user_id
            WHERE s.user_id = :user_id AND s.status = 'active'
            GROUP BY s.schedule_id
            ORDER BY s.date ASC, s.time ASC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting schedules: " . $e->getMessage());
        return [];
    }
}

/**
 * Enhanced games retrieval with categories and ratings
 */
function getGames() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT *, 
                   (SELECT COUNT(*) FROM UserGames WHERE game_id = Games.game_id) as popularity_count
            FROM Games 
            WHERE status = 'active'
            ORDER BY popularity_count DESC, titel ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting games: " . $e->getMessage());
        return [];
    }
}

/**
 * Enhanced favorite games management
 */
function getFavoriteGames($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT g.*, ug.added_at, ug.play_hours, ug.skill_level
            FROM UserGames ug 
            JOIN Games g ON ug.game_id = g.game_id 
            WHERE ug.user_id = :user_id AND g.status = 'active'
            ORDER BY ug.added_at DESC, g.titel ASC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting favorite games: " . $e->getMessage());
        return [];
    }
}

/**
 * Enhanced add favorite game with duplicate prevention
 */
function addFavoriteGame($user_id, $game_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO UserGames (user_id, game_id, added_at) 
            VALUES (?, ?, NOW())
        ");
        $result = $stmt->execute([$user_id, $game_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            logUserActivity($user_id, 'game_added', $game_id);
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error adding favorite game: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced edit functions with comprehensive validation and logging
 */
function editSchedule($schedule_id, $update_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $friends_str = is_array($update_data['friends']) ? 
                      implode(',', array_filter($update_data['friends'], 'is_numeric')) : '';
        
        $stmt = $pdo->prepare("
            UPDATE Schedules 
            SET game_id = ?, date = ?, time = ?, friends = ?, 
                notes = ?, priority = ?, duration = ?, updated_at = NOW() 
            WHERE schedule_id = ?
        ");
        
        $result = $stmt->execute([
            $update_data['game_id'], 
            $update_data['date'], 
            $update_data['time'], 
            $friends_str,
            $update_data['notes'] ?? '',
            $update_data['priority'] ?? 'medium',
            $update_data['duration'] ?? null,
            $schedule_id
        ]);
        
        if ($result) {
            logUserActivity($_SESSION['user_id'] ?? 0, 'schedule_updated', $schedule_id, $update_data);
            $pdo->commit();
            return ['success' => true, 'message' => 'Schema succesvol bijgewerkt'];
        }
        
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Fout bij bijwerken schema'];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error editing schedule: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database fout bij bijwerken'];
    }
}

function editEvent($event_id, $update_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update main event
        $stmt = $pdo->prepare("
            UPDATE Events 
            SET title = ?, date = ?, time = ?, description = ?, reminder = ?, 
                schedule_id = ?, event_type = ?, max_participants = ?, updated_at = NOW() 
            WHERE event_id = ?
        ");
        
        $result = $stmt->execute([
            $update_data['title'],
            $update_data['date'],
            $update_data['time'],
            $update_data['description'] ?? '',
            $update_data['reminder'] ?? '',
            $update_data['schedule_id'] ?? null,
            $update_data['event_type'] ?? 'tournament',
            $update_data['max_participants'] ?? null,
            $event_id
        ]);
        
        if (!$result) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Fout bij bijwerken evenement'];
        }
        
        // Update shared friends
        $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
        $stmt->execute([$event_id]);
        
        if (!empty($update_data['shared_friends']) && is_array($update_data['shared_friends'])) {
            $stmt = $pdo->prepare("INSERT INTO EventUserMap (event_id, friend_id, shared_at) VALUES (?, ?, NOW())");
            foreach ($update_data['shared_friends'] as $friend_id) {
                if (is_numeric($friend_id)) {
                    $stmt->execute([$event_id, $friend_id]);
                }
            }
        }
        
        logUserActivity($_SESSION['user_id'] ?? 0, 'event_updated', $event_id, $update_data);
        $pdo->commit();
        return ['success' => true, 'message' => 'Evenement succesvol bijgewerkt'];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error editing event: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database fout bij bijwerken'];
    }
}

/**
 * Enhanced delete functions with comprehensive logging
 */
function deleteSchedule($schedule_id, $user_id = null) {
    global $pdo;
    
    try {
        $user_id = $user_id ?? $_SESSION['user_id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
        $result = $stmt->execute([$schedule_id, $user_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            logUserActivity($user_id, 'schedule_deleted', $schedule_id);
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error deleting schedule: " . $e->getMessage());
        return false;
    }
}

function deleteEvent($event_id, $user_id = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        $user_id = $user_id ?? $_SESSION['user_id'] ?? 0;
        
        // Delete shared mappings first
        $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = ?");
        $stmt->execute([$event_id]);
        
        // Delete the event
        $stmt = $pdo->prepare("DELETE FROM Events WHERE event_id = ? AND user_id = ?");
        $result = $stmt->execute([$event_id, $user_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            logUserActivity($user_id, 'event_deleted', $event_id);
            $pdo->commit();
            return true;
        }
        
        $pdo->rollBack();
        return false;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error deleting event: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced logout with comprehensive cleanup
 */
function logout() {
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        logUserActivity($user_id, 'logout');
    }
    
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(), '', 0, '/');
    session_regenerate_id(true);
    header("Location: login.php");
    exit;
}

/**
 * Enhanced activity retrieval for dashboard
 */
function getUpcomingActivities($user_id, $days = 7) {
    global $pdo;
    
    try {
        // Get schedules
        $stmt = $pdo->prepare("
            SELECT 'schedule' as type, s.schedule_id as id, g.titel as title, 
                   s.date, s.time, s.friends, g.genre, s.priority, s.notes as description
            FROM Schedules s 
            JOIN Games g ON s.game_id = g.game_id 
            WHERE s.user_id = :user AND s.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY) 
            AND s.status = 'active'
        ");
        $stmt->execute([':user' => $user_id, ':days' => $days]);
        $schedules = $stmt->fetchAll();
        
        // Get events
        $stmt = $pdo->prepare("
            SELECT 'event' as type, event_id as id, title, date, time, description, 
                   reminder, event_type, max_participants
            FROM Events 
            WHERE user_id = :user AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
        ");
        $stmt->execute([':user' => $user_id, ':days' => $days]);
        $events = $stmt->fetchAll();
        
        // Combine and sort
        $activities = array_merge($schedules, $events);
        usort($activities, function($a, $b) {
            $datetime_a = strtotime($a['date'] . ' ' . $a['time']);
            $datetime_b = strtotime($b['date'] . ' ' . $b['time']);
            return $datetime_a - $datetime_b;
        });
        
        return $activities;
        
    } catch (Exception $e) {
        error_log("Error getting upcoming activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Enhanced user statistics with comprehensive metrics
 */
function getUserStats($user_id) {
    global $pdo;
    
    try {
        $stats = [];
        
        // Friend statistics
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as friends, 
                   COUNT(CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
                             THEN 1 END) as active_friends
            FROM Friends f 
            JOIN Users u ON f.friend_user_id = u.user_id 
            WHERE f.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $friend_stats = $stmt->fetch();
        $stats['friends'] = $friend_stats['friends'];
        $stats['active_friends'] = $friend_stats['active_friends'];
        
        // Activity statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN s.date >= CURDATE() THEN 1 END) as upcoming_schedules,
                COUNT(CASE WHEN e.date >= CURDATE() THEN 1 END) as upcoming_events
            FROM (SELECT date FROM Schedules WHERE user_id = ? AND status = 'active') s
            FULL OUTER JOIN (SELECT date FROM Events WHERE user_id = ?) e ON 1=1
        ");
        $stmt->execute([$user_id, $user_id]);
        $activity_stats = $stmt->fetch();
        $stats['upcoming_events'] = $activity_stats['upcoming_events'] ?? 0;
        $stats['upcoming_schedules'] = $activity_stats['upcoming_schedules'] ?? 0;
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting user statistics: " . $e->getMessage());
        return ['friends' => 0, 'active_friends' => 0, 'upcoming_events' => 0, 'upcoming_schedules' => 0];
    }
}

/**
 * Advanced activity logging system
 */
function logUserActivity($user_id, $action, $target_id = null, $metadata = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_log (user_id, action, target_id, metadata, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $target_id,
            json_encode($metadata),
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging user activity: " . $e->getMessage());
    }
}

/**
 * Security event logging
 */
function logSecurityEvent($event_type, $data = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_log (event_type, data, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $event_type,
            json_encode($data),
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging security event: " . $e->getMessage());
    }
}

/**
 * Validate friends list for security
 */
function validateFriendsList($user_id, $friend_ids) {
    global $pdo;
    
    try {
        if (empty($friend_ids)) return [];
        
        $placeholders = str_repeat('?,', count($friend_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT friend_user_id 
            FROM Friends 
            WHERE user_id = ? AND friend_user_id IN ($placeholders)
        ");
        
        $stmt->execute(array_merge([$user_id], $friend_ids));
        return array_column($stmt->fetchAll(), 'friend_user_id');
        
    } catch (Exception $e) {
        error_log("Error validating friends list: " . $e->getMessage());
        return [];
    }
}

/**
 * Professional login check with session validation
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time']) > 1800) {
        logout();
        return false;
    }
    
    // Check IP consistency for security
    if (isset($_SESSION['ip_address']) && 
        $_SESSION['ip_address'] !== get_client_ip()) {
        logout();
        return false;
    }
    
    return true;
}

/**
 * Time ago helper function
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'net';
    if ($diff < 3600) return floor($diff / 60) . ' min geleden';
    if ($diff < 86400) return floor($diff / 3600) . ' uur geleden';
    if ($diff < 2592000) return floor($diff / 86400) . ' dagen geleden';
    
    return date('j M Y', $time);
}

/**
 * Get events with advanced filtering capabilities
 */
function getEventsWithFiltering($user_id, $sort_by = 'date', $sort_order = 'ASC', $filter_type = 'all', $search_query = '') {
    global $pdo;
    
    try {
        $where_conditions = ['e.user_id = :user_id'];
        $params = [':user_id' => $user_id];
        
        // Filter conditions
        switch ($filter_type) {
            case 'upcoming':
                $where_conditions[] = 'e.date >= CURDATE()';
                break;
            case 'past':
                $where_conditions[] = 'e.date < CURDATE()';
                break;
            case 'tournament':
                $where_conditions[] = "e.event_type = 'tournament'";
                break;
            case 'meetup':
                $where_conditions[] = "e.event_type = 'meetup'";
                break;
            case 'shared':
                $where_conditions[] = 'eum.friend_id IS NOT NULL';
                break;
        }
        
        // Search condition
        if (!empty($search_query)) {
            $where_conditions[] = '(e.title LIKE :search OR e.description LIKE :search)';
            $params[':search'] = '%' . $search_query . '%';
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Sort validation
        $valid_sorts = ['date', 'title', 'type', 'participants'];
        $sort_by = in_array($sort_by, $valid_sorts) ? $sort_by : 'date';
        $sort_order = in_array($sort_order, ['ASC', 'DESC']) ? $sort_order : 'ASC';
        
        $sort_mapping = [
            'date' => 'e.date, e.time',
            'title' => 'e.title',
            'type' => 'e.event_type, e.title',
            'participants' => 'participant_count, e.title'
        ];
        
        $order_by = $sort_mapping[$sort_by] . ' ' . $sort_order;
        
        $sql = "
            SELECT e.*, 
                   COUNT(DISTINCT eum.friend_id) as participant_count,
                   s.game_id, g.titel as linked_game_title
            FROM Events e
            LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
            LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
            LEFT JOIN Games g ON s.game_id = g.game_id
            {$where_sql}
            GROUP BY e.event_id
            ORDER BY {$order_by}
            LIMIT 100
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        // Get shared friends for each event
        foreach ($events as &$event) {
            $stmt = $pdo->prepare("
                SELECT u.user_id, u.username 
                FROM EventUserMap eum 
                JOIN Users u ON eum.friend_id = u.user_id 
                WHERE eum.event_id = ?
            ");
            $stmt->execute([$event['event_id']]);
            $event['shared_with'] = $stmt->fetchAll();
        }
        
        return $events;
        
    } catch (Exception $e) {
        error_log("Error getting events with filtering: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user event statistics
 */
function getUserEventStatistics($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_events,
                COUNT(CASE WHEN e.date >= CURDATE() THEN 1 END) as upcoming_events,
                COUNT(CASE WHEN e.date < CURDATE() THEN 1 END) as past_events,
                COUNT(CASE WHEN eum.friend_id IS NOT NULL THEN 1 END) as shared_events
            FROM Events e
            LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
            WHERE e.user_id = :user_id
        ");
        
        $stmt->execute([':user_id' => $user_id]);
        $stats = $stmt->fetch();
        
        return $stats ?: [
            'total_events' => 0,
            'upcoming_events' => 0, 
            'past_events' => 0,
            'shared_events' => 0
        ];
        
    } catch (Exception $e) {
        error_log("Error getting event statistics: " . $e->getMessage());
        return ['total_events' => 0, 'upcoming_events' => 0, 'past_events' => 0, 'shared_events' => 0];
    }
}
?>