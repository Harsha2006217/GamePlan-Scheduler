<?php
// profile.php - Profile Management Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Allows adding, editing, deleting favorite games with notes and viewing profile.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$favorites = getFavoriteGames($userId);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_favorite'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $note = $_POST['note'] ?? '';
    $error = addFavoriteGame($userId, $title, $description, $note);
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
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Add Favorite Game</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Game Title</label>
                <input type="text" id="title" name="title" class="form-control" required maxlength="100" aria-label="Game Title">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea id="description" name="description" class="form-control" rows="2" maxlength="500" aria-label="Game Description"></textarea>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">Note (Optional)</label>
                <textarea id="note" name="note" class="form-control" rows="2" aria-label="Note"></textarea>
            </div>
            <button type="submit" name="add_favorite" class="btn btn-primary">Add</button>
        </form>

        <h2 class="mt-4">Your Favorites</h2>
        <table class="table table-dark table-bordered">
            <thead class="bg-info">
                <tr><th>Title</th><th>Description</th><th>Note</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($favorites as $game): ?>
                    <tr>
                        <td><?php echo safeEcho($game['titel']); ?></td>
                        <td><?php echo safeEcho($game['description']); ?></td>
                        <td><?php echo safeEcho($game['note']); ?></td>
                        <td>
                            <a href="edit_favorite.php?id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-warning" aria-label="Edit <?php echo safeEcho($game['titel']); ?>">Edit</a>
                            <a href="delete.php?type=favorite&id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');" aria-label="Delete <?php echo safeEcho($game['titel']); ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>