<?php
// profile.php - Profile Management Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Allows adding favorite games and viewing profile.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$games = getGames();
$favorites = getFavoriteGames($userId);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameId = $_POST['game_id'] ?? '';
    $error = addFavoriteGame($userId, $gameId);
    if (!$error) {
        setMessage('success', 'Favorite game added!');
        header("Location: profile.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?> <!-- Assume header.php for common header -->

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Add Favorite Game</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="game_id" class="form-label">Select Game</label>
                <select id="game_id" name="game_id" class="form-select" required>
                    <option value="">Choose a game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo safeEcho($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>

        <h2 class="mt-4">Your Favorites</h2>
        <ul class="list-group">
            <?php foreach ($favorites as $game): ?>
                <li class="list-group-item bg-secondary"><?php echo safeEcho($game['titel']); ?>: <?php echo safeEcho($game['description']); ?></li>
            <?php endforeach; ?>
        </ul>
    </main>

    <?php include 'footer.php'; ?> <!-- Assume footer.php for common footer -->
</body>
</html>