<?php
// edit_schedule.php - Edit Schedule Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to edit existing schedules.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$schedules = getSchedules($userId);
$schedule = array_filter($schedules, function($s) use ($id) { return $s['schedule_id'] == $id; });
$schedule = reset($schedule);
if (!$schedule) {
    setMessage('danger', 'Schedule not found or no permission.');
    header("Location: index.php");
    exit;
}

$friends = getFriends($userId);
$selectedFriends = explode(',', $schedule['friends']);
$selectedSharedWith = explode(',', $schedule['shared_with']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameTitle = $_POST['game_title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $selectedFriendsPost = $_POST['friends'] ?? [];
    $selectedSharedWithPost = $_POST['shared_with'] ?? [];
    $error = editSchedule($userId, $id, $gameTitle, $date, $time, $selectedFriendsPost, $selectedSharedWithPost);
    if (!$error) {
        setMessage('success', 'Schedule updated successfully!');
        header("Location: index.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Edit Schedule</h2>
        <form method="POST" onsubmit="return validateScheduleForm();">
            <div class="mb-3">
                <label for="game_title" class="form-label">Game Title</label>
                <input type="text" id="game_title" name="game_title" class="form-control" required maxlength="100" value="<?php echo safeEcho($schedule['game_titel']); ?>" aria-label="Game Title">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo safeEcho($schedule['date']); ?>" aria-label="Date">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" id="time" name="time" class="form-control" required value="<?php echo safeEcho($schedule['time']); ?>" aria-label="Time">
            </div>
            <div class="mb-3">
                <label class="form-label">Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="friends[]" value="<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], $selectedFriends)) echo 'checked'; ?>>
                        <label class="form-check-label"><?php echo safeEcho($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Shared With</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="shared_with[]" value="<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], $selectedSharedWith)) echo 'checked'; ?>>
                        <label class="form-check-label"><?php echo safeEcho($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update Schedule</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>