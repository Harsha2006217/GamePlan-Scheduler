<?php
/**
 * Advanced Schedule Deletion System
 * GamePlan Scheduler - Professional Gaming Schedule Management
 * 
 * This module handles secure deletion of gaming schedules with comprehensive
 * validation, logging, and user authorization checks.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

require 'functions.php';

// Advanced security check with session validation
if (!isLoggedIn() || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables for advanced processing
$user_id = $_SESSION['user_id'];
$schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$confirmation = filter_input(INPUT_GET, 'confirm', FILTER_SANITIZE_STRING);
$redirect_url = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?? 'index.php';

// Advanced validation checks
$errors = [];
$success_messages = [];

// Validate schedule ID
if (!$schedule_id || $schedule_id <= 0) {
    $errors[] = 'Ongeldig schema ID opgegeven.';
}

try {
    // Get PDO connection with error handling
    global $pdo;
    
    if (!$pdo) {
        throw new Exception('Database verbinding niet beschikbaar.');
    }
    
    // Advanced schedule verification with detailed information
    $stmt = $pdo->prepare("
        SELECT s.*, g.titel as game_titel, g.description as game_description,
               COUNT(DISTINCT f.friend_id) as friend_count,
               u.username as owner_username
        FROM Schedules s 
        LEFT JOIN Games g ON s.game_id = g.game_id
        LEFT JOIN Friends f ON FIND_IN_SET(f.friend_user_id, s.friends) > 0
        LEFT JOIN Users u ON s.user_id = u.user_id
        WHERE s.schedule_id = :id AND s.user_id = :user_id
        GROUP BY s.schedule_id
    ");
    
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $schedule_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule_data) {
        $errors[] = 'Schema niet gevonden of je hebt geen toestemming om dit schema te verwijderen.';
    } else {
        // Check if schedule is in the past (optional warning)
        $schedule_datetime = strtotime($schedule_data['date'] . ' ' . $schedule_data['time']);
        $is_past_schedule = $schedule_datetime < time();
        
        // Check if there are related events
        $event_check_stmt = $pdo->prepare("
            SELECT COUNT(*) as event_count 
            FROM Events 
            WHERE schedule_id = :schedule_id
        ");
        $event_check_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $event_check_stmt->execute();
        $related_events = $event_check_stmt->fetchColumn();
        
        // Process deletion if confirmed
        if ($confirmation === 'yes' && empty($errors)) {
            
            // Begin transaction for data integrity
            $pdo->beginTransaction();
            
            try {
                // Log the deletion attempt for audit trail
                $log_stmt = $pdo->prepare("
                    INSERT INTO ActivityLog (user_id, action_type, target_type, target_id, details, created_at)
                    VALUES (:user_id, 'delete_attempt', 'schedule', :schedule_id, :details, NOW())
                ");
                
                $log_details = json_encode([
                    'schedule_title' => $schedule_data['game_titel'],
                    'schedule_date' => $schedule_data['date'],
                    'schedule_time' => $schedule_data['time'],
                    'friend_count' => $schedule_data['friend_count'],
                    'related_events' => $related_events,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]);
                
                $log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $log_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                $log_stmt->bindParam(':details', $log_details, PDO::PARAM_STR);
                $log_stmt->execute();
                
                // Update related events to unlink from this schedule
                if ($related_events > 0) {
                    $unlink_stmt = $pdo->prepare("
                        UPDATE Events 
                        SET schedule_id = NULL, 
                            updated_at = NOW(),
                            notes = CONCAT(COALESCE(notes, ''), 
                                         '\nGekoppeld schema verwijderd op " . date('Y-m-d H:i:s') . "')
                        WHERE schedule_id = :schedule_id
                    ");
                    $unlink_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                    $unlink_stmt->execute();
                }
                
                // Execute the actual deletion
                $delete_result = deleteSchedule($schedule_id);
                
                if ($delete_result['success']) {
                    // Log successful deletion
                    $success_log_stmt = $pdo->prepare("
                        INSERT INTO ActivityLog (user_id, action_type, target_type, target_id, details, created_at)
                        VALUES (:user_id, 'delete_success', 'schedule', :schedule_id, :details, NOW())
                    ");
                    
                    $success_details = json_encode([
                        'action' => 'schedule_deleted',
                        'schedule_title' => $schedule_data['game_titel'],
                        'deletion_timestamp' => date('Y-m-d H:i:s'),
                        'events_unlinked' => $related_events
                    ]);
                    
                    $success_log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $success_log_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                    $success_log_stmt->bindParam(':details', $success_details, PDO::PARAM_STR);
                    $success_log_stmt->execute();
                    
                    // Update user statistics
                    updateUserStatistics($user_id, 'schedule_deleted');
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Set success message for redirect
                    $_SESSION['success_message'] = 'Schema "' . htmlspecialchars($schedule_data['game_titel']) . '" is succesvol verwijderd.';
                    
                    if ($related_events > 0) {
                        $_SESSION['info_message'] = $related_events . ' gerelateerde evenement(en) zijn ontkoppeld van dit schema.';
                    }
                    
                } else {
                    throw new Exception($delete_result['message'] ?? 'Onbekende fout bij verwijderen.');
                }
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                
                // Log the error
                error_log("Schedule deletion failed for user {$user_id}, schedule {$schedule_id}: " . $e->getMessage());
                
                $errors[] = 'Er is een fout opgetreden bij het verwijderen: ' . $e->getMessage();
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in delete_schedule.php: " . $e->getMessage());
    $errors[] = 'Database fout: Probeer het later opnieuw.';
} catch (Exception $e) {
    error_log("General error in delete_schedule.php: " . $e->getMessage());
    $errors[] = 'Er is een onverwachte fout opgetreden.';
}

// Handle redirect with messages
if (empty($errors) && $confirmation === 'yes') {
    header("Location: " . $redirect_url . "?deleted=1");
    exit();
}

// If no confirmation yet, show confirmation page
if (empty($errors) && $confirmation !== 'yes') {
    // Store schedule data in session for confirmation page
    $_SESSION['delete_schedule_data'] = $schedule_data;
}

// Redirect with errors if any
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    header("Location: " . $redirect_url . "?error=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema Verwijderen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <header class="bg-dark text-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-gamepad me-2"></i>GamePlan Scheduler</h1>
            <nav>
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-1"></i>Terug naar Dashboard
                </a>
            </nav>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Schema Verwijderen - Bevestiging Vereist
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($schedule_data)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-warning me-2"></i>
                                <strong>Let op:</strong> Deze actie kan niet ongedaan worden gemaakt!
                            </div>
                            
                            <h5>Je staat op het punt het volgende schema te verwijderen:</h5>
                            
                            <div class="bg-light p-3 rounded mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-gamepad me-2"></i>Game:</strong> 
                                        <?php echo htmlspecialchars($schedule_data['game_titel']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar me-2"></i>Datum:</strong> 
                                        <?php echo date('j M Y', strtotime($schedule_data['date'])); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-clock me-2"></i>Tijd:</strong> 
                                        <?php echo date('H:i', strtotime($schedule_data['time'])); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-users me-2"></i>Vrienden:</strong> 
                                        <?php echo $schedule_data['friend_count']; ?> uitgenodigd
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($related_events > 0): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Gerelateerde evenementen:</strong> 
                                    Dit schema heeft <?php echo $related_events; ?> gekoppelde evenement(en). 
                                    Deze worden ontkoppeld maar niet verwijderd.
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($is_past_schedule): ?>
                                <div class="alert alert-secondary">
                                    <i class="fas fa-history me-2"></i>
                                    <strong>Historisch schema:</strong> 
                                    Dit schema ligt in het verleden.
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo htmlspecialchars($redirect_url); ?>" 
                                   class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Annuleren
                                </a>
                                <a href="delete_schedule.php?id=<?php echo $schedule_id; ?>&confirm=yes&redirect=<?php echo urlencode($redirect_url); ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Weet je het zeker? Deze actie kan niet ongedaan worden gemaakt!');">
                                    <i class="fas fa-trash me-1"></i>Ja, Verwijder Schema
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Schema niet gevonden of toegang geweigerd.
                            </div>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i>Terug naar Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <div class="container">
            <p class="mb-0">
                © 2025 GamePlan Scheduler door Harsha Kanaparthi | 
                <a href="privacy.php" class="text-white text-decoration-none">Privacy</a>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>