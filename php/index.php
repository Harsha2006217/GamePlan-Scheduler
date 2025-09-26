<?php
session_start();
require 'functions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$user = getUser($user_id);
$friends = getFriends($user_id);
$favorites = getFavoriteGames($user_id);
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-gamepad"></i> GamePlan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="friends.php">Friends</a></li>
                    <li class="nav-item"><a class="nav-link" href="schedules.php">Schedules</a></li>
                    <li class="nav-item"><a class="nav-link" href="events.php">Events</a></li>
                </ul>
                <span class="navbar-text">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-light ms-2">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
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
                <h3 class="mt-3">Favorite Games</h3>
                <ul class="list-group">
                    <?php foreach ($favorites as $game): ?>
                        <li class="list-group-item"><?php echo htmlspecialchars($game['title']); ?> - <?php echo htmlspecialchars($game['description']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-8">
                <h3>Calendar</h3>
                <div class="row">
                    <?php foreach ($calendar_items as $item): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['title'] ?? $item['game_title']); ?> - <?php echo htmlspecialchars($item['date']); ?> at <?php echo htmlspecialchars($item['time']); ?></h5>
                                    <?php if (isset($item['description'])): ?>
                                        <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['reminder'])): ?>
                                        <p class="card-text"><small>Reminder: <?php echo htmlspecialchars($item['reminder']); ?></small></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['shared_with'])): ?>
                                        <p class="card-text"><small>Shared with: <?php echo htmlspecialchars(implode(', ', $item['shared_with'])); ?></small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>