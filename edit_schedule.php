<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    setMessage('error', 'Invalid ID.');
    header('Location: index.php');
    exit;
}
$schedule = getScheduleById($id, getUserId());
if (!$schedule) {
    setMessage('error', 'No schedule or permission.');
    header('Location: index.php');
    exit;
}
$games = getGames();
$friends = getFriends(getUserId());
$shared_friends = explode(',', $schedule['friends'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $game_id = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] . ':00';
    $friends_arr = $_POST['friends'] ?? [];
    $result = editSchedule($id, $game_id, $date, $time, $friends_arr);
    if ($result === true) {
        setMessage('success', 'Schedule updated.');
        header('Location: index.php');
        exit;
    } else {
        setMessage('error', $result);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; }
        header { background: #1e1e1e; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .nav-link { color: #ffffff; margin: 0 15px; text-decoration: none; font-size: 1.1em; transition: color 0.3s; }
        .nav-link:hover { color: #007bff; }
        .navbar-toggler { border-color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-control, .form-select { background: #2c2c2c; color: #fff; border: 1px solid #ddd; transition: border 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #007bff; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; font-size: 0.9em; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php">Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php">Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="container">
        <h2>Edit Schedule <i class="bi bi-pencil-square"></i></h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateScheduleForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game <i class="bi bi-joystick"></i></label>
                <select class="form-select" id="game_id" name="game_id" required aria-label="Select game">
                    <option value="">Choose...</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>" <?php if ($game['game_id'] == $schedule['game_id']) echo 'selected'; ?>><?php echo htmlspecialchars($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date <i class="bi bi-calendar-date"></i></label>
                <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($schedule['date']); ?>" aria-label="Schedule date">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Time (HH:MM) <i class="bi bi-clock"></i></label>
                <input type="time" class="form-control" id="time" name="time" required value="<?php echo substr(htmlspecialchars($schedule['time']), 0, 5); ?>" aria-label="Schedule time">
            </div>
            <div class="mb-3">
                <label class="form-label">Share with Friends <i class="bi bi-share-fill"></i></label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" id="friend_<?php echo $friend['user_id']; ?>" <?php if (in_array($friend['user_id'], $shared_friends)) echo 'checked'; ?>>
                        <label class="form-check-label" for="friend_<?php echo $friend['user_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update <i class="bi bi-save"></i></button>
        </form>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | <a href="#" style="color: #aaa;">Privacy</a> | <a href="#" style="color: #aaa;">Contact</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateScheduleForm() {
            // Same as add
            return true;  // Client validation as before
        }
    </script>
</body>
</html>