<?php
session_start();
require 'functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$schedules = getSchedules($user_id);
$friends = getFriends($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? null;
    $shared_friends = $_POST['shared_friends'] ?? [];
    if (strlen($title) <= 100 && !empty($title) && validateInput($date, 'date') && validateInput($time, 'time') && strtotime($date) >= time()) {
        if (addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends)) {
            header('Location: events.php');
            exit;
        } else {
            $message = 'Failed to add event.';
        }
    } else {
        $message = 'Invalid input.';
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
    <link rel="stylesheet" href="../CSS/style.css">
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
        <form method="post">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" class="form-control" id="time" name="time" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Reminder</label>
                <select class="form-control" id="reminder" name="reminder">
                    <option value="">None</option>
                    <option value="1 hour before">1 hour before</option>
                    <option value="1 day before">1 day before</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="schedule_id" class="form-label">Link to Schedule (optional)</label>
                <select class="form-control" id="schedule_id" name="schedule_id">
                    <option value="">Select Schedule</option>
                    <?php foreach ($schedules as $schedule): ?>
                        <option value="<?php echo $schedule['schedule_id']; ?>"><?php echo htmlspecialchars($schedule['game_title']); ?> - <?php echo htmlspecialchars($schedule['date']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" id="shared<?php echo $friend['user_id']; ?>">
                        <label class="form-check-label" for="shared<?php echo $friend['user_id']; ?>">
                            <?php echo htmlspecialchars($friend['username']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Add Event</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>