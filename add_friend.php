<?php
// add_friend.php: Add friend form
require_once 'functions.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $friend_username = trim($_POST['friend_username'] ?? '');
    $result = addFriend($friend_username);
    if ($result === true) {
        setMessage('success', 'Friend added.');
        header('Location: index.php');
        exit;
    } else {
        setMessage('danger', $result);
    }
}
$msg = getMessage();
$friends = getFriends(getUserId());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Friend - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; }
        header { background: #1e1e1e; padding: 15px; text-align: center; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: sticky; top: 0; z-index: 1; }
        nav a { color: #fff; margin: 0 15px; text-decoration: none; }
        nav a:hover { color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .section { background: #2c2c2c; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-control { background: #2c2c2c; border: 1px solid #444; color: #fff; }
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
            <h3>Friends List</h3>
            <ul>
                <?php foreach ($friends as $friend): ?>
                    <li><?php echo htmlspecialchars($friend['username']); ?> - <?php echo $friend['status']; ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="add_friend.php" class="btn btn-primary">Add Friend</a>
        </div>

        <div class="section">
            <h3>Add Friend</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="friend_username" class="form-label">Friend's Username</label>
                    <input type="text" class="form-control" id="friend_username" name="friend_username" required>
                </div>
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
        </div>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy | Contact
    </footer>
</body>
</html>