<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$profile = getProfile($user_id);
$favorites = getFavoriteGames($user_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_games = $_POST['games'] ?? [];
    foreach ($selected_games as $game_id) {
        addFavoriteGame($user_id, $game_id);
    }
    $message = '<div class="alert alert-success">Favorieten bijgewerkt.</div>';
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
        <h2 class="text-center mb-4">Profiel</h2>
        <?php echo $message; ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">Gebruikersinformatie</div>
                    <div class="card-body">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($profile['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                        <p><strong>Lid sinds:</strong> <?php echo htmlspecialchars($profile['created_at']); ?></p>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-success text-white">Favoriete Games</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Selecteer favoriete games:</label>
                                <?php $games = getGames(); ?>
                                <?php foreach ($games as $game): ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="games[]" value="<?php echo $game['game_id']; ?>" class="form-check-input" <?php if (in_array($game['titel'], array_column($favorites, 'titel'))) echo 'checked'; ?>>
                                        <label class="form-check-label"><?php echo htmlspecialchars($game['titel']); ?> - <?php echo htmlspecialchars($game['description']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-success">Opslaan</button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="index.php" class="btn btn-outline-primary btn-lg">Terug naar dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>