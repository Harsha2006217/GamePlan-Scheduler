<?php
// edit_event.php: Edit existing event
require_once 'functions.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = ? AND user_id = ?');
$stmt->execute([$id, getUserId()]);
$event = $stmt->fetch();
if (!$event) {
    setMessage('danger', 'Event not found.');
    header('Location: index.php');
    exit;
}
$schedules = getSchedules(getUserId());
$friends = getFriends(getUserId());
$selected_shared = explode(',', $event['shared_with'] ?? '');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $reminder = $_POST['reminder'] ?? 'none';
    $shared_with = $_POST['shared_with'] ?? [];
    $result = editEvent($id, $schedule_id, $title, $date, $time, $description, $reminder, $shared_with);
    if ($result === true) {
        setMessage('success', 'Event updated.');
        header('Location: index.php');
        exit;
    } else {
        setMessage('danger', $result);
    }
}
$msg = getMessage();
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
        .section { background: #2c2c2c; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-control, .form-select { background: #2c2c2c; border: 1px solid #444; color: #fff; }
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
        <nav>
            <a href="index.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="add_friend.php">Add Friend</a>
            <a href="add_schedule.php">Add Schedule</a>
            <a href="add_event.php">Add Event</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Edit Event</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="schedule_id" class="form-label">Linked Schedule</label>
                    <select class="form-select" id="schedule_id" name="schedule_id" required>
                        <option value="">Select Schedule</option>
                        <?php foreach ($schedules as $sched): ?>
                            <option value="<?php echo $sched['schedule_id']; ?>" <?php echo $sched['schedule_id'] == $event['schedule_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sched['game'] . ' on ' . $sched['date']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $event['date']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="time" class="form-label">Time</label>
                    <input type="time" class="form-control" id="time" name="time" value="<?php echo $event['time']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Share With Friends</label>
                    <?php foreach ($friends as $friend): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="shared_with[]" value="<?php echo $friend['friend_user_id']; ?>" <?php echo in_array($friend['friend_user_id'], $selected_shared) ? 'checked' : ''; ?>>
                            <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-3">
                    <label for="reminder" class="form-label">Reminder</label>
                    <select class="form-select" id="reminder" name="reminder">
                        <option value="none" <?php echo $event['reminder'] == 'none' ? 'selected' : ''; ?>>None</option>
                        <option value="1hour" <?php echo $event['reminder'] == '1hour' ? 'selected' : ''; ?>>1 Hour Before</option>
                        <option value="1day" <?php echo $event['reminder'] == '1day' ? 'selected' : ''; ?>>1 Day Before</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Event</button>
            </form>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
</body>
</html>