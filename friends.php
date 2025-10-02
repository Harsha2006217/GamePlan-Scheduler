<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

$user_id = getUserId();
$friends = getFriends($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Friends</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <ul>
            <?php foreach ($friends as $friend): ?>
                <li><?php echo $friend['username']; ?> - <?php echo $friend['status']; ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Add Friend</h3>
        <form method="POST" action="add_friend.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
                <label for="friend_username" class="form-label">Friend Username</label>
                <input type="text" class="form-control" id="friend_username" name="friend_username" required>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </div>
</body>
</html>