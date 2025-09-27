<?php
// Evenement toevoegen pagina
// Met schedule select, reminder dropdown, shared vrienden checkboxes

require 'functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$schedules = getSchedules($user_id);
$friends = getFriends($user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? null;
    $shared_friends = $_POST['shared_friends'] ?? [];

    try {
        addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends);
        $_SESSION['msg'] = "Evenement toegevoegd!";
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
    <title>Evenement Toevoegen - GamePlan Scheduler</title>
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
                    <li class="nav-item"><a class="nav-link" href="add_schedule.php">Schema Toevoegen</a></li>
                    <li class="nav-item"><a class="nav-link active" href="add_event.php">Evenement Toevoegen</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Uitloggen</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container mt-5 pt-5">
        <h2>Evenement Toevoegen</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo sanitizeInput($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="addEventForm">
            <div class="mb-3">
                <label for="title" class="form-label">Titel</label>
                <input type="text" class="form-control" id="title" name="title" required maxlength="100" placeholder="Bijv. Toernooi">
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
                <label for="description" class="form-label">Beschrijving</label>
                <textarea class="form-control" id="description" name="description" maxlength="500" placeholder="Details over het evenement"></textarea>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Reminder</label>
                <select class="form-select" id="reminder" name="reminder">
                    <option value="">Geen</option>
                    <option value="1 uur ervoor">1 uur ervoor</option>
                    <option value="1 dag ervoor">1 dag ervoor</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="schedule_id" class="form-label">Link aan Schema (optioneel)</label>
                <select class="form-select" id="schedule_id" name="schedule_id">
                    <option value="">Geen</option>
                    <?php foreach ($schedules as $sch): ?>
                        <option value="<?php echo $sch['schedule_id']; ?>"><?php echo sanitizeInput($sch['game_titel'] . ' op ' . $sch['date'] . ' ' . $sch['time']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Deel met Vrienden</label>
                <?php if (empty($friends)): ?>
                    <p class="text-muted">Geen vrienden om te delen.</p>
                <?php else: ?>
                    <?php foreach ($friends as $friend): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" id="shared_<?php echo $friend['user_id']; ?>">
                            <label class="form-check-label" for="shared_<?php echo $friend['user_id']; ?>"><?php echo sanitizeInput($friend['username']); ?> <?php echo $friend['online'] ? '(Online)' : '(Offline)'; ?></label>
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