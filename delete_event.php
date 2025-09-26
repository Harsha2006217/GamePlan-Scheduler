<?php
// GamePlan Scheduler - Professional Event Deletion
// Secure event deletion with confirmation and validation

require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = getCurrentUserId();
$eventId = (int)($_GET['id'] ?? 0);

if (!$eventId) {
    header('Location: events.php');
    exit;
}

// Get event data for confirmation
$event = getEventById($eventId, $userId);
if (!$event) {
    header('Location: events.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        deleteEvent($eventId, $userId);

        $_SESSION['message'] = 'Event deleted successfully!';
        $_SESSION['message_type'] = 'success';

        header('Location: events.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Delete Event</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    <i class="fas fa-gamepad"></i> GamePlan Scheduler
                </a>
                <nav>
                    <ul class="d-flex">
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="friends.php">Friends</a></li>
                        <li><a href="schedules.php">Schedules</a></li>
                        <li><a href="events.php" class="active">Events</a></li>
                        <li><a href="?logout=1">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h2 class="mb-0"><i class="fas fa-trash"></i> Delete Event</h2>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Warning:</strong> This action cannot be undone. The event will be permanently deleted and removed from all shared friends' views.
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Event Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <strong>Title:</strong><br>
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </div>
                                        <div class="col-sm-6">
                                            <strong>Date & Time:</strong><br>
                                            <?php echo date('l, F j, Y \a\t g:i A', strtotime($event['date'] . ' ' . $event['time'])); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($event['description'])): ?>
                                        <div class="mt-3">
                                            <strong>Description:</strong><br>
                                            <?php echo htmlspecialchars($event['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($event['reminder'] && $event['reminder'] !== 'none'): ?>
                                        <div class="mt-3">
                                            <strong>Reminder:</strong><br>
                                            <?php echo $event['reminder'] == '1hour' ? '1 hour before' : '1 day before'; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($event['shared_with'])): ?>
                                        <div class="mt-3">
                                            <strong>Shared With:</strong><br>
                                            <?php
                                            $sharedList = explode(',', $event['shared_with']);
                                            foreach ($sharedList as $shared):
                                            ?>
                                                <span class="badge bg-info me-1"><?php echo htmlspecialchars(trim($shared)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($event['game_title']): ?>
                                        <div class="mt-3">
                                            <strong>Linked Game:</strong><br>
                                            <?php echo htmlspecialchars($event['game_title']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-3">
                                        <strong>Created:</strong><br>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($event['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted">
                                Are you sure you want to delete this event? This will remove it from your calendar and notify any friends it's shared with.
                            </p>

                            <form method="POST" action="">
                                <div class="d-flex gap-2">
                                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Yes, Delete Event
                                    </button>
                                    <a href="edit_event.php?id=<?php echo $eventId; ?>" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Edit Instead
                                    </a>
                                    <a href="events.php" class="btn btn-outline-secondary ms-auto">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>