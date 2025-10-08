<?php
// edit_favorite.php - Edit Favorite Game Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to edit game title, description, note.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    header("Location: profile.php");
    exit;
}

$favorites = getFavoriteGames($userId);
$game = array_filter($favorites, function($g) use ($id) { return $g['game_id'] == $id; });
$game = reset($game);
if (!$game) {
    setMessage('danger', 'Game not found or no permission.');
    header("Location: profile.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $note = $_POST['note'] ?? '';
        $error = updateFavoriteGame($userId, $id, $title, $description, $note);
        if (!$error) {
            setMessage('success', 'Favorite game updated!');
            header("Location: profile.php");
            exit;
        }
    }
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Favorite - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Edit Favorite Game</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo safeEcho($csrfToken); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Game Title</label>
                <input type="text" id="title" name="title" class="form-control" required maxlength="100" value="<?php echo safeEcho($game['titel']); ?>" aria-label="Game Title">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" rows="2" maxlength="500" aria-label="Game Description"><?php echo safeEcho($game['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">Note</label>
                <textarea id="note" name="note" class="form-control" rows="2" aria-label="Note"><?php echo safeEcho($game['note']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>