<?php
/**
 * Advanced Event Deletion System
 * GamePlan Scheduler - Professional Gaming Event Management
 * 
 * This module handles secure deletion of gaming events with comprehensive
 * validation, logging, cascade deletions, and user authorization checks.
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
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$confirmation = filter_input(INPUT_GET, 'confirm', FILTER_SANITIZE_STRING);
$redirect_url = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL) ?? 'index.php';

// Advanced validation checks
$errors = [];
$success_messages = [];

// Validate event ID
if (!$event_id || $event_id <= 0) {
    $errors[] = 'Ongeldig evenement ID opgegeven.';
}

try {
    // Get PDO connection with error handling
    global $pdo;
    
    if (!$pdo) {
        throw new Exception('Database verbinding niet beschikbaar.');
    }
    
    // Advanced event verification with detailed information
    $stmt = $pdo->prepare("
        SELECT e.*, s.game_id, g.titel as game_titel,
               COUNT(DISTINCT eum.friend_id) as shared_count,
               u.username as owner_username
        FROM Events e
        LEFT JOIN Schedules s ON e.schedule_id = s.schedule_id
        LEFT JOIN Games g ON s.game_id = g.game_id
        LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
        LEFT JOIN Users u ON e.user_id = u.user_id
        WHERE e.event_id = :id AND e.user_id = :user_id
        GROUP BY e.event_id
    ");
    
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $event_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event_data) {
        $errors[] = 'Evenement niet gevonden of je hebt geen toestemming om dit evenement te verwijderen.';
    } else {
        // Check if event is in the past (optional warning)
        $event_datetime = strtotime($event_data['date'] . ' ' . $event_data['time']);
        $is_past_event = $event_datetime < time();
        
        // Check if event is linked to a schedule
        $linked_schedule = !empty($event_data['schedule_id']);
        
        // Get list of shared friends for notification
        $shared_friends_stmt = $pdo->prepare("
            SELECT u.username, u.email
            FROM EventUserMap eum
            JOIN Users u ON eum.friend_id = u.user_id
            WHERE eum.event_id = :event_id
        ");
        $shared_friends_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $shared_friends_stmt->execute();
        $shared_friends = $shared_friends_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process deletion if confirmed
        if ($confirmation === 'yes' && empty($errors)) {
            
            // Begin transaction for data integrity
            $pdo->beginTransaction();
            
            try {
                // Log the deletion attempt for audit trail
                $log_stmt = $pdo->prepare("
                    INSERT INTO ActivityLog (user_id, action_type, target_type, target_id, details, created_at)
                    VALUES (:user_id, 'delete_attempt', 'event', :event_id, :details, NOW())
                ");
                
                $log_details = json_encode([
                    'event_title' => $event_data['title'],
                    'event_date' => $event_data['date'],
                    'event_time' => $event_data['time'],
                    'shared_count' => $event_data['shared_count'],
                    'linked_schedule' => $linked_schedule,
                    'is_past_event' => $is_past_event,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]);
                
                $log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $log_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                $log_stmt->bindParam(':details', $log_details, PDO::PARAM_STR);
                $log_stmt->execute();
                
                // Delete all shared mappings first (cascade delete)
                $delete_mappings_stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
                $delete_mappings_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                $delete_mappings_stmt->execute();
                $deleted_mappings = $delete_mappings_stmt->rowCount();
                
                // Execute the actual event deletion
                $delete_result = deleteEvent($event_id);
                
                if ($delete_result['success']) {
                    // Log successful deletion
                    $success_log_stmt = $pdo->prepare("
                        INSERT INTO ActivityLog (user_id, action_type, target_type, target_id, details, created_at)
                        VALUES (:user_id, 'delete_success', 'event', :event_id, :details, NOW())
                    ");
                    
                    $success_details = json_encode([
                        'action' => 'event_deleted',
                        'event_title' => $event_data['title'],
                        'deletion_timestamp' => date('Y-m-d H:i:s'),
                        'mappings_deleted' => $deleted_mappings,
                        'notifications_sent' => count($shared_friends)
                    ]);
                    
                    $success_log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $success_log_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                    $success_log_stmt->bindParam(':details', $success_details, PDO::PARAM_STR);
                    $success_log_stmt->execute();
                    
                    // Send notifications to shared friends (if enabled)
                    if (!empty($shared_friends)) {
                        foreach ($shared_friends as $friend) {
                            // This could be expanded to send actual email notifications
                            // For now, we'll create notification records in the database
                            $notification_stmt = $pdo->prepare("
                                INSERT INTO Notifications (user_id, type, title, message, created_at)
                                SELECT u.user_id, 'event_cancelled', :title, :message, NOW()
                                FROM Users u WHERE u.username = :username
                            ");
                            
                            $notification_title = 'Evenement Geannuleerd';
                            $notification_message = 'Het evenement "' . $event_data['title'] . '" op ' . 
                                                   date('j M Y', strtotime($event_data['date'])) . 
                                                   ' is geannuleerd door de organisator.';
                            
                            $notification_stmt->bindParam(':title', $notification_title, PDO::PARAM_STR);
                            $notification_stmt->bindParam(':message', $notification_message, PDO::PARAM_STR);
                            $notification_stmt->bindParam(':username', $friend['username'], PDO::PARAM_STR);
                            $notification_stmt->execute();
                        }
                    }
                    
                    // Update user statistics
                    updateUserStatistics($user_id, 'event_deleted');
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Set success message for redirect
                    $_SESSION['success_message'] = 'Evenement "' . htmlspecialchars($event_data['title']) . '" is succesvol verwijderd.';
                    
                    if (count($shared_friends) > 0) {
                        $_SESSION['info_message'] = count($shared_friends) . ' vriend(en) zijn geïnformeerd over de annulering.';
                    }
                    
                } else {
                    throw new Exception($delete_result['message'] ?? 'Onbekende fout bij verwijderen.');
                }
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                
                // Log the error
                error_log("Event deletion failed for user {$user_id}, event {$event_id}: " . $e->getMessage());
                
                $errors[] = 'Er is een fout opgetreden bij het verwijderen: ' . $e->getMessage();
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in delete_event.php: " . $e->getMessage());
    $errors[] = 'Database fout: Probeer het later opnieuw.';
} catch (Exception $e) {
    error_log("General error in delete_event.php: " . $e->getMessage());
    $errors[] = 'Er is een onverwachte fout opgetreden.';
}

// Handle redirect with messages
if (empty($errors) && $confirmation === 'yes') {
    header("Location: " . $redirect_url . "?deleted=1");
    exit();
}

// If no confirmation yet, show confirmation page
if (empty($errors) && $confirmation !== 'yes') {
    // Store event data in session for confirmation page
    $_SESSION['delete_event_data'] = $event_data;
    $_SESSION['shared_friends_data'] = $shared_friends;
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
    <title>Evenement Verwijderen - GamePlan Scheduler</title>
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
                            Evenement Verwijderen - Bevestiging Vereist
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($event_data)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-warning me-2"></i>
                                <strong>Let op:</strong> Deze actie kan niet ongedaan worden gemaakt!
                            </div>
                            
                            <h5>Je staat op het punt het volgende evenement te verwijderen:</h5>
                            
                            <div class="bg-light p-3 rounded mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-trophy me-2"></i>Titel:</strong> 
                                        <?php echo htmlspecialchars($event_data['title']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-calendar me-2"></i>Datum:</strong> 
                                        <?php echo date('j M Y', strtotime($event_data['date'])); ?>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-clock me-2"></i>Tijd:</strong> 
                                        <?php echo date('H:i', strtotime($event_data['time'])); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-share me-2"></i>Gedeeld met:</strong> 
                                        <?php echo $event_data['shared_count']; ?> vriend(en)
                                    </div>
                                </div>
                                <?php if (!empty($event_data['description'])): ?>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <strong><i class="fas fa-align-left me-2"></i>Beschrijving:</strong> 
                                            <?php echo htmlspecialchars(substr($event_data['description'], 0, 100)); ?>
                                            <?php if (strlen($event_data['description']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($linked_schedule): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-link me-2"></i>
                                    <strong>Gekoppeld schema:</strong> 
                                    Dit evenement is gekoppeld aan een gaming schema. 
                                    Het schema blijft bestaan na verwijdering.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($shared_friends)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-users me-2"></i>
                                    <strong>Gedeelde vrienden:</strong> 
                                    De volgende vrienden worden geïnformeerd over de annulering:
                                    <br>
                                    <small>
                                        <?php 
                                        $friend_names = array_column($shared_friends, 'username');
                                        echo htmlspecialchars(implode(', ', $friend_names));
                                        ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($is_past_event): ?>
                                <div class="alert alert-secondary">
                                    <i class="fas fa-history me-2"></i>
                                    <strong>Historisch evenement:</strong> 
                                    Dit evenement ligt in het verleden.
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo htmlspecialchars($redirect_url); ?>" 
                                   class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Annuleren
                                </a>
                                <a href="delete_event.php?id=<?php echo $event_id; ?>&confirm=yes&redirect=<?php echo urlencode($redirect_url); ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Weet je het zeker? Deze actie kan niet ongedaan worden gemaakt!');">
                                    <i class="fas fa-trash me-1"></i>Ja, Verwijder Evenement
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Evenement niet gevonden of toegang geweigerd.
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
    
    <script>
        // Auto-focus on confirmation buttons for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const cancelButton = document.querySelector('.btn-secondary');
            if (cancelButton) {
                cancelButton.focus();
            }
        });
    </script>
</body>
</html>