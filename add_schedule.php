<?php
// add_schedule.php: Form to add schedule
require_once 'functions.php';
requireLogin();
$games = getGames();
$friends = getFriends(getUserId());
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $game_id = (int)($_POST['game_id'] ?? 0);
    $game = trim($_POST['game'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friends_selected = $_POST['friends'] ?? [];
    $reminder = $_POST['reminder'] ?? 'none';
    $result = addSchedule($game_id, $game, $date, $time, $friends_selected, $reminder);
    if ($result === true) {
        setMessage('success', 'Schedule added.');
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
    <title>Add Schedule - GamePlan Scheduler</title>
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
            <h3>Add Schedule</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="game_id" class="form-label">Game</label>
                    <select class="form-select" id="game_id" name="game_id" required>
                        <option value="">Select Game</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="game" class="form-label">Game Name</label>
                    <input type="text" class="form-control" id="game" name="game" required>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>
                <div class="mb-3">
                    <label for="time" class="form-label">Time</label>
                    <input type="time" class="form-control" id="time" name="time" required>
                </div>
                <div class="mb-3">
                    <label>Friends to Share With</label>
                    <?php foreach ($friends as $friend): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['friend_user_id']; ?>">
                            <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-3">
                    <label for="reminder" class="form-label">Reminder</label>
                    <select class="form-select" id="reminder" name="reminder">
                        <option value="none">None</option>
                        <option value="1hour">1 Hour Before</option>
                        <option value="1day">1 Day Before</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Schedule</button>
            </form>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
</body>
</html>