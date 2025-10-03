<?php
// profile.php: Manage profile and favorites
require_once 'functions.php';
requireLogin();
$user_id = getUserId();
$games = getGames();
$favorites = getFavoriteGames($user_id);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $game_id = (int)($_POST['game_id'] ?? 0);
    $result = addFavoriteGame($game_id);
    if ($result === true) {
        setMessage('success', 'Favorite added.');
    } else {
        setMessage('danger', $result);
    }
    header('Location: profile.php');
    exit;
}
$msg = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; }
        header { background: #1e1e1e; padding: 15px; text-align: center; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: sticky; top: 0; z-index: 1; }
        nav a { color: #fff; margin: 0 15px; text-decoration: none; }
        nav a:hover { color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .section { background: #2c2c2c; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-select { background: #2c2c2c; border: 1px solid #444; color: #fff; }
        .btn-primary { background: #007bff; border: none; }
        .btn-primary:hover { background: #0069d9; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaa; }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="add_friend.php">Add Friend</a>
            <a href="add_schedule.php">Add Schedule</a>
            <a href="add_event.php">Add Event</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Favorite Games</h3>
            <ul>
                <?php foreach ($favorites as $fav): ?>
                    <li><?php echo htmlspecialchars($fav['titel']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="section">
            <h3>Add Favorite Game</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <select class="form-select mb-3" name="game_id" required>
                    <option value="">Select Game</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
</body>
</html>