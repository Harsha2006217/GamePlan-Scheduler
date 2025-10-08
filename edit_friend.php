<?php
// edit_friend.php - Edit Friend Note Page
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form to edit friend note.

require_once 'functions.php';

checkSessionTimeout();
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$id = $_GET['id'] ?? 0;
if (!is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$friends = getFriends($userId);
$friend = array_filter($friends, function($f) use ($id) { return $f['user_id'] == $id; });
$friend = reset($friend);
if (!$friend) {
    setMessage('danger', 'Friend not found.');
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $note = $_POST['note'] ?? '';
        $error = updateFriendNote($userId, $id, $note);
        if (!$error) {
            setMessage('success', 'Friend note updated!');
            header("Location: index.php");
            exit;
        }
    }
}

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Friend Note - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">
    <?php include 'header.php'; ?>

    <main class="container mt-5 pt-5">
        <?php echo getMessage(); ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo safeEcho($error); ?></div><?php endif; ?>

        <h2>Edit Note for <?php echo safeEcho($friend['username']); ?></h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo safeEcho($csrfToken); ?>">
            <div class="mb-3">
                <label for="note" class="form-label">Note</label>
                <textarea id="note" name="note" class="form-control" rows="2" aria-label="Note"><?php echo safeEcho($friend['note']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Note</button>
        </form>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>