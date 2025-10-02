<?php
require_once 'functions.php';

requireLogin();
checkTimeout();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();

    $friend_username = $_POST['friend_username'] ?? '';

    $result = addFriend($friend_username);
    if ($result === true) {
        setMessage('success', 'Friend added successfully');
        header('Location: friends.php');
        exit;
    } else {
        setMessage('danger', $result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Friend - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Friend</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo $msg['message']; ?></div>
        <?php endif; ?>
        <form method="POST">
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