<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

$schedules = getSchedules(getUserId());
$friends = getFriends(getUserId());

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();

    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? '';
    $friend_ids = $_POST['friend_ids'] ?? [];

    $result = addEvent($title, $date, $time, $description, $reminder, $schedule_id, $friend_ids);
    if ($result === true) {
        setMessage('success', 'Event added successfully');
        header('Location: index.php');
        exit;
    } else {
        setMessage('danger', $result);
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
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Event</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo $msg['message']; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" class="form-control" id="time" name="time" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"></textarea>
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
                <label for="schedule_id" class="form-label">Linked Schedule</label>
                <select class="form-select" id="schedule_id" name="schedule_id">
                    <option value="">None</option>
                    <?php foreach ($schedules as $schedule): ?>
                        <option value="<?php echo $schedule['schedule_id']; ?>"><?php echo $schedule['game_titel'] . ' - ' . $schedule['date'] . ' ' . $schedule['time']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Friends to Share With</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="friend_ids[]" value="<?php echo $friend['user_id']; ?>">
                        <label class="form-check-label"><?php echo $friend['username']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </div>
</body>
</html>