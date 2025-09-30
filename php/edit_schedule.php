<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM Schedules WHERE schedule_id = :id AND user_id = :user");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->bindParam(':user', $user_id, PDO::PARAM_INT);
$stmt->execute();
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$schedule) {
    header("Location: schedules.php");
    exit;
}
$games = getGames();
$friends = getFriends($user_id);
$selected_friends = explode(',', $schedule['friends']);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game_id = $_POST['game_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $friends_selected = $_POST['friends'] ?? [];
    if (editSchedule($id, $game_id, $date, $time, $friends_selected)) {
        header("Location: schedules.php");
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
    <title>Schema bewerken - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Schema bewerken</h2>
        <?php echo $message; ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" onsubmit="return validateForm(this);" class="shadow p-4 rounded">
                    <div class="mb-3">
                        <label for="game_id" class="form-label">Game</label>
                        <select id="game_id" name="game_id" class="form-select" required>
                            <option value="">Kies game</option>
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['game_id']; ?>" <?php if ($game['game_id'] == $schedule['game_id']) echo 'selected'; ?>><?php echo htmlspecialchars($game['titel']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Datum</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($schedule['date']); ?>" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="time" class="form-label">Tijd</label>
                        <input type="time" id="time" name="time" class="form-control" value="<?php echo htmlspecialchars($schedule['time']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Vrienden (optioneel)</label>
                        <?php foreach ($friends as $friend): ?>
                            <div class="form-check">
                                <input type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" class="form-check-input" <?php if (in_array($friend['username'], $selected_friends)) echo 'checked'; ?>>
                                <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Opslaan</button>
                        <a href="schedules.php" class="btn btn-outline-secondary">Annuleren</a>
                        <a href="index.php" class="btn btn-outline-primary">Terug naar dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>