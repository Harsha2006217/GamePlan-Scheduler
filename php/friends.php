<?php
// Vriendenlijst pagina voor GamePlan Scheduler
// Toont lijst met online status en optie om te verwijderen

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$friends = getFriends($user_id);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vrienden - GamePlan Scheduler</title>
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
                    <li class="nav-item"><a class="nav-link active" href="friends.php">Vrienden</a></li>
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
        <h2>Je Vrienden</h2>
        <a href="add_friend.php" class="btn btn-success mb-3">Vriend Toevoegen</a>
        <ul class="list-group">
            <?php if (empty($friends)): ?>
                <li class="list-group-item list-group-item-dark">Geen vrienden toegevoegd.</li>
            <?php else: ?>
                <?php foreach ($friends as $friend): ?>
                    <li class="list-group-item list-group-item-dark d-flex justify-content-between align-items-center">
                        <?php echo sanitizeInput($friend['username']); ?> - <?php echo $friend['online'] ? '<span class="badge bg-success">Online</span>' : '<span class="badge bg-secondary">Offline</span>'; ?>
                        <a href="delete.php?type=friend&id=<?php echo $friend['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Weet je zeker dat je deze vriend wilt verwijderen?');">Verwijderen</a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </main>

    <footer class="bg-primary text-center py-3 mt-auto">
        <p class="mb-0 text-light">© 2025 GamePlan Scheduler door Harsha Kanaparthi. <a href="privacy.php" class="text-light">Privacybeleid</a> | <a href="#" class="text-light">Contact</a></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>