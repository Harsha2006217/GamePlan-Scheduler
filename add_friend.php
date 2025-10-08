<?php
// add_friend.php - Add Friend Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to add friends by username with note and status, and list with CRUD.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$friends = getFriends($userId);

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_friend'])) {
    $friendUsername = $_POST['friend_username'] ?? '';
    $note = $_POST['note'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $error = addFriend($userId, $friendUsername, $note, $status);
    if (!$error) {
        setMessage('success', 'Friend added!');
        header("Location: add_friend.php");
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
                <input type="text" id="friend_username" name="friend_username" class="form-control" required maxlength="50" aria-label="Friend's Username">
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" aria-label="Status">
                    <option value="Active">Active</option>
                    <option value="Pending">Pending</option>
                    <option value="Blocked">Blocked</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">Note (Optional)</label>
                <textarea id="note" name="note" class="form-control" rows="2" aria-label="Note"></textarea>
            </div>
            <button type="submit" name="add_friend" class="btn btn-primary">Add</button>
        </form>

        <h2 class="mt-4">My Friends List</h2>
        <table class="table table-dark table-bordered">
            <thead class="bg-info">
                <tr><th>Username</th><th>Status</th><th>Note</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($friends as $friend): ?>
                    <tr>
                        <td><?php echo safeEcho($friend['username']); ?></td>
                        <td><?php echo safeEcho($friend['status']); ?></td>
                        <td><?php echo safeEcho($friend['note']); ?></td>
                        <td>
                            <a href="edit_friend.php?id=<?php echo $friend['user_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete.php?type=friend&id=<?php echo $friend['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>