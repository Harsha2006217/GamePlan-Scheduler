<?php
require 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Validate request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php?error=Invalid request method');
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    header('Location: events.php?error=Invalid security token');
    exit;
}

// Validate event ID
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header('Location: events.php?error=Invalid event ID');
    exit;
}

$user_id = $_SESSION['user_id'];
global $pdo;

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if event exists and belongs to user
    $stmt = $pdo->prepare("SELECT status FROM Events WHERE event_id = :id AND user_id = :user");
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':user', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Event not found or you don't have permission to delete it");
    }

    // Perform soft delete
    $stmt = $pdo->prepare("
        UPDATE Events 
        SET status = 'deleted', 
            deleted_at = NOW() 
        WHERE event_id = :id 
        AND user_id = :user
    ");
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':user', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception("Could not delete event");
    }

    // Remove participant associations
    $stmt = $pdo->prepare("DELETE FROM EventParticipants WHERE event_id = :id");
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->execute();

    // Add notifications for participants
    $stmt = $pdo->prepare("
        INSERT INTO Notifications (user_id, type, message, related_id, created_at)
        SELECT participant_id, 'event_cancelled', 
               'Een event waar je aan deelnam is geannuleerd', 
               :event_id, NOW()
        FROM EventParticipants
        WHERE event_id = :id
    ");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
    $stmt->execute();

    // Commit transaction
    $pdo->commit();
    
    header('Location: events.php?msg=Event successfully deleted');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: events.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>