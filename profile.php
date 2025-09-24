<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
$user_id = $_SESSION['user_id'];
$profile = getProfile($user_id);
$games = getGames();
$favorite_games = getFavoriteGames($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verwijder oude favorites
    $stmt = $pdo->prepare("DELETE FROM UserGames WHERE user_id = :user");
    $stmt->bindParam(':user', $user_id);
    $stmt->execute();
    // Voeg nieuwe toe
    $favorite_ids = $_POST['favorite_games'] ?? [];
    foreach ($favorite_ids as $game_id) {
        addFavoriteGame($user_id, $game_id);
    }
    $message = '<div class="alert alert-success">Favoriete games opgeslagen.</div>';
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
            <div class="mb-3">
                <label>Favoriete games selecteren:</label>
                <?php foreach ($games as $game): ?>
                    <div class="form-check">
                        <input type="checkbox" name="favorite_games[]" value="<?php echo $game['game_id']; ?>" class="form-check-input" <?php if (array_search($game['titel'], array_column($favorite_games, 'titel')) !== false) echo 'checked'; ?>>
                        <?php echo htmlspecialchars($game['titel']); ?> - <?php echo htmlspecialchars($game['description']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </form>
    </div>
</body>
</html>