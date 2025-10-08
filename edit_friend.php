<?php
// edit_friend.php - Edit Friend Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to edit friend username.

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
    $newUsername = $_POST['new_username'] ?? '';
    $error = updateFriend($userId, $id, $newUsername);
    if (!$error) {
        setMessage('success', 'Friend updated successfully!');
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

        <h2>Edit Friend</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="new_username" class="form-label">New Username</label>
                <input type="text" id="new_username" name="new_username" class="form-control" required maxlength="50" value="<?php echo safeEcho($friend['username']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>