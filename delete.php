<?php
// delete.php: Generic delete with confirmation
require_once 'functions.php';
requireLogin();
$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $result = deleteItem($type, $id);
    if ($result === true) {
        setMessage('success', ucfirst($type) . ' deleted.');
    } else {
        setMessage('danger', $result);
    }
    header('Location: index.php');
    exit;
}
$msg = getMessage();
$item_name = '';  // Fetch name for confirmation
$pdo = getPDO();
if ($type == 'schedule') {
    $stmt = $pdo->prepare('SELECT game FROM schedules WHERE schedule_id = ? AND user_id = ?');
    $stmt->execute([$id, getUserId()]);
    $item = $stmt->fetch();
    $item_name = $item['game'] ?? 'Unknown';
} elseif ($type == 'event') {
    $stmt = $pdo->prepare('SELECT title FROM events WHERE event_id = ? AND user_id = ?');
    $stmt->execute([$id, getUserId()]);
    $item = $stmt->fetch();
    $item_name = $item['title'] ?? 'Unknown';
}
if (!$item_name) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete <?php echo ucfirst($type); ?> - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: sans-serif; }
        .container { max-width: 500px; margin: 100px auto; padding: 30px; background: #1e1e1e; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .btn-danger { background: #dc3545; border: none; }
        .btn-secondary { background: #6c757d; border: none; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo htmlspecialchars($msg['msg']); ?></div>
        <?php endif; ?>
        <h2 class="text-center mb-4">Delete <?php echo ucfirst($type); ?></h2>
        <p>Are you sure you want to delete <?php echo htmlspecialchars($item_name); ?>?</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
            <button type="submit" class="btn btn-danger w-100 mb-2">Yes, Delete</button>
        </form>
        <a href="index.php" class="btn btn-secondary w-100">No, Cancel</a>
    </div>
</body>
</html>