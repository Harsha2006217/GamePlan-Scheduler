<?php
require 'functions.php';
if (!isLoggedIn()) header("Location: login.php");
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
        <h2>Vriendenlijst</h2>
        <ul class="list-group">
            <?php foreach ($friends as $friend): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($friend['username']); ?> - <?php echo (strtotime($friend['last_activity']) > time() - 300) ? 'Online' : 'Offline'; ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="add_friend.php" class="btn btn-primary mt-2">Vriend toevoegen</a>
    </div>
</body>
</html>