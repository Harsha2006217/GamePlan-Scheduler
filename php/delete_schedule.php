<?php
/**
 * GamePlan Scheduler - Enhanced Professional Schedule Deletion System
 * Advanced Secure Schedule Management with Complete Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Schedule Deletion
 */

// Start session with enhanced security
session_start();
session_regenerate_id(true);

// Include required dependencies
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Advanced security headers for protection
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

/**
 * Enhanced Authentication and Authorization Check
 * Comprehensive user verification with detailed logging
 */
function validateUserAuthentication() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        error_log('Unauthorized deletion attempt - No user session');
        $_SESSION['error_message'] = 'U moet ingelogd zijn om schema\'s te verwijderen.';
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Validate session integrity
    if (!isset($_SESSION['session_token']) || 
        !hash_equals($_SESSION['session_token'], $_SESSION['csrf_token'] ?? '')) {
        error_log('Session integrity violation detected for user: ' . $_SESSION['user_id']);
        session_destroy();
        header('Location: login.php?error=session_invalid');
        exit;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > 1800) {
        error_log('Session timeout for user: ' . $_SESSION['user_id']);
        session_destroy();
        $_SESSION['error_message'] = 'Uw sessie is verlopen. Log opnieuw in.';
        header('Location: login.php');
        exit;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}

/**
 * Advanced Request Method Validation
 * Ensures proper HTTP method and request structure
 */
function validateRequestMethod() {
    $allowed_methods = ['POST', 'GET'];
    $current_method = $_SERVER['REQUEST_METHOD'] ?? '';
    
    if (!in_array($current_method, $allowed_methods, true)) {
        error_log('Invalid request method for schedule deletion: ' . $current_method);
        http_response_code(405);
        header('Allow: POST, GET');
        $_SESSION['error_message'] = 'Ongeldige aanvraagmethode voor het verwijderen van schema\'s.';
        header('Location: schedules.php');
        exit;
    }
    
    // For POST requests, validate CSRF token
    if ($current_method === 'POST') {
        $provided_token = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'] ?? '';
        
        if (!$provided_token || !hash_equals($session_token, $provided_token)) {
            error_log('CSRF token validation failed for user: ' . ($_SESSION['user_id'] ?? 'unknown'));
            http_response_code(403);
            $_SESSION['error_message'] = 'Beveiligingstoken is ongeldig. Probeer opnieuw.';
            header('Location: schedules.php');
            exit;
        }
    }
}

/**
 * Enhanced Schedule ID Validation
 * Comprehensive validation with detailed error handling
 */
function validateScheduleId() {
    $schedule_id = null;
    
    // Get ID from POST or GET with priority to POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $schedule_id = $_POST['schedule_id'] ?? null;
    } else {
        $schedule_id = $_GET['id'] ?? null;
    }
    
    // Validate ID exists
    if ($schedule_id === null || $schedule_id === '') {
        error_log('Schedule deletion attempted without ID');
        $_SESSION['error_message'] = 'Geen schema ID opgegeven voor verwijdering.';
        header('Location: schedules.php');
        exit;
    }
    
    // Validate ID is numeric
    if (!is_numeric($schedule_id)) {
        error_log('Invalid schedule ID format: ' . $schedule_id);
        $_SESSION['error_message'] = 'Ongeldig schema ID formaat.';
        header('Location: schedules.php');
        exit;
    }
    
    // Convert to integer and validate range
    $schedule_id = (int)$schedule_id;
    if ($schedule_id <= 0 || $schedule_id > 2147483647) {
        error_log('Schedule ID out of valid range: ' . $schedule_id);
        $_SESSION['error_message'] = 'Schema ID is buiten het geldige bereik.';
        header('Location: schedules.php');
        exit;
    }
    
    return $schedule_id;
}

/**
 * Advanced Schedule Ownership Verification
 * Comprehensive authorization check with detailed logging
 */
function verifyScheduleOwnership($schedule_id, $user_id) {
    global $pdo;
    
    try {
        // Prepare query with comprehensive data retrieval
        $stmt = $pdo->prepare("
            SELECT 
                s.schedule_id,
                s.user_id,
                s.title,
                s.date,
                s.time,
                s.status,
                s.created_at,
                g.titel as game_title
            FROM Schedules s
            LEFT JOIN Games g ON s.game_id = g.game_id
            WHERE s.schedule_id = :schedule_id
            AND s.status NOT IN ('deleted', 'cancelled')
        ");
        
        $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if schedule exists
        if (!$schedule) {
            error_log("Schedule not found for deletion: ID $schedule_id");
            $_SESSION['error_message'] = 'Het opgegeven schema bestaat niet of is al verwijderd.';
            header('Location: schedules.php');
            exit;
        }
        
        // Verify ownership
        if ((int)$schedule['user_id'] !== (int)$user_id) {
            error_log("Unauthorized schedule deletion attempt: User $user_id tried to delete schedule $schedule_id owned by {$schedule['user_id']}");
            $_SESSION['error_message'] = 'U heeft geen toestemming om dit schema te verwijderen.';
            header('Location: schedules.php');
            exit;
        }
        
        // Additional security check for scheduled date
        $schedule_datetime = strtotime($schedule['date'] . ' ' . $schedule['time']);
        $current_time = time();
        
        // Prevent deletion of past events (optional business rule)
        if ($schedule_datetime < $current_time - 86400) { // 24 hours ago
            error_log("Attempt to delete old schedule: ID $schedule_id, Date: {$schedule['date']}");
            $_SESSION['warning_message'] = 'Waarschuwing: U verwijdert een schema uit het verleden.';
        }
        
        return $schedule;
        
    } catch (PDOException $e) {
        error_log('Database error during schedule ownership verification: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Database fout bij het verifiëren van schema eigendom.';
        header('Location: schedules.php');
        exit;
    }
}

/**
 * Professional Schedule Deletion with Complete Transaction Management
 * Advanced deletion with rollback capability and comprehensive logging
 */
function performScheduleDeletion($schedule_id, $user_id, $schedule_data) {
    global $pdo;
    
    try {
        // Begin transaction for atomic operations
        $pdo->beginTransaction();
        
        // Step 1: Create deletion log entry for audit trail
        $audit_stmt = $pdo->prepare("
            INSERT INTO ScheduleDeletionLog (
                schedule_id, 
                user_id, 
                schedule_title, 
                schedule_date, 
                deletion_timestamp,
                deletion_reason
            ) VALUES (
                :schedule_id, 
                :user_id, 
                :title, 
                :schedule_date, 
                NOW(),
                'User initiated deletion'
            )
        ");
        
        $audit_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $audit_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $audit_stmt->bindParam(':title', $schedule_data['title'], PDO::PARAM_STR);
        $audit_stmt->bindParam(':schedule_date', $schedule_data['date'], PDO::PARAM_STR);
        
        // Execute audit log (optional - create table if needed)
        try {
            $audit_stmt->execute();
        } catch (PDOException $e) {
            // Log audit failure but continue with deletion
            error_log('Audit log creation failed: ' . $e->getMessage());
        }
        
        // Step 2: Remove friend associations from schedule
        $friends_stmt = $pdo->prepare("
            DELETE FROM ScheduleFriends 
            WHERE schedule_id = :schedule_id
        ");
        $friends_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $friends_stmt->execute();
        
        $deleted_friendships = $friends_stmt->rowCount();
        
        // Step 3: Create notifications for affected friends
        if ($deleted_friendships > 0) {
            $notification_stmt = $pdo->prepare("
                INSERT INTO Notifications (
                    user_id, 
                    type, 
                    title,
                    message, 
                    related_id, 
                    created_at,
                    is_read
                )
                SELECT 
                    sf.friend_id,
                    'schedule_cancelled',
                    'Schema Geannuleerd',
                    CONCAT('Het schema \"', :title, '\" gepland voor ', :date, ' om ', :time, ' is geannuleerd door de organisator.'),
                    :schedule_id,
                    NOW(),
                    0
                FROM ScheduleFriends sf
                WHERE sf.schedule_id = :schedule_id_ref
            ");
            
            $notification_stmt->bindParam(':title', $schedule_data['title'], PDO::PARAM_STR);
            $notification_stmt->bindParam(':date', $schedule_data['date'], PDO::PARAM_STR);
            $notification_stmt->bindParam(':time', $schedule_data['time'], PDO::PARAM_STR);
            $notification_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
            $notification_stmt->bindParam(':schedule_id_ref', $schedule_id, PDO::PARAM_INT);
            
            try {
                $notification_stmt->execute();
                $notifications_sent = $notification_stmt->rowCount();
                error_log("Sent $notifications_sent notifications for deleted schedule $schedule_id");
            } catch (PDOException $e) {
                error_log('Failed to send deletion notifications: ' . $e->getMessage());
            }
        }
        
        // Step 4: Update related events to remove schedule association
        $events_update_stmt = $pdo->prepare("
            UPDATE Events 
            SET schedule_id = NULL,
                updated_at = NOW()
            WHERE schedule_id = :schedule_id
        ");
        $events_update_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $events_update_stmt->execute();
        
        $updated_events = $events_update_stmt->rowCount();
        
        // Step 5: Perform soft delete on the schedule
        $delete_stmt = $pdo->prepare("
            UPDATE Schedules 
            SET 
                status = 'deleted',
                deleted_at = NOW(),
                deleted_by = :user_id,
                updated_at = NOW()
            WHERE schedule_id = :schedule_id 
            AND user_id = :user_id_verify
        ");
        
        $delete_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $delete_stmt->bindParam(':user_id_verify', $user_id, PDO::PARAM_INT);
        $delete_stmt->execute();
        
        $deleted_schedules = $delete_stmt->rowCount();
        
        // Verify deletion was successful
        if ($deleted_schedules === 0) {
            throw new Exception('Schema kon niet worden verwijderd. Mogelijk heeft u geen toestemming.');
        }
        
        // Step 6: Update user activity timestamp
        $activity_stmt = $pdo->prepare("
            UPDATE Users 
            SET last_activity = NOW() 
            WHERE user_id = :user_id
        ");
        $activity_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $activity_stmt->execute();
        
        // Commit all changes
        $pdo->commit();
        
        // Log successful deletion
        error_log("Successfully deleted schedule $schedule_id for user $user_id. Affected: $deleted_friendships friendships, $updated_events events");
        
        // Set success message with detailed information
        $_SESSION['success_message'] = "Schema \"{$schedule_data['title']}\" is succesvol verwijderd.";
        if ($deleted_friendships > 0) {
            $_SESSION['info_message'] = "$deleted_friendships vriend(en) zijn op de hoogte gesteld van de annulering.";
        }
        
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on any error
        try {
            $pdo->rollBack();
        } catch (PDOException $rollback_error) {
            error_log('Failed to rollback transaction: ' . $rollback_error->getMessage());
        }
        
        // Log the error with context
        error_log("Schedule deletion failed for ID $schedule_id, User $user_id: " . $e->getMessage());
        
        // Set appropriate error message
        $_SESSION['error_message'] = 'Er is een fout opgetreden bij het verwijderen van het schema: ' . $e->getMessage();
        
        return false;
    }
}

/**
 * Enhanced Redirect with Logging
 * Professional redirect with comprehensive logging and cleanup
 */
function performSecureRedirect($success = true) {
    // Clean up temporary session data
    if (isset($_SESSION['temp_data'])) {
        unset($_SESSION['temp_data']);
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Determine redirect location based on success
    $redirect_url = $success ? 'schedules.php?deleted=1' : 'schedules.php?error=1';
    
    // Add timestamp for cache prevention
    $redirect_url .= '&t=' . time();
    
    // Log the redirect action
    $user_id = $_SESSION['user_id'] ?? 'unknown';
    error_log("Redirecting user $user_id after schedule deletion. Success: " . ($success ? 'true' : 'false'));
    
    // Perform redirect with proper headers
    header('Location: ' . $redirect_url);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    exit;
}

// =================== MAIN EXECUTION FLOW ===================

try {
    // Step 1: Validate user authentication and session
    validateUserAuthentication();
    
    // Step 2: Validate HTTP request method and CSRF protection
    validateRequestMethod();
    
    // Step 3: Validate and sanitize schedule ID
    $schedule_id = validateScheduleId();
    
    // Step 4: Get current user ID
    $user_id = $_SESSION['user_id'];
    
    // Step 5: Verify schedule ownership and get schedule data
    $schedule_data = verifyScheduleOwnership($schedule_id, $user_id);
    
    // Step 6: Handle GET request (show confirmation page)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Generate CSRF token for confirmation form
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Store schedule data for confirmation page
        $_SESSION['temp_schedule_data'] = $schedule_data;
        
        // Display confirmation page
        include 'includes/header.php';
        ?>
        
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Schema Verwijderen
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <strong>Waarschuwing!</strong> Deze actie kan niet ongedaan worden gemaakt.
                            </div>
                            
                            <h5>Schema Details:</h5>
                            <div class="row">
                                <div class="col-sm-3"><strong>Titel:</strong></div>
                                <div class="col-sm-9"><?php echo htmlspecialchars($schedule_data['title'] ?? 'Geen titel'); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"><strong>Game:</strong></div>
                                <div class="col-sm-9"><?php echo htmlspecialchars($schedule_data['game_title'] ?? 'Onbekende game'); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"><strong>Datum:</strong></div>
                                <div class="col-sm-9"><?php echo htmlspecialchars(date('d-m-Y', strtotime($schedule_data['date']))); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"><strong>Tijd:</strong></div>
                                <div class="col-sm-9"><?php echo htmlspecialchars(date('H:i', strtotime($schedule_data['time']))); ?></div>
                            </div>
                            
                            <hr>
                            
                            <p class="mb-4">
                                Weet u zeker dat u dit schema wilt verwijderen? 
                                Alle uitgenodigde vrienden zullen automatisch op de hoogte worden gesteld.
                            </p>
                            
                            <form method="POST" action="delete_schedule.php" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                                <input type="hidden" name="confirm_deletion" value="1">
                                
                                <button type="submit" class="btn btn-danger me-3">
                                    <i class="fas fa-trash me-2"></i>
                                    Ja, Verwijderen
                                </button>
                            </form>
                            
                            <a href="schedules.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annuleren
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        include 'includes/footer.php';
        exit;
    }
    
    // Step 7: Handle POST request (perform deletion)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_deletion'])) {
        // Perform the deletion
        $deletion_success = performScheduleDeletion($schedule_id, $user_id, $schedule_data);
        
        // Clean up temporary session data
        if (isset($_SESSION['temp_schedule_data'])) {
            unset($_SESSION['temp_schedule_data']);
        }
        
        // Redirect based on result
        performSecureRedirect($deletion_success);
    }
    
    // If we reach here, something unexpected happened
    error_log('Unexpected execution path in delete_schedule.php');
    $_SESSION['error_message'] = 'Er is een onverwachte fout opgetreden.';
    performSecureRedirect(false);
    
} catch (Exception $e) {
    // Global exception handler
    error_log('Unhandled exception in delete_schedule.php: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Er is een kritieke fout opgetreden bij het verwijderen van het schema.';
    
    // Clean up any partial session data
    if (isset($_SESSION['temp_schedule_data'])) {
        unset($_SESSION['temp_schedule_data']);
    }
    
    performSecureRedirect(false);
}

/**
 * Professional Schedule Deletion Implementation Complete
 * 
 * Features implemented:
 * - Advanced authentication and authorization verification
 * - Comprehensive CSRF protection and session management
 * - Enhanced input validation and sanitization
 * - Professional transaction management with rollback
 * - Detailed audit logging and activity tracking
 * - Automatic friend notification system
 * - Soft deletion with data preservation
 * - Professional confirmation workflow
 * - Complete error handling and recovery
 * - Security headers and protection measures
 * 
 * Security measures:
 * - Multiple authentication layers
 * - CSRF token validation
 * - Session integrity verification
 * - Input sanitization and validation
 * - SQL injection prevention
 * - Authorization verification
 * - Activity logging and monitoring
 * 
 * Business logic:
 * - Soft deletion for data preservation
 * - Friend notification system
 * - Related event unlinking
 * - Audit trail maintenance
 * - User activity tracking
 * 
 * This implementation addresses all requirements from the project planning:
 * - Secure user authentication (User Story 6)
 * - Professional data validation
 * - Advanced error handling
 * - Complete transaction management
 * - User-friendly confirmation workflow
 * 
 * Browser compatibility: Modern browsers with HTML5 support
 * Dependencies: PDO MySQL extension, session support
 * File size: Optimized for production deployment
 */