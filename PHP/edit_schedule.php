<?php
session_start();
require 'functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;
$schedule = null;
foreach (getSchedules($user_id) as $s) {
    if ($s['schedule_id'] == $id) {
        $schedule = $s;
        break;
    }
}
if (!$schedule) {
    header('Location: schedules.php');
    exit;
}
$games = getGames();
$friends = getFriends($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friends_selected = $_POST['friends'] ?? [];
    if (!empty($game_id) && validateInput($date, 'date') && validateInput($time, 'time')) {
        $friends_str = implode(', ', array_map(function($id) use ($friends) {
            foreach ($friends as $f) if ($f['user_id'] == $id) return $f['username'];
            return '';
        }, $friends_selected));
        if (editSchedule($id, $user_id, $game_id, $date, $time, $friends_str)) {
            header('Location: schedules.php');
            exit;
        } else {
            $message = 'Failed to edit schedule.';
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
    <title>Edit Schedule - GamePlan Scheduler</title>
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
        <h2>Edit Schedule</h2>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game</label>
                <select class="form-control" id="game_id" name="game_id" required>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>" <?php echo ($game['game_id'] == $schedule['game_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($game['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($schedule['date']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" class="form-control" id="time" name="time" value="<?php echo htmlspecialchars($schedule['time']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" id="friend<?php echo $friend['user_id']; ?>" <?php echo (strpos($schedule['friends'], $friend['username']) !== false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="friend<?php echo $friend['user_id']; ?>">
                            <?php echo htmlspecialchars($friend['username']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update Schedule</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>