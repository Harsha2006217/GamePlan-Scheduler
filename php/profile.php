<?php
// Profiel pagina voor GamePlan Scheduler
// Toont favoriete games en laat toevoegen toe met select dropdown
// Inclusief validatie en melding na opslaan

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$favoriteGames = getFavoriteGames($user_id);
$allGames = getGames();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game_id = $_POST['game_id'] ?? 0;
    try {
        addFavoriteGame($user_id, $game_id);
        $_SESSION['msg'] = "Favoriete game toegevoegd!";
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
    <title>Profiel - GamePlan Scheduler</title>
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
                    <li class="nav-item"><a class="nav-link active" href="profile.php">Profiel</a></li>
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
        <h2>Je Profiel</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo sanitizeInput($error); ?></div>
        <?php endif; ?>

        <section class="mb-4">
            <h3>Favoriete Games</h3>
            <ul class="list-group">
                <?php if (empty($favoriteGames)): ?>
                    <li class="list-group-item list-group-item-dark">Geen favoriete games toegevoegd.</li>
                <?php else: ?>
                    <?php foreach ($favoriteGames as $game): ?>
                        <li class="list-group-item list-group-item-dark">
                            <strong><?php echo sanitizeInput($game['titel']); ?></strong>: <?php echo sanitizeInput($game['description']); ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>

        <section>
            <h3>Favoriete Game Toevoegen</h3>
            <form method="POST" id="addFavoriteForm">
                <div class="mb-3">
                    <label for="game_id" class="form-label">Kies een game</label>
                    <select class="form-select" id="game_id" name="game_id" required>
                        <option value="">Selecteer een game</option>
                        <?php foreach ($allGames as $game): ?>
                            <option value="<?php echo $game['game_id']; ?>"><?php echo sanitizeInput($game['titel']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Toevoegen</button>
            </form>
        </section>
    </main>

    <footer class="bg-primary text-center py-3 mt-auto">
        <p class="mb-0 text-light">© 2025 GamePlan Scheduler door Harsha Kanaparthi. <a href="privacy.php" class="text-light">Privacybeleid</a> | <a href="#" class="text-light">Contact</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>