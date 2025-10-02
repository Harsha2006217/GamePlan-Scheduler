<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$id = $_GET['id'] ?? 0;
$user_id = getUserId();
$event = getEventById($id, $user_id);
if (!$event) {
    setMessage('danger', 'Event not found.');
    header('Location: index.php');
    exit;
}
$schedules = getSchedules($user_id);
$friends = getFriends($user_id);
$shared_friends = $event['shared_friends'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        setMessage('success', 'Event updated successfully.');
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
    <title>Edit Event - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; }
        header { background: #1e1e1e; padding: 15px; text-align: center; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: sticky; top: 0; z-index: 1; }
        nav a { color: #fff; margin: 0 15px; text-decoration: none; }
        nav a:hover { color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-control { background: #2c2c2c; border: 1px solid #444; color: #fff; }
        .form-select { background: #2c2c2c; border: 1px solid #444; color: #fff; }
        .btn-primary { background: #007bff; border: none; }
        .btn-primary:hover { background: #0069d9; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; }
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
        <h2>Edit Event</h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" required max length="100" value="<?php echo htmlspecialchars($event['title']); ?>">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($event['date']); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" class="form-control" id="time" name="time" required value="<?php echo htmlspecialchars($event['time']); ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
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
                        <input class="form-check-input" type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], $shared_friends)) echo 'checked'; ?>>
                        <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
</body>
</html>