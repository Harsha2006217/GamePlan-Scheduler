<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$games = getGames();
$friends = getFriends($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game_id = $_POST['game_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $friends_selected = $_POST['friends'] ?? [];
    if (addSchedule($user_id, $game_id, $date, $time, $friends_selected)) {
        header("Location: schedules.php");
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
    <title>Schema toevoegen - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Schema toevoegen</h2>
        <?php echo $message; ?>
        <form method="POST" onsubmit="return validateForm(this);">
            <div class="mb-3">
                <label for="game_id" class="form-label">Game</label>
                <select id="game_id" name="game_id" class="form-select" required>
                    <option value="">Kies game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label>Vrienden (optioneel)</label>
                <?php foreach ($friends as $friend): ?>
                    <div class="form-check">
                        <input type="checkbox" name="friends[]" value="<?php echo $friend['user_id']; ?>" class="form-check-input">
                        <label class="form-check-label"><?php echo htmlspecialchars($friend['username']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Toevoegen</button>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>