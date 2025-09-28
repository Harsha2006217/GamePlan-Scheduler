<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$profile = getProfile($user_id);
$favorite_games = getFavoriteGames($user_id);
$friends = getFriends($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
// Update last_activity bij load voor online status
global $pdo;
$stmt = $pdo->prepare("UPDATE Users SET last_activity = NOW() WHERE user_id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="bg-dark text-white p-3 fixed-top">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="mb-0">GamePlan Scheduler</h1>
            <nav class="nav">
                <a href="profile.php" class="nav-link text-white">Profiel</a>
                <a href="friends.php" class="nav-link text-white">Vrienden</a>
                <a href="schedules.php" class="nav-link text-white">Schema's</a>
                <a href="events.php" class="nav-link text-white">Evenementen</a>
                <a href="logout.php" class="nav-link text-white">Uitloggen</a>
            </nav>
        </div>
    </header>
    <main class="container my-5 pt-5">
        <h2 class="mt-5">Welkom, <?php echo htmlspecialchars($profile['username']); ?></h2>
        <h3>Favoriete Games</h3>
        <ul class="list-group mb-4">
            <?php if (empty($favorite_games)): ?>
                <li class="list-group-item">Geen favoriete games toegevoegd.</li>
            <?php else: ?>
                <?php foreach ($favorite_games as $game): ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($game['titel']); ?> - <?php echo htmlspecialchars($game['description']); ?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        
        <h3>Vriendenlijst</h3>
        <ul class="list-group mb-4">
            <?php if (empty($friends)): ?>
                <li class="list-group-item">Geen vrienden toegevoegd.</li>
            <?php else: ?>
                <?php foreach ($friends as $friend): ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($friend['username']); ?> - <span class="badge bg-<?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'success' : 'secondary'; ?>"><?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?></span></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <a href="add_friend.php" class="btn btn-primary mb-4">Vriend toevoegen</a>
        
        <h3>Schema's</h3>
        <table class="table table-dark table-bordered mb-4">
            <thead class="bg-lightblue">
                <tr>
                    <th>Game</th>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Vrienden</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="5">Geen schema's toegevoegd.</td></tr>
                <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['game_titel']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['date']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['time']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['friends']); ?></td>
                            <td>
                                <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-warning btn-sm">Bewerken</a>
                                <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Weet je zeker dat je dit schema wilt verwijderen?');">Verwijderen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="add_schedule.php" class="btn btn-primary mb-4">Schema toevoegen</a>
        
        <h3>Evenementen</h3>
        <table class="table table-dark table-bordered mb-4">
            <thead class="bg-lightblue">
                <tr>
                    <th>Titel</th>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Beschrijving</th>
                    <th>Herinnering</th>
                    <th>Gedeeld met</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="7">Geen evenementen toegevoegd.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                            <td><?php echo htmlspecialchars($event['date']); ?></td>
                            <td><?php echo htmlspecialchars($event['time']); ?></td>
                            <td><?php echo htmlspecialchars($event['description']); ?></td>
                            <td><?php echo htmlspecialchars($event['reminder']); ?></td>
                            <td><?php echo implode(', ', $event['shared_with']); ?></td>
                            <td>
                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-warning btn-sm">Bewerken</a>
                                <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen?');">Verwijderen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="add_event.php" class="btn btn-primary mb-4">Evenement toevoegen</a>
        
        <h3>Kalender Overzicht</h3>
        <div class="row">
            <?php
            $all_items = array_merge($schedules, $events);
            usort($all_items, function($a, $b) {
                return strtotime($a['date'] . ' ' . $a['time']) <=> strtotime($b['date'] . ' ' . $b['time']);
            });
            if (empty($all_items)): ?>
                <p>Geen items in de kalender.</p>
            <?php else: ?>
                <?php foreach ($all_items as $item): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-header">
                                <?php echo htmlspecialchars($item['title'] ?? $item['game_titel']); ?> - <?php echo $item['date'] . ' om ' . $item['time']; ?>
                            </div>
                            <div class="card-body">
                                <?php if (isset($item['description'])): ?>
                                    <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php endif; ?>
                                <?php if (isset($item['reminder'])): ?>
                                    <p class="card-text">Herinnering: <?php echo htmlspecialchars($item['reminder']); ?></p>
                                <?php endif; ?>
                                <?php if (isset($item['shared_with'])): ?>
                                    <p class="card-text">Gedeeld met: <?php echo implode(', ', $item['shared_with']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <footer class="bg-dark text-white text-center p-3 fixed-bottom">
        © 2025 GamePlan Scheduler door Harsha Kanaparthi | <a href="privacy.php" class="text-white">Privacybeleid</a> | Contact
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>