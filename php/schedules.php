<?php
/**
 * GamePlan Scheduler - Professional Schedule Management System
 * Advanced scheduling system with calendar integration and gaming-focused features
 * 
 * @author Harsha Kanaparthi
 * @version 2.1 Professional Edition
 * @date September 30, 2025
 * @description Complete schedule management with CRUD operations, calendar views, and collaboration
 */

// Enable comprehensive error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'db.php';
require_once 'functions.php';

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, redirect if not
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// Get current user information
$currentUser = getCurrentUser();
$user_id = $currentUser['user_id'];

// Update user activity
updateUserActivity();

// Initialize variables
$error_message = '';
$success_message = '';
$schedules = [];
$user_games = [];
$view_mode = $_GET['view'] ?? 'month';
$current_date = $_GET['date'] ?? date('Y-m-d');

try {
    $db = getDBConnection();
    
    // Get user's games for schedule creation
    $stmt = $db->prepare("
        SELECT g.game_id, g.titel as title 
        FROM Games g
        JOIN UserGames ug ON g.game_id = ug.game_id
        WHERE ug.user_id = ? AND g.is_active = 1
        ORDER BY g.titel
    ");
    $stmt->execute([$user_id]);
    $user_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's schedules based on view mode
    $date_filter = getDateFilterForView($current_date, $view_mode);
    
    $stmt = $db->prepare("
        SELECT s.*, g.titel as game_title, g.category as game_category,
               (SELECT COUNT(*) FROM ScheduleParticipants sp WHERE sp.schedule_id = s.schedule_id AND sp.status = 'accepted') as participant_count
        FROM Schedules s
        LEFT JOIN Games g ON s.game_id = g.game_id
        WHERE s.user_id = ? AND s.scheduled_date BETWEEN ? AND ?
        ORDER BY s.scheduled_date ASC, s.start_time ASC
    ");
    $stmt->execute([$user_id, $date_filter['start'], $date_filter['end']]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Schedule data error: " . $e->getMessage());
    $error_message = "Unable to load schedule data. Please try again.";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Protection
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Security token validation failed. Please try again.');
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_schedule':
                $result = createSchedule($user_id, $_POST);
                break;
                
            case 'update_schedule':
                $result = updateSchedule($user_id, $_POST);
                break;
                
            case 'delete_schedule':
                $result = deleteSchedule($user_id, $_POST['schedule_id']);
                break;
                
            case 'share_schedule':
                $result = shareSchedule($user_id, $_POST);
                break;
                
            default:
                throw new Exception('Invalid action specified.');
        }
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Refresh page to show updated data
            header("Location: schedules.php?view=$view_mode&date=$current_date&updated=success");
            exit;
        } else {
            $error_message = $result['message'];
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Schedule operation error for user $user_id: " . $error_message);
    }
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_schedule':
                $schedule_id = $_GET['schedule_id'] ?? 0;
                echo json_encode(getScheduleDetails($user_id, $schedule_id));
                break;
                
            case 'get_calendar_events':
                $start = $_GET['start'] ?? '';
                $end = $_GET['end'] ?? '';
                echo json_encode(getCalendarEvents($user_id, $start, $end));
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle success message from redirect
if (isset($_GET['updated']) && $_GET['updated'] === 'success') {
    $success_message = 'Schedule updated successfully!';
}

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();

/**
 * Get date filter based on view mode
 */
function getDateFilterForView($current_date, $view_mode) {
    $date = new DateTime($current_date);
    
    switch ($view_mode) {
        case 'week':
            $start = clone $date;
            $start->modify('monday this week');
            $end = clone $start;
            $end->modify('+6 days');
            break;
            
        case 'day':
            $start = clone $date;
            $end = clone $date;
            break;
            
        case 'month':
        default:
            $start = clone $date;
            $start->modify('first day of this month');
            $end = clone $date;
            $end->modify('last day of this month');
            break;
    }
    
    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d')
    ];
}

/**
 * Create a new schedule
 */
function createSchedule($user_id, $post_data, $template_id = null) {
    try {
        $db = getDBConnection();
        
        // Start transaction for possible template linking
        $db->beginTransaction();
        
        // Check if using template and it hasn't been used for this date
        if (!empty($template_id)) {
            $stmt = $db->prepare("SELECT template_id FROM TemplateSchedules 
                                WHERE template_id = ? AND generated_for_date = ?");
            $stmt->execute([$template_id, $post_data['scheduled_date']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'This template has already been used for the selected date.'];
            }
        }

        // Validate and sanitize input
        $title = sanitizeInput($post_data['title'] ?? '');
        $description = sanitizeInput($post_data['description'] ?? '');
        $game_id = intval($post_data['game_id'] ?? 0);
        $scheduled_date = sanitizeInput($post_data['scheduled_date'] ?? '');
        $start_time = sanitizeInput($post_data['start_time'] ?? '');
        $end_time = sanitizeInput($post_data['end_time'] ?? '');
        $is_recurring = isset($post_data['is_recurring']) ? 1 : 0;
        $recurrence_pattern = sanitizeInput($post_data['recurrence_pattern'] ?? '');
        $max_participants = intval($post_data['max_participants'] ?? 0);
        $is_public = isset($post_data['is_public']) ? 1 : 0;
        
        // Validation
        if (empty($title) || empty($scheduled_date) || empty($start_time) || empty($end_time)) {
            return ['success' => false, 'message' => 'Please fill in all required fields.'];
        }
        
        // Validate date and time
        if (strtotime($scheduled_date) === false) {
            return ['success' => false, 'message' => 'Please enter a valid date.'];
        }
        
        if (strtotime($start_time) === false || strtotime($end_time) === false) {
            return ['success' => false, 'message' => 'Please enter valid times.'];
        }
        
        if (strtotime($end_time) <= strtotime($start_time)) {
            return ['success' => false, 'message' => 'End time must be after start time.'];
        }
        
        // Check for conflicts
        $conflict_check = checkScheduleConflict($user_id, $scheduled_date, $start_time, $end_time);
        if (!$conflict_check['success']) {
            return $conflict_check;
        }
        
        // Create schedule
        $stmt = $db->prepare("
            INSERT INTO Schedules (user_id, title, description, game_id, scheduled_date, 
                                 start_time, end_time, is_recurring, recurrence_pattern, 
                                 max_participants, is_public, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $user_id, $title, $description, $game_id ?: null, $scheduled_date,
            $start_time, $end_time, $is_recurring, $recurrence_pattern ?: null,
            $max_participants ?: null, $is_public
        ]);
        
        if ($result) {
            $schedule_id = $db->lastInsertId();
            
            // Handle recurring schedules
            if ($is_recurring && !empty($recurrence_pattern)) {
                createRecurringSchedules($schedule_id, $post_data);
            }
            
            // If this is from a template, link it
            if (!empty($template_id)) {
                $stmt = $db->prepare("INSERT INTO TemplateSchedules (template_id, schedule_id, generated_for_date)
                                    VALUES (?, ?, ?)");
                $stmt->execute([$template_id, $schedule_id, $scheduled_date]);
            }

            $db->commit();
            return ['success' => true, 'message' => 'Schedule created successfully!', 'schedule_id' => $schedule_id];
        } else {
            $db->rollBack();
            return ['success' => false, 'message' => 'Failed to create schedule. Please try again.'];
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Create schedule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while creating the schedule.'];
    }
}

/**
 * Update an existing schedule
 */
function updateSchedule($user_id, $post_data) {
    try {
        $db = getDBConnection();
        
        $schedule_id = intval($post_data['schedule_id'] ?? 0);
        $title = sanitizeInput($post_data['title'] ?? '');
        $description = sanitizeInput($post_data['description'] ?? '');
        $game_id = intval($post_data['game_id'] ?? 0);
        $scheduled_date = sanitizeInput($post_data['scheduled_date'] ?? '');
        $start_time = sanitizeInput($post_data['start_time'] ?? '');
        $end_time = sanitizeInput($post_data['end_time'] ?? '');
        $max_participants = intval($post_data['max_participants'] ?? 0);
        $is_public = isset($post_data['is_public']) ? 1 : 0;
        
        // Verify ownership
        $stmt = $db->prepare("SELECT user_id FROM Schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $schedule = $stmt->fetch();
        
        if (!$schedule || $schedule['user_id'] != $user_id) {
            return ['success' => false, 'message' => 'Schedule not found or access denied.'];
        }
        
        // Validation
        if (empty($title) || empty($scheduled_date) || empty($start_time) || empty($end_time)) {
            return ['success' => false, 'message' => 'Please fill in all required fields.'];
        }
        
        // Check for conflicts (excluding current schedule)
        $conflict_check = checkScheduleConflict($user_id, $scheduled_date, $start_time, $end_time, $schedule_id);
        if (!$conflict_check['success']) {
            return $conflict_check;
        }
        
        // Update schedule
        $stmt = $db->prepare("
            UPDATE Schedules 
            SET title = ?, description = ?, game_id = ?, scheduled_date = ?, 
                start_time = ?, end_time = ?, max_participants = ?, is_public = ?, updated_at = NOW()
            WHERE schedule_id = ? AND user_id = ?
        ");
        
        $result = $stmt->execute([
            $title, $description, $game_id ?: null, $scheduled_date,
            $start_time, $end_time, $max_participants ?: null, $is_public,
            $schedule_id, $user_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Schedule updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to update schedule. Please try again.'];
        }
        
    } catch (Exception $e) {
        error_log("Update schedule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the schedule.'];
    }
}

/**
 * Delete a schedule
 */
function deleteSchedule($user_id, $schedule_id) {
    try {
        $db = getDBConnection();
        
        // Verify ownership
        $stmt = $db->prepare("SELECT user_id FROM Schedules WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $schedule = $stmt->fetch();
        
        if (!$schedule || $schedule['user_id'] != $user_id) {
            return ['success' => false, 'message' => 'Schedule not found or access denied.'];
        }
        
        // Delete schedule (cascade will handle participants)
        $stmt = $db->prepare("DELETE FROM Schedules WHERE schedule_id = ? AND user_id = ?");
        $result = $stmt->execute([$schedule_id, $user_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Schedule deleted successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete schedule. Please try again.'];
        }
        
    } catch (Exception $e) {
        error_log("Delete schedule error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting the schedule.'];
    }
}

/**
 * Check for schedule conflicts
 */
function checkScheduleConflict($user_id, $date, $start_time, $end_time, $exclude_id = null) {
    try {
        $db = getDBConnection();
        
        $sql = "
            SELECT schedule_id, title 
            FROM Schedules 
            WHERE user_id = ? AND scheduled_date = ? 
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
        ";
        
        $params = [$user_id, $date, $start_time, $start_time, $end_time, $end_time];
        
        if ($exclude_id) {
            $sql .= " AND schedule_id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $conflict = $stmt->fetch();
        
        if ($conflict) {
            return ['success' => false, 'message' => "Time conflict with existing schedule: {$conflict['title']}"];
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Conflict check error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to check for conflicts.'];
    }
}

/**
 * Get schedule details for editing
 */
function getScheduleDetails($user_id, $schedule_id) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("
            SELECT 
                s.*,
                g.titel as game_title,
                t.template_id,
                t.name as template_name,
                ts.generated_for_date
            FROM Schedules s
            LEFT JOIN Games g ON s.game_id = g.game_id
            LEFT JOIN TemplateSchedules ts ON s.schedule_id = ts.schedule_id
            LEFT JOIN ScheduleTemplates t ON ts.template_id = t.template_id
            WHERE s.schedule_id = ? AND s.user_id = ?
        ");
        $stmt->execute([$schedule_id, $user_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule) {
            return ['success' => true, 'schedule' => $schedule];
        } else {
            return ['success' => false, 'message' => 'Schedule not found.'];
        }
        
    } catch (Exception $e) {
        error_log("Get schedule details error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to load schedule details.'];
    }
}

/**
 * Get calendar events for FullCalendar
 */
function getCalendarEvents($user_id, $start, $end) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("
            SELECT s.schedule_id, s.title, s.scheduled_date, s.start_time, s.end_time,
                   s.is_public, g.titel as game_title, g.category as game_category
            FROM Schedules s
            LEFT JOIN Games g ON s.game_id = g.game_id
            WHERE s.user_id = ? AND s.scheduled_date BETWEEN ? AND ?
            ORDER BY s.scheduled_date, s.start_time
        ");
        $stmt->execute([$user_id, $start, $end]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $events = [];
        foreach ($schedules as $schedule) {
            $events[] = [
                'id' => $schedule['schedule_id'],
                'title' => $schedule['title'],
                'start' => $schedule['scheduled_date'] . 'T' . $schedule['start_time'],
                'end' => $schedule['scheduled_date'] . 'T' . $schedule['end_time'],
                'backgroundColor' => $schedule['is_public'] ? '#28a745' : '#6f42c1',
                'borderColor' => $schedule['is_public'] ? '#28a745' : '#6f42c1',
                'extendedProps' => [
                    'game' => $schedule['game_title'],
                    'category' => $schedule['game_category'],
                    'isPublic' => $schedule['is_public']
                ]
            ];
        }
        
        return ['success' => true, 'events' => $events];
        
    } catch (Exception $e) {
        error_log("Get calendar events error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to load calendar events.'];
    }
}

/**
 * Create recurring schedules
 */
function createRecurringSchedules($parent_schedule_id, $post_data) {
    try {
        $db = getDBConnection();
        
        // Get parent schedule details
        $stmt = $db->prepare("SELECT * FROM Schedules WHERE schedule_id = ?");
        $stmt->execute([$parent_schedule_id]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$parent) return false;
        
        $pattern = $post_data['recurrence_pattern'];
        $start_date = new DateTime($parent['scheduled_date']);
        $end_limit = new DateTime();
        $end_limit->modify('+3 months'); // Limit to 3 months
        
        $stmt = $db->prepare("
            INSERT INTO Schedules (user_id, title, description, game_id, scheduled_date, 
                                 start_time, end_time, is_recurring, recurrence_pattern, 
                                 max_participants, is_public, parent_schedule_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $current_date = clone $start_date;
        $created_count = 0;
        
        while ($current_date <= $end_limit && $created_count < 20) { // Max 20 recurring instances
            switch ($pattern) {
                case 'daily':
                    $current_date->modify('+1 day');
                    break;
                case 'weekly':
                    $current_date->modify('+1 week');
                    break;
                case 'monthly':
                    $current_date->modify('+1 month');
                    break;
                default:
                    break 2; // Exit while loop
            }
            
            if ($current_date <= $end_limit) {
                $stmt->execute([
                    $parent['user_id'], $parent['title'], $parent['description'], 
                    $parent['game_id'], $current_date->format('Y-m-d'),
                    $parent['start_time'], $parent['end_time'], 1, $pattern,
                    $parent['max_participants'], $parent['is_public'], $parent_schedule_id
                ]);
                $created_count++;
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Create recurring schedules error: " . $e->getMessage());
        return false;
    }
}

/**
 * Format schedule time for display
 */
function formatScheduleTime($date, $start_time, $end_time) {
    $date_obj = new DateTime($date);
    $start_obj = new DateTime($start_time);
    $end_obj = new DateTime($end_time);
    
    return [
        'date' => $date_obj->format('M j, Y'),
        'time' => $start_obj->format('g:i A') . ' - ' . $end_obj->format('g:i A'),
        'day' => $date_obj->format('l')
    ];
}

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GamePlan Scheduler - Manage your gaming schedules with advanced calendar features">
    <meta name="keywords" content="gaming, schedules, calendar, planning, events">
    <meta name="author" content="Harsha Kanaparthi">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <title>Schedule Management - GamePlan Scheduler | Plan Your Gaming Sessions</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    
    <style>
        :root {
            --gameplan-primary: #6f42c1;
            --gameplan-secondary: #e83e8c;
            --gameplan-dark: #0d1117;
            --gameplan-light: #f8f9fa;
            --gameplan-success: #198754;
            --gameplan-danger: #dc3545;
            --gameplan-warning: #ffc107;
            --gameplan-info: #0dcaf0;
            --gameplan-sidebar: #1a1a2e;
            --gameplan-card: rgba(255, 255, 255, 0.95);
        }
        
        body {
            background: linear-gradient(135deg, var(--gameplan-dark) 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .schedule-container {
            background: var(--gameplan-card);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin: 2rem 0;
        }
        
        .schedule-header {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .schedule-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 60%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(15deg);
            z-index: 1;
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            border: none;
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.4);
            color: white;
        }
        
        .btn-outline-custom {
            border: 2px solid var(--gameplan-primary);
            color: var(--gameplan-primary);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-custom:hover {
            background: var(--gameplan-primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .view-controls {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .view-controls .btn-group .btn {
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .schedule-card {
            background: white;
            border-radius: 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .schedule-card-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .schedule-card-body {
            padding: 1.5rem;
        }
        
        .game-badge {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .time-badge {
            background: var(--gameplan-info);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .participants-info {
            background: rgba(111, 66, 193, 0.1);
            border-left: 4px solid var(--gameplan-primary);
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .fc-theme-standard .fc-toolbar {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .fc-theme-standard .fc-toolbar .fc-button {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .fc-theme-standard .fc-toolbar .fc-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .fc-theme-standard .fc-toolbar .fc-button:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--gameplan-primary);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--gameplan-primary), var(--gameplan-secondary));
            color: white;
            border-radius: 20px 20px 0 0;
        }
        
        @media (max-width: 768px) {
            .schedule-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .schedule-header {
                padding: 1.5rem;
                border-radius: 15px 15px 0 0;
            }
            
            .view-controls {
                padding: 0.75rem;
            }
            
            .schedule-card-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="py-4">
    <div class="container-fluid">
        <!-- Navigation Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../index.php" class="text-white text-decoration-none">
                        <i class="bi bi-house me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active text-white" aria-current="page">Schedule Management</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-12">
                <div class="schedule-container">
                    <!-- Schedule Header -->
                    <div class="schedule-header">
                        <div class="header-content">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h1 class="mb-2">
                                        <i class="bi bi-calendar-event me-3"></i>
                                        Schedule Management
                                    </h1>
                                    <p class="mb-0 opacity-75">Plan and organize your gaming sessions with advanced scheduling tools</p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
                                        <i class="bi bi-plus-circle me-2"></i>New Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Content -->
                    <div class="p-4">
                        <!-- Flash Messages -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- View Controls -->
                        <div class="view-controls">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="btn-group" role="group" aria-label="View modes">
                                        <a href="?view=day&date=<?php echo $current_date; ?>" 
                                           class="btn <?php echo $view_mode === 'day' ? 'btn-custom' : 'btn-outline-custom'; ?>">
                                            <i class="bi bi-calendar-day me-1"></i>Day
                                        </a>
                                        <a href="?view=week&date=<?php echo $current_date; ?>" 
                                           class="btn <?php echo $view_mode === 'week' ? 'btn-custom' : 'btn-outline-custom'; ?>">
                                            <i class="bi bi-calendar-week me-1"></i>Week
                                        </a>
                                        <a href="?view=month&date=<?php echo $current_date; ?>" 
                                           class="btn <?php echo $view_mode === 'month' ? 'btn-custom' : 'btn-outline-custom'; ?>">
                                            <i class="bi bi-calendar-month me-1"></i>Month
                                        </a>
                                        <a href="?view=calendar&date=<?php echo $current_date; ?>" 
                                           class="btn <?php echo $view_mode === 'calendar' ? 'btn-custom' : 'btn-outline-custom'; ?>">
                                            <i class="bi bi-calendar3 me-1"></i>Calendar
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <input type="date" class="form-control d-inline-block w-auto" 
                                           value="<?php echo $current_date; ?>" 
                                           onchange="window.location.href='?view=<?php echo $view_mode; ?>&date='+this.value">
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Content Based on View Mode -->
                        <?php if ($view_mode === 'calendar'): ?>
                            <!-- Calendar View -->
                            <div class="calendar-container">
                                <div id="calendar"></div>
                            </div>
                        <?php else: ?>
                            <!-- List View -->
                            <div class="schedules-list">
                                <?php if (!empty($schedules)): ?>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <?php $time_info = formatScheduleTime($schedule['scheduled_date'], $schedule['start_time'], $schedule['end_time']); ?>
                                        <div class="schedule-card">
                                            <div class="schedule-card-header">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($schedule['title']); ?></h5>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="time-badge">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?php echo $time_info['time']; ?>
                                                            </span>
                                                            <?php if ($schedule['game_title']): ?>
                                                                <span class="game-badge">
                                                                    <i class="bi bi-controller me-1"></i>
                                                                    <?php echo htmlspecialchars($schedule['game_title']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($schedule['is_public']): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="bi bi-globe me-1"></i>Public
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 text-md-end">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                    onclick="editSchedule(<?php echo $schedule['schedule_id']; ?>)">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                    onclick="deleteSchedule(<?php echo $schedule['schedule_id']; ?>)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="schedule-card-body">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <p class="mb-2">
                                                            <i class="bi bi-calendar-date me-2"></i>
                                                            <strong><?php echo $time_info['day']; ?>, <?php echo $time_info['date']; ?></strong>
                                                        </p>
                                                        <?php if ($schedule['template_id']): ?>
                                                            <p class="mb-2">
                                                                <i class="bi bi-repeat me-2"></i>
                                                                <span class="text-info">
                                                                    From template: <?php echo htmlspecialchars($schedule['template_name']); ?>
                                                                </span>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($schedule['description']): ?>
                                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($schedule['description']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <?php if ($schedule['max_participants']): ?>
                                                            <div class="participants-info">
                                                                <small class="fw-semibold">Participants</small>
                                                                <div>
                                                                    <i class="bi bi-people me-1"></i>
                                                                    <?php echo $schedule['participant_count']; ?> / <?php echo $schedule['max_participants']; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Empty State -->
                                    <div class="empty-state">
                                        <i class="bi bi-calendar-x"></i>
                                        <h4>No Schedules Found</h4>
                                        <p>You haven't created any schedules for this time period yet.</p>
                                        <button type="button" class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
                                            <i class="bi bi-plus-circle me-2"></i>Create Your First Schedule
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Back to Dashboard Button -->
                <div class="text-center mt-4">
                    <a href="../index.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Schedule Modal -->
    <div class="modal fade" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createScheduleModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Create New Schedule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="schedules.php" id="createScheduleForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="create_schedule">
                    <input type="hidden" name="template_id" id="template_id" value="">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label fw-semibold">
                                    Schedule Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="Enter schedule title...">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="game_id" class="form-label fw-semibold">Game</label>
                                <select class="form-select" id="game_id" name="game_id">
                                    <option value="">Select a game (optional)</option>
                                    <?php foreach ($user_games as $game): ?>
                                        <option value="<?php echo $game['game_id']; ?>">
                                            <?php echo htmlspecialchars($game['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Describe your gaming session..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="scheduled_date" class="form-label fw-semibold">
                                    Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label fw-semibold">
                                    Start Time <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label fw-semibold">
                                    End Time <span class="text-danger">*</span>
                                </label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_participants" class="form-label fw-semibold">Max Participants</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants" 
                                       min="1" max="100" placeholder="Optional">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1">
                                    <label class="form-check-label" for="is_public">
                                        <i class="bi bi-globe me-1"></i>Make schedule public
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1">
                                    <label class="form-check-label" for="is_recurring">
                                        <i class="bi bi-arrow-repeat me-1"></i>Recurring schedule
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3" id="recurrence_options" style="display: none;">
                                <select class="form-select" id="recurrence_pattern" name="recurrence_pattern">
                                    <option value="">Select pattern</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">
                            <i class="bi bi-check-circle me-2"></i>Create Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Schedule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="schedules.php" id="editScheduleForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_schedule">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    
                    <div class="modal-body" id="editScheduleBody">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">
                            <i class="bi bi-check-circle me-2"></i>Update Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize calendar if in calendar view
            <?php if ($view_mode === 'calendar'): ?>
            initializeCalendar();
            <?php endif; ?>
            
            // Set default date to today for new schedules
            const todayDate = new Date().toISOString().split('T')[0];
            const scheduledDateInput = document.getElementById('scheduled_date');
            if (scheduledDateInput && !scheduledDateInput.value) {
                scheduledDateInput.value = todayDate;
            }
            
            // Recurring schedule toggle
            const isRecurringCheckbox = document.getElementById('is_recurring');
            const recurrenceOptions = document.getElementById('recurrence_options');
            
            if (isRecurringCheckbox) {
                isRecurringCheckbox.addEventListener('change', function() {
                    recurrenceOptions.style.display = this.checked ? 'block' : 'none';
                });
            }
            
            // Form validation
            const createForm = document.getElementById('createScheduleForm');
            if (createForm) {
                createForm.addEventListener('submit', function(e) {
                    const startTime = document.getElementById('start_time').value;
                    const endTime = document.getElementById('end_time').value;
                    
                    if (startTime && endTime && startTime >= endTime) {
                        e.preventDefault();
                        alert('End time must be after start time.');
                        return false;
                    }
                });
            }
            
            // Auto-dismiss alerts
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // Initialize FullCalendar
        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch(`schedules.php?ajax=1&action=get_calendar_events&start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                successCallback(data.events);
                            } else {
                                failureCallback(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading calendar events:', error);
                            failureCallback(error);
                        });
                },
                eventClick: function(info) {
                    editSchedule(info.event.id);
                },
                dateClick: function(info) {
                    // Pre-fill create form with clicked date
                    const scheduledDateInput = document.getElementById('scheduled_date');
                    if (scheduledDateInput) {
                        scheduledDateInput.value = info.dateStr;
                    }
                    
                    const createModal = new bootstrap.Modal(document.getElementById('createScheduleModal'));
                    createModal.show();
                }
            });
            
            calendar.render();
        }
        
        // Edit schedule function
        function editSchedule(scheduleId) {
            fetch(`schedules.php?ajax=1&action=get_schedule&schedule_id=${scheduleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditForm(data.schedule);
                        const editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                        editModal.show();
                    } else {
                        alert(data.message || 'Error loading schedule details');
                    }
                })
                .catch(error => {
                    console.error('Error loading schedule:', error);
                    alert('Error loading schedule details');
                });
        }
        
        // Populate edit form
        function populateEditForm(schedule) {
            document.getElementById('edit_schedule_id').value = schedule.schedule_id;
            
            const editBody = document.getElementById('editScheduleBody');
            editBody.innerHTML = `
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="edit_title" class="form-label fw-semibold">
                            Schedule Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_title" name="title" required 
                               value="${escapeHtml(schedule.title)}" placeholder="Enter schedule title...">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_game_id" class="form-label fw-semibold">Game</label>
                        <select class="form-select" id="edit_game_id" name="game_id">
                            <option value="">Select a game (optional)</option>
                            <?php foreach ($user_games as $game): ?>
                                <option value="<?php echo $game['game_id']; ?>" ${schedule.game_id == <?php echo $game['game_id']; ?> ? 'selected' : ''}>
                                    <?php echo htmlspecialchars($game['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="edit_description" class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" id="edit_description" name="description" rows="3" 
                              placeholder="Describe your gaming session...">${escapeHtml(schedule.description || '')}</textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="edit_scheduled_date" class="form-label fw-semibold">
                            Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" id="edit_scheduled_date" name="scheduled_date" required
                               value="${schedule.scheduled_date}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_start_time" class="form-label fw-semibold">
                            Start Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" class="form-control" id="edit_start_time" name="start_time" required
                               value="${schedule.start_time}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_end_time" class="form-label fw-semibold">
                            End Time <span class="text-danger">*</span>
                        </label>
                        <input type="time" class="form-control" id="edit_end_time" name="end_time" required
                               value="${schedule.end_time}">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_max_participants" class="form-label fw-semibold">Max Participants</label>
                        <input type="number" class="form-control" id="edit_max_participants" name="max_participants" 
                               min="1" max="100" placeholder="Optional" value="${schedule.max_participants || ''}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="edit_is_public" name="is_public" value="1"
                                   ${schedule.is_public ? 'checked' : ''}>
                            <label class="form-check-label" for="edit_is_public">
                                <i class="bi bi-globe me-1"></i>Make schedule public
                            </label>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Delete schedule function
        function deleteSchedule(scheduleId) {
            if (confirm('Are you sure you want to delete this schedule? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'schedules.php';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = 'csrf_token';
                csrfToken.value = '<?php echo $csrf_token; ?>';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete_schedule';
                
                const scheduleIdInput = document.createElement('input');
                scheduleIdInput.type = 'hidden';
                scheduleIdInput.name = 'schedule_id';
                scheduleIdInput.value = scheduleId;
                
                form.appendChild(csrfToken);
                form.appendChild(action);
                form.appendChild(scheduleIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>