<?php
require_once 'functions.php';
requireLogin();

$user_id = getCurrentUserId();
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($type) || empty($id)) {
    header('Location: index.php?error=Invalid deletion request');
    exit();
}

try {
    switch ($type) {
        case 'schedule':
            // Delete schedule and associated events
            $query = "DELETE FROM schedules WHERE schedule_id = :id AND user_id = :user_id";
            $stmt = $gameplan->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                header('Location: schedules.php?success=Schedule deleted successfully');
            } else {
                throw new Exception("Failed to delete schedule");
            }
            break;
            
        case 'event':
            // First check if user owns the event
            $check_query = "SELECT e.event_id 
                           FROM events e 
                           JOIN schedules s ON e.schedule_id = s.schedule_id 
                           WHERE e.event_id = :event_id AND s.user_id = :user_id";
            $check_stmt = $gameplan->conn->prepare($check_query);
            $check_stmt->bindParam(':event_id', $id);
            $check_stmt->bindParam(':user_id', $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->fetch()) {
                // User owns the event, delete it
                $delete_query = "DELETE FROM events WHERE event_id = :event_id";
                $delete_stmt = $gameplan->conn->prepare($delete_query);
                $delete_stmt->bindParam(':event_id', $id);
                
                if ($delete_stmt->execute()) {
                    header('Location: events.php?success=Event deleted successfully');
                } else {
                    throw new Exception("Failed to delete event");
                }
            } else {
                // User doesn't own the event, just remove participation
                $participant_query = "DELETE FROM event_participants 
                                     WHERE event_id = :event_id AND user_id = :user_id";
                $participant_stmt = $gameplan->conn->prepare($participant_query);
                $participant_stmt->bindParam(':event_id', $id);
                $participant_stmt->bindParam(':user_id', $user_id);
                
                if ($participant_stmt->execute()) {
                    header('Location: events.php?success=Left event successfully');
                } else {
                    throw new Exception("Failed to leave event");
                }
            }
            break;
            
        case 'usergame':
            // Remove game from user's collection
            $query = "DELETE FROM user_games WHERE user_game_id = :id AND user_id = :user_id";
            $stmt = $gameplan->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                header('Location: profile.php?success=Game removed from collection');
            } else {
                throw new Exception("Failed to remove game");
            }
            break;
            
        case 'friend':
            // Remove friendship
            $query = "DELETE FROM friends 
                     WHERE (user_id = :user_id AND friend_id = :friend_id) 
                     OR (user_id = :friend_id AND friend_id = :user_id)";
            $stmt = $gameplan->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':friend_id', $id);
            
            if ($stmt->execute()) {
                header('Location: friends.php?success=Friend removed successfully');
            } else {
                throw new Exception("Failed to remove friend");
            }
            break;
            
        default:
            throw new Exception("Invalid deletion type");
    }
    
} catch (Exception $e) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php') . '?error=' . urlencode($e->getMessage()));
}

exit();
?>