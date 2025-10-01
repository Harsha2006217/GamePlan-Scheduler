<?php
require_once 'functions.php';
require_once 'template_functions.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$template = null;
$template_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$games = getGames();
$friends = getFriends($user_id);

// If editing existing template, get its details
if ($template_id) {
    $template = getTemplateDetails($template_id, $user_id);
    if (!$template) {
        $_SESSION['error_msg'] = "Template not found or access denied";
        header('Location: templates.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_data = [
        'name' => $_POST['name'],
        'game_id' => intval($_POST['game_id']),
        'description' => $_POST['description'],
        'time' => $_POST['time'],
        'duration' => intval($_POST['duration']),
        'max_participants' => !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : null,
        'recurring_pattern' => $_POST['recurring_pattern'],
        'weekdays' => isset($_POST['weekdays']) ? $_POST['weekdays'] : [],
        'monthly_day' => isset($_POST['monthly_day']) ? intval($_POST['monthly_day']) : null
    ];
    
    // Process friend invites
    if (!empty($_POST['friends'])) {
        $template_data['friends'] = [];
        foreach ($_POST['friends'] as $friend_id) {
            $template_data['friends'][] = [
                'user_id' => $friend_id,
                'auto_invite' => isset($_POST['auto_invite'][$friend_id])
            ];
        }
    }
    
    if ($template_id) {
        // Update existing template
        if (updateScheduleTemplate($template_id, $user_id, $template_data)) {
            $_SESSION['success_msg'] = "Template updated successfully";
            header('Location: templates.php');
            exit;
        }
    } else {
        // Create new template
        if ($template_id = createScheduleTemplate($user_id, $template_data)) {
            $_SESSION['success_msg'] = "Template created successfully";
            header('Location: templates.php');
            exit;
        }
    }
    
    $_SESSION['error_msg'] = "Failed to save template";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $template_id ? 'Edit' : 'Create'; ?> Schedule Template - GamePlan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="container">
        <h1><?php echo $template_id ? 'Edit' : 'Create'; ?> Schedule Template</h1>
        
        <form method="post" class="form-large">
            <div class="form-group">
                <label for="name">Template Name:</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo htmlspecialchars($template['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="game_id">Game:</label>
                <select id="game_id" name="game_id" required>
                    <option value="">Select a game...</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"
                                <?php echo ($template['game_id'] ?? '') == $game['game_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($game['titel']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3"><?php 
                    echo htmlspecialchars($template['description'] ?? ''); 
                ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="time">Start Time:</label>
                    <input type="time" id="time" name="time" required
                           value="<?php echo htmlspecialchars($template['time'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration (minutes):</label>
                    <input type="number" id="duration" name="duration" min="30" step="30" required
                           value="<?php echo htmlspecialchars($template['duration'] ?? '60'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_participants">Max Participants:</label>
                    <input type="number" id="max_participants" name="max_participants" min="2"
                           value="<?php echo htmlspecialchars($template['max_participants'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="recurring_pattern">Recurring Pattern:</label>
                <select id="recurring_pattern" name="recurring_pattern" required>
                    <option value="daily" <?php echo ($template['recurring_pattern'] ?? '') === 'daily' ? 'selected' : ''; ?>>
                        Daily
                    </option>
                    <option value="weekly" <?php echo ($template['recurring_pattern'] ?? '') === 'weekly' ? 'selected' : ''; ?>>
                        Weekly
                    </option>
                    <option value="biweekly" <?php echo ($template['recurring_pattern'] ?? '') === 'biweekly' ? 'selected' : ''; ?>>
                        Bi-weekly
                    </option>
                    <option value="monthly" <?php echo ($template['recurring_pattern'] ?? '') === 'monthly' ? 'selected' : ''; ?>>
                        Monthly
                    </option>
                </select>
            </div>
            
            <div id="weekday-options" class="form-group" style="display: none;">
                <label>Select Days:</label>
                <div class="checkbox-group">
                    <?php 
                    $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    $selected_days = $template ? explode(',', $template['weekdays']) : [];
                    
                    foreach ($weekdays as $day): ?>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="weekdays[]" value="<?php echo $day; ?>"
                                   <?php echo in_array($day, $selected_days) ? 'checked' : ''; ?>>
                            <?php echo ucfirst($day); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="monthly-options" class="form-group" style="display: none;">
                <label for="monthly_day">Day of Month:</label>
                <input type="number" id="monthly_day" name="monthly_day" min="1" max="31"
                       value="<?php echo htmlspecialchars($template['monthly_day'] ?? '1'); ?>">
            </div>
            
            <div class="form-group">
                <label>Invite Friends:</label>
                <div class="friends-list">
                    <?php foreach ($friends as $friend): ?>
                        <div class="friend-item">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>"
                                       <?php 
                                       if ($template && !empty($template['invited_friends'])) {
                                           foreach ($template['invited_friends'] as $invited) {
                                               if ($invited['user_id'] == $friend['user_id']) {
                                                   echo 'checked';
                                                   break;
                                               }
                                           }
                                       }
                                       ?>>
                                <?php echo htmlspecialchars($friend['username']); ?>
                            </label>
                            <label class="checkbox-inline auto-invite">
                                <input type="checkbox" name="auto_invite[<?php echo $friend['user_id']; ?>]"
                                       <?php 
                                       if ($template && !empty($template['invited_friends'])) {
                                           foreach ($template['invited_friends'] as $invited) {
                                               if ($invited['user_id'] == $friend['user_id'] && $invited['auto_invite']) {
                                                   echo 'checked';
                                                   break;
                                               }
                                           }
                                       }
                                       ?>>
                                Auto-invite
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $template_id ? 'Update' : 'Create'; ?> Template
                </button>
                <a href="templates.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
    
    <?php include 'footer.php'; ?>
    <script>
        // Show/hide pattern-specific options based on selection
        document.getElementById('recurring_pattern').addEventListener('change', function() {
            const pattern = this.value;
            const weekdayOptions = document.getElementById('weekday-options');
            const monthlyOptions = document.getElementById('monthly-options');
            
            weekdayOptions.style.display = (pattern === 'weekly' || pattern === 'biweekly') ? 'block' : 'none';
            monthlyOptions.style.display = pattern === 'monthly' ? 'block' : 'none';
            
            if (pattern === 'weekly' || pattern === 'biweekly') {
                document.querySelectorAll('[name="weekdays[]"]').forEach(cb => cb.required = true);
            } else {
                document.querySelectorAll('[name="weekdays[]"]').forEach(cb => cb.required = false);
            }
            
            document.getElementById('monthly_day').required = pattern === 'monthly';
        });
        
        // Trigger initial state
        document.getElementById('recurring_pattern').dispatchEvent(new Event('change'));
        
        // Link friend checkboxes with auto-invite checkboxes
        document.querySelectorAll('[name="friends[]"]').forEach(checkbox => {
            const friendId = checkbox.value;
            const autoInvite = document.querySelector(`[name="auto_invite[${friendId}]"]`);
            
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    autoInvite.checked = false;
                }
                autoInvite.disabled = !this.checked;
            });
            
            // Set initial state
            autoInvite.disabled = !checkbox.checked;
        });
    </script>
</body>
</html>