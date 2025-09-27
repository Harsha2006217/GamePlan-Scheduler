<?php
// Dashboard (index.php) voor GamePlan Scheduler
// Geavanceerde lay-out met Bootstrap 5 voor responsive design
// Inclusief kalender merge, online status, en dynamische content loading
// Dark theme met custom CSS classes voor game-feel

require 'functions.php';
requireLogin();  // Vereis login, update activity

$user_id = $_SESSION['user_id'];
$favoriteGames = getFavoriteGames($user_id);
$friends = getFriends($user_id);
$schedules = getSchedules($user_id);
$events = getEvents($user_id);
$calendarItems = getCalendarItems($user_id);

// Sessie melding tonen als aanwezig (bijv. na toevoegen)
if (isset($_SESSION['msg'])) {
    $msg = sanitizeInput($_SESSION['msg']);
    unset($_SESSION['msg']);
} else {
    $msg = '';
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <!-- Inline JSON voor JS reminders -->
    <script data-events type="application/json"><?php echo json_encode($events); ?></script>
</head>
<body class="bg-dark text-light">
    <header class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">GamePlan Scheduler</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profiel</a></li>
                    <li class="nav-item"><a class="nav-link" href="friends.php">Vrienden</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_schedule.php">Schema Toevoegen</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_event.php">Evenement Toevoegen</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container mt-5 pt-5">
        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="mb-4">
            <h2>Favoriete Games</h2>
            <ul class="list-group">
                <?php if (empty($favoriteGames)): ?>
                    <li class="list-group-item list-group-item-dark">Geen favoriete games toegevoegd. Ga naar profiel om toe te voegen.</li>
                <?php else: ?>
                    <?php foreach ($favoriteGames as $game): ?>
                        <li class="list-group-item list-group-item-dark">
                            <strong><?php echo sanitizeInput($game['titel']); ?></strong>: <?php echo sanitizeInput($game['description']); ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>

        <section class="mb-4">
            <h2>Vriendenlijst</h2>
            <ul class="list-group">
                <?php if (empty($friends)): ?>
                    <li class="list-group-item list-group-item-dark">Geen vrienden toegevoegd. Voeg toe via vrienden pagina.</li>
                <?php else: ?>
                    <?php foreach ($friends as $friend): ?>
                        <li class="list-group-item list-group-item-dark d-flex justify-content-between align-items-center">
                            <?php echo sanitizeInput($friend['username']); ?> - <?php echo $friend['online'] ? '<span class="badge bg-success">Online</span>' : '<span class="badge bg-secondary">Offline</span>'; ?>
                            <a href="delete.php?type=friend&id=<?php echo $friend['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Weet je zeker dat je deze vriend wilt verwijderen?');">Verwijderen</a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>

        <section class="mb-4">
            <h2>Schema's</h2>
            <table class="table table-dark table-hover table-bordered" style="width: 100%; font-size: 1.1em;">
                <thead class="thead-light" style="background-color: lightblue; border: 1px solid #dddddd;">
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
                        <tr>
                            <td colspan="5" class="text-center">Geen schema's toegevoegd.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo sanitizeInput($schedule['game_titel']); ?></td>
                                <td><?php echo sanitizeInput($schedule['date']); ?></td>
                                <td><?php echo sanitizeInput($schedule['time']); ?></td>
                                <td><?php echo sanitizeInput($schedule['friends']); ?></td>
                                <td>
                                    <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-warning">Bewerken</a>
                                    <a href="delete.php?type=schedule&id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Weet je zeker dat je dit schema wilt verwijderen?');">Verwijderen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="mb-4">
            <h2>Evenementen</h2>
            <table class="table table-dark table-hover table-bordered" style="width: 100%; font-size: 1.1em;">
                <thead class="thead-light" style="background-color: lightblue; border: 1px solid #dddddd;">
                    <tr>
                        <th>Titel</th>
                        <th>Datum</th>
                        <th>Tijd</th>
                        <th>Beschrijving</th>
                        <th>Reminder</th>
                        <th>Gedeeld met</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Geen evenementen toegevoegd.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo sanitizeInput($event['title']); ?></td>
                                <td><?php echo sanitizeInput($event['date']); ?></td>
                                <td><?php echo sanitizeInput($event['time']); ?></td>
                                <td><?php echo sanitizeInput($event['description']); ?></td>
                                <td><?php echo sanitizeInput($event['reminder']); ?></td>
                                <td><?php echo implode(', ', array_map('sanitizeInput', $event['shared_with'] ?? [])); ?></td>
                                <td>
                                    <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-warning">Bewerken</a>
                                    <a href="delete.php?type=event&id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Weet je zeker dat je dit evenement wilt verwijderen?');">Verwijderen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="mb-4">
            <h2>Kalender Overzicht</h2>
            <div class="row">
                <?php if (empty($calendarItems)): ?>
                    <p class="text-center">Geen items in de kalender.</p>
                <?php else: ?>
                    <?php foreach ($calendarItems as $item): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-secondary text-light border-0 rounded-3 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo sanitizeInput($item['title']); ?> - <?php echo sanitizeInput($item['date'] . ' om ' . $item['time']); ?></h5>
                                    <?php if (isset($item['description'])): ?>
                                        <p class="card-text"><?php echo sanitizeInput($item['description']); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['reminder']) && $item['reminder']): ?>
                                        <p class="card-text small text-success">Reminder: <?php echo sanitizeInput($item['reminder']); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['shared_with']) && !empty($item['shared_with'])): ?>
                                        <p class="card-text small">Gedeeld met: <?php echo implode(', ', array_map('sanitizeInput', $item['shared_with'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($item['friends']) && $item['friends']): ?>
                                        <p class="card-text small">Vrienden: <?php echo sanitizeInput($item['friends']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-primary text-center py-3 mt-auto">
        <p class="mb-0 text-light">© 2025 GamePlan Scheduler door Harsha Kanaparthi. <a href="privacy.php" class="text-light">Privacybeleid</a> | <a href="#" class="text-light">Contact</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>