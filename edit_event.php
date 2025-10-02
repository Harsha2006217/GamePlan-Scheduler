<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    setMessage('error', 'Invalid event ID.');
    header('Location: index.php');
    exit;
}
$event = getEventById($id, getUserId());
if (!$event) {
    setMessage('error', 'Event not found or no permission.');
    header('Location: index.php');
    exit;
}
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
    $result = editEvent($id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends);
    if ($result === true) {
        setMessage('success', 'Event updated.');
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
    <title>Edit Event - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; }
        header { background: #1e1e1e; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .nav-link { color: #ffffff; margin: 0 15px; text-decoration: none; font-size: 1.1em; transition: color 0.3s; }
        .nav-link:hover { color: #007bff; }
        .navbar-toggler { border-color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-control, .form-select, textarea { background: #2c2c2c; color: #fff; border: 1px solid #ddd; transition: border 0.3s; }
        .form-control:focus, .form-select:focus, textarea:focus { border-color: #007bff; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; font-size: 0.9em; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php">Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php">Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="container">
        <h2>Edit Event <i class="bi bi-pencil-square"></i></h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateEventForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title (max 100 chars)</label>
                <input type="text" class="form-control" id="title" name="title" required maxlength="100" value="<?php echo htmlspecialchars($event['title']); ?>">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($event['date']); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time (HH:MM)</label>
                <input type="time" class="form-control" id="time" name="time" required value="<?php echo htmlspecialchars($event['time']); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (max 500 chars)</label>
                <textarea class="form-control" id="description" name="description" maxlength="500"><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Reminder</label>
                <select class="form-select" id="reminder" name="reminder">
                    <option value="">None</option>
                    <option value="1 hour before" <?php if ($event['reminder'] == '1 hour before') echo 'selected'; ?>>1 hour before</option>
                    <option value="1 day before" <?php if ($event['reminder'] == '1 day before') echo 'selected'; ?>>1 day before</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="schedule_id" class="form-label">Link to Schedule (optional)</label>
                <select class="form-select" id="schedule_id" name="schedule_id">
                    <option value="">None</option>
                    <?php foreach ($schedules as $sched): ?>
                        <option value="<?php echo $sched['schedule_id']; ?>" <?php if ($sched['schedule_id'] == $event['schedule_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sched['game_titel'] . ' - ' . $sched['date']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" id="shared_<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], $event['shared_friends'])) echo 'checked'; ?>>
                        <label class="form-check-label" for="shared_<?php echo $friend['user_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update Event</button>
        </form>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | <a href="#" style="color: #aaa;">Privacy</a> | <a href="#" style="color: #aaa;">Contact</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reuse validation from add_event
        function validateEventForm() {
            // Same as add_event.js
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