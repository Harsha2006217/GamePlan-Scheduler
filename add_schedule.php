<?php
// add_schedule.php: Form to add new schedule
// Validates input, adds to DB if valid
// Dark theme, responsive, beautiful UI with game select and friend checkboxes

require_once 'functions.php';
requireLogin();
checkTimeout();
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
        setMessage('success', 'Schedule added successfully.');
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
                        <li class="nav-item"><a class="nav-link active" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
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
            <h3 class="section-title"><i class="bi bi-calendar-plus me-2"></i>Add New Schedule</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="game_id" class="form-label">Select Game</label>
                    <select class="form-select" id="game_id" name="game_id" required>
                        <option value="">Choose a game...</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="game" class="form-label">Game Name (Custom)</label>
                    <input type="text" class="form-control" id="game" name="game" placeholder="Enter custom game name if needed">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="time" name="time" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Friends to Share With</label>
                    <div class="friends-grid">
                        <?php foreach ($friends as $friend): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['friend_user_id']; ?>" id="friend_<?php echo $friend['friend_user_id']; ?>">
                                <label class="form-check-label" for="friend_<?php echo $friend['friend_user_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?> (<?php echo $friend['calculated_status']; ?>)</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reminder" class="form-label">Set Reminder</label>
                    <select class="form-select" id="reminder" name="reminder">
                        <option value="none">None</option>
                        <option value="1hour">1 Hour Before</option>
                        <option value="1day">1 Day Before</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-plus me-2"></i>Add Schedule</button>
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