<?php
/**
 * Enhanced notification management functions for GamePlan
 */

/**
 * Create a new notification with enhanced features
 * 
 * @param int $user_id Recipient user ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $options Additional options (link_url, reference_id, reference_type, priority, expire_at)
 * @return int|false The notification ID if successful, false otherwise
 */
function createEnhancedNotification($user_id, $type, $title, $message, array $options = []) {
    global $pdo;
    
    try {
        // Check user's notification preferences
        $stmt = $pdo->prepare("
            SELECT email_enabled, browser_enabled, quiet_hours_start, quiet_hours_end
            FROM NotificationPreferences
            WHERE user_id = :user_id AND notification_type = :type
        ");
        $stmt->execute(['user_id' => $user_id, 'type' => $type]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Use default preferences if none set
        if (!$prefs) {
            $prefs = [
                'email_enabled' => true,
                'browser_enabled' => true,
                'quiet_hours_start' => null,
                'quiet_hours_end' => null
            ];
        }
        
        // Check quiet hours
        $is_quiet_time = false;
        if ($prefs['quiet_hours_start'] && $prefs['quiet_hours_end']) {
            $current_time = date('H:i:s');
            $start = $prefs['quiet_hours_start'];
            $end = $prefs['quiet_hours_end'];
            
            if ($start < $end) {
                $is_quiet_time = ($current_time >= $start && $current_time <= $end);
            } else {
                $is_quiet_time = ($current_time >= $start || $current_time <= $end);
            }
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Create notification
        $stmt = $pdo->prepare("
            INSERT INTO Notifications (
                user_id, type, title, message, link_url, reference_id, 
                reference_type, priority, expire_at, email_scheduled_for
            ) VALUES (
                :user_id, :type, :title, :message, :link_url, :reference_id,
                :reference_type, :priority, :expire_at, :email_scheduled_for
            )
        ");
        
        $email_scheduled_for = null;
        if ($prefs['email_enabled'] && !$is_quiet_time) {
            $email_scheduled_for = date('Y-m-d H:i:s');
        } elseif ($prefs['email_enabled'] && $is_quiet_time) {
            $email_scheduled_for = $prefs['quiet_hours_end'];
        }
        
        $stmt->execute([
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link_url' => $options['link_url'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'priority' => $options['priority'] ?? 'normal',
            'expire_at' => $options['expire_at'] ?? null,
            'email_scheduled_for' => $email_scheduled_for
        ]);
        
        $notification_id = $pdo->lastInsertId();
        
        // Queue email if enabled and not in quiet hours
        if ($prefs['email_enabled']) {
            queueNotificationEmail($notification_id, $user_id, $type, $title, $message, $email_scheduled_for);
        }
        
        $pdo->commit();
        return $notification_id;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Create Enhanced Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Queue a notification email
 */
function queueNotificationEmail($notification_id, $user_id, $type, $title, $message, $scheduled_for) {
    global $pdo;
    
    try {
        // Get user's email
        $stmt = $pdo->prepare("SELECT email FROM Users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return false;
        
        // Prepare email content
        $subject = "[GamePlan] " . $title;
        $body = generateEmailTemplate($type, $title, $message);
        
        // Queue email
        $stmt = $pdo->prepare("
            INSERT INTO EmailQueue (
                notification_id, user_id, email_type, subject, body, scheduled_for
            ) VALUES (
                :notification_id, :user_id, :email_type, :subject, :body, :scheduled_for
            )
        ");
        
        return $stmt->execute([
            'notification_id' => $notification_id,
            'user_id' => $user_id,
            'email_type' => $type,
            'subject' => $subject,
            'body' => $body,
            'scheduled_for' => $scheduled_for
        ]);
        
    } catch (PDOException $e) {
        error_log("Queue Notification Email Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate email template based on notification type
 */
function generateEmailTemplate($type, $title, $message) {
    $template = file_get_contents(__DIR__ . '/email_templates/base.html');
    
    // Replace placeholders
    $template = str_replace(
        ['{{TITLE}}', '{{MESSAGE}}', '{{TYPE}}', '{{YEAR}}'],
        [$title, $message, ucfirst($type), date('Y')],
        $template
    );
    
    return $template;
}

/**
 * Mark notification as read
 */
function markNotificationRead($notification_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE Notifications 
            SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE notification_id = :notification_id 
            AND user_id = :user_id
        ");
        
        return $stmt->execute([
            'notification_id' => $notification_id,
            'user_id' => $user_id
        ]);
        
    } catch (PDOException $e) {
        error_log("Mark Notification Read Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's notifications with pagination and filtering
 */
function getUserNotifications($user_id, $options = []) {
    global $pdo;
    
    $limit = $options['limit'] ?? 10;
    $offset = $options['offset'] ?? 0;
    $type = $options['type'] ?? null;
    $unread_only = $options['unread_only'] ?? false;
    
    try {
        $where = ["user_id = :user_id"];
        $params = ['user_id' => $user_id];
        
        if ($type) {
            $where[] = "type = :type";
            $params['type'] = $type;
        }
        
        if ($unread_only) {
            $where[] = "is_read = 0";
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "
            SELECT *
            FROM Notifications
            {$where_clause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get User Notifications Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM Notifications
            WHERE user_id = :user_id AND is_read = 0
        ");
        
        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
        
    } catch (PDOException $e) {
        error_log("Get Unread Notifications Count Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Update notification preferences
 */
function updateNotificationPreferences($user_id, $preferences) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($preferences as $type => $settings) {
            $stmt = $pdo->prepare("
                INSERT INTO NotificationPreferences (
                    user_id, notification_type, email_enabled, browser_enabled,
                    quiet_hours_start, quiet_hours_end
                ) VALUES (
                    :user_id, :type, :email_enabled, :browser_enabled,
                    :quiet_hours_start, :quiet_hours_end
                ) ON DUPLICATE KEY UPDATE
                    email_enabled = :email_enabled,
                    browser_enabled = :browser_enabled,
                    quiet_hours_start = :quiet_hours_start,
                    quiet_hours_end = :quiet_hours_end
            ");
            
            $stmt->execute([
                'user_id' => $user_id,
                'type' => $type,
                'email_enabled' => $settings['email_enabled'] ?? true,
                'browser_enabled' => $settings['browser_enabled'] ?? true,
                'quiet_hours_start' => $settings['quiet_hours_start'] ?? null,
                'quiet_hours_end' => $settings['quiet_hours_end'] ?? null
            ]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Update Notification Preferences Error: " . $e->getMessage());
        return false;
    }
}