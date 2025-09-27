<?php
// Schema toevoegen pagina
// Met game select, date/time pickers, vrienden checkboxes

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$allGames = getGames();
$friends = getFriends($user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game_id = $_POST['game_id'] ?? 0;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friendsPost = $_POST['friends'] ?? [];

    try {
        addSchedule($user_id, $game_id, $date, $time, $friendsPost);
        $_SESSION['msg'] = "Schema toegevoegd!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema Toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profiel</a></li>
                    <li class="nav-item"><a class="nav-link" href="friends.php">Vrienden</a></li>
                    <li class="nav-item"><a class="nav-link active" href="add_schedule.php">Schema Toevoegen</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_event.php">Evenement Toevoegen</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container mt-5 pt-5">
        <h2>Schema Toevoegen</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo sanitizeInput($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="addScheduleForm">
            <div class="mb-3">
                <label for="game_id" class="form-label">Kies Game</label>
                <select class="form-select" id="game_id" name="game_id" required>
                    <option value="">Selecteer een game</option>
                    <?php foreach ($allGames as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo sanitizeInput($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Datum</label>
                <input type="date" class="form-control" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Tijd</label>
                <input type="time" class="form-control" id="time" name="time" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Deel met Vrienden</label>
                <?php if (empty($friends)): ?>
                    <p class="text-muted">Geen vrienden om te delen. Voeg eerst vrienden toe.</p>
                <?php else: ?>
                    <?php foreach ($friends as $friend): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" id="friend_<?php echo $friend['user_id']; ?>">
                            <label class="form-check-label" for="friend_<?php echo $friend['user_id']; ?>"><?php echo sanitizeInput($friend['username']); ?> <?php echo $friend['online'] ? '(Online)' : '(Offline)'; ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Toevoegen</button>
        </form>
    </main>

    <footer class="bg-primary text-center py-3 mt-auto">
        <p class="mb-0 text-light">© 2025 GamePlan Scheduler door Harsha Kanaparthi. <a href="privacy.php" class="text-light">Privacybeleid</a> | <a href="#" class="text-light">Contact</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>