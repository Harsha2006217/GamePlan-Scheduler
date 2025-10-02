<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$user_id = getUserId();
$games = getGames();
$favorites = getFavoriteGames($user_id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $game_ids = $_POST['game_ids'] ?? [];  // Now multiple
    $success = true;
    foreach ($game_ids as $game_id) {
        $result = addFavoriteGame($game_id);
        if ($result !== true) {
            setMessage('error', $result);
            $success = false;
            break;
        }
    }
    if ($success) {
        setMessage('success', 'Favorites added.');
    }
    header('Location: profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; margin: 0; padding: 0; }
        header { background: #1e1e1e; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .nav-link { color: #ffffff; margin: 0 15px; text-decoration: none; font-size: 1.1em; transition: color 0.3s; }
        .nav-link:hover { color: #007bff; }
        .navbar-toggler { border-color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .section { background: #2c2c2c; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .form-select { background: #2c2c2c; color: #fff; border: 1px solid #ddd; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; font-size: 0.9em; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
	body
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php">Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php">Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php">Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div class="container">
        <h2>Manage Profile <i class="bi bi-person-circle"></i></h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Add Favorite Games</h3>
            <form method="POST" onsubmit="return validateFavoriteForm();">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mb-3">
                    <label for="game_ids" class="form-label">Select Games (hold Ctrl for multiple)</label>
                    <select class="form-select" id="game_ids" name="game_ids[]" multiple required aria-label="Select favorite games">
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Favorites <i class="bi bi-star"></i></button>
            </form>
        </div>

        <div class="section">
            <h3>Your Favorites</h3>
            <ul>
                <?php foreach ($favorites as $fav): ?>
                    <li><?php echo htmlspecialchars($fav['titel']); ?> - <?php echo htmlspecialchars($fav['description']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | <a href="#" style="color: #aaa;">Privacy</a> | <a href="#" style="color: #aaa;">Contact</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateFavoriteForm() {
            const selected = document.getElementById('game_ids').selectedOptions.length;
            if (selected === 0) {
                alert('Select at least one game.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>