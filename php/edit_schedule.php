<?php
session_start();
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user_id = getCurrentUserId();
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: schedules.php');
    exit;
}
$schedules = getSchedules($user_id);
$schedule = array_filter($schedules, fn($s) => $s['schedule_id'] == $id);
if (empty($schedule)) {
    header('Location: schedules.php');
    exit;
}
$schedule = reset($schedule);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friends = $_POST['friends'] ?? [];
    if (validateInput($date, 'date') && validateInput($time, 'time') && $game_id) {
        editSchedule($id, $user_id, $game_id, $date, $time, $friends);
        header('Location: schedules.php');
        exit;
    } else {
        $message = 'Invalid input.';
    }
}
$games = getGames();
$friends_list = getFriends($user_id);
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
<body>
    <header class="bg-dark text-white p-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-edit"></i> Edit Schedule</h1>
                <nav>
                    <a href="schedules.php" class="btn btn-outline-light">Back</a>
                </nav>
            </div>
        </div>
    </header>
    <main class="container my-4">
        <h2>Edit Schedule</h2>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" onsubmit="return validateScheduleForm()">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game</label>
                <select class="form-control" id="game_id" name="game_id" required>
                    <option value="">Select Game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>" <?php echo ($game['game_id'] == $schedule['game_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($game['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" required value="<?php echo htmlspecialchars($schedule['date']); ?>" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" class="form-control" id="time" name="time" required value="<?php echo htmlspecialchars($schedule['time']); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Friends</label>
                <?php foreach ($friends_list as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" id="friend_<?php echo $friend['user_id']; ?>" <?php echo in_array($friend['user_id'], explode(',', $schedule['friends'])) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="friend_<?php echo $friend['user_id']; ?>">
                            <?php echo htmlspecialchars($friend['username']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update Schedule</button>
        </form>
    </main>
    <footer class="bg-dark text-white text-center p-3">
        <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi.</p>
    </footer>
    <script src="../JS/script.js"></script>
</body>
</html>