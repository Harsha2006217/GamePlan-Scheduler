<?php
/**
 * Schedule template management functions
 */

/**
 * Create a new schedule template
 */
function createScheduleTemplate($user_id, $template_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Create template
        $stmt = $pdo->prepare("
            INSERT INTO ScheduleTemplates (
                user_id, name, game_id, description, time, duration,
                max_participants, recurring_pattern, weekdays, monthly_day
            ) VALUES (
                :user_id, :name, :game_id, :description, :time, :duration,
                :max_participants, :recurring_pattern, :weekdays, :monthly_day
            )
        ");
        
        $stmt->execute([
            'user_id' => $user_id,
            'name' => $template_data['name'],
            'game_id' => $template_data['game_id'],
            'description' => $template_data['description'] ?? null,
            'time' => $template_data['time'],
            'duration' => $template_data['duration'] ?? 60,
            'max_participants' => $template_data['max_participants'] ?? null,
            'recurring_pattern' => $template_data['recurring_pattern'] ?? null,
            'weekdays' => !empty($template_data['weekdays']) ? implode(',', $template_data['weekdays']) : null,
            'monthly_day' => $template_data['monthly_day'] ?? null
        ]);
        
        $template_id = $pdo->lastInsertId();
        
        // Add friend invites if any
        if (!empty($template_data['friends'])) {
            $stmt = $pdo->prepare("
                INSERT INTO TemplateInvites (template_id, friend_id, auto_invite)
                VALUES (:template_id, :friend_id, :auto_invite)
            ");
            
            foreach ($template_data['friends'] as $friend) {
                $stmt->execute([
                    'template_id' => $template_id,
                    'friend_id' => $friend['user_id'],
                    'auto_invite' => $friend['auto_invite'] ?? true
                ]);
            }
        }
        
        $pdo->commit();
        return $template_id;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Create Schedule Template Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing schedule template
 */
function updateScheduleTemplate($template_id, $user_id, $template_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Verify ownership
        $stmt = $pdo->prepare("
            SELECT template_id FROM ScheduleTemplates 
            WHERE template_id = :template_id AND user_id = :user_id
        ");
        $stmt->execute(['template_id' => $template_id, 'user_id' => $user_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Template not found or access denied");
        }
        
        // Update template
        $stmt = $pdo->prepare("
            UPDATE ScheduleTemplates SET
                name = :name,
                game_id = :game_id,
                description = :description,
                time = :time,
                duration = :duration,
                max_participants = :max_participants,
                recurring_pattern = :recurring_pattern,
                weekdays = :weekdays,
                monthly_day = :monthly_day,
                updated_at = CURRENT_TIMESTAMP
            WHERE template_id = :template_id
        ");
        
        $stmt->execute([
            'template_id' => $template_id,
            'name' => $template_data['name'],
            'game_id' => $template_data['game_id'],
            'description' => $template_data['description'] ?? null,
            'time' => $template_data['time'],
            'duration' => $template_data['duration'] ?? 60,
            'max_participants' => $template_data['max_participants'] ?? null,
            'recurring_pattern' => $template_data['recurring_pattern'] ?? null,
            'weekdays' => !empty($template_data['weekdays']) ? implode(',', $template_data['weekdays']) : null,
            'monthly_day' => $template_data['monthly_day'] ?? null
        ]);
        
        // Update friend invites
        if (isset($template_data['friends'])) {
            // Remove existing invites
            $stmt = $pdo->prepare("DELETE FROM TemplateInvites WHERE template_id = :template_id");
            $stmt->execute(['template_id' => $template_id]);
            
            // Add new invites
            $stmt = $pdo->prepare("
                INSERT INTO TemplateInvites (template_id, friend_id, auto_invite)
                VALUES (:template_id, :friend_id, :auto_invite)
            ");
            
            foreach ($template_data['friends'] as $friend) {
                $stmt->execute([
                    'template_id' => $template_id,
                    'friend_id' => $friend['user_id'],
                    'auto_invite' => $friend['auto_invite'] ?? true
                ]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update Schedule Template Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate schedules from template for a given date range
 */
function generateSchedulesFromTemplate($template_id, $start_date, $end_date) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get template details
        $stmt = $pdo->prepare("
            SELECT t.*, g.average_session_time
            FROM ScheduleTemplates t
            JOIN Games g ON t.game_id = g.game_id
            WHERE t.template_id = :template_id AND t.is_active = 1
        ");
        $stmt->execute(['template_id' => $template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception("Template not found or inactive");
        }
        
        // Get template invites
        $stmt = $pdo->prepare("
            SELECT friend_id, auto_invite 
            FROM TemplateInvites 
            WHERE template_id = :template_id
        ");
        $stmt->execute(['template_id' => $template_id]);
        $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate dates based on recurring pattern
        $dates = [];
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        while ($current <= $end) {
            $should_generate = false;
            
            switch ($template['recurring_pattern']) {
                case 'daily':
                    $should_generate = true;
                    break;
                    
                case 'weekly':
                    $weekday = strtolower($current->format('l'));
                    $should_generate = in_array($weekday, explode(',', $template['weekdays']));
                    break;
                    
                case 'biweekly':
                    $weekday = strtolower($current->format('l'));
                    $week_number = $current->format('W');
                    $should_generate = in_array($weekday, explode(',', $template['weekdays'])) && 
                                     $week_number % 2 == 0;
                    break;
                    
                case 'monthly':
                    $should_generate = $current->format('j') == $template['monthly_day'];
                    break;
            }
            
            if ($should_generate) {
                $dates[] = $current->format('Y-m-d');
            }
            
            $current->modify('+1 day');
        }
        
        // Generate schedules for calculated dates
        $schedules_created = [];
        
        foreach ($dates as $date) {
            // Check if schedule already exists for this date
            $stmt = $pdo->prepare("
                SELECT 1 FROM TemplateSchedules ts
                WHERE ts.template_id = :template_id
                AND ts.generated_for_date = :date
            ");
            $stmt->execute([
                'template_id' => $template_id,
                'date' => $date
            ]);
            
            if ($stmt->fetch()) {
                continue; // Skip if already generated
            }
            
            // Create schedule
            $stmt = $pdo->prepare("
                INSERT INTO Schedules (
                    user_id, game_id, title, date, time, end_time,
                    max_participants, description, is_recurring
                ) VALUES (
                    :user_id, :game_id, :title, :date, :time,
                    DATE_ADD(:time, INTERVAL :duration MINUTE),
                    :max_participants, :description, 1
                )
            ");
            
            $stmt->execute([
                'user_id' => $template['user_id'],
                'game_id' => $template['game_id'],
                'title' => $template['name'],
                'date' => $date,
                'time' => $template['time'],
                'duration' => $template['duration'],
                'max_participants' => $template['max_participants'],
                'description' => $template['description']
            ]);
            
            $schedule_id = $pdo->lastInsertId();
            
            // Link schedule to template
            $stmt = $pdo->prepare("
                INSERT INTO TemplateSchedules (template_id, schedule_id, generated_for_date)
                VALUES (:template_id, :schedule_id, :date)
            ");
            $stmt->execute([
                'template_id' => $template_id,
                'schedule_id' => $schedule_id,
                'date' => $date
            ]);
            
            // Add auto-invited friends
            foreach ($invites as $invite) {
                if ($invite['auto_invite']) {
                    addScheduleFriend($schedule_id, $invite['friend_id']);
                }
            }
            
            $schedules_created[] = $schedule_id;
        }
        
        $pdo->commit();
        return $schedules_created;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Generate Schedules From Template Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's schedule templates
 */
function getUserTemplates($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                g.titel as game_name,
                g.image_url as game_image,
                COUNT(DISTINCT ts.schedule_id) as schedules_generated,
                COUNT(DISTINCT ti.friend_id) as invited_friends
            FROM ScheduleTemplates t
            JOIN Games g ON t.game_id = g.game_id
            LEFT JOIN TemplateSchedules ts ON t.template_id = ts.template_id
            LEFT JOIN TemplateInvites ti ON t.template_id = ti.template_id
            WHERE t.user_id = :user_id
            GROUP BY t.template_id
            ORDER BY t.created_at DESC
        ");
        
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get User Templates Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get template details including invited friends
 */
function getTemplateDetails($template_id, $user_id) {
    global $pdo;
    
    try {
        // Get template info
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                g.titel as game_name,
                g.image_url as game_image,
                COUNT(DISTINCT ts.schedule_id) as schedules_generated
            FROM ScheduleTemplates t
            JOIN Games g ON t.game_id = g.game_id
            LEFT JOIN TemplateSchedules ts ON t.template_id = ts.template_id
            WHERE t.template_id = :template_id AND t.user_id = :user_id
            GROUP BY t.template_id
        ");
        
        $stmt->execute([
            'template_id' => $template_id,
            'user_id' => $user_id
        ]);
        
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            return null;
        }
        
        // Get invited friends
        $stmt = $pdo->prepare("
            SELECT 
                u.user_id,
                u.username,
                ti.auto_invite
            FROM TemplateInvites ti
            JOIN Users u ON ti.friend_id = u.user_id
            WHERE ti.template_id = :template_id
        ");
        
        $stmt->execute(['template_id' => $template_id]);
        $template['invited_friends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $template;
        
    } catch (PDOException $e) {
        error_log("Get Template Details Error: " . $e->getMessage());
        return null;
    }
}