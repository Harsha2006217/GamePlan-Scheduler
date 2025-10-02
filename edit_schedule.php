<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

$schedule_id = $_GET['schedule_id'] ?? 0;

if (!is_numeric($schedule_id)) {
    setMessage('danger', 'Invalid schedule ID');
    header('Location: index.php');
    exit;
}

$schedule = getScheduleById($schedule_id);

if (!$schedule) {
    setMessage('danger', 'Schedule not found');
    header('Location: index.php');
    exit;
}

$games = getGames();
$friends = getFriends(getUserId());

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();

    $game_id = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friends_arr = $_POST['friends'] ?? [];

    $result = editSchedule($schedule_id, $game_id, $date, $time, $friends_arr);
    if ($result === true) {
        setMessage('success', 'Schedule updated successfully');
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
    <title>Edit Schedule - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Schedule</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo $msg['message']; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game</label>
                <select class="form-select" id="game_id" name="game_id" required>
                    <option value="">Select a game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>" <?php if ($game['game_id'] == $schedule['game_id']) echo 'selected'; ?>><?php echo $game['titel']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required value="<?php echo $schedule['date']; ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" class="form-control" id="time" name="time" required value="<?php echo $schedule['time']; ?>">
            </div>
            <div class="mb-3">
                <label>Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], explode(',', $schedule['friends']))) echo 'checked'; ?>>
                        <label class="form-check-label"><?php echo $friend['username']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</body>
</html>