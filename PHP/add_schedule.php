<?php
// GamePlan Scheduler - Professional Schedule Creation
// Advanced form for creating new gaming session schedules

session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$games = getGames();
$friends = getFriends($userId);
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gameId = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friendsSelected = $_POST['friends'] ?? [];

    if (!empty($gameId) && validateInput($date, 'date') && validateInput($time, 'time') && strtotime($date) >= time()) {
        $friendsStr = implode(', ', array_map(function($id) use ($friends) {
            foreach ($friends as $f) if ($f['user_id'] == $id) return $f['username'];
            return '';
        }, $friendsSelected));

        // Create schedule
        $scheduleId = addSchedule($userId, $gameId, $date, $time, $friendsStr);

        if ($scheduleId) {
            $_SESSION['message'] = 'Schedule created successfully!';
            $_SESSION['message_type'] = 'success';

            header('Location: schedules.php');
            exit;
        } else {
            $message = 'Failed to add schedule.';
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
    <title>Add Schedule - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <h2>Add Schedule</h2>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game</label>
                <select class="form-control" id="game_id" name="game_id" required>
                    <option value="">Select Game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['title']); ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label class="form-label">Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" id="friend<?php echo $friend['user_id']; ?>">
                        <label class="form-check-label" for="friend<?php echo $friend['user_id']; ?>">
                            <?php echo htmlspecialchars($friend['username']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Add Schedule</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>