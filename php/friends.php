<?php
require 'functions.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$friends = getFriends($user_id);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vrienden - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Vriendenlijst</h2>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <ul class="list-group shadow-sm">
                    <?php if (empty($friends)): ?>
                        <li class="list-group-item text-center text-muted">Geen vrienden toegevoegd.</li>
                    <?php else: ?>
                        <?php foreach ($friends as $friend): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($friend['username']); ?></span>
                                <span class="badge <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <div class="mt-4 text-center">
                    <a href="add_friend.php" class="btn btn-primary btn-lg me-2">Vriend toevoegen</a>
                    <a href="index.php" class="btn btn-outline-primary btn-lg">Terug naar dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>