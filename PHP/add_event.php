<?php
// GamePlan Scheduler - Professional Event Creation
// Advanced form for creating new events with sharing capabilities

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$friends = getFriends($userId);
$schedules = getSchedules($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $reminder = trim($_POST['reminder'] ?? 'none');
    $scheduleId = !empty($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : null;
    $sharedFriends = $_POST['shared_friends'] ?? [];

    try {
        // Validate input
        if (empty($title)) {
            throw new Exception("Title is required");
        }

        if (strlen($title) > 100) {
            throw new Exception("Title cannot exceed 100 characters");
        }

        if (preg_match('/^\s*$/', $title)) {
            throw new Exception("Title cannot be only spaces");
        }

        $eventDateTime = strtotime("$date $time");
        if ($eventDateTime === false || $eventDateTime <= time()) {
            throw new Exception("Event must be in the future");
        }

        if (strlen($description) > 500) {
            throw new Exception("Description cannot exceed 500 characters");
        }

        $validReminders = ['none', '1hour', '1day'];
        if (!in_array($reminder, $validReminders)) {
            $reminder = 'none';
        }

        if ($scheduleId) {
            $schedule = getScheduleById($scheduleId, $userId);
            if (!$schedule) {
                throw new Exception("Invalid schedule selected");
            }
        }

        // Create event
        $eventId = addEvent($userId, $title, $date, $time, $description, $reminder, $sharedFriends, $scheduleId);

        $_SESSION['message'] = 'Event created successfully!';
        $_SESSION['message_type'] = 'success';

        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Add Event</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    <i class="fas fa-gamepad"></i> GamePlan Scheduler
                </a>
                <nav>
                    <ul class="d-flex">
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="friends.php">Friends</a></li>
                        <li><a href="schedules.php">Schedules</a></li>
                        <li><a href="events.php" class="active">Events</a></li>
                        <li><a href="?logout=1">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-trophy"></i> Create Event</h2>
                            <p class="text-muted mb-0">Organize tournaments, meetups, or special gaming events</p>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" novalidate>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="title" class="form-label">
                                            <i class="fas fa-heading"></i> Event Title *
                                        </label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               maxlength="100" required>
                                        <div class="form-text">Give your event a catchy name (max 100 characters)</div>
                                        <div class="invalid-feedback">
                                            Title is required and cannot be only spaces.
                                        </div>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label for="date" class="form-label">
                                            <i class="fas fa-calendar"></i> Date *
                                        </label>
                                        <input type="date" class="form-control" id="date" name="date"
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">
                                            Please select a future date.
                                        </div>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label for="time" class="form-label">
                                            <i class="fas fa-clock"></i> Time *
                                        </label>
                                        <input type="time" class="form-control" id="time" name="time" required>
                                        <div class="invalid-feedback">
                                            Please select a time.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left"></i> Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              maxlength="500"></textarea>
                                    <div class="form-text">Describe your event (max 500 characters)</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="reminder" class="form-label">
                                            <i class="fas fa-bell"></i> Reminder
                                        </label>
                                        <select class="form-select" id="reminder" name="reminder">
                                            <option value="none">No reminder</option>
                                            <option value="1hour">1 hour before</option>
                                            <option value="1day">1 day before</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="schedule_id" class="form-label">
                                            <i class="fas fa-link"></i> Link to Schedule (Optional)
                                        </label>
                                        <select class="form-select" id="schedule_id" name="schedule_id">
                                            <option value="">Select a gaming session...</option>
                                            <?php foreach ($schedules as $schedule): ?>
                                                <option value="<?php echo $schedule['schedule_id']; ?>">
                                                    <?php echo htmlspecialchars($schedule['game_title']); ?> - <?php echo date('M j, g:i A', strtotime($schedule['date'] . ' ' . $schedule['time'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Link this event to an existing gaming session</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-share"></i> Share with Friends
                                    </label>
                                    <div class="row">
                                        <?php if (empty($friends)): ?>
                                            <div class="col-12">
                                                <p class="text-muted">No friends to share with yet. <a href="friends.php">Add some friends</a> first!</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="col-12 mb-2">
                                                <div class="d-flex gap-2 mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllFriends()">Select All</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllFriends()">Clear All</button>
                                                </div>
                                            </div>
                                            <?php foreach ($friends as $friend): ?>
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="friend_<?php echo $friend['user_id']; ?>"
                                                               name="shared_friends[]" value="<?php echo $friend['user_id']; ?>">
                                                        <label class="form-check-label" for="friend_<?php echo $friend['user_id']; ?>">
                                                            <?php echo htmlspecialchars($friend['username']); ?>
                                                            <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?> ms-1">
                                                                <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-trophy"></i> Create Event
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Event Types Guide -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Event Types & Ideas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-trophy text-warning"></i> Tournaments</h6>
                                    <ul class="small mb-3">
                                        <li>1v1 Battles</li>
                                        <li>Team Competitions</li>
                                        <li>Speed Runs</li>
                                        <li>Custom Challenges</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-users text-info"></i> Meetups</h6>
                                    <ul class="small mb-0">
                                        <li>Casual Gaming Sessions</li>
                                        <li>New Game Launches</li>
                                        <li>LAN Parties</li>
                                        <li>Game Discussions</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
    <script>
        // Select/clear all friends
        function selectAllFriends() {
            document.querySelectorAll('input[name="shared_friends[]"]').forEach(cb => cb.checked = true);
        }

        function clearAllFriends() {
            document.querySelectorAll('input[name="shared_friends[]"]').forEach(cb => cb.checked = false);
        }

        // Character counter for description
        document.getElementById('description').addEventListener('input', function() {
            const maxLength = 500;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;

            let counter = this.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter form-text';
                this.parentNode.appendChild(counter);
            }

            counter.textContent = `${currentLength}/${maxLength} characters`;
            counter.style.color = remaining < 50 ? '#dc3545' : '#6c757d';
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title');
            const date = document.getElementById('date');
            const time = document.getElementById('time');
            const description = document.getElementById('description');

            let isValid = true;

            // Title validation
            if (!title.value.trim()) {
                title.classList.add('is-invalid');
                isValid = false;
            } else if (/^\s*$/.test(title.value)) {
                title.classList.add('is-invalid');
                isValid = false;
            } else {
                title.classList.remove('is-invalid');
            }

            // Date validation
            if (!date.value) {
                date.classList.add('is-invalid');
                isValid = false;
            } else {
                const selectedDate = new Date(date.value + 'T' + (time.value || '00:00'));
                const now = new Date();
                if (selectedDate <= now) {
                    date.classList.add('is-invalid');
                    isValid = false;
                } else {
                    date.classList.remove('is-invalid');
                }
            }

            // Time validation
            if (!time.value) {
                time.classList.add('is-invalid');
                isValid = false;
            } else {
                time.classList.remove('is-invalid');
            }

            // Description length
            if (description.value.length > 500) {
                description.classList.add('is-invalid');
                isValid = false;
            } else {
                description.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Set minimum date to today
        document.getElementById('date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>