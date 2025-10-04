<?php
// edit_event.php: Form to edit existing event
// Loads current data, validates updates
// Dark theme, responsive, beautiful UI

require_once 'functions.php';
requireLogin();
checkTimeout();
$event_id = (int)($_GET['id'] ?? 0);
$events = getEvents(getUserId());
$event = null;
foreach ($events as $evt) {
    if ($evt['event_id'] == $event_id) {
        $event = $evt;
        break;
    }
}
if (!$event) {
    setMessage('danger', 'Event not found.');
    header('Location: index.php');
    exit;
}
$schedules = getSchedules(getUserId());
$friends = getFriends(getUserId());
$selected_shared = $event['shared_with'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $reminder = $_POST['reminder'] ?? 'none';
    $shared_with = $_POST['shared_with'] ?? [];
    $result = editEvent($event_id, $schedule_id, $title, $date, $time, $description, $reminder, $shared_with);
    if ($result === true) {
        setMessage('success', 'Event updated successfully.');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php"><i class="bi bi-controller me-2"></i>GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h3 class="section-title"><i class="bi bi-pencil-square me-2"></i>Edit Event</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="schedule_id" class="form-label">Link to Schedule (Optional)</label>
                    <select class="form-select" id="schedule_id" name="schedule_id">
                        <option value="">No link</option>
                        <?php foreach ($schedules as $sched): ?>
                            <option value="<?php echo $sched['schedule_id']; ?>" <?php echo $sched['schedule_id'] == $event['schedule_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sched['game_titel'] . ' on ' . $sched['date']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required maxlength="100" value="<?php echo htmlspecialchars($event['title']); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo $event['date']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="time" name="time" required value="<?php echo substr($event['time'], 0, 5); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" maxlength="500"><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Share With Friends</label>
                    <div class="friends-grid">
                        <?php foreach ($friends as $friend): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shared_with[]" value="<?php echo $friend['friend_user_id']; ?>" id="share_<?php echo $friend['friend_user_id']; ?>" <?php echo in_array($friend['friend_user_id'], $selected_shared) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="share_<?php echo $friend['friend_user_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?> (<?php echo $friend['calculated_status']; ?>)</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reminder" class="form-label">Set Reminder</label>
                    <select class="form-select" id="reminder" name="reminder">
                        <option value="none" <?php echo $event['reminder'] === 'none' ? 'selected' : ''; ?>>None</option>
                        <option value="1hour" <?php echo $event['reminder'] === '1hour' ? 'selected' : ''; ?>>1 Hour Before</option>
                        <option value="1day" <?php echo $event['reminder'] === '1day' ? 'selected' : ''; ?>>1 Day Before</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Update Event</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy Policy | Contact Support
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>