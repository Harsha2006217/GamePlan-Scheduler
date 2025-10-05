<?php
// add_schedule.php - Add Schedule Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to add new schedules with game, date, time, friends.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$games = getGames();
$friends = getFriends($userId);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameId = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $selectedFriends = $_POST['friends'] ?? [];
    $error = addSchedule($userId, $gameId, $date, $time, $selectedFriends);
    if (!$error) {
        setMessage('success', 'Schedule added successfully!');
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
    <title>Add Schedule - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Add Schedule</h2>
        <form method="POST" onsubmit="return validateScheduleForm();">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game</label>
                <select id="game_id" name="game_id" class="form-select" required>
                    <option value="">Select Game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo safeEcho($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time</label>
                <input type="time" id="time" name="time" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="friends[]" value="<?php echo $friend['user_id']; ?>">
                        <label class="form-check-label"><?php echo safeEcho($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Add Schedule</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>