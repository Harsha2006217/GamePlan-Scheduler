<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$profile = getProfile($user_id);
$favorite_games = getFavoriteGames($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
$friends = getFriends($user_id);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="bg-dark text-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1>GamePlan Scheduler</h1>
            <nav>
                <ul class="nav">
                    <li class="nav-item"><a href="profile.php" class="nav-link text-white">Profiel</a></li>
                    <li class="nav-item"><a href="friends.php" class="nav-link text-white">Vrienden</a></li>
                    <li class="nav-item"><a href="schedules.php" class="nav-link text-white">Schema's</a></li>
                    <li class="nav-item"><a href="events.php" class="nav-link text-white">Evenementen</a></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link text-white">Uitloggen</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container my-4">
        <h2>Welkom, <?php echo htmlspecialchars($profile['username']); ?></h2>
        <h3>Favoriete games</h3>
        <ul class="list-group">
            <?php foreach ($favorite_games as $game): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($game['titel']); ?> - <?php echo htmlspecialchars($game['description']); ?></li>
            <?php endforeach; ?>
        </ul>
        
        <h3>Vriendenlijst</h3>
        <ul class="list-group">
            <?php foreach ($friends as $friend): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($friend['username']); ?> - <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="add_friend.php" class="btn btn-primary mt-2">Vriend toevoegen</a>
        
        <h3>Schema's</h3>
        <table class="table table-dark">
            <thead><tr><th>Game</th><th>Datum</th><th>Tijd</th><th>Vrienden</th><th>Acties</th></tr></thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($schedule['game_titel']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['date']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['time']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['friends']); ?></td>
                        <td>
                            <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-warning">Bewerken</a>
                            <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Zeker weten?');">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="add_schedule.php" class="btn btn-primary">Schema toevoegen</a>
        
        <h3>Evenementen</h3>
        <table class="table table-dark">
            <thead><tr><th>Titel</th><th>Datum</th><th>Tijd</th><th>Beschrijving</th><th>Herinnering</th><th>Gedeeld met</th><th>Acties</th></tr></thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['date']); ?></td>
                        <td><?php echo htmlspecialchars($event['time']); ?></td>
                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                        <td><?php echo htmlspecialchars($event['reminder']); ?></td>
                        <td>
                            <?php foreach ($event['shared_with'] as $shared): ?>
                                <?php echo htmlspecialchars($shared['username']); ?>,
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-warning">Bewerken</a>
                            <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Zeker weten?');">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="add_event.php" class="btn btn-primary">Evenement toevoegen</a>
        
        <h3>Kalender</h3>
        <div id="calendar" class="bg-light p-3 rounded">
            <!-- Kalender logica: combineer schedules en events, gesorteerd op date -->
            <?php
            $all_items = array_merge($schedules, $events);
            usort($all_items, function($a, $b) {
                return strtotime($a['date'] . ' ' . $a['time']) <=> strtotime($b['date'] . ' ' . $b['time']);
            });
            foreach ($all_items as $item):
            ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($item['title'] ?? $item['game_titel']); ?> - <?php echo $item['date']; ?> om <?php echo $item['time']; ?></h5>
                        <?php if (isset($item['description'])) echo '<p>' . htmlspecialchars($item['description']) . '</p>'; ?>
                        <?php if (isset($item['reminder'])) echo '<p>Herinnering: ' . htmlspecialchars($item['reminder']) . '</p>'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <footer class="bg-dark text-white text-center p-2">
        © 2025 GamePlan Scheduler door Harsha Kanaparthi | <a href="privacy.php" class="text-white">Privacy</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>