<?php
// profile.php: User profile page for managing favorites
// Displays current favorites and form to add new ones
// Dark theme, responsive, beautiful UI with game select

require_once 'functions.php';
requireLogin();
checkTimeout();
$user_id = getUserId();
$games = getGames();
$favorites = getFavoriteGames($user_id);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $game_id = (int)($_POST['game_id'] ?? 0);
    $result = addFavoriteGame($game_id);
    if ($result === true) {
        setMessage('success', 'Game added to favorites.');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php"><i class="bi bi-controller me-2"></i>GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h3 class="section-title"><i class="bi bi-star-fill me-2"></i>My Favorite Games</h3>
            <?php if (empty($favorites)): ?>
                <p class="text-muted">No favorites yet. Add one below!</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($favorites as $fav): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <h6 class="card-title text-primary"><?php echo htmlspecialchars($fav['titel']); ?></h6>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($fav['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3 class="section-title"><i class="bi bi-plus-circle me-2"></i>Add Favorite Game</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="game_id" class="form-label">Select Game</label>
                    <select class="form-select" id="game_id" name="game_id" required>
                        <option value="">Choose a game...</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['game_id']; ?>"><?php echo htmlspecialchars($game['titel']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-star me-2"></i>Add to Favorites</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy Policy | Contact Support
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>