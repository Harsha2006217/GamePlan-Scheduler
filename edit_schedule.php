<?php
$page_title = "Edit Schedule";
require_once 'header.php';

$user_id = getCurrentUserId();
$error = '';
$success = '';

// Get schedule ID from URL
$schedule_id = $_GET['id'] ?? '';
if (empty($schedule_id)) {
    header('Location: schedules.php');
    exit();
}

// Get schedule details
$query = "SELECT s.*, g.game_title 
          FROM schedules s 
          JOIN games g ON s.game_id = g.game_id 
          WHERE s.schedule_id = :schedule_id AND s.user_id = :user_id";
$stmt = $gameplan->conn->prepare($query);
$stmt->bindParam(':schedule_id', $schedule_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$schedule = $stmt->fetch();

if (!$schedule) {
    header('Location: schedules.php?error=Schedule not found');
    exit();
}

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

        // Update schedule
        $query = "UPDATE schedules SET 
                  game_id = :game_id,
                  schedule_title = :schedule_title,
                  schedule_description = :schedule_description,
                  schedule_date = :schedule_date,
                  start_time = :start_time,
                  end_time = :end_time,
                  recurring = :recurring,
                  max_participants = :max_participants
                  WHERE schedule_id = :schedule_id AND user_id = :user_id";
        
        $stmt = $gameplan->conn->prepare($query);
        $stmt->bindParam(':game_id', $game_id);
        $stmt->bindParam(':schedule_title', $schedule_title);
        $stmt->bindParam(':schedule_description', $schedule_description);
        $stmt->bindParam(':schedule_date', $schedule_date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->bindParam(':recurring', $recurring);
        $stmt->bindParam(':max_participants', $max_participants);
        $stmt->bindParam(':schedule_id', $schedule_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            $success = "Schedule updated successfully!";
            
            // Create notification
            $gameplan->createNotification(
                $user_id,
                'Schedule Updated',
                "Your schedule '{$schedule_title}' has been updated.",
                'System',
                $schedule_id
            );
        } else {
            throw new Exception("Failed to update schedule");
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
                <i class="fas fa-edit me-2 text-primary"></i>Edit Schedule
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
            <a href="schedules.php" class="btn btn-sm btn-success">View All Schedules</a>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-edit me-2 text-primary"></i>
                    Edit Schedule Details
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
                                            <?php echo ($schedule['game_id'] == $game['game_id'] || (isset($_POST['game_id']) && $_POST['game_id'] == $game['game_id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($game['game_title']); ?>
                                            <?php if ($game['favorite']): ?> ⭐<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_title" class="form-label">Schedule Title *</label>
                                <input type="text" class="form-control" id="schedule_title" name="schedule_title" 
                                       value="<?php echo htmlspecialchars($_POST['schedule_title'] ?? $schedule['schedule_title']); ?>" 
                                       required maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="schedule_description" class="form-label">Description</label>
                        <textarea class="form-control" id="schedule_description" name="schedule_description" 
                                  rows="3"><?php echo htmlspecialchars($_POST['schedule_description'] ?? $schedule['schedule_description']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="schedule_date" class="form-label">Date *</label>
                                <input type="date" class="form-control date-picker" id="schedule_date" 
                                       name="schedule_date" 
                                       value="<?php echo htmlspecialchars($_POST['schedule_date'] ?? $schedule['schedule_date']); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control time-picker" id="start_time" 
                                       name="start_time" 
                                       value="<?php echo htmlspecialchars($_POST['start_time'] ?? $schedule['start_time']); ?>" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" class="form-control time-picker" id="end_time" 
                                       name="end_time" 
                                       value="<?php echo htmlspecialchars($_POST['end_time'] ?? $schedule['end_time']); ?>" 
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
                                    <option value="None" <?php echo (($_POST['recurring'] ?? $schedule['recurring']) === 'None') ? 'selected' : ''; ?>>One-time session</option>
                                    <option value="Daily" <?php echo (($_POST['recurring'] ?? $schedule['recurring']) === 'Daily') ? 'selected' : ''; ?>>Daily</option>
                                    <option value="Weekly" <?php echo (($_POST['recurring'] ?? $schedule['recurring']) === 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="Monthly" <?php echo (($_POST['recurring'] ?? $schedule['recurring']) === 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_participants" class="form-label">Max Participants</label>
                                <input type="number" class="form-control" id="max_participants" 
                                       name="max_participants" min="1" max="50" 
                                       value="<?php echo htmlspecialchars($_POST['max_participants'] ?? $schedule['max_participants']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="schedules.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedule Info -->
        <div class="card shadow mt-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2 text-info"></i>Schedule Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Created:</th>
                                <td><?php echo date('M j, Y g:i A', strtotime($schedule['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Original Game:</th>
                                <td><?php echo htmlspecialchars($schedule['game_title']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <?php
                                    $schedule_datetime = $gameplan->formatDateTime($schedule['schedule_date'], $schedule['start_time']);
                                    $is_upcoming = strtotime($schedule_datetime) > time();
                                    $status_class = $is_upcoming ? 'bg-warning' : 'bg-secondary';
                                    $status_text = $is_upcoming ? 'Upcoming' : 'Completed';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid gap-2">
                            <a href="add_event.php?schedule_id=<?php echo $schedule['schedule_id']; ?>" 
                               class="btn btn-success btn-sm">
                                <i class="fas fa-trophy me-1"></i>Convert to Event
                            </a>
                            <a href="delete.php?type=schedule&id=<?php echo $schedule['schedule_id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this schedule?')">
                                <i class="fas fa-trash me-1"></i>Delete Schedule
                            </a>
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
            const duration = (end - start) / (1000 * 60 * 60);
            
            if (duration > 0) {
                durationDisplay.textContent = `${duration.toFixed(1)} hours`;
                durationDisplay.className = 'text-success';
            } else {
                durationDisplay.textContent = 'Invalid time range';
                durationDisplay.className = 'text-danger';
            }
        }
    }

    document.getElementById('start_time').addEventListener('change', updateDuration);
    document.getElementById('end_time').addEventListener('change', updateDuration);
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
        return true;
    });
});
</script>

<?php require_once 'footer.php'; ?>