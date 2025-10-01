<?php
require 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Validate request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedules.php?error=Invalid request method');
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    header('Location: schedules.php?error=Invalid security token');
    exit;
}

// Validate schedule ID
$schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
if (!$schedule_id) {
    header('Location: schedules.php?error=Invalid schedule ID');
    exit;
}

$user_id = $_SESSION['user_id'];
global $pdo;

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if schedule exists and belongs to user
    $stmt = $pdo->prepare("SELECT status FROM Schedules WHERE schedule_id = :id AND user_id = :user");
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':user', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Schedule not found or you don't have permission to delete it");
    }

    // Perform soft delete
    $stmt = $pdo->prepare("
        UPDATE Schedules 
        SET status = 'deleted', 
            deleted_at = NOW() 
        WHERE schedule_id = :id 
        AND user_id = :user
    ");
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':user', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception("Could not delete schedule");
    }

    // Remove friend associations
    $stmt = $pdo->prepare("DELETE FROM ScheduleFriends WHERE schedule_id = :id");
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->execute();

    // Add notifications for invited friends
    $stmt = $pdo->prepare("
        INSERT INTO Notifications (user_id, type, message, related_id, created_at)
        SELECT friend_id, 'schedule_cancelled', 
               'Een gaming sessie waar je voor was uitgenodigd is geannuleerd', 
               :schedule_id, NOW()
        FROM ScheduleFriends
        WHERE schedule_id = :id
    ");
    $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $schedule_id, PDO::PARAM_INT);
    $stmt->execute();

    // Commit transaction
    $pdo->commit();
    
    header('Location: schedules.php?msg=Schedule successfully deleted');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: schedules.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>