<?php
$page_title = "Add Schedule";
require_once 'header.php';

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Get user games for dropdown
$user_games = $gameplan->getUserGames($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $game_id = $_POST['game_id'] ?? '';
        $schedule_title = $_POST['schedule_title'] ?? '';
        $schedule_description = $_POST['schedule_description'] ?? '';
        $schedule_date = $_POST['schedule_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $recurring = $_POST['recurring'] ?? 'None';
        $max_participants = $_POST['max_participants'] ?? 1;

        // Validation
        if (empty($game_id) || empty($schedule_title) || empty($schedule_date) || empty($start_time) || empty($end_time)) {
            throw new Exception("All required fields must be filled");
        }

        if (!$gameplan->validateDate($schedule_date)) {
            throw new Exception("Invalid date format");
        }

        if (!$gameplan->validateTime($start_time) || !$gameplan->validateTime($end_time)) {
            throw new Exception("Invalid time format");
        }

        // Check if end time is after start time
        if (strtotime($end_time) <= strtotime($start_time)) {
            throw new Exception("End time must be after start time");
        }

        // Check if date is in the future
        $schedule_datetime = $gameplan->formatDateTime($schedule_date, $start_time);
        if (strtotime($schedule_datetime) < time()) {
            throw new Exception("Schedule date must be in the future");
        }

        // Create schedule
        $schedule_id = $gameplan->createSchedule(
            $user_id,
            $game_id,
            $schedule_title,
            $schedule_description,
            $schedule_date,
            $start_time,
            $end_time,
            $recurring,
            $max_participants
        );

        if ($schedule_id) {
            $success = "Schedule created successfully!";
            
            // Create notification
            $gameplan->createNotification(
                $user_id,
                'Schedule Created',
                "Your schedule '{$schedule_title}' has been created successfully.",
                'System',
                $schedule_id
            );
            
            // Redirect to schedules page after 2 seconds
            header("Refresh: 2; URL=schedules.php");
        } else {
            throw new Exception("Failed to create schedule");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-plus me-2 text-primary"></i>Create New Schedule
            </h1>
            <a href="schedules.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Schedules
            </a>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
        <div class="mt-2">
            <small>Redirecting to schedules page...</small>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-gamepad me-2 text-primary"></i>
                    Schedule Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="scheduleForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="game_id" class="form-label">Game *</label>
                                <select class="form-select" id="game_id" name="game_id" required>
                                    <option value="">Select a game...</option>
                                    <?php foreach ($user_games as $game): ?>
                                        <option value="<?php echo $game['game_id']; ?>" 
                                            <?php echo (isset($_POST['game_id']) && $_POST['game_id'] == $game['game_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($game['game_title']); ?>
                                            <?php if ($game['favorite']): ?> ⭐<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($user_games)): ?>
                                    <div class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        No games in your collection. <a href="profile.php">Add games first</a>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_title" class="form-label">Schedule Title *</label>
                                <input type="text" class="form-control" id="schedule_title" name="schedule_title" 
                                       value="<?php echo htmlspecialchars($_POST['schedule_title'] ?? ''); ?>" 
                                       required maxlength="100" placeholder="e.g., Weekly Fortnite Session">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="schedule_description" class="form-label">Description</label>
                        <textarea class="form-control" id="schedule_description" name="schedule_description" 
                                  rows="3" placeholder="Describe your gaming session..."><?php echo htmlspecialchars($_POST['schedule_description'] ?? ''); ?></textarea>
                        <div class="form-text">Optional: Add details about your gaming session</div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="schedule_date" class="form-label">Date *</label>
                                <input type="date" class="form-control date-picker" id="schedule_date" 
                                       name="schedule_date" 
                                       value="<?php echo htmlspecialchars($_POST['schedule_date'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control time-picker" id="start_time" 
                                       name="start_time" 
                                       value="<?php echo htmlspecialchars($_POST['start_time'] ?? '19:00'); ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" class="form-control time-picker" id="end_time" 
                                       name="end_time" 
                                       value="<?php echo htmlspecialchars($_POST['end_time'] ?? '21:00'); ?>" 
                                       required>
                                <div class="form-text">
                                    Duration: <span id="duration_display" class="text-muted">--</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="recurring" class="form-label">Recurring</label>
                                <select class="form-select" id="recurring" name="recurring">
                                    <option value="None" <?php echo (($_POST['recurring'] ?? 'None') === 'None') ? 'selected' : ''; ?>>One-time session</option>
                                    <option value="Daily" <?php echo (($_POST['recurring'] ?? '') === 'Daily') ? 'selected' : ''; ?>>Daily</option>
                                    <option value="Weekly" <?php echo (($_POST['recurring'] ?? '') === 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="Monthly" <?php echo (($_POST['recurring'] ?? '') === 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_participants" class="form-label">Max Participants</label>
                                <input type="number" class="form-control" id="max_participants" 
                                       name="max_participants" min="1" max="50" 
                                       value="<?php echo htmlspecialchars($_POST['max_participants'] ?? 1); ?>">
                                <div class="form-text">Maximum number of participants (including yourself)</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tip:</strong> After creating a schedule, you can convert it to an event to invite friends!
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="schedules.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-1"></i>Create Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="card shadow mt-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lightbulb me-2 text-warning"></i>Quick Tips
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex mb-3">
                            <i class="fas fa-clock text-primary me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Time Management</h6>
                                <p class="small text-muted mb-0">Schedule sessions in 2-3 hour blocks for optimal gaming experience.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex mb-3">
                            <i class="fas fa-repeat text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Recurring Sessions</h6>
                                <p class="small text-muted mb-0">Use recurring schedules for regular gaming habits.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex mb-3">
                            <i class="fas fa-users text-info me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Group Gaming</h6>
                                <p class="small text-muted mb-0">Set participant limits based on your game's team sizes.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex mb-3">
                            <i class="fas fa-trophy text-warning me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Events</h6>
                                <p class="small text-muted mb-0">Convert schedules to events for tournaments and competitions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate duration
    function updateDuration() {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const durationDisplay = document.getElementById('duration_display');
        
        if (startTime && endTime) {
            const start = new Date(`2000-01-01T${startTime}`);
            const end = new Date(`2000-01-01T${endTime}`);
            const duration = (end - start) / (1000 * 60 * 60); // Convert to hours
            
            if (duration > 0) {
                durationDisplay.textContent = `${duration.toFixed(1)} hours`;
                durationDisplay.className = 'text-success';
            } else {
                durationDisplay.textContent = 'Invalid time range';
                durationDisplay.className = 'text-danger';
            }
        }
    }

    // Add event listeners for time inputs
    document.getElementById('start_time').addEventListener('change', updateDuration);
    document.getElementById('end_time').addEventListener('change', updateDuration);
    
    // Initial calculation
    updateDuration();

    // Form validation
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        
        if (startTime && endTime) {
            const start = new Date(`2000-01-01T${startTime}`);
            const end = new Date(`2000-01-01T${endTime}`);
            
            if (end <= start) {
                e.preventDefault();
                alert('End time must be after start time');
                return false;
            }
        }
        
        const scheduleDate = document.getElementById('schedule_date').value;
        const today = new Date().toISOString().split('T')[0];
        
        if (scheduleDate < today) {
            e.preventDefault();
            alert('Schedule date must be today or in the future');
            return false;
        }
        
        return true;
    });
});
</script>

<?php require_once 'footer.php'; ?>