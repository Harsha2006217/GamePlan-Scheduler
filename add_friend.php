<?php
// add_friend.php - Add Friend Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to add friends by username.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $friendUsername = $_POST['friend_username'] ?? '';
    $error = addFriend($userId, $friendUsername);
    if (!$error) {
        setMessage('success', 'Friend added successfully!');
        header("Location: index.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Friend - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Add Friend</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="friend_username" class="form-label">Friend's Username</label>
                <input type="text" id="friend_username" name="friend_username" class="form-control" required maxlength="50">
            </div>
            <button type="submit" class="btn btn-primary">Add Friend</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>