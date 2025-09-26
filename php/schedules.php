<?php
session_start();
require_once 'functions.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$user_id = getCurrentUserId();
$schedules = getSchedules($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
    <header class="bg-dark text-white p-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-calendar"></i> Schedules</h1>
                <nav>
                    <a href="index.php" class="btn btn-outline-light me-2">Home</a>
                    <a href="add_schedule.php" class="btn btn-success me-2">Add Schedule</a>
                    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                </nav>
            </div>
        </div>
    </header>
    <main class="container my-4">
        <h2>Your Schedules</h2>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Friends</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($schedule['game_title']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['date']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['time']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['friends']); ?></td>
                        <td>
                            <a href="edit_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_schedule.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this schedule?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <footer class="bg-dark text-white text-center p-3">
        <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi.</p>
    </footer>
</body>
</html>