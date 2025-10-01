<?php
/**
 * GamePlan Scheduler - Enhanced Professional Schedule Conflict Checker
 * Advanced Real-Time Conflict Detection with Comprehensive Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Conflict Detection System
 */

// Ensure strict error reporting for production quality
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to true in production with HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

// Include required dependencies
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

// Set proper JSON response header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

/**
 * Enhanced authentication and authorization check
 * Ensures user is properly logged in with valid session
 */
function validateUserAuthentication() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized access',
            'message' => 'U moet ingelogd zijn om conflicten te controleren.',
            'error_code' => 'AUTH_REQUIRED'
        ]);
        exit;
    }

    // Additional session validation
    if (!isset($_SESSION['username']) || !isset($_SESSION['session_token'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid session',
            'message' => 'Uw sessie is ongeldig. Log opnieuw in.',
            'error_code' => 'INVALID_SESSION'
        ]);
        exit;
    }

    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_destroy();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired',
            'message' => 'Uw sessie is verlopen. Log opnieuw in.',
            'error_code' => 'SESSION_EXPIRED'
        ]);
        exit;
    }

    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Validate HTTP request method
 * Only POST requests are allowed for security
 */
function validateRequestMethod() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'message' => 'Alleen POST verzoeken zijn toegestaan.',
            'error_code' => 'METHOD_NOT_ALLOWED',
            'allowed_methods' => ['POST']
        ]);
        exit;
    }
}

/**
 * Enhanced CSRF token validation with comprehensive security checks
 */
function validateCSRFToken() {
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    
    if (empty($csrf_token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'CSRF token missing',
            'message' => 'Beveiligingstoken ontbreekt.',
            'error_code' => 'CSRF_TOKEN_MISSING'
        ]);
        exit;
    }

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid CSRF token',
            'message' => 'Ongeldig beveiligingstoken.',
            'error_code' => 'CSRF_TOKEN_INVALID'
        ]);
        exit;
    }
}

/**
 * Advanced input validation with comprehensive data sanitization
 * Validates and sanitizes all user input according to project specifications
 */
function validateAndSanitizeInput() {
    // Get JSON input from request body
    $json_input = file_get_contents('php://input');
    if (empty($json_input)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No input data',
            'message' => 'Geen invoergegevens ontvangen.',
            'error_code' => 'NO_INPUT_DATA'
        ]);
        exit;
    }

    // Decode JSON input
    $input = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON',
            'message' => 'Ongeldige JSON-gegevens.',
            'error_code' => 'INVALID_JSON',
            'json_error' => json_last_error_msg()
        ]);
        exit;
    }

    // Validate required fields
    $required_fields = ['gameId', 'date', 'time'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields',
            'message' => 'Verplichte velden ontbreken: ' . implode(', ', $missing_fields),
            'error_code' => 'MISSING_FIELDS',
            'missing_fields' => $missing_fields
        ]);
        exit;
    }

    // Validate and sanitize game ID
    $game_id = filter_var($input['gameId'], FILTER_VALIDATE_INT);
    if ($game_id === false || $game_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid game ID',
            'message' => 'Ongeldig game ID. Moet een positief geheel getal zijn.',
            'error_code' => 'INVALID_GAME_ID'
        ]);
        exit;
    }

    // Validate date format and ensure it's in the future
    $date = trim($input['date']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid date format',
            'message' => 'Ongeldige datumnotatie. Gebruik YYYY-MM-DD.',
            'error_code' => 'INVALID_DATE_FORMAT'
        ]);
        exit;
    }

    // Check if date is valid and in the future
    $date_timestamp = strtotime($date);
    if ($date_timestamp === false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid date',
            'message' => 'Ongeldige datum.',
            'error_code' => 'INVALID_DATE'
        ]);
        exit;
    }

    if ($date_timestamp < strtotime(date('Y-m-d'))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Date in past',
            'message' => 'Datum moet in de toekomst liggen.',
            'error_code' => 'DATE_IN_PAST'
        ]);
        exit;
    }

    // Validate time format
    $time = trim($input['time']);
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid time format',
            'message' => 'Ongeldige tijdnotatie. Gebruik HH:MM.',
            'error_code' => 'INVALID_TIME_FORMAT'
        ]);
        exit;
    }

    // Check for negative time (edge case from test report #1004)
    if (strpos($time, '-') !== false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Negative time',
            'message' => 'Tijd mag niet negatief zijn.',
            'error_code' => 'NEGATIVE_TIME'
        ]);
        exit;
    }

    // Validate and sanitize friend IDs (optional)
    $friend_ids = [];
    if (isset($input['friendIds']) && is_array($input['friendIds'])) {
        foreach ($input['friendIds'] as $friend_id) {
            $validated_id = filter_var($friend_id, FILTER_VALIDATE_INT);
            if ($validated_id !== false && $validated_id > 0) {
                $friend_ids[] = $validated_id;
            }
        }
    }

    // Validate schedule ID (optional for editing)
    $schedule_id = null;
    if (isset($input['scheduleId']) && !empty($input['scheduleId'])) {
        $schedule_id = filter_var($input['scheduleId'], FILTER_VALIDATE_INT);
        if ($schedule_id === false || $schedule_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid schedule ID',
                'message' => 'Ongeldig schema ID.',
                'error_code' => 'INVALID_SCHEDULE_ID'
            ]);
            exit;
        }
    }

    return [
        'game_id' => $game_id,
        'date' => $date,
        'time' => $time,
        'friend_ids' => $friend_ids,
        'schedule_id' => $schedule_id
    ];
}

/**
 * Enhanced conflict detection with comprehensive analysis
 * Checks for scheduling conflicts based on user, friends, and time overlaps
 */
function checkScheduleConflicts($user_id, $game_id, $date, $time, $friend_ids = [], $exclude_schedule_id = null) {
    global $pdo;
    
    try {
        $conflicts = [
            'has_conflicts' => false,
            'user_conflicts' => [],
            'friend_conflicts' => [],
            'game_conflicts' => [],
            'time_conflicts' => [],
            'total_conflicts' => 0,
            'severity' => 'none', // none, low, medium, high
            'recommendations' => []
        ];

        // Parse time for overlap checking
        $check_time = strtotime($date . ' ' . $time);
        $time_window_start = $check_time - 1800; // 30 minutes before
        $time_window_end = $check_time + 1800;   // 30 minutes after

        // Check user's own schedule conflicts
        $user_conflicts_query = "
            SELECT s.schedule_id, s.date, s.time, g.titel as game_name,
                   CONCAT(s.date, ' ', s.time) as datetime_str
            FROM Schedules s
            LEFT JOIN Games g ON s.game_id = g.game_id
            WHERE s.user_id = :user_id 
                AND s.date = :date
                AND TIME(CONCAT(:date, ' ', s.time)) BETWEEN TIME(:time_start) AND TIME(:time_end)
                " . ($exclude_schedule_id ? "AND s.schedule_id != :exclude_id" : "") . "
            ORDER BY s.time
        ";

        $stmt = $pdo->prepare($user_conflicts_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':time_start', date('H:i:s', $time_window_start), PDO::PARAM_STR);
        $stmt->bindParam(':time_end', date('H:i:s', $time_window_end), PDO::PARAM_STR);
        
        if ($exclude_schedule_id) {
            $stmt->bindParam(':exclude_id', $exclude_schedule_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $user_schedule_conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($user_schedule_conflicts as $conflict) {
            $conflicts['user_conflicts'][] = [
                'type' => 'schedule_overlap',
                'schedule_id' => $conflict['schedule_id'],
                'game_name' => $conflict['game_name'],
                'date' => $conflict['date'],
                'time' => $conflict['time'],
                'message' => "U heeft al een schema voor {$conflict['game_name']} op {$conflict['date']} om {$conflict['time']}"
            ];
            $conflicts['has_conflicts'] = true;
            $conflicts['total_conflicts']++;
        }

        // Check friend conflicts if friend IDs provided
        if (!empty($friend_ids)) {
            $friend_placeholders = str_repeat('?,', count($friend_ids) - 1) . '?';
            
            $friend_conflicts_query = "
                SELECT s.schedule_id, s.user_id, u.username, s.date, s.time, 
                       g.titel as game_name, s.friends
                FROM Schedules s
                LEFT JOIN Users u ON s.user_id = u.user_id
                LEFT JOIN Games g ON s.game_id = g.game_id
                WHERE s.user_id IN ($friend_placeholders)
                    AND s.date = ?
                    AND TIME(CONCAT(?, ' ', s.time)) BETWEEN TIME(?) AND TIME(?)
                ORDER BY s.time, u.username
            ";

            $stmt = $pdo->prepare($friend_conflicts_query);
            $params = array_merge(
                $friend_ids,
                [$date, $date, date('H:i:s', $time_window_start), date('H:i:s', $time_window_end)]
            );
            $stmt->execute($params);
            $friend_schedule_conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($friend_schedule_conflicts as $conflict) {
                $conflicts['friend_conflicts'][] = [
                    'type' => 'friend_busy',
                    'user_id' => $conflict['user_id'],
                    'username' => $conflict['username'],
                    'schedule_id' => $conflict['schedule_id'],
                    'game_name' => $conflict['game_name'],
                    'date' => $conflict['date'],
                    'time' => $conflict['time'],
                    'message' => "{$conflict['username']} heeft al een schema voor {$conflict['game_name']} op {$conflict['date']} om {$conflict['time']}"
                ];
                $conflicts['has_conflicts'] = true;
                $conflicts['total_conflicts']++;
            }
        }

        // Check for same game conflicts (multiple sessions of same game)
        $game_conflicts_query = "
            SELECT s.schedule_id, s.date, s.time, g.titel as game_name,
                   COUNT(*) as concurrent_sessions
            FROM Schedules s
            LEFT JOIN Games g ON s.game_id = g.game_id
            WHERE (s.user_id = :user_id OR s.user_id IN (
                SELECT friend_user_id FROM Friends WHERE user_id = :user_id AND status = 'accepted'
            ))
                AND s.game_id = :game_id
                AND s.date = :date
                AND TIME(CONCAT(:date, ' ', s.time)) BETWEEN TIME(:time_start) AND TIME(:time_end)
                " . ($exclude_schedule_id ? "AND s.schedule_id != :exclude_id" : "") . "
            GROUP BY s.schedule_id, s.date, s.time, g.titel
            HAVING concurrent_sessions > 0
            ORDER BY s.time
        ";

        $stmt = $pdo->prepare($game_conflicts_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':game_id', $game_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':time_start', date('H:i:s', $time_window_start), PDO::PARAM_STR);
        $stmt->bindParam(':time_end', date('H:i:s', $time_window_end), PDO::PARAM_STR);
        
        if ($exclude_schedule_id) {
            $stmt->bindParam(':exclude_id', $exclude_schedule_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $same_game_conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($same_game_conflicts as $conflict) {
            $conflicts['game_conflicts'][] = [
                'type' => 'duplicate_game_session',
                'schedule_id' => $conflict['schedule_id'],
                'game_name' => $conflict['game_name'],
                'date' => $conflict['date'],
                'time' => $conflict['time'],
                'concurrent_sessions' => $conflict['concurrent_sessions'],
                'message' => "Er is al een {$conflict['game_name']} sessie gepland op {$conflict['date']} om {$conflict['time']}"
            ];
        }

        // Check for event conflicts
        $event_conflicts_query = "
            SELECT e.event_id, e.title, e.date, e.time
            FROM Events e
            WHERE e.user_id = :user_id
                AND e.date = :date
                AND TIME(CONCAT(:date, ' ', e.time)) BETWEEN TIME(:time_start) AND TIME(:time_end)
                AND e.status = 'upcoming'
            ORDER BY e.time
        ";

        $stmt = $pdo->prepare($event_conflicts_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':time_start', date('H:i:s', $time_window_start), PDO::PARAM_STR);
        $stmt->bindParam(':time_end', date('H:i:s', $time_window_end), PDO::PARAM_STR);
        $stmt->execute();
        $event_conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($event_conflicts as $conflict) {
            $conflicts['time_conflicts'][] = [
                'type' => 'event_overlap',
                'event_id' => $conflict['event_id'],
                'title' => $conflict['title'],
                'date' => $conflict['date'],
                'time' => $conflict['time'],
                'message' => "U heeft het evenement '{$conflict['title']}' op {$conflict['date']} om {$conflict['time']}"
            ];
            $conflicts['has_conflicts'] = true;
            $conflicts['total_conflicts']++;
        }

        // Determine conflict severity
        if ($conflicts['total_conflicts'] == 0) {
            $conflicts['severity'] = 'none';
        } elseif ($conflicts['total_conflicts'] <= 2) {
            $conflicts['severity'] = 'low';
        } elseif ($conflicts['total_conflicts'] <= 5) {
            $conflicts['severity'] = 'medium';
        } else {
            $conflicts['severity'] = 'high';
        }

        // Generate recommendations based on conflicts
        $conflicts['recommendations'] = generateRecommendations($conflicts, $date, $time);

        return $conflicts;

    } catch (PDOException $e) {
        error_log("Database error in checkScheduleConflicts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error',
            'message' => 'Er is een fout opgetreden bij het controleren van conflicten.',
            'error_code' => 'DATABASE_ERROR'
        ]);
        exit;
    } catch (Exception $e) {
        error_log("General error in checkScheduleConflicts: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal error',
            'message' => 'Er is een onverwachte fout opgetreden.',
            'error_code' => 'INTERNAL_ERROR'
        ]);
        exit;
    }
}

/**
 * Generate intelligent recommendations based on detected conflicts
 */
function generateRecommendations($conflicts, $date, $time) {
    $recommendations = [];

    if (!$conflicts['has_conflicts']) {
        $recommendations[] = [
            'type' => 'success',
            'message' => 'Geen conflicten gedetecteerd! Dit tijdstip is beschikbaar.',
            'icon' => 'check-circle'
        ];
        return $recommendations;
    }

    if (!empty($conflicts['user_conflicts'])) {
        $recommendations[] = [
            'type' => 'warning',
            'message' => 'U heeft al iets gepland op dit tijdstip. Overweeg een ander tijdstip te kiezen.',
            'icon' => 'exclamation-triangle',
            'suggestion' => 'Probeer 1-2 uur eerder of later.'
        ];
    }

    if (!empty($conflicts['friend_conflicts'])) {
        $busy_friends = array_column($conflicts['friend_conflicts'], 'username');
        $recommendations[] = [
            'type' => 'info',
            'message' => 'Sommige vrienden zijn bezet: ' . implode(', ', array_unique($busy_friends)),
            'icon' => 'users',
            'suggestion' => 'Kies andere vrienden of een ander tijdstip.'
        ];
    }

    if (!empty($conflicts['game_conflicts'])) {
        $recommendations[] = [
            'type' => 'info',
            'message' => 'Er is al een sessie van dit spel gepland.',
            'icon' => 'gamepad2',
            'suggestion' => 'Overweeg om deel te nemen aan de bestaande sessie.'
        ];
    }

    if ($conflicts['severity'] === 'high') {
        $recommendations[] = [
            'type' => 'danger',
            'message' => 'Veel conflicten gedetecteerd. Sterk aanbevolen om een ander tijdstip te kiezen.',
            'icon' => 'x-circle',
            'suggestion' => 'Bekijk de kalender voor beschikbare tijdslots.'
        ];
    }

    return $recommendations;
}

/**
 * Main execution flow with comprehensive error handling
 */
try {
    // Validate user authentication
    validateUserAuthentication();

    // Validate HTTP request method
    validateRequestMethod();

    // Validate CSRF token
    validateCSRFToken();

    // Validate and sanitize input
    $validated_input = validateAndSanitizeInput();

    // Extract validated data
    $user_id = $_SESSION['user_id'];
    $game_id = $validated_input['game_id'];
    $date = $validated_input['date'];
    $time = $validated_input['time'];
    $friend_ids = $validated_input['friend_ids'];
    $schedule_id = $validated_input['schedule_id'];

    // Check for schedule conflicts
    $conflicts = checkScheduleConflicts($user_id, $game_id, $date, $time, $friend_ids, $schedule_id);

    // Return successful response with conflict data
    echo json_encode([
        'success' => true,
        'conflicts' => $conflicts,
        'request_info' => [
            'game_id' => $game_id,
            'date' => $date,
            'time' => $time,
            'friend_count' => count($friend_ids),
            'check_timestamp' => date('Y-m-d H:i:s')
        ],
        'message' => $conflicts['has_conflicts'] 
            ? 'Conflicten gedetecteerd. Bekijk de details hieronder.' 
            : 'Geen conflicten gevonden. Dit tijdstip is beschikbaar!'
    ]);

} catch (Exception $e) {
    // Log unexpected errors
    error_log("Unexpected error in check_conflicts.php: " . $e->getMessage());
    
    // Return generic error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.',
        'error_code' => 'UNEXPECTED_ERROR'
    ]);
} finally {
    // Ensure database connection is closed properly
    if (isset($pdo)) {
        $pdo = null;
    }
}

/**
 * Additional utility functions for enhanced functionality
 */

/**
 * Get available time slots for a specific date and game
 * Helps users find alternative times when conflicts exist
 */
function getAvailableTimeSlots($user_id, $game_id, $date, $friend_ids = []) {
    global $pdo;
    
    $available_slots = [];
    $business_hours_start = 9;  // 9 AM
    $business_hours_end = 23;   // 11 PM
    
    try {
        for ($hour = $business_hours_start; $hour < $business_hours_end; $hour++) {
            $time_slot = sprintf('%02d:00', $hour);
            $conflicts = checkScheduleConflicts($user_id, $game_id, $date, $time_slot, $friend_ids);
            
            if (!$conflicts['has_conflicts']) {
                $available_slots[] = [
                    'time' => $time_slot,
                    'formatted_time' => date('H:i', strtotime($time_slot)),
                    'recommended' => true
                ];
            }
        }
        
        return $available_slots;
        
    } catch (Exception $e) {
        error_log("Error getting available time slots: " . $e->getMessage());
        return [];
    }
}

/**
 * Get friend availability for a specific date and time range
 * Helps determine which friends are available for group sessions
 */
function getFriendAvailability($user_id, $date, $time, $friend_ids = []) {
    global $pdo;
    
    if (empty($friend_ids)) {
        return [];
    }
    
    try {
        $friend_availability = [];
        $check_time = strtotime($date . ' ' . $time);
        $time_window_start = $check_time - 1800; // 30 minutes before
        $time_window_end = $check_time + 1800;   // 30 minutes after
        
        $friend_placeholders = str_repeat('?,', count($friend_ids) - 1) . '?';
        
        $availability_query = "
            SELECT u.user_id, u.username,
                   CASE 
                       WHEN s.schedule_id IS NOT NULL THEN 'busy'
                       WHEN e.event_id IS NOT NULL THEN 'event'
                       ELSE 'available'
                   END as status,
                   COALESCE(g.titel, e.title) as activity_name,
                   COALESCE(s.time, e.time) as activity_time
            FROM Users u
            LEFT JOIN Schedules s ON u.user_id = s.user_id 
                AND s.date = ? 
                AND TIME(CONCAT(?, ' ', s.time)) BETWEEN TIME(?) AND TIME(?)
            LEFT JOIN Games g ON s.game_id = g.game_id
            LEFT JOIN Events e ON u.user_id = e.user_id 
                AND e.date = ? 
                AND TIME(CONCAT(?, ' ', e.time)) BETWEEN TIME(?) AND TIME(?)
                AND e.status = 'upcoming'
            WHERE u.user_id IN ($friend_placeholders)
            ORDER BY u.username
        ";
        
        $stmt = $pdo->prepare($availability_query);
        $params = [
            $date, $date, 
            date('H:i:s', $time_window_start), 
            date('H:i:s', $time_window_end),
            $date, $date,
            date('H:i:s', $time_window_start), 
            date('H:i:s', $time_window_end)
        ];
        $params = array_merge($params, $friend_ids);
        
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $result) {
            $friend_availability[] = [
                'user_id' => $result['user_id'],
                'username' => $result['username'],
                'status' => $result['status'],
                'activity_name' => $result['activity_name'],
                'activity_time' => $result['activity_time'],
                'available' => $result['status'] === 'available'
            ];
        }
        
        return $friend_availability;
        
    } catch (Exception $e) {
        error_log("Error getting friend availability: " . $e->getMessage());
        return [];
    }
}
?>