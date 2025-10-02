<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();

    $game_id = $_POST['game_id'] ?? '';

    $result = addFavoriteGame($game_id);
    if ($result === true) {
        setMessage('success', 'Favorite game added');
    } else {
        setMessage('danger', $result);
    }
}

$favorites = getFavoriteGames($user_id);
$games = getGames();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Profile</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <h3>Add Favorite Game</h3>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo $msg['message']; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
                <label for="game_id" class="form-label">Select Game</label>
                <select class="form-select" id="game_id" name="game_id" required>
                    <option value="">Select a game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo $game['titel']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>

        <h3>Favorite Games</h3>
        <ul>
            <?php foreach ($favorites as $favorite): ?>
                <li><?php echo $favorite['gametitel']; ?> - <?php echo $favorite['game_description']; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>