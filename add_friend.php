<?php
// add_friend.php: Form to add friends by username
// Validates input, adds friend if valid
// Dark theme, responsive, beautiful UI

require_once 'functions.php';
requireLogin();
checkTimeout();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCSRF();
    $friend_username = trim($_POST['friend_username'] ?? '');
    $result = addFriend($friend_username);
    if ($result === true) {
        setMessage('success', 'Friend added successfully.');
        header('Location: friends.php');
        exit;
    } else {
        setMessage('danger', $result);
    }
}
$msg = getMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Friend - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php"><i class="bi bi-controller me-2"></i>GamePlan Scheduler</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> mb-4">
                <i class="bi bi-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h3 class="section-title"><i class="bi bi-person-plus me-2"></i>Add Friend</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRF()); ?>">
                <div class="mb-3">
                    <label for="friend_username" class="form-label">Friend's Username</label>
                    <input type="text" class="form-control" id="friend_username" name="friend_username" required placeholder="Enter friend's username">
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Add Friend</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            © 2025 GamePlan Scheduler by Harsha Kanaparthi | Privacy Policy | Contact Support
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>