<?php
// edit_friend.php - Edit Friend Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to edit friend note and status.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    header("Location: add_friend.php");
    exit;
}

$friends = getFriends($userId);
$friend = array_filter($friends, function($f) use ($id) { return $f['user_id'] == $id; });
$friend = reset($friend);
if (!$friend) {
    setMessage('danger', 'Friend not found.');
    header("Location: add_friend.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $note = $_POST['note'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $error = updateFriend($userId, $id, $note, $status);
    if (!$error) {
        setMessage('success', 'Friend updated!');
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
    <title>Edit Friend - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Edit Friend: <?php echo safeEcho($friend['username']); ?></h2>
        <form method="POST">
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" aria-label="Status">
                    <option value="Active" <?php if ($friend['status'] == 'Active') echo 'selected'; ?>>Active</option>
                    <option value="Pending" <?php if ($friend['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Blocked" <?php if ($friend['status'] == 'Blocked') echo 'selected'; ?>>Blocked</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">Note</label>
                <textarea id="note" name="note" class="form-control" rows="2" aria-label="Note"><?php echo safeEcho($friend['note']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>