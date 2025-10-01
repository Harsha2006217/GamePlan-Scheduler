<?php
/**
 * GamePlan Scheduler - Enhanced Professional Event Deletion Handler
 * Advanced Gaming Schedule Management System with Security & Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working Event Deletion
 */

// Start session and include required files
session_start();
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Enhanced security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

/**
 * Professional Event Deletion with Advanced Security & Validation
 * Handles complete event removal with participant notifications
 */

// Comprehensive authentication check
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'U moet ingelogd zijn om evenementen te verwijderen.';
    header("Location: login.php");
    exit;
}

// Enhanced request method validation
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Ongeldige verzoek methode. Alleen GET en POST zijn toegestaan.';
    header('Location: events.php');
    exit;
}

// CSRF token validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Ongeldig beveiligingstoken. Probeer opnieuw.';
        header('Location: events.php');
        exit;
    }
}

// Enhanced event ID validation with comprehensive checks
$event_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
} else {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

// Validate event ID
if (!$event_id || $event_id <= 0) {
    $_SESSION['error_message'] = 'Ongeldig evenement ID. Het evenement kon niet worden gevonden.';
    header('Location: events.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Onbekende gebruiker';

try {
    // Begin comprehensive database transaction
    $pdo->beginTransaction();

    // Enhanced event existence and ownership verification
    $stmt = $pdo->prepare("
        SELECT 
            e.event_id,
            e.title,
            e.description,
            e.date,
            e.time,
            e.user_id,
            e.status,
            e.created_at,
            u.username as owner_username,
            COUNT(eum.friend_id) as participant_count
        FROM Events e 
        LEFT JOIN Users u ON e.user_id = u.user_id
        LEFT JOIN EventUserMap eum ON e.event_id = eum.event_id
        WHERE e.event_id = :event_id 
        AND e.status != 'deleted'
        GROUP BY e.event_id
    ");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Comprehensive event validation
    if (!$event) {
        throw new Exception("Evenement niet gevonden of reeds verwijderd.");
    }
    
    // Enhanced ownership verification
    if ($event['user_id'] !== $user_id) {
        // Log security violation attempt
        error_log("Security violation: User {$user_id} ({$username}) attempted to delete event {$event_id} owned by {$event['user_id']}");
        throw new Exception("U heeft geen toestemming om dit evenement te verwijderen. Alleen de eigenaar kan dit evenement beheren.");
    }

    // GET request: Show confirmation page
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get event participants for confirmation display
        $stmt = $pdo->prepare("
            SELECT u.username, u.email
            FROM EventUserMap eum
            JOIN Users u ON eum.friend_id = u.user_id
            WHERE eum.event_id = :event_id
            ORDER BY u.username
        ");
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Rollback transaction (we're just reading)
        $pdo->rollBack();
        
        // Generate CSRF token for form
        $csrf_token = generateCSRFToken();
        
        // Show professional confirmation page
        include 'includes/header.php';
        ?>
        
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-lg border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="card-title mb-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Evenement Verwijderen
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning border-left-warning">
                                <h5 class="alert-heading">
                                    <i class="bi bi-warning-fill me-2"></i>
                                    Waarschuwing!
                                </h5>
                                <p class="mb-0">
                                    Weet u zeker dat u dit evenement permanent wilt verwijderen? 
                                    Deze actie kan <strong>niet</strong> ongedaan worden gemaakt.
                                </p>
                            </div>
                            
                            <!-- Event Details -->
                            <div class="event-details bg-light p-4 rounded mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="bi bi-calendar-event me-2"></i>
                                    Evenement Details
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Titel:</strong> <?php echo htmlspecialchars($event['title']); ?></p>
                                        <p><strong>Datum:</strong> <?php echo date('d-m-Y', strtotime($event['date'])); ?></p>
                                        <p><strong>Tijd:</strong> <?php echo date('H:i', strtotime($event['time'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Eigenaar:</strong> <?php echo htmlspecialchars($event['owner_username']); ?></p>
                                        <p><strong>Deelnemers:</strong> <?php echo $event['participant_count']; ?> personen</p>
                                        <p><strong>Aangemaakt:</strong> <?php echo date('d-m-Y H:i', strtotime($event['created_at'])); ?></p>
                                    </div>
                                </div>
                                <?php if (!empty($event['description'])): ?>
                                    <p><strong>Beschrijving:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Participants List -->
                            <?php if (!empty($participants)): ?>
                                <div class="participants-list mb-4">
                                    <h6 class="text-info mb-3">
                                        <i class="bi bi-people-fill me-2"></i>
                                        Gedeeld met vrienden (<?php echo count($participants); ?>):
                                    </h6>
                                    <div class="row">
                                        <?php foreach ($participants as $participant): ?>
                                            <div class="col-md-4 mb-2">
                                                <span class="badge bg-info">
                                                    <i class="bi bi-person me-1"></i>
                                                    <?php echo htmlspecialchars($participant['username']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <small>Alle deelnemers krijgen een melding dat dit evenement is geannuleerd.</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Confirmation Form -->
                            <form method="POST" action="delete_event.php" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="events.php" class="btn btn-secondary btn-lg">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        Annuleren
                                    </a>
                                    
                                    <button type="submit" class="btn btn-danger btn-lg" 
                                            onclick="return confirm('Laatste waarschuwing: Weet u absoluut zeker dat u dit evenement wilt verwijderen? Deze actie kan NIET ongedaan worden gemaakt!');">
                                        <i class="bi bi-trash-fill me-2"></i>
                                        Definitief Verwijderen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        include 'includes/footer.php';
        exit;
    }

    // POST request: Execute deletion
    
    // Get participants for notifications before deletion
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email
        FROM EventUserMap eum
        JOIN Users u ON eum.friend_id = u.user_id
        WHERE eum.event_id = :event_id
    ");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 1: Remove participant associations with detailed logging
    $stmt = $pdo->prepare("DELETE FROM EventUserMap WHERE event_id = :event_id");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $deleteMapResult = $stmt->execute();
    $deletedParticipants = $stmt->rowCount();
    
    if (!$deleteMapResult) {
        throw new Exception("Fout bij het verwijderen van evenement deelnemers.");
    }

    // Step 2: Soft delete the event (professional approach)
    $stmt = $pdo->prepare("
        UPDATE Events 
        SET status = 'deleted', 
            deleted_at = NOW(),
            deleted_by = :user_id
        WHERE event_id = :event_id 
        AND user_id = :owner_id
    ");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':owner_id', $user_id, PDO::PARAM_INT);
    $deleteEventResult = $stmt->execute();
    
    if (!$deleteEventResult || $stmt->rowCount() === 0) {
        throw new Exception("Fout bij het verwijderen van het evenement. Het evenement bestaat mogelijk niet meer.");
    }

    // Step 3: Create notifications for all participants
    if (!empty($participants)) {
        $notificationStmt = $pdo->prepare("
            INSERT INTO Notifications (user_id, type, title, message, created_at, related_id)
            VALUES (:user_id, 'event_cancelled', :title, :message, NOW(), :event_id)
        ");
        
        $notificationTitle = 'Evenement Geannuleerd';
        $notificationMessage = sprintf(
            'Het evenement "%s" gepland voor %s om %s is geannuleerd door de organisator.',
            $event['title'],
            date('d-m-Y', strtotime($event['date'])),
            date('H:i', strtotime($event['time']))
        );
        
        $notificationsSent = 0;
        foreach ($participants as $participant) {
            $notificationStmt->bindParam(':user_id', $participant['user_id'], PDO::PARAM_INT);
            $notificationStmt->bindParam(':title', $notificationTitle, PDO::PARAM_STR);
            $notificationStmt->bindParam(':message', $notificationMessage, PDO::PARAM_STR);
            $notificationStmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            
            if ($notificationStmt->execute()) {
                $notificationsSent++;
            }
        }
    }

    // Step 4: Log the deletion for audit trail
    $auditStmt = $pdo->prepare("
        INSERT INTO AuditLog (user_id, action, entity_type, entity_id, details, created_at)
        VALUES (:user_id, 'DELETE', 'Event', :event_id, :details, NOW())
    ");
    
    $auditDetails = json_encode([
        'event_title' => $event['title'],
        'event_date' => $event['date'],
        'participants_notified' => $notificationsSent ?? 0,
        'deleted_by' => $username
    ]);
    
    $auditStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $auditStmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $auditStmt->bindParam(':details', $auditDetails, PDO::PARAM_STR);
    $auditStmt->execute(); // Audit logging is non-critical

    // Commit all changes
    $pdo->commit();
    
    // Set success message with detailed information
    $successMessage = sprintf(
        'Evenement "%s" is succesvol verwijderd. %d deelnemers zijn op de hoogte gesteld.',
        htmlspecialchars($event['title']),
        $notificationsSent ?? 0
    );
    $_SESSION['success_message'] = $successMessage;
    
    // Log successful deletion
    error_log("Event deleted successfully: ID {$event_id} ('{$event['title']}') by user {$user_id} ({$username})");
    
    // Redirect with success
    header('Location: events.php?msg=' . urlencode('Evenement succesvol verwijderd'));
    exit;

} catch (PDOException $e) {
    // Database error handling
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database error in delete_event.php: " . $e->getMessage() . " | User: {$user_id} | Event: {$event_id}");
    $_SESSION['error_message'] = 'Er is een databasefout opgetreden. Probeer het later opnieuw.';
    
    header('Location: events.php?error=' . urlencode('Database fout bij verwijderen'));
    exit;
    
} catch (Exception $e) {
    // General error handling
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in delete_event.php: " . $e->getMessage() . " | User: {$user_id} | Event: {$event_id}");
    $_SESSION['error_message'] = $e->getMessage();
    
    header('Location: events.php?error=' . urlencode($e->getMessage()));
    exit;
    
} catch (Throwable $e) {
    // Catch any other errors (PHP 7+ compatibility)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Critical error in delete_event.php: " . $e->getMessage() . " | User: {$user_id} | Event: {$event_id}");
    $_SESSION['error_message'] = 'Er is een onverwachte fout opgetreden. Neem contact op met de beheerder.';
    
    header('Location: events.php?error=' . urlencode('Onverwachte fout'));
    exit;
}

// This should never be reached, but just in case
header('Location: events.php');
exit;
?>