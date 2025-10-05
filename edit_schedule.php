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

$games = getGames();
$friends = getFriends($userId);
$selectedFriends = explode(',', $schedule['friends']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameId = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $selectedFriendsPost = $_POST['friends'] ?? [];
    $error = editSchedule($userId, $id, $gameId, $date, $time, $selectedFriendsPost);
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
                <label for="game_id" class="form-label">Game</label>
                <select id="game_id" name="game_id" class="form-select" required>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>" <?php if ($game['game_id'] == $schedule['game_id']) echo 'selected'; ?>><?php echo safeEcho($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo safeEcho($schedule['date']); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" id="time" name="time" class="form-control" required value="<?php echo safeEcho($schedule['time']); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="friends[]" value="<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], $selectedFriends)) echo 'checked'; ?>>
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