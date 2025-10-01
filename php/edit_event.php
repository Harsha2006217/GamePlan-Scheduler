<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = :id AND user_id = :user");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->bindParam(':user', $user_id, PDO::PARAM_INT);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    header("Location: events.php");
    exit;
}
$schedules = getSchedules($user_id);
$friends = getFriends($user_id);
$shared_friends_ids = [];
$stmt = $pdo->prepare("SELECT friend_id FROM EventUserMap WHERE event_id = :event");
$stmt->bindParam(':event', $id, PDO::PARAM_INT);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $shared_friends_ids[] = $row['friend_id'];
}
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
    if (editEvent($id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends)) {
        header("Location: events.php");
        exit;
    } else {
        $message = '<div class="alert alert-danger">Fout bij bewerken: controleer inputs.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evenement bewerken - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Evenement bewerken</h2>
        <?php echo $message; ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form method="POST" onsubmit="return validateForm(this);" class="shadow p-4 rounded">
                    <div class="mb-3">
                        <label for="title" class="form-label">Titel</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($event['title']); ?>" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Datum</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($event['date']); ?>" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="time" class="form-label">Tijd</label>
                        <input type="time" id="time" name="time" class="form-control" value="<?php echo htmlspecialchars($event['time']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="event_type" class="form-label">Type evenement</label>
                        <select id="event_type" name="event_type" class="form-select" required>
                            <option value="tournament" <?php if ($event['event_type'] == 'tournament') echo 'selected'; ?>>Toernooi</option>
                            <option value="practice" <?php if ($event['event_type'] == 'practice') echo 'selected'; ?>>Team Training</option>
                            <option value="competition" <?php if ($event['event_type'] == 'competition') echo 'selected'; ?>>Competitie</option>
                            <option value="stream" <?php if ($event['event_type'] == 'stream') echo 'selected'; ?>>Livestream</option>
                            <option value="meetup" <?php if ($event['event_type'] == 'meetup') echo 'selected'; ?>>Meet-up</option>
                            <option value="casual" <?php if ($event['event_type'] == 'casual') echo 'selected'; ?>>Casual Gameplay</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Beschrijving</label>
                        <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="reminder" class="form-label">Herinnering</label>
                        <select id="reminder" name="reminder" class="form-select">
                            <option value="" <?php if ($event['reminder'] == '') echo 'selected'; ?>>Geen</option>
                            <option value="1 uur ervoor" <?php if ($event['reminder'] == '1 uur ervoor') echo 'selected'; ?>>1 uur ervoor</option>
                            <option value="1 dag ervoor" <?php if ($event['reminder'] == '1 dag ervoor') echo 'selected'; ?>>1 dag ervoor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_id" class="form-label">Link aan schema (optioneel)</label>
                        <select id="schedule_id" name="schedule_id" class="form-select">
                            <option value="">Geen schema</option>
                            <?php foreach ($schedules as $sched): ?>
                                <option value="<?php echo $sched['schedule_id']; ?>" <?php if ($sched['schedule_id'] == $event['schedule_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sched['game_titel']) . ' - ' . $sched['date'] . ' om ' . $sched['time']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Deel met vrienden (optioneel)</label>
                        <?php foreach ($friends as $friend): ?>
                            <div class="form-check">
                                <input type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" class="form-check-input" <?php if (in_array($friend['user_id'], $shared_friends_ids)) echo 'checked'; ?>>
                                <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Opslaan</button>
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