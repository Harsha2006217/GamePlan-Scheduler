<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$schedules = getSchedules($user_id);
$friends = getFriends($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $event_type = $_POST['event_type'] ?? 'casual';
    $reminder = $_POST['reminder'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?: null;
    $shared_friends = $_POST['shared_friends'] ?? [];
    if (addEvent($user_id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends)) {
        header("Location: events.php");
        exit;
    } else {
        $message = '<div class="alert alert-danger">Fout bij toevoegen: controleer inputs.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenement toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Evenement toevoegen</h2>
        <?php echo $message; ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form method="POST" onsubmit="return validateForm(this);" class="shadow p-4 rounded">
                    <div class="mb-3">
                        <label for="title" class="form-label">Titel</label>
                        <input type="text" id="title" name="title" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Datum</label>
                        <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="time" class="form-label">Tijd</label>
                        <input type="time" id="time" name="time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="event_type" class="form-label">Type evenement</label>
                        <select id="event_type" name="event_type" class="form-select" required>
                            <option value="tournament">Toernooi</option>
                            <option value="practice">Team Training</option>
                            <option value="competition">Competitie</option>
                            <option value="stream">Livestream</option>
                            <option value="meetup">Meet-up</option>
                            <option value="casual">Casual Gameplay</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Beschrijving</label>
                        <textarea id="description" name="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="reminder" class="form-label">Herinnering</label>
                        <select id="reminder" name="reminder" class="form-select">
                            <option value="">Geen</option>
                            <option value="1 uur ervoor">1 uur ervoor</option>
                            <option value="1 dag ervoor">1 dag ervoor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_id" class="form-label">Link aan schema (optioneel)</label>
                        <select id="schedule_id" name="schedule_id" class="form-select">
                            <option value="">Geen schema</option>
                            <?php foreach ($schedules as $sched): ?>
                                <option value="<?php echo $sched['schedule_id']; ?>"><?php echo htmlspecialchars($sched['game_titel']) . ' - ' . $sched['date'] . ' om ' . $sched['time']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Deel met vrienden (optioneel)</label>
                        <?php foreach ($friends as $friend): ?>
                            <div class="form-check">
                                <input type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" class="form-check-input">
                                <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Toevoegen</button>
                        <a href="events.php" class="btn btn-outline-secondary">Annuleren</a>
                        <a href="index.php" class="btn btn-outline-primary">Terug naar dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>