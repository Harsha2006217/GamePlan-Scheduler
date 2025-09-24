<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$games = getGames();
$friends_list = getFriends($user_id);
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = :id AND user_id = :user");
$stmt->bindParam(':id', $id);
$stmt->bindParam(':user', $user_id);
$stmt->execute();
$schedule = $stmt->fetch();
if (!$schedule) header("Location: index.php");
$selected_friends = explode(',', $schedule['friends']);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game_id = $_POST['game_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $friends = $_POST['friends'] ?? [];
    if (editSchedule($id, $game_id, $date, $time, $friends)) {
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
    <title>Schema bewerken - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Schema bewerken</h2>
        <?php echo $message; ?>
        <form method="POST" onsubmit="return validateForm(this);">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game selecteren</label>
                <select id="game_id" name="game_id" class="form-select" required>
                    <option value="">Kies een game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>" <?php if ($game['game_id'] == $schedule['game_id']) echo 'selected'; ?>><?php echo htmlspecialchars($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Datum</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo $schedule['date']; ?>" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="mb-3">
                <label for="time" class="form-label">Tijd</label>
                <input type="time" id="time" name="time" class="form-control" value="<?php echo $schedule['time']; ?>" required>
            </div>
            <div class="mb-3">
                <label>Vrienden selecteren:</label>
                <?php foreach ($friends_list as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" class="form-check-input" <?php if (in_array($friend['user_id'], $selected_friends)) echo 'checked'; ?>>
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