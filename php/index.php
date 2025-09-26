<?php
session_start();
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user_id = getCurrentUserId();

// Fetch data
$friends = getFriends($user_id);
$favorite_games = getFavoriteGames($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);

// Merge schedules and events for calendar
$calendar_items = array_merge($schedules, $events);
usort($calendar_items, function($a, $b) {
    return strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <header class="bg-dark text-white p-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-gamepad"></i> GamePlan Scheduler</h1>
                <nav>
                    <a href="index.php" class="btn btn-outline-light me-2">Home</a>
                    <a href="profile.php" class="btn btn-outline-light me-2">Profile</a>
                    <a href="friends.php" class="btn btn-outline-light me-2">Friends</a>
                    <a href="schedules.php" class="btn btn-outline-light me-2">Schedules</a>
                    <a href="events.php" class="btn btn-outline-light me-2">Events</a>
                    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                </nav>
            </div>
        </div>
    </header>
    <main class="container my-4">
        <h2>Welcome to Your Dashboard</h2>
        <div class="row">
            <div class="col-md-4">
                <h3>Friends</h3>
                <ul class="list-group">
                    <?php foreach ($friends as $friend): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($friend['username']); ?>
                            <span class="badge bg-<?php echo (time() - strtotime($friend['last_activity']) < 300) ? 'success' : 'secondary'; ?>">
                                <?php echo (time() - strtotime($friend['last_activity']) < 300) ? 'Online' : 'Offline'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h3>Favorite Games</h3>
                <ul class="list-group">
                    <?php foreach ($favorite_games as $game): ?>
                        <li class="list-group-item"><?php echo htmlspecialchars($game['title'] . ' - ' . $game['description']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h3>Upcoming Schedules & Events</h3>
                <div id="calendar">
                    <?php foreach ($calendar_items as $item): ?>
                        <div class="card mb-2 bg-secondary text-white">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($item['title'] ?? $item['game_title']); ?> - <?php echo htmlspecialchars($item['date'] . ' ' . $item['time']); ?></h5>
                                <?php if (isset($item['description'])): ?>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php endif; ?>
                                <?php if (isset($item['reminder']) && $item['reminder'] !== 'geen'): ?>
                                    <p>Reminder: <?php echo htmlspecialchars($item['reminder']); ?></p>
                                <?php endif; ?>
                                <?php if (isset($item['shared_with'])): ?>
                                    <p>Shared with: <?php echo htmlspecialchars(implode(', ', $item['shared_with'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-dark text-white text-center p-3">
        <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. <a href="privacy.php">Privacy Policy</a> | <a href="#">Contact</a></p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>