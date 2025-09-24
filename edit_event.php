<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$schedules = getSchedules($user_id);
$friends_list = getFriends($user_id);
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = :id AND user_id = :user");
$stmt->bindParam(':id', $id);
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$event = $stmt->fetch();
if (!$event) header("Location: index.php");
$shared_friends = array_column($event['shared_with'], 'username');
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $description = trim($_POST['description']);
    $reminder = $_POST['reminder'];
    $schedule_id = $_POST['schedule_id'] ?: null;
    $shared_friends_post = $_POST['shared_friends'] ?? [];
    if (editEvent($id, $title, $date, $time, $description, $reminder, $schedule_id, $shared_friends_post)) {
        header("Location: index.php");
        exit;
    } else {
        $message = '<div class="alert alert-danger">Fout bij update: controleer inputs.</div>';
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
        <h2>Evenement bewerken</h2>
        <?php echo $message; ?>
        <form method="POST" onsubmit="return validateForm(this);">
            <div class="mb-3">
                <label for="title" class="form-label">Titel</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($event['title']); ?>" required maxlength="100">
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Datum</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo $event['date']; ?>" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Tijd</label>
                <input type="time" id="time" name="time" class="form-control" value="<?php echo $event['time']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Beschrijving</label>
                <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="reminder" class="form-label">Herinnering</label>
                <select id="reminder" name="reminder" class="form-select">
                    <option value="" <?php if ($event['reminder'] == '') echo 'selected'; ?>>Geen herinnering</option>
                    <option value="1 uur ervoor" <?php if ($event['reminder'] == '1 uur ervoor') echo 'selected'; ?>>1 uur ervoor</option>
                    <option value="1 dag ervoor" <?php if ($event['reminder'] == '1 dag ervoor') echo 'selected'; ?>>1 dag ervoor</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="schedule_id" class="form-label">Link aan schema (optioneel)</label>
                <select id="schedule_id" name="schedule_id" class="form-select">
                    <option value="">Geen schema</option>
                    <?php foreach ($schedules as $sched): ?>
                        <option value="<?php echo $sched['schedule_id']; ?>" <?php if ($sched['schedule_id'] == $event['schedule_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sched['game_titel']) . ' - ' . $sched['date']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Deel met vrienden (optioneel):</label>
                <?php foreach ($friends_list as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" name="shared_friends[]" value="<?php echo $friend['user_id']; ?>" class="form-check-input" <?php if (in_array($friend['username'], $shared_friends)) echo 'checked'; ?>>
                        <?php echo htmlspecialchars($friend['username']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>