<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$games = getGames();
$favorite_games = getFavoriteGames($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = :user");
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    $selected_games = $_POST['favorite_games'] ?? [];
    foreach ($selected_games as $game_id) {
        addFavoriteGame($user_id, $game_id);
    }
    $message = '<div class="alert alert-success">Favoriete games opgeslagen.</div>';
    $favorite_games = getFavoriteGames($user_id);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Profiel bewerken</h2>
        <?php echo $message; ?>
        <form method="POST">
            <h3>Favoriete Games</h3>
            <?php foreach ($games as $game): ?>
                <div class="form-check">
                    <input type="checkbox" name="favorite_games[]" value="<?php echo $game['game_id']; ?>" class="form-check-input" <?php if (in_array($game['titel'], array_column($favorite_games, 'titel'))) echo 'checked'; ?>>
                    <label class="form-check-label"><?php echo htmlspecialchars($game['titel']); ?> - <?php echo htmlspecialchars($game['description']); ?></label>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary mt-3">Opslaan</button>
        </form>
    </div>
</body>
</html>