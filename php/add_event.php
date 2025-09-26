<?php
session_start();
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user_id = getCurrentUserId();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? 'geen';
    $schedule_id = $_POST['schedule_id'] ?? null;
    $shared_friends = $_POST['shared_friends'] ?? [];
    if (validateInput($title, 'text') && validateInput($date, 'date') && validateInput($time, 'time')) {
        addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends);
        header('Location: events.php');
        exit;
    } else {
        $message = 'Invalid input.';
    }
}
$schedules = getSchedules($user_id);
$friends = getFriends($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-dark text-white">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-gamepad"></i> GamePlan</a>
            <div class="navbar-nav ms-auto">
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Add Event</h2>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <div class="mb-3">
                <label for="title" class="form-label">Title *</label>
                <input type="text" class="form-control" id="title" name="title" required maxlength="100">
                <div class="form-text">Event title (max 100 characters)</div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date" class="form-label">Date *</label>
                    <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="time" class="form-label">Time *</label>
                    <input type="time" class="form-control" id="time" name="time" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"></textarea>
                <div class="form-text">Optional description (max 500 characters)</div>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Reminder</label>
                <select class="form-control" id="reminder" name="reminder">
                    <option value="geen">None</option>
                    <option value="1 uur ervoor">1 hour before</option>
                    <option value="1 dag ervoor">1 day before</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="schedule_id" class="form-label">Link to Schedule (Optional)</label>
                <select class="form-control" id="schedule_id" name="schedule_id">
                    <option value="">Select Schedule</option>
                    <?php foreach ($schedules as $schedule): ?>
                        <option value="<?php echo $schedule['schedule_id']; ?>"><?php echo htmlspecialchars($schedule['game_title'] . ' - ' . $schedule['date']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <div class="row">
                    <?php if (empty($friends)): ?>
                        <div class="col-12">
                            <p class="text-muted">No friends to share with. <a href="friends.php">Add friends first</a>.</p>
                        </div>
                    <?php else: ?>
                        <div class="col-12 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllFriends()">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllFriends()">Clear All</button>
                        </div>
                        <?php foreach ($friends as $friend): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" id="shared<?php echo $friend['user_id']; ?>">
                                    <label class="form-check-label" for="shared<?php echo $friend['user_id']; ?>">
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
            <button type="submit" class="btn btn-primary">Add Event</button>
            <a href="events.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
    <footer class="bg-dark text-white text-center p-3">
        <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi.</p>
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