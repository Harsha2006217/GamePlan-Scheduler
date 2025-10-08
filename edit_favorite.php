<?php
// edit_favorite.php - Edit Favorite Game Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Allows users to edit the title and description of a favorite game.
// Ensures ownership check, input validation, and secure updates.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id) || $id <= 0) {
    setMessage('danger', 'Invalid game ID.');
    header("Location: profile.php");
    exit;
}

$favorites = getFavoriteGames($userId);
$game = null;
foreach ($favorites as $fav) {
    if ($fav['game_id'] == $id) {
        $game = $fav;
        break;
    }
}
if (!$game) {
    setMessage('danger', 'Game not found or no permission to edit.');
    header("Location: profile.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($err = validateRequired($title, "Title", 100)) {
        $error = $err;
    } elseif (!empty($description) && strlen($description) > 500) {
        $error = "Description exceeds maximum length of 500 characters.";
    } else {
        $error = updateFavoriteGame($userId, $id, $title, $description);
        if (!$error) {
            setMessage('success', 'Favorite game updated successfully!');
            header("Location: profile.php");
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Favorite Game - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo safeEcho($error); ?>
            </div>
        <?php endif; ?>

        <h2>Edit Favorite Game</h2>
        <form method="POST" novalidate>
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo safeEcho($game['titel']); ?>" required maxlength="100" aria-describedby="titleHelp">
                <div id="titleHelp" class="form-text text-muted">Enter the game title (max 100 characters).</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" maxlength="500" aria-describedby="descHelp"><?php echo safeEcho($game['description']); ?></textarea>
                <div id="descHelp" class="form-text text-muted">Optional description (max 500 characters).</div>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="profile.php" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="script.js"></script>
</body>
</html>