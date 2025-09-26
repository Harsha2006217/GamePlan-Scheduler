<?php
session_start();
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user_id = getCurrentUserId();

// Update last activity
updateLastActivity($user_id);

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
<body class="dark-theme">
    <?php include 'header.php'; ?>
    <div class="container mt-5">
        <h2>Welcome to Your Dashboard</h2>
        <div class="row">
            <div class="col-md-8">
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Kalender</h3>
                        <div>
                            <a href="add_schedule.php" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-plus"></i> Schema toevoegen
                            </a>
                            <a href="add_event.php" class="btn btn-success btn-sm">
                                <i class="fas fa-calendar-plus"></i> Evenement toevoegen
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($calendar_items)): ?>
                            <p class="text-center">Nog geen schema's of evenementen gepland.</p>
                        <?php else: ?>
                            <div class="calendar-items">
                                <?php foreach ($calendar_items as $item): ?>
                                    <div class="calendar-item mb-3 <?= $item['type'] === 'schedule' ? 'calendar-schedule' : 'calendar-event' ?>">
                                        <div class="card bg-dark-secondary">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <?php if ($item['type'] === 'schedule'): ?>
                                                        <i class="fas fa-gamepad me-2"></i>
                                                        <?= htmlspecialchars($item['game_titel']) ?>
                                                    <?php else: ?>
                                                        <i class="fas fa-calendar-day me-2"></i>
                                                        <?= htmlspecialchars($item['title']) ?>
                                                    <?php endif; ?>
                                                </h5>
                                                
                                                <p class="card-text">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <?= formatDate($item['date']) ?> om <?= formatTime($item['time']) ?>
                                                </p>
                                                
                                                <?php if (!empty($item['description'])): ?>
                                                    <p class="card-text">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <?= htmlspecialchars($item['description']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($item['reminder'])): ?>
                                                    <p class="card-text">
                                                        <i class="fas fa-bell me-2"></i>
                                                        Herinnering: <?= htmlspecialchars($item['reminder']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['type'] === 'schedule' && !empty($item['friends'])): ?>
                                                    <p class="card-text">
                                                        <i class="fas fa-user-friends me-2"></i>
                                                        Gedeeld met: <?= htmlspecialchars($item['friends']) ?>
                                                    </p>
                                                <?php elseif ($item['type'] === 'event' && !empty($item['shared_with'])): ?>
                                                    <p class="card-text">
                                                        <i class="fas fa-user-friends me-2"></i>
                                                        Gedeeld met: <?= htmlspecialchars(implode(', ', $item['shared_with'])) ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="btn-group">
                                                    <?php if ($item['type'] === 'schedule'): ?>
                                                        <a href="edit_schedule.php?id=<?= $item['schedule_id'] ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-edit"></i> Bewerken
                                                        </a>
                                                        <a href="delete_schedule.php?id=<?= $item['schedule_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Weet je zeker dat je dit schema wilt verwijderen?')">
                                                            <i class="fas fa-trash"></i> Verwijderen
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="edit_event.php?id=<?= $item['event_id'] ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-edit"></i> Bewerken
                                                        </a>
                                                        <a href="delete_event.php?id=<?= $item['event_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen?')">
                                                            <i class="fas fa-trash"></i> Verwijderen
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Friends section -->
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Vrienden</h4>
                        <a href="friends.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-friends"></i> Beheren
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($friends)): ?>
                            <p class="text-center">Nog geen vrienden toegevoegd.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($friends as $friend): ?>
                                    <li class="list-group-item bg-dark-secondary text-light d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($friend['username']) ?>
                                        <span class="badge <?= $friend['online'] ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                                            <?= $friend['online'] ? 'Online' : 'Offline' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Favorite games section -->
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Favoriete Games</h4>
                        <a href="profile.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-edit"></i> Profiel
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($favorite_games)): ?>
                            <p class="text-center">Nog geen favoriete games toegevoegd.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($favorite_games as $game): ?>
                                    <li class="list-group-item bg-dark-secondary text-light">
                                        <strong><?= htmlspecialchars($game['titel']) ?></strong>
                                        <?php if (!empty($game['description'])): ?>
                                            <p class="small mb-0"><?= htmlspecialchars($game['description']) ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>