<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$schedules = getSchedules(getUserId());
$friends = getFriends(getUserId());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? '';
    $shared_friends = $_POST['shared_friends'] ?? [];
    $result = addEvent($title, $date, $time, $description, $reminder, $schedule_id, $shared_friends);
    if ($result === true) {
        setMessage('success', 'Event added successfully.');
        header('Location: index.php');
        exit;
    } else {
        setMessage('error', $result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Reuse styling */
        body { background-color: #121212; color: #ffffff; font-family: 'Sans-serif', Arial; margin: 0; padding: 0; }
        header { background: #1e1e1e; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        header nav a { color: #ffffff; margin: 0 15px; text-decoration: none; font-size: 1.1em; transition: color 0.3s; }
        header nav a:hover { color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-control, .form-select, textarea { background: #2c2c2c; color: #fff; border: 1px solid #dddddd; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { margin-bottom: 20px; border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaaaaa; font-size: 0.9em; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
<body>
    <header>
        <h1>GamePlan Scheduler</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="friends.php">Friends</a>
            <a href="add_schedule.php">Add Schedule</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <h2>Add Event</h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateEventForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title (max 100 chars)</label>
                <input type="text" class="form-control" id="title" name="title" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time (HH:MM)</label>
                <input type="time" class="form-control" id="time" name="time" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (max 500 chars)</label>
                <textarea class="form-control" id="description" name="description" maxlength="500"></textarea>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Reminder</label>
                <select class="form-select" id="reminder" name="reminder">
                    <option value="">None</option>
                    <option value="1 hour before">1 hour before</option>
                    <option value="1 day before">1 day before</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="schedule_id" class="form-label">Link to Schedule (optional)</label>
                <select class="form-select" id="schedule_id" name="schedule_id">
                    <option value="">None</option>
                    <?php foreach ($schedules as $sched): ?>
                        <option value="<?php echo $sched['schedule_id']; ?>"><?php echo htmlspecialchars($sched['game_titel'] . ' - ' . $sched['date']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" id="shared_<?php echo $friend['user_id']; ?>">
                        <label class="form-check-label" for="shared_<?php echo $friend['user_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Add Event</button>
        </form>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | <a href="#" style="color: #aaaaaa;">Privacy</a> | <a href="#" style="color: #aaaaaa;">Contact</a>
    </footer>
    <script>
        // Client-side validation for event form
        function validateEventForm() {
            const title = document.getElementById('title').value.trim();
            const date = document.getElementById('date').value;
            const time = document.getElementById('time').value;
            const desc = document.getElementById('description').value;
            if (title === '' || title.length > 100 || /^\s*$/.test(title)) {
                alert('Title required, max 100 chars, not just spaces.');
                return false;
            }
            if (new Date(date) < new Date().setHours(0,0,0,0)) {
                alert('Date must be in the future.');
                return false;
            }
            if (!time.match(/^([01]\d|2[0-3]):[0-5]\d$/)) {
                alert('Invalid time format (HH:MM).');
                return false;
            }
            if (desc.length > 500) {
                alert('Description max 500 chars.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>