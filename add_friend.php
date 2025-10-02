<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $friend_username = $_POST['friend_username'] ?? '';
    $result = addFriend($friend_username);
    if ($result === true) {
        setMessage('success', 'Friend added successfully.');
        header('Location: friends.php');
        exit;
    } else {
        setMessage('error', $result);
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
        /* Reuse styling */
        body { background-color: #121212; color: #ffffff; font-family: 'Sans-serif', Arial; margin: 0; padding: 0; }
        header { background: #1e1e1e; padding: 15px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        header nav a { color: #ffffff; margin: 0 15px; text-decoration: none; font-size: 1.1em; transition: color 0.3s; }
        header nav a:hover { color: #007bff; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-control { background: #2c2c2c; color: #fff; border: 1px solid #dddddd; }
        .btn-primary { background: #007bff; border: none; transition: background 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .alert { margin-bottom: 20px; border-radius: 5px; padding: 12px; }
        .alert-success { background: #28a745; }
        .alert-danger { background: #dc3545; }
        footer { background: #1e1e1e; padding: 10px; text-align: center; color: #aaaaaa; font-size: 0.9em; }
        @media (max-width: 768px) { .container { padding: 15px; } }
    </style>
</head>
<body>
    <header>
        <h1>GamePlan Scheduler</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="friends.php">Friends</a>
            <a href="add_schedule.php">Add Schedule</a>
            <a href="add_event.php">Add Event</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
        <h2>Add Friend</h2>
        <?php $msg = getMessage(); if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validateAddFriendForm();">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="friend_username" class="form-label">Friend's Username</label>
                <input type="text" class="form-control" id="friend_username" name="friend_username" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Friend</button>
        </form>
    </div>
    <footer>
        © 2025 GamePlan Scheduler by Harsha Kanaparthi | <a href="#" style="color: #aaaaaa;">Privacy</a> | <a href="#" style="color: #aaaaaa;">Contact</a>
    </footer>
    <script>
        // Client-side validation for add friend
        function validateAddFriendForm() {
            const username = document.getElementById('friend_username').value.trim();
            if (username === '') {
                alert('Username required.');
                return false;
            }
            if (!/^\w+$/.test(username)) {
                alert('Alphanumeric characters only.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>